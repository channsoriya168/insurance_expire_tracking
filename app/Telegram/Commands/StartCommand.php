<?php

namespace App\Telegram\Commands;

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

        $this->replyWithMessage([
            'text' => 'Welcome to Insurance Bot.',
            'reply_markup' => json_encode([
                'keyboard' => [[['text' => '📤 Export', 'web_app' => ['url' => FormLinks::export($chatId)]]]],
                'resize_keyboard' => true,
                'is_persistent' => true,
            ]),
        ]);
    }
}
