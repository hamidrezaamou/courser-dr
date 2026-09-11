# نقشهٔ کامل سامانه «آرشیو بیمار»

این فایل راهنمای داخلی پروژه است تا بعداً بتوانید به یک هوش مصنوعی بگویید **دقیقاً کدام فایل‌ها را بخواند و ادیت کند**، بدون اینکه کل پروژه را حدس بزند.

پروژه روی Laravel (PHP) + Blade + Alpine.js + Tailwind/CSS سفارشی ساخته شده.
مسیر ریشهٔ کد: `patient-archive-cpanel/`
آدرس عمومی سایت معمولاً Document Root روی پوشهٔ `public/` است.

---

## ۰) چطور از این فایل با هوش مصنوعی استفاده کنید

جملهٔ پیشنهادی وقتی می‌خواهید چیزی را عوض کنید:

> این پروژه Laravel است. فایل `MAP.md` را بخوان. می‌خواهم **[موضوع]** را تغییر بدهم. فقط همین فایل‌هایی که در بخش مربوطه نوشته شده را باز کن و ادیت کن. بقیه را دست نزن.

بعد موضوع را از فهرست میانبر پایین انتخاب کنید.

### میانبر: اگر می‌خواهم فلان چیز را عوض کنم، کدام فایل را بدهم؟

| چیزی که می‌خواهید عوض کنید | فایل‌های اصلی که باید به AI بدهید |
|---|---|
| صفحهٔ اول / ولکام / ظاهر ورود به سایت | `resources/views/welcome.blade.php` + بخش Landing در `resources/css/app.css` |
| منوی بالا (داشبورد، نوبت‌ها، ثبت عمل…) | `resources/views/layouts/navigation.blade.php` + `resources/css/app.css` (کلاس‌های `.nav-`) |
| ظاهر کلی پنل (رنگ، دکمه، فونت) | `resources/css/app.css` + `resources/views/layouts/app.blade.php` |
| جستجوی بیمار / داشبورد | `app/Http/Controllers/PatientController.php` + `resources/views/dashboard.blade.php` + `resources/views/dashboard/partials/*` |
| پرونده بیمار (تایم‌لاین، گالری، معاینه، ویس) | `resources/views/patients/show.blade.php` + `app/Http/Controllers/PatientController.php` + `VisitController` / `MedicalDocumentController` |
| ثبت ویزیت مطب | `app/Http/Controllers/AppointmentController.php` + `resources/views/appointments/create.blade.php` + `resources/views/components/booking-datetime.blade.php` + `resources/js/booking-datetime.js` |
| ثبت عمل (از پرونده بیمار) | `app/Http/Controllers/SurgeryAppointmentController.php` + `resources/views/surgery-appointments/create.blade.php` + `partials/form-fields.blade.php` + `partials/booking-script.blade.php` |
| ثبت عمل سریع (منوی «ثبت عمل») | همان کنترلر + `resources/views/surgery-appointments/register.blade.php` + همان partialها |
| تقویم و تایم‌های ویزیت/عمل | `app/Http/Controllers/TimeController.php` + `resources/views/times/index.blade.php` |
| متن پیامک آمادهٔ هر روز | `times/index.blade.php` + `times/partials/sms-edit.blade.php` + `app/Support/AppointmentSms.php` + `TimeController::updateSms` |
| بُرد نوبت‌ها (وایت‌برد روزانه) | `AppointmentBoardController.php` + `appointments/board.blade.php` + `appointments/partials/board-card.blade.php` |
| گزارشات | `ReportController.php` + `reports/index.blade.php` + `ReportNoteController.php` |
| جعبه ابزار ردیف (تماس/پیامک/ویرایش) | `components/row-toolbox.blade.php` + `components/row-toolbox-modal.blade.php` + `app/Support/ToolboxPrefs.php` |
| یادآوری خودکار SMS/تلگرام | `config/reminders.php` + `app/Services/ReminderService.php` + `AdminSettingsController` ارتباطات |
| صفحهٔ مدیریت کل سایت | `AdminDashboardController.php` + `admin/index.blade.php` + `admin/widgets/*` |
| کاربران و نقش‌ها | `AdminUserController.php` + `admin/users/*` + `app/Models/User.php` |
| پرینت عمل | `SurgeryPrintController.php` + `resources/views/prints/**` |
| چشم OD/OS/OU | `app/Support/EyeSide.php` + فرم‌های عمل |
| کد ملی / موبایل | `app/Support/IranianId.php` |
| تاریخ شمسی | `app/Support/Jalali.php` + `resources/js/booking-datetime.js` |
| مسیر URL / روت | `routes/web.php` (و اگر API سایت عمومی: `routes/api.php`) |

بعد از تغییر CSS یا JS همیشه `npm run build` لازم است تا `public/build/` به‌روز شود. روی هاست باید پوشهٔ `public/build` را هم آپلود کنید.

---

## ۱) این سامانه چیست؟

سامانهٔ داخلی کلینیک چشم‌پزشکی برای:

1. جستجو و نگهداری پروندهٔ بیمار
2. ثبت معاینه، ویس، وایت‌برد، گالری مدارک، نسخه
3. نوبت ویزیت مطب
4. نوبت عمل در بیمارستان‌ها (با نوع عمل و زیرگروه)
5. بُرد روزانه، گزارش، پرینت، پیامک/یادآوری
6. تنظیمات تایم، بیمارستان، دارو، نقش کاربران

نقش‌ها (`users.role`):

- `admin` مدیر
- `doctor` پزشک
- `assistant` منشی / دستیار
- `patient` بیمار (فقط پروندهٔ خودش: `/my-profile`)

میان‌افزار نقش: `app/Http/Middleware/RoleMiddleware.php`  
ثبت alias در: `bootstrap/app.php`

---

## ۲) ساختار پوشه‌ها (از ریشهٔ `patient-archive-cpanel`)

```
patient-archive-cpanel/
├── MAP.md                          ← همین فایل
├── routes/
│   ├── web.php                     ← تقریباً همهٔ صفحات پنل
│   ├── api.php                     ← API نوبت ویزیت برای سایت عمومی
│   ├── auth.php                    ← ورود / ثبت‌نام / رمز
│   └── console.php                 ← دستورات artisan زمان‌بندی
├── app/
│   ├── Http/Controllers/           ← منطق هر صفحه
│   ├── Http/Middleware/            ← نقش و API key
│   ├── Models/                     ← جداول دیتابیس
│   ├── Support/                    ← توابع کمکی (تاریخ، SMS، کد ملی…)
│   ├── Services/                   ← ارسال پیامک و یادآوری
│   ├── Console/Commands/           ← کرون: یادآوری، بکاپ، ایمپورت وردپرس
│   └── helpers.php                 ← توابع سراسری مثل jalali()
├── resources/
│   ├── views/                      ← HTML صفحات (Blade)
│   ├── css/app.css                 ← تقریباً تمام ظاهر
│   └── js/
│       ├── app.js                  ← Alpine + ورود booking-datetime
│       ├── booking-datetime.js     ← تقویم شمسی ثبت ویزیت
│       └── bootstrap.js
├── database/migrations/            ← ساختار جداول
├── config/                         ← تنظیمات env
├── public/                         ← فایل‌های وب (Document Root)
│   ├── .htaccess
│   ├── index.php
│   └── build/                      ← خروجی Vite (بعد از npm run build)
└── .env                            ← رمز دیتابیس، SMS.ir، تلگرام (محرمانه)
```

---

## ۳) لایه‌ها: ظاهر، منطق، داده

هر قابلیت معمولاً ۳ لایه دارد. وقتی ادیت می‌کنید معمولاً **هر سه** لازم است:

1. **View (ظاهر):** `resources/views/...`
2. **Controller (منطق ذخیره/خواندن):** `app/Http/Controllers/...`
3. **Model / جدول:** `app/Models/...` و migration

اگر تقویم/تایم زنده باشد، لایهٔ چهارم هم هست:

4. **JS:** Alpine داخل Blade، یا `resources/js/booking-datetime.js`

---

## ۴) صفحه به صفحه — جزئیات

### ۴.۱ صفحهٔ خوش‌آمد (ولکام) — آدرس `/`

- **View:** `resources/views/welcome.blade.php`
- **استایل:** انتهای `resources/css/app.css` بخش `/* —— Landing (/) —— */`  
  کلاس‌ها: `.landing` `.landing-hero` `.lp-shot` `.landing-close`
- **روت:** در `routes/web.php` خط `Route::get('/', ...)`
- **بدون لاگین** باز می‌شود.
- دمو محصول حذف شده؛ الان سکرین‌شات ساختگی از پنل داخلی است.
- دکمه ورود می‌رود به `route('login')`.

اگر ظاهر «سامانه داخلی» را می‌خواهید عوض کنید، **فقط همین دو فایل** کافی است. منو داخل پنل اینجا نیست.

---

### ۴.۲ ورود / احراز هویت

- روت‌ها: `routes/auth.php`
- کنترلرها: `app/Http/Controllers/Auth/*`
- ویوها: `resources/views/auth/*` + لایه `resources/views/layouts/guest.blade.php`
- درخواست لاگین: `app/Http/Requests/Auth/LoginRequest.php`
- پروفایل کاربر واردشده: `ProfileController` + `resources/views/profile/*`

---

### ۴.۳ لایهٔ پنل بعد از ورود

- لایهٔ اصلی همهٔ صفحات داخلی: `resources/views/layouts/app.blade.php`
- منوی بالا: `resources/views/layouts/navigation.blade.php`
- فلش موفقیت/خطا: `resources/views/components/flash.blade.php`

منوی دسکتاپ برای کارکنان:

- داشبورد → جستجوی بیمار
- نوبت‌ها → بُرد روزانه
- گزارشات
- پرینت‌ها
- ثبت عمل (فرم سریع بدون پرونده از قبل)
- ثبت بیمار
- تنظیمات (فقط پزشک/مدیر)

---

### ۴.۴ داشبورد / جستجوی بیمار — `/dashboard`

**کار:** لیست/جستجوی بیماران با نام، کد ملی، موبایل.

| لایه | فایل |
|---|---|
| کنترلر | `app/Http/Controllers/PatientController.php` متد `index` |
| صفحه | `resources/views/dashboard.blade.php` |
| هد جدول | `resources/views/dashboard/partials/results-head.blade.php` |
| بدنه جدول | `resources/views/dashboard/partials/results-body.blade.php` |
| نتایج کامل | `resources/views/dashboard/partials/results.blade.php` |
| Ajax | `resources/views/dashboard/partials/results-ajax.blade.php` |
| CSS کارت بیمار | `resources/css/app.css` بخش `/* —— Dashboard (patient search) —— */` |
| نرمال‌سازی جستجو | `app/Support/SearchNormalizer.php` |
| صفحه‌بندی | `app/Support/ListPagination.php` + `components/list-pager.blade.php` |

دکمه‌های هر کارت بیمار (ویزیت / عمل / پرونده) در `results-body` ساخته می‌شوند و به جعبه ابزار وصل‌اند.

---

### ۴.۵ ثبت بیمار جدید — `/patients/create`

| لایه | فایل |
|---|---|
| کنترلر | `PatientController` متدهای `create` / `store` |
| فرم | `resources/views/patients/create.blade.php` |
| مدل | `app/Models/Patient.php` فیلدها: `name`, `national_code`, `mobile`, `age` |

اعتبارسنجی کد ملی/موبایل: `app/Support/IranianId.php`

---

### ۴.۶ پرونده بیمار — `/patients/{id}`  (مهم‌ترین صفحه)

این صفحه شبیه چت/تایم‌لاین است: معاینات، یادداشت، نسخه، نوبت ویزیت، ثبت عمل، سررسید عمل، گالری.

| لایه | فایل |
|---|---|
| کنترلر نمایش | `PatientController::show` |
| صفحهٔ غول‌پیکر | `resources/views/patients/show.blade.php` |
| سایدبار پروفایل | `resources/views/patients/partials/profile-sidebar.blade.php` |
| کشوی نسخه | `resources/views/patients/partials/prescription-drawer.blade.php` |
| معاینه / ویس / وایت‌برد | `VisitController` |
| مدارک / گالری | `MedicalDocumentController` |
| یادداشت داخلی | `InternalNoteController` |
| نسخه | `PrescriptionController` |
| یادآوری پیگیری | `FollowUpReminderController` |

**کارت‌های جمع‌شوندهٔ ویزیت و عمل** داخل خود `patients/show.blade.php` هستند (کلاس `.evt-card`).  
منطق Alpine: `surgeryCards` / `toggleSurgeryCard`.  
استایل کارت: `resources/css/app.css` کلاس‌های `.evt-card` `.evt-card__bar` `.evt-card__drawer`.

**حالت embed گالری:** اگر URL دارای `?embed=1&open=photos` باشد، بعد از حذف عکس نباید به صفحهٔ کامل بیمار پرتاب شود. منطق redirect در `MedicalDocumentController` و `VisitController` است؛ لینک‌های فرم حذف در `patients/show.blade.php`.

دسترسی بالینی (معاینه/نسخه): معمولاً پزشک و ادمین (`role:doctor,admin` در روت‌ها). منشی پرونده را می‌بیند ولی بعضی اکشن‌ها را نه.

---

### ۴.۷ ثبت ویزیت مطب

آدرس ایجاد: `/patients/{patient}/appointments/create`

| لایه | فایل | توضیح |
|---|---|---|
| کنترلر | `app/Http/Controllers/AppointmentController.php` | `create` `store` `edit` `update` `updateStatus` `slots` `visitCalendar` |
| فرم ایجاد | `resources/views/appointments/create.blade.php` | نوع ویزیت + تقویم |
| فرم ویرایش | `resources/views/appointments/edit.blade.php` | |
| کامپوننت تقویم | `resources/views/components/booking-datetime.blade.php` | `kind="visit"` |
| JS تقویم | `resources/js/booking-datetime.js` | فقط روزهایی که در تایم‌ها ثبت شده‌اند |
| مدل نوبت | `app/Models/Appointment.php` | جدول `appointments` |
| قفل نوبت تکراری | `app/Support/SlotGuard.php` | `assertVisitSlotFree` |

**منطق تقویم ویزیت (مثل عمل):**

1. صفحه تایم‌ها روز ویزیت می‌سازد → جدول `clinic_schedules` با `kind=visit` و `hospital_id=null`
2. `GET /appointments/visit-calendar` لیست همان روزها را می‌دهد
3. JS فقط آن روزها را سبز/قابل کلیک می‌کند
4. `GET /appointments/slots?kind=visit&date=...` ساعت‌های همان روز را می‌دهد
5. اگر روز در تایم‌ها نباشد، نوبت ذخیره نمی‌شود (`assertVisitSchedule`)

دیگر ساعت پیش‌فرض ۸–۱۸ برای هر روز وجود ندارد.

---

### ۴.۸ ثبت عمل

دو ورودی دارد، ولی **فرم و اسکریپت مشترک** است:

**الف) از پرونده بیمار**  
`/patients/{patient}/surgery-appointments/create`  
ویو: `resources/views/surgery-appointments/create.blade.php`

**ب) ثبت سریع از منو**  
`/surgery/register`  
ویو: `resources/views/surgery-appointments/register.blade.php`  
اینجا بیمار ممکن است از روی کد ملی ساخته/پیدا شود.

| لایه | فایل | توضیح |
|---|---|---|
| کنترلر | `SurgeryAppointmentController.php` | `register` `registerStore` `create` `store` `edit` `update` `updateStatus` |
| فیلدهای فرم | `surgery-appointments/partials/form-fields.blade.php` | بیمارستان، نوع، زیرگروه، تاریخ، نوبت، چشم، جراح |
| اسکریپت Alpine | `surgery-appointments/partials/booking-script.blade.php` | جریان: بیمارستان → نوع → زیرگروه → تقویم → اسلات |
| مودال تقویم | `surgery-appointments/partials/calendar-modal.blade.php` | |
| ویرایش | `surgery-appointments/edit.blade.php` | |
| مدل | `app/Models/SurgeryAppointment.php` | |
| کاتالوگ/تقویم API | `AppointmentController::surgeryOptions` | |
| اسلات API | `AppointmentController::slots` با `kind=surgery` | |

**ترتیب اجباری ثبت عمل:**

1. بیمارستان (`hospitals`)
2. نوع عمل (`surgery_types`) که برای آن بیمارستان در تایم‌ها روز دارد
3. زیرگروه (`surgery_subtypes`) یا «عمومی»
4. فقط روزهایی که در تایم‌ها برای همان ترکیب ثبت شده
5. نوبت/ساعت همان روز

**چشم:** مقادیر ذخیره‌شده `OD` / `OS` / `OU`  
فایل کمکی: `app/Support/EyeSide.php`  
سلکت در `form-fields.blade.php` و `edit.blade.php`

اورژانس: چک‌باکس `is_emergency` روی فرم عمل.  
استثنا (نوبت خارج از برنامه): `is_exception`.

---

### ۴.۹ تنظیم تایم‌ها — `/settings/times`

اینجا مشخص می‌شود **کدام روزها** برای ویزیت یا عمل باز هستند.

| لایه | فایل |
|---|---|
| کنترلر | `app/Http/Controllers/TimeController.php` |
| صفحه (Alpine خیلی بزرگ) | `resources/views/times/index.blade.php` |
| کارت روزهای ویزیت | `resources/views/times/partials/day-cards.blade.php` |
| ویرایش پیامک یک روز | `resources/views/times/partials/sms-edit.blade.php` |
| مدل | `app/Models/ClinicSchedule.php` |
| افزودن سریع بیمارستان/نوع | `SettingsQuickAddController.php` |

ستون JSON `clinic_schedules.settings` تقریباً این شکل است:

```json
{
  "totalSlots": 10,
  "slotPrefix": "ویزیت",
  "slotMode": "time",
  "times": ["08:00", "08:30"],
  "smsText": "{name} عزیز، نوبت شما {date} ساعت {time} ثبت شد."
}
```

`slotMode`:

- `time` = ساعت مشخص
- `queue` = نوبت شماره‌ای (Q01, Q02, …) که با `SlotLabel` نمایش «نوبت ۱» می‌شود

فایل ساعت/نوبت: `app/Support/SlotLabel.php`

---

### ۴.۱۰ پیامک آماده (متنی که با روز ذخیره می‌شود)

**کجا ست می‌شود**

- هنگام افزودن روز در فرم راست صفحهٔ تایم‌ها (`times/index.blade.php` فیلد `sms_text`)
- روی کارت روز قبلی: دکمهٔ «✉️ پیامک»

**کجا ذخیره می‌شود**

- `TimeController::store` / `bulkStore` / `updateSms`
- داخل `clinic_schedules.settings.smsText`

**کجا استفاده می‌شود**

فایل مرکزی: `app/Support/AppointmentSms.php`

متغیرها: `{name}` `{date}` `{slotNumber}` `{hospital}` `{time}` `{surgeryType}` `{eyeType}` `{description}`

مصرف‌کننده‌ها:

| محل | فایل |
|---|---|
| بُرد نوبت → دکمه ابزار | `appointments/partials/board-card.blade.php` → `AppointmentSms::forItem` |
| گزارشات → دکمه ابزار | `reports/index.blade.php` |
| یادآوری خودکار بیمار | `app/Services/ReminderService.php` |
| پیش‌نمایش در جعبه ابزار | `components/row-toolbox-modal.blade.php` بلوک `data-rt-sms-preview` |
| ارسال واقعی SMS.ir | `SmsController` + `app/Services/Sms/SmsIrClient.php` |

اگر SMS در تنظیمات ارتباطات روی `log` باشد، ارسال واقعی نمی‌شود.

---

### ۴.۱۱ بُرد نوبت‌ها — `/appointments/board`

وایت‌برد روزانهٔ ویزیت + عمل.

| لایه | فایل |
|---|---|
| کنترلر | `AppointmentBoardController.php` |
| صفحه | `appointments/board.blade.php` |
| کارت هر نوبت | `appointments/partials/board-card.blade.php` |
| وضعیت تأیید/لغو | `components/booking-status-actions.blade.php` |
| CSS | `app.css` کلاس‌های `.board-` |

کارت‌ها جمع‌شونده‌اند (`toggleBoardCard`). ستون یادآوری هم جدا جمع می‌شود.

ارسال دستی یادآوری همان صفحه: فرم POST به `reminders.send`.

---

### ۴.۱۲ گزارشات — `/reports`

| لایه | فایل |
|---|---|
| کنترلر | `ReportController.php` |
| صفحه | `reports/index.blade.php` |
| یادداشت روی ردیف | `ReportNoteController.php` + مدل `ReportNote` |
| خروجی اکسل/فایل | `ReportController::export` |

فیلتر اورژانس، سورت هدر جدول، آیکون یادداشت در همین ویو و کنترلر است.

---

### ۴.۱۳ جعبه ابزار ردیف (Toolbox)

دکمهٔ «ابزار» روی کارت بیمار، بُرد، گزارش.

| لایه | فایل |
|---|---|
| دکمهٔ ماشه | `components/row-toolbox.blade.php` | payload شامل smsBody |
| مودال + JS خالص | `components/row-toolbox-modal.blade.php` | بدون Alpine؛ باید حتی اگر Alpine خراب شد باز شود |
| ترجیحات کاربر | `ToolboxPrefs.php` + `ToolboxPrefsController` + ستون `users.toolbox_prefs` |
| پنل پاسخ‌های آماده | `components/answer-panel.blade.php` + `ReadyAnswerController` |

اکشن‌های رایج: تماس، پیامک گوشی، ارسال از پنل SMS.ir، پرونده، ویرایش، پرینت، یادداشت گزارش.

---

### ۴.۱۴ پرینت‌ها

| لایه | فایل |
|---|---|
| کنترلر | `SurgeryPrintController.php` |
| لیست انواع | `prints/index.blade.php` |
| هاب یک عمل | `prints/hub.blade.php` |
| لایه چاپ | `prints/layout.blade.php` |
| قالب‌ها | `prints/templates/hospital.blade.php` `prescription.blade.php` `laboratory.blade.php` `anesthesiologist.blade.php` `iol_master.blade.php` |

متادیتای چاپ بیمارستان روی مدل `Hospital` (migration `add_print_meta_to_hospitals`).

---

### ۴.۱۵ تنظیمات کلینیک (غیر از ادمین کل سایت)

`SettingsController::index` **ویو ندارد**؛ فقط redirect می‌کند به `/settings/times`.
پوشهٔ `resources/views/settings` وجود ندارد.

| بخش | URL | کنترلر | ویو |
|---|---|---|---|
| هاب تنظیمات | `/settings` | SettingsController | redirect به تایم‌ها |
| تایم‌ها | `/settings/times` | TimeController | `times/index.blade.php` |
| بیمارستان‌ها | `/settings/hospitals` | HospitalController | `hospitals/index.blade.php` |
| انواع عمل | `/settings/surgery-types` | SurgeryTypeController | `surgery-types/index.blade.php` |
| داروها | `/settings/drugs` | DrugController | `drugs/index.blade.php` |

داک پایین/بالای تنظیمات (۴ تب): `components/settings-dock.blade.php`
کلاس CSS: `.settings-dock`

مدل‌ها: `Hospital` `SurgeryType` `SurgerySubtype` `Drug`

---

### ۴.۱۶ مدیریت کل سایت — `/admin`

فقط `admin` و `doctor`.

| بخش | فایل‌ها |
|---|---|
| نمای کلی ویجت‌ها | `AdminDashboardController.php` + `admin/index.blade.php` + `admin/widgets/*.blade.php` + `Support/AdminDashboardWidgets.php` |
| کاربران | `AdminUserController.php` + `admin/users/index.blade.php` + `form.blade.php` |
| ارتباطات (SMS/تلگرام/یادآوری) | `AdminSettingsController` + `admin/settings/communications.blade.php` + `config/reminders.php` |
| برند | `admin/settings/brand.blade.php` |
| سیستم / بکاپ | `admin/settings/system.blade.php` + دستور `BackupDatabase` |
| قابلیت‌ها (feature flags) | `admin/settings/features.blade.php` + `FeatureFlags.php` + `SiteSettings.php` |
| لاگ فعالیت | `ActivityLogController` + `activity-logs/index.blade.php` + `ActivityLogger.php` |

ویجت‌ها در `resources/views/admin/widgets/`:

- `kpis` `alerts` `funnel` `roles` `comms` `system` `shortcuts` `activity`
- `catalog` `docs_rx` `week_bookings` `visit_surgery` `upcoming_surgeries` `patients_growth`

داک ادمین: `components/admin-dock.blade.php`

تنظیمات ذخیره‌شده در جدول `site_settings` از طریق `SiteSettings` روی مقادیر `config/reminders.php` و غیره overlay می‌شود.

---

### ۴.۱۷ API سایت عمومی (نوبت ویزیت از وب‌سایت کلینیک)

`routes/api.php` با هدر API key:

- `GET /api/public/visit/days`
- `GET /api/public/visit/slots`
- `POST /api/public/visit/book`

کنترلر: `app/Http/Controllers/Api/PublicVisitBookingController.php`  
میان‌افزار: `VerifyWebsiteApiKey.php`  
تنظیم لیبل: `config/services.php` کلید `website_api`

این API فقط روزهایی را برمی‌گرداند که در تایم‌های **ویزیت** ثبت شده‌اند.

---

## ۵) مدل‌ها و جداول

| مدل | جدول تقریبی | یعنی چه |
|---|---|---|
| User | users | کارکنان و بیمار؛ نقش، layout ادمین، toolbox_prefs |
| Patient | patients | پرونده پایه |
| Visit | visits | معاینه متنی + مسیر ویس + مسیر وایت‌برد |
| MedicalDocument | medical_documents | عکس/فایل گالری |
| InternalNote | internal_notes | یادداشت داخلی کارکنان |
| Appointment | appointments | نوبت ویزیت مطب |
| SurgeryAppointment | surgery_appointments | نوبت عمل |
| ClinicSchedule | clinic_schedules | روز کاری ویزیت یا عمل + settings JSON |
| Hospital | hospitals | مراکز عمل |
| SurgeryType | surgery_types | مثلاً کاتاراکت |
| SurgerySubtype | surgery_subtypes | زیرگروه نوع عمل |
| Drug | drugs | کاتالوگ دارو |
| Prescription / PrescriptionItem | prescriptions / items | نسخه |
| FollowUpReminder | follow_up_reminders | یادآوری پیگیری پرونده |
| ReadyAnswer | ready_answers | متن‌های آماده پیامک |
| ReportNote | report_notes | یادداشت روی ردیف گزارش |
| ActivityLog | activity_logs | تاریخچه تغییرات |
| SiteSetting | site_settings | تنظیمات ذخیره در DB |

وضعیت نوبت‌ها (`BookingStatus.php`):

- `scheduled` در انتظار
- `confirmed` تأیید
- `cancelled` لغو
- `done` انجام‌شده

نوبت‌های `scheduled` و `confirmed` جا را اشغال می‌کنند (`holdingSlot`).

---

## ۶) فایل‌های Support (مغز مشترک — اغلب چند صفحه به آن‌ها وابسته‌اند)

اگر این‌ها را عوض کنید، اثرش گسترده است. با احتیاط به AI بدهید.

| فایل | کار |
|---|---|
| `app/Support/Jalali.php` | تبدیل میلادی ↔ شمسی؛ `jalali()` در Blade از `helpers.php` |
| `app/Support/SlotLabel.php` | ساعت در برابر نوبت شماره‌ای (Q01) |
| `app/Support/SlotGuard.php` | جلوگیری از رزرو تکراری |
| `app/Support/BookingStatus.php` | برچسب و انتقال وضعیت |
| `app/Support/AppointmentSms.php` | ساخت متن پیامک از قالب روز |
| `app/Support/EyeSide.php` | OD/OS/OU |
| `app/Support/IranianId.php` | کد ملی و موبایل ایران |
| `app/Support/Digits.php` | ارقام فارسی/انگلیسی |
| `app/Support/SearchNormalizer.php` | جستجوی نام/کد |
| `app/Support/ActivityLogger.php` | نوشتن لاگ |
| `app/Support/ToolboxPrefs.php` | دکمه‌های جعبه ابزار |
| `app/Support/SiteSettings.php` | خواندن تنظیمات سایت از DB |
| `app/Support/FeatureFlags.php` | روشن/خاموش کردن قابلیت |
| `app/Support/ListPagination.php` | صفحه‌بندی لیست‌ها |
| `app/Support/AdminDashboardWidgets.php` | تعریف ویجت‌های ادمین |

---

## ۷) ظاهر (CSS) — نقشهٔ داخل `resources/css/app.css`

یک فایل خیلی بزرگ است. با کامنت بخش‌ها را پیدا کنید:

| کامنت / کلاس | مربوط به |
|---|---|
| متغیرهای `--brand` `--ink` `--panel` نزدیک ابتدای فایل | رنگ کل پنل |
| `.nav-shell` `.nav-link` | منو |
| `.btn-primary` `.field-input` `.panel` | دکمه‌ها و فرم‌ها |
| `.evt-card` | کارت ویزیت/عمل در پرونده بیمار |
| `.tg-` (تلگرام‌گونه) | چت/تایم‌لاین پرونده |
| `Dashboard (patient search)` | کارت‌های داشبورد |
| `.board-` | بُرد نوبت و کارت‌های جمع‌شو |
| `.booking-calendar` `.booking-day` `.booking-slot` | تقویم و گرید ساعت |
| `Admin command center` / widgets | صفحه ادمین |
| `Landing (/)` `.landing` `.lp-shot` | ولکام |
| `.row-toolbox-` | مودال ابزار |
| `.times-sms-` | ویرایش پیامک روی کارت روز |
| `.rx-drug-picker` | انتخاب دارو در نسخه |

تم تاریک با کلاس `html.dark` و کلید `localStorage.theme` در navigation.

---

## ۸) جاوااسکریپت

| فایل | نقش |
|---|---|
| `resources/js/app.js` | Alpine را start می‌کند و booking-datetime را import می‌کند |
| `resources/js/booking-datetime.js` | تقویم شمسی ثبت **ویزیت** + فیلد تاریخ گزارش/بُرد (`data-jalali-date`) |
| Alpine داخل Blade | پرونده بیمار، تایم‌ها، ثبت عمل (`booking-script.blade.php`)، بُرد، گزارش، جعبه ابزار تا حدی |
| JS خالص در `row-toolbox-modal.blade.php` | باز شدن ابزار حتی اگر Alpine fail شود |

بعد از تغییر JS/CSS:

```
cd patient-archive-cpanel
npm run build
```

خروجی: `public/build/assets/app-xxxxx.css` و `app-xxxxx.js` + `manifest.json`

اگر روی هاست CSS قدیمی دیدید، `public/build` آپلود نشده یا کش مرورگر است (Ctrl+F5).

---

## ۹) دستورات زمان‌بندی (Cron)

تعریف در `app/Console/Commands/` و `routes/console.php`:

| کلاس | کار |
|---|---|
| `SendAppointmentReminders` | یادآوری نوبت ویزیت/عمل به بیمار (از قالب SMS روز) |
| `SendFollowUpReminders` | یادآوری پیگیری پرونده |
| `BackupDatabase` | بکاپ |
| `ImportWpSurgeryAppointments` | ورود داده از سیستم وردپرسی قدیمی |

سرویس‌ها: `ReminderService` `FollowUpReminderService` `SmsIrClient`

---

## ۱۰) روت‌ها بر اساس نقش (خلاصهٔ `web.php`)

**بدون لاگین:** `/` ولکام، صفحات auth.

**doctor + admin + assistant:** داشبورد، بیمار، بُرد، گزارش، پرینت، ثبت عمل، ثبت ویزیت، SMS، toolbox.

**فقط doctor + admin:** معاینه/ویس/نسخه/یادداشت، تنظیمات تایم/بیمارستان/دارو/نوع عمل.

**admin + doctor:** `/admin` و کاربران و ارتباطات و لاگ.

**patient:** فقط `/my-profile`.

اگر صفحه‌ای 403 می‌دهد، اول نقش کاربر و گروه middleware همان روت را در `web.php` چک کنید.

---

## ۱۱) وابستگی جریان‌ها (اگر یکی خراب شود، بعدی هم می‌شکند)

```
تنظیم بیمارستان
    → تنظیم نوع عمل / زیرگروه
        → تنظیم تایم عمل برای آن ترکیب + تاریخ
            → ثبت عمل فقط همان روزها را نشان می‌دهد

تنظیم تایم ویزیت (بدون بیمارستان)
    → ثبت ویزیت فقط همان روزها
    → API سایت عمومی هم همان روزها

متن sms_text روی همان روز تایم
    → بُرد / گزارش / یادآوری خودکار همان متن را پر می‌کنند
```

پس اگر «تقویم ویزیت همهٔ روزها را می‌دهد» مشکل در `booking-datetime.js` یا `visitCalendar` است.  
اگر «تقویم عمل همهٔ روزها را می‌دهد» مشکل در `booking-script.blade.php` یا `surgeryOptions` است.

---

## ۱۲) فایل‌هایی که معمولاً نباید به AI بدهید مگر اینکه صریحاً بخواهید

- `.env` (رمز و کلید SMS)
- `vendor/` `node_modules/`
- `public/build/` (ساخته می‌شود، سورس نیست)
- `database/migrations` مگر تغییر ساختار جدول لازم باشد
- کل `app.css` وقتی فقط یک صفحه را می‌خواهید؛ بگویید **کدام کلاس/کامنت بخش**

---

## ۱۳) چک‌لیست آپلود روی هاست (cPanel)

بسته به تغییر:

1. فایل‌های PHP در `app/` و `routes/`
2. Bladeها در `resources/views/`
3. اگر CSS/JS عوض شده: کل `public/build/`
4. `.htaccess` داخل `public/` اگر مسیرها 404/403 شدند
5. `php artisan migrate` فقط اگر migration جدید آمده
6. کش: `php artisan view:clear` در صورت امکان

هرگز پوشهٔ فیزیکی `public/demo` نسازید؛ با روت تداخل 403 می‌دهد (دمو حذف شده).

---

## ۱۴) جملهٔ آماده برای هر موضوع رایج

**ثبت ویزیت / تقویم ویزیت**  
بده: `AppointmentController.php`, `appointments/create.blade.php`, `components/booking-datetime.blade.php`, `resources/js/booking-datetime.js`, `ClinicSchedule.php`, `TimeController.php`

**ثبت عمل / تقویم عمل**  
بده: `SurgeryAppointmentController.php`, `surgery-appointments/register.blade.php`, `create.blade.php`, `partials/form-fields.blade.php`, `partials/booking-script.blade.php`, `partials/calendar-modal.blade.php`, `AppointmentController.php` (متدهای surgeryOptions و slots)

**پرونده بیمار / کارت‌های جمع‌شو**  
بده: `patients/show.blade.php`, بخش `.evt-card` در `app.css`, در صورت نیاز `VisitController` / `MedicalDocumentController`

**بُرد نوبت**  
بده: `AppointmentBoardController.php`, `appointments/board.blade.php`, `appointments/partials/board-card.blade.php`, CSS `.board-`

**پیامک**  
بده: `AppointmentSms.php`, `times/index.blade.php`, `times/partials/sms-edit.blade.php`, `TimeController.php`, `ReminderService.php`, `row-toolbox-modal.blade.php`

**ولکام**  
بده: `welcome.blade.php` + بخش Landing در `app.css`

**منو**  
بده: `layouts/navigation.blade.php` + `routes/web.php` اگر لینک جدید است

---

## ۱۵) نکات فنی کوچک که بارها باعث باگ شده‌اند

- تاریخ نوبت در DB **میلادی** است؛ در UI **شمسی** `Y/m/d`. تبدیل فقط با `Jalali`.
- `scheduled_time` ممکن است `08:30:00` یا `Q03` باشد. نمایش همیشه با `SlotLabel::display`.
- Apache اگر پوشه‌ای هم‌نام روت Laravel ببیند (مثل `/demo`) به‌جای Laravel، 403 می‌دهد.
- `RewriteBase /public/` در `public/.htaccess` برای بعضی هاست‌ها که سایت با `/public` باز می‌شود.
- Alpine در Blade با `@js(...)` داده PHP را به JS می‌دهد؛ اگر سینتکس Alpine خراب شود کل صفحه تعاملی می‌میرد.
- نقش منشی تنظیمات تایم را نمی‌بیند؛ اگر منشی نتواند روز اضافه کند، عمدی است مگر روت را عوض کنید.

---

آخرین به‌روزرسانی این نقشه: مطابق کد فعلی پروژه (ولکام بدون دمو، تایم با پیامک آماده، ویزیت فقط روزهای برنامه‌دار، چشم OD/OS/OU، کارت‌های جمع‌شونده در پرونده و بُرد).

---

## ۱۶) پرونده بیمار — تک‌تک عناصر UI و محل‌شان

صفحه: `resources/views/patients/show.blade.php`  
کنترلر: `PatientController::show` (کارکنان) و `PatientController::myProfile` (نقش بیمار، همان ویو)  
لایه: `layouts/app.blade.php` با کلاس بدنه `is-patient-chat` (و در حالت embed: `is-patient-embed`)  
استایل: `app.css` از حدود خط ۱۶۶۵ (`Patient profile`) و ۱۸۰۲ (`Telegram-like patient chat`) و ۳۶۳۸ (`.evt-card`)

حالت embed: URL مثل `/patients/{id}?embed=1&open=photos` از جعبه ابزار باز می‌شود تا فقط فضای بالینی دیده شود، نه کل پنل.

### ۱۶.۱ چیدمان کلی

| عنصر روی صفحه | کلاس / شناسه | کجای فایل |
|---|---|---|
| کل صفحه پرونده | `.tg-layout` + Alpine `x-data` | ابتدای `show.blade.php` |
| سایدبار راست (دسکتاپ) | `.tg-side` | include `patients/partials/profile-sidebar.blade.php` |
| ستون چت/تایم‌لاین | `.tg-chat` | همان فایل |
| هدر چت (نام بیمار، ابزارها) | `.tg-chat__header` | همان |
| فید تایم‌لاین | `#tg-feed` `.tg-chat__feed` | حلقه `$timelineByDate` |
| نوار پایین نوشتن | `.tg-composer` | انتهای ستون چت |
| کشوهای موبایل/فرم | `.tg-drawer` | پنل‌های `profile` `media` `exam` `upload` `whiteboard` `exam-view` |

منطق Alpine داخل خود `show.blade.php` است (`panel`, `composerMode`, `surgeryCards`, `openPanel`). اگر یک `{` خراب شود کل پرونده از کار می‌افتد.

### ۱۶.۲ هدر پرونده (دکمه‌های بالا)

| دکمه | کار | فایل |
|---|---|---|
| بازگشت | لینک داشبورد | `show.blade.php` کلاس `.tg-tool` |
| مراجعه بعدی | منوی فالوآپ | همان + POST `followups.store` |
| گالری / رسانه | `openPanel('media')` | همان |
| تم تاریک (موبایل) | localStorage theme | همان |
| مشخصات (موبایل) | `openPanel('profile')` | همان |

### ۱۶.۳ تایم‌لاین (هر نوع ردیف)

تایم‌لاین در PHP بالای همان ویو ساخته می‌شود (`$timeline`) از این منابع:

| نوع ردیف | `type` | ظاهر | داده از |
|---|---|---|---|
| معاینه / ویس / وایت‌برد | `visit` | حباب `.tg-bubble--staff` | مدل `Visit` |
| عکس مدارک | `document` | حباب تصویر | `MedicalDocument` |
| نسخه | `prescription` | حباب سبز «نسخه دارو» | `Prescription` |
| یادداشت داخلی | `note` | حباب هشدار؛ فقط کارکنان | `InternalNote` |
| نوبت ویزیت | `appointment` | کارت جمع‌شو `.evt-card--visit` | `Appointment` |
| ثبت عمل | `surgery_created` | کارت `.evt-card--surgery` | `SurgeryAppointment` |
| سررسید عمل | `surgery_due` | کارت `.evt-card--due` | همان عمل، تاریخ نوبت ساعت ۰۹:۰۰ ساختگی برای سورت |

کارت ویزیت/عمل پیش‌فرض **جمع** است. باز شدن با `toggleSurgeryCard` / `isSurgeryCardOpen`.  
استایل: `.evt-card__bar` نوار خلاصه، `.evt-card__drawer` جزئیات بازشده.

اگر ظاهر کارت ویزیت/عمل را می‌خواهید عوض کنید: `patients/show.blade.php` (HTML) + `app.css` کلاس‌های `.evt-*`.  
اگر دادهٔ روی کارت غلط است: مدل/کنترلر همان نوبت، نه CSS.

### ۱۶.۴ نوار نوشتن پایین (composer)

فقط برای کارکنان با دسترسی بالینی (`canManageClinical` = پزشک/ادمین):

| ابزار | شناسه/کلاس | اکشن سرور |
|---|---|---|
| ضبط ویس | `#start-voice-btn` `#stop-voice-btn` `#send-voice-btn` | POST `/visits/voice` → `VisitController::storeVoice` |
| آپلود عکس | منوی سریع → `openPanel('upload')` | POST `documents.store` |
| وایت‌برد | `openPanel('whiteboard')` | POST `/visits/drawing` |
| فرم معاینه کامل | `openPanel('exam')` | POST `visits.store` |
| سوییچ معاینه/یادداشت | `composerMode` exam\|note | `visits.store` یا `notes.store` |
| اینپوت کوتاه | `.tg-composer__input` | فیلد `examination` یا `note` |

منشی این نوار را ندارد (یا محدود است). نقش در `User::canManageClinical()`.

### ۱۶.۵ کشوها (panel)

| مقدار `panel` | یعنی چه | فرم/محتوا |
|---|---|---|
| `profile` | مشخصات بیمار در موبایل | همان سایدبار |
| `media` | تب‌ها: photos / drawings / exams / rx | گالری + لیست |
| `exam` | فرم معاینه کامل | history, examination, diagnosis, treatment, next_instruction |
| `exam-view` | پیش‌نمایش یک معاینه | از `$examVisitsPayload` |
| `upload` | آپلود تصویر (دوربین / گالری) | `x-ref="gallery"` و input دوربین |
| `whiteboard` | کانواس `#exam-canvas` | JS داخل همین ویو |
| `prescription` | کشوی نسخه | `patients/partials/prescription-drawer.blade.php` |

Query `?open=photos|drawings|exams|rx|whiteboard|prescription|exam` پنل را باز می‌کند.

### ۱۶.۶ سایدبار پروفایل

فایل جدا: `resources/views/patients/partials/profile-sidebar.blade.php`

معمولاً: نام، کد ملی، موبایل، سن، شمارنده‌ها (معاینه، عکس، ویس، وایت‌برد، نسخه)، لینک ثبت ویزیت، لینک ثبت عمل، تماس.

اگر کارت مشخصات بیمار را می‌خواهید عوض کنید **همین partial** را بدهید، نه کل `show.blade.php` مگر لازم باشد.

### ۱۶.۷ نسخه

| لایه | فایل |
|---|---|
| کشوی UI | `patients/partials/prescription-drawer.blade.php` |
| کنترلر | `PrescriptionController.php` |
| مدل | `Prescription.php` + `PrescriptionItem.php` |
| کاتالوگ دارو | `Drug.php` + صفحه `drugs/index.blade.php` |
| جستجوی دارو در نسخه | CSS انتهای `app.css` بخش Prescription drug searchable picker کلاس `.rx-drug-picker` |

---

## ۱۷) بُرد نوبت — عناصر UI

صفحه: `appointments/board.blade.php`  
کارت: `appointments/partials/board-card.blade.php`  
کنترلر: `AppointmentBoardController.php`

| عنصر | کجا |
|---|---|
| انتخاب تاریخ شمسی بالای بُرد | کامپوننت jalali در همین صفحه + `booking-datetime.js` (`data-jalali-date`) |
| ستون ویزیت / ستون عمل | `board.blade.php` |
| کارت هر نوبت (جمع/باز) | `board-card.blade.php` Alpine `boardCards` |
| ستون یادآوری | همان صفحه، جدا جمع می‌شود |
| وضعیت تأیید/لغو/انجام | `components/booking-status-actions.blade.php` → PATCH `appointments.status` یا `surgery-appointments.status` |
| جعبه ابزار روی کارت | `components/row-toolbox.blade.php` با `smsBody` از `AppointmentSms::forItem` |
| ارسال یادآوری دستی | فرم POST `reminders.send` |

CSS: `app.css` کلاس‌های `.board-` و بخش `Board: compact mobile` حدود خط ۵۹۳۹.

---

## ۱۸) منوی بالا — تک‌تک لینک‌ها

فایل فقط این است: `resources/views/layouts/navigation.blade.php`

| برچسب منو | روت Laravel | چه کسی می‌بیند |
|---|---|---|
| برند «آرشیو بیمار» | dashboard یا my-profile | همه |
| داشبورد | `dashboard` | کارکنان |
| نوبت‌ها | `appointments.board` | کارکنان |
| گزارشات | `reports.index` | کارکنان |
| پرینت‌ها | `prints.index` | کارکنان |
| ثبت عمل | `surgery-appointments.register` | کارکنان |
| ثبت بیمار | `patients.create` | کارکنان |
| تنظیمات | `settings.index` → تایم‌ها | `canManageSettings()` پزشک/ادمین |
| پرونده من | `my-profile` | نقش patient |
| دکمه تم | JS در همین فایل | همه |
| مدیریت کل سایت (دراپ‌داون) | admin.* | پزشک/ادمین |
| حساب / پروفایل کاربری | `profile.edit` | همهٔ لاگین‌شده |

منوی موبایل هم در **همان فایل** پایین‌تر تکرار شده (کلاس‌های responsive). اگر لینک جدید می‌خواهید، هم دسکتاپ هم موبایل را در همین فایل اضافه کنید و روت را در `routes/web.php`.

---

## ۱۹) فهرست کامل ویوها (Blade)

همه زیر `resources/views/`:

### صفحات اصلی
- `welcome.blade.php` — صفحهٔ اول سایت
- `dashboard.blade.php` — جستجوی بیمار
- `dashboard/partials/results.blade.php`
- `dashboard/partials/results-head.blade.php`
- `dashboard/partials/results-body.blade.php`
- `dashboard/partials/results-ajax.blade.php`
- `patients/create.blade.php`
- `patients/show.blade.php`
- `patients/partials/profile-sidebar.blade.php`
- `patients/partials/prescription-drawer.blade.php`

### نوبت ویزیت / بُرد
- `appointments/create.blade.php`
- `appointments/edit.blade.php`
- `appointments/board.blade.php`
- `appointments/partials/board-card.blade.php`

### عمل
- `surgery-appointments/register.blade.php` — ثبت سریع از منو
- `surgery-appointments/create.blade.php` — از پرونده
- `surgery-appointments/edit.blade.php`
- `surgery-appointments/partials/form-fields.blade.php`
- `surgery-appointments/partials/booking-script.blade.php`
- `surgery-appointments/partials/calendar-modal.blade.php`

### تایم و کاتالوگ
- `times/index.blade.php`
- `times/partials/day-cards.blade.php`
- `times/partials/sms-edit.blade.php`
- `hospitals/index.blade.php`
- `surgery-types/index.blade.php`
- `drugs/index.blade.php`

### گزارش / پرینت / ادمین
- `reports/index.blade.php`
- `prints/index.blade.php` `prints/hub.blade.php` `prints/layout.blade.php`
- `prints/partials/type-grid.blade.php`
- `prints/templates/hospital.blade.php` `prescription.blade.php` `laboratory.blade.php` `anesthesiologist.blade.php` `iol_master.blade.php`
- `admin/index.blade.php`
- `admin/users/index.blade.php` `admin/users/form.blade.php`
- `admin/settings/communications.blade.php` `brand.blade.php` `system.blade.php` `features.blade.php`
- `admin/widgets/` — kpis, alerts, funnel, roles, comms, system, shortcuts, activity, catalog, docs_rx, week_bookings, visit_surgery, upcoming_surgeries, patients_growth
- `activity-logs/index.blade.php`

### لایه‌ها و احراز هویت
- `layouts/app.blade.php` — پوسته پنل (منو + محتوا). مودال ابزار اینجا نیست؛ در خود صفحات داشبورد، پرونده، بُرد و گزارش با `<x-row-toolbox-modal />` می‌آید.
- `layouts/guest.blade.php` — پوسته صفحات ورود
- `layouts/navigation.blade.php`
- `auth/login.blade.php` `register.blade.php` `forgot-password.blade.php` `reset-password.blade.php` `verify-email.blade.php` `confirm-password.blade.php`
- `profile/edit.blade.php` + partialهای اطلاعات، رمز، حذف حساب

### کامپوننت‌های Blade (`resources/views/components/`)
| فایل | کار |
|---|---|
| `booking-datetime.blade.php` | تقویم شمسی ثبت ویزیت |
| `jalali-date-input.blade.php` | اینپوت تاریخ شمسی ساده |
| `row-toolbox.blade.php` | دکمه ابزار روی ردیف |
| `row-toolbox-modal.blade.php` | مودال ابزار + پیش‌نمایش SMS |
| `answer-panel.blade.php` / `answer-launcher.blade.php` | پاسخ‌های آماده |
| `booking-status-actions.blade.php` | تأیید / لغو / انجام‌شده |
| `settings-dock.blade.php` | تب‌های تنظیمات کلینیک |
| `admin-dock.blade.php` | تب‌های مدیریت سایت |
| `list-pager.blade.php` | صفحه‌بندی لیست |
| `flash.blade.php` | پیام موفقیت/خطا |
| `phone-call.blade.php` | لینک تماس |
| `day-countdown.blade.php` | شمارش روز تا نوبت |
| `item-actions.blade.php` | اکشن‌های ردیف عمومی |
| `audit-meta.blade.php` | چه کسی ثبت/ویرایش کرده |
| بقیه (`primary-button`, `modal`, `dropdown`, …) | قطعات Breeze/UI عمومی |

اگر `layouts/app.blade.php` را عوض کنید، **همهٔ صفحات پنل** عوض می‌شوند.

---

## ۲۰) فیلدهای مهم دیتابیس (برای ادیت فرم)

### Patient
`name` `national_code` `mobile` `age`

### Visit (معاینه)
`history` `examination` `diagnosis` `treatment` `next_instruction`  
`drawing_path` (وایت‌برد در storage)  
`voice_path` (ویس در storage)  
`created_by` `updated_by`

### Appointment (ویزیت)
`patient_id` `patient_name` `national_code` `mobile` `age`  
`visit_type` `scheduled_date` (میلادی) `scheduled_time` (ساعت یا Q01)  
`reason` `notes` `status` `reminder_sent_at`

### SurgeryAppointment (عمل)
علاوه بر مشخصات بیمار:  
`hospital_id` `surgery_type` (متن، نه FK) `eye_side` (OD/OS/OU)  
`scheduled_date` `scheduled_time` `surgeon_name` `notes`  
`status` `is_exception` `is_emergency` `mobile_secondary` `reminder_sent_at`

نوع عمل روی نوبت **رشته** ذخیره می‌شود (`surgery_type`)، حتی اگر کاتالوگ `surgery_types` جدا باشد.

### ClinicSchedule (روز تایم)
`kind` = `visit` یا `surgery`  
`hospital_id` برای عمل؛ برای ویزیت null  
`surgery_type_id` `surgery_subtype_id`  
`date_key` `year` `month` `day` `weekday` `display_text`  
`settings` JSON: totalSlots, slotPrefix, slotMode, times[], smsText

### User
`name` `email` `password` `role`  
`toolbox_prefs` JSON دکمه‌های ابزار  
layout ادمین (ویجت‌ها) روی کاربر ذخیره می‌شود

---

## ۲۱) فایل‌های config و env مرتبط

| فایل | موضوع |
|---|---|
| `.env` | DB، APP_URL، کلید SMS.ir، تلگرام، WEBSITE_API_KEY — **به AI ندهید** |
| `config/reminders.php` | روشن بودن یادآوری، روز جلوتر، درایور SMS (log/smsir/http)، تلگرام |
| `config/services.php` → `website_api` | کلید API سایت عمومی + لیبل مطب |
| `config/clinic.php` | نام پزشک پیش‌فرض پرینت، فاصله هدر چاپ، تلفن کلینیک |
| `config/filesystems.php` | دیسک ذخیره ویس/عکس (معمولاً `storage/app/public`) |

روی هاست باید `php artisan storage:link` زده شده باشد وگرنه عکس/ویس ۴۰۴ می‌شود. مسیر فیزیکی: `storage/app/public/` و لینک `public/storage`.

---

## ۲۲) میان‌افزار و دسترسی متد به متد

| میان‌افزار | فایل | کار |
|---|---|---|
| `auth` + `verified` | Laravel پیش‌فرض | باید وارد شده باشد |
| `role:...` | `app/Http/Middleware/RoleMiddleware.php` | نقش باید در لیست باشد وگرنه ۴۰۳ فارسی |
| API key سایت | `app/Http/Middleware/VerifyWebsiteApiKey.php` | هدر کلید برای `/api/public/*` |

متدهای کمکی روی User (`app/Models/User.php`):

- `isStaff()` ادمین/پزشک/منشی
- `canManageSchedule()` تایم و بیمارستان
- `canManageSettings()` منوی ادمین
- `canManageClinical()` معاینه ویس وایت‌برد یادداشت نسخه

منشی می‌تواند بیمار و نوبت ثبت کند، پرونده را ببیند، بُرد و گزارش را ببیند؛ **نمی‌تواند** تایم تنظیم کند یا معاینه کامل/نسخه بگذارد مگر روت/متد را عوض کنید.

---

## ۲۳) کنترلرها — هر فایل چه صفحه‌ای را می‌چرخاند

مسیر پایه: `app/Http/Controllers/`

| کنترلر | صفحات / اکشن |
|---|---|
| PatientController | داشبورد، ثبت بیمار، پرونده، my-profile |
| VisitController | ذخیره/ویرایش/حذف معاینه، ویس، وایت‌برد |
| MedicalDocumentController | آپلود/حذف عکس گالری |
| InternalNoteController | یادداشت داخلی |
| PrescriptionController | نسخه |
| FollowUpReminderController | مراجعه بعدی |
| AppointmentController | فرم ویزیت، اسلات، تقویم ویزیت، surgery-options |
| SurgeryAppointmentController | ثبت/ویرایش عمل |
| AppointmentBoardController | بُرد روزانه |
| ReportController | گزارش و خروجی فایل |
| ReportNoteController | یادداشت روی ردیف گزارش |
| TimeController | تنظیم روزها + updateSms |
| HospitalController | CRUD بیمارستان |
| SurgeryTypeController | نوع عمل و زیرگروه |
| DrugController | داروها |
| SettingsController | فقط redirect |
| SettingsQuickAddController | افزودن سریع بیمارستان/نوع از فرم عمل |
| SurgeryPrintController | لیست/هاب/قالب چاپ + متای بیمارستان |
| SmsController | ارسال SMS.ir از جعبه ابزار |
| ReminderController | ارسال دستی یادآوری از بُرد |
| ReadyAnswerController | CRUD و ارسال پاسخ آماده |
| ToolboxPrefsController | ذخیره دکمه‌های ابزار کاربر |
| ActivityLogController | لاگ و خروجی |
| ProfileController | حساب کاربری واردشده |
| Admin/AdminDashboardController | نمای کلی + چیدمان ویجت |
| Admin/AdminUserController | کاربران |
| Admin/AdminSettingsController | ارتباطات، برند، سیستم/بکاپ، فیچر |
| Api/PublicVisitBookingController | نوبت ویزیت سایت عمومی |
| Auth/* | ورود و رمز |

---

## ۲۴) ذخیره فایل‌های آپلودی

| نوع | ستون | مسیر تقریبی روی دیسک |
|---|---|---|
| عکس مدارک | `medical_documents.file_path` | `storage/app/public/...` |
| وایت‌برد | `visits.drawing_path` | همان |
| ویس | `visits.voice_path` | همان |

نمایش در مرورگر: `asset('storage/'.$path)` یعنی باید لینک `public/storage` وجود داشته باشد.

---

## ۲۵) اسکریپت‌های داخل Blade که فایل JS جدا نیستند

این‌ها را اگر خراب کنید، صفحه همان‌جا می‌میرد. برای ادیت به AI بگویید «فقط بلوک اسکریپت مربوط به X را عوض کن»:

| صفحه | اسکریپت |
|---|---|
| `patients/show.blade.php` | Alpine پرونده + ضبط ویس + کانواس وایت‌برد + گالری |
| `times/index.blade.php` | Alpine تقویم ماهانه افزودن روز، bulk، پیامک |
| `surgery-appointments/partials/booking-script.blade.php` | Alpine جریان ثبت عمل |
| `appointments/board.blade.php` | Alpine کارت‌های بُرد |
| `reports/index.blade.php` | فیلتر/سورت/یادداشت |
| `components/row-toolbox-modal.blade.php` | JS خالص مودال ابزار (عمداً بدون Alpine) |
| `components/booking-datetime.blade.php` | هوک به `booking-datetime.js` |

`resources/js/booking-datetime.js` فقط برای **ثبت ویزیت** و اینپوت‌های `data-jalali-date` است، نه تقویم عمل.

---

## ۲۶) جدول «صفحه → URL → کنترلر → ویو → CSS/JS»

| صفحه در UI | URL | کنترلر | ویو | ظاهر/اسکریپت |
|---|---|---|---|---|
| ولکام | `/` | closure در web.php | `welcome.blade.php` | `app.css` Landing |
| ورود | `/login` | AuthenticatedSessionController | `auth/login.blade.php` | guest layout |
| داشبورد | `/dashboard` | PatientController@index | `dashboard.blade.php` | Dashboard CSS |
| ثبت بیمار | `/patients/create` | PatientController | `patients/create.blade.php` | فرم‌های عمومی |
| پرونده | `/patients/{id}` | PatientController@show | `patients/show.blade.php` | tg-* evt-* |
| پرونده خود بیمار | `/my-profile` | PatientController@myProfile | همان show | همان |
| ثبت ویزیت | `/patients/{id}/appointments/create` | AppointmentController@create | `appointments/create.blade.php` | booking-datetime.js |
| ویرایش ویزیت | `/appointments/{id}/edit` | AppointmentController@edit | `appointments/edit.blade.php` | همان |
| API روزهای ویزیت | `/appointments/visit-calendar` | visitCalendar | JSON | JS ویزیت |
| API اسلات | `/appointments/slots` | slots | JSON | ویزیت و عمل |
| API گزینه‌های عمل | `/appointments/surgery-options` | surgeryOptions | JSON | booking-script |
| ثبت عمل از پرونده | `/patients/{id}/surgery-appointments/create` | SurgeryAppointmentController@create | `create.blade.php` | booking-script |
| ثبت عمل سریع | `/surgery/register` | register | `register.blade.php` | همان |
| بُرد | `/appointments/board` | AppointmentBoardController | `board.blade.php` | board- CSS |
| گزارش | `/reports` | ReportController | `reports/index.blade.php` | |
| تایم‌ها | `/settings/times` | TimeController | `times/index.blade.php` | Alpine سنگین |
| پیامک روز | `PATCH /times/{id}/sms` | updateSms | sms-edit partial | |
| بیمارستان | `/settings/hospitals` | HospitalController | `hospitals/index.blade.php` | settings-dock |
| انواع عمل | `/settings/surgery-types` | SurgeryTypeController | `surgery-types/index.blade.php` | |
| داروها | `/settings/drugs` | DrugController | `drugs/index.blade.php` | |
| پرینت لیست | `/prints` | SurgeryPrintController@index | `prints/index.blade.php` | |
| هاب چاپ یک عمل | `/surgery-appointments/{id}/prints` | hub | `prints/hub.blade.php` | |
| ادمین | `/admin` | AdminDashboardController | `admin/index.blade.php` | Admin CSS ~۶۶۰۵ |
| کاربران | `/admin/users` | AdminUserController | `admin/users/*` | |
| ارتباطات | `/admin/communications` | AdminSettingsController | `communications.blade.php` | |
| لاگ | `/activity-logs` | ActivityLogController | `activity-logs/index.blade.php` | |

---

## ۲۷) جملهٔ کپی‌پیست برای شروع هر چت جدید با AI

متن زیر را همراه با `MAP.md` بدهید:

```
پروژه Laravel است در پوشه patient-archive-cpanel.
اول فایل MAP.md را بخوان.
می‌خواهم فقط بخش «……» را عوض کنم.
طبق جدول میانبر MAP.md فقط همان فایل‌ها را باز کن.
ظاهر در resources/css/app.css است؛ اگر CSS عوض شد بگو npm run build لازم است.
بدون درخواست من migration جدید، تغییر نقش‌ها، یا دست زدن به .env نساز.
```

جای خالی را با یکی از این‌ها پر کنید: ولکام، منو، داشبورد، پرونده بیمار، کارت ویزیت/عمل، ثبت ویزیت، ثبت عمل، بُرد، گزارش، پیامک تایم، جعبه ابزار، ادمین، پرینت، چشم OD/OS/OU، کارت‌های حسابداری.

---

## ۲۸) حسابداری کارتی — `/modules/accounting`

صفحهٔ حسابداری دو تب دارد:

- **کارت‌های گزارش:** کارت‌های دلخواه که هرکدام بازهٔ تاریخ، منبع داده، فیلتر و رنگ مخصوص خودش را دارد.
- **دفتر روزانه:** همان صفحهٔ قبلی (ثبت تراکنش، دریافت/بدهکاری یک روز، بیماران بدهکار، خروجی CSV).

تب فعال با کوئری `?tab=cards` یا `?tab=ledger` تعیین می‌شود.

| لایه | فایل |
|---|---|
| کنترلر صفحه | `app/Http/Controllers/Modules/AccountingController.php` متد `index` |
| کنترلر کارت‌ها | `app/Http/Controllers/Modules/AccountingCardController.php` (`store` `update` `destroy` `presets` `export`) |
| مغز محاسبه | `app/Support/ReportCardMetrics.php` |
| مدل | `app/Models/ReportCard.php` + جدول `report_cards` |
| migration | `database/migrations/2026_08_25_140000_create_report_cards_table.php` |
| ویو | `resources/views/modules/accounting/index.blade.php` (Alpine `accountingCards`) |
| استایل | `resources/css/app.css` بخش `Accounting report cards` کلاس‌های `.rc-` |
| روت‌ها | `routes/web.php` داخل گروه `feature:features.accounting` |

### منابع دادهٔ کارت (`ReportCardMetrics::sources()`)

| کلید | یعنی چه | فیلتر اول | فیلتر دوم |
|---|---|---|---|
| `payments` | دریافت‌های صندوق | روش دریافت | — |
| `charges` | بدهکاری بیمار | منشأ (ویزیت/عمل/دستی) | — |
| `balance` | بدهکاری منهای دریافت | — | — |
| `visits` | نوبت‌های ویزیت | نوع ویزیت | وضعیت نوبت |
| `surgeries` | نوبت‌های عمل | نوع عمل | وضعیت نوبت |
| `billing_fee` / `billing_patient` / `billing_insurance` | صورتحساب: کل / سهم بیمار / سهم بیمه | تعرفه | وضعیت تسویه |

هر منبع دو عدد می‌دهد: `count` و `total`. شاخص کارت (`metric`) یکی از `count` / `total` / `avg` است و کارت شراکت درصدی از `total` را نشان می‌دهد.

### بازهٔ زمانی

`range_mode`: `today` `this_week` `this_month` `last_30` `this_year` `custom`.
حالت `custom` تاریخ شمسی را از سه سلکت (سال/ماه/روز) می‌گیرد و در `from_date` / `to_date` میلادی ذخیره می‌کند.

### دسترسی

ساخت/ویرایش/حذف کارت فقط `doctor` و `admin` (`role:doctor,admin` روی همان روت‌ها). منشی کارت‌ها را فقط می‌بیند.
دکمهٔ «ساخت کارت‌های پیش‌فرض» فقط وقتی هیچ کارتی وجود ندارد کار می‌کند.

### اگر می‌خواهید منبع دادهٔ جدید اضافه کنید

۱. یک کلید در `ReportCardMetrics::sources()` اضافه کنید (برچسب، واحد، برچسب فیلترها)
۲. گزینه‌های فیلتر آن را در `ReportCardMetrics::filterOptions()` بدهید
۳. یک `case` در `ReportCardMetrics::base()` بنویسید که `['count' => …, 'total' => …]` برگرداند

ویو خودش گزینهٔ جدید را در فرم نشان می‌دهد؛ نیازی به تغییر Blade نیست.

> بعد از این تغییرات `php artisan migrate` و `npm run build` لازم است.

---

## ۲۹) برند چاپ و کد QR — لوگو، امضا، مهر

### مسیر فایل‌های آپلودی (مهم)

دیسک `public` در `config/filesystems.php` مستقیم روی **`public/storage`** است، نه `storage/app/public`.
این کار برای cPanel است تا نیازی به symlink نباشد؛ به همین دلیل `links` در همان فایل **خالی** است و
`php artisan storage:link` نباید اجرا شود.

هر جا با فایل آپلودی کار دارید از `app/Support/PublicStorage.php` استفاده کنید، نه `storage_path('app/public/…')`:

| متد | کار |
|---|---|
| `PublicStorage::root()` | ریشهٔ واقعی دیسک |
| `PublicStorage::path($rel)` | مسیر مطلق برای نوشتن |
| `PublicStorage::resolve($rel)` | مسیر مطلق فایل موجود، وگرنه `null` — هم ریشهٔ جدید هم مسیر قدیمی را می‌گردد |
| `PublicStorage::delete($rel)` | حذف از هر دو مسیر |
| `PublicStorage::writable()` | برای هشدار در پنل مدیریت |

نصب‌های قدیمی که فایل‌هایشان هنوز در `storage/app/public` است خودبه‌خود کار می‌کنند؛ `resolve()` هر دو را می‌بیند.

### برند روی برگه‌های چاپی

`app/Support/ClinicBrand.php` دو خانواده متد دارد:

- `logoUrl()` / `signatureUrl()` / `stampUrl()` — لینک معمولی، برای صفحات وب
- `logoDataUri()` / `signatureDataUri()` / `stampDataUri()` — تصویر را **base64 داخل خود صفحه** می‌گذارد

برگه‌های چاپی همیشه از نسخهٔ `…DataUri()` استفاده می‌کنند تا اگر symlink خراب بود یا مرورگر آفلاین بود،
امضا و مهر از نسخه حذف نشوند. اگر فایل نبود مقدار `null` برمی‌گردد و Blade با `@if` از آن رد می‌شود.

استفاده‌کننده‌ها: `StructuredPrescriptionController`، `ConsentController`، `SurgeryPrintController`.

### کد QR

`app/Support/QrCode.php` یک انکودر کامل QR (Model 2، حالت byte، سطح تصحیح خطا M، نسخه‌های ۱ تا ۱۰ یعنی تا ۲۱۳ بایت)
است که **داخل خود پروژه** نوشته شده. هیچ درخواست اینترنتی و هیچ پکیج composer لازم ندارد.
`QrCode::matrix($payload)` یک ماتریس `bool` برمی‌گرداند.

`app/Support/QrImage.php` آن ماتریس را تصویر می‌کند:

| متد | خروجی |
|---|---|
| `QrImage::dataUri($payload, $size)` | PNG به‌صورت base64 (اگر GD نبود، خودکار SVG) — برای چاپ |
| `QrImage::publicUrl($payload, $size)` | لینک فایل کش‌شده |
| `QrImage::svg($payload, $size)` | خود مارک‌آپ SVG |

PNGها در `public/storage/qr-cache/` کش می‌شوند و دستور `retention:purge` فایل‌های قدیمی را پاک می‌کند.

محتوای QR نسخه = **آدرس کامل صفحهٔ چاپ نسخه**، پس اسکن با دوربین موبایل مستقیم همان نسخه را باز می‌کند.
زیر تصویر هم کد کوتاه `RX:<شماره نسخه>:P<شماره بیمار>` چاپ می‌شود.

### تست

| اسکریپت | چه چیزی را چک می‌کند |
|---|---|
| `php scripts/check-qr.php` | صحت انکودر: بردار مرجع Reed-Solomon + رمزگشایی کامل چند نمونه + نمایش ASCII |
| `php scripts/check-prescription-print.php` | لوگو/امضا/مهر روی برگه می‌آید، QR واقعاً پیام را رمزگذاری کرده، ویو رندر می‌شود (روی SQLite در حافظه، به MySQL نیاز ندارد) |

### عیب‌یابی سریع

- امضا روی نسخه نمی‌آید → «مدیریت ← برند و چاپ»؛ بالای صفحه وضعیت نوشتن در پوشهٔ آپلود و یک نمونه QR نشان داده می‌شود
- QR سفید/خالی است → افزونهٔ GD روی سرور نیست؛ کد خودش به SVG سوییچ می‌کند، ولی بهتر است GD فعال شود

---

## ۳۰) وایت‌برد پروندهٔ بیمار

### فایل‌ها

| لایه | فایل |
|---|---|
| موتور ترسیم | `resources/js/whiteboard.js` |
| نشانه‌گذاری و استایل | `resources/views/patients/show.blade.php` (کشوی `panel === 'whiteboard'` و بلوک `<style>`) |
| ذخیره | `app/Http/Controllers/VisitController.php` متد `storeDrawing` |
| ستون دیتابیس | `visits.drawing_path` — فایل PNG در `public/storage/drawings/` |
| تست | `node scripts/check-whiteboard.mjs` |

### مدل داده (مهم‌ترین نکته)

خطوط **برداری** نگه داشته می‌شوند، نه پیکسلی:

```js
{ tool: 'pen'|'eraser', color, size, isPen, points: [[x, y, pressure], …] }
```

دلیلش سه چیز است: undo آنی می‌شود (بدون PNG گرفتن)، دست‌خط موقع تغییر اندازه یا تمام‌صفحه‌شدن تار نمی‌شود،
و مهم‌تر از همه پیش‌نمایش زنده و نسخهٔ نهایی از **یک تابع واحد** رد می‌شوند، پس موقع برداشتن قلم هیچ‌چیز جابه‌جا یا ضخیم/نازک نمی‌شود.

فقط خروجی نهایی PNG است (چون ستون دیتابیس همان است). اگر نسخهٔ قدیمی را ویرایش کنید،
آن PNG به‌عنوان `background` زیر خطوط جدید می‌نشیند.

### دو بوم روی هم

| بوم | نقش |
|---|---|
| `#exam-canvas` (base) | جوهر تثبیت‌شده؛ فقط وقتی فهرست خطوط عوض شود بازکشیده می‌شود |
| `.tg-whiteboard-live` (ساختهٔ JS) | فقط خطی که زیر قلم است؛ هر فریم پاک و بازکشیده می‌شود |

`.tg-whiteboard-stage` باید `position: relative` بماند و هر دو بوم `position: absolute; inset: 0` هستند.
**اندازه را JS تعیین می‌کند** (`canvas.width/height`)، پس در CSS برای بوم `width/height` ثابت ننویسید — قبلاً همین باعث کشیده‌شدن تصویر شده بود.

### نکته‌های ریز که نباید خراب شوند

- فقط `pointermove` + `getCoalescedEvents()` نقطه اضافه می‌کند. اگر `pointerrawupdate` را هم اضافه کنید،
  هر نقطه **دوبار** ثبت می‌شود و خط روی خودش برمی‌گردد (باگ قبلی).
- `getBoundingClientRect()` فقط در `syncSize` / `pointerdown` / `pointerenter` صدا زده می‌شود، نه برای هر نقطه.
- روی `pointerleave` خط تمام نمی‌شود؛ `setPointerCapture` کار را تا `pointerup` نگه می‌دارد.
- پاک‌کن با `destination-out` عمل می‌کند (نه رنگ سفید)، پس تصویر بارگذاری‌شده را هم پاک می‌کند.
  بوم پس‌زمینهٔ سفیدش از CSS می‌آید و موقع خروجی با `destination-over` سفید زیر همه‌چیز گذاشته می‌شود.
- رد پنجه: بعد از برداشتن قلم `PEN_LOCKOUT_MS` (۴۰۰ms) لمس نادیده گرفته می‌شود؛ انگشت دوم خطِ در حال کشیدن را **لغو** می‌کند.
- دکمهٔ کناری قلم (`buttons & 32`) موقتاً پاک‌کن می‌شود.
- میان‌بر: `Ctrl+Z` و `Ctrl+Shift+Z` / `Ctrl+Y`.

### تست

`node scripts/check-whiteboard.mjs` موتور را با یک DOM ساختگی واقعاً اجرا می‌کند (بدون مرورگر) و ۴۰ ادعا را می‌سنجد:
تک‌بار ثبت‌شدن نقاط، یکسان‌بودن خط زنده و نهایی، عرض واقعی پاک‌کن، undo/redo، رد پنجه، لغو با انگشت دوم و مقیاس‌شدن با تغییر اندازه.

اگر موتور را دست زدید حتماً این را اجرا کنید.

---

## ۳۱) همگام‌سازی HIS (Bina) → سایت

### چیست

عامل فقط‌خواندنی روی سرور مطب، دیتابیس SQL Serverِ `HIS` را می‌خواند و هر ۵ دقیقه به سایت می‌فرستد.
HIS مرجع است؛ رکوردهای واردشده در سایت فقط‌خواندنی‌اند.

```
[SQL Server HIS] --SELECT--> [agent/his-sync/sync.ps1] --HTTPS+HMAC--> [/api/his/sync/{resource}]
                                                                    └─ HisImporter → patients / appointments / visits / financial_transactions
```

### فایل‌های مهم

| لایه | مسیر |
|---|---|
| تنظیمات | `config/his.php` + envهای `HIS_*` |
| مهاجرت | `database/migrations/2026_08_26_120000_create_his_sync_tables.php` |
| API | `app/Http/Controllers/Api/HisSyncController.php` |
| میان‌افزار | `app/Http/Middleware/VerifyHisAgentKey.php` (بدون کلید پیش‌فرض) |
| واردکننده‌ها | `app/Support/His/*` |
| قفل ویرایش | `HisLock::guard()` در Appointment / Visit / Surgery |
| پایش | `/admin/his` → `HisSyncMonitorController` |
| عامل | `agent/his-sync/` (`discover.ps1`, `sync.ps1`, `install-task.ps1`, `README.fa.md`) |
| تست | `php scripts/check-his-ingest.php` |

### ستون‌های اضافه‌شده

روی `patients` / `appointments` / `surgery_appointments` / `visits` / `financial_transactions`:

- `his_*_id` (unique, nullable)
- `source` (`manual` \| `his`)
- `his_synced_at`

جداول کمکی: `his_sync_states` (watermark)، `his_sync_logs` (هر دسته).

### قواعد تطبیق بیمار

1. `his_patient_id`
2. `national_code` فقط اگر خالی نباشد (NULLهای یکتا در MySQL نباید ادغام شوند)
3. ساخت بیمار جدید؛ بدون کد ملی → `his{id}` (مثل پیشوند `wp` در واردات وردپرس)

مالی فقط به `financial_transactions` می‌رود، نه `billing_records` (morph اجباری).

**منبع پول واقعی HIS (مثل اسکریپت آفلاین mali):** `Accounting.CashPayment` + `Accounting.Payment`  
با `PaymentType` ۱=کارتخوان (`pos`)، ۲=نقد (`cash`)، ۳=کیف‌پول (`wallet`).  
کوئری نمونهٔ قبلی روی `AdmissionAccountingBundle` اشتباه بود. موبایل بیمار: `MobileTel`.

در حسابداری سایت تب **صندوق HIS** همان نمای فیلترپذیر آفلاین را روی داده‌های همگام‌شده نشان می‌دهد.

### نصب سریع

۱. روی سایت: `HIS_SYNC_ENABLED=true` و کلید/رمز در `.env` — سایت باید HTTPS باشد  
۲. روی سرور مطب: `discover.ps1` → پر کردن `config.json` از روی `discovery.txt`  
۳. `sync.ps1 -PingOnly` سپس `-DryRun` سپس `install-task.ps1`  
۴. راهنمای فارسی کامل: `agent/his-sync/README.fa.md`

### تست

`php scripts/check-his-ingest.php` روی SQLite در حافظه: fail-closed بودن کلید، رد replay، وصل با کد ملی، کد مصنوعی، idempotent upsert، نگاشت وضعیت، نرمال‌سازی زمان/صف، قفل ویرایش.

---

## ۳۲) اپ اندروید کارکنان (Android Studio)

اپ نیتیو Kotlin/Compose داخل پوشهٔ `android/` است و به **همان هاست سایت** وصل می‌شود (ورود با کد ملی + رمز وب).

| لایه | فایل |
|---|---|
| روت API | `routes/api.php` گروه `mobile/v1` |
| توکن | جدول `mobile_tokens` + `app/Models/MobileToken.php` |
| میان‌افزار | `AuthenticateMobile` `EnsureMobileStaff` |
| کنترلرها | `app/Http/Controllers/Api/Mobile/*` |
| JSON یکدست | `app/Support/MobilePayload.php` |
| پروژهٔ استودیو | `android/` — راهنما: `android/README.md` |

آدرس پایه: `https://YOUR-HOST/api/mobile/v1/`  
ورود: `POST /login` با Bearer بعدی روی بقیهٔ مسیرها.

بعد از آپلود PHP روی هاست: `php artisan migrate` (جدول توکن موبایل).

تست: `php scripts/check-mobile-api.php`

---

## ۳۳) اپ ویندوز کارکنان (Tauri)

ایستگاه کاری داخل پوشهٔ `desktop/` است. سایت را WebView نمی‌کند؛ همان API موبایل را می‌خواند و پاسخ را در `localStorage` کش می‌کند تا آفلاین آخرین داده را نشان بدهد.

| لایه | فایل |
|---|---|
| پوسته و منو | `desktop/src/screens/Shell.tsx` |
| ورود | `desktop/src/screens/LoginScreen.tsx` |
| API و کش | `desktop/src/lib/api.ts` `session.ts` `cache.ts` |
| پنجره نیتیو | `desktop/src-tauri/` |
| راهنما | `desktop/README.md` |

ورود: `POST /api/mobile/v1/login` با `device_name=windows-desktop`.

صف خروج آفلاین: `desktop/src/lib/outbox.ts` (ثبت بیمار، ویزیت، عمل، وضعیت نوبت). اگر اسلات پر باشد آیتم `rejected` می‌شود.

اجرا در مرورگر: `cd desktop && npm install && npm run dev` → `http://localhost:1420`  
پنجره ویندوز: Rust + MSVC، سپس `npm run tauri dev`.


