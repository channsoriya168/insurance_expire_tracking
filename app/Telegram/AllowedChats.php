<?php

namespace App\Telegram;

use App\Enums\TelegramAccessStatus;
use App\Models\TelegramAccessRequest;

final class AllowedChats
{
    /**
     * @return list<int>
     */
    public static function ids(): array
    {
        $approvedRequestIds = TelegramAccessRequest::query()
            ->where('status', TelegramAccessStatus::Approved)
            ->pluck('chat_id')
            ->map(fn (int|string $chatId): int => (int) $chatId)
            ->all();

        return array_values(array_unique([
            ...config('insurance-bot.allowed_chat_ids'),
            ...$approvedRequestIds,
        ]));
    }

    public static function contains(int|string $chatId): bool
    {
        return in_array((int) $chatId, self::ids(), true);
    }
}
