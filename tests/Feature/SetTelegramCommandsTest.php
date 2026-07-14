<?php

use Telegram\Bot\Api;

it('registers the bot commands with telegram', function () {
    $this->mock(Api::class, function ($mock) {
        $mock->shouldReceive('setMyCommands')
            ->once()
            ->withArgs(function (array $params) {
                expect($params['commands'])->toBe([
                    ['command' => 'start', 'description' => 'Show the welcome message'],
                    ['command' => 'export', 'description' => 'Export insurance policies'],
                ]);

                return true;
            });
    });

    $this->artisan('telegram:commands')->assertExitCode(0);
});
