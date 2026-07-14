<?php

namespace App\Http\Controllers;

use App\Models\Insurance;
use App\Services\InsuranceService;
use Illuminate\Http\RedirectResponse;

final class ToggleInsuranceNotificationReadController extends Controller
{
    public function __construct(private readonly InsuranceService $insurances) {}

    public function __invoke(Insurance $insurance): RedirectResponse
    {
        $this->insurances->toggleNotificationRead($insurance);

        return to_route('insurances.notifications');
    }
}
