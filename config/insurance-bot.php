<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed Telegram Chat IDs
    |--------------------------------------------------------------------------
    |
    | Only these chat IDs may use the bot's commands or receive scheduled
    | expiry notifications. Set TELEGRAM_ALLOWED_CHAT_IDS to a comma-separated
    | list, e.g. "111,222" (surrounding brackets like "[111,222]" are fine too).
    |
    */
    'allowed_chat_ids' => array_values(array_filter(array_map(
        static fn (string $id): int => (int) trim($id),
        explode(',', trim((string) env('TELEGRAM_ALLOWED_CHAT_IDS', ''), " \t\n\r\0\x0B[]"))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Open Access (testing only)
    |--------------------------------------------------------------------------
    |
    | When true, every chat is treated as allowed and the approval workflow
    | is skipped entirely - useful for demos/testing. Leave false to keep the
    | allow-list + approval flow enforced. Set via TELEGRAM_OPEN_ACCESS.
    |
    */
    'open_access' => env('TELEGRAM_OPEN_ACCESS', false),

    /*
    |--------------------------------------------------------------------------
    | Expiry Notification Thresholds
    |--------------------------------------------------------------------------
    |
    | Number of days before expiry at which policies are included in the
    | daily notification. Already-overdue policies are never notified.
    |
    */
    'expiry_thresholds' => [10, 20, 30],

    /*
    |--------------------------------------------------------------------------
    | Notification Time
    |--------------------------------------------------------------------------
    |
    | Time of day (HH:MM) the daily expiry notification is scheduled to run.
    |
    */
    'notification_time' => env('INSURANCE_NOTIFY_TIME', '08:00'),
];
