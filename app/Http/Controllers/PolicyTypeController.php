<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePolicyTypeRequest;
use App\Models\PolicyType;
use Illuminate\Http\JsonResponse;

final class PolicyTypeController extends Controller
{
    public function store(StorePolicyTypeRequest $request): JsonResponse
    {
        $policyType = PolicyType::create($request->validated());

        return response()->json($policyType, 201);
    }
}
