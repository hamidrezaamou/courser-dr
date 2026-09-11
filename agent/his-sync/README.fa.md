# همگام‌سازی HIS → سایت

عامل فقط‌خواندنی روی سرور مطب. دیتابیس Bina را می‌خواند و به سایت می‌فرستد.
**هیچ INSERT / UPDATE / DELETE روی HIS اجرا نمی‌کند.**

## پیش‌نیاز

1. سایت روی **HTTPS** باشد (`https://...`).
2. در `.env` سایت:
   ```
   HIS_SYNC_ENABLED=true
   HIS_AGENT_KEY=یک-رشته-بلند-تصادفی
   HIS_AGENT_SECRET=یک-رشته-بلند-دیگر
   ```
3. یک لاگین SQL Server با نقش **`db_datareader` فقط** روی دیتابیس `HIS`.
4. سرور مطب به اینترنت خروجی داشته باشد.

## نصب قدم‌به‌قدم

### ۱) کشف ستون‌ها (یک‌بار)

روی سرور مطب، در PowerShell:

```powershell
cd C:\path\to\agent\his-sync
.\discover.ps1 -SqlServer localhost -Database HIS
```

اگر با یوزر SQL وصل می‌شوید:

```powershell
.\discover.ps1 -SqlServer localhost -Database HIS -SqlUser his_readonly -SqlPassword '***'
```

خروجی: `discovery.txt`. این فایل را برای تنظیم `config.json` نگه دارید.
**هیچ دادهٔ بیمار چاپ نمی‌شود** — فقط نام ستون و تعداد ردیف.

### ۲) ساخت config.json

```powershell
Copy-Item config.example.json config.json
notepad config.json
```

- `website.baseUrl` → آدرس HTTPS سایت
- `website.agentKey` / `agentSecret` → همان مقادیر `.env`
- `sql.*` → اتصال فقط‌خواندنی
- کوئری‌های داخل `resources` را با نام ستون‌های واقعی از `discovery.txt` اصلاح کنید

### ۳) تست اتصال

**مهم:** روی فایل `.ps1` دوبار کلیک نکنید — ویندوز آن را در Notepad باز می‌کند و خودِ کد را می‌بینید، نه خروجی اجرا.

در PowerShell:

```powershell
cd C:\path\to\agent\his-sync
powershell -NoProfile -ExecutionPolicy Bypass -File .\sync.ps1 -PingOnly
powershell -NoProfile -ExecutionPolicy Bypass -File .\sync.ps1 -DryRun -Resource patients
powershell -NoProfile -ExecutionPolicy Bypass -File .\sync.ps1 -Resource patients
```

یا دوبار کلیک روی `run-ping.bat` / `run-dryrun.bat`.

خروجی درست شبیه این است (نه متن کامل اسکریپت):

```
2026-08-26 18:20:01 [INFO] start DryRun=False
2026-08-26 18:20:02 [INFO] ping ok server_time=... resources=patients,appointments,visits,finance
```

اگر `-PingOnly` خطا داد، کلید یا HTTPS را چک کنید. اگر `-DryRun` ردیف نشان داد ولی `his_id` خالی بود، کوئری را اصلاح کنید.

### ۴) زمان‌بندی هر ۵ دقیقه

```powershell
.\install-task.ps1
```

حذف:

```powershell
.\install-task.ps1 -Uninstall
```

لاگ‌ها در پوشهٔ `logs\` هستند.

## ترتیب منابع

عامل همیشه به این ترتیب می‌فرستد:

1. `patients`
2. `appointments`
3. `visits`
4. `finance`

بیمار قبل از نوبت ساخته می‌شود تا FK نشکند.

## کوئری‌ها چه باید برگردانند

| منبع | ستون‌های لازم |
|---|---|
| patients | `his_id`, `name`, `national_code`, `mobile`, `age?`, `changed_at?` |
| appointments | `his_id`, `his_patient_id`, `patient_name`, `national_code`, `mobile`, `scheduled_date`, `scheduled_time?`, `status?`, `reason?`, `changed_at?` |
| visits | `his_id`, `his_patient_id`, `patient_name`/`name`, `national_code`, `mobile`, `diagnosis?`, `visited_at?`, `his_appointment_id?`, `changed_at?` |
| finance | `his_id`, `his_patient_id`, `patient_name`/`name`, `type` (`charge`\|`payment`), `amount`, `transaction_date`, `label?`, `method?` (`pos`/`cash`/`wallet`), `payment_type?` (1/2/3), `his_admission_id?`, `changed_at?` |

**منبع درست پول در Bina:** `Accounting.CashPayment` + `Accounting.Payment`  
(`PaymentType`: ۱=کارتخوان، ۲=نقد، ۳=کیف‌پول).  
**نه** `AdmissionAccountingBundle`. موبایل بیمار: `MobileTel` نه `MobileNo`.

پارامترهای کوئری که عامل پر می‌کند: `@Top`, `@LastId`, `@LastChangedAt`.

برای اینکه ویرایش‌های هم‌زمان از دست نروند، فیلتر پیشنهادی:

```sql
WHERE (
  @LastChangedAt IS NULL
  OR ModifiedDate > @LastChangedAt
  OR (ModifiedDate = @LastChangedAt AND Id > @LastId)
)
ORDER BY ModifiedDate ASC, Id ASC
```

اگر تاریخ شمسی در `nvarchar` ذخیره شده، نام ستون را در `jalaliColumns` بگذارید تا عامل قبل از ارسال به میلادی تبدیل کند.

## پایش

در سایت: **مدیریت ← HIS** (`/admin/his`)

- اگر بیش از ۳۰ دقیقه همگام‌سازی موفق نباشد، وضعیت «بی‌خبر» نشان داده می‌شود.
- رکوردهای آمده از HIS در سایت **فقط‌خواندنی** هستند؛ ویرایش باید در HIS انجام شود.

## امنیت

- لاگین SQL فقط `db_datareader`
- `config.json` را در پوشهٔ مشترک نگذارید (رمز دارد)
- `HIS_AGENT_SECRET` را خالی نگذارید تا امضای HMAC فعال شود
- اختیاری: `HIS_AGENT_ALLOWED_IPS` در `.env` سایت
