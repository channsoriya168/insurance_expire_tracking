<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInsuranceCompanyRequest;
use App\Models\InsuranceCompany;
use Illuminate\Http\JsonResponse;

final class InsuranceCompanyController extends Controller
{
    public function store(StoreInsuranceCompanyRequest $request): JsonResponse
    {
        $company = InsuranceCompany::create($request->validated());

        return response()->json($company, 201);
    }
}
