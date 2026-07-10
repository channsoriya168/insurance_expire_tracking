<?php

namespace App\Http\Controllers;

use App\Models\Insurance;
use App\Services\InsuranceService;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final class InsuranceNotificationController extends Controller
{
    public function __construct(private readonly InsuranceService $insurances) {}

    public function __invoke(): Response
    {
        $groups = $this->insurances->expiringGroups();

        return Inertia::render('Insurances/Notifications', [
            'overdue' => $this->toArray($groups['overdue']),
            'buckets' => collect($groups['buckets'])
                ->map(fn (Collection $policies): array => $this->toArray($policies))
                ->all(),
        ]);
    }

    /**
     * @param  Collection<int, Insurance>  $policies
     * @return list<array<string, mixed>>
     */
    private function toArray(Collection $policies): array
    {
        return $policies
            ->map(fn (Insurance $insurance): array => [
                'id' => $insurance->id,
                'policy_no' => $insurance->policy_no,
                'insured_name' => $insurance->insured_name,
                'insurance_company' => $insurance->insurance_company,
                'expiry_date' => $insurance->expiry_date->format('Y-m-d'),
            ])
            ->all();
    }
}
