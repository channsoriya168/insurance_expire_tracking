<?php

namespace App\Telegram\Commands;

use App\Telegram\FormLinks;
use Telegram\Bot\Commands\Command;

final class ExportCommand extends Command
{
    protected string $name = 'export';

    protected array $aliases = ['list'];

    protected string $description = 'Export policies via a web form: /export [filter]';

    protected string $pattern = '{filter:.*}';

    public function handle(): void
    {
        $chatId = (int) $this->getUpdate()->getChat()->get('id');
        $filter = $this->argument('filter');
        $filter = $filter !== null && trim($filter) !== '' ? trim($filter) : null;

        $this->replyWithMessage([
            'text' => 'Tap below to export the list of insurance policies. This link will expire in 24 hours.',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => 'Open export form', 'web_app' => ['url' => FormLinks::export($chatId, $filter)]]]],
            ]),
        ]);
    }
}
