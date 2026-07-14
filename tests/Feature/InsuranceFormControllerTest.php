<?php

use App\Models\Insurance;
use Illuminate\Support\Facades\URL;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Objects\Message;

function allowedChatId(): int
{
    return 111;
}

beforeEach(function () {
    config(['insurance-bot.allowed_chat_ids' => [allowedChatId()]]);

    $this->mock(Api::class, function ($mock) {
        $mock->shouldReceive('sendMessage')->andReturn(new Message([]));
        $mock->shouldReceive('sendDocument')->andReturn(new Message([]));
    });
});

it('sends the xlsx export to the chat for the default all filter', function () {
    Insurance::factory()->count(3)->create();

    $url = URL::temporarySignedRoute('forms.insurances.export', now()->addMinutes(10), ['chat' => allowedChatId()]);

    $this->mock(Api::class, function ($mock) {
        $mock->shouldReceive('sendDocument')
            ->once()
            ->withArgs(function (array $params) {
                expect($params['chat_id'])->toBe(allowedChatId());
                expect($params['document'])->toBeInstanceOf(InputFile::class);

                return true;
            })
            ->andReturn(new Message([]));
    });

    $response = $this->post($url, ['filter' => 'all']);

    $response->assertOk()->assertSee('Sent!');
});

it('shows an error instead of sending when the filter matches nothing', function () {
    Insurance::factory()->create(['expiry_date' => '2020-01-01']);

    $url = URL::temporarySignedRoute('forms.insurances.export', now()->addMinutes(10), ['chat' => allowedChatId()]);

    $response = $this->post($url, ['filter' => '2099-01']);

    $response->assertOk()->assertSee('No policies found');
});

it('rejects a signed export link for a chat id that is not allowed', function () {
    $url = URL::temporarySignedRoute('forms.insurances.export', now()->addMinutes(10), ['chat' => 999]);

    $this->get($url)->assertForbidden();
});

it('rejects an unsigned export request even for an allowed chat id', function () {
    $this->get('/forms/insurances/export?chat='.allowedChatId())->assertForbidden();
});
