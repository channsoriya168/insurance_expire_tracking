<?php

namespace App\Enums;

enum ContactMethod: string
{
    case Telegram = 'Telegram';
    case Phone = 'Phone';
    case WhatsApp = 'WhatsApp';
    case WeChat = 'WeChat';
    case Email = 'Email';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
