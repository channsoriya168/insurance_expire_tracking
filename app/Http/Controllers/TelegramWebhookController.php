<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramWebhookController extends Controller
{
    public function __invoke(): Response
    {
        $update = Telegram::getWebhookUpdate();

        $chatId = (string) $update->getChat()->get('id');

        if (in_array($chatId, config('telegram.allowed_chat_ids'), true)) {
            Telegram::processCommand($update);
        }

        return response()->noContent();
    }
}
