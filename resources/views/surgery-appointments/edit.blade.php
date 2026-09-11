<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="page-title">ویرایش نوبت عمل</h2>
            <a href="{{ route('appointments.board', ['kind' => 'surgery']) }}" class="btn-ghost !text-xs !px-2.5 !py-1.5">نوبت‌ها</a>
        </div>
    </x-slot>

    @php
        $editHospitals = $hospitals ?? \App\Models\Hospital::query()->orderBy('name')->get(['id', 'name']);
        $editHospitalId = old('hospital_id', $surgery->hospital_id);
        $editTime = old('scheduled_time', \App\Support\SlotLabel::normalize((string) $surgery->scheduled_time));
    @endphp

    <div
        class="py-4 sm:py-6"
        x-data="surgeryBookingForm({
            optionsUrl: @js(route('appointments.surgery-options')),
            slotsUrl: @js(route('appointments.slots')),
            excludeId: @js($surgery->id),
            hospitals: @js($editHospitals->map(fn ($h) => ['id' => $h->id, 'name' => $h->name])->values()),
            initialDate: @js(old('scheduled_date', jalali($surgery->scheduled_date, 'Y/m/d'))),
            initialTime: @js($editTime),
            initialHospitalId: @js($editHospitalId),
            initialTypeId: @js(old('surgery_type_id', $surgery->surgery_type_id)),
            initialSubtypeId: @js(old('surgery_subtype_id', $surgery->surgery_subtype_id)),
            initialTypeLabel: @js(old('surgery_type', $surgery->surgery_type)),
            initialException: @js((bool) old('is_exception', $surgery->is_exception)),
            initialEyeSide: @js(\App\Support\EyeSide::normalize(old('eye_side', $surgery->eye_side))),
            cooldownUrl: @js(route('surgery-appointments.cooldown-check')),
            patientId: @js($surgery->patient_id),
        })"
    >
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('surgery-appointments.update', $surgery) }}" class="panel fade-up space-y-5 p-4 sm:p-6" @submit="prepareSubmit($event)">
                @csrf
                @method('PUT')

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="patient_name" value="نام و نام خانوادگی" />
                        <x-text-input id="patient_name" name="patient_name" type="text" class="mt-1 block w-full" :value="old('patient_name', $surgery->patient_name)" required />
                    </div>
                    <div>
                        <x-input-label for="national_code" value="کد ملی" />
                        <x-text-input id="national_code" name="national_code" type="text" inputmode="numeric" maxlength="10" class="mt-1 block w-full font-mono" dir="ltr" :value="old('national_code', $surgery->national_code)" required x-on:input="$el.value = $el.value.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g, '').slice(0, 10)" />
                    </div>
                    <div>
                        <x-input-label for="mobile" value="شماره تماس" />
                        <x-text-input id="mobile" name="mobile" type="text" inputmode="tel" maxlength="11" class="mt-1 block w-full font-mono" dir="ltr" :value="old('mobile', $surgery->mobile)" required x-on:input="$el.value = $el.value.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g, '').slice(0, 11)" />
                    </div>
                    <div>
                        <x-input-label for="mobile_secondary" value="شماره تماس دوم (اختیاری)" />
                        <x-text-input id="mobile_secondary" name="mobile_secondary" type="text" inputmode="tel" maxlength="11" class="mt-1 block w-full font-mono" dir="ltr" :value="old('mobile_secondary', $surgery->mobile_secondary)" x-on:input="$el.value = $el.value.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g, '').slice(0, 11)" />
                    </div>
                    <div>
                        <x-input-label for="age" value="سن" />
                        <x-text-input id="age" name="age" type="text" class="mt-1 block w-full" :value="old('age', $surgery->age)" />
                    </div>
                </div>

                <div class="border-t pt-5 space-y-4" style="border-color: var(--line);">
                    <div>
                        <x-input-label value="۱) بیمارستان" />
                        <select name="hospital_id" class="field-input mt-1" required x-model="hospitalId" @change="onHospitalChange()">
                            <option value="">انتخاب...</option>
                            @foreach ($editHospitals as $hospital)
                                <option value="{{ $hospital->id }}" @selected((string) $editHospitalId === (string) $hospital->id)>{{ $hospital->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2" x-show="hospitalId" x-cloak>
                        <div>
                            <x-input-label value="۲) نوع عمل" />
                            <select name="surgery_type_id" class="field-input mt-1" required x-model="typeId" @change="onTypeChange()">
                                <option value="">انتخاب...</option>
                                <template x-for="t in catalogTypes" :key="t.id">
                                    <option :value="String(t.id)" :selected="String(t.id) === String(typeId)" x-text="t.name"></option>
                                </template>
                            </select>
                            <input type="hidden" name="surgery_type" :value="selectedTypeName">
                        </div>
                        <div>
                            <x-input-label value="۳) زیرگروه" />
                            <select name="surgery_subtype_id" class="field-input mt-1" x-model="subtypeId" @change="onSubtypeChange()" :disabled="!typeId">
                                <option value="" x-show="selectedTypeHasGeneral" x-cloak>عمومی</option>
                                <template x-for="s in selectedTypeSubtypes" :key="s.id">
                                    <option :value="String(s.id)" :selected="String(s.id) === String(subtypeId)" x-text="s.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div x-show="hospitalId && typeId && subtypeReady" x-cloak>
                        <label class="mb-3 flex items-start gap-2 rounded-xl border px-3 py-2.5 text-xs font-bold" style="border-color: var(--line); color: var(--ink); background: color-mix(in srgb, var(--brand) 6%, var(--panel));">
                            <input type="checkbox" class="mt-0.5" x-model="showBooked">
                            <span>
                                نمایش روزها و تایم‌های پر شده
                                <span class="mt-0.5 block font-medium" style="color: var(--muted);">با فعال کردن این گزینه می‌توانید روزهای تکمیل‌شده را ببینید، جزئیات نوبت‌های پر را باز کنید و برای همان روز نوبت استثنا تعریف کنید.</span>
                            </span>
                        </label>
                        <x-input-label value="۴) تاریخ عمل" />
                        <input type="text" name="scheduled_date" class="field-input mt-1 cursor-pointer" readonly required
                               x-model="dateKey" @click.prevent="openCalendar = true" @keydown.enter.prevent="openCalendar = true">
                        <p class="mt-1 text-xs" style="color:var(--muted)" x-show="!showBooked" x-cloak>
                            روزهای قرمز (تکمیل) فقط بعد از زدن تیک بالا قابل انتخاب‌اند.
                        </p>
                    </div>

                    <div x-show="dateKey && hospitalId && typeId" x-cloak>
                        <label class="block text-sm font-bold" style="color:var(--ink)" x-text="slotMode === 'queue' ? '۵) نوبت آزاد' : '۵) ساعت آزاد'"></label>
                        <input type="hidden" name="scheduled_time" x-model="timeValue">
                        <div class="booking-time-grid mt-2">
                            <template x-if="loadingSlots"><div class="booking-slot-loading">بارگذاری...</div></template>
                            <template x-if="!loadingSlots && !slotList.length">
                                <div class="booking-slot-empty">
                                    <span x-text="hasBookedSlots && !showBooked ? 'نوبت آزادی نیست. تیک «نمایش روزها و تایم‌های پر شده» را بزنید یا از «نوبت استثنا» استفاده کنید.' : (slotsMessage || 'نوبت آزادی نیست.')"></span>
                                </div>
                            </template>
                            <template x-for="slot in slotList" :key="slot.time">
                                <button type="button" class="booking-slot" :class="{ 'is-booked': slot.booked, 'is-removed': slot.removed && !slot.booked, 'is-selected': timeValue === slot.time, 'is-exception-slot': slot.exception, 'is-booked-clickable': slot.booked }" :disabled="!slot.booked && slot.bookable === false" @click="selectSlot(slot)">
                                    <span x-text="slot.label || slot.time"></span>
                                    <small x-show="slot.booked" x-cloak>پر</small>
                                </button>
                            </template>
                        </div>
                        <input type="hidden" name="is_exception" :value="isException ? 1 : 0">
                        <label class="mt-2 flex items-center gap-2 text-xs font-bold" style="color: var(--warn);">
                            <input type="checkbox" name="is_emergency" value="1" @checked(old('is_emergency', $surgery->is_emergency))> اورژانس
                        </label>
                        <div class="exception-btn-wrapper mt-3">
                            <button type="button" class="btn-exception" @click="addExceptionSlot()">➕ نوبت استثنا</button>
                            <span class="exception-hint">نوبت اضافی فراتر از ظرفیت</span>
                        </div>
                        <div class="mt-3" x-show="showExceptionTimeInput" x-cloak>
                            <div class="flex gap-2">
                                <input type="time" class="field-input" x-model="exceptionTimeDraft" dir="ltr">
                                <button type="button" class="btn-secondary shrink-0" @click="confirmExceptionTime()">تأیید</button>
                            </div>
                        </div>
                        <div x-show="bookedModalOpen" x-cloak class="booked-slot-modal" @keydown.escape.window="closeBookedModal()">
                            <div class="booked-slot-modal__backdrop" @click="closeBookedModal()"></div>
                            <div class="booked-slot-modal__panel panel" role="dialog" aria-modal="true">
                                <div class="flex items-start justify-between gap-3 border-b pb-3" style="border-color: var(--line);">
                                    <div>
                                        <h4 class="text-sm font-extrabold" style="color: var(--ink);">جزئیات نوبت پر شده</h4>
                                        <p class="mt-0.5 text-xs" style="color: var(--muted);" x-text="activeSlotLabel"></p>
                                    </div>
                                    <button type="button" class="booked-slot-modal__close" @click="closeBookedModal()">×</button>
                                </div>
                                <template x-if="activeBooking">
                                    <dl class="booked-slot-modal__grid mt-4">
                                        <div><dt>نام بیمار</dt><dd x-text="activeBooking.patient_name || '—'"></dd></div>
                                        <div><dt>موبایل</dt><dd x-text="activeBooking.mobile || '—'" dir="ltr"></dd></div>
                                        <div x-show="activeBooking.national_code"><dt>کد ملی</dt><dd x-text="activeBooking.national_code" dir="ltr"></dd></div>
                                        <div><dt>نوع عمل</dt><dd x-text="activeBooking.surgery_type || '—'"></dd></div>
                                        <div x-show="activeBooking.subtype_name"><dt>زیرگروه</dt><dd x-text="activeBooking.subtype_name"></dd></div>
                                        <div x-show="activeBooking.eye_side"><dt>چشم</dt><dd x-text="activeBooking.eye_side"></dd></div>
                                        <div x-show="activeBooking.surgeon_name"><dt>جراح</dt><dd x-text="activeBooking.surgeon_name"></dd></div>
                                        <div><dt>وضعیت</dt><dd x-text="activeBooking.status_label || activeBooking.status || '—'"></dd></div>
                                    </dl>
                                </template>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <a x-show="activeBooking && activeBooking.edit_url" :href="activeBooking ? activeBooking.edit_url : '#'" class="btn-secondary text-xs">ویرایش نوبت</a>
                                    <button type="button" class="btn-ghost text-xs" @click="closeBookedModal()">بستن</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="eye_side" value="چشم *" />
                            <select id="eye_side" name="eye_side" class="field-input" required x-model="eyeSide">
                                <option value="">انتخاب کنید</option>
                                @foreach (\App\Support\EyeSide::options() as $side)
                                    <option value="{{ $side['value'] }}" @selected(\App\Support\EyeSide::normalize(old('eye_side', $surgery->eye_side)) === $side['value'])>{{ $side['label'] }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('eye_side')" />
                        </div>
                        <div>
                            <x-input-label for="surgeon_name" value="نام جراح" />
                            <x-text-input id="surgeon_name" name="surgeon_name" type="text" class="mt-1 block w-full" :value="old('surgeon_name', $surgery->surgeon_name)" />
                        </div>
                    </div>
                    @include('surgery-appointments.partials.cooldown-warn')
                    <div>
                        <x-input-label for="notes" value="توضیحات" />
                        <textarea id="notes" name="notes" rows="3" class="field-input">{{ old('notes', $surgery->notes) }}</textarea>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn-primary">ذخیره تغییرات</button>
                    <a href="{{ route('patients.show', $patient) }}" class="btn-secondary">پرونده بیمار</a>
                </div>
            </form>
        </div>

        @include('surgery-appointments.partials.calendar-modal')
    </div>

    @include('surgery-appointments.partials.booking-script')
</x-app-layout>
