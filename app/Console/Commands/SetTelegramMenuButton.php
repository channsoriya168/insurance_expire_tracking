<?php

namespace App\Console\Commands;

use App\Telegram\FormLinks;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Telegram\Bot\Api;

#[Signature('telegram:menu-button')]
#[Description('Sets the Telegram bot\'s persistent chat menu button to open the Insurance Mini App.')]
class SetTelegramMenuButton extends Command
{
    public function handle(Api $telegram): int
    {
        $url = FormLinks::launch();

        $telegram->post('setChatMenuButton', [
            'menu_button' => json_encode([
                'type' => 'web_app',
                'text' => 'បើកកម្មវិធី',
                'web_app' => ['url' => $url],
            ]),
        ]);

        $this->info("Menu button set to open {$url}");

        return self::SUCCESS;
    }
}
