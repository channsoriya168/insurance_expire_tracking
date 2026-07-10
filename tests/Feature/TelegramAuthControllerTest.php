<?php

beforeEach(function () {
    config([
        'telegram.default' => 'mybot',
        'telegram.bots.mybot.token' => 'test-bot-token',
        'insurance-bot.allowed_chat_ids' => [111],
    ]);
});

it('authenticates an allowed chat id and redirects to the app', function () {
    $initData = buildTelegramInitData(111, 'test-bot-token');

    $response = $this->post('/telegram/auth', ['init_data' => $initData]);

    $response->assertRedirect('/insurances');
    expect(session('telegram_chat_id'))->toBe(111);
});

it('rejects a chat id that is not on the allow list', function () {
    $initData = buildTelegramInitData(999, 'test-bot-token');

    $this->post('/telegram/auth', ['init_data' => $initData])->assertForbidden();

    expect(session('telegram_chat_id'))->toBeNull();
});

it('rejects an invalid initData payload', function () {
    $this->post('/telegram/auth', ['init_data' => 'garbage'])->assertForbidden();
});

it('rejects a missing initData payload', function () {
    $this->post('/telegram/auth', [])->assertForbidden();
});
