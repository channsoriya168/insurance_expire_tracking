<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TelegramLaunchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Telegram/Launch', [
            'redirect' => $request->string('redirect')->trim()->value() ?: null,
        ]);
    }
}
