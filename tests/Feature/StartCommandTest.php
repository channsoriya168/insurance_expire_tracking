<?php

use App\Telegram\Commands\StartCommand;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

it('replies with the welcome message and a persistent export web_app keyboard button', function () {
    $update = new Update([
        'update_id' => 1,
        'message' => [
            'message_id' => 1,
            'chat' => ['id' => 111, 'type' => 'private'],
            'text' => '/start',
            'date' => time(),
        ],
    ]);

    $telegram = Mockery::mock(Api::class);
    $telegram->shouldReceive('sendMessage')
        ->once()
        ->withArgs(function (array $params) {
            expect($params['chat_id'])->toBe(111);
            expect($params['text'])->not->toContain('/export');

            $keyboard = json_decode($params['reply_markup'], true);
            $button = $keyboard['keyboard'][0][0];
            expect($button['web_app']['url'])->toContain('/forms/insurances/export');
            expect($keyboard['is_persistent'])->toBeTrue();

            return true;
        });

    (new StartCommand)->make($telegram, $update, []);
});
