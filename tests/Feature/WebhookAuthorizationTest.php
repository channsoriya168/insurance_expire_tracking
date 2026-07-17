<?php

use Telegram\Bot\Api;

function telegramUpdatePayload(int $chatId, string $text, bool $isCommand = false): array
{
    return [
        'update_id' => random_int(1, 1000000),
        'message' => [
            'message_id' => 1,
            'chat' => ['id' => $chatId, 'type' => 'private'],
            'text' => $text,
            'date' => time(),
            'entities' => $isCommand ? [['type' => 'bot_command', 'offset' => 0, 'length' => strlen($text)]] : [],
        ],
    ];
}

it('forwards a command update to Telegram even from a chat id that is not allowed', function () {
    config(['insurance-bot.allowed_chat_ids' => [111]]);

    $this->mock(Api::class, function ($mock) {
        $mock->shouldReceive('processCommand')->once();
        $mock->shouldNotReceive('sendMessage');
    });

    $response = $this->postJson('/api/telegram/webhook', telegramUpdatePayload(999, '/start', isCommand: true));

    $response->assertNoContent();
});

it('rejects non-command messages from a chat id that is not allowed', function () {
    config(['insurance-bot.allowed_chat_ids' => [111]]);

    $this->mock(Api::class, function ($mock) {
        $mock->shouldNotReceive('processCommand');
        $mock->shouldReceive('sendMessage')->once()
            ->withArgs(fn (array $params) => $params['chat_id'] === 999 && str_contains($params['text'], 'not authorized'));
    });

    $response = $this->postJson('/api/telegram/webhook', telegramUpdatePayload(999, 'random text'));

    $response->assertNoContent();
});

it('dispatches a command update from an allowed chat id', function () {
    config(['insurance-bot.allowed_chat_ids' => [111]]);

    $this->mock(Api::class, function ($mock) {
        $mock->shouldReceive('processCommand')->once();
    });

    $response = $this->postJson('/api/telegram/webhook', telegramUpdatePayload(111, '/start', isCommand: true));

    $response->assertNoContent();
});

it('ignores plain text messages that are not commands', function () {
    config(['insurance-bot.allowed_chat_ids' => [111]]);

    $this->mock(Api::class, function ($mock) {
        $mock->shouldNotReceive('sendMessage');
        $mock->shouldNotReceive('processCommand');
    });

    $response = $this->postJson('/api/telegram/webhook', telegramUpdatePayload(111, 'random text'));

    $response->assertNoContent();
});
