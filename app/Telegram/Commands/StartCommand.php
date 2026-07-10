<?php

namespace App\Telegram\Commands;

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
