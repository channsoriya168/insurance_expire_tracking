<?php

use App\Models\InsuranceCompany;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;

function authenticateChat(int $chatId, string $botToken): void
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
    $this->postJson('/insurance-companies', ['name' => 'Lonpac'])->assertRedirect('/telegram/launch');
});

it('creates a new insurance company', function () {
    authenticateChat($this->chatId, $this->botToken);

    $response = $this->postJson('/insurance-companies', ['name' => 'Lonpac']);

    $response->assertCreated();
    $response->assertJsonFragment(['name' => 'Lonpac']);
    expect(InsuranceCompany::where('name', 'Lonpac')->exists())->toBeTrue();
});

it('rejects a duplicate insurance company name', function () {
    InsuranceCompany::factory()->create(['name' => 'Lonpac']);

    authenticateChat($this->chatId, $this->botToken);

    $response = $this->postJson('/insurance-companies', ['name' => 'Lonpac']);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('name');
    expect(InsuranceCompany::where('name', 'Lonpac')->count())->toBe(1);
});

it('requires a name', function () {
    authenticateChat($this->chatId, $this->botToken);

    $response = $this->postJson('/insurance-companies', ['name' => '']);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('name');
});
