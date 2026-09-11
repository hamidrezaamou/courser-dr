<x-app-layout>
    <x-slot name="header">
        <h2 class="page-title">نسخه چاپی ساختاریافته</h2>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-modules-dock :module-section="$moduleSection" />
            <x-flash />

            <div class="panel p-4">
                <div class="space-y-2">
                    @forelse($prescriptions as $rx)
                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border px-3 py-2 text-sm" style="border-color: var(--line);">
                            <div>
                                <div class="font-bold">{{ $rx->patient?->name }}</div>
                                <div class="text-xs" style="color: var(--muted);">{{ $rx->items->count() }} قلم · {{ $rx->creator?->name ?? '—' }}</div>
                                <div class="text-xs" dir="ltr">{{ jalali($rx->created_at, 'Y/m/d H:i') }}</div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('modules.prescriptions.print', $rx) }}" target="_blank" class="btn-primary !px-3 !py-1.5 !text-xs">چاپ</a>
                                @if($rx->patient_id)
                                    <a href="{{ route('patients.show', $rx->patient_id) }}" class="btn-secondary !px-3 !py-1.5 !text-xs">پرونده</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm" style="color: var(--muted);">نسخه‌ای ثبت نشده.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
