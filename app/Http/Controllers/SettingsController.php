<?php

namespace App\Http\Controllers;

use App\Enums\TelegramAccessStatus;
use App\Models\TelegramAccessRequest;
use Inertia\Inertia;
use Inertia\Response;

final class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Index', [
            'pendingTelegramAccessCount' => TelegramAccessRequest::query()
                ->where('status', TelegramAccessStatus::Pending)
                ->count(),
        ]);
    }
}
