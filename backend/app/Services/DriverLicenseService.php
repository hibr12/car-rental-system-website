<?php

namespace App\Services;

use App\Models\DriverLicense;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\LicenseApproved;
use App\Notifications\LicenseRejected;
use App\Notifications\LicenseSubmitted;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DriverLicenseService
{
    // Configurable via config/booking.php — days before expiry to warn customer.
    private const EXPIRY_WARNING_DAYS = [30, 14, 7];

    public function __construct(
        private AuditLogService $auditLogService,
        private NotificationRecipientService $notificationRecipients,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Customer: submit / replace
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create a new license submission for a customer.
     * If the customer already has an active license (pending/verified/rejected/expired),
     * that record is marked REPLACED and a new PENDING_REVIEW record is created.
     */
    public function submit(array $data, User $customer, ?UploadedFile $frontFile = null, ?UploadedFile $backFile = null): DriverLicense
    {
        if (!$customer->isCustomer()) {
            throw new \InvalidArgumentException('Only customers can submit a driver\'s license.');
        }

        return DB::transaction(function () use ($data, $customer, $frontFile, $backFile) {
            // Mark any existing active license as replaced.
            $previous = $this->getActiveLicense($customer);
            if ($previous) {
                $previous->update(['status' => DriverLicense::STATUS_REPLACED]);
            }

            $frontPath = $frontFile ? $this->storeDocument($frontFile, $customer->id, 'front') : null;
            $backPath  = $backFile  ? $this->storeDocument($backFile,  $customer->id, 'back')  : null;

            $license = DriverLicense::create([
                'user_id'           => $customer->id,
                'license_number'    => $data['license_number'],
                'full_name'         => $data['full_name'],
                'date_of_birth'     => $data['date_of_birth'] ?? null,
                'license_category'  => $data['license_category'] ?? DriverLicense::CATEGORY_AUTOMOBILE,
                'issue_date'        => $data['issue_date'],
                'expiry_date'       => $data['expiry_date'],
                'issuing_authority' => $data['issuing_authority'] ?? null,
                'issuing_country'   => $data['issuing_country'] ?? null,
                'front_document_path' => $frontPath,
                'back_document_path'  => $backPath,
                'status'            => DriverLicense::STATUS_PENDING_REVIEW,
                'submitted_at'      => now(),
                'replaced_by'       => null,
            ]);

            if ($previous) {
                $previous->update(['replaced_by' => $license->id]);
            }

            $this->auditLogService->log(
                $customer,
                $previous ? 'license_resubmitted' : 'license_submitted',
                'driver_license',
                $license->id,
                $previous ? ['status' => $previous->status] : null,
                ['status' => DriverLicense::STATUS_PENDING_REVIEW, 'license_id' => $license->id],
                $previous ? 'Customer resubmitted driver\'s license.' : 'Customer submitted driver\'s license.',
            );

            $customer->notify(new LicenseSubmitted($license));

            foreach ($this->notificationRecipients->adminsAndBranchManagers() as $recipient) {
                $recipient->notify(new AdminLicenseSubmitted($license->loadMissing('user')));
            }

            Log::info('[DriverLicense] Submitted', [
                'license_id' => $license->id,
                'customer_id' => $customer->id,
                'replaced_id' => $previous?->id,
            ]);

            return $license->fresh();
        });
    }

    /**
     * Update document images on a PENDING or REJECTED license (customer self-service).
     */
    public function updateDocuments(DriverLicense $license, User $customer, ?UploadedFile $frontFile = null, ?UploadedFile $backFile = null): DriverLicense
    {
        $this->assertOwnership($license, $customer);

        if (!in_array($license->status, [DriverLicense::STATUS_PENDING_REVIEW, DriverLicense::STATUS_REJECTED], true)) {
            throw new \InvalidArgumentException('Documents can only be updated on a pending or rejected license.');
        }

        $updates = [];

        if ($frontFile) {
            $this->deleteDocument($license->front_document_path);
            $updates['front_document_path'] = $this->storeDocument($frontFile, $customer->id, 'front');
        }

        if ($backFile) {
            $this->deleteDocument($license->back_document_path);
            $updates['back_document_path'] = $this->storeDocument($backFile, $customer->id, 'back');
        }

        if ($updates) {
            $license->update($updates);
        }

        return $license->fresh();
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

        return DB::transaction(function () use ($license, $reviewer) {
            $old = $license->status;

            $license->update([
                'status'      => DriverLicense::STATUS_VERIFIED,
                'verified_by' => $reviewer->id,
                'verified_at' => now(),
                'rejection_reason' => null,
            ]);

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

            $license->user->notify(new LicenseApproved($license));

            Log::info('[DriverLicense] Approved', [
                'license_id' => $license->id,
                'reviewer_id' => $reviewer->id,
            ]);

            return $license->fresh(['user', 'reviewer']);
        });
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

        return DB::transaction(function () use ($license, $reviewer, $reason) {
            $old = $license->status;

            $license->update([
                'status'           => DriverLicense::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'verified_by'      => $reviewer->id,
                'verified_at'      => now(),
            ]);

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

            $license->user->notify(new LicenseRejected($license, $reason));

            Log::info('[DriverLicense] Rejected', [
                'license_id' => $license->id,
                'reviewer_id' => $reviewer->id,
            ]);

            return $license->fresh(['user', 'reviewer']);
        });
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
                'code'     => 'LICENSE_PENDING',
                'reason'   => 'Your driver\'s license is awaiting verification. You can continue once it has been approved.',
            ];
        }

        if ($effective === DriverLicense::STATUS_REJECTED) {
            return [
                'eligible' => false,
                'code'     => 'LICENSE_REJECTED',
                'reason'   => 'Your driver\'s license was rejected. Please upload a valid document.',
            ];
        }

        if ($effective === DriverLicense::STATUS_EXPIRED) {
            return [
                'eligible' => false,
                'code'     => 'LICENSE_EXPIRED',
                'reason'   => 'Your driver\'s license has expired. Please upload your renewed license.',
            ];
        }

        if ($effective !== DriverLicense::STATUS_VERIFIED) {
            return [
                'eligible' => false,
                'code'     => 'LICENSE_INVALID',
                'reason'   => 'Your driver\'s license is not in a valid state for booking.',
            ];
        }

        // Vehicle-specific checks.
        if ($vehicle) {
            // Requires license at all?
            if (!($vehicle->requires_license ?? true)) {
                return ['eligible' => true, 'code' => 'OK', 'reason' => 'No license required for this vehicle.'];
            }

            // Category compatibility.
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

        return ['eligible' => true, 'code' => 'OK', 'reason' => 'License verified and eligible.'];
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

    private function licenseDisk(): string
    {
        return config('services.cloudinary.enabled') ? 'cloudinary' : 'local';
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

    private function storeDocument(UploadedFile $file, int $customerId, string $side): string
    {
        $disk = $this->licenseDisk();
        $folder = config('services.cloudinary.license_folder', 'apex-rentals/licenses');
        $ext  = $file->getClientOriginalExtension();
        $safe = ($disk === 'cloudinary')
            ? $folder . '/' . $customerId . '/' . $side . '_' . Str::random(32)
            : 'licenses/' . $customerId . '/' . $side . '_' . Str::random(32) . '.' . $ext;

        Storage::disk($disk)->put($safe, file_get_contents($file->getRealPath()));

        return $safe;
    }

    private function deleteDocument(?string $path): void
    {
        if (!$path) {
            return;
        }

        $disk = $this->resolveDocumentDisk($path);

        if ($disk) {
            Storage::disk($disk)->delete($path);
        }
    }

    private function assertOwnership(DriverLicense $license, User $customer): void
    {
        if ((int) $license->user_id !== (int) $customer->id) {
            throw new \InvalidArgumentException('You are not authorized to access this license.');
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
