# مستندات استقرار و انتقال پروژه (Deployment)
این فایل شامل چک‌لیست و تنظیمات لازم برای انتقال سامانه پرونده الکترونیک سلامت (EHR) از سرور محلی (کامپیوتر شخصی) به هاست واقعی (cPanel) است.

## ۱. پیش‌نیازهای سرور (cPanel)
* **نسخه PHP مورد نیاز:** PHP 8.2 یا بالاتر
* **دیتابیس:** MySQL یا MariaDB
* **اکستنشن‌های ضروری PHP:** BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML

## ۲. ساختار پوشه‌ها در هاست
برای امنیت بیشتر، فایل‌های اصلی پروژه نباید در پوشه `public_html` قرار بگیرند. ساختار باید به این شکل باشد:
* فایل‌های هسته لاراول (پوشه‌های app, storage, vendor و فایل .env) -> مسیر `/home/user/patient-archive/`
* فایل‌های داخل پوشه public لاراول (مثل تصاویر و فایل‌های CSS/JS) -> مسیر `/home/user/public_html/`

## ۳. تنظیمات دیتابیس (فایل .env)
پس از انتقال به هاست، مقادیر زیر در فایل `.env` باید تغییر کنند:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=[https://ehr.drfotouhi.com](https://ehr.drfotouhi.com)

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=نام_دیتابیس_در_سیپنل
DB_USERNAME=نام_کاربر_دیتابیس
DB_PASSWORD=رمز_عبور_دیتابیس
UPDATE_TOKEN=یک_رمز_قوی_برای_به_روزرسانی
```

## ۴. نصب اولیه (فقط یک‌بار)

1. فایل‌های پروژه را روی هاست آپلود کنید.
2. `composer install --no-dev --optimize-autoloader` (در Terminal cPanel یا محلی قبل از آپلود vendor).
3. `npm ci && npm run build` (برای CSS/JS — خروجی در `public/build`).
4. بروید به: `https://your-domain.com/cpanel-install.php`
5. فرم را پر کنید و «شروع نصب» را بزنید.
6. **بعد از نصب موفق:** `cpanel-install.php` را حذف کنید.

## ۵. به‌روزرسانی (بدون نصب مجدد)

هر بار که نسخه جدید دارید، **نیازی به پاک کردن دیتابیس یا نصب از نو نیست**.

### روش A — از مرورگر (cPanel)

1. فایل‌های جدید را جایگزین کنید (به‌جز `.env` و محتوای `storage/`).
2. در `.env` مقدار `UPDATE_TOKEN` را تنظیم کنید.
3. بروید به: `https://your-domain.com/cpanel-update.php`
4. رمز را وارد کنید و «اجرای به‌روزرسانی» را بزنید.
5. Ctrl+F5 در مرورگر.

### روش B — از Terminal cPanel

```bash
cd /home/user/patient-archive
php artisan app:update
```

این دستور به‌ترتیب اجرا می‌کند:
- `migrate --force` (جداول جدید، داده حفظ)
- `optimize:clear`
- در production: `config:cache` + `route:cache` + `view:cache`

### چه چیزهایی را آپلود کنید؟

| همیشه | معمولاً نگه دارید |
|--------|-------------------|
| `app/` | `.env` |
| `routes/` | `storage/` (آپلودها) |
| `resources/` | `vendor/` (یا composer install) |
| `database/migrations/` | |
| `public/` (شامل build) | |
| `VERSION` | |

### نسخه فعلی

نسخه در فایل `VERSION` در ریشه پروژه است (مثلاً `1.3.0`).

## ۶. نکات امنیتی

- `cpanel-install.php` را بعد از نصب حذف کنید.
- `cpanel-update.php` را با `UPDATE_TOKEN` قوی محافظت کنید یا بعد از هر به‌روزرسانی حذف کنید.
- `APP_DEBUG=false` در production.
- از `storage/` و `.env` بکاپ بگیرید قبل از به‌روزرسانی‌های بزرگ.

### ۷. نصب اپ روی موبایل

دو راه دارید:

**الف) اپ نیتیو اندروید (پیشنهادی برای کارکنان)**  
پوشهٔ `android/` را در Android Studio باز کنید. راهنما: `android/README.md`.  
روی هاست حتماً `php artisan migrate` بزنید تا جدول توکن موبایل ساخته شود.

**ب) PWA**
سایت به‌صورت **Progressive Web App** قابل نصب روی گوشی و تبلت است.

### Android / Chrome
1. سایت را در Chrome باز کنید (باید **HTTPS** باشد).
2. بنر «نصب اپ» پایین صفحه را بزنید، یا از منو ⋮ → **Install app**.

### iPhone / iPad (Safari)
1. Safari → دکمه **Share** (مربع با فلش).
2. **Add to Home Screen** → Add.

### بعد از deploy
- `npm run build` را بزنید (فایل `pwa.js` داخل bundle است).
- `php artisan route:cache` یا `cpanel-update.php` برای ثبت routeهای `/manifest.webmanifest` و `/sw.js`.