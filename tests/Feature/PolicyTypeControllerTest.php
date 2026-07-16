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

it('renders the policy types settings page', function () {
    PolicyType::factory()->create(['name' => 'Motor']);
    PolicyType::factory()->create(['name' => 'Fire']);

    authenticatePolicyTypeChat($this->chatId, $this->botToken);

    $this->get('/policy-types')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/PolicyTypes')
            ->where('policyTypes.data.0.name', 'Fire')
            ->where('policyTypes.data.1.name', 'Motor'));
});

it('paginates policy types at 10 per page', function () {
    PolicyType::factory()->count(11)->create();

    authenticatePolicyTypeChat($this->chatId, $this->botToken);

    $this->get('/policy-types')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policyTypes.data', fn ($data) => count($data) === 10)
            ->where('policyTypes.total', 11)
            ->where('policyTypes.last_page', 2));

    $this->get('/policy-types?page=2')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('policyTypes.data', fn ($data) => count($data) === 1));
});

it('searches policy types by name', function () {
    PolicyType::factory()->create(['name' => 'Motor']);
    PolicyType::factory()->create(['name' => 'Fire']);

    authenticatePolicyTypeChat($this->chatId, $this->botToken);

    $this->get('/policy-types?search=Fir')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policyTypes.data.0.name', 'Fire')
            ->where('policyTypes.total', 1)
            ->where('filters.search', 'Fir'));
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

it('updates a policy type name', function () {
    $policyType = PolicyType::factory()->create(['name' => 'Motor']);

    authenticatePolicyTypeChat($this->chatId, $this->botToken);

    $response = $this->patchJson("/policy-types/{$policyType->id}", ['name' => 'Motor Vehicle']);

    $response->assertOk();
    $response->assertJsonFragment(['name' => 'Motor Vehicle']);
    expect($policyType->fresh()->name)->toBe('Motor Vehicle');
});

it('rejects renaming a policy type to a name already taken', function () {
    PolicyType::factory()->create(['name' => 'Fire']);
    $policyType = PolicyType::factory()->create(['name' => 'Motor']);

    authenticatePolicyTypeChat($this->chatId, $this->botToken);

    $response = $this->patchJson("/policy-types/{$policyType->id}", ['name' => 'Fire']);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('name');
    expect($policyType->fresh()->name)->toBe('Motor');
});

it('deletes a policy type', function () {
    $policyType = PolicyType::factory()->create();

    authenticatePolicyTypeChat($this->chatId, $this->botToken);

    $response = $this->deleteJson("/policy-types/{$policyType->id}");

    $response->assertOk();
    expect(PolicyType::find($policyType->id))->toBeNull();
});
