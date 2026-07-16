<?php

namespace App\Services;

use App\Models\Insurance;
use App\Models\InsuranceNotification;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class InsuranceNotificationService
{
    /**
     * The notification bucket a policy currently falls into ('overdue',
     * 'today', or a configured day threshold as a string), or null if it
     * doesn't currently warrant a notification.
     */
    public function bucketKeyFor(CarbonInterface $expiryDate): ?string
    {
        if ($expiryDate->isPast() && ! $expiryDate->isToday()) {
            return 'overdue';
        }

        if ($expiryDate->isToday()) {
            return 'today';
        }

        $days = today()->diffInDays($expiryDate);

        foreach ($this->expiryRanges() as $threshold => [$min, $max]) {
            if ($days >= $min && $days <= $max) {
                return (string) $threshold;
            }
        }

        return null;
    }

    /**
     * Recompute and persist the notification row for a single policy,
     * creating/updating it while the policy is within an active window and
     * removing it once the policy moves outside every window. A bucket
     * transition (e.g. 20d -> 10d) is treated as a fresh notification and
     * resets the read state; staying in the same bucket preserves it.
     */
    public function syncNotificationFor(Insurance $insurance): void
    {
        $bucket = $insurance->expiry_date ? $this->bucketKeyFor($insurance->expiry_date) : null;

        if ($bucket === null) {
            InsuranceNotification::query()->where('insurance_id', $insurance->id)->delete();

            return;
        }

        $existing = InsuranceNotification::query()->where('insurance_id', $insurance->id)->first();

        InsuranceNotification::query()->updateOrCreate(
            ['insurance_id' => $insurance->id],
            [
                'bucket' => $bucket,
                'expiry_date' => $insurance->expiry_date,
                'read_at' => $existing?->bucket === $bucket ? $existing->read_at : null,
            ],
        );
    }

    /**
     * Resync every policy's notification row, for the daily scheduled
     * command that catches pure day-count aging (see {@see self::syncNotificationFor()}
     * for changes triggered by editing a policy directly).
     */
    public function syncAllNotifications(): void
    {
        Insurance::query()->chunkById(200, function (Collection $insurances): void {
            foreach ($insurances as $insurance) {
                $this->syncNotificationFor($insurance);
            }
        });
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
     * Newest-first, paginated list of persisted notifications for the
     * notifications screen's infinite scroll, optionally narrowed to one
     * bucket ('today', or a configured day threshold) and/or unread-only.
     */
    public function paginatedNotifications(?string $expiry, bool $unreadOnly, int $perPage = 15): LengthAwarePaginator
    {
        return InsuranceNotification::query()
            ->with('insurance.insuranceCompany')
            ->when($expiry !== null, fn (Builder $q) => $q->where('bucket', $expiry))
            ->when($unreadOnly, fn (Builder $q) => $q->whereNull('read_at'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Count of unread policies across the expiring/overdue notification set,
     * for the "Unread" tab badge on the notifications screen.
     */
    public function unreadCount(): int
    {
        return InsuranceNotification::query()->whereNull('read_at')->count();
    }

    /**
     * Total count of active notifications, for the bottom-nav bell badge.
     */
    public function count(): int
    {
        return InsuranceNotification::query()->count();
    }

    public function toggleRead(Insurance $insurance): void
    {
        $notification = InsuranceNotification::query()->where('insurance_id', $insurance->id)->first();

        if (! $notification) {
            return;
        }

        $notification->read_at = $notification->read_at ? null : now();
        $notification->save();
    }

    /**
     * Mark a policy's notification as read, e.g. when viewing its details
     * from the notifications list. Unlike {@see self::toggleRead()}, this
     * never flips an already-read notification back to unread.
     */
    public function markRead(Insurance $insurance): void
    {
        $notification = InsuranceNotification::query()->where('insurance_id', $insurance->id)->first();

        if ($notification && $notification->read_at === null) {
            $notification->read_at = now();
            $notification->save();
        }
    }

    /**
     * Bulk-mark the given policies' notifications as read, for the
     * notifications screen's multi-select "mark as read" action.
     *
     * @param  list<int>  $insuranceIds
     */
    public function markManyRead(array $insuranceIds): void
    {
        InsuranceNotification::query()
            ->whereIn('insurance_id', $insuranceIds)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
