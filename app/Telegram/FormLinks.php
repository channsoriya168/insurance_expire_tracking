<?php

namespace App\Telegram;

use Illuminate\Support\Facades\URL;

final class FormLinks
{
    private const int EXPIRES_IN_MINUTES = 30;

    public static function app(int $chatId): string
    {
        return URL::temporarySignedRoute(
            'insurances.index',
            now()->addMinutes(self::EXPIRES_IN_MINUTES),
            ['chat' => $chatId],
        );
    }

    public static function export(int $chatId, ?string $filter = null): string
    {
        return URL::temporarySignedRoute(
            'forms.insurances.export',
            now()->addMinutes(self::EXPIRES_IN_MINUTES),
            array_filter(['chat' => $chatId, 'filter' => $filter], fn (mixed $value): bool => $value !== null),
        );
    }
}
