<?php

namespace App\Telegram\Commands;

use App\Enums\TelegramAccessStatus;
use App\Models\TelegramAccessRequest;
use App\Telegram\AllowedChats;
use App\Telegram\FormLinks;
use Telegram\Bot\Commands\Command;

final class StartCommand extends Command
{
    protected string $name = 'start';

    protected array $aliases = ['menu'];

    protected string $description = 'Show the welcome message.';

    public function handle(): void
    {
        $chatId = (int) $this->getUpdate()->getChat()->get('id');

        if (! AllowedChats::contains($chatId)) {
            $this->requestAccess($chatId);

            $this->replyWithMessage([
                'text' => "Thanks! Your request to use this bot has been sent for approval. You'll get a message here once you're approved.",
            ]);

            return;
        }

        $this->replyWithMessage([
            'text' => 'Welcome to Insurance Bot.',
            'reply_markup' => json_encode([
                'keyboard' => [[['text' => '📤 Export', 'web_app' => ['url' => FormLinks::export($chatId)]]]],
                'resize_keyboard' => true,
                'is_persistent' => true,
            ]),
        ]);
    }

    private function requestAccess(int $chatId): void
    {
        $chat = $this->getUpdate()->getChat();

        $attributes = [
            'first_name' => $chat->get('first_name'),
            'username' => $chat->get('username'),
        ];

        $request = TelegramAccessRequest::firstOrCreate(
            ['chat_id' => $chatId],
            [...$attributes, 'status' => TelegramAccessStatus::Pending],
        );

        if (! $request->wasRecentlyCreated) {
            $request->update([
                ...$attributes,
                'status' => $request->status === TelegramAccessStatus::Rejected
                    ? TelegramAccessStatus::Pending
                    : $request->status,
            ]);
        }
    }
}
