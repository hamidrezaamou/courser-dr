<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'برگه چاپ')</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Vazirmatn, Tahoma, sans-serif;
            background: #f3f4f6;
            color: #000;
            font-size: 14px;
            padding: 12px;
        }
        .print-sheet {
            max-width: 600px;
            margin: 16px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 12px rgba(0,0,0,.08);
        }
        .header-spacer { height: {{ (int) ($headerSpacer ?? 120) }}px; }
        .header-date { text-align: left; margin-bottom: 1.5rem; direction: ltr; }
        .actions {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px dashed #ccc;
        }
        .actions .btn {
            display: inline-block;
            padding: 10px 22px;
            margin: 0 4px;
            border: 1px solid #000;
            background: #fff;
            border-radius: 6px;
            font-family: inherit;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            color: #000;
        }
        .actions .btn:hover { background: #000; color: #fff; }
        .sig { margin-top: 2.5rem; display: flex; justify-content: flex-end; padding-left: 2rem; }
        .sig .box { text-align: center; }
        .sig .line { width: 150px; border-bottom: 1px solid #000; margin: 40px auto 0; }
        @media print {
            body { background: #fff; padding: 0; }
            .print-sheet { max-width: none; margin: 0; box-shadow: none; border-radius: 0; padding: 0; }
            .actions, .no-print { display: none !important; }
            @page {
                size: 148mm 210mm;
                margin: 15mm 12mm 10mm 12mm;
            }
        }
        @yield('styles')
    </style>
</head>
<body>
    <div class="print-sheet">
        @if(!empty($checklistWarning))
            <div class="no-print" style="margin-bottom:12px;padding:10px 12px;border:1px solid #f59e0b;background:#fffbeb;border-radius:8px;color:#92400e;font-size:13px;font-weight:700;">
                {{ $checklistWarning }}
                <div style="margin-top:4px;font-weight:500;color:#78716c;font-size:12px;">هشدار نرم — چاپ همچنان مجاز است.</div>
            </div>
        @endif
        @yield('content')
        <div class="actions">
            <button type="button" class="btn" onclick="window.print()">چاپ برگه</button>
            <button type="button" class="btn" onclick="window.close()">بستن</button>
        </div>
    </div>
</body>
</html>
