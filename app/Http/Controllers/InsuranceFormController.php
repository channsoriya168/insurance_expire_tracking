<?php

namespace App\Http\Controllers;

use App\Exports\InsurancesExport;
use App\Models\Insurance;
use App\Services\InsuranceService;
use App\Support\ExpiryDateRange;
use App\Telegram\AllowedChats;
use App\Telegram\Conversations\PolicyFieldSteps;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Telegram\Bot\Api;

final class InsuranceFormController extends Controller
{
    public function __construct(private readonly InsuranceService $insurances) {}

    public function showCreate(Request $request): View
    {
        $this->assertChatAllowed($request);

        return view('forms.insurances.create', [
            'fields' => PolicyFieldSteps::ORDER,
            'old' => [],
            'errors' => [],
        ]);
    }

    public function store(Request $request): View
    {
        $this->assertChatAllowed($request);

        [$data, $errors] = $this->parseFields($request);

        if ($errors !== []) {
            return view('forms.insurances.create', [
                'fields' => PolicyFieldSteps::ORDER,
                'old' => $request->all(),
                'errors' => $errors,
            ]);
        }

        $insurance = $this->insurances->create($data);
        $this->notifyChat($request, "Saved policy #{$insurance->id} ({$insurance->policy_no}) via the web form.");

        return view('forms.insurances.create', [
            'fields' => PolicyFieldSteps::ORDER,
            'old' => [],
            'errors' => [],
            'status' => "Saved policy {$insurance->policy_no}.",
        ]);
    }

    public function showEdit(Request $request): View
    {
        $this->assertChatAllowed($request);

        $policyNo = trim((string) $request->query('policy_no', ''));
        $insurance = $policyNo !== '' ? $this->insurances->findByPolicyNo($policyNo) : null;

        return view('forms.insurances.edit', [
            'fields' => PolicyFieldSteps::ORDER,
            'insurance' => $insurance,
            'policyNo' => $policyNo,
            'old' => [],
            'errors' => [],
            'error' => $policyNo !== '' && $insurance === null ? "No policy found with number \"{$policyNo}\"." : null,
        ]);
    }

    public function saveEdit(Request $request): View
    {
        $this->assertChatAllowed($request);

        $insuranceId = $request->input('insurance_id');

        if ($insuranceId === null || $insuranceId === '') {
            $policyNo = trim((string) $request->input('policy_no', ''));
            $insurance = $policyNo !== '' ? $this->insurances->findByPolicyNo($policyNo) : null;

            return view('forms.insurances.edit', [
                'fields' => PolicyFieldSteps::ORDER,
                'insurance' => $insurance,
                'policyNo' => $policyNo,
                'old' => [],
                'errors' => [],
                'error' => $insurance === null ? "No policy found with number \"{$policyNo}\"." : null,
            ]);
        }

        $insurance = Insurance::find((int) $insuranceId);

        if ($insurance === null) {
            return view('forms.insurances.edit', [
                'fields' => PolicyFieldSteps::ORDER,
                'insurance' => null,
                'old' => [],
                'errors' => [],
                'error' => 'That policy no longer exists.',
            ]);
        }

        [$data, $errors] = $this->parseFields($request, excludeInsuranceId: $insurance->id);

        if ($errors !== []) {
            return view('forms.insurances.edit', [
                'fields' => PolicyFieldSteps::ORDER,
                'insurance' => $insurance,
                'old' => $request->all(),
                'errors' => $errors,
            ]);
        }

        foreach ($data as $field => $value) {
            $this->insurances->updateField($insurance, $field, $value);
        }

        $this->notifyChat($request, "Updated policy {$insurance->policy_no} via the web form.");

        return view('forms.insurances.edit', [
            'fields' => PolicyFieldSteps::ORDER,
            'insurance' => $insurance->fresh(),
            'old' => [],
            'errors' => [],
            'status' => "Updated policy {$insurance->policy_no}.",
        ]);
    }

    public function showDelete(Request $request): View
    {
        $this->assertChatAllowed($request);

        $policyNo = trim((string) $request->query('policy_no', ''));
        $insurance = $policyNo !== '' ? $this->insurances->findByPolicyNo($policyNo) : null;

        return view('forms.insurances.delete', [
            'insurance' => $insurance,
            'policyNo' => $policyNo,
            'error' => $policyNo !== '' && $insurance === null ? "No policy found with number \"{$policyNo}\"." : null,
        ]);
    }

    public function destroy(Request $request): View
    {
        $this->assertChatAllowed($request);

        $insuranceId = $request->input('insurance_id');

        if ($insuranceId === null || $insuranceId === '') {
            $policyNo = trim((string) $request->input('policy_no', ''));
            $insurance = $policyNo !== '' ? $this->insurances->findByPolicyNo($policyNo) : null;

            return view('forms.insurances.delete', [
                'insurance' => $insurance,
                'policyNo' => $policyNo,
                'error' => $insurance === null ? "No policy found with number \"{$policyNo}\"." : null,
            ]);
        }

        $insurance = Insurance::find((int) $insuranceId);

        if ($insurance === null) {
            return view('forms.insurances.delete', ['insurance' => null, 'error' => 'That policy no longer exists.']);
        }

        $policyNo = $insurance->policy_no;
        $this->insurances->delete($insurance);
        $this->notifyChat($request, "Deleted policy {$policyNo} via the web form.");

        return view('forms.insurances.delete', ['insurance' => null, 'status' => "Deleted policy {$policyNo}."]);
    }

    public function showExport(Request $request): View
    {
        $this->assertChatAllowed($request);

        $filter = trim((string) $request->query('filter', 'all'));

        return view('forms.insurances.export', ['filter' => $filter === '' ? 'all' : $filter]);
    }

    public function export(Request $request): BinaryFileResponse|View
    {
        $this->assertChatAllowed($request);

        $filter = trim((string) $request->input('filter', 'all'));

        try {
            $range = ExpiryDateRange::parse($filter === '' ? 'all' : $filter);
        } catch (InvalidArgumentException $exception) {
            return view('forms.insurances.export', ['filter' => $filter, 'error' => $exception->getMessage()]);
        }

        $query = $this->insurances->exportQuery($range);

        if (! $query->exists()) {
            return view('forms.insurances.export', ['filter' => $filter, 'error' => 'No policies found for that filter.']);
        }

        $this->notifyChat($request, "Exported policies (filter: {$filter}) via the web form.");

        return Excel::download(new InsurancesExport($query), 'insurances.xlsx');
    }

    private function assertChatAllowed(Request $request): void
    {
        $chatId = (int) $request->query('chat', 0);

        if ($chatId === 0 || ! AllowedChats::contains($chatId)) {
            throw new AccessDeniedHttpException('This link is no longer authorized.');
        }
    }

    private function notifyChat(Request $request, string $text): void
    {
        $chatId = (int) $request->query('chat', 0);

        if ($chatId !== 0 && AllowedChats::contains($chatId)) {
            app(Api::class)->sendMessage(['chat_id' => $chatId, 'text' => $text]);
        }
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function parseFields(Request $request, ?int $excludeInsuranceId = null): array
    {
        $data = [];
        $errors = [];

        foreach (PolicyFieldSteps::ORDER as $field) {
            $step = PolicyFieldSteps::get($field);
            $raw = trim((string) $request->input($field, ''));

            if ($raw === '' && $step->skippable) {
                $raw = '-';
            }

            $result = PolicyFieldSteps::parse($field, $raw, $excludeInsuranceId);

            if ($result->ok) {
                $data[$field] = $result->value;
            } else {
                $errors[$field] = $result->error;
            }
        }

        return [$data, $errors];
    }
}
