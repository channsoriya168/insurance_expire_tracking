<?php

use App\Models\Insurance;
use Illuminate\Support\Facades\URL;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;

function allowedChatId(): int
{
    return 111;
}

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

beforeEach(function () {
    config(['insurance-bot.allowed_chat_ids' => [allowedChatId()]]);

    $this->mock(Api::class, function ($mock) {
        $mock->shouldReceive('sendMessage')->andReturn(new Message([]));
    });
});

it('rejects a signed link for a chat id that is not allowed', function () {
    $url = URL::temporarySignedRoute('forms.insurances.create', now()->addMinutes(10), ['chat' => 999]);

    $this->get($url)->assertForbidden();
});

it('rejects an unsigned request even for an allowed chat id', function () {
    $this->get('/forms/insurances/create?chat='.allowedChatId())->assertForbidden();
});

it('shows the create form for a valid signed link', function () {
    $url = URL::temporarySignedRoute('forms.insurances.create', now()->addMinutes(10), ['chat' => allowedChatId()]);

    $this->get($url)->assertOk()->assertSee('Add a New Policy');
});

it('creates a policy from valid form input', function () {
    $url = URL::temporarySignedRoute('forms.insurances.create', now()->addMinutes(10), ['chat' => allowedChatId()]);

    $response = $this->post($url, insuranceFormPayload());

    $response->assertOk()->assertSee('Saved policy');
    expect(Insurance::where('policy_no', 'Y25TEST00099')->exists())->toBeTrue();
});

it('rejects invalid form input without creating a policy', function () {
    $url = URL::temporarySignedRoute('forms.insurances.create', now()->addMinutes(10), ['chat' => allowedChatId()]);

    $response = $this->post($url, insuranceFormPayload(['expiry_date' => 'not-a-date']));

    $response->assertOk()->assertSee('Please send a valid date');
    expect(Insurance::where('policy_no', 'Y25TEST00099')->exists())->toBeFalse();
});

it('looks up a policy by number then updates a field via the edit form', function () {
    $insurance = Insurance::factory()->create(['insurance_company' => 'Lonpac']);

    $url = URL::temporarySignedRoute('forms.insurances.edit', now()->addMinutes(10), ['chat' => allowedChatId()]);

    $lookup = $this->post($url, ['policy_no' => $insurance->policy_no]);
    $lookup->assertOk()->assertSee('Editing');

    $payload = insuranceFormPayload([
        'policy_no' => $insurance->policy_no,
        'insurance_company' => 'Infinity',
        'insurance_id' => $insurance->id,
    ]);

    $update = $this->post($url, $payload);

    $update->assertOk()->assertSee('Updated policy');
    expect($insurance->fresh()->insurance_company)->toBe('Infinity');
});

it('jumps straight to the prefilled edit form when policy_no is embedded in the link', function () {
    $insurance = Insurance::factory()->create();

    $url = URL::temporarySignedRoute('forms.insurances.edit', now()->addMinutes(10), [
        'chat' => allowedChatId(),
        'policy_no' => $insurance->policy_no,
    ]);

    $this->get($url)->assertOk()->assertSee('Editing');
});

it('looks up a policy then deletes it after confirmation', function () {
    $insurance = Insurance::factory()->create();

    $url = URL::temporarySignedRoute('forms.insurances.delete', now()->addMinutes(10), ['chat' => allowedChatId()]);

    $lookup = $this->post($url, ['policy_no' => $insurance->policy_no]);
    $lookup->assertOk()->assertSee('Confirm you want to permanently delete');

    $confirm = $this->post($url, ['insurance_id' => $insurance->id]);
    $confirm->assertOk()->assertSee('Deleted policy');

    expect(Insurance::find($insurance->id))->toBeNull();
});

it('downloads an xlsx export for the default all filter', function () {
    Insurance::factory()->count(3)->create();

    $url = URL::temporarySignedRoute('forms.insurances.export', now()->addMinutes(10), ['chat' => allowedChatId()]);

    $response = $this->post($url, ['filter' => 'all']);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('spreadsheetml');
});

it('shows an error instead of downloading when the filter matches nothing', function () {
    Insurance::factory()->create(['expiry_date' => '2020-01-01']);

    $url = URL::temporarySignedRoute('forms.insurances.export', now()->addMinutes(10), ['chat' => allowedChatId()]);

    $response = $this->post($url, ['filter' => '2099-01']);

    $response->assertOk()->assertSee('No policies found');
});
