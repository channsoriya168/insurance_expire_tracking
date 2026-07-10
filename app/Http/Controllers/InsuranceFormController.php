<?php

namespace App\Http\Controllers;

use App\Exports\InsurancesExport;
use App\Services\InsuranceService;
use App\Support\ExpiryDateRange;
use App\Telegram\AllowedChats;
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
}
