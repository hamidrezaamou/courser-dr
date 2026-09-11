<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>رضایت‌نامه #{{ $consent->id }}</title>
    <style>
        body { font-family: Tahoma, sans-serif; margin: 24px; color: #111; }
        .sheet { max-width: 720px; margin: 0 auto; border: 1px solid #ccc; padding: 24px; }
        .head { display: flex; justify-content: space-between; gap: 12px; align-items: center; border-bottom: 1px solid #ddd; padding-bottom: 12px; margin-bottom: 16px; }
        .head img { max-height: 60px; }
        .body { white-space: pre-line; line-height: 1.9; font-size: 14px; }
        .foot { margin-top: 32px; display: flex; justify-content: space-between; align-items: flex-end; }
        .sign img { max-height: 70px; }
        .stamp { max-height: 80px; margin-top: 8px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:12px;"><button onclick="window.print()">چاپ</button></div>
    <div class="sheet">
        <div class="head">
            <div style="display:flex;gap:12px;align-items:center;">
                @if($logoUrl)<img src="{{ $logoUrl }}" alt="logo">@endif
                <div>
                    <h1 style="font-size:18px;margin:0;">{{ $consent->template?->title }}</h1>
                    <div style="font-size:12px;color:#666;">{{ $doctorName }} @if($clinicPhone)· {{ $clinicPhone }}@endif</div>
                </div>
            </div>
            <div style="font-size:12px;" dir="ltr">{{ jalali($consent->signed_at, 'Y/m/d H:i') }}</div>
        </div>
        <p><strong>بیمار:</strong> {{ $consent->patient?->name }} · <strong>امضا:</strong> {{ $consent->signed_by_name }}</p>
        <div class="body">{{ $consent->template?->body }}</div>
        <div class="foot">
            <div style="font-size:12px;color:#666;">ثبت در پرونده الکترونیک — #{{ $consent->id }}</div>
            <div class="sign">
                @if($signatureUrl)<img src="{{ $signatureUrl }}" alt="امضا">@endif
                @if($stampUrl)<img src="{{ $stampUrl }}" alt="مهر" class="stamp">@endif
                <div style="border-top:1px solid #000;width:160px;margin-top:8px;padding-top:4px;font-size:12px;text-align:center;">{{ $doctorName }}</div>
            </div>
        </div>
    </div>
</body>
</html>
