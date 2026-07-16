<?php

use App\Enums\PaymentStatus;
use App\Enums\PolicyStatus;
use App\Models\Insurance;
use App\Models\InsuranceCompany;
use App\Models\PolicyType;
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

it('passes insurance company and policy type options to the create form', function () {
    InsuranceCompany::factory()->create(['name' => 'Lonpac']);
    PolicyType::factory()->create(['name' => 'Motor']);

    authenticate($this->chatId, $this->botToken);

    $this->get('/insurances/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Insurances/Create')
            ->where('insuranceCompanies.0.name', 'Lonpac')
            ->where('policyTypes.0.name', 'Motor'));
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

it('rejects a policy without a sum insured', function () {
    authenticate($this->chatId, $this->botToken);

    $response = $this->post('/insurances', insuranceFormPayload(['sum_insured' => '']));

    $response->assertInvalid(['sum_insured']);
    expect(Insurance::where('policy_no', 'Y25TEST00099')->exists())->toBeFalse();
});

it('rejects a policy with only the premium provided', function () {
    authenticate($this->chatId, $this->botToken);

    $response = $this->post('/insurances', [
        'insurance_company_id' => '',
        'policy_no' => '',
        'contact_method' => '',
        'contact_value' => '',
        'contact_person' => '',
        'insured_name' => '',
        'expiry_date' => '',
        'policy_type_id' => '',
        'sum_insured' => '',
        'premium' => '500',
        'net_premium' => '',
        'revised_sum_insured' => '',
        'revised_premium' => '',
        'revised_premium_rate' => '',
        'confirmed_date' => '',
        'status' => '',
        'payment_status' => '',
        'payment_date' => '',
        'policy_received_date' => '',
        'remarks' => '',
    ]);

    $response->assertInvalid([
        'insurance_company_id', 'policy_no', 'contact_method', 'contact_value', 'contact_person',
        'insured_name', 'expiry_date', 'policy_type_id', 'sum_insured', 'net_premium',
        'revised_sum_insured', 'revised_premium', 'revised_premium_rate',
    ]);
    expect(Insurance::where('premium', 500)->exists())->toBeFalse();
});

it('defaults status and payment status when left blank', function () {
    authenticate($this->chatId, $this->botToken);

    $response = $this->post('/insurances', insuranceFormPayload());

    $response->assertRedirect('/insurances');

    $insurance = Insurance::where('policy_no', 'Y25TEST00099')->first();
    expect($insurance->status)->toBe(PolicyStatus::Pending);
    expect($insurance->payment_status)->toBe(PaymentStatus::Unpaid);
});

it('rejects invalid input without creating a policy', function () {
    authenticate($this->chatId, $this->botToken);

    $response = $this->post('/insurances', insuranceFormPayload(['expiry_date' => 'not-a-date']));

    $response->assertInvalid(['expiry_date']);
    expect(Insurance::where('policy_no', 'Y25TEST00099')->exists())->toBeFalse();
});

it('rejects a policy with an insurance company or policy type that does not exist', function () {
    authenticate($this->chatId, $this->botToken);

    $response = $this->post('/insurances', insuranceFormPayload(['insurance_company_id' => 999999]));

    $response->assertInvalid(['insurance_company_id']);
    expect(Insurance::where('policy_no', 'Y25TEST00099')->exists())->toBeFalse();
});

it('updates a policy field via the edit form', function () {
    $lonpac = InsuranceCompany::factory()->create(['name' => 'Lonpac']);
    $infinity = InsuranceCompany::factory()->create(['name' => 'Infinity']);
    $insurance = Insurance::factory()->create(['insurance_company_id' => $lonpac->id]);

    authenticate($this->chatId, $this->botToken);

    $response = $this->put("/insurances/{$insurance->id}", insuranceFormPayload([
        'policy_no' => $insurance->policy_no,
        'insurance_company_id' => $infinity->id,
    ]));

    $response->assertRedirect('/insurances');
    expect($insurance->fresh()->insuranceCompany->name)->toBe('Infinity');
});

it('quick-updates a policy payment status from the list', function () {
    $insurance = Insurance::factory()->create(['payment_status' => 'Unpaid']);

    authenticate($this->chatId, $this->botToken);

    $response = $this->patch("/insurances/{$insurance->id}/payment-status", ['payment_status' => 'Paid']);

    $response->assertRedirect();
    expect($insurance->fresh()->payment_status)->toBe(PaymentStatus::Paid);
});

it('rejects an invalid payment status value', function () {
    $insurance = Insurance::factory()->create(['payment_status' => 'Unpaid']);

    authenticate($this->chatId, $this->botToken);

    $response = $this->patch("/insurances/{$insurance->id}/payment-status", ['payment_status' => 'Not A Status']);

    $response->assertInvalid(['payment_status']);
    expect($insurance->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

it('redirects to the launch page when duplicating without a session', function () {
    $insurance = Insurance::factory()->create();

    $this->get("/insurances/{$insurance->id}/duplicate")->assertRedirect('/telegram/launch');
});

it('pre-fills the create form from an existing policy when duplicating', function () {
    $lonpac = InsuranceCompany::factory()->create(['name' => 'Lonpac']);

    $insurance = Insurance::factory()->create([
        'insurance_company_id' => $lonpac->id,
        'policy_no' => 'Y25VC31008507',
        'insured_name' => 'SIGLO (CAMBODIA) CO., LTD.',
        'sum_insured' => '200000.00',
        'expiry_date' => '2026-07-16',
        'status' => 'Confirmed',
        'payment_status' => 'Paid',
        'confirmed_date' => '2025-08-01',
        'payment_date' => '2025-08-05',
        'policy_received_date' => '2025-08-10',
    ]);

    authenticate($this->chatId, $this->botToken);

    $this->get("/insurances/{$insurance->id}/duplicate")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Insurances/Create')
            ->where('duplicateFrom.insurance_company_id', $lonpac->id)
            ->where('duplicateFrom.insured_name', 'SIGLO (CAMBODIA) CO., LTD.')
            ->where('duplicateFrom.sum_insured', '200000.00')
            ->missing('duplicateFrom.id')
            ->missing('duplicateFrom.policy_no')
            ->missing('duplicateFrom.expiry_date')
            ->missing('duplicateFrom.status')
            ->missing('duplicateFrom.payment_status')
            ->missing('duplicateFrom.confirmed_date')
            ->missing('duplicateFrom.payment_date')
            ->missing('duplicateFrom.policy_received_date'));
});

it('creates a new policy from a duplicated one', function () {
    $lonpac = InsuranceCompany::factory()->create(['name' => 'Lonpac']);

    $original = Insurance::factory()->create([
        'insurance_company_id' => $lonpac->id,
        'insured_name' => 'SIGLO (CAMBODIA) CO., LTD.',
    ]);

    authenticate($this->chatId, $this->botToken);

    $response = $this->post('/insurances', insuranceFormPayload([
        'insurance_company_id' => $lonpac->id,
        'insured_name' => 'SIGLO (CAMBODIA) CO., LTD.',
        'policy_no' => 'Y26VC31008507',
    ]));

    $response->assertRedirect('/insurances');
    $duplicate = Insurance::where('policy_no', 'Y26VC31008507')->first();
    expect($duplicate)->not->toBeNull();
    expect($duplicate->id)->not->toBe($original->id);
    expect($duplicate->insuranceCompany->name)->toBe('Lonpac');
    expect($duplicate->status)->toBe(PolicyStatus::Pending);
    expect($duplicate->payment_status)->toBe(PaymentStatus::Unpaid);
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

it('filters the list by insurance company name', function () {
    $lonpac = InsuranceCompany::factory()->create(['name' => 'Lonpac Insurance']);
    $other = InsuranceCompany::factory()->create(['name' => 'Infinity Insurance']);
    Insurance::factory()->create(['insurance_company_id' => $lonpac->id, 'policy_no' => 'Y25TEST00001']);
    Insurance::factory()->create(['insurance_company_id' => $other->id, 'policy_no' => 'Y25TEST00002']);

    authenticate($this->chatId, $this->botToken);

    $this->get('/insurances?search=Lonpac')
        ->assertInertia(fn ($page) => $page
            ->where('insurances.data.0.policy_no', 'Y25TEST00001')
            ->where('insurances.data.0.insurance_company', 'Lonpac Insurance')
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
        'insurance_company_id' => InsuranceCompany::firstOrCreate(['name' => 'Lonpac'])->id,
        'policy_no' => 'Y25TEST00099',
        'contact_method' => 'Email',
        'contact_value' => 'client@example.com',
        'contact_person' => 'John Client',
        'insured_name' => 'Test Garment Co., Ltd.',
        'expiry_date' => '2026-12-31',
        'policy_type_id' => PolicyType::firstOrCreate(['name' => 'Fire'])->id,
        'sum_insured' => '100000',
        'premium' => '500',
        'net_premium' => '425',
        'revised_sum_insured' => '100000',
        'revised_premium' => '500',
        'revised_premium_rate' => '0.500',
        'confirmed_date' => '',
        'status' => '',
        'payment_status' => '',
        'payment_date' => '',
        'policy_received_date' => '',
        'remarks' => 'Test remarks',
    ], $overrides);
}
