@php
    $fmt = function (int $bytes): string {
        if ($bytes < 1024) return $bytes.' B';
        if ($bytes < 1048576) return number_format($bytes / 1024, 1).' KB';
        if ($bytes < 1073741824) return number_format($bytes / 1048576, 1).' MB';
        return number_format($bytes / 1073741824, 2).' GB';
    };
@endphp

<x-app-layout body-class="is-admin">
    <x-slot name="header">
        <div class="admin-header">
            <div>
                <p class="admin-header__eyebrow">مدیریت کل سایت</p>
                <h2 class="admin-header__title">سامانه</h2>
            </div>
            <div class="admin-header__actions">
                <form method="POST" action="{{ route('admin.settings.backup') }}">
                    @csrf
                    <button type="submit" class="btn-primary btn-primary--compact" onclick="return confirm('بک‌آپ دیتابیس الان گرفته شود؟')">
                        اجرای بک‌آپ
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-admin-dock :admin-section="'system'" />
            <x-flash />

            <div class="admin-split">
                <section class="admin-panel">
                    <div class="admin-panel__head">
                        <h3 class="admin-panel__title">سلامت سیستم</h3>
                    </div>
                    <div class="admin-status-list">
                        <div class="admin-status-row">
                            <span>اپلیکیشن</span>
                            <strong class="is-on">سالم</strong>
                        </div>
                        <div class="admin-status-row">
                            <span>دیتابیس ({{ $driver }})</span>
                            <strong class="{{ $health['db'] ? 'is-on' : 'is-off' }}">{{ $health['db'] ? 'متصل' : 'قطع' }}</strong>
                        </div>
                        <div class="admin-status-row">
                            <span>صف انتظار (jobs)</span>
                            <strong>{{ $health['queue_pending'] === null ? '—' : number_format($health['queue_pending']) }}</strong>
                        </div>
                        <div class="admin-status-row">
                            <span>جاب‌های ناموفق</span>
                            <strong class="{{ ($health['queue_failed'] ?? 0) > 0 ? 'is-off' : 'is-on' }}">
                                {{ $health['queue_failed'] === null ? '—' : number_format($health['queue_failed']) }}
                            </strong>
                        </div>
                    </div>
                    <p class="admin-panel__hint">کرون‌ها: یادآوری ۱۸:۰۰ · پیگیری ۱۰:۰۰ · بک‌آپ ۰۲:۳۰ · پاکسازی هفتگی ۰۳:۱۵</p>
                </section>

                <section class="admin-panel">
                    <div class="admin-panel__head">
                        <h3 class="admin-panel__title">فضای ذخیره‌سازی</h3>
                    </div>
                    <div class="admin-status-list">
                        <div class="admin-status-row">
                            <span>فایل‌های عمومی (عکس/ویس/...)</span>
                            <strong class="ltr-data" dir="ltr">{{ $fmt($storage['documents']) }}</strong>
                        </div>
                        <div class="admin-status-row">
                            <span>بک‌آپ‌ها</span>
                            <strong class="ltr-data" dir="ltr">{{ $fmt($storage['backups']) }}</strong>
                        </div>
                        <div class="admin-status-row">
                            <span>لاگ‌های Laravel</span>
                            <strong class="ltr-data" dir="ltr">{{ $fmt($storage['logs']) }}</strong>
                        </div>
                    </div>
                </section>
            </div>

            <section class="admin-panel space-y-3">
                <div class="admin-panel__head">
                    <h3 class="admin-panel__title">سیاست نگه‌داشت داده</h3>
                </div>
                <form method="POST" action="{{ route('admin.settings.privacy.update') }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <x-input-label value="عمر لاگ ممیزی (روز)" />
                            <x-text-input name="activity_log_days" type="number" min="30" max="3650" class="mt-1 block w-full" :value="old('activity_log_days', $privacy['activity_log_days'])" required />
                        </div>
                        <div>
                            <x-input-label value="عمر کش QR (روز)" />
                            <x-text-input name="qr_cache_days" type="number" min="1" max="365" class="mt-1 block w-full" :value="old('qr_cache_days', $privacy['qr_cache_days'])" required />
                        </div>
                    </div>
                    <div>
                        <x-input-label value="متن سیاست برای مطب" />
                        <textarea name="retention_note" rows="3" class="field-input mt-1 w-full">{{ old('retention_note', $privacy['retention_note']) }}</textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-primary !py-2 !px-4 !text-sm">ذخیره سیاست</button>
                    </div>
                </form>
            </section>

            <section class="admin-panel">
                <div class="admin-panel__head">
                    <h3 class="admin-panel__title">انطباق و قابلیت‌ها</h3>
                    <a href="{{ route('admin.settings.features') }}" class="admin-panel__link">ویرایش</a>
                </div>
                <div class="admin-status-list">
                    <div class="admin-status-row">
                        <span>آخرین خروجی ممیزی</span>
                        <strong>
                            @if ($lastAuditExport)
                                {{ jalali($lastAuditExport->created_at, 'Y/m/d H:i') }}
                                <a href="{{ route('activity-logs.index', ['action' => 'exported']) }}" class="admin-panel__link ms-2">مشاهده</a>
                            @else
                                —
                            @endif
                        </strong>
                    </div>
                    @foreach (($features ?? []) as $key => $on)
                        <div class="admin-status-row">
                            <span>{{ \App\Support\FeatureFlags::definitions()[$key]['label'] ?? $key }}</span>
                            <strong class="{{ $on ? 'is-on' : 'is-off' }}">{{ $on ? 'روشن' : 'خاموش' }}</strong>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="admin-panel !p-0 overflow-hidden">
                <div class="admin-panel__head px-4 pt-4">
                    <h3 class="admin-panel__title">بک‌آپ‌های اخیر</h3>
                    <span class="admin-panel__hint !mt-0">دانلود برای همه مدیران · بازیابی فقط مدیر کل</span>
                </div>
                @if ($backups->isEmpty())
                    <p class="admin-empty">هنوز بک‌آپی نیست.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>فایل</th>
                                    <th>حجم</th>
                                    <th>زمان</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($backups as $backup)
                                    <tr>
                                        <td class="ltr-data" dir="ltr">{{ $backup['name'] }}</td>
                                        <td class="ltr-data" dir="ltr">{{ $fmt($backup['size']) }}</td>
                                        <td class="ltr-data" dir="ltr">{{ jalali(\Carbon\Carbon::createFromTimestamp($backup['at']), 'Y/m/d H:i') }}</td>
                                        <td>
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ route('admin.settings.backup.download', $backup['name']) }}" class="btn-secondary !px-2 !py-1 !text-[10px]">دانلود</a>
                                                @if($isAdmin)
                                                    <form method="POST" action="{{ route('admin.settings.backup.restore') }}" onsubmit="return confirm('دیتابیس فعلی جایگزین می‌شود. قبل از بازیابی یک بک‌آپ تازه گرفته می‌شود. مطمئنید؟')">
                                                        @csrf
                                                        <input type="hidden" name="file" value="{{ $backup['name'] }}">
                                                        <input type="hidden" name="confirm" value="RESTORE">
                                                        <button type="submit" class="btn-secondary !px-2 !py-1 !text-[10px] text-red-600">بازیابی</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
