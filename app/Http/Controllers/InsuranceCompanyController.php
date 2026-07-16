<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInsuranceCompanyRequest;
use App\Http\Requests\UpdateInsuranceCompanyRequest;
use App\Models\InsuranceCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class InsuranceCompanyController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value() ?: null;

        return Inertia::render('Settings/InsuranceCompanies', [
            'insuranceCompanies' => InsuranceCompany::query()
                ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orderBy('name')
                ->paginate(10, ['id', 'name'])
                ->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    public function store(StoreInsuranceCompanyRequest $request): JsonResponse
    {
        $company = InsuranceCompany::create($request->validated());

        return response()->json($company, 201);
    }

    public function update(UpdateInsuranceCompanyRequest $request, InsuranceCompany $insuranceCompany): JsonResponse
    {
        $insuranceCompany->update($request->validated());

        return response()->json($insuranceCompany);
    }

    public function destroy(InsuranceCompany $insuranceCompany): JsonResponse
    {
        if ($insuranceCompany->insurances()->exists()) {
            return response()->json([
                'message' => 'This insurance company is used by existing policies and cannot be deleted.',
            ], 409);
        }

        $insuranceCompany->delete();

        return response()->json(['deleted' => true]);
    }
}
