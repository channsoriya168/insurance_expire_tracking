<?php

use App\Models\Insurance;
use Telegram\Bot\Api;

it('sends a summary to every allowed chat id', function () {
    config(['insurance-bot.allowed_chat_ids' => [111, 222]]);

    $overdue = Insurance::factory()->expired()->create();
    $in10 = Insurance::factory()->expiringInDays(10)->create();
    $in20 = Insurance::factory()->expiringInDays(20)->create();
    $in30 = Insurance::factory()->expiringInDays(30)->create();
    Insurance::factory()->expiringInDays(15)->create(); // unrelated, should not appear

    $this->mock(Api::class, function ($mock) use ($overdue, $in10, $in20, $in30) {
        $mock->shouldReceive('sendMessage')
            ->twice()
            ->withArgs(function (array $params) use ($overdue, $in10, $in20, $in30) {
                expect($params['chat_id'])->toBeIn([111, 222]);
                expect($params['text'])
                    ->toContain($overdue->policy_no)
                    ->toContain($in10->policy_no)
                    ->toContain($in20->policy_no)
                    ->toContain($in30->policy_no);

                return true;
            });
    });

    $this->artisan('insurance:notify-expiring')->assertExitCode(0);
});

it('sends nothing when no policies are overdue or expiring soon', function () {
    config(['insurance-bot.allowed_chat_ids' => [111]]);

    Insurance::factory()->expiringInDays(15)->create();

    $this->mock(Api::class, function ($mock) {
        $mock->shouldNotReceive('sendMessage');
    });

    $this->artisan('insurance:notify-expiring')->assertExitCode(0);
});
