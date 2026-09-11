@php
    use App\Models\Patient;
    $patients = $patients ?? Patient::query()->orderBy('name')->limit(100)->get(['id', 'name', 'mobile']);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="page-title">لیست انتظار</h2>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-modules-dock :module-section="$moduleSection" />
            <x-flash />

            <div class="grid gap-4 lg:grid-cols-3">
                <form method="POST" action="{{ route('modules.waiting.store') }}" class="panel space-y-3 p-4 lg:col-span-1">
                    @csrf
                    <h3 class="text-sm font-bold" style="color: var(--ink);">افزودن به صف</h3>
                    <select name="patient_id" class="field-input w-full">
                        <option value="">بیمار موجود (اختیاری)</option>
                        @foreach($patients as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="patient_name" class="field-input w-full" placeholder="نام" required maxlength="160">
                    <input type="text" name="mobile" class="field-input w-full" placeholder="موبایل" required maxlength="20" dir="ltr">
                    <input type="text" name="national_code" class="field-input w-full" placeholder="کد ملی" maxlength="20" dir="ltr">
                    <select name="kind" class="field-input w-full" required>
                        <option value="visit">ویزیت</option>
                        <option value="surgery">عمل</option>
                    </select>
                    <input type="text" name="preferred_date" class="field-input w-full" placeholder="تاریخ ترجیحی (شمسی)" dir="ltr">
                    <input type="number" name="priority" class="field-input w-full" placeholder="اولویت (1=بالا)" min="1" max="999" value="50">
                    <textarea name="notes" rows="2" class="field-input w-full" placeholder="یادداشت"></textarea>
                    <button type="submit" class="btn-primary w-full !py-2">ثبت در صف</button>
                </form>

                <div class="panel p-4 lg:col-span-2">
                    <h3 class="mb-3 text-sm font-bold" style="color: var(--ink);">صف فعال</h3>
                    <div class="space-y-2">
                        @forelse($entries as $entry)
                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border px-3 py-2 text-xs" style="border-color: var(--line);">
                                <div>
                                    <div class="font-bold">{{ $entry->patient_name }}</div>
                                    <div style="color: var(--muted);">{{ $entry->mobile }} · {{ $entry->kind === 'surgery' ? 'عمل' : 'ویزیت' }} · اولویت {{ $entry->priority }}</div>
                                    @if($entry->preferred_date)
                                        <div dir="ltr">{{ jalali($entry->preferred_date, 'Y/m/d') }}</div>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('modules.waiting.status', $entry) }}" class="flex flex-wrap gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="contacted">
                                    <button type="submit" class="btn-secondary !px-2 !py-1 !text-[10px]">تماس شد</button>
                                </form>
                                <form method="POST" action="{{ route('modules.waiting.convert', $entry) }}">
                                    @csrf
                                    <button type="submit" class="btn-primary !px-2 !py-1 !text-[10px]">ساخت نوبت</button>
                                </form>
                                <form method="POST" action="{{ route('modules.waiting.status', $entry) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" class="btn-secondary !px-2 !py-1 !text-[10px]">لغو</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-xs" style="color: var(--muted);">صف خالی است.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
