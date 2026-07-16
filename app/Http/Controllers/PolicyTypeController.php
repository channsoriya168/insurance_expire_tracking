<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePolicyTypeRequest;
use App\Http\Requests\UpdatePolicyTypeRequest;
use App\Models\PolicyType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PolicyTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value() ?: null;

        return Inertia::render('Settings/PolicyTypes', [
            'policyTypes' => PolicyType::query()
                ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orderBy('name')
                ->paginate(10, ['id', 'name'])
                ->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    public function store(StorePolicyTypeRequest $request): JsonResponse
    {
        $policyType = PolicyType::create($request->validated());

        return response()->json($policyType, 201);
    }

    public function update(UpdatePolicyTypeRequest $request, PolicyType $policyType): JsonResponse
    {
        $policyType->update($request->validated());

        return response()->json($policyType);
    }

    public function destroy(PolicyType $policyType): JsonResponse
    {
        if ($policyType->insurances()->exists()) {
            return response()->json([
                'message' => 'This policy type is used by existing policies and cannot be deleted.',
            ], 409);
        }

        $policyType->delete();

        return response()->json(['deleted' => true]);
    }
}
