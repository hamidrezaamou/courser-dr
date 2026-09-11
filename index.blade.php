<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="page-title">صفحه پرینت‌ها</h2>
                <p class="page-desc">برگه‌های عمومی همیشه دیده می‌شوند؛ با انتخاب بیمارستان، فرم‌های مخصوص همان مرکز هم اضافه می‌شود.</p>
            </div>
            <a href="{{ route('reports.index') }}" class="btn-ghost hidden sm:inline-flex">گزارشات</a>
        </div>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-xl space-y-5 px-4 sm:px-6">
            <form method="GET" action="{{ route('prints.index') }}" class="panel space-y-4 p-5" id="prints-filter-form">
                <div>
                    <x-input-label value="بیمارستان" />
                    <select name="hospital_id" class="field-input mt-1" onchange="this.form.submit()">
                        <option value="">فقط برگه‌های عمومی</option>
                        @foreach ($hospitals as $hospital)
                            <option value="{{ $hospital->id }}" @selected((string) ($hospitalId ?? '') === (string) $hospital->id)>
                                {{ $hospital->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-[11px] font-semibold" style="color: var(--muted);">
                        با انتخاب بیمارستان، برگه‌های عمومی + مخصوص همان بیمارستان لیست می‌شود.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <x-input-label value="شناسه نوبت عمل (برای چاپ با مشخصات بیمار)" />
                        <input type="number" name="id" min="1" class="field-input mt-1 font-mono" dir="ltr"
                               value="{{ $lookupId }}" placeholder="مثلاً 12">
                    </div>
                    <button type="submit" class="btn-primary !py-2.5 shrink-0">نمایش</button>
                </div>
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
            @else
                <div class="panel space-y-4 p-5">
                    <div class="rounded-2xl border p-4" style="border-color: var(--line); background: var(--panel-soft);">
                        <div class="text-sm font-extrabold" style="color: var(--ink);">
                            @if($selectedHospital)
                                برگه‌های قابل چاپ · {{ $selectedHospital->name }}
                            @else
                                برگه‌های عمومی
                            @endif
                        </div>
                        <p class="mt-1 text-xs font-semibold" style="color: var(--muted);">
                            برای چاپ با نام بیمار، شناسه نوبت عمل را بالا وارد کنید یا از گزارش‌ها → ابزار → پرینت وارد شوید (آنجا بیمارستان نوبت خودکار انتخاب می‌شود).
                        </p>
                    </div>

                    <div class="grid gap-2">
                        @forelse ($types as $type)
                            <div class="flex items-center justify-between gap-3 rounded-2xl border px-4 py-3"
                                 style="border-color: var(--line); background: var(--panel);">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="text-2xl">{{ $type['icon'] }}</span>
                                    <div class="min-w-0 text-right">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-bold" style="color: var(--ink);">{{ $type['title'] }}</span>
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-extrabold"
                                                  style="{{ ($type['scope'] ?? '') === 'عمومی'
                                                    ? 'background: color-mix(in srgb, var(--hue-slate) 14%, var(--panel)); color: var(--muted);'
                                                    : 'background: var(--brand-soft); color: var(--brand-dark);' }}">
                                                {{ $type['scope'] ?? 'عمومی' }}
                                            </span>
                                        </div>
                                        <div class="text-xs" style="color: var(--muted);">{{ $type['desc'] }}</div>
                                    </div>
                                </div>
                                <span class="shrink-0 rounded-full px-3 py-1 text-[11px] font-bold"
                                      style="background: var(--panel-soft); color: var(--muted);">نیاز به نوبت</span>
                            </div>
                        @empty
                            <div class="rounded-2xl border px-4 py-5 text-center text-sm" style="border-color: var(--line); color: var(--muted);">
                                @if($hospitalId)
                                    برای این بیمارستان هنوز فرم مخصوصی تنظیم نشده و برگه عمومی هم نیست.
                                @else
                                    هنوز برگه عمومی‌ای تعریف نشده است.
                                @endif
                                در «برند و چاپ» فرم‌ها را بسازید و بیمارستان را تیک بزنید.
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
