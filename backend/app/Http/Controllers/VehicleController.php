<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Vehicle::with(['category', 'images', 'primaryImage']);

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

    public function show(Vehicle $vehicle): JsonResponse
    {
        $vehicle->load(['category', 'images', 'primaryImage']);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle retrieved successfully',
            'data' => new VehicleResource($vehicle),
        ]);
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $vehicle = Vehicle::create(
            array_merge(
                $request->validated(),
                ['created_by' => $request->user()->id]
            )
        );

        if ($request->has('images')) {
            foreach ($request->input('images') as $index => $image) {
                $vehicle->images()->create([
                    'image_url' => $image['image_url'],
                    'is_primary' => $image['is_primary'] ?? ($index === 0),
                ]);
            }
        }

        $vehicle->load(['category', 'images', 'primaryImage']);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle created successfully',
            'data' => new VehicleResource($vehicle),
        ], 201);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $vehicle->update($request->validated());

        if ($request->has('images')) {
            $vehicle->images()->delete();
            foreach ($request->input('images') as $index => $image) {
                $vehicle->images()->create([
                    'image_url' => $image['image_url'],
                    'is_primary' => $image['is_primary'] ?? ($index === 0),
                ]);
            }
        }

        $vehicle->load(['category', 'images', 'primaryImage']);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle updated successfully',
            'data' => new VehicleResource($vehicle->fresh(['category', 'images', 'primaryImage'])),
        ]);
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle deleted successfully',
        ]);
    }
}
