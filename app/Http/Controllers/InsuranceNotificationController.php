<?php

namespace App\Http\Controllers;

use App\Models\Insurance;
use App\Services\InsuranceService;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class InsuranceNotificationController extends Controller
{
    public function __construct(private readonly InsuranceService $insurances) {}

    public function __invoke(Request $request): Response
    {
        $expiry = $request->string('expiry')->trim()->value() ?: null;
        $unreadOnly = $request->boolean('unread');

        $notifications = $this->insurances->paginatedNotifications($expiry, $unreadOnly);

        return Inertia::render('Insurances/Notifications', [
            'notifications' => Inertia::scroll($this->transform($notifications)),
            'tabCounts' => $this->insurances->notificationTabCounts(),
            'filters' => [
                'expiry' => $expiry,
                'unread' => $unreadOnly,
            ],
            'notificationTime' => config('insurance-bot.notification_time'),
        ]);
    }

    private function transform(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $ranges = $this->insurances->expiryRanges();

        return $paginator->through(fn (Insurance $insurance): array => [
            'id' => $insurance->id,
            'policy_no' => $insurance->policy_no,
            'insured_name' => $insurance->insured_name,
            'insurance_company' => $insurance->insurance_company,
            'expiry_date' => $insurance->expiry_date->format('Y-m-d'),
            'read' => $insurance->notification_read_at !== null,
            'bucket' => $this->bucketFor($insurance->expiry_date, $ranges),
        ]);
    }

    /**
     * @param  array<int, array{0: int, 1: int}>  $ranges
     */
    private function bucketFor(CarbonInterface $expiryDate, array $ranges): string
    {
        if ($expiryDate->isPast() && ! $expiryDate->isToday()) {
            return 'Expired';
        }

        if ($expiryDate->isToday()) {
            return 'Today';
        }

        $days = today()->diffInDays($expiryDate);

        foreach ($ranges as $threshold => [$min, $max]) {
            if ($days >= $min && $days <= $max) {
                return "{$threshold}d";
            }
        }

        return "{$days}d";
    }
}
