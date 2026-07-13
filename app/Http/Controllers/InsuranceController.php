<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInsuranceRequest;
use App\Http\Requests\UpdateInsuranceRequest;
use App\Models\Insurance;
use App\Services\InsuranceService;
use App\Telegram\Conversations\PolicyFieldSteps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Telegram\Bot\Api;

final class InsuranceController extends Controller
{
    public function __construct(private readonly InsuranceService $insurances) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value() ?: null;
        $status = $request->string('status')->trim()->value() ?: null;
        $expiry = $request->string('expiry')->trim()->value() ?: null;

        // Default the calendar strip to today until the user picks a
        // different day or an expiry tab (which takes over the date range).
        $date = match (true) {
            $request->has('date') => $request->string('date')->trim()->value() ?: null,
            $request->has('expiry') => null,
            default => today()->toDateString(),
        };

        $insurances = $this->insurances->paginate($search, $status, $expiry, $date);

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
                'status' => $status,
                'expiry' => $expiry,
                'date' => $date,
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

        $this->notifyChat($request, "Saved insurance policy #{$insurance->id} ({$insurance->policy_no}) via Mini App.");

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

        $this->notifyChat($request, "Updated insurance policy {$insurance->policy_no} via Mini App.");

        return to_route('insurances.index')->with('status', "Policy {$insurance->policy_no} updated.");
    }

    public function destroy(Request $request, Insurance $insurance): RedirectResponse
    {
        $policyNo = $insurance->policy_no;
        $this->insurances->delete($insurance);

        $this->notifyChat($request, "Deleted insurance policy {$policyNo} via Mini App.");

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

    private function notifyChat(Request $request, string $text): void
    {
        $chatId = (int) $request->session()->get('telegram_chat_id', 0);

        if ($chatId !== 0) {
            app(Api::class)->sendMessage(['chat_id' => $chatId, 'text' => $text]);
        }
    }
}
