<x-app-layout>
    <x-slot name="header">
        <h2 class="page-title">پرتال بیمار</h2>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-modules-dock :module-section="$moduleSection" />
            <x-flash />

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="panel p-4 text-center">
                    <div class="text-2xl font-bold" style="color: var(--brand-dark);">{{ $portalPatients }}</div>
                    <div class="mt-1 text-xs" style="color: var(--muted);">بیمار با حساب کاربری</div>
                </div>
                <div class="panel p-4 text-center">
                    <div class="text-2xl font-bold" style="color: var(--ink);">{{ $totalPatients }}</div>
                    <div class="mt-1 text-xs" style="color: var(--muted);">کل پرونده‌ها</div>
                </div>
                <div class="panel p-4 text-center">
                    <div class="text-2xl font-bold" style="color: var(--warn);">{{ max(0, $totalPatients - $portalPatients) }}</div>
                    <div class="mt-1 text-xs" style="color: var(--muted);">بدون دسترسی پرتال</div>
                </div>
            </div>

            <div class="panel space-y-3 p-4 text-sm leading-7">
                <h3 class="text-sm font-bold" style="color: var(--ink);">راهنمای فعال‌سازی</h3>
                <p style="color: var(--muted);">
                    برای دسترسی بیمار به پرونده، کاربری با نقش «بیمار» و همان کد ملی پرونده بسازید.
                    بیمار پس از ورود، منوی «پرونده من» را می‌بیند.
                </p>
                @if(auth()->user()?->canManageSettings())
                    <a href="{{ route('admin.users.create') }}" class="btn-primary inline-flex !py-2">ساخت کاربر بیمار</a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
