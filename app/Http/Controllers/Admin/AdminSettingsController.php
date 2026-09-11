<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ActivityLogger;
use App\Support\ClinicBrand;
use App\Support\NavQuickLinks;
use App\Support\PrintTemplateEngine;
use App\Support\PublicStorage;
use App\Support\QrImage;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AdminSettingsController extends Controller
{
    public function communications(): View
    {
        return view('admin.settings.communications', [
            'adminSection' => 'comms',
            'values' => $this->commsValues(),
        ]);
    }

    public function updateCommunications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reminders_enabled' => ['nullable', 'boolean'],
            'days_ahead' => ['required', 'integer', 'min:0', 'max:14'],
            'telegram_enabled' => ['nullable', 'boolean'],
            'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'telegram_chat_id' => ['nullable', 'string', 'max:120'],
            'sms_enabled' => ['nullable', 'boolean'],
            'sms_driver' => ['required', Rule::in(['log', 'smsir', 'http'])],
            'smsir_api_key' => ['nullable', 'string', 'max:255'],
            'smsir_line_number' => ['nullable', 'string', 'max:40'],
            'send_time' => ['required', 'date_format:H:i'],
            'website_api_key' => ['nullable', 'string', 'max:255'],
            'website_clinic_label' => ['nullable', 'string', 'max:160'],
            'visit_sms_on_booking' => ['nullable', 'boolean'],
        ]);

        $pairs = [
            'reminders.enabled' => $request->boolean('reminders_enabled'),
            'reminders.days_ahead' => (int) $validated['days_ahead'],
            'reminders.send_time' => $validated['send_time'],
            'reminders.telegram.enabled' => $request->boolean('telegram_enabled'),
            'reminders.telegram.chat_id' => trim((string) ($validated['telegram_chat_id'] ?? '')),
            'reminders.sms.enabled' => $request->boolean('sms_enabled'),
            'reminders.sms.driver' => $validated['sms_driver'],
            'reminders.sms.smsir.line_number' => trim((string) ($validated['smsir_line_number'] ?? '')),
            'services.website_api.clinic_label' => trim((string) ($validated['website_clinic_label'] ?? ''))
                ?: 'مطب شخصی (ساختمان سان)',
            'reminders.sms.visit_on_booking' => $request->boolean('visit_sms_on_booking'),
        ];

        $token = trim((string) ($validated['telegram_bot_token'] ?? ''));
        if (! SiteSettings::isMasked($token)) {
            $pairs['reminders.telegram.bot_token'] = $token;
        }

        $apiKey = trim((string) ($validated['smsir_api_key'] ?? ''));
        if (! SiteSettings::isMasked($apiKey)) {
            $pairs['reminders.sms.smsir.api_key'] = $apiKey;
        }

        $websiteKey = trim((string) ($validated['website_api_key'] ?? ''));
        if (! SiteSettings::isMasked($websiteKey)) {
            $pairs['services.website_api.key'] = $websiteKey;
        }

        SiteSettings::putMany($pairs);
        SiteSettings::applyToConfig();

        ActivityLogger::log(auth()->user(), 'updated', null, [
            'section' => 'communications',
            'sms_driver' => $pairs['reminders.sms.driver'],
            'sms_enabled' => $pairs['reminders.sms.enabled'],
            'telegram_enabled' => $pairs['reminders.telegram.enabled'],
            'website_api_configured' => (string) SiteSettings::effective('services.website_api.key', '') !== '',
        ]);

        return back()->with('success', 'تنظیمات ارتباطات ذخیره شد.');
    }

    public function brand(): View
    {
        $doctorName = (string) SiteSettings::effective('clinic.doctor_name', '');
        $phone = (string) SiteSettings::effective('clinic.phone', '');
        $logoUrl = ClinicBrand::logoDataUri();
        $signatureUrl = ClinicBrand::signatureDataUri();
        $stampUrl = ClinicBrand::stampDataUri();

        $surgeryTypesForPrint = [];
        try {
            $surgeryTypesForPrint = \App\Models\SurgeryType::query()
                ->active()
                ->ordered()
                ->with(['subtypes' => fn ($q) => $q->active()->ordered()])
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'has_general' => true,
                    'subtypes' => $t->subtypes->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()->all(),
                ])->values()->all();
        } catch (\Throwable) {
            $surgeryTypesForPrint = [];
        }

        $hospitalsForPrint = [];
        try {
            $hospitalsForPrint = \App\Models\Hospital::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($h) => ['id' => $h->id, 'name' => $h->name])
                ->values()
                ->all();
        } catch (\Throwable) {
            $hospitalsForPrint = [];
        }

        return view('admin.settings.brand', [
            'adminSection' => 'brand',
            'doctorName' => $doctorName,
            'phone' => $phone,
            'headerSpacer' => (int) SiteSettings::effective('clinic.print_header_spacer', 120),
            'logoUrl' => $logoUrl,
            'signatureUrl' => $signatureUrl,
            'stampUrl' => $stampUrl,
            'storageWritable' => PublicStorage::writable(),
            'storageRoot' => PublicStorage::root(),
            'qrSample' => QrImage::dataUri(url('/'), 160),
            'printTypes' => PrintTemplateEngine::types(),
            'printTags' => PrintTemplateEngine::tagDefinitions(),
            'printTemplates' => collect(PrintTemplateEngine::types())
                ->mapWithKeys(fn (array $type) => [$type['key'] => PrintTemplateEngine::customBody($type['key'])])
                ->all(),
            'printDefaults' => collect(PrintTemplateEngine::types())
                ->mapWithKeys(fn (array $type) => [$type['key'] => PrintTemplateEngine::defaultBody($type['key'])])
                ->all(),
            'printCustomForms' => PrintTemplateEngine::customForms(),
            'printCustomFlags' => collect(PrintTemplateEngine::types())
                ->mapWithKeys(fn (array $type) => [$type['key'] => PrintTemplateEngine::hasCustom($type['key'])])
                ->all(),
            'printTemplateMeta' => PrintTemplateEngine::allTemplateMetaForEditor(),
            'surgeryTypesForPrint' => $surgeryTypesForPrint,
            'hospitalsForPrint' => $hospitalsForPrint,
            'printBrandAssets' => [
                // Prefer public URLs in the editor (keeps Alpine boot JSON small).
                'logo' => ClinicBrand::logoUrl() ?: $logoUrl,
                'signature' => ClinicBrand::signatureUrl() ?: $signatureUrl,
                'stamp' => ClinicBrand::stampUrl() ?: $stampUrl,
                'doctorName' => $doctorName,
                'phone' => $phone,
            ],
        ]);
    }

    public function updateBrand(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'doctor_name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'print_header_spacer' => ['required', 'integer', 'min:0', 'max:400'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'signature' => ['nullable', 'image', 'max:2048'],
            'stamp' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_signature' => ['nullable', 'boolean'],
            'remove_stamp' => ['nullable', 'boolean'],
        ]);

        $pairs = [
            'clinic.doctor_name' => $validated['doctor_name'],
            'clinic.phone' => trim((string) ($validated['phone'] ?? '')),
            'clinic.print_header_spacer' => (int) $validated['print_header_spacer'],
        ];

        foreach ([
            'logo' => 'clinic.logo_path',
            'signature' => 'clinic.signature_path',
            'stamp' => 'clinic.stamp_path',
        ] as $field => $key) {
            if ($request->boolean('remove_'.$field)) {
                $this->deleteBrandFile((string) SiteSettings::effective($key, ''));
                $pairs[$key] = '';
            } elseif ($request->hasFile($field)) {
                $this->deleteBrandFile((string) SiteSettings::effective($key, ''));
                $pairs[$key] = $request->file($field)->store('clinic-brand', 'public');
            }
        }

        SiteSettings::putMany($pairs);
        SiteSettings::applyToConfig();

        ActivityLogger::log(auth()->user(), 'updated', null, [
            'section' => 'brand',
            'doctor_name' => $validated['doctor_name'],
        ]);

        return back()->with('success', 'برند و چاپ ذخیره شد.');
    }

    public function updatePrintTemplates(Request $request): RedirectResponse
    {
        $types = collect(PrintTemplateEngine::types())->pluck('key')->all();
        $rules = [
            'templates' => ['nullable', 'array'],
            'custom_forms' => ['nullable', 'array'],
            'custom_forms.*.id' => ['required_with:custom_forms', 'string', 'max:64'],
            'custom_forms.*.label' => ['required_with:custom_forms', 'string', 'max:120'],
            'custom_forms.*.desc' => ['nullable', 'string', 'max:255'],
            'custom_forms.*.body' => ['nullable', 'string', 'max:250000'],
            'custom_forms.*.delete' => ['nullable', 'boolean'],
            'template_meta' => ['nullable', 'array'],
            'template_meta.*.mode' => ['nullable', 'in:html,overlay'],
            'template_meta.*.subtype_ids' => ['nullable', 'array'],
            'template_meta.*.hospital_ids' => ['nullable', 'array'],
            'template_meta.*.sheet' => ['nullable', 'array'],
            'template_meta.*.overlay' => ['nullable', 'array'],
            'overlay_backgrounds' => ['nullable', 'array'],
            'overlay_backgrounds.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:15360'],
        ];
        foreach ($types as $type) {
            if (PrintTemplateEngine::isCustomType($type)) {
                continue;
            }
            $rules['templates.'.$type] = ['nullable', 'string', 'max:250000'];
            $rules['reset_'.$type] = ['nullable', 'boolean'];
        }

        $validated = $request->validate($rules);
        $pairs = [];

        foreach ($types as $type) {
            if (PrintTemplateEngine::isCustomType($type)) {
                continue;
            }
            if ($request->boolean('reset_'.$type)) {
                $pairs[PrintTemplateEngine::settingsKey($type)] = '';

                continue;
            }

            $raw = (string) ($validated['templates'][$type] ?? '');
            if ($raw === '') {
                continue;
            }

            $pairs[PrintTemplateEngine::settingsKey($type)] = PrintTemplateEngine::sanitizeHtml($raw);
        }

        $customForms = [];
        foreach ($validated['custom_forms'] ?? [] as $row) {
            if (! empty($row['delete'])) {
                continue;
            }
            $id = trim((string) ($row['id'] ?? ''));
            if ($id === '' || ! PrintTemplateEngine::isCustomType($id)) {
                continue;
            }
            $body = PrintTemplateEngine::sanitizeHtml((string) ($row['body'] ?? ''));
            if ($body === '' && isset($validated['templates'][$id])) {
                $body = PrintTemplateEngine::sanitizeHtml((string) $validated['templates'][$id]);
            }
            $customForms[] = [
                'id' => $id,
                'label' => trim((string) ($row['label'] ?? 'فرم سفارشی')) ?: 'فرم سفارشی',
                'desc' => trim((string) ($row['desc'] ?? '')),
                'body' => $body,
            ];
        }

        PrintTemplateEngine::saveCustomForms($customForms);

        $metaKeys = array_unique(array_merge(
            PrintTemplateEngine::typeKeys(),
            array_keys($validated['template_meta'] ?? [])
        ));

        foreach ($metaKeys as $type) {
            $rawMeta = $validated['template_meta'][$type] ?? null;
            if (! is_array($rawMeta) || ! PrintTemplateEngine::isKnownType((string) $type)) {
                continue;
            }

            $existing = PrintTemplateEngine::templateMeta((string) $type);
            $overlay = is_array($rawMeta['overlay'] ?? null) ? $rawMeta['overlay'] : $existing['overlay'];

            if ($request->hasFile('overlay_backgrounds.'.$type)) {
                $file = $request->file('overlay_backgrounds.'.$type);
                $mime = (string) $file->getMimeType();
                $overlay['background'] = $file->store('print-backgrounds', 'public');
                $overlay['background_type'] = str_contains($mime, 'pdf') ? 'pdf' : 'image';
            } elseif (isset($rawMeta['overlay']['background'])) {
                $overlay['background'] = trim((string) $rawMeta['overlay']['background']);
            }

            if (isset($rawMeta['overlay']['background_type'])) {
                $overlay['background_type'] = in_array($rawMeta['overlay']['background_type'], ['image', 'pdf'], true)
                    ? $rawMeta['overlay']['background_type']
                    : ($overlay['background_type'] ?? 'image');
            }

            if (isset($rawMeta['overlay']['tags'])) {
                $overlay['tags'] = $rawMeta['overlay']['tags'];
            }

            PrintTemplateEngine::saveTemplateMeta((string) $type, [
                'mode' => $rawMeta['mode'] ?? $existing['mode'],
                'subtype_ids' => $rawMeta['subtype_ids'] ?? [],
                'hospital_ids' => $rawMeta['hospital_ids'] ?? [],
                'sheet' => $rawMeta['sheet'] ?? ($existing['sheet'] ?? []),
                'overlay' => $overlay,
            ]);
        }

        if ($pairs !== []) {
            SiteSettings::putMany($pairs);
        }

        ActivityLogger::log(auth()->user(), 'updated', null, [
            'section' => 'print_templates',
            'types' => array_keys($pairs),
            'custom_count' => count($customForms),
        ]);

        return back()->with('success', 'قالب‌های پرینت ذخیره شد.');
    }

    private function deleteBrandFile(string $path): void
    {
        if ($path === '') {
            return;
        }

        PublicStorage::delete($path);
    }

    public function system(): View
    {
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

        $storage = [
            'documents' => $this->dirSize(PublicStorage::root()),
            'backups' => $this->dirSize($backupDir),
            'logs' => $this->dirSize(storage_path('logs')),
        ];

        $health = [
            'app' => true,
            'db' => false,
            'queue_pending' => null,
            'queue_failed' => null,
        ];

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
            // ignore
        }

        $lastAuditExport = null;
        try {
            if (Schema::hasTable('activity_logs')) {
                $lastAuditExport = \App\Models\ActivityLog::query()
                    ->where('action', 'exported')
                    ->latest()
                    ->limit(30)
                    ->get()
                    ->first(fn ($log) => ($log->new_values['section'] ?? null) === 'audit');
            }
        } catch (Throwable) {
            $lastAuditExport = null;
        }

        return view('admin.settings.system', [
            'adminSection' => 'system',
            'backups' => $backups,
            'storage' => $storage,
            'health' => $health,
            'connection' => config('database.default'),
            'driver' => config('database.connections.'.config('database.default').'.driver'),
            'lastAuditExport' => $lastAuditExport,
            'features' => \App\Support\FeatureFlags::all(),
            'privacy' => [
                'activity_log_days' => (int) SiteSettings::effective('privacy.activity_log_days', 365),
                'qr_cache_days' => (int) SiteSettings::effective('privacy.qr_cache_days', 30),
                'retention_note' => (string) SiteSettings::effective('privacy.retention_note', ''),
            ],
            'isAdmin' => auth()->user()?->isAdmin() ?? false,
        ]);
    }

    public function updatePrivacy(Request $request): RedirectResponse
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

        ActivityLogger::log(auth()->user(), 'updated', null, [
            'section' => 'privacy',
            'activity_log_days' => $validated['activity_log_days'],
        ]);

        return back()->with('success', 'سیاست نگه‌داشت داده ذخیره شد.');
    }

    public function downloadBackup(string $file)
    {
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->isDoctor(), 403);

        $name = basename($file);
        abort_unless(preg_match('/^backup_[\w\-\.]+\.(sqlite|sql)$/', $name) === 1, 404);

        $path = storage_path('app/backups'.DIRECTORY_SEPARATOR.$name);
        abort_unless(File::exists($path), 404);

        ActivityLogger::log(auth()->user(), 'exported', null, [
            'section' => 'backup_download',
            'file' => $name,
        ]);

        return response()->download($path, $name);
    }

    public function restoreBackup(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'file' => ['required', 'string'],
            'confirm' => ['required', 'in:RESTORE'],
        ]);

        $name = basename($validated['file']);
        abort_unless(preg_match('/^backup_[\w\-\.]+\.(sqlite|sql)$/', $name) === 1, 404);

        $code = Artisan::call('db:restore', [
            'file' => $name,
            '--force' => true,
        ]);
        $output = trim(Artisan::output());

        ActivityLogger::log(auth()->user(), 'updated', null, [
            'section' => 'backup_restore',
            'file' => $name,
            'exit' => $code,
        ]);

        if ($code !== 0) {
            return back()->with('error', 'بازیابی ناموفق بود. '.$output);
        }

        return back()->with('success', 'بازیابی انجام شد. برای اطمینان یک‌بار از سامانه خارج و دوباره وارد شوید.');
    }

    public function support(): View
    {
        return view('admin.settings.support', [
            'adminSection' => 'support',
            'values' => [
                'telegram' => (string) SiteSettings::effective('support.telegram', ''),
                'phone' => (string) SiteSettings::effective('support.phone', ''),
                'email' => (string) SiteSettings::effective('support.email', ''),
                'sla_hours' => (int) SiteSettings::effective('support.sla_hours', 24),
                'notes' => (string) SiteSettings::effective('support.notes', ''),
            ],
        ]);
    }

    public function updateSupport(Request $request): RedirectResponse
    {
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

        ActivityLogger::log(auth()->user(), 'updated', null, [
            'section' => 'support',
        ]);

        return back()->with('success', 'تنظیمات پشتیبانی ذخیره شد.');
    }

    public function quickLinks(): View
    {
        $links = NavQuickLinks::all();
        if ($links === []) {
            $links = [['title' => '', 'url' => '']];
        }

        return view('admin.settings.quick-links', [
            'adminSection' => 'quick-links',
            'links' => $links,
        ]);
    }

    public function updateQuickLinks(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'links' => ['nullable', 'array', 'max:'.NavQuickLinks::MAX],
            'links.*.title' => ['nullable', 'string', 'max:80'],
            'links.*.url' => ['nullable', 'string', 'max:500'],
        ]);

        $normalized = NavQuickLinks::normalize($validated['links'] ?? []);

        foreach ($validated['links'] ?? [] as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            $url = trim((string) ($row['url'] ?? ''));
            if ($title === '' && $url === '') {
                continue;
            }
            if ($title === '' || $url === '') {
                return back()->withErrors(['links' => 'برای هر ردیف هم عنوان و هم لینک لازم است.'])->withInput();
            }
            if (! NavQuickLinks::isAllowedUrl($url)) {
                return back()->withErrors(['links' => 'لینک باید مسیر داخلی (/...) یا آدرس http/https معتبر باشد.'])->withInput();
            }
        }

        NavQuickLinks::save($normalized);

        ActivityLogger::log(auth()->user(), 'updated', null, [
            'section' => 'nav_quick_links',
            'count' => count($normalized),
        ]);

        return back()->with('success', 'لینک‌های دکمه ویژه هدر ذخیره شد.');
    }

    public function features(): View
    {
        return view('admin.settings.features', [
            'adminSection' => 'features',
            'definitions' => \App\Support\FeatureFlags::definitions(),
            'groups' => \App\Support\FeatureFlags::groups(),
            'values' => \App\Support\FeatureFlags::all(),
        ]);
    }

    public function updateFeatures(Request $request): RedirectResponse
    {
        $defs = \App\Support\FeatureFlags::definitions();
        $pairs = [];
        foreach (array_keys($defs) as $key) {
            $field = str_replace('.', '_', $key);
            $pairs[$key] = $request->boolean($field);
        }

        SiteSettings::putMany($pairs);
        SiteSettings::applyToConfig();

        ActivityLogger::log(auth()->user(), 'updated', null, [
            'section' => 'features',
            'flags' => $pairs,
        ]);

        return back()->with('success', 'قابلیت‌های سامانه ذخیره شد.');
    }

    public function runBackup(): RedirectResponse
    {
        $code = Artisan::call('db:backup', ['--keep' => 14]);
        $output = trim(Artisan::output());

        ActivityLogger::log(auth()->user(), 'created', null, [
            'section' => 'backup',
            'exit' => $code,
        ]);

        if ($code !== 0) {
            return back()->with('error', 'بک‌آپ ناموفق بود. '.$output);
        }

        return back()->with('success', 'بک‌آپ با موفقیت گرفته شد.');
    }

    private function commsValues(): array
    {
        $botToken = (string) SiteSettings::effective('reminders.telegram.bot_token', '');
        $apiKey = (string) SiteSettings::effective('reminders.sms.smsir.api_key', '');
        $websiteKey = (string) SiteSettings::effective('services.website_api.key', '');

        return [
            'reminders_enabled' => (bool) SiteSettings::effective('reminders.enabled', true),
            'days_ahead' => (int) SiteSettings::effective('reminders.days_ahead', 1),
            'telegram_enabled' => (bool) SiteSettings::effective('reminders.telegram.enabled', false),
            'telegram_bot_token' => SiteSettings::maskSecret($botToken),
            'telegram_bot_token_set' => $botToken !== '',
            'telegram_chat_id' => (string) SiteSettings::effective('reminders.telegram.chat_id', ''),
            'sms_enabled' => (bool) SiteSettings::effective('reminders.sms.enabled', false),
            'sms_driver' => (string) SiteSettings::effective('reminders.sms.driver', 'log'),
            'smsir_api_key' => SiteSettings::maskSecret($apiKey),
            'smsir_api_key_set' => $apiKey !== '',
            'smsir_line_number' => (string) SiteSettings::effective('reminders.sms.smsir.line_number', ''),
            'send_time' => (string) SiteSettings::effective('reminders.send_time', '18:00'),
            'website_api_key' => SiteSettings::maskSecret($websiteKey),
            'website_api_key_set' => $websiteKey !== '',
            'website_clinic_label' => (string) SiteSettings::effective(
                'services.website_api.clinic_label',
                config('services.website_api.clinic_label', 'مطب شخصی (ساختمان سان)')
            ),
            'visit_sms_on_booking' => (bool) SiteSettings::effective('reminders.sms.visit_on_booking', false),
        ];
    }

    private function dirSize(string $path): int
    {
        if (! File::isDirectory($path)) {
            return 0;
        }

        $bytes = 0;
        foreach (File::allFiles($path) as $file) {
            $bytes += $file->getSize();
        }

        return $bytes;
    }
}
