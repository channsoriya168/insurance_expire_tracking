<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceNotification extends Model
{
    protected $fillable = [
        'insurance_id',
        'bucket',
        'expiry_date',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Insurance, $this>
     */
    public function insurance(): BelongsTo
    {
        return $this->belongsTo(Insurance::class);
    }
}
