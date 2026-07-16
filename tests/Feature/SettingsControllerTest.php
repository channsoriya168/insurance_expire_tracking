<?php

use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;

beforeEach(function () {
    $this->chatId = 111;
    $this->botToken = 'test-bot-token';

    config([
        'telegram.default' => 'mybot',
        'telegram.bots.mybot.token' => $this->botToken,
        'insurance-bot.allowed_chat_ids' => [$this->chatId],
    ]);

    $this->mock(Api::class, function ($mock) {
        $mock->shouldReceive('sendMessage')->andReturn(new Message([]));
    });
});

function authenticateSettingsChat(int $chatId, string $botToken): void
{
    $initData = buildTelegramInitData($chatId, $botToken);

    test()->post('/telegram/auth', ['init_data' => $initData])->assertRedirect('/insurances');
}

it('redirects to the launch page when no session is established', function () {
    $this->get('/settings')->assertRedirect('/telegram/launch');
});

it('renders the settings menu page', function () {
    authenticateSettingsChat($this->chatId, $this->botToken);

    $this->get('/settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Settings/Index'));
});
