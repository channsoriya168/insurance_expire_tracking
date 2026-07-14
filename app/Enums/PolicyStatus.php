<?php

namespace App\Enums;

enum PolicyStatus: string
{
    case Pending = 'Pending';
    case Confirmed = 'Confirmed';
    case Cancelled = 'Cancelled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
