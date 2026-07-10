<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class TelegramLaunchController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Telegram/Launch');
    }
}
