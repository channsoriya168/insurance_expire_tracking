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
        return Inertia::render('Insurances/Index', [
            'insurances' => $this->insurances->paginate(
                $request->string('search')->trim()->value() ?: null,
                $request->string('status')->trim()->value() ?: null,
            ),
            'filters' => $request->only(['search', 'status']),
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

        $this->notifyChat($request, "Saved policy #{$insurance->id} ({$insurance->policy_no}) via the Mini App.");

        return to_route('insurances.index')->with('status', "Saved policy {$insurance->policy_no}.");
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

        $this->notifyChat($request, "Updated policy {$insurance->policy_no} via the Mini App.");

        return to_route('insurances.index')->with('status', "Updated policy {$insurance->policy_no}.");
    }

    public function destroy(Request $request, Insurance $insurance): RedirectResponse
    {
        $policyNo = $insurance->policy_no;
        $this->insurances->delete($insurance);

        $this->notifyChat($request, "Deleted policy {$policyNo} via the Mini App.");

        return to_route('insurances.index')->with('status', "Deleted policy {$policyNo}.");
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
