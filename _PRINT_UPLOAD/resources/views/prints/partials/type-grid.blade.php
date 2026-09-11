<div class="panel space-y-4 p-5">
    <div class="rounded-2xl border p-4 text-center" style="border-color: var(--line); background: var(--panel-soft);">
        <div class="text-lg font-extrabold" style="color: var(--ink);">{{ $surgery->patient_name }}</div>
        <div class="mt-1 text-xs" style="color: var(--muted);">
            #{{ $surgery->id }}
            · {{ $surgery->hospital?->name ?: 'بدون بیمارستان' }}
            · {{ jalali($surgery->scheduled_date, 'Y/m/d') }}
            @if($surgery->scheduled_time)
                · {{ \App\Support\SlotLabel::display((string) $surgery->scheduled_time) }}
            @endif
            · {{ $surgery->surgery_type }}
            @if($surgery->surgerySubtype)
                · {{ $surgery->surgerySubtype->name }}
            @elseif($surgery->surgery_subtype_id === null)
                · عمومی
            @endif
        </div>
        <div class="mt-2 flex flex-wrap items-center justify-center gap-2">
            <span class="font-mono text-xs" dir="ltr" style="color: var(--muted);">{{ $surgery->mobile }}</span>
        </div>
    </div>

    @if(!empty($hospitals) && $hospitals->isNotEmpty())
        <form method="POST" action="{{ route('surgery-appointments.prints.hospital', $surgery) }}" class="rounded-2xl border p-3 space-y-2" style="border-color: var(--line);">
            @csrf
            <label class="block text-xs font-extrabold" style="color: var(--ink);">بیمارستان این نوبت (فیلتر خودکار برگه‌ها)</label>
            <div class="flex flex-wrap gap-2">
                <select name="hospital_id" class="field-input flex-1 !min-h-[2.2rem] !py-1.5 text-sm" required onchange="this.form.submit()">
                    @if(! $surgery->hospital_id)
                        <option value="" disabled selected>بیمارستان را انتخاب کنید…</option>
                    @endif
                    @foreach ($hospitals as $hospital)
                        <option value="{{ $hospital->id }}" @selected((string) $surgery->hospital_id === (string) $hospital->id)>{{ $hospital->name }}</option>
                    @endforeach
                </select>
                <noscript>
                    <button type="submit" class="btn-primary !text-xs !px-3 !py-1.5 shrink-0">اعمال</button>
                </noscript>
            </div>
            <p class="text-[11px]" style="color: var(--muted);">
                برگه‌های <strong>عمومی</strong> به‌علاوه فرم‌های <strong>مخصوص همین بیمارستان</strong> خودکار لیست می‌شوند.
            </p>
        </form>
    @elseif(! $surgery->hospital_id)
        <div class="rounded-2xl border px-4 py-3 text-xs font-semibold" style="border-color: color-mix(in srgb, #b45309 35%, var(--line)); background: color-mix(in srgb, #b45309 10%, var(--panel)); color: #92400e;">
            برای این نوبت بیمارستان ثبت نشده؛ فعلاً فقط برگه‌های عمومی دیده می‌شود.
        </div>
    @endif

    <div class="grid gap-2">
        @forelse ($types as $type)
            <a href="{{ route('surgery-appointments.print', [$surgery, $type['key']]) }}"
               target="_blank"
               @if(!empty($checklistWarning))
                   onclick="return confirm(@js(($checklistWarning ?? '').' ادامه چاپ؟'))"
               @endif
               class="flex items-center justify-between gap-3 rounded-2xl border px-4 py-3 transition hover:border-[var(--brand)]"
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
                      style="background: var(--brand-soft); color: var(--brand-dark);">پرینت</span>
            </a>
        @empty
            <div class="rounded-2xl border px-4 py-5 text-center text-sm" style="border-color: var(--line); color: var(--muted);">
                برای این زیرگروه و بیمارستان فرمی تنظیم نشده است.
                در «برند و چاپ» زیرگروه و بیمارستان هر فرم را تیک بزنید.
            </div>
        @endforelse
    </div>

    @if(count($types) > 0)
        <div class="mt-4">
            <a href="{{ route('surgery-appointments.prints.all', $surgery) }}" target="_blank"
               class="flex items-center justify-center gap-2 rounded-2xl border px-4 py-3 text-sm font-extrabold transition hover:border-[var(--brand)]"
               style="border-color: var(--brand); background: color-mix(in srgb, var(--brand) 8%, var(--panel)); color: var(--brand-dark);">
                🖨️ چاپ همه برگه‌های این عمل
            </a>
        </div>
    @endif
</div>
