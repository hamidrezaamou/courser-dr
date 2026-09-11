<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="page-title">لاگ تغییرات</h2>
            <div class="flex flex-wrap items-center gap-2">
                @if ($canExport)
                    <a href="{{ route('activity-logs.export', request()->query()) }}" class="btn-secondary btn-primary--compact">
                        خروجی CSV
                    </a>
                @endif
                <a href="{{ route('admin.index') }}" class="btn-ghost hidden sm:inline-flex">مدیریت کل سایت</a>
            </div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            <x-admin-dock :admin-section="'audit'" />
            <x-flash />

            <form method="GET" action="{{ route('activity-logs.index') }}" class="panel grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <div>
                    <x-input-label value="نوع رویداد" />
                    <select name="action" class="field-input mt-1">
                        <option value="">همه</option>
                        <option value="created" @selected($filters['action'] === 'created')>ثبت</option>
                        <option value="updated" @selected($filters['action'] === 'updated')>ویرایش</option>
                        <option value="status_changed" @selected($filters['action'] === 'status_changed')>تغییر وضعیت</option>
                        <option value="deleted" @selected($filters['action'] === 'deleted')>حذف</option>
                        <option value="viewed" @selected($filters['action'] === 'viewed')>مشاهده پرونده</option>
                        <option value="login" @selected($filters['action'] === 'login')>ورود</option>
                        <option value="logout" @selected($filters['action'] === 'logout')>خروج</option>
                        <option value="secure_erased" @selected($filters['action'] === 'secure_erased')>حذف امن</option>
                        <option value="reminder_sent" @selected($filters['action'] === 'reminder_sent')>یادآوری</option>
                        <option value="exported" @selected($filters['action'] === 'exported')>خروجی‌گیری</option>
                    </select>
                </div>
                <div>
                    <x-input-label value="کاربر" />
                    <select name="user_id" class="field-input mt-1">
                        <option value="">همه</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected($filters['user_id'] === (string) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label value="موضوع" />
                    <select name="subject_type" class="field-input mt-1">
                        <option value="">همه</option>
                        @foreach ($subjectTypes as $type => $label)
                            <option value="{{ $type }}" @selected($filters['subject_type'] === $type)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-jalali-date-input name="from" :value="$filters['from']" label="از تاریخ" :allow-past="true" :allow-friday="true" />
                </div>
                <div>
                    <x-jalali-date-input name="to" :value="$filters['to']" label="تا تاریخ" :allow-past="true" :allow-friday="true" />
                </div>
                <div>
                    <x-input-label value="جستجو" />
                    <x-text-input name="q" class="mt-1 block w-full" :value="$filters['q']" placeholder="IP، JSON، …" />
                </div>
                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-3 xl:col-span-6">
                    <button type="submit" class="btn-primary !py-2.5">فیلتر</button>
                    <a href="{{ route('activity-logs.index') }}" class="btn-ghost !py-2.5">پاک کردن</a>
                </div>
            </form>

            <section class="panel overflow-hidden p-0">
                @if ($logs->isEmpty())
                    <div class="pp-empty">لاگی با این فیلتر پیدا نشد.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-right text-xs" style="color: var(--muted); background: var(--panel-soft);">
                                    <th class="px-4 py-3 font-semibold">زمان</th>
                                    <th class="px-4 py-3 font-semibold">کاربر</th>
                                    <th class="px-4 py-3 font-semibold">رویداد</th>
                                    <th class="px-4 py-3 font-semibold">موضوع</th>
                                    <th class="px-4 py-3 font-semibold">IP</th>
                                    <th class="px-4 py-3 font-semibold">جزئیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($logs as $log)
                                    <tr class="border-t align-top" style="border-color: var(--line);" x-data="{ open: false }">
                                        <td class="px-4 py-3 font-mono text-xs ltr-data">{{ jalali($log->created_at, 'Y/m/d H:i') }}</td>
                                        <td class="px-4 py-3">{{ $log->user?->name ?? 'سیستم' }}</td>
                                        <td class="px-4 py-3 font-bold">{{ $log->actionLabel() }}</td>
                                        <td class="px-4 py-3 text-xs" style="color: var(--muted);">
                                            {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                        </td>
                                        <td class="px-4 py-3 font-mono text-xs ltr-data" dir="ltr">{{ $log->ip_address ?? '—' }}</td>
                                        <td class="max-w-[14rem] px-3 py-3 text-xs sm:max-w-xs sm:px-4" style="color: var(--muted);">
                                            @if($log->old_values || $log->new_values)
                                                <button type="button" class="btn-ghost !px-2 !py-1 !text-[11px]" @click="open = !open">
                                                    <span x-text="open ? 'بستن' : 'نمایش'"></span>
                                                </button>
                                                <div x-show="open" x-cloak class="mt-2 space-y-1">
                                                    @if($log->old_values)
                                                        <div class="break-all">قبل: <span dir="ltr">{{ json_encode($log->old_values, JSON_UNESCAPED_UNICODE) }}</span></div>
                                                    @endif
                                                    @if($log->new_values)
                                                        <div class="break-all">بعد: <span dir="ltr">{{ json_encode($log->new_values, JSON_UNESCAPED_UNICODE) }}</span></div>
                                                    @endif
                                                </div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t px-4 py-3" style="border-color: var(--line);">
                        {{ $logs->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
