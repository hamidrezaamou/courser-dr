<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="page-title">ماژول‌های پیشرفته</h2>
            @if(auth()->user()?->canManageSettings())
                <a href="{{ route('admin.settings.features') }}" class="btn-secondary !px-3 !py-1.5 !text-xs">تنظیم قابلیت‌ها</a>
            @endif
        </div>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-modules-dock module-section="hub" />
            <x-flash />

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($modules as $key => $meta)
                    <a href="{{ route($meta['route']) }}" class="panel p-4 transition hover:shadow-md">
                        <h3 class="text-sm font-bold" style="color: var(--ink);">{{ $meta['label'] }}</h3>
                        <p class="mt-1 text-xs leading-6" style="color: var(--muted);">
                            {{ \App\Support\FeatureFlags::definitions()[$meta['feature']]['hint'] ?? '' }}
                        </p>
                        <span class="mt-3 inline-flex text-[11px] font-bold" style="color: var(--brand-dark);">ورود ←</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
