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
            'text' => 'Welcome to the Insurance Bot. Choose an action below.',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '➕ Add Policy', 'web_app' => ['url' => FormLinks::create($chatId)]]],
                    [['text' => '✏️ Edit Policy', 'web_app' => ['url' => FormLinks::edit($chatId)]]],
                    [['text' => '🗑 Delete Policy', 'web_app' => ['url' => FormLinks::delete($chatId)]]],
                    [['text' => '📤 Export Policies', 'web_app' => ['url' => FormLinks::export($chatId)]]],
                ],
            ]),
use Telegram\Bot\Commands\Command;

class StartCommand extends Command
{
    protected string $name = 'start';

    protected string $description = 'Start the bot';

    public function handle()
    {
        $this->replyWithMessage([
            'text' => 'Welcome to Insurance Bot!',
        ]);
    }
}
