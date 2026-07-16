<?php

use App\Exports\InsurancesExport;
use App\Models\Insurance;
use App\Services\InsuranceService;
use App\Support\ExpiryDateRange;

it('exports the expected headings', function () {
    $export = new InsurancesExport(Insurance::query());

    expect($export->headings())->toBe([
        'Insurance Company',
        'Policy No',
        'Contact Method',
        'Contact Name',
        'Contact Person',
        'Insured Name',
        'Expiry Date',
        'Policy Type',
        'Sum Insured',
        'Premium',
        'Net Premium',
        'Revised Sum Insured',
        'Revised Premium',
        'Revised Premium Rate',
        'Confirmed Date',
        'Status',
        'Payment Status',
        'Payment Date',
        'Policy Received Date',
        'Remarks',
    ]);
});

it('maps a policy row to the expected columns', function () {
    $insurance = Insurance::factory()->create([
        'policy_no' => 'Y25VP00008165',
        'expiry_date' => '2026-03-15',
    ]);

    $export = new InsurancesExport(Insurance::query());
    $row = $export->map($insurance);

    expect($row[1])->toBe('Y25VP00008165');
    expect($row[6])->toBe('2026-03-15');
});

it('only includes policies within the requested expiry range', function () {
    $insurances = app(InsuranceService::class);

    $inRange = Insurance::factory()->create(['expiry_date' => '2026-03-15']);
    Insurance::factory()->create(['expiry_date' => '2026-05-01']);

    $range = ExpiryDateRange::parse('2026-03');
    $query = $insurances->exportQuery($range);

    expect($query->pluck('id')->all())->toBe([$inRange->id]);
});

it('only includes policies on the requested single day', function () {
    $insurances = app(InsuranceService::class);

    $inRange = Insurance::factory()->create(['expiry_date' => '2026-03-15']);
    Insurance::factory()->create(['expiry_date' => '2026-03-16']);

    $range = ExpiryDateRange::parse('2026-03-15');
    $query = $insurances->exportQuery($range);

    expect($query->pluck('id')->all())->toBe([$inRange->id]);
});

it('includes every policy when the filter is "all"', function () {
    $insurances = app(InsuranceService::class);

    Insurance::factory()->count(3)->create();

    $range = ExpiryDateRange::parse('all');
    $query = $insurances->exportQuery($range);

    expect($query->count())->toBe(3);
});
