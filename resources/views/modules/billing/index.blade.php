<x-app-layout>
    <x-slot name="header">
        <h2 class="page-title">صورتحساب و بیمه</h2>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-modules-dock :module-section="$moduleSection" />
            <x-flash />

            <div class="grid gap-4 lg:grid-cols-2">
                <form method="POST" action="{{ route('modules.billing.tariffs.store') }}" class="panel space-y-3 p-4">
                    @csrf
                    <h3 class="text-sm font-bold" style="color: var(--ink);">تعرفه جدید</h3>
                    <input type="text" name="name" class="field-input w-full" placeholder="نام خدمت" required maxlength="160">
                    <div class="grid grid-cols-2 gap-2">
                        <select name="kind" class="field-input w-full" required>
                            <option value="visit">ویزیت</option>
                            <option value="surgery">عمل</option>
                        </select>
                        <input type="number" name="amount" class="field-input w-full" placeholder="مبلغ (تومان)" min="0" required dir="ltr">
                    </div>
                    <div>
                        <label class="text-xs font-bold" style="color: var(--muted);">پوشش بیمه (%)</label>
                        <input type="number" name="insurance_coverage" class="field-input mt-1 w-full" min="0" max="100" value="0" required dir="ltr">
                    </div>
                    <button type="submit" class="btn-primary w-full !py-2">ذخیره تعرفه</button>
                </form>

                <form method="POST" action="{{ route('modules.billing.records.store') }}" class="panel space-y-3 p-4">
                    @csrf
                    <h3 class="text-sm font-bold" style="color: var(--ink);">ثبت صورتحساب</h3>
                    <select name="billable_type" class="field-input w-full" required>
                        <option value="visit">ویزیت</option>
                        <option value="surgery">عمل</option>
                    </select>
                    <select name="billable_id" class="field-input w-full" required>
                        <option value="">انتخاب نوبت…</option>
                        <optgroup label="ویزیت‌ها">
                            @foreach($recentVisits as $a)
                                <option value="{{ $a->id }}">#{{ $a->id }} · {{ $a->patient_name }} · {{ jalali($a->scheduled_date, 'Y/m/d') }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="عمل‌ها">
                            @foreach($recentSurgeries as $s)
                                <option value="{{ $s->id }}">#{{ $s->id }} · {{ $s->patient_name }} · {{ jalali($s->scheduled_date, 'Y/m/d') }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                    <select name="service_tariff_id" class="field-input w-full" required>
                        <option value="">تعرفه…</option>
                        @foreach($tariffs as $t)
                            <option value="{{ $t->id }}">{{ $t->name }} — {{ number_format($t->amount) }}</option>
                        @endforeach
                    </select>
                    <select name="settlement_status" class="field-input w-full" required>
                        <option value="open">باز</option>
                        <option value="partial">جزئی</option>
                        <option value="paid">تسویه</option>
                    </select>
                    <button type="submit" class="btn-primary w-full !py-2">ثبت صورتحساب</button>
                </form>
            </div>

            <div class="panel p-4">
                <h3 class="mb-3 text-sm font-bold" style="color: var(--ink);">تعرفه‌های فعال</h3>
                <div class="mb-4 flex flex-wrap gap-2">
                    @forelse($tariffs as $t)
                        <span class="rounded-full border px-3 py-1 text-xs font-bold" style="border-color: var(--line);">
                            {{ $t->name }} · {{ number_format($t->amount) }} · بیمه {{ $t->insurance_coverage }}%
                        </span>
                    @empty
                        <p class="text-xs" style="color: var(--muted);">تعرفه‌ای ثبت نشده.</p>
                    @endforelse
                </div>

                <h3 class="mb-2 text-sm font-bold" style="color: var(--ink);">آخرین صورتحساب‌ها</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr style="color: var(--muted);">
                                <th class="py-2 text-right">بیمار</th>
                                <th class="py-2 text-right">تعرفه</th>
                                <th class="py-2 text-right">سهم بیمار</th>
                                <th class="py-2 text-right">وضعیت</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $r)
                                <tr class="border-t" style="border-color: var(--line);">
                                    <td class="py-2">{{ $r->patient?->name }}</td>
                                    <td class="py-2">{{ $r->tariff?->name ?? '—' }}</td>
                                    <td class="py-2 font-mono" dir="ltr">{{ number_format($r->patient_share) }}</td>
                                    <td class="py-2">{{ match($r->settlement_status) { 'paid' => 'تسویه', 'partial' => 'جزئی', default => 'باز' } }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center" style="color: var(--muted);">صورتحسابی ثبت نشده.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
