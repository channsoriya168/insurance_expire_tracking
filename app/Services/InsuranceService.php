<?php

namespace App\Services;

use App\Models\Insurance;
use App\Support\ExpiryDateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class InsuranceService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Insurance
    {
        return Insurance::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Insurance $insurance, array $data): Insurance
    {
        $insurance->update($data);

        return $insurance;
    }

    public function delete(Insurance $insurance): void
    {
        $insurance->delete();
    }

    /**
     * Group policies by expiry urgency: already overdue, expiring today, plus
     * one bucket per configured day-out threshold (e.g. 10/20/30 days before
     * expiry). Only these exact days trigger a notification; every other day
     * count is left out on purpose.
     *
     * @return array{overdue: Collection<int, Insurance>, today: Collection<int, Insurance>, buckets: array<int, Collection<int, Insurance>>}
     */
    public function expiringGroups(): array
    {
        /** @var list<int> $thresholds */
        $thresholds = config('insurance-bot.expiry_thresholds');

        return [
            'overdue' => Insurance::query()->expired()->orderBy('expiry_date')->get(),
            'today' => Insurance::query()->expiringOn(today())->orderBy('expiry_date')->get(),
            'buckets' => collect($thresholds)
                ->mapWithKeys(fn (int $days): array => [
                    $days => Insurance::query()->expiringInDays($days)->orderBy('expiry_date')->get(),
                ])
                ->all(),
        ];
    }

    /**
     * @return Builder<Insurance>
     */
    public function exportQuery(ExpiryDateRange $range): Builder
    {
        return Insurance::query()
            ->when(
                ! $range->isUnfiltered(),
                fn (Builder $query) => $query->expiringBetween($range->from, $range->to),
            )
            ->orderBy('expiry_date');
    }
}
