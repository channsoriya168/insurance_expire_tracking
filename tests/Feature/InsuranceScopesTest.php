<?php

use App\Models\Insurance;

it('finds expired policies', function () {
    $expired = Insurance::factory()->expired()->create();
    Insurance::factory()->expiringInDays(10)->create();

    $results = Insurance::expired()->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($expired->id);
});

it('finds policies expiring in a given number of days', function () {
    $target = Insurance::factory()->expiringInDays(10)->create();
    Insurance::factory()->expiringInDays(20)->create();

    $results = Insurance::expiringInDays(10)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($target->id);
});

it('finds policies expiring on a specific date', function () {
    $date = today()->addDays(5);
    $target = Insurance::factory()->create(['expiry_date' => $date]);
    Insurance::factory()->create(['expiry_date' => today()->addDays(6)]);

    $results = Insurance::expiringOn($date)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($target->id);
});

it('finds policies expiring between two dates', function () {
    $inRange = Insurance::factory()->create(['expiry_date' => today()->addDays(3)]);
    Insurance::factory()->create(['expiry_date' => today()->addDays(30)]);

    $results = Insurance::expiringBetween(today(), today()->addDays(7))->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($inRange->id);
});

it('finds policies expiring within a given month', function () {
    $inMonth = Insurance::factory()->create(['expiry_date' => '2026-03-15']);
    Insurance::factory()->create(['expiry_date' => '2026-04-01']);

    $results = Insurance::forMonth(2026, 3)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($inMonth->id);
});
