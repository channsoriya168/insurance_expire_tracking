<?php

use App\Enums\PolicyStatus;
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

it('shows a policy full details', function () {
    $insurance = Insurance::factory()->create(['insured_name' => 'Test Garment Co., Ltd.']);

    authenticate($this->chatId, $this->botToken);

    $this->get("/insurances/{$insurance->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Insurances/Show')
            ->where('insurance.id', $insurance->id)
            ->where('insurance.insured_name', 'Test Garment Co., Ltd.'));
});

it('marks the notification read when viewing details from the notifications list', function () {
    $insurance = Insurance::factory()->expiringInDays(10)->create();

    authenticate($this->chatId, $this->botToken);

    expect($insurance->notification->read_at)->toBeNull();

    $this->get("/insurances/{$insurance->id}?from=notifications")->assertOk();

    expect($insurance->notification->fresh()->read_at)->not->toBeNull();
});

it('does not mark the notification read when viewing details from elsewhere', function () {
    $insurance = Insurance::factory()->expiringInDays(10)->create();

    authenticate($this->chatId, $this->botToken);

    $this->get("/insurances/{$insurance->id}")->assertOk();

    expect($insurance->notification->fresh()->read_at)->toBeNull();
});

it('creates a policy without notifying the telegram chat', function () {
    $this->mock(Api::class, function ($mock) {
        $mock->shouldNotReceive('sendMessage');
    });

    authenticate($this->chatId, $this->botToken);

    $response = $this->post('/insurances', insuranceFormPayload());

    $response->assertRedirect('/insurances');
    $response->assertSessionHas('status', 'Policy Y25TEST00099 saved.');
    expect(Insurance::where('policy_no', 'Y25TEST00099')->exists())->toBeTrue();
});

it('creates a policy without a sum insured', function () {
    authenticate($this->chatId, $this->botToken);

    $response = $this->post('/insurances', insuranceFormPayload(['sum_insured' => '']));

    $response->assertRedirect('/insurances');
    $response->assertSessionDoesntHaveErrors();
    expect(Insurance::where('policy_no', 'Y25TEST00099')->first()->sum_insured)->toBeNull();
});

it('creates a policy with only the premium provided', function () {
    authenticate($this->chatId, $this->botToken);

    $response = $this->post('/insurances', [
        'insurance_company' => '',
        'policy_no' => '',
        'contact_method' => '',
        'contact_value' => '',
        'contact_person' => '',
        'insured_name' => '',
        'expiry_date' => '',
        'policy_type' => '',
        'sum_insured' => '',
        'premium' => '500',
        'revised_sum_insured' => '',
        'revised_premium' => '',
        'revised_premium_rate' => '',
        'confirmed_date' => '',
        'status' => '',
        'request_policy_date' => '',
        'policy_received_date' => '',
        'remarks' => '',
    ]);

    $response->assertRedirect('/insurances');
    $response->assertSessionDoesntHaveErrors();

    $insurance = Insurance::where('premium', 500)->first();
    expect($insurance)->not->toBeNull();
    expect($insurance->status)->toBe(PolicyStatus::Pending);
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

it('filters the list by expiry and search', function () {
    Insurance::factory()->create(['expiry_date' => today(), 'policy_no' => 'Y25TEST00001']);
    Insurance::factory()->create(['expiry_date' => today()->addDays(10), 'policy_no' => 'Y25TEST00002']);
    Insurance::factory()->create(['expiry_date' => today()->addDays(90), 'policy_no' => 'Y25TEST00003']);

    authenticate($this->chatId, $this->botToken);

    $this->get('/insurances?expiry=today')
        ->assertInertia(fn ($page) => $page
            ->where('filters.expiry', 'today')
            ->where('insurances.data.0.policy_no', 'Y25TEST00001')
            ->where('insurances.total', 1));

    $this->get('/insurances?expiry=10')
        ->assertInertia(fn ($page) => $page
            ->where('filters.expiry', '10')
            ->where('insurances.data.0.policy_no', 'Y25TEST00002')
            ->where('insurances.total', 1));

    $this->get('/insurances?search=Y25TEST00003')
        ->assertInertia(fn ($page) => $page
            ->where('filters.search', 'Y25TEST00003')
            ->where('insurances.total', 1));
});

it('filters the list by expiry ranges and expired policies', function () {
    Insurance::factory()->create(['expiry_date' => today()->subDay(), 'policy_no' => 'Y25TEST00001']);
    Insurance::factory()->create(['expiry_date' => today()->addDays(5), 'policy_no' => 'Y25TEST00002']);
    Insurance::factory()->create(['expiry_date' => today()->addDays(15), 'policy_no' => 'Y25TEST00003']);
    Insurance::factory()->create(['expiry_date' => today()->addDays(25), 'policy_no' => 'Y25TEST00004']);

    authenticate($this->chatId, $this->botToken);

    $this->get('/insurances?expiry=expired')
        ->assertInertia(fn ($page) => $page
            ->where('filters.expiry', 'expired')
            ->where('insurances.data.0.policy_no', 'Y25TEST00001')
            ->where('insurances.total', 1));

    $this->get('/insurances?expiry=10')
        ->assertInertia(fn ($page) => $page
            ->where('insurances.data.0.policy_no', 'Y25TEST00002')
            ->where('insurances.total', 1));

    $this->get('/insurances?expiry=20')
        ->assertInertia(fn ($page) => $page
            ->where('insurances.data.0.policy_no', 'Y25TEST00003')
            ->where('insurances.total', 1));

    $this->get('/insurances?expiry=30')
        ->assertInertia(fn ($page) => $page
            ->where('insurances.data.0.policy_no', 'Y25TEST00004')
            ->where('insurances.total', 1));
});

it('deletes a policy without notifying the telegram chat', function () {
    $insurance = Insurance::factory()->create();

    $this->mock(Api::class, function ($mock) {
        $mock->shouldNotReceive('sendMessage');
    });

    authenticate($this->chatId, $this->botToken);

    $response = $this->delete("/insurances/{$insurance->id}");

    $response->assertRedirect('/insurances');
    $response->assertSessionHas('status', "Policy {$insurance->policy_no} deleted.");
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
