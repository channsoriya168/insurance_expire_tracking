<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Builds a Telegram Mini App `initData` string, HMAC-signed the same way
 * Telegram signs a real one, for exercising InitDataValidator/TelegramAuthController
 * without touching the live bot.
 *
 * @param  array<string, mixed>  $userOverrides
 */
function buildTelegramInitData(int $userId, string $botToken, array $userOverrides = [], ?int $authDate = null): string
{
    $params = [
        'auth_date' => (string) ($authDate ?? now()->timestamp),
        'query_id' => 'AAFtestquery',
        'user' => json_encode(array_merge(['id' => $userId, 'first_name' => 'Test'], $userOverrides)),
    ];

    ksort($params);

    $checkString = collect($params)->map(fn ($value, $key) => "{$key}={$value}")->implode("\n");
    $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);

    $params['hash'] = hash_hmac('sha256', $checkString, $secretKey);

    return http_build_query($params);
}
