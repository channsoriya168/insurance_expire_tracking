<?php

use App\Enums\TelegramAccessStatus;
use App\Models\TelegramAccessRequest;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;

it('revokes an approved chat and notifies it', function () {
    $request = TelegramAccessRequest::factory()->approved()->create(['chat_id' => 222]);

    $this->mock(Api::class, function ($mock) {
        $mock->shouldReceive('sendMessage')->once()
            ->withArgs(fn (array $params) => $params['chat_id'] === 222 && str_contains($params['text'], 'revoked'))
            ->andReturn(new Message([]));
    });

    $this->artisan('telegram:revoke', ['chatId' => 222])
        ->assertExitCode(0);

    expect($request->fresh()->status)->toBe(TelegramAccessStatus::Rejected);
});

it('fails when the chat id has no access request and is not env-seeded', function () {
    config(['insurance-bot.allowed_chat_ids' => []]);

    $this->mock(Api::class, fn ($mock) => $mock->shouldNotReceive('sendMessage'));

    $this->artisan('telegram:revoke', ['chatId' => 999])
        ->assertExitCode(1);
});

it('points to .env when the chat id is only allowed via the config seed', function () {
    config(['insurance-bot.allowed_chat_ids' => [333]]);

    $this->mock(Api::class, fn ($mock) => $mock->shouldNotReceive('sendMessage'));

    $this->artisan('telegram:revoke', ['chatId' => 333])
        ->assertExitCode(1);
});
