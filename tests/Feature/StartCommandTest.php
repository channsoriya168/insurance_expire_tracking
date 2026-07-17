<?php

use App\Enums\TelegramAccessStatus;
use App\Models\TelegramAccessRequest;
use App\Telegram\Commands\StartCommand;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

function telegramStartUpdate(int $chatId, ?string $firstName = 'Alice', ?string $username = 'alice'): Update
{
    return new Update([
        'update_id' => 1,
        'message' => [
            'message_id' => 1,
            'chat' => array_filter([
                'id' => $chatId,
                'type' => 'private',
                'first_name' => $firstName,
                'username' => $username,
            ], fn ($value) => $value !== null),
            'text' => '/start',
            'date' => time(),
        ],
    ]);
}

it('replies with the welcome message and a persistent export web_app keyboard button for an allowed chat', function () {
    config(['insurance-bot.allowed_chat_ids' => [111]]);

    $telegram = Mockery::mock(Api::class);
    $telegram->shouldReceive('sendMessage')
        ->once()
        ->withArgs(function (array $params) {
            expect($params['chat_id'])->toBe(111);
            expect($params['text'])->not->toContain('/export');

            $keyboard = json_decode($params['reply_markup'], true);
            $button = $keyboard['keyboard'][0][0];
            expect($button['web_app']['url'])->toContain('/forms/insurances/export');
            expect($keyboard['is_persistent'])->toBeTrue();

            return true;
        });

    (new StartCommand)->make($telegram, telegramStartUpdate(111), []);
});

it('records a pending access request and asks an unknown chat to wait for approval', function () {
    config(['insurance-bot.allowed_chat_ids' => []]);

    $telegram = Mockery::mock(Api::class);
    $telegram->shouldReceive('sendMessage')
        ->once()
        ->withArgs(function (array $params) {
            expect($params['chat_id'])->toBe(999);
            expect($params['text'])->toContain('approval');
            expect($params)->not->toHaveKey('reply_markup');

            return true;
        });

    (new StartCommand)->make($telegram, telegramStartUpdate(999, 'Bob', 'bob'), []);

    $request = TelegramAccessRequest::where('chat_id', 999)->sole();

    expect($request->status)->toBe(TelegramAccessStatus::Pending);
    expect($request->first_name)->toBe('Bob');
    expect($request->username)->toBe('bob');
});

it('does not create a duplicate pending request for a repeated /start', function () {
    config(['insurance-bot.allowed_chat_ids' => []]);

    $telegram = Mockery::mock(Api::class);
    $telegram->shouldReceive('sendMessage')->twice();

    (new StartCommand)->make($telegram, telegramStartUpdate(999), []);
    (new StartCommand)->make($telegram, telegramStartUpdate(999), []);

    expect(TelegramAccessRequest::where('chat_id', 999)->count())->toBe(1);
});

it('resets a previously rejected request back to pending on a new /start', function () {
    config(['insurance-bot.allowed_chat_ids' => []]);

    TelegramAccessRequest::factory()->rejected()->create(['chat_id' => 999]);

    $telegram = Mockery::mock(Api::class);
    $telegram->shouldReceive('sendMessage')->once();

    (new StartCommand)->make($telegram, telegramStartUpdate(999), []);

    expect(TelegramAccessRequest::where('chat_id', 999)->sole()->status)->toBe(TelegramAccessStatus::Pending);
});
