<?php

namespace App\Telegram\Commands;

use App\Telegram\FormLinks;
use Telegram\Bot\Commands\Command;

final class StartCommand extends Command
{
    protected string $name = 'start';

    protected array $aliases = ['menu'];

    protected string $description = 'Show the main menu.';

    public function handle(): void
    {
        $chatId = (int) $this->getUpdate()->getChat()->get('id');

        $this->replyWithMessage([
            'text' => 'សូមស្វាគមន៍មកកាន់ Insurance Bot។ សូមជ្រើសរើសសកម្មភាពខាងក្រោម។',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '📤 នាំចេញបណ្ណសន្យារ៉ាប់រង', 'web_app' => ['url' => FormLinks::export($chatId)]]],
                ],
            ]),
        ]);
    }
}
