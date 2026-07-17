<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Index');
    }
}
