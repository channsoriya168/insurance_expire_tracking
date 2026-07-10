<?php

namespace App\Telegram\Commands;

use App\Telegram\FormLinks;
use Telegram\Bot\Commands\Command;

final class AddCommand extends Command
{
    protected string $name = 'add';

    protected string $description = 'Add a new insurance policy via a web form.';

    public function handle(): void
    {
        $chatId = (int) $this->getUpdate()->getChat()->get('id');

        $this->replyWithMessage([
            'text' => 'Tap below to add a new policy. The link expires in 30 minutes.',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => 'Open Add Form', 'web_app' => ['url' => FormLinks::create($chatId)]]]],
            ]),
        ]);
    }
}
