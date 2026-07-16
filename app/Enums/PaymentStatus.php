<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'Pending';
    case Paid = 'Paid';
    case Unpaid = 'Unpaid';
    case ThirtyPercentSelfInsured = '30% Self Insured';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
