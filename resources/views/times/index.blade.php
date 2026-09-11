@php
    $monthNames = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="page-header--compact flex items-center justify-between gap-3">
            <h2 class="page-title">تایم‌های کاری</h2>
            <a href="{{ route('dashboard') }}" class="btn-ghost hidden sm:inline-flex">داشبورد</a>
        </div>
    </x-slot>

    <div class="settings-page-body" x-data="timeScheduler(
        @js($kind),
        @js($selectedHospitalId),
        @js($selectedTypeId),
        @js($selectedSubtypeId),
        @js($hospitals->map(fn ($h) => ['id' => $h->id, 'name' => $h->name])->values()),
        @js($surgeryTypes->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'subtypes' => $t->subtypes->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
        ])->values()),
        @js($surgerySchedulesPayload),
        @js($monthNames),
        @js($smsPresets),
        @js($smsDefaultVisit),
        @js($smsDefaultSurgery),
        @js($programGroups ?? [])
    )">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            <x-settings-dock :settings-section="'times'" />
            @if(auth()->user()->canManageSettings())
                <div class="mb-3">
                    <a href="{{ route('admin.index') }}" class="admin-panel__link">← بازگشت به مدیریت کل سایت</a>
                </div>
            @endif

            <x-flash />

            {{-- Sliding segmented control --}}
            <div class="kind-switch panel !p-1.5">
                <div class="kind-switch__track" dir="ltr">
                    <span
                        class="kind-switch__thumb"
                        :class="selectedKind === 'surgery' ? 'is-surgery' : 'is-visit'"
                        :style="selectedKind === 'surgery' ? 'transform: translate3d(100%,0,0)' : 'transform: translate3d(0,0,0)'"
                    ></span>
                    <button type="button" class="kind-switch__btn" :class="selectedKind === 'visit' && 'is-active'" @click="setKind('visit')">
                        تایم‌های ویزیت
                    </button>
                    <button type="button" class="kind-switch__btn" :class="selectedKind === 'surgery' && 'is-active'" @click="setKind('surgery')">
                        تایم‌های عمل
                    </button>
                </div>
            </div>

            <div
                x-show="selectedKind === 'surgery'"
                x-cloak
                class="panel p-4 sm:p-5"
            >
                <p class="mb-3 text-xs font-bold" style="color: var(--muted);">محدوده برنامه عمل</p>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-2">
                            <label class="text-xs font-bold" style="color:var(--ink)">بیمارستان</label>
                            <button type="button" class="scope-plus" @click="showAddHospital = !showAddHospital" title="افزودن">+</button>
                        </div>
                        <div x-show="showAddHospital" x-cloak class="mb-2 flex gap-2">
                            <input type="text" class="field-input !py-2 text-sm" placeholder="نام جدید" x-model="newHospitalName" @keydown.enter.prevent="addHospital()">
                            <button type="button" class="btn-secondary !py-2 !px-3 shrink-0" @click="addHospital()">ثبت</button>
                        </div>
                        <select class="field-input" :value="selectedHospitalId" @change="setHospital($event.target.value)">
                            <option value="">انتخاب...</option>
                            <template x-for="h in hospitals" :key="h.id">
                                <option :value="h.id" :selected="String(selectedHospitalId) === String(h.id)" x-text="h.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-2">
                            <label class="text-xs font-bold" style="color:var(--ink)">نوع عمل</label>
                            <button type="button" class="scope-plus" @click="showAddType = !showAddType" title="افزودن">+</button>
                        </div>
                        <div x-show="showAddType" x-cloak class="mb-2 flex gap-2">
                            <input type="text" class="field-input !py-2 text-sm" placeholder="مثلاً آب مروارید" x-model="newTypeName" @keydown.enter.prevent="addType()">
                            <button type="button" class="btn-secondary !py-2 !px-3 shrink-0" @click="addType()">ثبت</button>
                        </div>
                        <select class="field-input" :value="selectedTypeId ?? ''" @change="setType($event.target.value)">
                            <option value="">همه انواع</option>
                            <template x-for="t in surgeryTypes" :key="t.id">
                                <option :value="t.id" :selected="String(selectedTypeId) === String(t.id)" x-text="t.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-2">
                            <label class="text-xs font-bold" style="color:var(--ink)">زیرگروه‌ها (چندتایی)</label>
                            <button type="button" class="scope-plus" @click="showAddSubtype = !showAddSubtype" :disabled="!selectedTypeId" title="افزودن">+</button>
                        </div>
                        <div x-show="showAddSubtype" x-cloak class="mb-2 flex gap-2">
                            <input type="text" class="field-input !py-2 text-sm" placeholder="زیرگروه" x-model="newSubtypeName" @keydown.enter.prevent="addSubtype()">
                            <button type="button" class="btn-secondary !py-2 !px-3 shrink-0" @click="addSubtype()">ثبت</button>
                        </div>
                        <div class="subtype-multi" :class="!selectedTypeId && 'is-disabled'">
                            <label class="subtype-chip" :class="isSaveSubtypeSelected('all') && 'is-on'">
                                <input type="checkbox" class="sr-only" :checked="isSaveSubtypeSelected('all')" @change="toggleSaveSubtype('all')">
                                همه (فیلتر)
                            </label>
                            <label class="subtype-chip" :class="isSaveSubtypeSelected('general') && 'is-on'">
                                <input type="checkbox" class="sr-only" :checked="isSaveSubtypeSelected('general')" @change="toggleSaveSubtype('general')" :disabled="!selectedTypeId">
                                عمومی
                            </label>
                            <template x-for="s in currentSubtypes" :key="s.id">
                                <label class="subtype-chip" :class="isSaveSubtypeSelected(String(s.id)) && 'is-on'">
                                    <input type="checkbox" class="sr-only" :checked="isSaveSubtypeSelected(String(s.id))" @change="toggleSaveSubtype(String(s.id))" :disabled="!selectedTypeId">
                                    <span x-text="s.name"></span>
                                </label>
                            </template>
                        </div>
                        <p class="mt-1.5 text-[11px]" style="color:var(--muted)">
                            برای ثبت روز: زیرگروه‌های انتخاب‌شده (غیر از «همه») همزمان برنامه می‌گیرند.
                            «همه» فقط فیلتر نمایش روزهاست.
                        </p>
                        <p class="mt-2 text-xs leading-6" x-show="matchingProgramGroup()" x-cloak style="color: var(--brand-dark);">
                            این انتخاب در گروه «<span x-text="matchingProgramGroup() && matchingProgramGroup().name"></span>» است.
                            ظرفیت همین روز برای این گروه در این بیمارستان مستقل از گروه‌های دیگر حساب می‌شود.
                            <a href="{{ route('settings.surgery-program-groups') }}" class="underline">ویرایش گروه‌ها</a>
                        </p>
                        <p class="mt-2 text-[11px]" x-show="selectedKind === 'surgery' && !matchingProgramGroup()" x-cloak style="color: var(--muted);">
                            برای ظرفیت مستقل چند نوع عمل، یک
                            <a href="{{ route('settings.surgery-program-groups') }}" class="underline">گروه برنامه</a>
                            بسازید.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
                <section class="panel overflow-hidden p-0">
                    <div class="flex items-center justify-between border-b px-5 py-4" style="border-color: var(--line);">
                        <h3 class="text-sm font-bold" style="color: var(--ink);">
                            روزهای تنظیم‌شده ·
                            <span x-text="selectedKind === 'surgery' ? 'عمل' : 'ویزیت'"></span>
                            <span x-show="selectedKind === 'surgery' && selectedHospitalName" x-cloak class="font-medium" style="color: var(--muted);">
                                · <span x-text="selectedHospitalName"></span>
                                <span x-show="selectedTypeName"> / <span x-text="selectedTypeName"></span></span>
                                <span x-show="scopeSubtypeLabel"> / <span x-text="scopeSubtypeLabel"></span></span>
                            </span>
                        </h3>
                        <span class="tone rounded-full px-3 py-1 text-xs font-bold transition"
                              :class="selectedKind === 'surgery' ? 'tone--surgery' : 'tone--visit'">
                            <span x-show="selectedKind === 'visit'">{{ $visitSchedules->count() }} روز</span>
                            <span x-show="selectedKind === 'surgery'" x-cloak x-text="filteredSurgeryDays.length + ' روز'"></span>
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-3" style="border-color: var(--line); background: color-mix(in srgb, var(--panel-soft) 70%, transparent);">
                        <div class="flex flex-wrap items-center gap-3">
                            <button type="button" class="text-[11px] font-bold" style="color: var(--brand);" @click="selectAllVisibleForDelete()">
                                انتخاب همهٔ روزهای نمایش‌داده‌شده
                            </button>
                            <span x-show="selectedDeleteIds.length" x-cloak class="text-[11px] font-bold" style="color: var(--brand-dark);" x-text="selectedDeleteIds.length + ' روز انتخاب شده'"></span>
                        </div>
                        <div class="flex flex-wrap gap-2" x-show="selectedDeleteIds.length" x-cloak>
                            <button type="button" class="text-[11px] font-bold text-slate-600" @click="selectedDeleteIds = []; bulkSmsText = ''; bulkSmsMixed = false">لغو انتخاب</button>
                            <button type="button" class="text-[11px] font-bold text-red-700" @click="bulkDeleteSelected()">حذف انتخاب‌شده‌ها</button>
                        </div>
                    </div>
                    <div class="border-b px-5 py-3" style="border-color: var(--line); background: color-mix(in srgb, var(--brand) 6%, var(--panel));">
                        <p class="mb-2 text-xs font-extrabold" style="color: var(--ink);">
                            پیامک مشترک روزهای انتخاب‌شده
                        </p>
                        <p class="mb-2 text-[11px]" style="color: var(--muted);" x-show="!selectedDeleteIds.length">
                            روزهای ایجادشده را تیک بزنید، سپس یک متن برای همه آن‌ها ذخیره کنید.
                        </p>
                        <p class="mb-2 text-[11px]" style="color: var(--muted);" x-show="selectedDeleteIds.length" x-cloak>
                            پیامک مشترک برای
                            <span class="font-extrabold" x-text="selectedDeleteIds.length"></span>
                            روز انتخاب‌شده
                        </p>
                        <p class="mb-2 text-[11px]" style="color: var(--muted);" x-show="bulkSmsMixed" x-cloak>
                            متن فعلی این روزها یکسان نیست. با ذخیره، همه همین متن جدید را می‌گیرند.
                        </p>
                        <select class="field-input mb-2 text-sm" :disabled="!selectedDeleteIds.length" @change="applyBulkSmsPreset($event.target.value); $event.target.value = ''">
                            <option value="">انتخاب از پیامک‌های آماده…</option>
                            @foreach ($smsPresets as $preset)
                                <option value="{{ $preset['id'] }}">{{ $preset['label'] }}</option>
                            @endforeach
                        </select>
                        <textarea rows="3" class="field-input text-sm" maxlength="1000" x-model="bulkSmsText" :disabled="!selectedDeleteIds.length" placeholder="متن پیامک را بنویسید و ذخیره کنید…"></textarea>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <button type="button" class="btn-primary !py-2 !px-3 !text-xs" :disabled="!selectedDeleteIds.length" @click="bulkSaveSms()">ذخیره پیامک برای روزهای انتخاب‌شده</button>
                            <span class="text-[11px]" style="color: var(--muted);">متغیرها: {name} {date} {time} {hospital} {slotNumber} {surgeryType}</span>
                        </div>
                    </div>

                    <div class="kind-panels" dir="ltr">
                        <div class="kind-panels__slider" :style="selectedKind === 'surgery' ? 'transform: translate3d(-50%,0,0)' : 'transform: translate3d(0,0,0)'">
                            <div class="kind-panels__pane p-5" dir="rtl">
                                @if ($visitGrouped->isEmpty())
                                    <div class="pp-empty">هنوز روزی برای ویزیت ثبت نشده است.</div>
                                @else
                                    @include('times.partials.day-cards', ['grouped' => $visitGrouped, 'bookedCounts' => $visitBookedCounts, 'monthNames' => $monthNames])
                                @endif
                            </div>
                            <div class="kind-panels__pane p-5" dir="rtl">
                                @if ($hospitals->isEmpty())
                                    <div class="pp-empty">ابتدا یک بیمارستان از منوی «بیمارستان‌ها» اضافه کنید.</div>
                                @else
                                    <template x-if="!filteredSurgeryDays.length">
                                    <div class="pp-empty">هنوز روزی برای این محدوده ثبت نشده است.</div>
                                    </template>
                                    <template x-for="group in filteredSurgeryGrouped" :key="'month-'+group.month">
                                        <div class="times-month"
                                             x-data="{ open: Number(group.month) === currentJalaliMonth }"
                                             :class="{ 'is-open': open }">
                                            <button type="button"
                                                    class="times-month__summary"
                                                    @click="open = !open"
                                                    :aria-expanded="open ? 'true' : 'false'">
                                                <span class="times-month__title">
                                                    <svg class="times-month__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
                                                    </svg>
                                                    <span x-text="monthNames[group.month] || group.month"></span>
                                                </span>
                                                <span class="times-month__count" x-text="group.days.length + ' روز'"></span>
                                            </button>
                                            <div class="times-month__body">
                                                <div class="flex flex-wrap gap-3">
                                                    <template x-for="day in group.days" :key="day.id">
                                                        <div class="min-w-[7.5rem] flex-1 cursor-pointer rounded-2xl border p-3 text-center sm:min-w-[96px] sm:flex-none"
                                                             :class="[
                                                                day.booked >= day.total && day.total > 0 ? 'border-red-200 bg-red-50' : 'border-emerald-200 bg-emerald-50/70',
                                                                isDeleteSelected(day.id) ? 'ring-2 ring-rose-400' : ''
                                                             ]"
                                                             @click="if (!$event.target.closest('input, button, textarea, select, label, a, form, details, summary')) toggleDeleteId(day.id)">
                                                            <label class="mb-1 flex items-center justify-center gap-1 text-[10px] font-bold text-slate-600">
                                                                <input type="checkbox" :checked="isDeleteSelected(day.id)" @change="toggleDeleteId(day.id)">
                                                                انتخاب
                                                            </label>
                                                            <div class="text-[11px]" style="color: var(--muted);" x-text="day.weekday"></div>
                                                            <div class="text-lg font-extrabold" style="color: var(--ink);" x-text="day.day"></div>
                                                            <div class="mt-1 text-[10px] leading-tight" style="color:var(--muted)" x-show="day.type_name">
                                                                <span x-text="day.type_name"></span>
                                                                <span x-text="' · ' + (day.subtype_name || 'عمومی')"></span>
                                                            </div>
                                                            <div class="mt-1 rounded-lg px-1.5 py-0.5 text-[10px] font-bold" style="background:#fff7ed;color:#b45309" x-show="day.program_group_name" x-cloak>
                                                                گروه <span x-text="day.program_group_name"></span>
                                                            </div>
                                                            <div class="mt-1 text-[10px] font-semibold"
                                                                 :class="day.booked >= day.total && day.total > 0 ? 'text-red-700' : 'text-emerald-700'"
                                                                 x-text="(day.booked >= day.total && day.total > 0) ? 'تکمیل' : (day.free + ' خالی از ' + day.total)"></div>
                                                            <form
                                                                x-show="day.program_group_id"
                                                                x-cloak
                                                                method="POST"
                                                                action="{{ route('times.group-day') }}"
                                                                class="mt-2 space-y-1"
                                                                @submit.stop
                                                            >
                                                                @csrf
                                                                <input type="hidden" name="surgery_program_group_id" :value="day.program_group_id">
                                                                <input type="hidden" name="hospital_id" :value="day.hospital_id">
                                                                <input type="hidden" name="date_key" :value="day.date_key">
                                                                <label class="block text-[10px] font-bold" style="color:var(--muted)">ظرفیت گروه</label>
                                                                <div class="flex gap-1">
                                                                    <input type="number" min="1" max="100" name="total_slots" class="field-input !py-1 !text-[11px]" :value="day.program_group_total || day.total">
                                                                    <button type="submit" class="btn-secondary !py-1 !px-2 !text-[10px]">ثبت</button>
                                                                </div>
                                                            </form>
                                                            <div x-show="day.capacity_limits_enabled" x-cloak class="mt-1 text-[10px] font-bold text-amber-700">محدودیت فعال</div>
                                                            <div x-show="day.capacity_limits && day.capacity_limits.length" x-cloak class="mt-1 space-y-0.5 text-right">
                                                                <template x-for="(rule, ridx) in (day.capacity_limits || [])" :key="'dcr-'+ridx">
                                                                    <p class="text-[10px] text-amber-800">
                                                                        <span x-text="subtypeName(rule.subtype_id)"></span>:
                                                                        <span x-text="rule.remove_count"></span> حذف از آخر
                                                                    </p>
                                                                </template>
                                                            </div>
                                                            <div class="mt-1 text-[10px] font-bold" style="color: var(--brand);" x-text="day.times_count + ' تایم'"></div>
                                                            <details class="times-sms-edit mt-1 text-right" @click.stop>
                                                                <summary class="times-sms-edit__toggle">✉️ پیامک</summary>
                                                                <form :action="day.update_sms_url" method="POST" class="times-sms-edit__form" @submit.stop>
                                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                                    <input type="hidden" name="_method" value="PATCH">
                                                                    <textarea name="sms_text" rows="3" class="field-input !text-[11px] !py-1.5" maxlength="1000" :value="day.sms_text || ''"></textarea>
                                                                    <button type="submit" class="times-sms-edit__save">ذخیره پیامک</button>
                                                                </form>
                                                            </details>
                                                            <form method="POST" :action="day.delete_url" class="mt-2" @submit="if (!confirm('حذف این روز؟')) $event.preventDefault()">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-[11px] font-bold text-red-600">حذف</button>
                                                            </form>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                <section class="panel p-5 sm:p-6">
                    <h3 class="mb-1 text-sm font-bold" style="color: var(--ink);">افزودن روز جدید</h3>
                    <p class="mb-4 text-xs" style="color: var(--muted);">
                        در حال ثبت برای
                        <strong x-text="selectedKind === 'surgery' ? 'عمل' : 'ویزیت'"
                                :style="selectedKind === 'surgery' ? 'color:#c2410c' : 'color:var(--brand-dark)'"></strong>
                        <span x-show="selectedKind === 'surgery'" x-cloak>
                            · <strong x-text="selectedHospitalName || '—'" style="color:#c2410c"></strong>
                            · <strong x-text="selectedTypeName || 'نوع را انتخاب کنید'"></strong>
                            · <strong x-text="saveSubtypeLabel" style="color:#0f766e"></strong>
                        </span>
                    </p>

                    <form method="POST" :action="bulkMode ? @js(route('times.bulk-store')) : @js(route('times.store'))" @submit="prepareSubmit($event)">
                        @csrf
                        <input type="hidden" name="kind" :value="selectedKind">
                        <input type="hidden" name="hospital_id" :value="selectedKind === 'surgery' ? selectedHospitalId : ''">
                        <input type="hidden" name="surgery_type_id" :value="selectedKind === 'surgery' ? selectedTypeId : ''">
                        <template x-for="(sid, sidx) in saveSubtypeIdsForForm" :key="'sub-'+sidx">
                            <input type="hidden" :name="'surgery_subtype_ids['+sidx+']'" :value="sid">
                        </template>
                        <input type="hidden" name="surgery_subtype_id" :value="selectedKind === 'surgery' ? (saveSubtypeIdsForForm[0] ?? '') : ''">
                        <template x-if="!bulkMode">
                            <div>
                                <input type="hidden" name="date_key" x-model="dateKey">
                                <input type="hidden" name="year" x-model="year">
                                <input type="hidden" name="month" x-model="month">
                                <input type="hidden" name="day" x-model="day">
                                <input type="hidden" name="weekday" x-model="weekday">
                                <input type="hidden" name="display_text" x-model="displayText">
                            </div>
                        </template>
                        <div x-show="bulkMode" x-cloak>
                            <template x-for="(d, idx) in selectedDays" :key="d.date">
                                <div>
                                    <input type="hidden" :name="'days['+idx+'][date_key]'" :value="d.date">
                                    <input type="hidden" :name="'days['+idx+'][year]'" :value="d.year">
                                    <input type="hidden" :name="'days['+idx+'][month]'" :value="d.month">
                                    <input type="hidden" :name="'days['+idx+'][day]'" :value="d.day">
                                    <input type="hidden" :name="'days['+idx+'][weekday]'" :value="d.weekday">
                                    <input type="hidden" :name="'days['+idx+'][display_text]'" :value="d.display_text">
                                </div>
                            </template>
                        </div>

                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <label class="flex items-center gap-2 text-xs font-bold" style="color:var(--ink)">
                                <input type="checkbox" class="rounded" x-model="bulkMode" @change="onBulkModeChange()">
                                ثبت چند روز با یک تنظیم
                            </label>
                            <span x-show="bulkMode" x-cloak class="text-[11px] font-bold" style="color:var(--brand-dark)" x-text="selectedDays.length + ' روز انتخاب شده'"></span>
                        </div>

                        <div class="mb-4" x-show="!bulkMode" x-cloak>
                            <x-input-label value="تاریخ شمسی" />
                            <input type="text" class="field-input cursor-pointer" placeholder="برای انتخاب کلیک کنید" readonly required
                                   x-model="dateKey" @click.prevent="openCalendar = true" @keydown.enter.prevent="openCalendar = true">
                            <p class="mt-1 text-[11px]" style="color: var(--muted);" x-text="displayText || 'روز حضور را از تقویم انتخاب کنید'"></p>
                            <x-input-error class="mt-2" :messages="$errors->get('date_key')" />
                            <x-input-error class="mt-2" :messages="$errors->get('hospital_id')" />
                            <x-input-error class="mt-2" :messages="$errors->get('surgery_type_id')" />
                            <x-input-error class="mt-2" :messages="$errors->get('surgery_subtype_id')" />
                        </div>

                        <div class="mb-4" x-show="bulkMode" x-cloak>
                            <x-input-label value="انتخاب روزها از تقویم" />
                            <button type="button" class="field-input cursor-pointer text-right" @click.prevent="openCalendar = true">
                                <span x-text="selectedDays.length ? (selectedDays.length + ' روز انتخاب شده — ویرایش') : 'باز کردن تقویم و انتخاب روزها'"></span>
                            </button>
                        </div>

                        <div class="mb-4">
                            <x-input-label value="نوع نوبت‌دهی" />
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <button type="button" class="btn-secondary !justify-center !py-2"
                                        :style="slotMode === 'time' ? 'background:var(--brand-dark);color:#fff;border-color:transparent' : ''"
                                        @click="setSlotMode('time')">ساعت مشخص</button>
                                <button type="button" class="btn-secondary !justify-center !py-2"
                                        :style="slotMode === 'queue' ? 'background:var(--brand-dark);color:#fff;border-color:transparent' : ''"
                                        @click="setSlotMode('queue')">نوبت شماره‌ای</button>
                            </div>
                            <input type="hidden" name="slot_mode" :value="slotMode">
                        </div>

                        <div class="mb-4 grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label for="total_slots" value="ظرفیت" />
                                <input id="total_slots" type="number" name="total_slots" min="1" max="100" class="field-input" x-model="totalSlots" @change="slotMode === 'queue' && syncQueueText()">
                            </div>
                            <div>
                                <x-input-label for="slot_prefix" value="پیشوند" />
                                <input id="slot_prefix" type="text" name="slot_prefix" class="field-input" x-model="slotPrefix">
                            </div>
                        </div>

                        <div class="mb-4 rounded-2xl border p-3 space-y-3" x-show="selectedKind === 'surgery'" x-cloak style="border-color: var(--line); background: #fff7ed;">
                            <label class="flex items-center gap-2 text-xs font-bold cursor-pointer" style="color: var(--ink);">
                                <input type="checkbox" class="rounded" name="capacity_limits_enabled" value="1" x-model="capacityLimitsEnabled" :disabled="!selectedTypeId">
                                محدودیت ظرفیت بر اساس زیرگروه
                            </label>
                            <p class="text-[11px] leading-relaxed" style="color: var(--muted);">
                                با انتخاب زیرگروهِ محدود، به ازای هر ثبت عمل آن زیرگروه، تعداد مشخصی نوبت از <strong>انتهای همان روز</strong> حذف می‌شود.
                            </p>
                            <div x-show="capacityLimitsEnabled" x-cloak class="space-y-2">
                                <div class="grid grid-cols-1 sm:grid-cols-[1fr_110px_auto] gap-2 items-end">
                                    <div>
                                        <label class="text-[11px] font-bold block mb-1" style="color: var(--ink);">زیرگروه</label>
                                        <select class="field-input !py-2 text-sm" x-model="capacityLimitDraftSubtype">
                                            <option value="">انتخاب زیرگروه…</option>
                                            <template x-for="s in currentSubtypes" :key="'cap-'+s.id">
                                                <option :value="String(s.id)" x-text="s.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-[11px] font-bold block mb-1" style="color: var(--ink);">حذف از آخر</label>
                                        <input type="number" min="1" max="99" class="field-input !py-2 text-sm" x-model.number="capacityLimitDraftCount">
                                    </div>
                                    <button type="button" class="btn-secondary !py-2 !px-3 !text-xs" @click="addCapacityRule()">افزودن</button>
                                </div>
                                <template x-if="capacityLimitRules.length">
                                    <ul class="space-y-1.5">
                                        <template x-for="(rule, idx) in capacityLimitRules" :key="'rule-'+rule.subtype_id">
                                            <li class="flex items-center justify-between gap-2 rounded-xl border px-3 py-2 text-xs" style="border-color: var(--line); background: #fff;">
                                                <span>
                                                    <strong x-text="subtypeName(rule.subtype_id)"></strong>
                                                    <span style="color: var(--muted);"> — با هر ثبت، </span>
                                                    <strong x-text="rule.remove_count"></strong>
                                                    <span style="color: var(--muted);"> نوبت از آخر حذف می‌شود</span>
                                                </span>
                                                <button type="button" class="text-red-600 font-bold" @click="removeCapacityRule(idx)">×</button>
                                            </li>
                                        </template>
                                    </ul>
                                </template>
                            </div>
                            <input type="hidden" name="capacity_limits" :value="JSON.stringify(capacityLimitRules)">
                        </div>

                        <div class="mb-3" x-show="slotMode === 'queue'" x-cloak>
                            <x-input-label value="تعداد نوبت (مثلاً ۱ تا ۱۰)" />
                            <input type="number" name="queue_count" min="1" max="99" class="field-input mt-1" x-model="queueCount" @change="syncQueueText()">
                            <p class="mt-1 text-[11px]" style="color:var(--muted)">به‌جای ساعت، نوبت ۱ تا <span x-text="queueCount"></span> ذخیره می‌شود.</p>
                        </div>

                        <div class="mb-3" x-show="slotMode === 'time'" x-cloak>
                            <x-input-label value="ساعت‌های حضور (هر خط یک ساعت)" />
                            <textarea name="times" rows="6" class="field-input font-mono" dir="ltr" x-model="timesText" @input="if (slotMode === 'time') savedTimeText = timesText" :required="slotMode === 'time' && !bulkMode" :disabled="slotMode === 'queue'" placeholder="08:00&#10;08:30&#10;09:00"></textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('times')" />
                        </div>
                        <input type="hidden" name="times" :value="slotMode === 'queue' ? timesText : ''" :disabled="slotMode === 'time'">

                        <div class="mb-4 grid grid-cols-2 gap-2" x-show="slotMode === 'time'" x-cloak>
                            <button type="button" class="btn-secondary !justify-center !px-2 !py-2 !text-xs" @click="useTemplate('08:00\n09:00\n10:00\n11:00\n12:00')">صبح</button>
                            <button type="button" class="btn-secondary !justify-center !px-2 !py-2 !text-xs" @click="useTemplate('14:00\n15:00\n16:00\n17:00\n18:00')">بعدازظهر</button>
                            <button type="button" class="btn-secondary !justify-center !px-2 !py-2 !text-xs" @click="useTemplate('08:00\n08:30\n09:00\n09:30\n10:00\n10:30')">۳۰ دقیقه‌ای</button>
                            <button type="button" class="btn-secondary !justify-center !px-2 !py-2 !text-xs" @click="useTemplate('09:00\n10:00\n11:00\n13:00\n14:00\n15:00')">ساعتی</button>
                        </div>

                        <div class="mb-4 grid grid-cols-2 gap-2" x-show="slotMode === 'queue'" x-cloak>
                            <button type="button" class="btn-secondary !justify-center !px-2 !py-2 !text-xs" @click="queueCount = 5; syncQueueText()">نوبت ۱–۵</button>
                            <button type="button" class="btn-secondary !justify-center !px-2 !py-2 !text-xs" @click="queueCount = 10; syncQueueText()">نوبت ۱–۱۰</button>
                            <button type="button" class="btn-secondary !justify-center !px-2 !py-2 !text-xs" @click="queueCount = 15; syncQueueText()">نوبت ۱–۱۵</button>
                            <button type="button" class="btn-secondary !justify-center !px-2 !py-2 !text-xs" @click="queueCount = 20; syncQueueText()">نوبت ۱–۲۰</button>
                        </div>

                        <div class="mb-4 rounded-2xl border p-3" style="border-color: var(--line); background: var(--panel-soft);">
                            <x-input-label value="✉️ متن پیامک آماده" />
                            <select class="field-input mt-1.5 mb-2 text-sm" @change="applySmsPreset($event.target.value)">
                                <option value="">انتخاب از پیامک‌های آماده…</option>
                                @foreach ($smsPresets as $preset)
                                    <option value="{{ $preset['id'] }}">{{ $preset['label'] }}</option>
                                @endforeach
                            </select>
                            <textarea name="sms_text" rows="3" class="field-input text-sm" x-model="smsText" maxlength="1000" placeholder="متن پیامک این روز…"></textarea>
                            <p class="mt-1.5 text-[11px] leading-relaxed" style="color: var(--muted);">
                                متغیرها:
                                <span class="ltr-data font-mono">{name}</span>
                                <span class="ltr-data font-mono">{date}</span>
                                <span class="ltr-data font-mono">{slotNumber}</span>
                                <span class="ltr-data font-mono">{hospital}</span>
                                <span class="ltr-data font-mono">{time}</span>
                                <span class="ltr-data font-mono">{surgeryType}</span>
                            </p>
                            <div x-show="smsText.trim()" x-cloak class="times-sms-live-preview mt-2">
                                <strong>نمونه پیام:</strong>
                                <p x-text="smsPreview(smsText)"></p>
                            </div>
                            <p class="mt-2 text-[11px] leading-relaxed" style="color: var(--muted);">
                                این متن با همان روز ذخیره می‌شود. بعداً در <strong>گزارش</strong>، <strong>بُرد نوبت</strong> (دکمه ابزار → ارسال پیامک) و <strong>یادآوری خودکار</strong> استفاده می‌شود.
                            </p>
                            <x-input-error class="mt-2" :messages="$errors->get('sms_text')" />
                        </div>

                        <button type="submit" class="btn-primary w-full !py-3 transition"
                                :style="selectedKind === 'surgery'
                                    ? 'background:linear-gradient(145deg,#ea580c,#9a3412)'
                                    : ''">
                            <span x-text="bulkMode ? ('اعمال روی ' + selectedDays.length + ' روز') : (selectedKind === 'surgery' ? 'ذخیره روز برای عمل' : 'ذخیره روز برای ویزیت')"></span>
                        </button>
                    </form>
                </section>
            </div>
        </div>

        <template x-teleport="body">
            <div
                class="booking-calendar-modal"
                :class="{ 'is-open': openCalendar }"
                x-cloak
                @click.self="openCalendar = false"
                @keydown.escape.window="if (openCalendar) openCalendar = false"
            >
                <div class="booking-calendar-content" @click.stop>
                    <div class="booking-calendar-header">
                        <button type="button" @click.prevent="shiftMonth(1)">‹</button>
                        <strong x-text="monthLabel"></strong>
                        <button type="button" @click.prevent="shiftMonth(-1)">›</button>
                    </div>
                    <p x-show="bulkMode" x-cloak class="mb-2 text-center text-xs font-bold" style="color:var(--brand-dark)">
                        چند روز را انتخاب کنید · <span x-text="selectedDays.length"></span> روز
                    </p>
                    <div class="booking-calendar-grid">
                        <template x-for="name in dayNames" :key="name">
                            <div class="booking-day-name" x-text="name"></div>
                        </template>
                        <template x-for="(cell, idx) in calendarCells" :key="idx">
                            <button
                                type="button"
                                class="booking-day"
                                :class="{
                                    'is-disabled': cell.disabled,
                                    'is-today': cell.today,
                                    'is-active': bulkMode ? isDaySelected(cell) : cell.date === dateKey,
                                    'is-bulk-selected': bulkMode && isDaySelected(cell)
                                }"
                                :disabled="cell.disabled || !cell.day"
                                x-text="cell.day || ''"
                                @click.prevent="selectDate(cell)"
                            ></button>
                        </template>
                    </div>
                    <div x-show="bulkMode" x-cloak class="mt-3 flex gap-2">
                        <button type="button" class="btn-primary flex-1 !py-2" @click.prevent="confirmBulkDays()">تأیید روزها</button>
                        <button type="button" class="btn-secondary !py-2" @click.prevent="selectedDays = []">پاک کردن</button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <style>
        .kind-switch { max-width: 420px; margin-inline: auto; width: 100%; }
        .kind-switch__track {
            position: relative;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            border-radius: 999px;
            background: var(--panel-soft);
            padding: 4px;
        }
        .kind-switch__thumb {
            position: absolute;
            top: 4px;
            left: 4px;
            width: calc(50% - 4px);
            height: calc(100% - 8px);
            border-radius: 999px;
            transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1), background 0.28s ease;
            box-shadow: 0 8px 20px -10px rgba(46, 63, 80, 0.55);
            z-index: 0;
            will-change: transform;
        }
        .kind-switch__thumb.is-visit {
            background: linear-gradient(145deg, var(--brand), var(--brand-dark));
        }
        .kind-switch__thumb.is-surgery {
            background: linear-gradient(145deg, #fb923c, #c2410c);
        }
        .kind-switch__btn {
            position: relative;
            z-index: 1;
            border: 0;
            background: transparent;
            padding: 0.75rem 0.5rem;
            min-height: 2.75rem;
            border-radius: 999px;
            font-size: 0.8125rem;
            font-weight: 800;
            color: var(--muted);
            cursor: pointer;
            transition: color 0.25s ease;
        }
        @media (min-width: 480px) {
            .kind-switch__btn {
                padding: 0.85rem 0.75rem;
                font-size: 0.875rem;
            }
        }
        .kind-switch__btn.is-active { color: #fff; }

        .kind-panels {
            overflow: hidden;
            width: 100%;
        }
        .kind-panels__slider {
            display: flex;
            width: 200%;
            transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1);
            will-change: transform;
        }
        .kind-panels__pane {
            width: 50%;
            flex-shrink: 0;
            backface-visibility: hidden;
        }
        .scope-plus {
            width: 1.65rem;
            height: 1.65rem;
            border: 0;
            border-radius: 999px;
            background: var(--brand-soft);
            color: var(--brand-dark);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1;
            cursor: pointer;
        }
        .scope-plus:disabled { opacity: .4; cursor: not-allowed; }
        .booking-day.is-bulk-selected {
            background: color-mix(in srgb, var(--brand) 22%, var(--panel)) !important;
            border-color: var(--brand-dark) !important;
            font-weight: 900;
        }
    </style>

    <script>
        function timeScheduler(initialKind, initialHospitalId, initialTypeId, initialSubtypeId, hospitalsList, typesList, surgeryDaysList, monthNamesList, smsPresetsList, smsDefaultVisitText, smsDefaultSurgeryText, programGroupsList) {
            const MONTH_NAMES = monthNamesList && monthNamesList.length
                ? monthNamesList
                : ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
            const WEEK_DAYS = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const bulkDestroyUrl = @js(route('times.bulk-destroy'));
            const bulkSmsUrl = @js(route('times.bulk-sms'));

            function toJalali(gy, gm, gd) {
                const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
                const gy2 = gm > 2 ? gy + 1 : gy;
                let days = 355666 + (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) + gd + g_d_m[gm - 1];
                let jy = -1595 + (33 * Math.floor(days / 12053));
                days %= 12053;
                jy += 4 * Math.floor(days / 1461);
                days %= 1461;
                if (days > 365) { jy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
                let jm, jd;
                if (days < 186) { jm = 1 + Math.floor(days / 31); jd = 1 + (days % 31); }
                else { jm = 7 + Math.floor((days - 186) / 30); jd = 1 + ((days - 186) % 30); }
                return { y: jy, m: jm, d: jd };
            }

            function isLeap(jy) {
                const breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
                let jp = breaks[0], jump = 0;
                for (let i = 1; i < breaks.length; i++) {
                    const jm = breaks[i];
                    jump = jm - jp;
                    if (jy < jm) break;
                    jp = jm;
                }
                let n = jy - jp;
                if (jump - n < 6) n = n - jump + Math.floor((jump + 4) / 33) * 33;
                let leap = (((n + 1) % 33) - 1) % 4;
                if (leap === -1) leap = 4;
                return leap === 0;
            }

            function daysInMonth(jy, jm) {
                if (jm <= 6) return 31;
                if (jm <= 11) return 30;
                return isLeap(jy) ? 30 : 29;
            }

            function jalaliToGregorian(jy, jm, jd) {
                jy += 1595;
                let days = -355668 + (365 * jy) + Math.floor(jy / 33) * 8 + Math.floor(((jy % 33) + 3) / 4) + jd + (jm < 7 ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
                let gy = 400 * Math.floor(days / 146097);
                days %= 146097;
                if (days > 36524) { gy += 100 * Math.floor(--days / 36524); days %= 36524; if (days >= 365) days++; }
                gy += 4 * Math.floor(days / 1461);
                days %= 1461;
                if (days > 365) { gy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
                let gd = days + 1;
                const sal_a = [0, 31, ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
                let gm = 1;
                for (; gm <= 12 && gd > sal_a[gm]; gm++) gd -= sal_a[gm];
                return { y: gy, m: gm, d: gd };
            }

            function weekdayIndex(jy, jm, jd) {
                const g = jalaliToGregorian(jy, jm, jd);
                return (new Date(g.y, g.m - 1, g.d).getDay() + 1) % 7;
            }

            function pad(n) { return n < 10 ? '0' + n : String(n); }

            const now = new Date();
            const today = toJalali(now.getFullYear(), now.getMonth() + 1, now.getDate());
            const hospitals = hospitalsList || [];
            const surgeryTypes = typesList || [];

            return {
                selectedKind: initialKind || 'visit',
                selectedHospitalId: initialHospitalId || (hospitals[0] ? hospitals[0].id : null),
                selectedTypeId: initialTypeId || null,
                selectedSubtypeId: initialSubtypeId || null,
                selectedSaveSubtypeIds: (initialSubtypeId && initialSubtypeId !== 'general')
                    ? [String(initialSubtypeId)]
                    : (initialSubtypeId === 'general' ? ['general'] : ['all']),
                hospitals,
                surgeryTypes,
                programGroups: Array.isArray(programGroupsList) ? programGroupsList : [],
                surgeryDays: Array.isArray(surgeryDaysList) ? surgeryDaysList : [],
                monthNames: MONTH_NAMES,
                currentJalaliMonth: {{ (int) jalali(now(), 'n') }},
                showAddHospital: false,
                showAddType: false,
                showAddSubtype: false,
                newHospitalName: '',
                newTypeName: '',
                newSubtypeName: '',
                openCalendar: false,
                viewYear: today.y,
                viewMonth: today.m,
                dateKey: '',
                year: '',
                month: '',
                day: '',
                weekday: '',
                displayText: '',
                timesText: "08:00\n08:30\n09:00\n09:30\n10:00\n10:30",
                savedTimeText: "08:00\n08:30\n09:00\n09:30\n10:00\n10:30",
                totalSlots: 10,
                queueCount: 10,
                slotMode: 'time',
                bulkMode: false,
                selectedDays: [],
                selectedDeleteIds: [],
                bulkSmsText: '',
                bulkSmsMixed: false,
                slotPrefix: initialKind === 'surgery' ? 'عمل' : 'ویزیت',
                smsPresets: Array.isArray(smsPresetsList) ? smsPresetsList : [],
                smsDefaultVisit: smsDefaultVisitText || '',
                smsDefaultSurgery: smsDefaultSurgeryText || '',
                smsText: (initialKind === 'surgery' ? smsDefaultSurgeryText : smsDefaultVisitText) || '',
                capacityLimitsEnabled: false,
                capacityLimitRules: [],
                capacityLimitDraftSubtype: '',
                capacityLimitDraftCount: 1,
                dayNames: ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'],
                init() {
                    this.$watch('openCalendar', (open) => {
                        document.documentElement.style.overflow = open ? 'hidden' : '';
                        document.body.style.overflow = open ? 'hidden' : '';
                    });
                    this.$watch('selectedDeleteIds', () => this.syncBulkSmsFromSelection());
                    if (window.OverlayHistory) {
                        window.OverlayHistory.bindWatch(this, 'openCalendar', 'times-calendar');
                    }
                },
                get selectedHospitalName() {
                    const h = this.hospitals.find((item) => String(item.id) === String(this.selectedHospitalId));
                    return h ? h.name : '';
                },
                get selectedTypeName() {
                    const t = this.surgeryTypes.find((item) => String(item.id) === String(this.selectedTypeId));
                    return t ? t.name : '';
                },
                matchingProgramGroup() {
                    const typeId = Number(this.selectedTypeId || 0);
                    if (!typeId) return null;
                    const subtypeIds = (this.selectedSaveSubtypeIds || [])
                        .filter((id) => id && id !== 'all' && id !== 'general')
                        .map(Number);
                    return this.programGroups.find((g) => {
                        const whole = (g.whole_type_ids || []).map(Number).includes(typeId);
                        if (whole) return true;
                        if (subtypeIds.length && (g.subtype_ids || []).some((id) => subtypeIds.includes(Number(id)))) return true;
                        return false;
                    }) || null;
                },
                get currentSubtypes() {
                    const t = this.surgeryTypes.find((item) => String(item.id) === String(this.selectedTypeId));
                    return t ? (t.subtypes || []) : [];
                },
                get selectedSubtypeName() {
                    if (!this.selectedSubtypeId || this.selectedSubtypeId === 'general') return '';
                    const s = this.currentSubtypes.find((item) => String(item.id) === String(this.selectedSubtypeId));
                    return s ? s.name : '';
                },
                get scopeSubtypeLabel() {
                    if (!this.selectedTypeId) return '';
                    if (this.isSaveSubtypeSelected('all') || !(this.selectedSaveSubtypeIds || []).length) return '';
                    const ids = this.selectedSaveSubtypeIds.filter((id) => id !== 'all');
                    if (ids.length === 1 && ids[0] === 'general') return 'عمومی';
                    if (ids.length === 1) {
                        const s = this.currentSubtypes.find((item) => String(item.id) === String(ids[0]));
                        return s ? s.name : '';
                    }
                    return ids.length + ' زیرگروه';
                },
                get saveSubtypeId() {
                    const ids = this.saveSubtypeIdsForForm;
                    return ids[0] ?? '';
                },
                get saveSubtypeIdsForForm() {
                    const ids = (this.selectedSaveSubtypeIds || []).filter((id) => id && id !== 'all');
                    if (!ids.length) return [''];
                    return ids.map((id) => id === 'general' ? '' : id);
                },
                get saveSubtypeLabel() {
                    if (!this.selectedTypeId) return '—';
                    const ids = (this.selectedSaveSubtypeIds || []).filter((id) => id !== 'all');
                    if (!ids.length) return 'عمومی';
                    return ids.map((id) => {
                        if (id === 'general') return 'عمومی';
                        const s = this.currentSubtypes.find((item) => String(item.id) === String(id));
                        return s ? s.name : id;
                    }).join('، ');
                },
                subtypeName(id) {
                    const s = this.currentSubtypes.find((item) => String(item.id) === String(id));
                    if (s) return s.name;
                    for (const t of this.surgeryTypes) {
                        const hit = (t.subtypes || []).find((item) => String(item.id) === String(id));
                        if (hit) return hit.name;
                    }
                    return 'زیرگروه #' + id;
                },
                addCapacityRule() {
                    const sid = String(this.capacityLimitDraftSubtype || '').trim();
                    const count = Math.max(1, Math.min(99, Number(this.capacityLimitDraftCount) || 1));
                    if (!sid) { alert('زیرگروه را انتخاب کنید.'); return; }
                    this.capacityLimitRules = (this.capacityLimitRules || []).filter((r) => String(r.subtype_id) !== sid);
                    this.capacityLimitRules.push({ subtype_id: Number(sid), remove_count: count });
                    this.capacityLimitDraftSubtype = '';
                    this.capacityLimitDraftCount = 1;
                },
                removeCapacityRule(idx) {
                    this.capacityLimitRules.splice(idx, 1);
                },
                get filteredSurgeryDays() {
                    if (!this.selectedHospitalId) return [];
                    return this.surgeryDays
                        .filter((day) => {
                            if (String(day.hospital_id) !== String(this.selectedHospitalId)) return false;
                            if (this.selectedTypeId && String(day.surgery_type_id) !== String(this.selectedTypeId)) return false;
                            if (this.isSaveSubtypeSelected('all') || !(this.selectedSaveSubtypeIds || []).length) return true;
                            const ids = this.selectedSaveSubtypeIds.filter((id) => id !== 'all');
                            return ids.some((id) => {
                                if (id === 'general') {
                                    return day.surgery_subtype_id === null || day.surgery_subtype_id === '' || typeof day.surgery_subtype_id === 'undefined';
                                }
                                return String(day.surgery_subtype_id) === String(id);
                            });
                        })
                        .slice()
                        .sort((a, b) => {
                            const typeCmp = (a.type_name || '').localeCompare(b.type_name || '', 'fa');
                            if (typeCmp !== 0) return typeCmp;
                            const subCmp = (a.subtype_name || 'عمومی').localeCompare(b.subtype_name || 'عمومی', 'fa');
                            if (subCmp !== 0) return subCmp;
                            return (a.year - b.year) || (a.month - b.month) || (a.day - b.day);
                        });
                },
                isSaveSubtypeSelected(id) {
                    return (this.selectedSaveSubtypeIds || []).includes(String(id));
                },
                toggleSaveSubtype(id) {
                    id = String(id);
                    if (!this.selectedTypeId && id !== 'all') return;
                    let next = [...(this.selectedSaveSubtypeIds || [])];
                    if (id === 'all') {
                        next = ['all'];
                        this.selectedSubtypeId = null;
                    } else {
                        next = next.filter((x) => x !== 'all');
                        if (next.includes(id)) next = next.filter((x) => x !== id);
                        else next.push(id);
                        if (!next.length) next = ['general'];
                        this.selectedSubtypeId = next.length === 1 ? next[0] : null;
                    }
                    this.selectedSaveSubtypeIds = next;
                    this.syncUrl();
                },
                get filteredSurgeryGrouped() {
                    const map = {};
                    this.filteredSurgeryDays.forEach((day) => {
                        const key = String(day.month);
                        if (!map[key]) map[key] = { month: day.month, days: [] };
                        map[key].days.push(day);
                    });
                    return Object.values(map).sort((a, b) => a.month - b.month);
                },
                get visibleDeleteIds() {
                    if (this.selectedKind === 'surgery') {
                        return this.filteredSurgeryDays.map((day) => Number(day.id));
                    }
                    return Array.from(document.querySelectorAll('[data-schedule-delete-id]'))
                        .map((el) => Number(el.getAttribute('data-schedule-delete-id')))
                        .filter((id) => id > 0);
                },
                isDeleteSelected(id) {
                    return this.selectedDeleteIds.includes(Number(id));
                },
                toggleDeleteId(id) {
                    id = Number(id);
                    if (this.selectedDeleteIds.includes(id)) {
                        this.selectedDeleteIds = this.selectedDeleteIds.filter((x) => x !== id);
                    } else {
                        this.selectedDeleteIds = [...this.selectedDeleteIds, id];
                    }
                },
                selectAllVisibleForDelete() {
                    const ids = this.visibleDeleteIds;
                    const allSelected = ids.length > 0 && ids.every((id) => this.selectedDeleteIds.includes(id));
                    if (allSelected) {
                        this.selectedDeleteIds = this.selectedDeleteIds.filter((id) => !ids.includes(id));
                    } else {
                        const merged = new Set([...this.selectedDeleteIds, ...ids]);
                        this.selectedDeleteIds = Array.from(merged);
                    }
                },
                bulkDeleteSelected() {
                    if (!this.selectedDeleteIds.length) return;
                    const count = this.selectedDeleteIds.length;
                    if (!confirm('حذف ' + count + ' روز انتخاب‌شده؟')) return;
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = bulkDestroyUrl;
                    const token = document.createElement('input');
                    token.type = 'hidden';
                    token.name = '_token';
                    token.value = csrf;
                    form.appendChild(token);
                    const kindInput = document.createElement('input');
                    kindInput.type = 'hidden';
                    kindInput.name = 'kind';
                    kindInput.value = this.selectedKind;
                    form.appendChild(kindInput);
                    this.selectedDeleteIds.forEach((id) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = String(id);
                        form.appendChild(input);
                    });
                    document.body.appendChild(form);
                    form.submit();
                },
                selectedSmsTexts() {
                    const ids = this.selectedDeleteIds.map(Number);
                    if (this.selectedKind === 'surgery') {
                        return this.surgeryDays
                            .filter((day) => ids.includes(Number(day.id)))
                            .map((day) => String(day.sms_text || '').trim());
                    }
                    return Array.from(document.querySelectorAll('[data-schedule-delete-id]'))
                        .filter((el) => ids.includes(Number(el.getAttribute('data-schedule-delete-id'))))
                        .map((el) => String(el.getAttribute('data-sms-text') || '').trim());
                },
                syncBulkSmsFromSelection() {
                    if (!this.selectedDeleteIds.length) {
                        this.bulkSmsText = '';
                        this.bulkSmsMixed = false;
                        return;
                    }
                    const unique = Array.from(new Set(this.selectedSmsTexts()));
                    this.bulkSmsMixed = unique.length > 1;
                    this.bulkSmsText = unique.length === 1 ? (unique[0] || '') : '';
                },
                applyBulkSmsPreset(id) {
                    const preset = this.smsPresets.find((item) => item.id === id);
                    if (preset) this.bulkSmsText = preset.body;
                },
                bulkSaveSms() {
                    if (!this.selectedDeleteIds.length) return;
                    const count = this.selectedDeleteIds.length;
                    const text = String(this.bulkSmsText || '').trim();
                    if (!text) {
                        alert('متن پیامک را بنویسید.');
                        return;
                    }
                    if (!confirm('همین متن پیامک برای ' + count + ' روز انتخاب‌شده ذخیره شود؟')) return;
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = bulkSmsUrl;
                    const token = document.createElement('input');
                    token.type = 'hidden';
                    token.name = '_token';
                    token.value = csrf;
                    form.appendChild(token);
                    const kindInput = document.createElement('input');
                    kindInput.type = 'hidden';
                    kindInput.name = 'kind';
                    kindInput.value = this.selectedKind;
                    form.appendChild(kindInput);
                    const smsInput = document.createElement('input');
                    smsInput.type = 'hidden';
                    smsInput.name = 'sms_text';
                    smsInput.value = text;
                    form.appendChild(smsInput);
                    this.selectedDeleteIds.forEach((id) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = String(id);
                        form.appendChild(input);
                    });
                    document.body.appendChild(form);
                    form.submit();
                },
                syncUrl() {
                    const url = new URL(window.location.href);
                    url.searchParams.set('kind', this.selectedKind);
                    if (this.selectedKind === 'surgery') {
                        if (this.selectedHospitalId) url.searchParams.set('hospital_id', this.selectedHospitalId);
                        else url.searchParams.delete('hospital_id');
                        if (this.selectedTypeId) url.searchParams.set('surgery_type_id', this.selectedTypeId);
                        else url.searchParams.delete('surgery_type_id');
                        if (this.selectedSubtypeId) url.searchParams.set('surgery_subtype_id', this.selectedSubtypeId);
                        else url.searchParams.delete('surgery_subtype_id');
                    } else {
                        url.searchParams.delete('hospital_id');
                        url.searchParams.delete('surgery_type_id');
                        url.searchParams.delete('surgery_subtype_id');
                    }
                    window.history.replaceState({}, '', url.toString());
                },
                setKind(kind) {
                    if (this.selectedKind === kind) return;
                    const prevDefault = this.selectedKind === 'surgery' ? this.smsDefaultSurgery : this.smsDefaultVisit;
                    this.selectedKind = kind;
                    this.selectedDeleteIds = [];
                    this.slotPrefix = kind === 'surgery' ? 'عمل' : 'ویزیت';
                    if (!this.smsText.trim() || this.smsText === prevDefault) {
                        this.smsText = kind === 'surgery' ? this.smsDefaultSurgery : this.smsDefaultVisit;
                    }
                    this.syncUrl();
                },
                applySmsPreset(id) {
                    const preset = this.smsPresets.find((item) => item.id === id);
                    if (preset) this.smsText = preset.body;
                },
                smsPreview(text) {
                    const sample = {
                        name: 'سارا احمدی',
                        date: '1404/06/15',
                        slotNumber: '10:30',
                        hospital: this.selectedKind === 'surgery' ? (this.selectedHospitalName || 'بیمارستان نمونه') : 'مطب',
                        time: '10:30',
                        surgeryType: this.selectedKind === 'surgery' ? (this.selectedTypeName || 'کاتاراکت') : 'ویزیت',
                    };
                    let out = String(text || '');
                    Object.keys(sample).forEach((key) => {
                        out = out.split('{' + key + '}').join(sample[key]);
                    });
                    return out;
                },
                setHospital(id) {
                    if (!id) return;
                    if (String(this.selectedHospitalId) === String(id)) return;
                    this.selectedHospitalId = id;
                    this.selectedTypeId = null;
                    this.selectedSubtypeId = null;
                    this.selectedSaveSubtypeIds = ['all'];
                    this.syncUrl();
                },
                setType(id) {
                    const next = id || null;
                    if (String(this.selectedTypeId || '') === String(next || '')) return;
                    this.selectedTypeId = next;
                    this.selectedSubtypeId = null;
                    this.selectedSaveSubtypeIds = ['all'];
                    this.syncUrl();
                },
                setSubtype(id) {
                    const next = id || null;
                    if (String(this.selectedSubtypeId || '') === String(next || '')) return;
                    this.selectedSubtypeId = next;
                    this.selectedSaveSubtypeIds = next ? [String(next)] : ['all'];
                    this.syncUrl();
                },
                async addHospital() {
                    const name = (this.newHospitalName || '').trim();
                    if (!name) return;
                    const res = await fetch(@js(route('settings.quick.hospital')), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ name }),
                    });
                    const data = await res.json();
                    if (!res.ok) { alert(data.message || 'خطا در افزودن بیمارستان'); return; }
                    this.hospitals.push({ id: data.id, name: data.name });
                    this.selectedHospitalId = data.id;
                    this.selectedSubtypeId = null;
                    this.selectedSaveSubtypeIds = ['all'];
                    this.newHospitalName = '';
                    this.showAddHospital = false;
                    this.syncUrl();
                },
                async addType() {
                    const name = (this.newTypeName || '').trim();
                    if (!name) return;
                    const res = await fetch(@js(route('settings.quick.surgery-type')), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ name }),
                    });
                    const data = await res.json();
                    if (!res.ok) { alert(data.message || 'خطا در افزودن نوع عمل'); return; }
                    this.surgeryTypes.push({ id: data.id, name: data.name, subtypes: [] });
                    this.selectedTypeId = data.id;
                    this.selectedSubtypeId = null;
                    this.selectedSaveSubtypeIds = ['all'];
                    this.newTypeName = '';
                    this.showAddType = false;
                    this.showAddSubtype = true;
                    this.syncUrl();
                },
                async addSubtype() {
                    const name = (this.newSubtypeName || '').trim();
                    if (!name || !this.selectedTypeId) return;
                    const res = await fetch(@js(route('settings.quick.surgery-subtype')), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ name, surgery_type_id: this.selectedTypeId }),
                    });
                    const data = await res.json();
                    if (!res.ok) { alert(data.message || 'خطا در افزودن زیرگروه'); return; }
                    const t = this.surgeryTypes.find((item) => String(item.id) === String(this.selectedTypeId));
                    if (t) {
                        t.subtypes = t.subtypes || [];
                        t.subtypes.push({ id: data.id, name: data.name });
                    }
                    this.selectedSubtypeId = data.id;
                    this.selectedSaveSubtypeIds = [String(data.id)];
                    this.newSubtypeName = '';
                    this.showAddSubtype = false;
                    this.syncUrl();
                },
                get monthLabel() {
                    return MONTH_NAMES[this.viewMonth] + ' ' + this.viewYear;
                },
                get calendarCells() {
                    const cells = [];
                    const first = weekdayIndex(this.viewYear, this.viewMonth, 1);
                    const dim = daysInMonth(this.viewYear, this.viewMonth);
                    for (let i = 0; i < first; i++) cells.push({ day: '', disabled: true });
                    for (let d = 1; d <= dim; d++) {
                        const isToday = this.viewYear === today.y && this.viewMonth === today.m && d === today.d;
                        const isPast = (this.viewYear < today.y) || (this.viewYear === today.y && this.viewMonth < today.m) || (this.viewYear === today.y && this.viewMonth === today.m && d < today.d);
                        const wd = weekdayIndex(this.viewYear, this.viewMonth, d);
                        cells.push({
                            day: d,
                            date: this.viewYear + '/' + pad(this.viewMonth) + '/' + pad(d),
                            year: this.viewYear,
                            month: this.viewMonth,
                            weekday: WEEK_DAYS[wd],
                            disabled: isPast || wd === 6,
                            today: isToday,
                        });
                    }
                    return cells;
                },
                shiftMonth(delta) {
                    this.viewMonth += delta;
                    if (this.viewMonth < 1) { this.viewMonth = 12; this.viewYear--; }
                    if (this.viewMonth > 12) { this.viewMonth = 1; this.viewYear++; }
                },
                selectDate(cell) {
                    if (!cell.day || cell.disabled) return;
                    if (this.bulkMode) {
                        const idx = this.selectedDays.findIndex((d) => d.date === cell.date);
                        if (idx >= 0) {
                            this.selectedDays.splice(idx, 1);
                        } else {
                            this.selectedDays.push({
                                date: cell.date,
                                year: cell.year,
                                month: cell.month,
                                day: cell.day,
                                weekday: cell.weekday,
                                display_text: cell.weekday + ' ' + cell.day + ' ' + MONTH_NAMES[cell.month] + ' ' + cell.year,
                            });
                        }
                        return;
                    }
                    this.dateKey = cell.date;
                    this.year = cell.year;
                    this.month = cell.month;
                    this.day = cell.day;
                    this.weekday = cell.weekday;
                    this.displayText = cell.weekday + ' ' + cell.day + ' ' + MONTH_NAMES[cell.month] + ' ' + cell.year;
                    this.openCalendar = false;
                },
                isDaySelected(cell) {
                    return !!cell.date && this.selectedDays.some((d) => d.date === cell.date);
                },
                confirmBulkDays() {
                    if (!this.selectedDays.length) {
                        alert('حداقل یک روز انتخاب کنید');
                        return;
                    }
                    this.openCalendar = false;
                },
                onBulkModeChange() {
                    if (!this.bulkMode) {
                        this.selectedDays = [];
                    }
                },
                useTemplate(text) {
                    this.setSlotMode('time');
                    this.timesText = text;
                    this.savedTimeText = text;
                    this.totalSlots = text.trim().split(/\n+/).filter(Boolean).length;
                },
                setSlotMode(mode) {
                    if (mode === this.slotMode) return;
                    if (this.slotMode === 'time') {
                        this.savedTimeText = this.timesText;
                    }
                    this.slotMode = mode;
                    if (mode === 'queue') {
                        this.syncQueueText();
                    } else {
                        this.timesText = this.savedTimeText;
                    }
                },
                syncQueueText() {
                    const n = Math.max(1, Math.min(99, parseInt(this.queueCount || this.totalSlots || 10, 10)));
                    this.queueCount = n;
                    this.totalSlots = n;
                    const lines = [];
                    for (let i = 1; i <= n; i++) lines.push('نوبت ' + i);
                    this.timesText = lines.join('\n');
                },
                prepareSubmit(e) {
                    if (this.selectedKind === 'surgery') {
                        if (!this.selectedHospitalId) { e.preventDefault(); alert('بیمارستان را انتخاب کنید'); return; }
                        if (!this.selectedTypeId) { e.preventDefault(); alert('نوع عمل را انتخاب کنید'); return; }
                        const saveIds = (this.selectedSaveSubtypeIds || []).filter((id) => id !== 'all');
                        if (!saveIds.length) {
                            e.preventDefault();
                            alert('حداقل یک زیرگروه (مثلاً عمومی) را برای ثبت انتخاب کنید. گزینه «همه» فقط فیلتر است.');
                            return;
                        }
                    }
                    if (!this.dateKey && !this.bulkMode) { e.preventDefault(); alert('لطفاً تاریخ را انتخاب کنید'); return; }
                    if (this.bulkMode && !this.selectedDays.length) { e.preventDefault(); alert('حداقل یک روز از تقویم انتخاب کنید'); return; }
                    if (this.slotMode === 'queue') {
                        this.syncQueueText();
                    } else {
                        this.savedTimeText = this.timesText;
                        if (!this.timesText.trim()) {
                            e.preventDefault(); alert('لطفاً حداقل یک ساعت وارد کنید'); return;
                        }
                    }
                    this.slotPrefix = this.selectedKind === 'surgery' ? (this.slotPrefix || 'عمل') : (this.slotPrefix || 'ویزیت');
                },
            };
        }
    </script>

</x-app-layout>
