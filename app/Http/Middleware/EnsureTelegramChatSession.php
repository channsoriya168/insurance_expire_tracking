<?php

namespace App\Http\Middleware;

use App\Telegram\AllowedChats;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Authorizes access to the Insurance Mini App.
 *
 * The first request must carry a valid Telegram-signed URL (see
 * App\Telegram\FormLinks::app()) with a `chat` query param for an allowed
 * chat id; once validated, the chat id is stashed in the session so that
 * subsequent in-app Inertia navigation doesn't need to be re-signed.
 */
class EnsureTelegramChatSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasValidSignature()) {
            $chatId = (int) $request->query('chat', 0);

            if ($chatId === 0 || ! AllowedChats::contains($chatId)) {
                throw new AccessDeniedHttpException('This link is no longer authorized.');
            }

            $request->session()->regenerate();
            $request->session()->put('telegram_chat_id', $chatId);

            return $next($request);
        }

        $chatId = (int) $request->session()->get('telegram_chat_id', 0);

        if ($chatId === 0 || ! AllowedChats::contains($chatId)) {
            throw new AccessDeniedHttpException('Please reopen this app from Telegram.');
        }

        return $next($request);
    }
}
