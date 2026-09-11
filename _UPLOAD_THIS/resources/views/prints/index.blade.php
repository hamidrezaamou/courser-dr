<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="page-title">صفحه پرینت‌ها</h2>
            <a href="{{ route('reports.index') }}" class="btn-ghost hidden sm:inline-flex">گزارشات</a>
        </div>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-xl space-y-5 px-4 sm:px-6">
            <form method="GET" action="{{ route('prints.index') }}" class="panel flex flex-col gap-3 p-5 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <x-input-label value="شناسه نوبت عمل" />
                    <input type="number" name="id" min="1" class="field-input mt-1 font-mono" dir="ltr"
                           value="{{ $lookupId }}" placeholder="مثلاً 12" required>
                </div>
                <button type="submit" class="btn-primary !py-2.5">نمایش برگه‌ها</button>
            </form>

            @if ($lookupId && ! $surgery)
                <x-flash type="error">نوبت عمل با شناسه {{ $lookupId }} پیدا نشد.</x-flash>
            @endif

            @if ($surgery)
                @include('prints.partials.type-grid', [
                    'surgery' => $surgery,
                    'types' => $types,
                    'hospitals' => $hospitals ?? collect(),
                ])
            @endif
        </div>
    </div>
</x-app-layout>
