<?php

namespace App\Http\Controllers;

use App\Enums\TelegramAccessStatus;
use App\Models\TelegramAccessRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Telegram\Bot\Api;
use Throwable;

final class TelegramAccessRequestController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/TelegramAccess', [
            'pendingRequests' => TelegramAccessRequest::query()
                ->where('status', TelegramAccessStatus::Pending)
                ->oldest()
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function approve(TelegramAccessRequest $telegramAccessRequest, Api $telegram): RedirectResponse
    {
        $telegramAccessRequest->update(['status' => TelegramAccessStatus::Approved]);

        $this->notify($telegram, $telegramAccessRequest, "You're approved! Tap /start to continue.");

        return back();
    }

    public function reject(TelegramAccessRequest $telegramAccessRequest, Api $telegram): RedirectResponse
    {
        $telegramAccessRequest->update(['status' => TelegramAccessStatus::Rejected]);

        $this->notify($telegram, $telegramAccessRequest, 'Your request to use this bot was declined.');

        return back();
    }

    private function notify(Api $telegram, TelegramAccessRequest $telegramAccessRequest, string $text): void
    {
        // A blocked bot or a chat the user has since deleted must not stop the approval/rejection from saving.
        try {
            $telegram->sendMessage([
                'chat_id' => $telegramAccessRequest->chat_id,
                'text' => $text,
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to notify Telegram chat about access request decision.', [
                'chat_id' => $telegramAccessRequest->chat_id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
