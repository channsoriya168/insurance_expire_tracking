<?php

use App\Models\Insurance;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;

beforeEach(function () {
    $this->chatId = 111;

    config(['insurance-bot.allowed_chat_ids' => [$this->chatId]]);

    $this->mock(Api::class, function ($mock) {
        $mock->shouldReceive('sendMessage')->andReturn(new Message([]));
    });
});

function signedIndexUrl(int $chatId): string
{
    return URL::temporarySignedRoute('insurances.index', now()->addMinutes(10), ['chat' => $chatId]);
}

it('rejects a signed link for a chat id that is not allowed', function () {
    $this->get(signedIndexUrl(999))->assertForbidden();
});

it('rejects an unsigned request with no established session', function () {
    $this->get('/insurances')->assertForbidden();
});

it('establishes a session from a valid signed link and renders the list', function () {
    $this->get(signedIndexUrl($this->chatId))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Insurances/Index'));

    expect(session('telegram_chat_id'))->toBe($this->chatId);
});

it('allows subsequent unsigned navigation once the session is established', function () {
    $this->get(signedIndexUrl($this->chatId))->assertOk();

    $this->get('/insurances')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Insurances/Index'));
});

it('creates a policy and notifies the telegram chat', function () {
    $this->mock(Api::class, function ($mock) {
        $mock->shouldReceive('sendMessage')->once()
            ->withArgs(fn (array $params) => $params['chat_id'] === $this->chatId
                && str_contains($params['text'], 'Saved policy')
                && str_contains($params['text'], 'Y25TEST00099'))
            ->andReturn(new Message([]));
    });

    $this->get(signedIndexUrl($this->chatId))->assertOk();

    $response = $this->post('/insurances', insuranceFormPayload());

    $response->assertRedirect('/insurances');
    expect(Insurance::where('policy_no', 'Y25TEST00099')->exists())->toBeTrue();
});

it('rejects invalid input without creating a policy', function () {
    $this->get(signedIndexUrl($this->chatId))->assertOk();

    $response = $this->post('/insurances', insuranceFormPayload(['expiry_date' => 'not-a-date']));

    $response->assertInvalid(['expiry_date']);
    expect(Insurance::where('policy_no', 'Y25TEST00099')->exists())->toBeFalse();
});

it('updates a policy field via the edit form', function () {
    $insurance = Insurance::factory()->create(['insurance_company' => 'Lonpac']);

    $this->get(signedIndexUrl($this->chatId))->assertOk();

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
                && str_contains($params['text'], "Deleted policy {$insurance->policy_no}"))
            ->andReturn(new Message([]));
    });

    $this->get(signedIndexUrl($this->chatId))->assertOk();

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
