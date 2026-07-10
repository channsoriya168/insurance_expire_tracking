<?php

namespace App\Telegram\Commands;

use App\Telegram\FormLinks;
use Telegram\Bot\Commands\Command;

final class DeleteCommand extends Command
{
    protected string $name = 'delete';

    protected string $description = 'Delete a policy via a web form: /delete [policyNo]';

    protected string $pattern = '{policyNo}';

    public function handle(): void
    {
        $chatId = (int) $this->getUpdate()->getChat()->get('id');
        $policyNo = $this->argument('policyNo');

        $this->replyWithMessage([
            'text' => 'Tap below to delete a policy. The link expires in 30 minutes.',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => 'Open Delete Form', 'web_app' => ['url' => FormLinks::delete($chatId, $policyNo)]]]],
            ]),
        ]);
    }
}
