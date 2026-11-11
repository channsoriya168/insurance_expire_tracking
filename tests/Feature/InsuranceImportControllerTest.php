<?php

use App\Models\Insurance;
use App\Models\InsuranceCompany;
use App\Models\PolicyType;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

    $initData = buildTelegramInitData($this->chatId, $this->botToken);
    $this->post('/telegram/auth', ['init_data' => $initData])->assertRedirect('/insurances');
});

/**
 * @param  list<list<mixed>>  $rows
 */
function fakeInsurancesUpload(array $rows): UploadedFile
{
    $headers = [
        'NO.', "INSURED NAME (Clients' name)", 'Policy Type', 'Contact Person', 'CONTACT METHOD',
        'Contact Name', 'INS Company', 'POLICY NO.:', 'EXPIRY DATE', 'SUM insured', 'PRE',
        'REVISED SUM INS (X)', 'REVISED PRE (Y) PRE Data', ' REVISED Pre Rate (Y/X)',
        'Net PRE 85% of Y', 'CONFIRMED (DD-MM-YY)', 'STATUS', 'PAYMENT DATE', 'POL RECEIVED DATE', 'REMARKS',
    ];

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($headers, null, 'A1');
    $sheet->fromArray($rows, null, 'A2');

    $path = tempnam(sys_get_temp_dir(), 'import').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'input.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

it('imports rows, maps policy type abbreviations, and defaults a blank company to Lonpac', function () {
    $file = fakeInsurancesUpload([
        [1, 'Morakot Co Ltd', 'PG', 'Lao', 'WeChat', 'Lao', null, 'Y25PG0001', '2027-01-01', 500000, 1773, null, null, null, null, null, null, null, null, null],
        [2, 'Ong Kwee Tong', 'LL', 'Lao', 'WeChat', 'Lao', null, 'Y25LL0002', '2027-02-01', 33885, 233, null, null, null, null, null, null, null, null, null],
    ]);

    $this->post('/insurances-import', ['file' => $file])
        ->assertRedirect('/insurances')
        ->assertSessionHas('status', 'Imported 2 policies. Created 1 new company. Created 2 new policy types.');

    $lonpac = InsuranceCompany::query()->where('name', 'Lonpac')->first();
    expect($lonpac)->not->toBeNull();

    $medical = PolicyType::query()->where('name', 'Medical')->first();
    $legalLiability = PolicyType::query()->where('name', 'Legal Liability / Public Liability')->first();
    expect($medical)->not->toBeNull();
    expect($legalLiability)->not->toBeNull();

    $policy = Insurance::query()->where('policy_no', 'Y25PG0001')->first();
    expect($policy)->not->toBeNull();
    expect($policy->insurance_company_id)->toBe($lonpac->id);
    expect($policy->policy_type_id)->toBe($medical->id);
    expect((float) $policy->revised_sum_insured)->toBe(500000.0);
    expect((float) $policy->revised_premium)->toBe(1773.0);
    expect((float) $policy->net_premium)->toBe(1507.05);

    $second = Insurance::query()->where('policy_no', 'Y25LL0002')->first();
    expect($second->policy_type_id)->toBe($legalLiability->id);
});

it('skips rows missing a policy no, missing an expiry date, or duplicating an existing policy', function () {
    $existing = Insurance::factory()->create(['policy_no' => 'DUPLICATE-1']);

    $file = fakeInsurancesUpload([
        [1, 'Missing Policy No', 'PG', null, null, null, null, null, '2027-01-01', 1000, 100, null, null, null, null, null, null, null, null, null],
        [2, 'Missing Expiry Date', 'PG', null, null, null, null, 'Y25PG0003', null, 1000, 100, null, null, null, null, null, null, null, null, null],
        [3, 'Duplicate Policy', 'PG', null, null, null, null, $existing->policy_no, '2027-01-01', 1000, 100, null, null, null, null, null, null, null, null, null],
    ]);

    $this->post('/insurances-import', ['file' => $file])
        ->assertRedirect('/insurances')
        ->assertSessionHas('status', 'Imported 0 policies. Skipped 3 (1 missing Policy No., 1 missing Expiry Date, 1 duplicate Policy No.).');

    expect(Insurance::query()->count())->toBe(1);
});

it('defaults missing contact info to placeholders instead of failing validation', function () {
    $file = fakeInsurancesUpload([
        [1, 'Ealytex Garment Co', null, null, null, null, 'INFINITY', 'INF-0001', '2027-01-01', null, 177, null, null, null, null, null, null, null, null, null],
    ]);

    $this->post('/insurances-import', ['file' => $file])->assertRedirect('/insurances');

    $policy = Insurance::query()->where('policy_no', 'INF-0001')->first();

    expect($policy)->not->toBeNull();
    expect($policy->contact_method->value)->toBe('Phone');
    expect($policy->contact_value)->toBe('N/A');
    expect($policy->contact_person)->toBe('N/A');
    expect((float) $policy->sum_insured)->toBe(0.0);
    expect($policy->insuranceCompany->name)->toBe('INFINITY');
});
