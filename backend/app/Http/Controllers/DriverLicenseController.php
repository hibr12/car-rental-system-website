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
     * Submit (or re-submit) a driver's license.
     */
    public function submit(Request $request): JsonResponse
    {
        Gate::authorize('create', DriverLicense::class);

        $validated = $request->validate([
            'license_number'    => ['required', 'string', 'max:100'],
            'full_name'         => ['required', 'string', 'max:200'],
            'date_of_birth'     => ['nullable', 'date', 'before:today'],
            'license_category'  => ['required', 'string', 'in:' . implode(',', DriverLicense::CATEGORIES)],
            'issue_date'        => ['required', 'date', 'before_or_equal:today'],
            'expiry_date'       => ['required', 'date', 'after:today'],
            'issuing_authority' => ['nullable', 'string', 'max:200'],
            'issuing_country'   => ['nullable', 'string', 'max:100'],
            'front_document'    => ['required', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
            'back_document'     => ['required', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
        ]);

        try {
            $license = $this->licenseService->submit(
                $validated,
                $request->user(),
                $request->file('front_document'),
                $request->file('back_document'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Driver\'s license submitted for verification.',
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

            return response()->json([
                'success' => true,
                'message' => 'Driver\'s license approved.',
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

            return response()->json([
                'success' => true,
                'message' => 'Driver\'s license rejected.',
                'data'    => new DriverLicenseResource($rejected),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/customer/license/eligibility
     * Returns the customer's license eligibility for an optional vehicle.
     */
    public function eligibility(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isCustomer()) {
            return response()->json(['success' => false, 'message' => 'Only customers can check license eligibility.'], 403);
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
