<?php

use App\Models\TelegramAccessRequest;
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

it('includes approved telegram access requests alongside the config seed', function () {
    config(['insurance-bot.allowed_chat_ids' => [111]]);

    TelegramAccessRequest::factory()->approved()->create(['chat_id' => 333]);

    $ids = AllowedChats::ids();
    sort($ids);

    expect($ids)->toBe([111, 333]);
    expect(AllowedChats::contains(333))->toBeTrue();
});

it('excludes pending and rejected telegram access requests', function () {
    config(['insurance-bot.allowed_chat_ids' => [111]]);

    TelegramAccessRequest::factory()->create(['chat_id' => 444]);
    TelegramAccessRequest::factory()->rejected()->create(['chat_id' => 555]);

    expect(AllowedChats::contains(444))->toBeFalse();
    expect(AllowedChats::contains(555))->toBeFalse();
});
