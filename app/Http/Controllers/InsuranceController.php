<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInsuranceRequest;
use App\Http\Requests\UpdateInsuranceRequest;
use App\Models\Insurance;
use App\Services\InsuranceService;
use App\Telegram\Conversations\PolicyFieldSteps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class InsuranceController extends Controller
{
    public function __construct(private readonly InsuranceService $insurances) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value() ?: null;
        $expiry = $request->string('expiry')->trim()->value() ?: null;
        $sort = $request->string('sort')->trim()->value() === 'desc' ? 'desc' : 'asc';

        $filters = array_filter(['search' => $search, 'expiry' => $expiry]);

        $insurances = QueryBuilder::for(Insurance::class, new Request(['filter' => $filters]))
            ->select(['id', 'policy_no', 'insurance_company', 'insured_name', 'policy_type', 'status', 'expiry_date'])
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, string $value): void {
                    $query->where(function (Builder $query) use ($value): void {
                        $query->where('policy_no', 'like', "%{$value}%")
                            ->orWhere('insured_name', 'like', "%{$value}%")
                            ->orWhere('insurance_company', 'like', "%{$value}%");
                    });
                }),
                AllowedFilter::callback('expiry', function (Builder $query, string $value): void {
                    match (true) {
                        $value === 'today' => $query->whereDate('expiry_date', today()),
                        ctype_digit($value) => $query->whereDate('expiry_date', today()->addDays((int) $value)->toDateString()),
                        default => null,
                    };
                }),
            )
            ->orderBy('expiry_date', $sort)
            ->paginate(15)
            ->withQueryString();

        $insurances->through(fn (Insurance $insurance): array => [
            'id' => $insurance->id,
            'policy_no' => $insurance->policy_no,
            'insurance_company' => $insurance->insurance_company,
            'insured_name' => $insurance->insured_name,
            'policy_type' => $insurance->policy_type,
            'status' => $insurance->status,
            'expiry_date' => $insurance->expiry_date?->format('Y-m-d'),
        ]);

        return Inertia::render('Insurances/Index', [
            'insurances' => $insurances,
            'filters' => [
                'search' => $search,
                'expiry' => $expiry,
                'sort' => $sort,
            ],
            'expiryThresholds' => config('insurance-bot.expiry_thresholds'),
        ]);
    }

    public function show(Insurance $insurance): Response
    {
        return Inertia::render('Insurances/Show', [
            'insurance' => $this->toFormArray($insurance),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Insurances/Create', [
            'contactMethods' => PolicyFieldSteps::contactMethods(),
        ]);
    }

    public function store(StoreInsuranceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = $data['status'] ?: 'Pending';

        $insurance = $this->insurances->create($data);

        return to_route('insurances.index')->with('status', "Policy {$insurance->policy_no} saved.");
    }

    public function edit(Insurance $insurance): Response
    {
        return Inertia::render('Insurances/Edit', [
            'insurance' => $this->toFormArray($insurance),
            'contactMethods' => PolicyFieldSteps::contactMethods(),
        ]);
    }

    public function update(UpdateInsuranceRequest $request, Insurance $insurance): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = $data['status'] ?: 'Pending';

        $this->insurances->update($insurance, $data);

        return to_route('insurances.index')->with('status', "Policy {$insurance->policy_no} updated.");
    }

    public function destroy(Insurance $insurance): RedirectResponse
    {
        $policyNo = $insurance->policy_no;
        $this->insurances->delete($insurance);

        return to_route('insurances.index')->with('status', "Policy {$policyNo} deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    private function toFormArray(Insurance $insurance): array
    {
        $fields = $insurance->only(['id', ...PolicyFieldSteps::ORDER]);

        foreach (['expiry_date', 'confirmed_date', 'request_policy_date', 'policy_received_date'] as $dateField) {
            $fields[$dateField] = $insurance->{$dateField}?->format('Y-m-d');
        }

        return $fields;
    }
}
