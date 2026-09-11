<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>آفلاین | آرشیو بیمار</title>
    <style>
        body { margin:0; font-family:Tahoma,Vazirmatn,sans-serif; background:#f0f6fc; color:#0f172a; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:1.5rem; }
        .card { max-width:420px; text-align:center; background:#fff; border-radius:1.25rem; padding:2rem; box-shadow:0 20px 50px rgba(47,95,140,.12); }
        h1 { margin:0 0 .75rem; font-size:1.25rem; color:#2f5f8c; }
        p { margin:0; line-height:1.9; color:#64748b; font-size:.95rem; }
        a { display:inline-block; margin-top:1.25rem; padding:.7rem 1.2rem; border-radius:.75rem; background:#2f5f8c; color:#fff; text-decoration:none; font-weight:800; }
    </style>
</head>
<body>
    <div class="card">
        <h1>اتصال اینترنت نیست</h1>
        <p>برای کار با پرونده‌ها و نوبت‌ها به اینترنت نیاز دارید. وقتی وصل شدید دوباره تلاش کنید.</p>
        <a href="{{ url('/dashboard') }}">تلاش مجدد</a>
    </div>
</body>
</html>
