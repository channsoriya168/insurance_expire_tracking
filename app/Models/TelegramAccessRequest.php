<?php

namespace App\Models;

use App\Enums\TelegramAccessStatus;
use Database\Factories\TelegramAccessRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['chat_id', 'first_name', 'username', 'status'])]
class TelegramAccessRequest extends Model
{
    /** @use HasFactory<TelegramAccessRequestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TelegramAccessStatus::class,
        ];
    }
}
