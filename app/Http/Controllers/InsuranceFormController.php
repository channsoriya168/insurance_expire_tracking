<?php

namespace App\Http\Controllers;

use App\Exports\InsurancesExport;
use App\Models\Insurance;
use App\Services\InsuranceService;
use App\Support\ExpiryDateRange;
use App\Telegram\AllowedChats;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Maatwebsite\Excel\Excel as ExcelWriterType;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;

final class InsuranceFormController extends Controller
{
    public function __construct(private readonly InsuranceService $insurances) {}

    public function showExport(Request $request): View
    {
        $this->assertChatAllowed($request);

        $filter = trim((string) $request->query('filter', 'all'));

        return view('forms.insurances.export', ['filter' => $filter === '' ? 'all' : $filter]);
    }

    public function export(Request $request): View
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

        $this->sendDocumentToChat($request, $query, $filter);

        return view('forms.insurances.export', ['filter' => $filter, 'sent' => true]);
    }

    private function assertChatAllowed(Request $request): void
    {
        $chatId = (int) $request->query('chat', 0);

        if ($chatId === 0 || ! AllowedChats::contains($chatId)) {
            throw new AccessDeniedHttpException('This link is no longer authorized.');
        }
    }

    /**
     * @param  Builder<Insurance>  $query
     */
    private function sendDocumentToChat(Request $request, Builder $query, string $filter): void
    {
        $chatId = (int) $request->query('chat', 0);

        if ($chatId === 0 || ! AllowedChats::contains($chatId)) {
            return;
        }

        $contents = Excel::raw(new InsurancesExport($query), ExcelWriterType::XLSX);

        app(Api::class)->sendDocument([
            'chat_id' => $chatId,
            'document' => InputFile::createFromContents($contents, 'insurances.xlsx'),
            'caption' => "Exported policies (filter: {$filter})",
        ]);
    }
}
