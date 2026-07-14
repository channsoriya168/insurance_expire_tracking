<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Telegram\Bot\Api;

#[Signature('telegram:commands')]
#[Description('Registers the bot\'s slash commands so they appear in Telegram\'s command menu next to the Open App button.')]
class SetTelegramCommands extends Command
{
    public function handle(Api $telegram): int
    {
        $telegram->setMyCommands([
            'commands' => [
                ['command' => 'start', 'description' => 'Show the welcome message'],
            ],
        ]);

        $this->info('Bot commands registered.');

        return self::SUCCESS;
    }
}
