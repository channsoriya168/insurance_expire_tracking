<?php

namespace App\Http\Controllers;

use App\Telegram\AllowedChats;
use App\Telegram\InitDataValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class TelegramAuthController extends Controller
{
    public function __invoke(Request $request, InitDataValidator $validator): RedirectResponse
    {
        $user = $validator->validate((string) $request->input('init_data', ''));

        if ($user === null || ! AllowedChats::contains($user['id'])) {
            throw new AccessDeniedHttpException('This Telegram account is not authorized to use this app.');
        }

        $request->session()->regenerate();
        $request->session()->put('telegram_chat_id', $user['id']);

        return redirect()->route('insurances.index');
    }
}
