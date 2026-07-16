<?php

use App\Models\Insurance;
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

it('renders the insurance companies settings page', function () {
    InsuranceCompany::factory()->create(['name' => 'Lonpac']);
    InsuranceCompany::factory()->create(['name' => 'Infinity']);

    authenticateChat($this->chatId, $this->botToken);

    $this->get('/insurance-companies')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/InsuranceCompanies')
            ->where('insuranceCompanies.data.0.name', 'Infinity')
            ->where('insuranceCompanies.data.1.name', 'Lonpac'));
});

it('paginates insurance companies at 10 per page', function () {
    InsuranceCompany::factory()->count(11)->create();

    authenticateChat($this->chatId, $this->botToken);

    $this->get('/insurance-companies')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('insuranceCompanies.data', fn ($data) => count($data) === 10)
            ->where('insuranceCompanies.total', 11)
            ->where('insuranceCompanies.last_page', 2));

    $this->get('/insurance-companies?page=2')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('insuranceCompanies.data', fn ($data) => count($data) === 1));
});

it('searches insurance companies by name', function () {
    InsuranceCompany::factory()->create(['name' => 'Lonpac']);
    InsuranceCompany::factory()->create(['name' => 'Infinity']);

    authenticateChat($this->chatId, $this->botToken);

    $this->get('/insurance-companies?search=Lon')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('insuranceCompanies.data.0.name', 'Lonpac')
            ->where('insuranceCompanies.total', 1)
            ->where('filters.search', 'Lon'));
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

it('updates an insurance company name', function () {
    $company = InsuranceCompany::factory()->create(['name' => 'Lonpac']);

    authenticateChat($this->chatId, $this->botToken);

    $response = $this->patchJson("/insurance-companies/{$company->id}", ['name' => 'Lonpac Insurance']);

    $response->assertOk();
    $response->assertJsonFragment(['name' => 'Lonpac Insurance']);
    expect($company->fresh()->name)->toBe('Lonpac Insurance');
});

it('rejects renaming an insurance company to a name already taken', function () {
    InsuranceCompany::factory()->create(['name' => 'Infinity']);
    $company = InsuranceCompany::factory()->create(['name' => 'Lonpac']);

    authenticateChat($this->chatId, $this->botToken);

    $response = $this->patchJson("/insurance-companies/{$company->id}", ['name' => 'Infinity']);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('name');
    expect($company->fresh()->name)->toBe('Lonpac');
});

it('allows updating an insurance company without changing its name', function () {
    $company = InsuranceCompany::factory()->create(['name' => 'Lonpac']);

    authenticateChat($this->chatId, $this->botToken);

    $response = $this->patchJson("/insurance-companies/{$company->id}", ['name' => 'Lonpac']);

    $response->assertOk();
});

it('deletes an insurance company', function () {
    $company = InsuranceCompany::factory()->create();

    authenticateChat($this->chatId, $this->botToken);

    $response = $this->deleteJson("/insurance-companies/{$company->id}");

    $response->assertOk();
    expect(InsuranceCompany::find($company->id))->toBeNull();
});

it('refuses to delete an insurance company used by an existing policy', function () {
    $company = InsuranceCompany::factory()->create();
    Insurance::factory()->create(['insurance_company_id' => $company->id]);

    authenticateChat($this->chatId, $this->botToken);

    $response = $this->deleteJson("/insurance-companies/{$company->id}");

    $response->assertConflict();
    expect(InsuranceCompany::find($company->id))->not->toBeNull();
});
