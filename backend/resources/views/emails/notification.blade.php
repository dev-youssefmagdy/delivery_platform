<!DOCTYPE html>
<html lang="{{ $language }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
</head>
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;line-height:1.6;font-size:15px;">
        <p style="margin:0 0 16px;font-weight:bold;font-size:17px;">{{ config('app.name') }}</p>
        {!! nl2br(e($text)) !!}
    </div>
</body>
</html>
