<?php

use App\Enums\TelegramAccessStatus;
use App\Models\TelegramAccessRequest;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;

function authenticateTelegramAccessChat(int $chatId, string $botToken): void
{
    $initData = buildTelegramInitData($chatId, $botToken);

    test()->post('/telegram/auth', ['init_data' => $initData])->assertRedirect('/insurances');
}

beforeEach(function () {
    $this->chatId = 111;
    $this->botToken = 'test-bot-token';

    config([
        'telegram.default' => 'mybot',
        'telegram.bots.mybot.token' => $this->botToken,
        'insurance-bot.allowed_chat_ids' => [$this->chatId],
    ]);
});

it('redirects to the launch page when no session is established', function () {
    $this->get('/telegram-access')->assertRedirect('/telegram/launch');
});

it('lists only pending access requests', function () {
    $this->mock(Api::class, fn ($mock) => $mock->shouldReceive('sendMessage')->andReturn(new Message([])));

    TelegramAccessRequest::factory()->create(['chat_id' => 222, 'first_name' => 'Bob']);
    TelegramAccessRequest::factory()->approved()->create(['chat_id' => 333]);
    TelegramAccessRequest::factory()->rejected()->create(['chat_id' => 444]);

    authenticateTelegramAccessChat($this->chatId, $this->botToken);

    $this->get('/telegram-access')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/TelegramAccess')
            ->where('pendingRequests.total', 1)
            ->where('pendingRequests.data.0.first_name', 'Bob'));
});

it('approves a pending access request and notifies the chat', function () {
    $request = TelegramAccessRequest::factory()->create(['chat_id' => 222]);

    $this->mock(Api::class, function ($mock) {
        $mock->shouldReceive('sendMessage')->once()
            ->withArgs(fn (array $params) => $params['chat_id'] === 222 && str_contains($params['text'], 'approved'))
            ->andReturn(new Message([]));
    });

    authenticateTelegramAccessChat($this->chatId, $this->botToken);

    $this->patch("/telegram-access/{$request->id}/approve")->assertRedirect();

    expect($request->fresh()->status)->toBe(TelegramAccessStatus::Approved);
});

it('rejects a pending access request and notifies the chat', function () {
    $request = TelegramAccessRequest::factory()->create(['chat_id' => 222]);

    $this->mock(Api::class, function ($mock) {
        $mock->shouldReceive('sendMessage')->once()
            ->withArgs(fn (array $params) => $params['chat_id'] === 222 && str_contains($params['text'], 'declined'))
            ->andReturn(new Message([]));
    });

    authenticateTelegramAccessChat($this->chatId, $this->botToken);

    $this->patch("/telegram-access/{$request->id}/reject")->assertRedirect();

    expect($request->fresh()->status)->toBe(TelegramAccessStatus::Rejected);
});

it('removes an approved request from the pending index', function () {
    $request = TelegramAccessRequest::factory()->create(['chat_id' => 222]);

    $this->mock(Api::class, fn ($mock) => $mock->shouldReceive('sendMessage')->andReturn(new Message([])));

    authenticateTelegramAccessChat($this->chatId, $this->botToken);

    $this->patch("/telegram-access/{$request->id}/approve");

    $this->get('/telegram-access')
        ->assertInertia(fn ($page) => $page->where('pendingRequests.total', 0));
});
