<?php

namespace App\Telegram\Commands;

use App\Telegram\FormLinks;
use Telegram\Bot\Commands\Command;

final class EditCommand extends Command
{
    protected string $name = 'edit';

    protected string $description = 'Edit a policy via the Mini App.';

    public function handle(): void
    {
        $chatId = (int) $this->getUpdate()->getChat()->get('id');

        $this->replyWithMessage([
            'text' => 'Tap below to open the app, then choose a policy to edit. The link expires in 30 minutes.',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => 'Open Policies', 'web_app' => ['url' => FormLinks::app($chatId)]]]],
            ]),
        ]);
    }
}
