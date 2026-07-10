<?php

use App\Telegram\InitDataValidator;

beforeEach(function () {
    config(['telegram.default' => 'mybot', 'telegram.bots.mybot.token' => 'test-bot-token']);

    $this->validator = new InitDataValidator;
});

it('accepts a correctly signed initData string', function () {
    $initData = buildTelegramInitData(111, 'test-bot-token');

    $user = $this->validator->validate($initData);

    expect($user)->not->toBeNull()
        ->and($user['id'])->toBe(111);
});

it('rejects an empty initData string', function () {
    expect($this->validator->validate(''))->toBeNull();
});

it('rejects a tampered hash', function () {
    $initData = buildTelegramInitData(111, 'test-bot-token');
    $tampered = preg_replace('/hash=[a-f0-9]+/', 'hash=deadbeef', $initData);

    expect($this->validator->validate($tampered))->toBeNull();
});

it('rejects initData signed with the wrong bot token', function () {
    $initData = buildTelegramInitData(111, 'a-different-token');

    expect($this->validator->validate($initData))->toBeNull();
});

it('rejects a stale auth_date older than 24 hours', function () {
    $initData = buildTelegramInitData(111, 'test-bot-token', authDate: now()->subDay()->subMinute()->timestamp);

    expect($this->validator->validate($initData))->toBeNull();
});

it('rejects initData missing a user field', function () {
    $params = ['auth_date' => (string) now()->timestamp, 'query_id' => 'AAFtest'];
    ksort($params);
    $checkString = collect($params)->map(fn ($v, $k) => "{$k}={$v}")->implode("\n");
    $secretKey = hash_hmac('sha256', 'test-bot-token', 'WebAppData', true);
    $params['hash'] = hash_hmac('sha256', $checkString, $secretKey);

    expect($this->validator->validate(http_build_query($params)))->toBeNull();
});
