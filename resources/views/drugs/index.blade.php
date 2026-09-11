<x-app-layout>
    <x-slot name="header">
        <div class="page-header--compact flex items-center justify-between gap-3">
            <h2 class="page-title">فهرست داروها</h2>
            <a href="{{ route('dashboard') }}" class="btn-ghost hidden sm:inline-flex">داشبورد</a>
        </div>
    </x-slot>

    <div class="settings-page-body">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            <x-settings-dock :settings-section="'drugs'" />

            <x-flash />

            <div class="grid gap-5 lg:grid-cols-1 xl:grid-cols-[400px_minmax(0,1fr)]">
                <section class="panel p-5 sm:p-6">
                    <h3 class="mb-4 text-sm font-bold" style="color: var(--ink);">افزودن دارو</h3>
                    <form method="POST" action="{{ route('drugs.store') }}" class="space-y-3">
                        @csrf
                        <div>
                            <x-input-label for="name" value="نام دارو" />
                            <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                        </div>
                        <div>
                            <x-input-label for="generic_name" value="نام ژنریک (اختیاری)" />
                            <x-text-input id="generic_name" name="generic_name" class="mt-1 block w-full" :value="old('generic_name')" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label for="dosage_form" value="شکل دارویی" />
                                <select id="dosage_form" name="dosage_form" class="field-input mt-1">
                                    <option value="">—</option>
                                    @foreach (['قرص', 'کپسول', 'قطره', 'پماد', 'آمپول', 'شربت', 'اسپری'] as $form)
                                        <option value="{{ $form }}" @selected(old('dosage_form') === $form)>{{ $form }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="default_dosage" value="دوز پیش‌فرض" />
                                <x-text-input id="default_dosage" name="default_dosage" class="mt-1 block w-full" :value="old('default_dosage')" placeholder="مثلاً ۱ قطره" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label for="default_frequency" value="دفعات مصرف" />
                                <x-text-input id="default_frequency" name="default_frequency" class="mt-1 block w-full" :value="old('default_frequency')" placeholder="هر ۸ ساعت" />
                            </div>
                            <div>
                                <x-input-label for="default_duration" value="مدت" />
                                <x-text-input id="default_duration" name="default_duration" class="mt-1 block w-full" :value="old('default_duration')" placeholder="۱۰ روز" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="default_instructions" value="دستور مصرف پیش‌فرض" />
                            <textarea id="default_instructions" name="default_instructions" rows="2" class="field-input">{{ old('default_instructions') }}</textarea>
                        </div>
                        <button type="submit" class="btn-primary w-full !py-3">ذخیره دارو</button>
                    </form>
                </section>

                <section class="panel overflow-hidden p-0">
                    <div class="flex items-center justify-between border-b px-5 py-4" style="border-color: var(--line);">
                        <h3 class="text-sm font-bold" style="color: var(--ink);">داروهای ثبت‌شده</h3>
                        <span class="rounded-full px-3 py-1 text-xs font-bold" style="background: var(--brand-soft); color: var(--brand-dark);">{{ $drugs->count() }}</span>
                    </div>
                    @if ($drugs->isEmpty())
                        <div class="pp-empty">هنوز دارویی ثبت نشده.</div>
                    @else
                        <div class="divide-y" style="border-color: var(--line);">
                            @foreach ($drugs as $drug)
                                <details class="group px-5 py-3">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-2">
                                        <div>
                                            <strong style="color: var(--ink);">{{ $drug->name }}</strong>
                                            @if($drug->dosage_form)
                                                <span class="mr-2 text-xs" style="color: var(--muted);">{{ $drug->dosage_form }}</span>
                                            @endif
                                        </div>
                                        <span class="text-xs" style="color: var(--muted);">{{ $drug->is_active ? 'فعال' : 'غیرفعال' }}</span>
                                    </summary>
                                    <form method="POST" action="{{ route('drugs.update', $drug) }}" class="mt-3 space-y-2 border-t pt-3" style="border-color: var(--line);">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="name" value="{{ $drug->name }}" class="field-input !py-2 text-sm" required>
                                        <input type="text" name="generic_name" value="{{ $drug->generic_name }}" class="field-input !py-2 text-sm" placeholder="ژنریک">
                                        <div class="grid grid-cols-2 gap-2">
                                            <input type="text" name="dosage_form" value="{{ $drug->dosage_form }}" class="field-input !py-2 text-sm" placeholder="شکل">
                                            <input type="text" name="default_dosage" value="{{ $drug->default_dosage }}" class="field-input !py-2 text-sm" placeholder="دوز">
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <input type="text" name="default_frequency" value="{{ $drug->default_frequency }}" class="field-input !py-2 text-sm" placeholder="دفعات">
                                            <input type="text" name="default_duration" value="{{ $drug->default_duration }}" class="field-input !py-2 text-sm" placeholder="مدت">
                                        </div>
                                        <label class="flex items-center gap-2 text-xs">
                                            <input type="checkbox" name="is_active" value="1" @checked($drug->is_active)> فعال
                                        </label>
                                        <div class="flex gap-2">
                                            <button type="submit" class="btn-secondary !py-2 !text-xs">ذخیره</button>
                                        </div>
                                    </form>
                                    <form method="POST" action="{{ route('drugs.destroy', $drug) }}" class="mt-2" onsubmit="return confirm('حذف این دارو؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-bold text-red-600">حذف</button>
                                    </form>
                                </details>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>

</x-app-layout>
