# اپ اندروید «آرشیو بیمار»

این پوشه یک پروژهٔ **Native Android** است (Kotlin + Jetpack Compose). سایت داخل WebView نیست؛ صفحهٔ اختصاصی موبایل دارد و داده‌ها را مستقیم از هاست Laravel می‌گیرد.

آدرس پیش‌فرض اتصال: `https://mramo.ir`  
مسیر API: `/api/mobile/v1/`

ورود با **همان کد ملی و رمز** پنل وب است.

---

## ۱) کارهایی که باید روی هاست سایت انجام شود

1. فایل‌های PHP جدید را آپلود کنید (`app/` `routes/api.php` `bootstrap/app.php` `database/migrations/`).
2. در Terminal سی‌پنل:

```bash
php artisan migrate --force
php artisan route:cache
```

یا از `cpanel-update.php` استفاده کنید.

بدون این migration، ورود اپ با خطای سرور مواجه می‌شود چون جدول `mobile_tokens` وجود ندارد.

---

## ۲) باز کردن در Android Studio (Google)

1. از سایت گوگل نصب کنید: [Android Studio](https://developer.android.com/studio)
2. نصب را کامل کنید (Android SDK، SDK Platform 35، و یک Device/Emulator).
3. Android Studio را باز کنید → **Open**.
4. پوشهٔ `android` را انتخاب کنید (نه ریشهٔ کل پروژهٔ Laravel).
   مسیر نمونه:
   `C:\Users\elahe\Downloads\Compressed\app_3\android`
5. صبر کنید Gradle همگام شود (اولین بار چند دقیقه طول می‌کشد؛ اینترنت لازم است).
6. اگر خواست SDK path:
   فایل `local.properties` ساخته می‌شود. اگر ساخته نشد، `local.properties.example` را کپی کنید و مسیر SDK ویندوز را بگذارید:
   `sdk.dir=C:\\Users\\YOU\\AppData\\Local\\Android\\Sdk`
7. یک گوشی با USB Debugging یا Emulator انتخاب کنید.
8. دکمهٔ Run سبز (Shift+F10).

اگر Gradle Wrapper خطا داد، Android Studio معمولاً خودش آن را می‌سازد: File → Settings → Build Tools → Gradle.

---

## ۳) اتصال به هاست

روی صفحهٔ ورود، فیلد «آدرس سایت» را روی دامنهٔ واقعی بگذارید، مثلاً:

`https://mramo.ir`

سپس با حساب پزشک / منشی / مدیر وارد شوید.

بیمار هم می‌تواند وارد شود؛ فقط خانه و پروندهٔ خودش را می‌بیند.

---

## ۴) امکانات نسخهٔ فعلی

- ورود امن با توکن (نه سشن مرورگر)
- خانه: آمار امروز + نوبت‌های روز
- جستجو و ثبت بیمار
- پرونده و تایم‌لاین
- بُرد نوبت ویزیت/عمل + تغییر وضعیت
- ثبت ویزیت روی روزهای تایم
- ثبت عمل: بیمارستان → نوع → روز → نوبت
- معاینه برای پزشک/مدیر
- تماس با شماره بیمار

تنظیمات سنگین وب (تایم‌ها، ادمین، حسابداری، پرینت) عمداً در اپ نیامده؛ همان‌ها روی سایت می‌مانند.

---

## ۵) ساخت APK برای نصب روی گوشی

در Android Studio: **Build → Build Bundle(s) / APK(s) → Build APK(s)**

خروجی معمولاً اینجاست:

`android/app/build/outputs/apk/debug/app-debug.apk`

این فایل را به گوشی بفرستید و نصب کنید. برای انتشار در Google Play بعداً باید Keystore و App Bundle بسازید.

---

## ۶) عیب‌یابی

| مشکل | کار |
|---|---|
| ورود ناموفق | کد ملی/رمز وب را چک کنید؛ روی هاست migrate زده شده باشد |
| «اینترنت یا آدرس سایت» | HTTPS دامنه، فیلتر فیلترینگ، یا `/api/mobile/v1/` روی سرور |
| ۴۰۴ روی login | `routes/api.php` آپلود نشده یا `route:cache` قدیمی است |
| Gradle sync fail | JDK 17 در Android Studio، اینترنت برای دانلود Gradle |
| اپ روی امولاتور به localhost | به‌جای دامنه بنویسید `http://10.0.2.2:8000` اگر `php artisan serve` روی کامپیوتر روشن است |
