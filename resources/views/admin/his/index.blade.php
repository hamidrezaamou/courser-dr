<x-app-layout body-class="is-admin">
    <x-slot name="header">
        <div class="admin-header">
            <div>
                <p class="admin-header__eyebrow">مدیریت کل سایت</p>
                <h2 class="admin-header__title">همگام‌سازی HIS</h2>
            </div>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-admin-dock :admin-section="'his'" />
            <x-flash />

            <div class="admin-panel space-y-2">
                <h3 class="text-sm font-extrabold">وضعیت اتصال</h3>

                @if(! $enabled)
                    <p class="text-xs font-bold text-red-600">
                        همگام‌سازی خاموش است. تا وقتی <span class="ltr-data" dir="ltr">HIS_SYNC_ENABLED=true</span> نباشد، عامل نمی‌تواند داده بفرستد.
                    </p>
                @elseif(! $keyConfigured)
                    <p class="text-xs font-bold text-red-600">
                        کلید عامل تنظیم نشده است؛ <span class="ltr-data" dir="ltr">HIS_AGENT_KEY</span> را در فایل env پر کنید.
                    </p>
                @else
                    <p class="text-xs text-emerald-700">همگام‌سازی روشن است و کلید عامل تنظیم شده.</p>
                @endif

                <p class="admin-panel__hint">
                    امضای درخواست‌ها:
                    @if($signed)
                        <span class="font-bold text-emerald-700">فعال</span>
                    @else
                        غیرفعال — برای امنیت بیشتر <span class="ltr-data" dir="ltr">HIS_AGENT_SECRET</span> را هم تنظیم کنید.
                    @endif
                </p>
            </div>

            <div class="admin-panel space-y-3">
                <h3 class="text-sm font-extrabold">آخرین وضعیت هر بخش</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs">
                        <thead class="text-slate-500">
                            <tr>
                                <th class="p-2">بخش</th>
                                <th class="p-2">آخرین موفقیت</th>
                                <th class="p-2">وضعیت</th>
                                <th class="p-2">ردیف واردشده</th>
                                <th class="p-2">از HIS / دستی</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr class="border-t border-slate-100">
                                    <td class="p-2 font-bold">{{ $row['label'] }}</td>
                                    <td class="p-2 ltr-data" dir="ltr">
                                        {{ $row['state']?->last_success_at?->diffForHumans() ?? '—' }}
                                    </td>
                                    <td class="p-2">
                                        @if($row['stale'])
                                            <span class="font-bold text-red-600">بی‌خبر</span>
                                        @elseif($row['state']?->last_status === 'partial')
                                            <span class="font-bold text-amber-600">ناقص</span>
                                        @else
                                            <span class="font-bold text-emerald-700">سالم</span>
                                        @endif
                                    </td>
                                    <td class="p-2 ltr-data" dir="ltr">{{ number_format($row['imported']) }}</td>
                                    <td class="p-2 ltr-data" dir="ltr">
                                        {{ number_format($counts[$row['resource']]['his'] ?? 0) }}
                                        /
                                        {{ number_format($counts[$row['resource']]['manual'] ?? 0) }}
                                    </td>
                                </tr>
                                @if($row['state']?->last_message)
                                    <tr>
                                        <td colspan="5" class="px-2 pb-2 text-[11px] text-amber-700">
                                            {{ $row['state']->last_message }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="admin-panel__hint">
                    اگر بیش از {{ $staleMinutes }} دقیقه خبری نرسد، بخش «بی‌خبر» علامت می‌خورد؛ یعنی عامل روی سرور مطب متوقف شده است.
                </p>
            </div>

            <div class="admin-panel space-y-3">
                <h3 class="text-sm font-extrabold">آخرین بسته‌های دریافتی</h3>

                @if($logs->isEmpty())
                    <p class="admin-panel__hint">هنوز هیچ بسته‌ای دریافت نشده است.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-xs">
                            <thead class="text-slate-500">
                                <tr>
                                    <th class="p-2">زمان</th>
                                    <th class="p-2">بخش</th>
                                    <th class="p-2">دریافت</th>
                                    <th class="p-2">جدید</th>
                                    <th class="p-2">به‌روز</th>
                                    <th class="p-2">خطا</th>
                                    <th class="p-2">مدت</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                    <tr class="border-t border-slate-100">
                                        <td class="p-2 ltr-data" dir="ltr">{{ $log->created_at?->format('m-d H:i') }}</td>
                                        <td class="p-2">{{ \App\Support\His\HisResources::label($log->resource) }}</td>
                                        <td class="p-2 ltr-data" dir="ltr">{{ $log->received }}</td>
                                        <td class="p-2 ltr-data" dir="ltr">{{ $log->created }}</td>
                                        <td class="p-2 ltr-data" dir="ltr">{{ $log->updated }}</td>
                                        <td class="p-2 ltr-data {{ $log->failed > 0 ? 'font-bold text-red-600' : '' }}" dir="ltr">{{ $log->failed }}</td>
                                        <td class="p-2 ltr-data" dir="ltr">{{ $log->duration_ms }}ms</td>
                                    </tr>
                                    @if($log->failed > 0 && $log->errors)
                                        <tr>
                                            <td colspan="7" class="px-2 pb-2">
                                                <ul class="space-y-1 text-[11px] text-red-700">
                                                    @foreach(array_slice($log->errors, 0, 5) as $error)
                                                        <li class="ltr-data" dir="ltr">
                                                            #{{ $error['his_id'] ?? $error['row'] ?? '?' }} — {{ $error['error'] ?? '' }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
