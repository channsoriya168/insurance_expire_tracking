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

    /**
     * @return LengthAwarePaginator<int, Insurance>
     */
    public function paginate(?string $search, ?string $status): LengthAwarePaginator
    {
        return Insurance::query()
            ->select(['id', 'policy_no', 'insurance_company', 'insured_name', 'policy_type', 'status', 'expiry_date'])
            ->when($search, fn(Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('policy_no', 'like', "%{$search}%")
                    ->orWhere('insured_name', 'like', "%{$search}%")
                    ->orWhere('insurance_company', 'like', "%{$search}%");
            }))
            ->when($status, fn(Builder $query) => $query->where('status', $status))
            ->orderBy('expiry_date')
            ->paginate(15)
            ->withQueryString();
    }

    public function delete(Insurance $insurance): void
    {
        $insurance->delete();
    }

    public function findByPolicyNo(string $policyNo): ?Insurance
    {
        return Insurance::query()->where('policy_no', $policyNo)->first();
    }

    /**
     * Group policies by expiry urgency: already overdue, plus one bucket per
     * configured day-out threshold (e.g. 10/20/30 days before expiry).
     *
     * @return array{overdue: Collection<int, Insurance>, buckets: array<int, Collection<int, Insurance>>}
     */
    public function expiringGroups(): array
    {
        /** @var list<int> $thresholds */
        $thresholds = config('insurance-bot.expiry_thresholds');

        return [
            'overdue' => Insurance::query()->expired()->orderBy('expiry_date')->get(),
            'buckets' => collect($thresholds)
                ->mapWithKeys(fn(int $days): array => [
                    $days => Insurance::query()->expiringInDays($days)->orderBy('expiry_date')->get(),
                ])
                ->all(),
        ];
    }

    /**
     * Total count of overdue and soon-to-expire policies, for a lightweight
     * notification badge (see {@see self::expiringGroups()} for the full list).
     */
    public function expiringCount(): int
    {
        /** @var list<int> $thresholds */
        $thresholds = config('insurance-bot.expiry_thresholds');

        return Insurance::query()->expired()->count()
            + collect($thresholds)->sum(fn(int $days): int => Insurance::query()->expiringInDays($days)->count());
    }

    /**
     * @return Builder<Insurance>
     */
    public function exportQuery(ExpiryDateRange $range): Builder
    {
        return Insurance::query()
            ->when(
                ! $range->isUnfiltered(),
                fn(Builder $query) => $query->expiringBetween($range->from, $range->to),
            )
            ->orderBy('expiry_date');
    }
}
