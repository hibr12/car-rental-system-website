<?php

namespace App\Http\Controllers;

use App\Http\Resources\DriverLicenseResource;
use App\Models\DriverLicense;
use App\Services\DriverLicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriverLicenseController extends Controller
{
    public function __construct(private DriverLicenseService $licenseService) {}

    // ── Customer ──────────────────────────────────────────────────────────────

    /**
     * GET /api/customer/license
     * Return the authenticated customer's active license.
     */
    public function myLicense(Request $request): JsonResponse
    {
        $license = $this->licenseService->getActiveLicense($request->user());

        if (!$license) {
            return response()->json([
                'success' => true,
                'data'    => null,
                'message' => 'No driver\'s license found.',
            ]);
        }

        Gate::authorize('view', $license);

        return response()->json([
            'success' => true,
            'data'    => new DriverLicenseResource($license->load('reviewer')),
        ]);
    }

/**
     * POST /api/customer/license
     * Submit (or re-submit) a driver's license / identity document.
     * Supports front/back upload for driver's license, or single-file upload for other types.
     */
    public function submit(Request $request): JsonResponse
    {
        Gate::authorize('create', DriverLicense::class);

        $documentType = $request->input('document_type', DriverLicense::DOCUMENT_TYPE_DRIVER_LICENSE);

        // Base rules that always apply
        $rules = [
            'document_type'    => ['required', 'string', 'in:' . implode(',', DriverLicense::DOCUMENT_TYPES)],
            'full_name'        => ['required', 'string', 'max:200'],
            'front_document'   => ['required', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
        ];

        // Document type specific validation - back_document is only required for driver's license
        $requiresBack = $documentType === DriverLicense::DOCUMENT_TYPE_DRIVER_LICENSE;

        $rules['back_document'] = $requiresBack ? ['required', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'] : ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'];

        // Document type specific additional fields
        switch ($documentType) {
            case DriverLicense::DOCUMENT_TYPE_DRIVER_LICENSE:
                $rules = array_merge($rules, [
                    'document_number'   => ['required', 'string', 'max:100'],
                    'license_category'  => ['required', 'string', 'in:' . implode(',', DriverLicense::CATEGORIES)],
                    'issue_date'        => ['required', 'date', 'before_or_equal:today'],
                    'expiry_date'       => ['required', 'date', 'after:today'],
                    'issuing_authority' => ['nullable', 'string', 'max:200'],
                    'issuing_country'   => ['nullable', 'string', 'max:100'],
                    'date_of_birth'     => ['nullable', 'date', 'before:today'],
                ]);
                break;

            case DriverLicense::DOCUMENT_TYPE_NATIONAL_ID:
                $rules = array_merge($rules, [
                    'document_number'   => ['required', 'string', 'max:100'],
                    'date_of_birth'     => ['nullable', 'date', 'before:today'],
                    'issue_date'        => ['nullable', 'date', 'before_or_equal:today'],
                    'expiry_date'       => ['nullable', 'date', 'after:today'],
                    'issuing_authority' => ['nullable', 'string', 'max:200'],
                    'issuing_country'   => ['nullable', 'string', 'max:100'],
                ]);
                break;

            case DriverLicense::DOCUMENT_TYPE_UNIVERSITY_ID:
                $rules = array_merge($rules, [
                    'document_number'   => ['required', 'string', 'max:100'],
                    'university_name'   => ['nullable', 'string', 'max:200'],
                    'department'        => ['nullable', 'string', 'max:200'],
                    'date_of_birth'     => ['nullable', 'date', 'before:today'],
                    'issue_date'        => ['nullable', 'date', 'before_or_equal:today'],
                    'expiry_date'       => ['nullable', 'date', 'after:today'],
                ]);
                break;

            default:
                // No additional fields required for unknown types
                break;
        }

        $validated = $request->validate($rules);

        try {
            $license = $this->licenseService->submit(
                $validated,
                $request->user(),
                $request->file('front_document'),
                $request->file('back_document'),
            );

            // Fire LicenseSubmitted event
            event(new \App\Events\LicenseSubmitted($license));

            return response()->json([
                'success' => true,
                'message' => 'Identity document submitted for verification.',
                'data'    => new DriverLicenseResource($license),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/customer/license/documents
     * Update documents on a pending/rejected license (without full re-submission).
     */
    public function updateDocuments(Request $request): JsonResponse
    {
        $license = $this->licenseService->getActiveLicense($request->user());

        if (!$license) {
            return response()->json(['success' => false, 'message' => 'No active license found.'], 404);
        }

        Gate::authorize('update', $license);

        $request->validate([
            'front_document' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
            'back_document'  => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
        ]);

        try {
            $license = $this->licenseService->updateDocuments(
                $license,
                $request->user(),
                $request->file('front_document'),
                $request->file('back_document'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Documents updated.',
                'data'    => new DriverLicenseResource($license),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/customer/license/document/{license}/{side}
     * Serve the front or back document securely (authenticated, owner only).
     */
    public function serveDocument(Request $request, DriverLicense $license, string $side): StreamedResponse|JsonResponse
    {
        Gate::authorize('viewDocument', $license);

        try {
            $doc = $this->licenseService->getDocumentStream($license, $side, $request->user());

            return response()->stream(function () use ($doc) {
                echo $doc['content'];
            }, 200, [
                'Content-Type'        => $doc['mime'],
                'Content-Disposition' => 'inline; filename="' . $doc['filename'] . '"',
                'Cache-Control'       => 'private, no-store',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => 'Document not available.'], 404);
        }
    }

    // ── Admin / Staff ─────────────────────────────────────────────────────────

    /**
     * GET /api/admin/licenses
     * Paginated review queue with filters.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', DriverLicense::class);

        $filters = $request->only(['status', 'search', 'category', 'per_page']);
        $licenses = $this->licenseService->getReviewQueue($request->user(), $filters);

        return response()->json([
            'success' => true,
            'data'    => DriverLicenseResource::collection($licenses),
            'summary' => $this->licenseService->summaryCounts(),
            'meta'    => [
                'current_page' => $licenses->currentPage(),
                'last_page'    => $licenses->lastPage(),
                'per_page'     => $licenses->perPage(),
                'total'        => $licenses->total(),
            ],
        ]);
    }

    /**
     * GET /api/admin/licenses/{license}
     */
    public function show(DriverLicense $license): JsonResponse
    {
        Gate::authorize('view', $license);

        return response()->json([
            'success' => true,
            'data'    => new DriverLicenseResource($license->load(['user', 'reviewer'])),
        ]);
    }

    /**
     * POST /api/admin/licenses/{license}/approve
     */
    public function approve(Request $request, DriverLicense $license): JsonResponse
    {
        Gate::authorize('approve', $license);

        try {
            $approved = $this->licenseService->approve($license, $request->user());

            // Fire LicenseApproved event
            event(new \App\Events\LicenseApproved($approved));

            return response()->json([
                'success' => true,
                'message' => 'Identity document approved.',
                'data'    => new DriverLicenseResource($approved),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/admin/licenses/{license}/reject
     */
    public function reject(Request $request, DriverLicense $license): JsonResponse
    {
        Gate::authorize('reject', $license);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        try {
            $rejected = $this->licenseService->reject($license, $request->user(), $data['reason']);

            // Fire LicenseRejected event
            event(new \App\Events\LicenseRejected($rejected, $data['reason']));

            return response()->json([
                'success' => true,
                'message' => 'Identity document rejected.',
                'data'    => new DriverLicenseResource($rejected),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/customer/license/eligibility
     * Returns the customer's identity document eligibility for an optional vehicle.
     */
    public function eligibility(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isCustomer()) {
            return response()->json(['success' => false, 'message' => 'Only customers can check document eligibility.'], 403);
        }

        $vehicle = null;
        if ($request->filled('vehicle_id')) {
            $vehicle = \App\Models\Vehicle::find($request->integer('vehicle_id'));
        }

        $result = $this->licenseService->checkEligibility($user, $vehicle);

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }
}
