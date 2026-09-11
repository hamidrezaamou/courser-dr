<div class="space-y-4">
        <div class="tg-side__card text-center">
        @php $canEditPhoto = $isStaff && auth()->user()?->canEditPatient() && ! str_starts_with((string) $patient->national_code, 'DEL-'); @endphp
        <div class="mx-auto w-fit">
            <x-profile-photo
                :url="$patient->photoUrl()"
                :initial="$patient->photoInitial()"
                :upload-url="route('patients.photo.update', $patient)"
                :delete-url="route('patients.photo.destroy', $patient)"
                :can-edit="$canEditPhoto"
                title="عکس پروفایل بیمار"
            />
        </div>
        <h2 class="mt-2 text-base font-bold" style="color: var(--ink);">{{ $patient->name }}</h2>
        <p class="mt-0.5 text-[11px]" style="color: var(--muted);">پرونده الکترونیک</p>

        @if($isStaff)
            <div class="tg-attach-row tg-attach-row--4 mt-4">
                <button type="button" class="tg-attach-btn" @click="openMedia('photos')">
                    <span class="tg-attach-btn__icon is-photo" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="5" width="16" height="14" rx="2"/><circle cx="9" cy="10" r="1.5"/><path stroke-linecap="round" d="M7 17l3.5-4 2.5 2.5L15 13l4 4"/></svg>
                    </span>
                    <span class="tg-attach-btn__label">عکس بیمار</span>
                    <span class="tg-attach-btn__count">{{ $docCount }}</span>
                </button>
                <button type="button" class="tg-attach-btn" @click="openMedia('drawings')">
                    <span class="tg-attach-btn__icon is-draw" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.042.806a1.125 1.125 0 01-1.37-1.37l.806-2.042a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>
                    </span>
                    <span class="tg-attach-btn__label">وایت‌برد</span>
                    <span class="tg-attach-btn__count">{{ $drawingCount }}</span>
                </button>
                <button type="button" class="tg-attach-btn" @click="openMedia('exams')">
                    <span class="tg-attach-btn__icon is-exam" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </span>
                    <span class="tg-attach-btn__label">معاینه</span>
                    <span class="tg-attach-btn__count">{{ $examVisits->count() }}</span>
                </button>
                @if($canClinical)
                <button type="button" class="tg-attach-btn" @click="openPanel('prescription')">
                    <span class="tg-attach-btn__icon is-rx" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
                    </span>
                    <span class="tg-attach-btn__label">دارو</span>
                    <span class="tg-attach-btn__count">{{ $rxCount }}</span>
                </button>
                @endif
            </div>

            <div class="mt-3 flex flex-wrap justify-center gap-2">
                <x-row-toolbox
                    :name="$patient->name"
                    :mobile="$patient->mobile"
                    :mobile-secondary="$patient->mobile_secondary"
                    :national-code="$patient->national_code"
                    meta="پرونده بیمار"
                    :patient-url="route('patients.show', $patient)"
                    sheet-mode="patient"
                    :patient-id="$patient->id"
                    :can-clinical="$canClinical"
                    trigger-label="ابزار"
                    class="!min-h-9 !px-3 !text-xs"
                />
                @if(auth()->user()?->canEditPatient() && ! str_starts_with((string) $patient->national_code, 'DEL-'))
                    <a href="{{ route('patients.edit', ['patient' => $patient, 'from_file' => 1]) }}" class="btn-secondary !min-h-9 !px-3 !text-xs">ویرایش بیمار</a>
                @endif
                <a href="{{ route('surgery-appointments.create', $patient) }}" class="btn-secondary !min-h-9 !px-3 !text-xs" onclick="window.open(this.href, 'surgery-register'); return false;">نوبت عمل</a>
                <a href="{{ route('appointments.create', $patient) }}" class="btn-secondary !min-h-9 !px-3 !text-xs">ویزیت</a>
            </div>

            <x-answer-launcher
                :mobile="$patient->mobile"
                :patient-name="$patient->name"
                :patient-id="$patient->id"
                :national-code="$patient->national_code"
                :mobile-secondary="$patient->mobile_secondary"
                label="ارسال پاسخ آماده"
                class="mt-2"
            />
        @endif
    </div>

    <div class="tg-side__card space-y-3 text-sm">
        <div class="flex items-center justify-between gap-2">
            <span style="color: var(--muted);">کد ملی</span>
            <span class="font-mono font-semibold" style="color: var(--ink);" dir="ltr">{{ $patient->national_code }}</span>
        </div>
        <div class="flex items-center justify-between gap-2">
            <span style="color: var(--muted);">موبایل</span>
            <span class="font-mono font-semibold" style="color: var(--ink);" dir="ltr">{{ $patient->mobile }}</span>
        </div>
        @if ($patient->mobile_secondary)
            <div class="flex items-center justify-between gap-2">
                <span style="color: var(--muted);">موبایل دوم</span>
                <span class="font-mono font-semibold" style="color: var(--ink);" dir="ltr">{{ $patient->mobile_secondary }}</span>
            </div>
        @endif
        @if ($patient->age)
            <div class="flex items-center justify-between gap-2">
                <span style="color: var(--muted);">سن</span>
                <span class="font-semibold" style="color: var(--ink);">{{ $patient->age }} سال</span>
            </div>
        @endif
        @if($isStaff && \App\Support\FeatureFlags::enabled('features.accounting'))
            @php $patientBalance = \App\Support\PatientFinance::balance($patient); @endphp
            <div class="flex items-center justify-between gap-2">
                <span style="color: var(--muted);">مانده حساب</span>
                <span class="font-mono font-bold {{ $patientBalance > 0 ? 'text-red-600' : '' }}" style="{{ $patientBalance <= 0 ? 'color: var(--ink);' : '' }}" dir="ltr">
                    {{ number_format($patientBalance) }}
                </span>
            </div>
        @endif
        @if(!$isStaff && \App\Support\FeatureFlags::enabled('features.patient_portal'))
            <div class="rounded-lg border px-2.5 py-2 text-[11px]" style="border-color: var(--line); background: var(--panel-soft); color: var(--muted);">
                پرتال بیمار فعال است — نوبت‌ها و مدارک قابل مشاهده در همین پرونده.
            </div>
        @endif
    </div>

    @if($isStaff && \App\Support\FeatureFlags::enabled('features.eye_chart') && $examVisits->isNotEmpty())
    <div class="tg-side__card">
        <a href="{{ route('modules.eye-chart.index', ['patient' => $patient->id]) }}" class="btn-secondary w-full !py-2 !text-xs text-center">
            جدول بینایی این بیمار
        </a>
    </div>
    @endif

    @if($isStaff && auth()->user()?->canManageSettings() && ! str_starts_with((string) $patient->national_code, 'DEL-'))
    <div class="tg-side__card space-y-2">
        <h3 class="text-sm font-bold text-red-700">حذف امن پرونده</h3>
        <p class="text-[11px] leading-5" style="color: var(--muted);">هویت ناشناس می‌شود، فایل‌های بالینی پاک می‌شوند، نوبت‌ها برای آمار می‌مانند. غیرقابل بازگشت.</p>
        <form method="POST" action="{{ route('patients.secure-erase', $patient) }}" onsubmit="return confirm('حذف امن قطعی است. ادامه؟')">
            @csrf
            @method('DELETE')
            <input type="text" name="confirm_name" class="field-input w-full !text-xs" placeholder="نام بیمار را برای تأیید بنویسید" required>
            <button type="submit" class="btn-secondary mt-2 w-full !py-1.5 !text-xs text-red-700">اجرای حذف امن</button>
        </form>
    </div>
    @endif

    @include('patients.partials.consent-panel', compact('patient', 'isStaff', 'canClinical'))

    @php
        $bookingHistory = collect()
            ->merge($patient->appointments->map(fn ($a) => ['type' => 'visit', 'item' => $a, 'date' => optional($a->scheduled_date)->timestamp ?? 0]))
            ->merge($patient->surgeryAppointments->map(fn ($s) => ['type' => 'surgery', 'item' => $s, 'date' => optional($s->scheduled_date)->timestamp ?? 0]))
            ->sortByDesc('date')
            ->take(12)
            ->values();
    @endphp

    <div class="tg-side__card space-y-2" x-data="{ openId: null }">
        <h3 class="text-sm font-bold" style="color: var(--ink);">تاریخچه نوبت‌ها</h3>
        <div class="tg-side__history space-y-2">
        @forelse ($bookingHistory as $row)
            @php
                $item = $row['item'];
                $rowKey = $row['type'].'-'.$item->id;
            @endphp
            <div class="rounded-xl border text-xs overflow-hidden" style="border-color: var(--line); background: var(--panel-soft);">
                <button type="button" class="flex w-full items-center justify-between gap-2 px-3 py-2 text-right"
                        @click="openId = openId === @js($rowKey) ? null : @js($rowKey)">
                    <span class="flex min-w-0 flex-1 items-center gap-2">
                        <span class="font-bold shrink-0" style="{{ $row['type'] === 'surgery' ? 'color:#c2410c' : 'color:var(--brand-dark)' }}">
                            {{ $row['type'] === 'surgery' ? 'عمل' : 'ویزیت' }}
                        </span>
                        <span class="truncate" style="color: var(--muted);">
                            {{ $row['type'] === 'surgery' ? ($item->surgery_type ?: 'عمل') : ($item->visit_type ?: 'ویزیت') }}
                        </span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 shrink-0">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold"
                              style="background:var(--panel);color:var(--ink)">{{ $item->statusLabel() }}</span>
                        <svg class="h-3.5 w-3.5 transition" style="color:var(--muted)" :class="openId === @js($rowKey) && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </span>
                </button>
                <div x-show="openId === @js($rowKey)" x-cloak class="border-t px-3 py-2 space-y-1" style="border-color: var(--line); color: var(--muted);">
                    <div class="flex items-center justify-between gap-2">
                        <span>تاریخ</span>
                        <span class="font-mono" dir="ltr">{{ jalali($item->scheduled_date, 'Y/m/d') }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span>ساعت/نوبت</span>
                        <span>{{ $item->scheduled_time ? \App\Support\SlotLabel::display((string) $item->scheduled_time) : '—' }}</span>
                    </div>
                    @if($row['type'] === 'surgery' && $item->hospital)
                        <div class="flex items-center justify-between gap-2">
                            <span>بیمارستان</span>
                            <span>{{ $item->hospital->name }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-xs" style="color: var(--muted);">نوبتی ثبت نشده است.</p>
        @endforelse
        </div>
    </div>

    @if($isStaff && \App\Support\FeatureFlags::enabled('features.followup_reminders'))
    <div class="tg-side__card space-y-2">
        <div class="flex items-center justify-between gap-2">
            <h3 class="text-sm font-bold" style="color: var(--ink);">مراجعه‌های بعدی</h3>
            <span class="text-[11px] font-bold" style="color: var(--muted);">{{ $pendingFollowUps->count() }}</span>
        </div>
        <div class="space-y-2 max-h-48 overflow-auto">
            @forelse($pendingFollowUps->take(12) as $rem)
                <div class="rounded-lg border px-2.5 py-2 text-[11px]" style="border-color: var(--line); background: var(--panel-soft);">
                    <div class="font-bold" style="color: var(--ink);" dir="ltr">{{ jalali($rem->due_date, 'Y/m/d') }}</div>
                    <div style="color: var(--muted);">یادآوری: {{ jalali($rem->remind_at, 'Y/m/d') }}</div>
                    <form method="POST" action="{{ route('followups.destroy', [$patient, $rem]) }}" class="mt-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-[10px] font-bold text-red-600">لغو</button>
                    </form>
                </div>
            @empty
                <p class="text-xs" style="color: var(--muted);">موردی ثبت نشده.</p>
            @endforelse
        </div>
    </div>
    @endif

    @if($isStaff && \App\Support\PatientFollowUps::isAvailable())
    @php $careFollowUps = $careFollowUps ?? collect(); @endphp
    <div class="tg-side__card space-y-2">
        <div class="flex items-center justify-between gap-2">
            <h3 class="text-sm font-bold" style="color: var(--ink);">پیگیری‌ها</h3>
            <span class="text-[11px] font-bold" style="color: var(--muted);">{{ $careFollowUps->filter(fn ($i) => $i->isOpen())->count() }} باز</span>
        </div>
        <div class="space-y-2 max-h-56 overflow-auto">
            @forelse($careFollowUps->take(12) as $fu)
                <div class="rounded-lg border px-2.5 py-2 text-[11px]" style="border-color: var(--line); background: var(--panel-soft);">
                    <div class="font-bold" style="color: var(--ink);">{{ $fu->title }}</div>
                    <div dir="ltr">{{ $fu->dueJalali('Y/m/d') }}</div>
                    <div style="color: var(--muted);">{{ $fu->statusLabel() }} · {{ $fu->kindLabel() }}</div>
                </div>
            @empty
                <p class="text-xs" style="color: var(--muted);">پیگیری ثبت نشده.</p>
            @endforelse
        </div>
        <button type="button" class="btn-secondary w-full !py-1.5 !text-[11px]" @click="openPanel('followup')">مشاهده و ثبت</button>
    </div>
    @endif
</div>
