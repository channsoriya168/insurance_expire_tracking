<?php

use App\Models\Insurance;
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

function authenticate(int $chatId, string $botToken): void
{
    $initData = buildTelegramInitData($chatId, $botToken);

    test()->post('/telegram/auth', ['init_data' => $initData])->assertRedirect('/insurances');
}

it('redirects to the launch page when no session is established', function () {
    $this->get('/insurances')->assertRedirect('/telegram/launch');
});

it('rejects auth for a chat id that is not allowed then keeps redirecting to launch', function () {
    $initData = buildTelegramInitData(999, $this->botToken);

    $this->post('/telegram/auth', ['init_data' => $initData])->assertForbidden();

    $this->get('/insurances')->assertRedirect('/telegram/launch');
});

it('establishes a session via the initData handshake and renders the list', function () {
    authenticate($this->chatId, $this->botToken);

    $this->get('/insurances')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Insurances/Index'));

    expect(session('telegram_chat_id'))->toBe($this->chatId);
});

it('creates a policy and notifies the telegram chat', function () {
    $this->mock(Api::class, function ($mock) {
        $mock->shouldReceive('sendMessage')->once()
            ->withArgs(fn (array $params) => $params['chat_id'] === $this->chatId
                && str_contains($params['text'], 'បានរក្សាទុកបណ្ណសន្យារ៉ាប់រង')
                && str_contains($params['text'], 'Y25TEST00099'))
            ->andReturn(new Message([]));
    });

    authenticate($this->chatId, $this->botToken);

    $response = $this->post('/insurances', insuranceFormPayload());

    $response->assertRedirect('/insurances');
    expect(Insurance::where('policy_no', 'Y25TEST00099')->exists())->toBeTrue();
});

it('rejects invalid input without creating a policy', function () {
    authenticate($this->chatId, $this->botToken);

    $response = $this->post('/insurances', insuranceFormPayload(['expiry_date' => 'not-a-date']));

    $response->assertInvalid(['expiry_date']);
    expect(Insurance::where('policy_no', 'Y25TEST00099')->exists())->toBeFalse();
});

it('updates a policy field via the edit form', function () {
    $insurance = Insurance::factory()->create(['insurance_company' => 'Lonpac']);

    authenticate($this->chatId, $this->botToken);

    $response = $this->put("/insurances/{$insurance->id}", insuranceFormPayload([
        'policy_no' => $insurance->policy_no,
        'insurance_company' => 'Infinity',
    ]));

    $response->assertRedirect('/insurances');
    expect($insurance->fresh()->insurance_company)->toBe('Infinity');
});

it('deletes a policy and notifies the telegram chat', function () {
    $insurance = Insurance::factory()->create();

    $this->mock(Api::class, function ($mock) use ($insurance) {
        $mock->shouldReceive('sendMessage')->once()
            ->withArgs(fn (array $params) => $params['chat_id'] === $this->chatId
                && str_contains($params['text'], "បានលុបបណ្ណសន្យា {$insurance->policy_no}"))
            ->andReturn(new Message([]));
    });

    authenticate($this->chatId, $this->botToken);

    $this->delete("/insurances/{$insurance->id}")->assertRedirect('/insurances');

    expect(Insurance::find($insurance->id))->toBeNull();
});

function insuranceFormPayload(array $overrides = []): array
{
    return array_merge([
        'insurance_company' => 'Lonpac',
        'policy_no' => 'Y25TEST00099',
        'contact_method' => 'Email',
        'contact_value' => 'client@example.com',
        'contact_person' => 'John Client',
        'insured_name' => 'Test Garment Co., Ltd.',
        'expiry_date' => '2026-12-31',
        'policy_type' => 'Fire',
        'sum_insured' => '100000',
        'premium' => '500',
        'revised_sum_insured' => '',
        'revised_premium' => '',
        'revised_premium_rate' => '',
        'confirmed_date' => '',
        'status' => '',
        'request_policy_date' => '',
        'policy_received_date' => '',
        'remarks' => '',
    ], $overrides);
}
