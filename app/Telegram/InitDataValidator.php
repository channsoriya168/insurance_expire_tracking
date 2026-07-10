<?php

namespace App\Telegram;

final class InitDataValidator
{
    private const int MAX_AGE_SECONDS = 86400;

    /**
     * Validates a Telegram Mini App `initData` string per Telegram's spec.
     *
     * @see https://core.telegram.org/bots/webapps#validating-data-received-via-the-mini-app
     *
     * @return array{id: int, first_name?: string, username?: string}|null
     */
    public function validate(string $initData): ?array
    {
        if ($initData === '') {
            return null;
        }

        parse_str($initData, $pairs);

        $hash = $pairs['hash'] ?? null;

        if (! is_string($hash) || $hash === '') {
            return null;
        }

        unset($pairs['hash']);
        ksort($pairs);

        $checkString = collect($pairs)
            ->map(fn (mixed $value, string $key): string => "{$key}={$value}")
            ->implode("\n");

        $botName = config('telegram.default');
        $botToken = (string) config("telegram.bots.{$botName}.token");

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $computedHash = hash_hmac('sha256', $checkString, $secretKey);

        if (! hash_equals($computedHash, $hash)) {
            return null;
        }

        $authDate = (int) ($pairs['auth_date'] ?? 0);

        if ($authDate === 0 || now()->timestamp - $authDate > self::MAX_AGE_SECONDS) {
            return null;
        }

        $user = json_decode((string) ($pairs['user'] ?? ''), true);

        if (! is_array($user) || ! isset($user['id'])) {
            return null;
        }

        return $user;
    }
}
