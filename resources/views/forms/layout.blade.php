<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Insurance Bot') &middot; Insurance Expiry Tracking</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f4f5f7;
            color: #1f2430;
            margin: 0;
            padding: 24px 16px 64px;
        }
        .card {
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            padding: 24px;
        }
        h1 { font-size: 1.25rem; margin: 0 0 4px; }
        p.subtitle { color: #6b7280; margin: 0 0 20px; font-size: 0.9rem; }
        .status { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; }
        .error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; }
        .field { margin-bottom: 14px; }
        label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; }
        .hint { color: #9ca3af; font-size: 0.78rem; margin-top: 2px; }
        .field-error { color: #dc2626; font-size: 0.78rem; margin-top: 2px; }
        input[type=text], input[type=number], input[type=date], select, textarea {
            width: 100%;
            padding: 9px 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
        }
        textarea { resize: vertical; min-height: 60px; }
        button {
            appearance: none;
            border: none;
            border-radius: 8px;
            padding: 10px 18px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
        }
        button.primary { background: #2563eb; color: #fff; }
        button.danger { background: #dc2626; color: #fff; }
        .summary { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; font-size: 0.9rem; }
        .summary dt { font-weight: 600; color: #6b7280; }
        .summary dd { margin: 0 0 6px; }
    </style>
</head>
<body>
    <div class="card">
        @yield('content')
    </div>
</body>
</html>
