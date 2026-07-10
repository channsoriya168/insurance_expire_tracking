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
            'text' => 'សូមចុចខាងក្រោមដើម្បីនាំចេញបញ្ជីបណ្ណសន្យារ៉ាប់រង។ តំណនេះនឹងផុតកំណត់ក្នុងរយៈពេល ៣០ នាទី។',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => 'បើកទម្រង់នាំចេញ', 'web_app' => ['url' => FormLinks::export($chatId, $filter)]]]],
            ]),
        ]);
    }
}
