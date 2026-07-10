<?php

use App\Telegram\AllowedChats;

it('reads allowed chat ids from config', function () {
    config(['insurance-bot.allowed_chat_ids' => [111, 222]]);

    expect(AllowedChats::ids())->toBe([111, 222]);
});

it('recognizes an allowed chat id', function () {
    config(['insurance-bot.allowed_chat_ids' => [111, 222]]);

    expect(AllowedChats::contains(111))->toBeTrue();
    expect(AllowedChats::contains('222'))->toBeTrue();
});

it('rejects a chat id that is not allowed', function () {
    config(['insurance-bot.allowed_chat_ids' => [111, 222]]);

    expect(AllowedChats::contains(333))->toBeFalse();
});
