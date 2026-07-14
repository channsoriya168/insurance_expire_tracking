<?php

use App\Models\Insurance;
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

it('lists overdue, expiring-today, and soon-to-expire policies sorted by expiry date', function () {
    $overdue = Insurance::factory()->expired()->create();
    $expiringToday = Insurance::factory()->create(['expiry_date' => today()]);
    $in10 = Insurance::factory()->expiringInDays(10)->create();
    $in15 = Insurance::factory()->create(['expiry_date' => today()->addDays(15)]);

    $this->get('/insurances-notifications')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Insurances/Notifications')
            ->has('notifications.data', 4)
            ->where('notifications.data.0.id', $overdue->id)
            ->where('notifications.data.1.id', $expiringToday->id)
            ->where('notifications.data.2.id', $in10->id)
            ->where('notifications.data.3.id', $in15->id)
            ->where('tabCounts.all', 4)
            ->where('tabCounts.today', 1)
            ->where('tabCounts.buckets.10', 1)
            ->where('tabCounts.buckets.20', 1)
            ->where('tabCounts.buckets.30', 0));
});

it('filters the notifications list by expiry bucket', function () {
    $expiringToday = Insurance::factory()->create(['expiry_date' => today()]);
    Insurance::factory()->expiringInDays(10)->create();

    $this->get('/insurances-notifications?expiry=today')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('notifications.data', 1)
            ->where('notifications.data.0.id', $expiringToday->id));
});

it('keeps a policy visible in its expiry bucket range, not just on the exact threshold day', function () {
    $withinTenDayRange = Insurance::factory()->create(['expiry_date' => today()->addDays(7)]);
    $withinTwentyDayRange = Insurance::factory()->create(['expiry_date' => today()->addDays(15)]);
    Insurance::factory()->create(['expiry_date' => today()->addDays(45)]);

    $this->get('/insurances-notifications')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('tabCounts.all', 2)
            ->where('tabCounts.buckets.10', 1)
            ->where('tabCounts.buckets.20', 1)
            ->where('tabCounts.buckets.30', 0));

    $this->get('/insurances-notifications?expiry=10')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('notifications.data', 1)
            ->where('notifications.data.0.id', $withinTenDayRange->id)
            ->where('notifications.data.0.bucket', '10d'));

    $this->get('/insurances-notifications?expiry=20')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('notifications.data', 1)
            ->where('notifications.data.0.id', $withinTwentyDayRange->id)
            ->where('notifications.data.0.bucket', '20d'));
});

it('filters the notifications list to unread policies only', function () {
    Insurance::factory()->expired()->create(['notification_read_at' => now()]);
    $unread = Insurance::factory()->expiringInDays(10)->create();

    $this->get('/insurances-notifications?unread=1')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('notifications.data', 1)
            ->where('notifications.data.0.id', $unread->id));
});

it('shares an expiring policy count for the bottom nav badge', function () {
    Insurance::factory()->expired()->create();
    Insurance::factory()->create(['expiry_date' => today()]);
    Insurance::factory()->expiringInDays(20)->create();
    Insurance::factory()->create(['expiry_date' => today()->addDays(15)]);

    $this->get('/insurances')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('expiringCount', 4));
});

it('toggles a policy notification read state', function () {
    $insurance = Insurance::factory()->expired()->create();

    expect($insurance->notification_read_at)->toBeNull();

    $this->patch("/insurances-notifications/{$insurance->id}/read")->assertRedirect();
    expect($insurance->fresh()->notification_read_at)->not->toBeNull();

    $this->patch("/insurances-notifications/{$insurance->id}/read")->assertRedirect();
    expect($insurance->fresh()->notification_read_at)->toBeNull();
});
