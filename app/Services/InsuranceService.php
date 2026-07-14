<?php

namespace App\Services;

use App\Models\Insurance;
use App\Support\ExpiryDateRange;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public function updateField(Insurance $insurance, string $field, mixed $value): Insurance
    {
        $insurance->update([$field => $value]);

        return $insurance;
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

    public function toggleNotificationRead(Insurance $insurance): Insurance
    {
        $insurance->notification_read_at = $insurance->notification_read_at ? null : now();
        $insurance->save();

        return $insurance;
    }

    public function findByPolicyNo(string $policyNo): ?Insurance
    {
        return Insurance::query()->where('policy_no', $policyNo)->first();
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
     * Non-overlapping day-count ranges derived from the configured
     * thresholds (e.g. [10, 20, 30] becomes 1-10, 11-20, 21-30), so a policy
     * keeps showing in its notification bucket every day it's within that
     * window instead of only on the one exact day it crosses a threshold.
     *
     * @return array<int, array{0: int, 1: int}>
     */
    public function expiryRanges(): array
    {
        /** @var list<int> $thresholds */
        $thresholds = config('insurance-bot.expiry_thresholds');
        sort($thresholds);

        $ranges = [];
        $lowerBound = 1;

        foreach ($thresholds as $days) {
            $ranges[$days] = [$lowerBound, $days];
            $lowerBound = $days + 1;
        }

        return $ranges;
    }

    /**
     * Single expiry-date-sorted, paginated list of overdue/today/soon-to-expire
     * policies for the notifications screen's infinite scroll, optionally
     * narrowed to one expiry bucket ('today', or a configured day threshold)
     * and/or unread-only.
     */
    public function paginatedNotifications(?string $expiry, bool $unreadOnly, int $perPage = 15): LengthAwarePaginator
    {
        $ranges = $this->expiryRanges();

        return $this->notificationScopeQuery($ranges)
            ->when($expiry === 'today', fn (Builder $q) => $q->expiringOn(today()))
            ->when(
                $expiry !== null && ctype_digit($expiry) && isset($ranges[(int) $expiry]),
                function (Builder $q) use ($ranges, $expiry): void {
                    [$min, $max] = $ranges[(int) $expiry];
                    $q->expiringBetween(today()->addDays($min), today()->addDays($max));
                },
            )
            ->when($unreadOnly, fn (Builder $q) => $q->whereNull('notification_read_at'))
            ->orderBy('expiry_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Counts for each notification tab badge, independent of the active filter.
     *
     * @return array{all: int, unread: int, today: int, buckets: array<int, int>}
     */
    public function notificationTabCounts(): array
    {
        $ranges = $this->expiryRanges();
        $todayCount = Insurance::query()->expiringOn(today())->count();

        $bucketCounts = collect($ranges)->mapWithKeys(fn (array $range, int $days): array => [
            $days => Insurance::query()->expiringBetween(today()->addDays($range[0]), today()->addDays($range[1]))->count(),
        ]);

        return [
            'all' => Insurance::query()->expired()->count() + $todayCount + $bucketCounts->sum(),
            'unread' => $this->expiringUnreadCount(),
            'today' => $todayCount,
            'buckets' => $bucketCounts->all(),
        ];
    }

    /**
     * Count of unread policies across the expiring/overdue notification set,
     * for the "Unread" tab badge on the notifications screen.
     */
    public function expiringUnreadCount(): int
    {
        return $this->notificationScopeQuery($this->expiryRanges())->whereNull('notification_read_at')->count();
    }

    /**
     * Total count of overdue, expiring-today, and soon-to-expire policies,
     * for a lightweight notification badge (see {@see self::expiringGroups()}
     * for the full list).
     */
    public function expiringCount(): int
    {
        return $this->notificationScopeQuery($this->expiryRanges())->count();
    }

    /**
     * @param  array<int, array{0: int, 1: int}>  $ranges
     * @return Builder<Insurance>
     */
    private function notificationScopeQuery(array $ranges): Builder
    {
        return Insurance::query()->where(function (Builder $query) use ($ranges): void {
            $query->expired()->orWhere(fn (Builder $q) => $q->expiringOn(today()));

            foreach ($ranges as [$min, $max]) {
                $query->orWhere(fn (Builder $q) => $q->expiringBetween(today()->addDays($min), today()->addDays($max)));
            }
        });
    }

    /**
     * Summary counts for the Mini App home screen.
     *
     * @return array{total: int, overdue: int, expiringSoon: int}
     */
    public function dashboardStats(): array
    {
        return [
            'total' => Insurance::query()->count(),
            'overdue' => Insurance::query()->expired()->count(),
            'expiringSoon' => Insurance::query()->expiringBetween(today(), today()->addDays(30))->count(),
        ];
    }

    /**
     * Nearest upcoming policies (not yet expired), for the home screen preview.
     *
     * @return Collection<int, Insurance>
     */
    public function upcoming(int $limit = 5): Collection
    {
        return Insurance::query()
            ->select(['id', 'policy_no', 'insurance_company', 'insured_name', 'expiry_date'])
            ->whereDate('expiry_date', '>=', today())
            ->orderBy('expiry_date')
            ->limit($limit)
            ->get();
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
