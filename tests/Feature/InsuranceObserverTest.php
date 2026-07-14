<?php

use App\Models\Insurance;
use App\Models\InsuranceNotification;

it('creates a notification row when a policy is created within an active window', function () {
    $insurance = Insurance::factory()->expiringInDays(10)->create();

    expect(InsuranceNotification::query()->where('insurance_id', $insurance->id)->first())
        ->not->toBeNull()
        ->bucket->toBe('10');
});

it('updates the notification bucket when a policy is edited into a different window', function () {
    $insurance = Insurance::factory()->expiringInDays(10)->create();

    $insurance->update(['expiry_date' => today()->addDays(25)]);

    expect($insurance->notification->fresh())
        ->not->toBeNull()
        ->bucket->toBe('30');
});

it('removes the notification row when a policy is edited outside every window', function () {
    $insurance = Insurance::factory()->expiringInDays(10)->create();

    $insurance->update(['expiry_date' => today()->addDays(90)]);

    expect(InsuranceNotification::query()->where('insurance_id', $insurance->id)->exists())->toBeFalse();
});

it('removes the notification row when the policy is deleted', function () {
    $insurance = Insurance::factory()->expiringInDays(10)->create();
    $notificationId = $insurance->notification->id;

    $insurance->delete();

    expect(InsuranceNotification::query()->find($notificationId))->toBeNull();
});

it('does not create a notification row for a policy outside every window', function () {
    $insurance = Insurance::factory()->expiringInDays(90)->create();

    expect(InsuranceNotification::query()->where('insurance_id', $insurance->id)->exists())->toBeFalse();
});
