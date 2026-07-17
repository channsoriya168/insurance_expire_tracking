<?php

namespace App\Enums;

enum TelegramAccessStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
