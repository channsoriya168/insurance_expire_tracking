<?php

namespace App\Observers;

use App\Models\Insurance;
use App\Services\InsuranceNotificationService;

class InsuranceObserver
{
    public function __construct(private readonly InsuranceNotificationService $notifications) {}

    /**
     * Keep the persisted notification bucket in sync whenever a policy is
     * created or edited, so expiry-date changes reflect immediately instead
     * of waiting for the next scheduled sync.
     */
    public function saved(Insurance $insurance): void
    {
        $this->notifications->syncNotificationFor($insurance);
    }
}
