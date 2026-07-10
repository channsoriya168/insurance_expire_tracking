<?php

namespace App\Telegram;

final class AllowedChats
{
    /**
     * @return list<int>
     */
    public static function ids(): array
    {
        return config('insurance-bot.allowed_chat_ids');
    }

    public static function contains(int|string $chatId): bool
    {
        return in_array((int) $chatId, self::ids(), true);
    }
}
