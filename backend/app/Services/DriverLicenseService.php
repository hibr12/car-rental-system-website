<?php

namespace App\Services;

use App\Exceptions\LicenseDocumentStorageException;
use App\Models\DriverLicense;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\AdminLicenseSubmitted;
use App\Notifications\LicenseApproved;
use App\Notifications\LicenseRejected;
use App\Notifications\LicenseSubmitted;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DriverLicenseService
{
    // Configurable via config/booking.php — days before expiry to warn customer.
    private const EXPIRY_WARNING_DAYS = [30, 14, 7];

    private const PENDING_SUBMISSION_UNIQUE_INDEX = 'driver_licenses_one_pending_per_user_unique';

    public function __construct(
        private AuditLogService $auditLogService,
        private NotificationRecipientService $notificationRecipients,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Customer: submit / replace
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create a new license/document submission for a customer.
     * If the customer already has an active license (pending/verified/rejected/expired),
     * that record is marked REPLACED and a new PENDING_REVIEW record is created.
     */
    public function submit(array $data, User $customer, ?UploadedFile $frontFile = null, ?UploadedFile $backFile = null): DriverLicense
    {
        if (!$customer->isCustomer()) {
            throw new \InvalidArgumentException('Only customers can submit an identity document.');
        }

        $documentType = $data['document_type'] ?? DriverLicense::DOCUMENT_TYPE_DRIVER_LICENSE;
        $storedDocuments = [];
        $stage = 'find_pending_submission';
        $previous = null;
        $previousStatus = null;

        try {
            $existingPending = $this->findPendingSubmission($customer);

            if ($existingPending) {
                return $this->markAsExistingSubmission($existingPending, $customer);
            }

            $stage = 'find_active_submission';
            $previous = DriverLicense::where('user_id', $customer->id)
                ->whereNotIn('status', [
                    DriverLicense::STATUS_PENDING_REVIEW,
                    DriverLicense::STATUS_REPLACED,
                ])
                ->latest()
                ->first();
            $previousStatus = $previous?->status;

            $frontPath = null;
            if ($frontFile) {
                $stage = 'store_front_document';
                $frontDisk = $this->licenseDisk();
                $frontPath = $this->storeDocument($frontFile, $customer->id, 'front', $frontDisk);
                $storedDocuments[] = ['disk' => $frontDisk, 'path' => $frontPath, 'side' => 'front'];
            }

            $backPath = null;
            if ($backFile) {
                $stage = 'store_back_document';
                $backDisk = $this->licenseDisk();
                $backPath = $this->storeDocument($backFile, $customer->id, 'back', $backDisk);
                $storedDocuments[] = ['disk' => $backDisk, 'path' => $backPath, 'side' => 'back'];
            }

            // Insert the pending submission before changing any previous record.
            // The partial unique index is the concurrency guard: a parallel
            // request loses this insert, removes its uploads, and returns the
            // pending row created by the winner.
            $stage = 'create_license';
            $license = DriverLicense::create([
                'user_id'             => $customer->id,
                'document_type'       => $documentType,
                'document_number'     => $data['document_number'] ?? $data['license_number'] ?? null,
                'full_name'           => $data['full_name'],
                'date_of_birth'       => $data['date_of_birth'] ?? null,
                'license_category'    => $data['license_category'] ?? null,
                'issue_date'          => $data['issue_date'] ?? null,
                'expiry_date'         => $data['expiry_date'] ?? null,
                'issuing_authority'   => $data['issuing_authority'] ?? null,
                'issuing_country'     => $data['issuing_country'] ?? null,
                'university_name'     => $data['university_name'] ?? null,
                'department'          => $data['department'] ?? null,
                'front_document_path' => $frontPath,
                'back_document_path'  => $backPath,
                'status'              => DriverLicense::STATUS_PENDING_REVIEW,
                'submitted_at'        => now(),
                'replaced_by'         => null,
            ]);
            $license->setAttribute('submission_was_created', true);
        } catch (Throwable $e) {
            $this->cleanupStoredDocuments($storedDocuments, $customer->id, $e);

            if ($this->isPendingSubmissionConflict($e)) {
                $existingPending = $this->findPendingSubmission($customer);

                if ($existingPending) {
                    return $this->markAsExistingSubmission($existingPending, $customer);
                }
            }

            Log::error('[DriverLicense] Submission failed', [
                'customer_id' => $customer->id,
                'stage' => $stage,
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        if ($previous && $previousStatus !== null) {
            try {
                $previousQuery = DriverLicense::query()
                    ->whereKey($previous->id)
                    ->where('user_id', $customer->id)
                    ->where('status', $previousStatus);

                if ($previous->replaced_by === null) {
                    $previousQuery->whereNull('replaced_by');
                } else {
                    $previousQuery->where('replaced_by', $previous->replaced_by);
                }

                $updated = $previousQuery->update([
                    'status' => DriverLicense::STATUS_REPLACED,
                    'replaced_by' => $license->id,
                    'updated_at' => now(),
                ]);

                if ($updated !== 1) {
                    Log::warning('[DriverLicense] Previous submission changed before it could be replaced', [
                        'customer_id' => $customer->id,
                        'license_id' => $license->id,
                        'previous_id' => $previous->id,
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('[DriverLicense] Failed to mark previous submission as replaced', [
                    'customer_id' => $customer->id,
                    'license_id' => $license->id,
                    'previous_id' => $previous->id,
                    'exception_class' => $e::class,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            $this->auditLogService->log(
                $customer,
                $previous ? 'license_resubmitted' : 'license_submitted',
                'driver_license',
                $license->id,
                $previousStatus ? ['status' => $previousStatus] : null,
                ['status' => DriverLicense::STATUS_PENDING_REVIEW, 'license_id' => $license->id],
                $previous ? 'Customer resubmitted driver\'s license.' : 'Customer submitted driver\'s license.',
            );
        } catch (Throwable $e) {
            Log::error('[DriverLicense] Submission committed but audit logging failed', [
                'customer_id' => $customer->id,
                'license_id' => $license->id,
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('[DriverLicense] Submitted', [
            'license_id' => $license->id,
            'customer_id' => $customer->id,
            'replaced_id' => $previous?->id,
        ]);

        $this->sendSubmissionNotifications($license, $customer);

        return $license;
    }

    /**
     * Update document images on a PENDING or REJECTED license (customer self-service).
     */
    public function updateDocuments(DriverLicense $license, User $customer, ?UploadedFile $frontFile = null, ?UploadedFile $backFile = null): DriverLicense
    {
        $this->assertOwnership($license, $customer);
        $this->assertDocumentsCanBeUpdated($license);

        if (! $frontFile && ! $backFile) {
            return $license->fresh();
        }

        $updates = [];
        $storedDocuments = [];
        $stage = 'store_replacement_documents';

        try {
            if ($frontFile) {
                $stage = 'store_front_replacement';
                $frontDisk = $this->licenseDisk();
                $frontPath = $this->storeDocument($frontFile, $customer->id, 'front', $frontDisk);
                $updates['front_document_path'] = $frontPath;
                $storedDocuments[] = ['disk' => $frontDisk, 'path' => $frontPath, 'side' => 'front'];
            }

            if ($backFile) {
                $stage = 'store_back_replacement';
                $backDisk = $this->licenseDisk();
                $backPath = $this->storeDocument($backFile, $customer->id, 'back', $backDisk);
                $updates['back_document_path'] = $backPath;
                $storedDocuments[] = ['disk' => $backDisk, 'path' => $backPath, 'side' => 'back'];
            }

            $stage = 'update_document_paths';
            $currentLicense = DriverLicense::query()->findOrFail($license->id);
            $this->assertOwnership($currentLicense, $customer);
            $this->assertDocumentsCanBeUpdated($currentLicense);

            $replacedDocuments = [];
            if (array_key_exists('front_document_path', $updates) && $currentLicense->front_document_path) {
                $replacedDocuments['front'] = $currentLicense->front_document_path;
            }
            if (array_key_exists('back_document_path', $updates) && $currentLicense->back_document_path) {
                $replacedDocuments['back'] = $currentLicense->back_document_path;
            }

            // Compare the paths and status we just read so a concurrent review or
            // replacement cannot be overwritten after the new files were uploaded.
            $updated = DriverLicense::query()
                ->whereKey($currentLicense->id)
                ->where('user_id', $customer->id)
                ->whereIn('status', [DriverLicense::STATUS_PENDING_REVIEW, DriverLicense::STATUS_REJECTED])
                ->where('front_document_path', $currentLicense->front_document_path)
                ->where('back_document_path', $currentLicense->back_document_path)
                ->update(array_merge($updates, ['updated_at' => now()]));

            if ($updated !== 1) {
                throw new \InvalidArgumentException('Your document status changed. Refresh the page and try again.');
            }

            $updatedLicense = $currentLicense->fresh();
        } catch (Throwable $e) {
            $this->cleanupStoredDocuments($storedDocuments, $customer->id, $e);

            Log::error('[DriverLicense] Document replacement failed', [
                'customer_id' => $customer->id,
                'license_id' => $license->id,
                'stage' => $stage,
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        foreach ($replacedDocuments as $side => $path) {
            $this->deleteReplacedDocument($path, $customer->id, $license->id, $side);
        }

        return $updatedLicense;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Admin / staff: review
    // ─────────────────────────────────────────────────────────────────────────

    public function approve(DriverLicense $license, User $reviewer): DriverLicense
    {
        $this->assertCanReview($reviewer);

        if ($license->status !== DriverLicense::STATUS_PENDING_REVIEW) {
            throw new \InvalidArgumentException('Only pending licenses can be approved.');
        }

        $old = $license->status;
        $verifiedAt = now();

        try {
            $updated = DriverLicense::query()
                ->whereKey($license->id)
                ->where('status', DriverLicense::STATUS_PENDING_REVIEW)
                ->update([
                    'status'      => DriverLicense::STATUS_VERIFIED,
                    'verified_by' => $reviewer->id,
                    'verified_at' => $verifiedAt,
                    'rejection_reason' => null,
                    'updated_at' => $verifiedAt,
                ]);
        } catch (Throwable $e) {
            Log::error('[DriverLicense] Approval transition failed', [
                'license_id' => $license->id,
                'reviewer_id' => $reviewer->id,
                'from_status' => $old,
                'to_status' => DriverLicense::STATUS_VERIFIED,
                'stage' => 'update_license',
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        if ($updated !== 1) {
            $currentStatus = DriverLicense::query()
                ->whereKey($license->id)
                ->value('status');

            Log::warning('[DriverLicense] Approval skipped because status changed', [
                'license_id' => $license->id,
                'reviewer_id' => $reviewer->id,
                'expected_status' => DriverLicense::STATUS_PENDING_REVIEW,
                'current_status' => $currentStatus,
            ]);

            throw new \InvalidArgumentException('This license is no longer pending review. Refresh the page and try again.');
        }

        $license->forceFill([
            'status' => DriverLicense::STATUS_VERIFIED,
            'verified_by' => $reviewer->id,
            'verified_at' => $verifiedAt,
            'rejection_reason' => null,
            'updated_at' => $verifiedAt,
        ]);
        $license->syncOriginal();
        $license->setRelation('reviewer', $reviewer);

        try {
            $this->auditLogService->log(
                $reviewer,
                'license_approved',
                'driver_license',
                $license->id,
                ['status' => $old],
                ['status' => DriverLicense::STATUS_VERIFIED],
                'License approved by reviewer.',
                $reviewer->branch_id,
            );
        } catch (Throwable $e) {
            Log::error('[DriverLicense] Approval committed but audit logging failed', [
                'license_id' => $license->id,
                'reviewer_id' => $reviewer->id,
                'from_status' => $old,
                'to_status' => DriverLicense::STATUS_VERIFIED,
                'stage' => 'write_audit_log',
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $license->user->notify(new LicenseApproved($license));
        } catch (Throwable $e) {
            Log::warning('[DriverLicense] Approval notification failed', [
                'license_id' => $license->id,
                'reviewer_id' => $reviewer->id,
                'stage' => 'notify_customer',
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('[DriverLicense] Approved', [
            'license_id' => $license->id,
            'reviewer_id' => $reviewer->id,
            'from_status' => $old,
            'to_status' => DriverLicense::STATUS_VERIFIED,
        ]);

        return $license;
    }

    public function reject(DriverLicense $license, User $reviewer, string $reason): DriverLicense
    {
        $this->assertCanReview($reviewer);

        if (trim($reason) === '') {
            throw new \InvalidArgumentException('A rejection reason is required.');
        }

        if ($license->status !== DriverLicense::STATUS_PENDING_REVIEW) {
            throw new \InvalidArgumentException('Only pending licenses can be rejected.');
        }

        $old = $license->status;
        $rejectedAt = now();

        try {
            $updated = DriverLicense::query()
                ->whereKey($license->id)
                ->where('status', DriverLicense::STATUS_PENDING_REVIEW)
                ->update([
                    'status'           => DriverLicense::STATUS_REJECTED,
                    'rejection_reason' => $reason,
                    'verified_by'      => $reviewer->id,
                    'verified_at'      => $rejectedAt,
                    'updated_at'       => $rejectedAt,
                ]);
        } catch (Throwable $e) {
            Log::error('[DriverLicense] Rejection transition failed', [
                'license_id' => $license->id,
                'reviewer_id' => $reviewer->id,
                'from_status' => $old,
                'to_status' => DriverLicense::STATUS_REJECTED,
                'stage' => 'update_license',
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        if ($updated !== 1) {
            $currentStatus = DriverLicense::query()
                ->whereKey($license->id)
                ->value('status');

            Log::warning('[DriverLicense] Rejection skipped because status changed', [
                'license_id' => $license->id,
                'reviewer_id' => $reviewer->id,
                'expected_status' => DriverLicense::STATUS_PENDING_REVIEW,
                'current_status' => $currentStatus,
            ]);

            throw new \InvalidArgumentException('This license is no longer pending review. Refresh the page and try again.');
        }

        $license->forceFill([
            'status' => DriverLicense::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'verified_by' => $reviewer->id,
            'verified_at' => $rejectedAt,
            'updated_at' => $rejectedAt,
        ]);
        $license->syncOriginal();
        $license->setRelation('reviewer', $reviewer);

        try {
            $this->auditLogService->log(
                $reviewer,
                'license_rejected',
                'driver_license',
                $license->id,
                ['status' => $old],
                ['status' => DriverLicense::STATUS_REJECTED, 'reason' => $reason],
                'License rejected: ' . Str::limit($reason, 100),
                $reviewer->branch_id,
            );
        } catch (Throwable $e) {
            Log::error('[DriverLicense] Rejection committed but audit logging failed', [
                'license_id' => $license->id,
                'reviewer_id' => $reviewer->id,
                'from_status' => $old,
                'to_status' => DriverLicense::STATUS_REJECTED,
                'stage' => 'write_audit_log',
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $license->user->notify(new LicenseRejected($license, $reason));
        } catch (Throwable $e) {
            Log::warning('[DriverLicense] Rejection notification failed', [
                'license_id' => $license->id,
                'reviewer_id' => $reviewer->id,
                'stage' => 'notify_customer',
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('[DriverLicense] Rejected', [
            'license_id' => $license->id,
            'reviewer_id' => $reviewer->id,
            'from_status' => $old,
            'to_status' => DriverLicense::STATUS_REJECTED,
        ]);

        return $license;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Queries
    // ─────────────────────────────────────────────────────────────────────────

    public function getActiveLicense(User $customer): ?DriverLicense
    {
        return DriverLicense::where('user_id', $customer->id)
            ->whereNotIn('status', [DriverLicense::STATUS_REPLACED])
            ->latest()
            ->first();
    }

    public function getLicenseById(int $id): DriverLicense
    {
        return DriverLicense::with(['user', 'reviewer'])->findOrFail($id);
    }

    /**
     * Paginated list for admin/staff review queue with optional filters.
     */
    public function getReviewQueue(User $actor, array $filters = []): LengthAwarePaginator
    {
        $query = DriverLicense::with(['user:id,name,email,phone', 'reviewer:id,name,role'])
            ->whereNotIn('status', [DriverLicense::STATUS_REPLACED]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            // Default: show pending first
            $query->orderByRaw("CASE WHEN status = 'pending_review' THEN 0 ELSE 1 END");
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'ilike', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%"));
            });
        }

        if (!empty($filters['category'])) {
            $query->where('license_category', $filters['category']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 20), 50);

        return $query->orderByDesc('submitted_at')->paginate($perPage);
    }

    public function summaryCounts(): array
    {
        return [
            'pending'  => DriverLicense::where('status', DriverLicense::STATUS_PENDING_REVIEW)->count(),
            'verified' => DriverLicense::where('status', DriverLicense::STATUS_VERIFIED)->count(),
            'rejected' => DriverLicense::where('status', DriverLicense::STATUS_REJECTED)->count(),
            'expired'  => DriverLicense::where('status', DriverLicense::STATUS_VERIFIED)
                ->where('expiry_date', '<', now()->toDateString())
                ->count(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Booking eligibility
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Checks whether the customer's active license satisfies eligibility for a vehicle.
     * Returns ['eligible' => bool, 'reason' => string].
     */
    public function checkEligibility(User $customer, ?Vehicle $vehicle = null): array
    {
        $license = $this->getActiveLicense($customer);

        if (!$license) {
            return [
                'eligible' => false,
                'code'     => 'LICENSE_NOT_SUBMITTED',
                'reason'   => 'No driver\'s license has been submitted. Please upload your license before booking.',
            ];
        }

        $effective = $license->effectiveStatus();

        if ($effective === DriverLicense::STATUS_PENDING_REVIEW) {
            return [
                'eligible' => false,
                'code'     => 'DOCUMENT_PENDING',
                'reason'   => 'Your identity document is awaiting verification. You can continue once it has been approved.',
            ];
        }

        if ($effective === DriverLicense::STATUS_REJECTED) {
            return [
                'eligible' => false,
                'code'     => 'DOCUMENT_REJECTED',
                'reason'   => 'Your identity document was rejected. Please upload a valid document.',
            ];
        }

        if ($effective === DriverLicense::STATUS_EXPIRED) {
            return [
                'eligible' => false,
                'code'     => 'DOCUMENT_EXPIRED',
                'reason'   => 'Your identity document has expired. Please upload your renewed document.',
            ];
        }

        if ($effective !== DriverLicense::STATUS_VERIFIED) {
            return [
                'eligible' => false,
                'code'     => 'DOCUMENT_INVALID',
                'reason'   => 'Your identity document is not in a valid state for booking.',
            ];
        }

        // Vehicle-specific checks.
        if ($vehicle) {
            // Requires license at all?
            if (!($vehicle->requires_license ?? true)) {
                return ['eligible' => true, 'code' => 'OK', 'reason' => 'No identity document required for this vehicle.'];
            }

            // Category compatibility (only for driver's license).
            if ($license->isDriverLicense()) {
                $requiredCategory = $vehicle->required_license_category;
                if ($requiredCategory && !$license->categoryCovers($requiredCategory)) {
                    return [
                        'eligible' => false,
                        'code'     => 'LICENSE_CATEGORY_MISMATCH',
                        'reason'   => "Your license category ({$license->license_category}) does not qualify for this vehicle (requires: {$requiredCategory}).",
                    ];
                }

                // Minimum holding period (configurable, defaults to 0 = disabled).
                $minHoldingDays = (int) config('booking.minimum_license_holding_days', 0);
                if ($minHoldingDays > 0) {
                    $heldDays = (int) $license->issue_date->diffInDays(now());
                    if ($heldDays < $minHoldingDays) {
                        $monthsRequired = (int) round($minHoldingDays / 30);
                        return [
                            'eligible' => false,
                            'code'     => 'LICENSE_HOLDING_PERIOD',
                            'reason'   => "Your license must have been held for at least {$monthsRequired} month(s) before renting this vehicle.",
                        ];
                    }
                }
            }
        }

        $docTypeName = $license->getDocumentTypeDisplayName();
        return ['eligible' => true, 'code' => 'OK', 'reason' => "{$docTypeName} verified and eligible."];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Secure document access
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate a temporary URL or return raw contents for authorized document access.
     * Returns ['content' => StreamInterface, 'mime' => string, 'filename' => string].
     */
    public function getDocumentStream(DriverLicense $license, string $side, User $accessor): array
    {
        $this->assertCanViewDocument($license, $accessor);

        $path = match ($side) {
            'front' => $license->front_document_path,
            'back'  => $license->back_document_path,
            default => throw new \InvalidArgumentException('Side must be front or back.'),
        };

        if (!$path) {
            throw new \RuntimeException('Document not found.');
        }

        $disk = $this->resolveDocumentDisk($path);

        if (!$disk) {
            throw new \RuntimeException('Document not found.');
        }

        $content  = Storage::disk($disk)->get($path);
        $mime     = Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';
        $filename = $side . '_license_' . $license->id . '.' . pathinfo($path, PATHINFO_EXTENSION);

        return compact('content', 'mime', 'filename');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function findPendingSubmission(User $customer): ?DriverLicense
    {
        return DriverLicense::query()
            ->where('user_id', $customer->id)
            ->where('status', DriverLicense::STATUS_PENDING_REVIEW)
            ->whereNull('deleted_at')
            ->first();
    }

    private function markAsExistingSubmission(DriverLicense $license, User $customer): DriverLicense
    {
        Log::info('[DriverLicense] Duplicate submission blocked — returning existing pending license', [
            'license_id' => $license->id,
            'customer_id' => $customer->id,
        ]);

        $license->setAttribute('submission_was_created', false);

        return $license;
    }

    private function isPendingSubmissionConflict(Throwable $exception): bool
    {
        if (! $exception instanceof UniqueConstraintViolationException) {
            return false;
        }

        $message = $exception->getMessage();

        return str_contains($message, self::PENDING_SUBMISSION_UNIQUE_INDEX)
            || (str_contains($message, 'driver_licenses.user_id')
                && str_contains($message, 'UNIQUE constraint failed'));
    }

    private function licenseDisk(): string
    {
        if (!config('services.cloudinary.enabled')) {
            // Local disk on Render is wiped on every redeploy — license photos
            // stored here won't survive. Not changing behavior, just making
            // sure this doesn't fail silently if Cloudinary was meant to be on.
            Log::warning('Cloudinary disabled — storing license document on ephemeral local disk.');

            return 'local';
        }

        return 'cloudinary';
    }

    /**
     * Resolve which disk holds an existing document (supports local → cloudinary migration).
     */
    private function resolveDocumentDisk(string $path): ?string
    {
        if (config('services.cloudinary.enabled') && Storage::disk('cloudinary')->exists($path)) {
            return 'cloudinary';
        }

        if (Storage::disk('local')->exists($path)) {
            return 'local';
        }

        return null;
    }

    private function storeDocument(UploadedFile $file, int $customerId, string $side, ?string $disk = null): string
    {
        $disk ??= $this->licenseDisk();

        try {
            $folder = config('services.cloudinary.license_folder', 'apex-rentals/licenses');
            $ext  = $file->getClientOriginalExtension();
            $safe = ($disk === 'cloudinary')
                ? $folder . '/' . $customerId . '/' . $side . '_' . Str::random(32) . '.' . $ext
                : 'licenses/' . $customerId . '/' . $side . '_' . Str::random(32) . '.' . $ext;

            $path = $file->storeAs(dirname($safe), basename($safe), $disk);

            if (!is_string($path) || $path === '') {
                throw new RuntimeException('The filesystem write did not return a stored path.');
            }

            return $path;
        } catch (Throwable $e) {
            Log::error('[DriverLicense] Failed to store document', [
                'customer_id' => $customerId,
                'side' => $side,
                'disk' => $disk,
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
            ]);

            throw new LicenseDocumentStorageException($e);
        }
    }

    /**
     * @param array<int, array{disk: string, path: string, side: string}> $documents
     */
    private function cleanupStoredDocuments(array $documents, int $customerId, Throwable $cause): void
    {
        foreach (array_reverse($documents) as $document) {
            try {
                $deleted = Storage::disk($document['disk'])->delete($document['path']);

                if (!$deleted) {
                    Log::warning('[DriverLicense] Failed to clean up stored document', [
                        'customer_id' => $customerId,
                        'side' => $document['side'],
                        'disk' => $document['disk'],
                        'failure_class' => $cause::class,
                    ]);
                }
            } catch (Throwable $cleanupError) {
                Log::warning('[DriverLicense] Stored document cleanup threw an exception', [
                    'customer_id' => $customerId,
                    'side' => $document['side'],
                    'disk' => $document['disk'],
                    'failure_class' => $cause::class,
                    'cleanup_exception_class' => $cleanupError::class,
                    'cleanup_error' => $cleanupError->getMessage(),
                ]);
            }
        }
    }

    private function sendSubmissionNotifications(DriverLicense $license, User $customer): void
    {
        try {
            $customer->notify(new LicenseSubmitted($license));
        } catch (Throwable $e) {
            Log::warning('[DriverLicense] Failed to send customer notification', [
                'license_id' => $license->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $notificationLicense = clone $license;
            $notificationLicense->loadMissing('user');

            foreach ($this->notificationRecipients->adminsAndBranchManagers() as $recipient) {
                $recipient->notify(new AdminLicenseSubmitted($notificationLicense));
            }
        } catch (Throwable $e) {
            Log::warning('[DriverLicense] Failed to send admin notifications', [
                'license_id' => $license->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function deleteDocument(?string $path): bool
    {
        if (!$path) {
            return true;
        }

        $disk = $this->resolveDocumentDisk($path);

        if ($disk) {
            return Storage::disk($disk)->delete($path);
        }

        return true;
    }

    private function deleteReplacedDocument(string $path, int $customerId, int $licenseId, string $side): void
    {
        try {
            if (!$this->deleteDocument($path)) {
                Log::warning('[DriverLicense] Failed to delete replaced document', [
                    'customer_id' => $customerId,
                    'license_id' => $licenseId,
                    'side' => $side,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('[DriverLicense] Replaced document cleanup threw an exception', [
                'customer_id' => $customerId,
                'license_id' => $licenseId,
                'side' => $side,
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function assertOwnership(DriverLicense $license, User $customer): void
    {
        if ((int) $license->user_id !== (int) $customer->id) {
            throw new \InvalidArgumentException('You are not authorized to access this license.');
        }
    }

    private function assertDocumentsCanBeUpdated(DriverLicense $license): void
    {
        if (!in_array($license->status, [DriverLicense::STATUS_PENDING_REVIEW, DriverLicense::STATUS_REJECTED], true)) {
            throw new \InvalidArgumentException('Documents can only be updated on a pending or rejected license.');
        }
    }

    private function assertCanReview(User $reviewer): void
    {
        if (!$reviewer->isAdmin() && !$reviewer->isBranchManager() && !$reviewer->isStaff()) {
            throw new \InvalidArgumentException('You are not authorized to review driver\'s licenses.');
        }
    }

    private function assertCanViewDocument(DriverLicense $license, User $accessor): void
    {
        if ($accessor->isAdmin()) {
            return;
        }

        if ($accessor->isBranchManager() || $accessor->isStaff()) {
            return; // Branch staff can view for review purposes.
        }

        if ($accessor->isCustomer() && (int) $license->user_id === (int) $accessor->id) {
            return;
        }

        throw new \InvalidArgumentException('You are not authorized to view this document.');
    }
}
