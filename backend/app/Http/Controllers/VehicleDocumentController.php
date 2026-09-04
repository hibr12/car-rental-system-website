<?php

namespace App\Http\Controllers;

use App\Http\Resources\VehicleDocumentResource;
use App\Models\VehicleDocument;
use App\Services\BranchScopeService;
use App\Services\VehicleDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleDocumentController extends Controller
{
    public function __construct(
        private VehicleDocumentService $documentService,
        private BranchScopeService $branchScope
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = VehicleDocument::with(['vehicle.branch', 'creator'])->latest();

        if ($this->branchScope->isBranchScoped($user) && $user->branch_id) {
            $query->whereHas('vehicle', fn ($q) => $q->where('branch_id', $user->branch_id));
        } elseif ($request->filled('branch_id') && $user->hasCompanyWideAccess()) {
            $query->whereHas('vehicle', fn ($q) => $q->where('branch_id', (int) $request->input('branch_id')));
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', (int) $request->input('vehicle_id'));
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->input('document_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $records = $query->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => VehicleDocumentResource::collection($records),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'document_type' => ['required', 'string', 'in:registration,insurance,inspection_certificate,roadworthiness,other'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'attachment_url' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_required' => ['nullable', 'boolean'],
        ]);

        $document = $this->documentService->create($validated, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Document created successfully.',
            'data' => new VehicleDocumentResource($document),
        ], 201);
    }

    public function update(Request $request, VehicleDocument $document): JsonResponse
    {
        $validated = $request->validate([
            'document_type' => ['sometimes', 'string', 'in:registration,insurance,inspection_certificate,roadworthiness,other'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'attachment_url' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_required' => ['nullable', 'boolean'],
        ]);

        $updated = $this->documentService->update($document, $validated, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Document updated successfully.',
            'data' => new VehicleDocumentResource($updated),
        ]);
    }

    public function destroy(VehicleDocument $document): JsonResponse
    {
        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.',
        ]);
    }
}
