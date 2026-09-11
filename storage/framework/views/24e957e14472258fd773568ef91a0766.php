<?php
    $monthNames = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
?>

<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div class="page-header--compact flex items-center justify-between gap-3">
            <h2 class="page-title">تایم‌های کاری</h2>
            <a href="<?php echo e(route('dashboard')); ?>" class="btn-ghost hidden sm:inline-flex">داشبورد</a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="settings-page-body" x-data="timeScheduler(
        <?php echo \Illuminate\Support\Js::from($kind)->toHtml() ?>,
        <?php echo \Illuminate\Support\Js::from($selectedHospitalId)->toHtml() ?>,
        <?php echo \Illuminate\Support\Js::from($selectedTypeId)->toHtml() ?>,
        <?php echo \Illuminate\Support\Js::from($selectedSubtypeId)->toHtml() ?>,
        <?php echo \Illuminate\Support\Js::from($hospitals->map(fn ($h) => ['id' => $h->id, 'name' => $h->name])->values())->toHtml() ?>,
        <?php echo \Illuminate\Support\Js::from($surgeryTypes->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'subtypes' => $t->subtypes->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
        ])->values())->toHtml() ?>,
        <?php echo \Illuminate\Support\Js::from($surgerySchedulesPayload)->toHtml() ?>,
        <?php echo \Illuminate\Support\Js::from($monthNames)->toHtml() ?>,
        <?php echo \Illuminate\Support\Js::from($smsPresets)->toHtml() ?>,
        <?php echo \Illuminate\Support\Js::from($smsDefaultVisit)->toHtml() ?>,
        <?php echo \Illuminate\Support\Js::from($smsDefaultSurgery)->toHtml() ?>
    )">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            <?php if (isset($component)) { $__componentOriginal8a7b4fd4c5e3c720c3975a07cc89c510 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8a7b4fd4c5e3c720c3975a07cc89c510 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.settings-dock','data' => ['settingsSection' => 'times']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('settings-dock'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['settings-section' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('times')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8a7b4fd4c5e3c720c3975a07cc89c510)): ?>
<?php $attributes = $__attributesOriginal8a7b4fd4c5e3c720c3975a07cc89c510; ?>
<?php unset($__attributesOriginal8a7b4fd4c5e3c720c3975a07cc89c510); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8a7b4fd4c5e3c720c3975a07cc89c510)): ?>
<?php $component = $__componentOriginal8a7b4fd4c5e3c720c3975a07cc89c510; ?>
<?php unset($__componentOriginal8a7b4fd4c5e3c720c3975a07cc89c510); ?>
<?php endif; ?>
            <?php if(auth()->user()->canManageSettings()): ?>
                <div class="mb-3">
                    <a href="<?php echo e(route('admin.index')); ?>" class="admin-panel__link">← بازگشت به مدیریت کل سایت</a>
                </div>
            <?php endif; ?>

            <?php if (isset($component)) { $__componentOriginal5168fdb0c14fd91c6598264bc4be63f2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.flash','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flash'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2)): ?>
<?php $attributes = $__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2; ?>
<?php unset($__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5168fdb0c14fd91c6598264bc4be63f2)): ?>
<?php $component = $__componentOriginal5168fdb0c14fd91c6598264bc4be63f2; ?>
<?php unset($__componentOriginal5168fdb0c14fd91c6598264bc4be63f2); ?>
<?php endif; ?>

            
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
                            <span x-show="selectedKind === 'visit'"><?php echo e($visitSchedules->count()); ?> روز</span>
                            <span x-show="selectedKind === 'surgery'" x-cloak x-text="filteredSurgeryDays.length + ' روز'"></span>
                        </span>
                    </div>

                    <div class="kind-panels" dir="ltr">
                        <div class="kind-panels__slider" :style="selectedKind === 'surgery' ? 'transform: translate3d(-50%,0,0)' : 'transform: translate3d(0,0,0)'">
                            <div class="kind-panels__pane p-5" dir="rtl">
                                <?php if($visitGrouped->isEmpty()): ?>
                                    <div class="pp-empty">هنوز روزی برای ویزیت ثبت نشده است.</div>
                                <?php else: ?>
                                    <?php echo $__env->make('times.partials.day-cards', ['grouped' => $visitGrouped, 'bookedCounts' => $visitBookedCounts, 'monthNames' => $monthNames], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php endif; ?>
                            </div>
                            <div class="kind-panels__pane p-5" dir="rtl">
                                <?php if($hospitals->isEmpty()): ?>
                                    <div class="pp-empty">ابتدا یک بیمارستان از منوی «بیمارستان‌ها» اضافه کنید.</div>
                                <?php else: ?>
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
                                                        <div class="min-w-[7.5rem] flex-1 rounded-2xl border p-3 text-center sm:min-w-[96px] sm:flex-none"
                                                             :class="day.booked >= day.total && day.total > 0 ? 'border-red-200 bg-red-50' : 'border-emerald-200 bg-emerald-50/70'">
                                                            <div class="text-[11px]" style="color: var(--muted);" x-text="day.weekday"></div>
                                                            <div class="text-lg font-extrabold" style="color: var(--ink);" x-text="day.day"></div>
                                                            <div class="mt-1 text-[10px] leading-tight" style="color:var(--muted)" x-show="day.type_name">
                                                                <span x-text="day.type_name"></span>
                                                                <span x-text="' · ' + (day.subtype_name || 'عمومی')"></span>
                                                            </div>
                                                            <div class="mt-1 text-[10px] font-semibold"
                                                                 :class="day.booked >= day.total && day.total > 0 ? 'text-red-700' : 'text-emerald-700'"
                                                                 x-text="(day.booked >= day.total && day.total > 0) ? 'تکمیل' : (day.free + ' خالی از ' + day.total)"></div>
                                                            <div class="mt-1 text-[10px] font-bold" style="color: var(--brand);" x-text="day.times_count + ' تایم'"></div>
                                                            <details class="times-sms-edit mt-1 text-right" @click.stop>
                                                                <summary class="times-sms-edit__toggle">✉️ پیامک</summary>
                                                                <form :action="day.update_sms_url" method="POST" class="times-sms-edit__form" @submit.stop>
                                                                    <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                                                                    <input type="hidden" name="_method" value="PATCH">
                                                                    <textarea name="sms_text" rows="3" class="field-input !text-[11px] !py-1.5" maxlength="1000" :value="day.sms_text || ''"></textarea>
                                                                    <button type="submit" class="times-sms-edit__save">ذخیره پیامک</button>
                                                                </form>
                                                            </details>
                                                            <form method="POST" :action="day.delete_url" class="mt-2" @submit="if (!confirm('حذف این روز؟')) $event.preventDefault()">
                                                                <?php echo csrf_field(); ?>
                                                                <?php echo method_field('DELETE'); ?>
                                                                <button type="submit" class="text-[11px] font-bold text-red-600">حذف</button>
                                                            </form>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                <?php endif; ?>
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

                    <form method="POST" :action="bulkMode ? <?php echo \Illuminate\Support\Js::from(route('times.bulk-store'))->toHtml() ?> : <?php echo \Illuminate\Support\Js::from(route('times.store'))->toHtml() ?>" @submit="prepareSubmit($event)">
                        <?php echo csrf_field(); ?>
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
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'تاریخ شمسی']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'تاریخ شمسی']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                            <input type="text" class="field-input cursor-pointer" placeholder="برای انتخاب کلیک کنید" readonly required
                                   x-model="dateKey" @click.prevent="openCalendar = true" @keydown.enter.prevent="openCalendar = true">
                            <p class="mt-1 text-[11px]" style="color: var(--muted);" x-text="displayText || 'روز حضور را از تقویم انتخاب کنید'"></p>
                            <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['class' => 'mt-2','messages' => $errors->get('date_key')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-2','messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('date_key'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['class' => 'mt-2','messages' => $errors->get('hospital_id')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-2','messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('hospital_id'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['class' => 'mt-2','messages' => $errors->get('surgery_type_id')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-2','messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('surgery_type_id'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['class' => 'mt-2','messages' => $errors->get('surgery_subtype_id')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-2','messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('surgery_subtype_id'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                        </div>

                        <div class="mb-4" x-show="bulkMode" x-cloak>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'انتخاب روزها از تقویم']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'انتخاب روزها از تقویم']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                            <button type="button" class="field-input cursor-pointer text-right" @click.prevent="openCalendar = true">
                                <span x-text="selectedDays.length ? (selectedDays.length + ' روز انتخاب شده — ویرایش') : 'باز کردن تقویم و انتخاب روزها'"></span>
                            </button>
                        </div>

                        <div class="mb-4">
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'نوع نوبت‌دهی']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'نوع نوبت‌دهی']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
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
                                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'total_slots','value' => 'ظرفیت']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'total_slots','value' => 'ظرفیت']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                                <input id="total_slots" type="number" name="total_slots" min="1" max="100" class="field-input" x-model="totalSlots" @change="slotMode === 'queue' && syncQueueText()">
                            </div>
                            <div>
                                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'slot_prefix','value' => 'پیشوند']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'slot_prefix','value' => 'پیشوند']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                                <input id="slot_prefix" type="text" name="slot_prefix" class="field-input" x-model="slotPrefix">
                            </div>
                        </div>

                        <div class="mb-3" x-show="slotMode === 'queue'" x-cloak>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'تعداد نوبت (مثلاً ۱ تا ۱۰)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'تعداد نوبت (مثلاً ۱ تا ۱۰)']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                            <input type="number" name="queue_count" min="1" max="99" class="field-input mt-1" x-model="queueCount" @change="syncQueueText()">
                            <p class="mt-1 text-[11px]" style="color:var(--muted)">به‌جای ساعت، نوبت ۱ تا <span x-text="queueCount"></span> ذخیره می‌شود.</p>
                        </div>

                        <div class="mb-3" x-show="slotMode === 'time'" x-cloak>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'ساعت‌های حضور (هر خط یک ساعت)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'ساعت‌های حضور (هر خط یک ساعت)']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                            <textarea name="times" rows="6" class="field-input font-mono" dir="ltr" x-model="timesText" @input="if (slotMode === 'time') savedTimeText = timesText" :required="slotMode === 'time' && !bulkMode" :disabled="slotMode === 'queue'" placeholder="08:00&#10;08:30&#10;09:00"></textarea>
                            <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['class' => 'mt-2','messages' => $errors->get('times')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-2','messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('times'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
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
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => '✉️ متن پیامک آماده']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => '✉️ متن پیامک آماده']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                            <select class="field-input mt-1.5 mb-2 text-sm" @change="applySmsPreset($event.target.value)">
                                <option value="">انتخاب از پیامک‌های آماده…</option>
                                <?php $__currentLoopData = $smsPresets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $preset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($preset['id']); ?>"><?php echo e($preset['label']); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                            <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['class' => 'mt-2','messages' => $errors->get('sms_text')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-2','messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('sms_text'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
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
        function timeScheduler(initialKind, initialHospitalId, initialTypeId, initialSubtypeId, hospitalsList, typesList, surgeryDaysList, monthNamesList, smsPresetsList, smsDefaultVisitText, smsDefaultSurgeryText) {
            const MONTH_NAMES = monthNamesList && monthNamesList.length
                ? monthNamesList
                : ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
            const WEEK_DAYS = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

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
                surgeryDays: Array.isArray(surgeryDaysList) ? surgeryDaysList : [],
                monthNames: MONTH_NAMES,
                currentJalaliMonth: <?php echo e((int) jalali(now(), 'n')); ?>,
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
                slotPrefix: initialKind === 'surgery' ? 'عمل' : 'ویزیت',
                smsPresets: Array.isArray(smsPresetsList) ? smsPresetsList : [],
                smsDefaultVisit: smsDefaultVisitText || '',
                smsDefaultSurgery: smsDefaultSurgeryText || '',
                smsText: (initialKind === 'surgery' ? smsDefaultSurgeryText : smsDefaultVisitText) || '',
                dayNames: ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'],
                init() {
                    this.$watch('openCalendar', (open) => {
                        document.documentElement.style.overflow = open ? 'hidden' : '';
                        document.body.style.overflow = open ? 'hidden' : '';
                    });
                },
                get selectedHospitalName() {
                    const h = this.hospitals.find((item) => String(item.id) === String(this.selectedHospitalId));
                    return h ? h.name : '';
                },
                get selectedTypeName() {
                    const t = this.surgeryTypes.find((item) => String(item.id) === String(this.selectedTypeId));
                    return t ? t.name : '';
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
                    const res = await fetch(<?php echo \Illuminate\Support\Js::from(route('settings.quick.hospital'))->toHtml() ?>, {
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
                    const res = await fetch(<?php echo \Illuminate\Support\Js::from(route('settings.quick.surgery-type'))->toHtml() ?>, {
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
                    const res = await fetch(<?php echo \Illuminate\Support\Js::from(route('settings.quick.surgery-subtype'))->toHtml() ?>, {
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

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\times\index.blade.php ENDPATH**/ ?>