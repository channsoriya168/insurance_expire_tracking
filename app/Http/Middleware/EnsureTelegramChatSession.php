<?php

namespace App\Http\Middleware;

use App\Telegram\AllowedChats;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authorizes access to the Insurance Mini App.
 *
 * Authentication happens once, client-side, via Telegram's `initData`
 * handshake (see TelegramAuthController), which stashes the chat id in the
 * session. This middleware just checks that session; if it's missing or no
 * longer allowed, it bounces the request back to the launch page to redo
 * the handshake instead of showing a raw error.
 */
class EnsureTelegramChatSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $chatId = (int) $request->session()->get('telegram_chat_id', 0);

        if ($chatId === 0 || ! AllowedChats::contains($chatId)) {
            return redirect()->route('telegram.launch');
        }

        return $next($request);
    }
}
