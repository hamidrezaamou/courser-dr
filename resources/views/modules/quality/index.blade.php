<x-app-layout>
    <x-slot name="header">
        <h2 class="page-title">داشبورد کیفیت</h2>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-modules-dock :module-section="$moduleSection" />
            <x-flash />

            <p class="text-sm" style="color: var(--muted);">آمار ماه {{ $monthLabel }}</p>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="panel p-4 text-center">
                    <div class="text-2xl font-bold" style="color: var(--ink);">{{ $stats['total'] }}</div>
                    <div class="mt-1 text-xs" style="color: var(--muted);">کل نوبت‌ها</div>
                </div>
                <div class="panel p-4 text-center">
                    <div class="text-2xl font-bold" style="color: #047857;">{{ $stats['done_rate'] }}%</div>
                    <div class="mt-1 text-xs" style="color: var(--muted);">انجام‌شده ({{ $stats['done'] }})</div>
                </div>
                <div class="panel p-4 text-center">
                    <div class="text-2xl font-bold" style="color: var(--warn);">{{ $stats['no_show_rate'] }}%</div>
                    <div class="mt-1 text-xs" style="color: var(--muted);">عدم حضور ({{ $stats['no_show'] }})</div>
                </div>
                <div class="panel p-4 text-center">
                    <div class="text-2xl font-bold" style="color: var(--ink);">{{ $stats['today_total'] }}</div>
                    <div class="mt-1 text-xs" style="color: var(--muted);">نوبت امروز</div>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="panel p-4">
                    <h3 class="text-sm font-bold" style="color: var(--ink);">لغوها</h3>
                    <div class="mt-2 text-3xl font-bold" style="color: var(--muted);">{{ $stats['cancelled'] }}</div>
                </div>
                @if($stats['checklist_total'] > 0 || \App\Support\SurgeryChecklist::isAvailable())
                <div class="panel p-4">
                    <h3 class="text-sm font-bold" style="color: var(--ink);">چک‌لیست عمل</h3>
                    <div class="mt-2 text-3xl font-bold" style="color: var(--brand-dark);">{{ $stats['checklist_rate'] }}%</div>
                    <p class="mt-1 text-xs" style="color: var(--muted);">{{ $stats['checklist_saved'] }} از {{ $stats['checklist_total'] }} ذخیره شده</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
