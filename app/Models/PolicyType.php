<?php

namespace App\Models;

use Database\Factories\PolicyTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class PolicyType extends Model
{
    /** @use HasFactory<PolicyTypeFactory> */
    use HasFactory;

    /**
     * @return HasMany<Insurance, $this>
     */
    public function insurances(): HasMany
    {
        return $this->hasMany(Insurance::class);
    }
}
