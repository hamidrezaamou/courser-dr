@php
    $patient = $patient ?? null;
    $showUploads = $showUploads ?? false;
    $dateAsSelect = $dateAsSelect ?? true;
    $identityLookup = $identityLookup ?? ($patient === null);
    $noNationalCodeDefault = old('no_national_code', $patient && blank($patient->national_code));
@endphp

@if($identityLookup)
    <x-patient-identity-fields :with-secondary-mobile="true" />
@else
<div class="grid gap-4 sm:grid-cols-2" x-data="{ noNationalCode: @js((bool) $noNationalCodeDefault) }">
    <div>
        <x-input-label for="patient_name" value="نام و نام خانوادگی" />
        <x-text-input id="patient_name" name="patient_name" type="text" class="mt-1 block w-full" :value="old('patient_name', $patient?->name)" required />
        <x-input-error class="mt-2" :messages="$errors->get('patient_name')" />
    </div>
    <div>
        <x-input-label for="national_code" value="کد ملی" />
        <x-text-input
            id="national_code"
            name="national_code"
            type="text"
            inputmode="numeric"
            maxlength="10"
            pattern="\d{10}"
            class="mt-1 block w-full font-mono"
            dir="ltr"
            :value="old('national_code', $patient?->national_code)"
            x-bind:required="!noNationalCode"
            x-bind:disabled="noNationalCode"
            x-on:input="$el.value = $el.value.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g, '').slice(0, 10)"
        />
        <label class="mt-2 flex items-center gap-2 text-xs" style="color: var(--muted);">
            <input type="checkbox" name="no_national_code" value="1" x-model="noNationalCode" @checked($noNationalCodeDefault)>
            فاقد کد ملی
        </label>
        <x-input-error class="mt-2" :messages="$errors->get('national_code')" />
    </div>
    <div>
        <x-input-label for="mobile" value="شماره تماس" />
        <x-text-input
            id="mobile"
            name="mobile"
            type="text"
            inputmode="tel"
            maxlength="11"
            pattern="09\d{9}"
            class="mt-1 block w-full font-mono"
            dir="ltr"
            :value="old('mobile', $patient?->mobile)"
            required
            x-on:input="$el.value = $el.value.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g, '').slice(0, 11)"
        />
        <x-input-error class="mt-2" :messages="$errors->get('mobile')" />
    </div>
    <div>
        <x-input-label for="mobile_secondary" value="شماره تماس دوم (اختیاری)" />
        <x-text-input
            id="mobile_secondary"
            name="mobile_secondary"
            type="text"
            inputmode="tel"
            maxlength="11"
            class="mt-1 block w-full font-mono"
            dir="ltr"
            :value="old('mobile_secondary', $patient?->mobile_secondary)"
            x-on:input="$el.value = $el.value.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g, '').slice(0, 11)"
        />
        <x-input-error class="mt-2" :messages="$errors->get('mobile_secondary')" />
    </div>
    <div>
        <x-input-label for="age" value="سن" />
        <x-text-input id="age" name="age" type="text" class="mt-1 block w-full" :value="old('age', $patient?->age)" />
    </div>
</div>
@endif

<div class="border-t pt-5 space-y-4" style="border-color: var(--line);">
    <h3 class="text-sm font-bold" style="color: var(--ink);">زمان‌بندی عمل</h3>

    <div>
        <x-input-label value="۱) بیمارستان" />
        <select name="hospital_id" class="field-input mt-1" required x-model="hospitalId" @change="onHospitalChange()">
            <option value="">انتخاب بیمارستان...</option>
            @foreach (($hospitals ?? collect()) as $hospital)
                <option value="{{ $hospital->id }}" @selected((string) old('hospital_id') === (string) $hospital->id)>{{ $hospital->name }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('hospital_id')" />
    </div>

    <div class="grid gap-3 sm:grid-cols-2" x-show="hospitalId" x-cloak>
        <div>
            <x-input-label value="۲) نوع عمل" />
            <select name="surgery_type_id" class="field-input mt-1" required x-model="typeId" @change="onTypeChange()" :disabled="loadingCatalog || !catalogTypes.length">
                <option value="">انتخاب...</option>
                <template x-for="t in catalogTypes" :key="t.id">
                    <option :value="String(t.id)" x-text="t.name"></option>
                </template>
            </select>
            <input type="hidden" name="surgery_type" :value="selectedTypeName">
            <p class="mt-1 text-[11px]" style="color:var(--muted)" x-show="loadingCatalog" x-cloak>بارگذاری انواع عمل...</p>
            <p class="mt-1 text-[11px] text-rose-600" x-show="!loadingCatalog && hospitalId && !catalogTypes.length" x-cloak>برای این بیمارستان برنامه‌ای ثبت نشده.</p>
        </div>
        <div>
            <x-input-label value="۳) زیرگروه" />
            <select name="surgery_subtype_id" class="field-input mt-1" x-model="subtypeId" @change="onSubtypeChange()" :disabled="!typeId">
                <option value="" x-show="selectedTypeHasGeneral" x-cloak>عمومی</option>
                <template x-for="s in selectedTypeSubtypes" :key="s.id">
                    <option :value="String(s.id)" x-text="s.name"></option>
                </template>
            </select>
        </div>
    </div>

    <div x-show="hospitalId && typeId && subtypeReady" x-cloak>
        <label class="mb-3 flex items-start gap-2 rounded-xl border px-3 py-2.5 text-xs font-bold" style="border-color: var(--line); color: var(--ink); background: color-mix(in srgb, var(--brand) 6%, var(--panel));">
            <input type="checkbox" class="mt-0.5" x-model="showBooked">
            <span>
                نمایش روزها و نوبت‌های پر شده
                <span class="mt-0.5 block font-medium" style="color: var(--muted);">با فعال کردن این گزینه می‌توانید روزهای تکمیل‌شده را انتخاب کنید، جزئیات نوبت‌های پر را ببینید و برای همان روز نوبت استثنا تعریف کنید.</span>
            </span>
        </label>

        <x-input-label value="۴) تاریخ عمل" />
        @if ($dateAsSelect)
            <select name="scheduled_date" class="field-input mt-1" required x-model="dateKey" @change="onDateSelectChange()">
                <option value="">-- انتخاب تاریخ --</option>
                <template x-for="opt in dateOptions" :key="opt.value">
                    <option :value="opt.value" :disabled="opt.disabled" x-text="opt.label"></option>
                </template>
            </select>
            <p class="mt-1 text-xs" style="color:var(--muted)" x-show="loadingCalendar" x-cloak>بارگذاری روزهای دارای برنامه...</p>
            <p class="mt-1 text-xs text-rose-600" x-show="!loadingCalendar && hospitalId && typeId && subtypeReady && !dateOptions.length" x-cloak>
                تاریخی برای این محدوده باقی نمانده است.
            </p>
            <p class="mt-1 text-xs" style="color:var(--muted)" x-show="!showBooked && hasFullDays" x-cloak>
                روزهای تکمیل‌شده در لیست هستند ولی غیرفعال‌اند؛ برای انتخاب آن‌ها تیک بالا را بزنید.
            </p>
        @else
            <input type="text" name="scheduled_date" class="field-input mt-1 cursor-pointer" readonly required
                   placeholder="کلیک برای انتخاب از تقویم..."
                   x-model="dateKey"
                   @click.prevent="openCalendar = true"
                   @keydown.enter.prevent="openCalendar = true">
            <p class="mt-1 text-xs" style="color:var(--muted)" x-show="loadingCalendar" x-cloak>بارگذاری روزهای دارای برنامه...</p>
            <p class="mt-1 text-xs" style="color:var(--muted)" x-show="!showBooked" x-cloak>
                روزهای قرمز (تکمیل) فقط بعد از زدن تیک بالا قابل انتخاب‌اند.
            </p>
        @endif
        <x-input-error class="mt-2" :messages="$errors->get('scheduled_date')" />
    </div>

    <div x-show="dateKey && hospitalId && typeId" x-cloak>
        <label class="block text-sm font-bold" style="color:var(--ink)" x-text="slotMode === 'queue' ? '۵) نوبت آزاد' : '۵) ساعت آزاد'"></label>
        <input type="hidden" name="scheduled_time" x-model="timeValue">
        <input type="hidden" name="is_exception" :value="isException ? 1 : 0">
        <div class="booking-time-grid mt-2">
            <template x-if="loadingSlots"><div class="booking-slot-loading">بارگذاری...</div></template>
            <template x-if="!loadingSlots && !slotList.length">
                <div class="booking-slot-empty">
                    <span x-text="hasBookedSlots && !showBooked ? 'نوبت آزادی نیست. تیک «نمایش روزها و نوبت‌های پر شده» را بزنید یا از «نوبت استثنا» استفاده کنید.' : (slotsMessage || 'نوبت آزادی نیست.')"></span>
                </div>
            </template>
            <template x-for="slot in slotList" :key="slot.time">
                <button type="button"
                        class="booking-slot"
                        :class="{
                            'is-booked': slot.booked,
                            'is-removed': slot.removed && !slot.booked,
                            'is-selected': timeValue === slot.time,
                            'is-exception-slot': slot.exception,
                            'is-booked-clickable': slot.booked
                        }"
                        :disabled="!slot.booked && slot.bookable === false"
                        @click="selectSlot(slot)">
                    <span x-text="slot.label || slot.time"></span>
                    <small x-show="slot.booked" x-cloak>پر</small>
                    <small x-show="slot.removed && !slot.booked" x-cloak>حذف‌شده</small>
                    <small x-show="slot.exception && !slot.booked" x-cloak>استثنا</small>
                </button>
            </template>
        </div>

        <div class="exception-btn-wrapper mt-3">
            <button type="button" class="btn-exception" @click="addExceptionSlot()">➕ نوبت استثنا</button>
            <span class="exception-hint">یک نوبت اضافی فراتر از ظرفیت برنامه ایجاد می‌کند</span>
        </div>

        <div class="mt-3" x-show="showExceptionTimeInput" x-cloak>
            <x-input-label value="ساعت نوبت استثنا" />
            <div class="mt-1 flex gap-2">
                <input type="time" class="field-input" x-model="exceptionTimeDraft" dir="ltr">
                <button type="button" class="btn-secondary shrink-0" @click="confirmExceptionTime()">تأیید</button>
            </div>
        </div>

        <label class="mt-2 flex items-center gap-2 text-xs font-bold" style="color: var(--warn);">
            <input type="checkbox" name="is_emergency" value="1" @checked(old('is_emergency'))> اورژانس
        </label>
        <x-input-error class="mt-2" :messages="$errors->get('scheduled_time')" />
    </div>

    <div x-show="bookedModalOpen" x-cloak class="booked-slot-modal" @keydown.escape.window="closeBookedModal()">
        <div class="booked-slot-modal__backdrop" @click="closeBookedModal()"></div>
        <div class="booked-slot-modal__panel panel" role="dialog" aria-modal="true" aria-labelledby="booked-slot-title">
            <div class="flex items-start justify-between gap-3 border-b pb-3" style="border-color: var(--line);">
                <div>
                    <h4 id="booked-slot-title" class="text-sm font-extrabold" style="color: var(--ink);">جزئیات نوبت پر شده</h4>
                    <p class="mt-0.5 text-xs" style="color: var(--muted);" x-text="activeSlotLabel"></p>
                </div>
                <button type="button" class="booked-slot-modal__close" @click="closeBookedModal()" aria-label="بستن">×</button>
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
                    <div x-show="activeBooking.is_exception"><dt>نوع</dt><dd>نوبت استثنا</dd></div>
                    <div x-show="activeBooking.is_emergency"><dt>اولویت</dt><dd class="text-rose-700">اورژانس</dd></div>
                </dl>
            </template>
            <div class="mt-4 flex flex-wrap gap-2">
                <a x-show="activeBooking && activeBooking.edit_url"
                   :href="activeBooking ? activeBooking.edit_url : '#'"
                   class="btn-secondary text-xs">ویرایش نوبت</a>
                <button type="button" class="btn-ghost text-xs" @click="closeBookedModal()">بستن</button>
            </div>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="eye_side" value="چشم *" />
            <select id="eye_side" name="eye_side" class="field-input" required x-model="eyeSide">
                <option value="">انتخاب کنید</option>
                @foreach (\App\Support\EyeSide::options() as $side)
                    <option value="{{ $side['value'] }}">{{ $side['label'] }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('eye_side')" />
        </div>
        <div>
            <x-input-label for="surgeon_name" value="نام جراح" />
            <x-text-input id="surgeon_name" name="surgeon_name" type="text" class="mt-1 block w-full" :value="old('surgeon_name', auth()->user()?->role === 'doctor' ? auth()->user()->name : '')" />
        </div>
    </div>
    @include('surgery-appointments.partials.cooldown-warn')
    <div>
        <x-input-label for="notes" value="توضیحات / آمادگی پیش از عمل" />
        <textarea id="notes" name="notes" rows="3" class="field-input">{{ old('notes') }}</textarea>
    </div>
</div>

@if ($showUploads)
<div class="border-t pt-5 space-y-4" style="border-color: var(--line);">
    <h3 class="text-sm font-bold" style="color: var(--ink);">پیوست‌ها (مستقیم در پرونده بیمار)</h3>
    <div x-data="clinicAttachPicker()" @paste.capture="onPaste($event)">
        <x-input-label value="عکس‌های عمل / مدارک تصویری" />
        <input type="file" name="photos[]" accept="image/*" multiple class="sr-only" x-ref="files" tabindex="-1" aria-hidden="true">
        <input type="file" accept="image/*" capture="environment" multiple class="sr-only" x-ref="camera" @change="pick($event, 'camera')">
        <input type="file" accept="image/*" multiple class="sr-only" x-ref="gallery" @change="pick($event, 'gallery')">

        <div class="doc-upload-sources mt-2">
            <button type="button" class="doc-upload-source" @click="$refs.camera.click()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 8.5A2.5 2.5 0 016.5 6h2l1.2-1.6A1.5 1.5 0 0110.9 4h2.2a1.5 1.5 0 011.2.6L15.5 6h2A2.5 2.5 0 0120 8.5v9A2.5 2.5 0 0117.5 20h-11A2.5 2.5 0 014 17.5v-9z"/>
                    <circle cx="12" cy="13" r="3.25"/>
                </svg>
                <span class="doc-upload-source__title">دوربین</span>
                <span class="doc-upload-source__hint">عکس بگیرید</span>
            </button>
            <button type="button" class="doc-upload-source" @click="$refs.gallery.click()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <rect x="3.5" y="5" width="17" height="14" rx="2"/>
                    <circle cx="9" cy="10.5" r="1.5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 17l3.5-3.5L13 16l2-2 3.5 3"/>
                </svg>
                <span class="doc-upload-source__title">گالری</span>
                <span class="doc-upload-source__hint">از گوشی یا Ctrl+V</span>
            </button>
        </div>

        <div class="doc-upload-preview mt-3" x-show="previews.length" x-cloak>
            <template x-for="(item, index) in previews" :key="item.url + index">
                <img :src="item.url" :alt="item.name" class="doc-upload-preview__img">
            </template>
            <div class="doc-upload-preview__meta">
                <strong x-text="label"></strong>
                <span x-show="count > 1" x-text="count + ' عکس آماده'"></span>
                <button type="button" class="doc-upload-preview__clear" @click="clearFiles()">حذف</button>
            </div>
        </div>
        <p class="mt-1 text-[11px]" style="color:var(--muted)">چند عکس همزمان — پس از ثبت در گالری پرونده بیمار دیده می‌شوند.</p>
        <p class="mt-1 text-xs text-rose-600" x-show="error" x-text="error" x-cloak></p>
        <x-input-error class="mt-2" :messages="$errors->get('photos')" />
        <x-input-error class="mt-2" :messages="$errors->get('photos.*')" />
    </div>
    <div>
        <x-input-label for="documents" value="مدارک دیگر (تصویر یا PDF)" />
        <input id="documents" type="file" name="documents[]" accept="image/*,.pdf,application/pdf" multiple class="field-input mt-1">
    </div>
</div>
@endif
