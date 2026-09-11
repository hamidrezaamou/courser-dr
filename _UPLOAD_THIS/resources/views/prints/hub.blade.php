<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="page-kicker">چاپ برگه‌ها</p>
                <h2 class="page-title">برگه‌های چاپ · {{ $surgery->patient_name }}</h2>
                <p class="page-desc">
                    شناسه #{{ $surgery->id }}
                    · {{ jalali($surgery->scheduled_date, 'Y/m/d') }}
                    @if($surgery->hospital) · {{ $surgery->hospital->name }} @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if(count($types) > 0)
                    <a href="{{ route('surgery-appointments.prints.all', $surgery) }}" target="_blank" class="btn-primary !text-xs !px-3 !py-1.5">🖨️ چاپ همه برگه‌ها</a>
                @endif
                <a href="{{ route('patients.show', $surgery->patient_id) }}" class="btn-ghost">پرونده</a>
                <a href="{{ route('reports.index', ['kind' => 'surgery']) }}" class="btn-ghost">گزارشات</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-xl px-4 sm:px-6">
            @if(!empty($checklistWarning))
                <div class="mb-4 rounded-2xl border px-4 py-3 text-sm font-semibold"
                     style="border-color: color-mix(in srgb, #b45309 35%, var(--line)); background: color-mix(in srgb, #b45309 10%, var(--panel)); color: #92400e;">
                    {{ $checklistWarning }}
                    <div class="mt-1 text-xs font-medium" style="color: var(--muted);">می‌توانید چاپ را ادامه دهید؛ این فقط هشدار نرم است.</div>
                </div>
            @endif
            @include('prints.partials.type-grid', [
                'surgery' => $surgery,
                'types' => $types,
                'checklistWarning' => $checklistWarning ?? null,
                'hospitals' => $hospitals ?? collect(),
            ])
        </div>
    </div>
</x-app-layout>
