<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function show(): JsonResponse
    {
        $company = Company::with('branches')->first();

        if (!$company) {
            return response()->json(['success' => false, 'message' => 'Company not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $company]);
    }

    public function update(Request $request): JsonResponse
    {
        $company = Company::first();

        if (!$company) {
            return response()->json(['success' => false, 'message' => 'Company not found.'], 404);
        }

        $data = $request->validate([
            'name'    => ['sometimes', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string'],
            'phone'   => ['sometimes', 'nullable', 'string', 'max:30'],
            'email'   => ['sometimes', 'nullable', 'email', 'max:255'],
        ]);

        $company->update($data);

        return response()->json(['success' => true, 'message' => 'Company updated successfully.', 'data' => $company->fresh()]);
    }
}
