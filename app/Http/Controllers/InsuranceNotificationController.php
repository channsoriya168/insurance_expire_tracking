<?php

namespace App\Http\Controllers;

use App\Http\Resources\InsuranceNotificationResource;
use App\Models\Insurance;
use App\Services\InsuranceNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class InsuranceNotificationController extends Controller
{
    public function __construct(private readonly InsuranceNotificationService $notifications) {}

    public function index(Request $request): Response
    {
        $expiry = $request->string('expiry')->trim()->value() ?: null;
        $unreadOnly = $request->boolean('unread');

        $notifications = $this->notifications->paginatedNotifications($expiry, $unreadOnly);

        return Inertia::render('Insurances/Notifications', [
            'notifications' => Inertia::scroll(InsuranceNotificationResource::collection($notifications))->matchOn('data.id'),
            'expiryBuckets' => array_keys($this->notifications->expiryRanges()),
            'filters' => [
                'expiry' => $expiry,
                'unread' => $unreadOnly,
            ],
            'notificationTime' => config('insurance-bot.notification_time'),
        ]);
    }

    public function toggleRead(Insurance $insurance): RedirectResponse
    {
        $this->notifications->toggleRead($insurance);

        return to_route('insurances.notifications');
    }

    public function markManyRead(Request $request): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];

        $this->notifications->markManyRead($ids);

        return back();
    }
}
