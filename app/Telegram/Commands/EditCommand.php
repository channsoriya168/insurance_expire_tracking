<?php

namespace App\Telegram\Commands;

use App\Telegram\FormLinks;
use Telegram\Bot\Commands\Command;

final class EditCommand extends Command
{
    protected string $name = 'edit';

    protected string $description = 'Edit a policy via a web form: /edit [policyNo]';

    protected string $pattern = '{policyNo}';

    public function handle(): void
    {
        $chatId = (int) $this->getUpdate()->getChat()->get('id');
        $policyNo = $this->argument('policyNo');

        $this->replyWithMessage([
            'text' => 'Tap below to edit a policy. The link expires in 30 minutes.',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => 'Open Edit Form', 'web_app' => ['url' => FormLinks::edit($chatId, $policyNo)]]]],
            ]),
        ]);
    }
}
