<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\InsuranceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'insurance_company',
    'policy_no',
    'contact_method',
    'contact_value',
    'contact_person',
    'insured_name',
    'expiry_date',
    'policy_type',
    'sum_insured',
    'premium',
    'revised_sum_insured',
    'revised_premium',
    'revised_premium_rate',
    'confirmed_date',
    'status',
    'request_policy_date',
    'policy_received_date',
    'remarks',
])]
class Insurance extends Model
{
    /** @use HasFactory<InsuranceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'confirmed_date' => 'date',
            'request_policy_date' => 'date',
            'policy_received_date' => 'date',
            'sum_insured' => 'decimal:2',
            'premium' => 'decimal:2',
            'revised_sum_insured' => 'decimal:2',
            'revised_premium' => 'decimal:2',
            'revised_premium_rate' => 'decimal:4',
        ];
    }

    #[Scope]
    protected function expired(Builder $query): void
    {
        $query->whereDate('expiry_date', '<', today());
    }

    #[Scope]
    protected function expiringOn(Builder $query, CarbonInterface $date): void
    {
        $query->whereDate('expiry_date', $date->toDateString());
    }

    #[Scope]
    protected function expiringInDays(Builder $query, int $days): void
    {
        $query->whereDate('expiry_date', today()->addDays($days)->toDateString());
    }

    #[Scope]
    protected function expiringBetween(Builder $query, CarbonInterface $start, CarbonInterface $end): void
    {
        $query->whereBetween('expiry_date', [$start->toDateString(), $end->toDateString()]);
    }

    #[Scope]
    protected function forMonth(Builder $query, int $year, int $month): void
    {
        $query->whereYear('expiry_date', $year)->whereMonth('expiry_date', $month);
    }
}
