<?php

namespace App\Console\Commands;

use App\Enums\TelegramAccessStatus;
use App\Models\TelegramAccessRequest;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Throwable;

#[Signature('telegram:revoke {chatId : The Telegram chat id to remove access for}')]
#[Description('Removes a chat\'s access to the bot/Mini App by rejecting its telegram_access_requests row.')]
class RevokeTelegramAccess extends Command
{
    public function handle(Api $telegram): int
    {
        $chatId = (int) $this->argument('chatId');

        $request = TelegramAccessRequest::where('chat_id', $chatId)->first();

        if ($request === null) {
            if (in_array($chatId, config('insurance-bot.allowed_chat_ids'), true)) {
                $this->error("Chat {$chatId} is allowed via TELEGRAM_ALLOWED_CHAT_IDS in .env - remove it there manually.");

                return self::FAILURE;
            }

            $this->error("No access request found for chat {$chatId}.");

            return self::FAILURE;
        }

        $request->update(['status' => TelegramAccessStatus::Rejected]);

        $this->notify($telegram, $chatId);

        $this->info("Access revoked for chat {$chatId}.");

        return self::SUCCESS;
    }

    private function notify(Api $telegram, int $chatId): void
    {
        // A blocked bot or a chat the user has since deleted must not stop the revoke from saving.
        try {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Your access to this bot has been revoked.',
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to notify Telegram chat about revoked access.', [
                'chat_id' => $chatId,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
