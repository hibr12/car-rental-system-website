<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Models\VehicleTransfer;
use App\Services\BranchScopeService;
use App\Services\VehicleStatusService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VehicleController extends Controller
{
    public function __construct(
        private VehicleStatusService $vehicleStatusService,
        private BranchScopeService $branchScope
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $relations = ['category', 'images', 'primaryImage', 'branch'];

        if ($user && !$user->isCustomer()) {
            $relations[] = 'activeTransfer.fromBranch';
            $relations[] = 'activeTransfer.toBranch';
            $query = Vehicle::with($relations)->withCount('completedTransfers');
        } else {
            $query = Vehicle::with($relations);
        }

        $requestedBranchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;
        $effectiveBranchId = null;

        if ($user && !$user->isCustomer()) {
            $effectiveBranchId = $this->branchScope->resolveBranchFilter($user, $requestedBranchId);

            if ($effectiveBranchId !== null) {
                $query->where('branch_id', $effectiveBranchId);
            }
        } elseif ($requestedBranchId) {
            $query->where('branch_id', $requestedBranchId);
        }

        if ($requestedBranchId || ($user && $this->branchScope->isBranchScoped($user))) {
            if (!$request->user()?->isAdmin()) {
                $branchIdForTransfer = $effectiveBranchId ?? $requestedBranchId;
                if ($branchIdForTransfer) {
                    $query->whereHas('branch', fn ($q) => $q->where('status', 'active'));

                    $query->whereDoesntHave('transfers', function ($q) use ($branchIdForTransfer) {
                        $q->where('from_branch_id', $branchIdForTransfer)
                            ->whereIn('status', VehicleTransfer::ACTIVE_STATUSES);
                    });
                }
            }
        } elseif (!$request->user()?->isAdmin()) {
            $query->whereHas('branch', fn ($q) => $q->where('status', 'active'));
        }

        if ($search = $request->input('search')) {
            $searchLower = strtolower($search);
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(brand) LIKE ?', ["%{$searchLower}%"])
                    ->orWhereRaw('LOWER(model) LIKE ?', ["%{$searchLower}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"])
                    ->orWhereRaw('LOWER(registration_number) LIKE ?', ["%{$searchLower}%"]);
            });
        }

        if ($category = $request->input('category')) {
            $query->whereHas('category', function ($q) use ($category) {
                $q->where('slug', $category);
            });
        }

        if ($request->has('min_price')) {
            $query->where('rental_price_per_day', '>=', $request->input('min_price'));
        }

        if ($request->has('max_price')) {
            $query->where('rental_price_per_day', '<=', $request->input('max_price'));
        }

        if ($fuelType = $request->input('fuel_type')) {
            $query->where('fuel_type', $fuelType);
        }

        if ($transmission = $request->input('transmission')) {
            $query->where('transmission', $transmission);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        } elseif ($request->boolean('available_only', false) || ($request->has('pickup_date') && $request->has('return_date'))) {
            $query->where('status', 'available');
        }

        if ($request->has('pickup_date') && $request->has('return_date')) {
            $pickupDate = Carbon::parse($request->input('pickup_date'));
            $returnDate = Carbon::parse($request->input('return_date'));

            $query->whereDoesntHave('bookings', function ($q) use ($pickupDate, $returnDate) {
                $q->whereIn('status', Booking::BLOCKING_STATUSES)
                  ->where(function ($q2) use ($pickupDate, $returnDate) {
                    $q2->whereBetween('pickup_date', [$pickupDate, $returnDate])
                       ->orWhereBetween('return_date', [$pickupDate, $returnDate])
                       ->orWhere(function ($q3) use ($pickupDate, $returnDate) {
                           $q3->where('pickup_date', '<=', $pickupDate)
                              ->where('return_date', '>=', $returnDate);
                       });
                });
            });
        }

        if ($request->has('featured')) {
            $query->where('featured', filter_var($request->input('featured'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('min_year')) {
            $query->where('year', '>=', $request->input('min_year'));
        }

        if ($request->has('max_year')) {
            $query->where('year', '<=', $request->input('max_year'));
        }

        if ($request->has('min_seats')) {
            $query->where('seats', '>=', $request->input('min_seats'));
        }

        if ($request->has('max_seats')) {
            $query->where('seats', '<=', $request->input('max_seats'));
        }

        if ($location = $request->input('location')) {
            $query->whereRaw('LOWER(location) LIKE ?', ["%" . strtolower($location) . "%"]);
        }

        if ($sort = $request->input('sort')) {
            match ($sort) {
                'price_asc' => $query->orderBy('rental_price_per_day', 'asc'),
                'price_desc' => $query->orderBy('rental_price_per_day', 'desc'),
                'newest' => $query->orderBy('created_at', 'desc'),
                'oldest' => $query->orderBy('created_at', 'asc'),
                'year_desc' => $query->orderBy('year', 'desc'),
                'year_asc' => $query->orderBy('year', 'asc'),
                default => $query->orderBy('created_at', 'desc'),
            };
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $vehicles = $query->paginate($request->input('per_page', 12));

        return response()->json([
            'success' => true,
            'message' => 'Vehicles retrieved successfully',
            'data' => VehicleResource::collection($vehicles),
            'meta' => [
                'current_page' => $vehicles->currentPage(),
                'last_page' => $vehicles->lastPage(),
                'per_page' => $vehicles->perPage(),
                'total' => $vehicles->total(),
            ],
        ]);
    }

    public function show(Request $request, Vehicle $vehicle): JsonResponse
    {
        $user = $request->user();

        if ($user && !$user->isCustomer()) {
            Gate::forUser($user)->authorize('view', $vehicle);
        }

        $vehicle->load(['category', 'images', 'primaryImage', 'branch']);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle retrieved successfully',
            'data' => new VehicleResource($vehicle),
        ]);
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        Gate::authorize('create', Vehicle::class);

        $user = $request->user();
        $data = $this->branchScope->forceOwnBranchId($user, $request->validated());

        if (($user->isAdmin() || $user->isFleetManager()) && $request->filled('branch_id')) {
            $data['branch_id'] = (int) $request->input('branch_id');
        }

        $vehicle = Vehicle::create(
            array_merge($data, ['created_by' => $user->id])
        );

        if ($request->has('images')) {
            foreach ($request->input('images') as $index => $image) {
                $vehicle->images()->create([
                    'image_url' => $image['image_url'],
                    'is_primary' => $image['is_primary'] ?? ($index === 0),
                ]);
            }
        }

        $vehicle->load(['category', 'images', 'primaryImage', 'branch']);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle created successfully',
            'data' => new VehicleResource($vehicle),
        ], 201);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        Gate::authorize('update', $vehicle);

        $validated = $this->branchScope->stripBranchId($request->user(), $request->validated());
        $user = $request->user();

        if (isset($validated['status']) && $validated['status'] !== $vehicle->status) {
            $this->vehicleStatusService->transition(
                $vehicle,
                $validated['status'],
                $user,
                'Manual vehicle status update'
            );
            unset($validated['status']);
        }

        if (isset($validated['mileage']) && (int) $validated['mileage'] !== (int) $vehicle->mileage) {
            $this->vehicleStatusService->updateMileage(
                $vehicle,
                (int) $validated['mileage'],
                $user,
                $request->boolean('mileage_correction')
            );
            unset($validated['mileage']);
        }

        if (!empty($validated)) {
            $vehicle->update($validated);
        }

        if ($request->has('images')) {
            $vehicle->images()->delete();
            foreach ($request->input('images') as $index => $image) {
                $vehicle->images()->create([
                    'image_url' => $image['image_url'],
                    'is_primary' => $image['is_primary'] ?? ($index === 0),
                ]);
            }
        }

        $vehicle->load(['category', 'images', 'primaryImage', 'branch']);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle updated successfully',
            'data' => new VehicleResource($vehicle->fresh(['category', 'images', 'primaryImage', 'branch'])),
        ]);
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        Gate::authorize('delete', $vehicle);

        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle deleted successfully',
        ]);
    }
}
