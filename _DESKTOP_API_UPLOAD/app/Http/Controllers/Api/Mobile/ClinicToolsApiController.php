<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PatientSurgeryChecklistController;
use App\Http\Controllers\ReadyAnswerController;
use App\Http\Controllers\SurgeryChecklistTemplateController;
use App\Models\PatientSurgeryChecklist;
use App\Models\PatientSurgeryChecklistItem;
use App\Models\ReadyAnswer;
use App\Models\SurgeryAppointment;
use App\Support\ActivityLogger;
use App\Support\FeatureFlags;
use App\Support\MessageTags;
use App\Support\SiteSettings;
use App\Support\SurgeryChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ClinicToolsApiController extends Controller
{
    public function readyAnswers(Request $request): JsonResponse
    {
        $response = app(ReadyAnswerController::class)->index($request);
        $payload = $response->getData(true);

        return response()->json([
            'ok' => true,
            'items' => $payload['data'] ?? [],
            'categories' => $payload['categories'] ?? [],
            'count' => $payload['count'] ?? 0,
            'chips' => MessageTags::chips(),
            'sms_enabled' => (bool) config('reminders.sms.enabled') && config('reminders.sms.driver') === 'smsir',
        ]);
    }

    public function storeReadyAnswer(Request $request): JsonResponse
    {
        return app(ReadyAnswerController::class)->store($request);
    }

    public function updateReadyAnswer(Request $request, ReadyAnswer $readyAnswer): JsonResponse
    {
        return app(ReadyAnswerController::class)->update($request, $readyAnswer);
    }

    public function destroyReadyAnswer(ReadyAnswer $readyAnswer): JsonResponse
    {
        return app(ReadyAnswerController::class)->destroy($readyAnswer);
    }

    public function pinReadyAnswer(ReadyAnswer $readyAnswer): JsonResponse
    {
        return app(ReadyAnswerController::class)->togglePin($readyAnswer);
    }

    public function duplicateReadyAnswer(Request $request, ReadyAnswer $readyAnswer): JsonResponse
    {
        return app(ReadyAnswerController::class)->duplicate($request, $readyAnswer);
    }

    public function markReadyAnswerUsed(ReadyAnswer $readyAnswer): JsonResponse
    {
        return app(ReadyAnswerController::class)->markUsed($readyAnswer);
    }

    public function sendReadyAnswer(Request $request): JsonResponse
    {
        return app(ReadyAnswerController::class)->send($request, app(\App\Services\Sms\SmsIrClient::class));
    }

    public function readyAnswerBookings(Request $request, \App\Models\Patient $patient): JsonResponse
    {
        return app(ReadyAnswerController::class)->bookings($patient);
    }

    public function checklistTemplate(Request $request): JsonResponse
    {
        return app(SurgeryChecklistTemplateController::class)->show($request);
    }

    public function updateChecklistTemplate(Request $request): JsonResponse
    {
        return app(SurgeryChecklistTemplateController::class)->update($request);
    }

    public function ensureChecklist(Request $request, SurgeryAppointment $surgeryAppointment): JsonResponse
    {
        return app(PatientSurgeryChecklistController::class)->ensure($surgeryAppointment);
    }

    public function showChecklist(PatientSurgeryChecklist $checklist): JsonResponse
    {
        return app(PatientSurgeryChecklistController::class)->show($checklist);
    }

    public function toggleChecklistItem(Request $request, PatientSurgeryChecklist $checklist, PatientSurgeryChecklistItem $item): JsonResponse
    {
        return app(PatientSurgeryChecklistController::class)->toggle($request, $checklist, $item);
    }

    public function storeChecklistItem(Request $request, PatientSurgeryChecklist $checklist): JsonResponse
    {
        return app(PatientSurgeryChecklistController::class)->storeItem($request, $checklist);
    }

    public function updateChecklistItem(Request $request, PatientSurgeryChecklist $checklist, PatientSurgeryChecklistItem $item): JsonResponse
    {
        return app(PatientSurgeryChecklistController::class)->updateItem($request, $checklist, $item);
    }

    public function destroyChecklistItem(PatientSurgeryChecklist $checklist, PatientSurgeryChecklistItem $item): JsonResponse
    {
        return app(PatientSurgeryChecklistController::class)->destroyItem($checklist, $item);
    }

    public function confirmChecklist(PatientSurgeryChecklist $checklist): JsonResponse
    {
        return app(PatientSurgeryChecklistController::class)->confirm($checklist);
    }

    public function system(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);
        $backups = collect(File::files($backupDir))
            ->filter(fn ($file) => preg_match('/^backup_/', $file->getFilename()))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->take(20)
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'at' => $file->getMTime(),
            ])
            ->values();

        $health = ['app' => true, 'db' => false, 'queue_pending' => null, 'queue_failed' => null];
        try {
            DB::connection()->getPdo();
            $health['db'] = true;
        } catch (Throwable) {
            $health['db'] = false;
        }
        try {
            if (Schema::hasTable('jobs')) {
                $health['queue_pending'] = DB::table('jobs')->count();
            }
            if (Schema::hasTable('failed_jobs')) {
                $health['queue_failed'] = DB::table('failed_jobs')->count();
            }
        } catch (Throwable) {
        }

        return response()->json([
            'ok' => true,
            'backups' => $backups,
            'health' => $health,
            'connection' => config('database.default'),
            'driver' => config('database.connections.'.config('database.default').'.driver'),
            'privacy' => [
                'activity_log_days' => (int) SiteSettings::effective('privacy.activity_log_days', 365),
                'qr_cache_days' => (int) SiteSettings::effective('privacy.qr_cache_days', 30),
                'retention_note' => (string) SiteSettings::effective('privacy.retention_note', ''),
            ],
            'checklist_available' => SurgeryChecklist::isAvailable(),
            'ready_answers' => FeatureFlags::enabled('features.ready_answers'),
            'is_admin' => (bool) $request->user()?->isAdmin(),
        ]);
    }

    public function runBackup(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $code = Artisan::call('db:backup', ['--keep' => 14]);
        $output = trim(Artisan::output());
        ActivityLogger::log($request->user(), 'created', null, ['section' => 'backup', 'exit' => $code]);
        if ($code !== 0) {
            return response()->json(['ok' => false, 'message' => 'بک‌آپ ناموفق بود. '.$output], 422);
        }

        return response()->json(['ok' => true, 'message' => 'بک‌آپ با موفقیت گرفته شد.']);
    }

    public function restoreBackup(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $validated = $request->validate([
            'file' => ['required', 'string'],
            'confirm' => ['required', 'in:RESTORE'],
        ]);
        $name = basename($validated['file']);
        abort_unless(preg_match('/^backup_[\w\-\.]+\.(sqlite|sql)$/', $name) === 1, 404);
        $code = Artisan::call('db:restore', ['file' => $name, '--force' => true]);
        $output = trim(Artisan::output());
        ActivityLogger::log($request->user(), 'updated', null, ['section' => 'backup_restore', 'file' => $name, 'exit' => $code]);
        if ($code !== 0) {
            return response()->json(['ok' => false, 'message' => 'بازیابی ناموفق بود. '.$output], 422);
        }

        return response()->json(['ok' => true, 'message' => 'بازیابی انجام شد. یک‌بار خارج و دوباره وارد شوید.']);
    }

    public function downloadBackup(Request $request, string $file): BinaryFileResponse
    {
        abort_unless($request->user()?->isAdmin() || $request->user()?->isDoctor(), 403);
        $name = basename($file);
        abort_unless(preg_match('/^backup_[\w\-\.]+\.(sqlite|sql)$/', $name) === 1, 404);
        $path = storage_path('app/backups'.DIRECTORY_SEPARATOR.$name);
        abort_unless(File::exists($path), 404);
        ActivityLogger::log($request->user(), 'exported', null, ['section' => 'backup_download', 'file' => $name]);

        return response()->download($path, $name);
    }

    public function updatePrivacy(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin() || $request->user()?->isDoctor(), 403);
        $validated = $request->validate([
            'activity_log_days' => ['required', 'integer', 'min:30', 'max:3650'],
            'qr_cache_days' => ['required', 'integer', 'min:1', 'max:365'],
            'retention_note' => ['nullable', 'string', 'max:2000'],
        ]);
        SiteSettings::putMany([
            'privacy.activity_log_days' => (int) $validated['activity_log_days'],
            'privacy.qr_cache_days' => (int) $validated['qr_cache_days'],
            'privacy.retention_note' => trim((string) ($validated['retention_note'] ?? '')),
        ]);

        return response()->json(['ok' => true, 'message' => 'سیاست نگه‌داشت داده ذخیره شد.']);
    }

    public function support(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);

        return response()->json([
            'ok' => true,
            'telegram' => (string) SiteSettings::effective('support.telegram', ''),
            'phone' => (string) SiteSettings::effective('support.phone', ''),
            'email' => (string) SiteSettings::effective('support.email', ''),
            'sla_hours' => (int) SiteSettings::effective('support.sla_hours', 24),
            'notes' => (string) SiteSettings::effective('support.notes', ''),
        ]);
    }

    public function help(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'support' => [
                'telegram' => (string) SiteSettings::effective('support.telegram', ''),
                'phone' => (string) SiteSettings::effective('support.phone', ''),
                'email' => (string) SiteSettings::effective('support.email', ''),
                'sla_hours' => (int) SiteSettings::effective('support.sla_hours', 24),
                'notes' => (string) SiteSettings::effective('support.notes', ''),
            ],
            'retention_note' => (string) SiteSettings::effective(
                'privacy.retention_note',
                'پرونده‌ها طبق سیاست مطب نگه داشته می‌شوند. حذف امن فقط توسط مدیر انجام می‌شود.'
            ),
            'sections' => [
                [
                    'title' => 'برای منشی (۲ دقیقه)',
                    'items' => [
                        'بیمار را از داشبورد جستجو کنید؛ اگر نبود «ثبت بیمار».',
                        'از پرونده یا منو، «ثبت ویزیت» / «ثبت عمل» را بزنید.',
                        'در «نوبت‌ها» وضعیت را به‌روز کنید و در صورت نیاز پیامک بفرستید.',
                        'از منوی «پیگیری» کارهای امروز و عقب‌افتاده را انجام دهید و نتیجه ثبت کنید.',
                        'اگر تأیید آنلاین روشن است، درخواست‌های بنفش را تأیید/رد کنید.',
                    ],
                ],
                [
                    'title' => 'برای پزشک (۲ دقیقه)',
                    'items' => [
                        'از «صف مطب» بیمار ارجاع‌شده را باز کنید.',
                        'معاینه، وایت‌برد و نسخه را در پرونده ثبت کنید.',
                        'برای عمل، چک‌لیست و پرینت‌های بیمارستان را از ابزار ردیف بگیرید.',
                        'الگوی پیگیری هر نوع/زیرگروه عمل را از تنظیمات → پیگیری بسازید تا با ثبت نوبت عمل خودکار ایجاد شود.',
                        'خروجی گزارش و ممیزی در مدیریت در دسترس است.',
                    ],
                ],
                [
                    'title' => 'پشتیبان‌گیری و امنیت',
                    'items' => [
                        'بک‌آپ روزانه خودکار ساعت ۰۲:۳۰؛ دستی از «مدیریت → سامانه».',
                        'بازیابی فقط برای مدیر؛ قبل از restore یک بک‌آپ تازه گرفته می‌شود.',
                        'مشاهده پرونده در ممیزی ثبت می‌شود.',
                        'حذف امن پرونده: فقط مدیر، با تأیید نام بیمار.',
                    ],
                ],
            ],
        ]);
    }

    public function updateSupport(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canManageSettings(), 403);
        $validated = $request->validate([
            'telegram' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'sla_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        SiteSettings::putMany([
            'support.telegram' => trim((string) ($validated['telegram'] ?? '')),
            'support.phone' => trim((string) ($validated['phone'] ?? '')),
            'support.email' => trim((string) ($validated['email'] ?? '')),
            'support.sla_hours' => (int) $validated['sla_hours'],
            'support.notes' => trim((string) ($validated['notes'] ?? '')),
        ]);

        return response()->json(['ok' => true, 'message' => 'تنظیمات پشتیبانی ذخیره شد.']);
    }
}
