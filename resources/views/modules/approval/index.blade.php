<x-app-layout>
    <x-slot name="header">
        <h2 class="page-title">تأیید نوبت آنلاین</h2>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-modules-dock :module-section="$moduleSection" />
            <x-flash />

            <div class="panel p-4">
                <h3 class="mb-3 text-sm font-bold" style="color: var(--ink);">در انتظار تأیید ({{ $pending->count() }})</h3>
                <div class="space-y-2">
                    @forelse($pending as $a)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border px-3 py-3 text-sm" style="border-color: var(--line); background: var(--panel-soft);">
                            <div>
                                <div class="font-bold">{{ $a->patient_name }}</div>
                                <div class="text-xs" style="color: var(--muted);">{{ $a->mobile }} · {{ $a->national_code }}</div>
                                <div class="mt-1 text-xs">
                                    <span dir="ltr">{{ jalali($a->scheduled_date, 'Y/m/d') }}</span>
                                    · {{ \App\Support\SlotLabel::display((string) $a->scheduled_time) }}
                                    · {{ $a->visit_type }}
                                </div>
                                @if($a->notes)
                                    <p class="mt-1 text-xs" style="color: var(--muted);">{{ $a->notes }}</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('modules.approval.approve', $a) }}">
                                    @csrf
                                    <button type="submit" class="btn-primary !px-3 !py-1.5 !text-xs">تأیید</button>
                                </form>
                                <form method="POST" action="{{ route('modules.approval.reject', $a) }}" onsubmit="return confirm('رد شود؟')">
                                    @csrf
                                    <button type="submit" class="btn-secondary !px-3 !py-1.5 !text-xs">رد</button>
                                </form>
                                @if($a->patient_id)
                                    <a href="{{ route('patients.show', $a->patient_id) }}" class="btn-secondary !px-3 !py-1.5 !text-xs">پرونده</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm" style="color: var(--muted);">درخواستی در صف تأیید نیست.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
