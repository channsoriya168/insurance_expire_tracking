<?php

namespace App\Http\Controllers;

use App\Telegram\AllowedChats;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

final class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, Api $telegram): Response
    {
        $update = new Update($request->json()->all());

        $chatId = (int) $update->getChat()->get('id');

        if ($chatId === 0) {
            return response()->noContent();
        }

        if (! AllowedChats::contains($chatId)) {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'You are not authorized to use this bot.',
            ]);

            return response()->noContent();
        }

        if ($update->hasCommand()) {
            $telegram->processCommand($update);
        }

        return response()->noContent();
    }
}
