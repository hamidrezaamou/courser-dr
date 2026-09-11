<x-app-layout>
    <x-slot name="header">
        <h2 class="page-title">جدول بینایی</h2>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-modules-dock :module-section="$moduleSection" />
            <x-flash />

            <div class="panel p-4">
                <form method="GET" class="flex flex-wrap items-end gap-2">
                    <div>
                        <label class="text-xs font-bold" style="color: var(--muted);">فیلتر بیمار</label>
                        <select name="patient" class="field-input mt-1 min-w-[12rem]">
                            <option value="">همه</option>
                            @foreach($patients as $p)
                                <option value="{{ $p->id }}" @selected($patientFilter == $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-secondary !py-2">اعمال</button>
                    @if($patientFilter)
                        <a href="{{ route('modules.eye-chart.index') }}" class="btn-ghost !py-2 !text-xs">حذف فیلتر</a>
                    @endif
                </form>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="panel p-4 lg:col-span-1">
                    <h3 class="mb-2 text-sm font-bold" style="color: var(--ink);">بیماران با VA</h3>
                    <div class="space-y-1 max-h-96 overflow-auto">
                        @foreach($patients as $p)
                            <a href="{{ route('modules.eye-chart.index', ['patient' => $p->id]) }}"
                               class="flex items-center justify-between rounded-lg px-2 py-1.5 text-xs {{ $patientFilter == $p->id ? 'font-bold' : '' }}"
                               style="color: var(--ink); background: {{ $patientFilter == $p->id ? 'var(--panel-soft)' : 'transparent' }};">
                                <span>{{ $p->name }}</span>
                                <span style="color: var(--muted);">{{ $p->visits_count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="panel p-4 lg:col-span-2">
                    <h3 class="mb-3 text-sm font-bold" style="color: var(--ink);">روند بینایی</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr style="color: var(--muted);">
                                    <th class="py-2 text-right">بیمار</th>
                                    <th class="py-2 text-right">تاریخ</th>
                                    <th class="py-2 text-right">VA راست</th>
                                    <th class="py-2 text-right">VA چپ</th>
                                    <th class="py-2 text-right">چشم</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($visits as $v)
                                    <tr class="border-t" style="border-color: var(--line);">
                                        <td class="py-2">
                                            <a href="{{ route('patients.show', $v->patient_id) }}" class="font-bold hover:underline">{{ $v->patient?->name }}</a>
                                        </td>
                                        <td class="py-2" dir="ltr">{{ jalali($v->created_at, 'Y/m/d') }}</td>
                                        <td class="py-2 font-mono" dir="ltr">{{ $v->va_right ?: '—' }}</td>
                                        <td class="py-2 font-mono" dir="ltr">{{ $v->va_left ?: '—' }}</td>
                                        <td class="py-2">{{ $v->eye_side ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-4 text-center" style="color: var(--muted);">رکورد VA ثبت نشده.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
