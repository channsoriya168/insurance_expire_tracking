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

it('groups overdue, expiring-today, and soon-to-expire policies for the notifications page', function () {
    $overdue = Insurance::factory()->expired()->create();
    $expiringToday = Insurance::factory()->create(['expiry_date' => today()]);
    $in10 = Insurance::factory()->expiringInDays(10)->create();
    Insurance::factory()->expiringInDays(15)->create();

    $this->get('/insurances-notifications')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Insurances/Notifications')
            ->where('overdue.0.id', $overdue->id)
            ->where('today.0.id', $expiringToday->id)
            ->where('buckets.10.0.id', $in10->id)
            ->where('buckets.20', [])
            ->where('buckets.30', []));
});

it('shares an expiring policy count for the bottom nav badge', function () {
    Insurance::factory()->expired()->create();
    Insurance::factory()->create(['expiry_date' => today()]);
    Insurance::factory()->expiringInDays(20)->create();
    Insurance::factory()->expiringInDays(15)->create();

    $this->get('/insurances')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('expiringCount', 3));
});

it('toggles a policy notification read state', function () {
    $insurance = Insurance::factory()->expired()->create();

    expect($insurance->notification_read_at)->toBeNull();

    $this->patch("/insurances-notifications/{$insurance->id}/read")->assertRedirect();
    expect($insurance->fresh()->notification_read_at)->not->toBeNull();

    $this->patch("/insurances-notifications/{$insurance->id}/read")->assertRedirect();
    expect($insurance->fresh()->notification_read_at)->toBeNull();
});
