<?php

use App\Models\Insurance;
use App\Models\InsuranceNotification;
use Telegram\Bot\Api;

it('sends a summary to every allowed chat id', function () {
    config(['insurance-bot.allowed_chat_ids' => [111, 222]]);

    $overdue = Insurance::factory()->expired()->create();
    $expiringToday = Insurance::factory()->create(['expiry_date' => today()]);
    $in10 = Insurance::factory()->expiringInDays(10)->create();
    $in20 = Insurance::factory()->expiringInDays(20)->create();
    $in30 = Insurance::factory()->expiringInDays(30)->create();
    Insurance::factory()->expiringInDays(15)->create(); // unrelated, should not appear

    $this->mock(Api::class, function ($mock) use ($overdue, $expiringToday, $in10, $in20, $in30) {
        $mock->shouldReceive('sendMessage')
            ->twice()
            ->withArgs(function (array $params) use ($overdue, $expiringToday, $in10, $in20, $in30) {
                expect($params['chat_id'])->toBeIn([111, 222]);
                expect($params['text'])
                    ->not->toContain($overdue->policy_no)
                    ->toContain($expiringToday->policy_no)
                    ->toContain($in10->policy_no)
                    ->toContain($in20->policy_no)
                    ->toContain($in30->policy_no);

                $keyboard = json_decode($params['reply_markup'], true);
                $url = $keyboard['inline_keyboard'][0][0]['web_app']['url'];
                expect($url)->toContain('/telegram/launch')->toContain('redirect=notifications');

                return true;
            });
    });

    $this->artisan('insurance:notify-expiring')->assertExitCode(0);
});

it('does not send a second Telegram summary if run again the same day', function () {
    config(['insurance-bot.allowed_chat_ids' => [111]]);

    Insurance::factory()->create(['expiry_date' => today()]);

    $this->mock(Api::class, function ($mock) {
        $mock->shouldReceive('sendMessage')->once();
    });

    $this->artisan('insurance:notify-expiring')->assertExitCode(0);
    $this->artisan('insurance:notify-expiring')->assertExitCode(0);
});

it('sends nothing when no policies are expiring soon', function () {
    config(['insurance-bot.allowed_chat_ids' => [111]]);

    Insurance::factory()->expired()->create();
    Insurance::factory()->expiringInDays(15)->create();

    $this->mock(Api::class, function ($mock) {
        $mock->shouldNotReceive('sendMessage');
    });

    $this->artisan('insurance:notify-expiring')->assertExitCode(0);
});

it('corrects a notification row that drifted out of date without a save', function () {
    // Simulate pure day-count aging: the policy is now only 8 days out, but
    // its notification row is stuck reflecting an older, wider bucket - the
    // kind of drift a save/update would fix instantly via the observer, but
    // that only this scheduled command catches for otherwise-untouched policies.
    $insurance = Insurance::factory()->create(['expiry_date' => today()->addDays(8)]);
    $insurance->notification->update(['bucket' => '20', 'read_at' => now()]);

    $this->artisan('insurance:notify-expiring')->assertExitCode(0);

    expect($insurance->notification->fresh())
        ->bucket->toBe('10')
        ->read_at->toBeNull();
});

it('removes notification rows for policies that have aged out of every window', function () {
    $insurance = Insurance::factory()->create(['expiry_date' => today()->addDays(15)]);
    $notificationId = $insurance->notification->id;

    // Simulate the policy having aged past every threshold without a save.
    $insurance->notification->update(['bucket' => '30']);
    $insurance->newQuery()->where('id', $insurance->id)->update(['expiry_date' => today()->addDays(90)]);

    $this->artisan('insurance:notify-expiring')->assertExitCode(0);

    expect(InsuranceNotification::query()->find($notificationId))->toBeNull();
});
