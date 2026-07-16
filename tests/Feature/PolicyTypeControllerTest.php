<?php

use App\Models\PolicyType;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;

function authenticatePolicyTypeChat(int $chatId, string $botToken): void
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

    $this->mock(Api::class, function ($mock) {
        $mock->shouldReceive('sendMessage')->andReturn(new Message([]));
    });
});

it('redirects to the launch page when no session is established', function () {
    $this->postJson('/policy-types', ['name' => 'Motor'])->assertRedirect('/telegram/launch');
});

it('creates a new policy type', function () {
    authenticatePolicyTypeChat($this->chatId, $this->botToken);

    $response = $this->postJson('/policy-types', ['name' => 'Motor']);

    $response->assertCreated();
    $response->assertJsonFragment(['name' => 'Motor']);
    expect(PolicyType::where('name', 'Motor')->exists())->toBeTrue();
});

it('rejects a duplicate policy type name', function () {
    PolicyType::factory()->create(['name' => 'Motor']);

    authenticatePolicyTypeChat($this->chatId, $this->botToken);

    $response = $this->postJson('/policy-types', ['name' => 'Motor']);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('name');
    expect(PolicyType::where('name', 'Motor')->count())->toBe(1);
});

it('requires a name', function () {
    authenticatePolicyTypeChat($this->chatId, $this->botToken);

    $response = $this->postJson('/policy-types', ['name' => '']);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('name');
});
