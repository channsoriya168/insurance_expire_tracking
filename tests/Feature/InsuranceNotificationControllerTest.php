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

it('lists overdue, expiring-today, and soon-to-expire policies sorted by newest created first', function () {
    $overdue = Insurance::factory()->expired()->create();
    $expiringToday = Insurance::factory()->create(['expiry_date' => today()]);
    $in10 = Insurance::factory()->expiringInDays(10)->create();
    $in15 = Insurance::factory()->create(['expiry_date' => today()->addDays(15)]);

    $this->get('/insurances-notifications')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Insurances/Notifications')
            ->has('notifications.data', 4)
            ->where('notifications.data.0.id', $in15->id)
            ->where('notifications.data.1.id', $in10->id)
            ->where('notifications.data.2.id', $expiringToday->id)
            ->where('notifications.data.3.id', $overdue->id)
            ->where('expiryBuckets', [10, 20, 30]));
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
            ->has('notifications.data', 2)
            ->where('expiryBuckets', [10, 20, 30]));

    $this->get('/insurances-notifications?expiry=10')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('notifications.data', 1)
            ->where('notifications.data.0.id', $withinTenDayRange->id)
            ->where('notifications.data.0.bucket', '10'));

    $this->get('/insurances-notifications?expiry=20')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('notifications.data', 1)
            ->where('notifications.data.0.id', $withinTwentyDayRange->id)
            ->where('notifications.data.0.bucket', '20'));
});

it('filters the notifications list to unread policies only', function () {
    $read = Insurance::factory()->expired()->create();
    $read->notification->update(['read_at' => now()]);
    $unread = Insurance::factory()->expiringInDays(10)->create();

    $this->get('/insurances-notifications?unread=1')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('notifications.data', 1)
            ->where('notifications.data.0.id', $unread->id));
});

it('shares only the unread expiring policy count for the bottom nav badge', function () {
    $read = Insurance::factory()->expired()->create();
    $read->notification->update(['read_at' => now()]);
    Insurance::factory()->create(['expiry_date' => today()]);
    Insurance::factory()->expiringInDays(20)->create();
    Insurance::factory()->create(['expiry_date' => today()->addDays(15)]);

    $this->get('/insurances')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('expiringCount', 3));
});

it('configures the notifications list to match/replace by id when merging, not just append', function () {
    // Without an explicit matchOn, Inertia's infinite-scroll merge blindly
    // appends new pages instead of updating existing items in place - so
    // toggling a policy's read state (which re-renders page 1) would leave a
    // stale duplicate card behind instead of updating the one already shown.
    Insurance::factory()->expiringInDays(10)->create();

    $page = $this->get('/insurances-notifications')->getOriginalContent()->getData()['page'];

    expect($page['mergeProps'] ?? [])->toContain('notifications.data');
    expect($page['matchPropsOn'] ?? [])->toContain('notifications.data.id');
});

it('toggles a policy notification read state', function () {
    $insurance = Insurance::factory()->expired()->create();

    expect($insurance->notification->read_at)->toBeNull();

    $this->patch("/insurances-notifications/{$insurance->id}/read")->assertRedirect();
    expect($insurance->notification->fresh()->read_at)->not->toBeNull();

    $this->patch("/insurances-notifications/{$insurance->id}/read")->assertRedirect();
    expect($insurance->notification->fresh()->read_at)->toBeNull();
});

it('bulk-marks the selected policies as read', function () {
    $first = Insurance::factory()->expiringInDays(10)->create();
    $second = Insurance::factory()->expiringInDays(20)->create();
    $untouched = Insurance::factory()->expiringInDays(30)->create();

    $this->patch('/insurances-notifications/read', ['ids' => [$first->id, $second->id]])->assertRedirect();

    expect($first->notification->fresh()->read_at)->not->toBeNull();
    expect($second->notification->fresh()->read_at)->not->toBeNull();
    expect($untouched->notification->fresh()->read_at)->toBeNull();
});

it('leaves an already-read policy alone when bulk-marking as read', function () {
    $insurance = Insurance::factory()->expiringInDays(10)->create();
    $insurance->notification->update(['read_at' => '2026-01-01 00:00:00']);

    $this->patch('/insurances-notifications/read', ['ids' => [$insurance->id]])->assertRedirect();

    expect($insurance->notification->fresh()->read_at->toDateTimeString())->toBe('2026-01-01 00:00:00');
});

it('requires an ids array to bulk-mark policies as read', function () {
    $this->patch('/insurances-notifications/read', [])->assertInvalid(['ids']);
});
