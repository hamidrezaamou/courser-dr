<div class="space-y-4">
    <div class="tg-side__card text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full text-xl font-extrabold text-white"
             style="background: linear-gradient(145deg, var(--brand), var(--brand-dark));">
            <?php echo e(mb_substr($patient->name, 0, 1)); ?>

        </div>
        <h2 class="mt-2 text-base font-bold" style="color: var(--ink);"><?php echo e($patient->name); ?></h2>
        <p class="mt-0.5 text-[11px]" style="color: var(--muted);">پرونده الکترونیک</p>

        <?php if($isStaff): ?>
            <div class="tg-attach-row tg-attach-row--4 mt-4">
                <button type="button" class="tg-attach-btn" @click="openMedia('photos')">
                    <span class="tg-attach-btn__icon is-photo" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="5" width="16" height="14" rx="2"/><circle cx="9" cy="10" r="1.5"/><path stroke-linecap="round" d="M7 17l3.5-4 2.5 2.5L15 13l4 4"/></svg>
                    </span>
                    <span class="tg-attach-btn__label">عکس بیمار</span>
                    <span class="tg-attach-btn__count"><?php echo e($docCount); ?></span>
                </button>
                <button type="button" class="tg-attach-btn" @click="openMedia('drawings')">
                    <span class="tg-attach-btn__icon is-draw" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.042.806a1.125 1.125 0 01-1.37-1.37l.806-2.042a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>
                    </span>
                    <span class="tg-attach-btn__label">وایت‌برد</span>
                    <span class="tg-attach-btn__count"><?php echo e($drawingCount); ?></span>
                </button>
                <button type="button" class="tg-attach-btn" @click="openMedia('exams')">
                    <span class="tg-attach-btn__icon is-exam" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </span>
                    <span class="tg-attach-btn__label">معاینه</span>
                    <span class="tg-attach-btn__count"><?php echo e($examVisits->count()); ?></span>
                </button>
                <?php if($canClinical): ?>
                <button type="button" class="tg-attach-btn" @click="openPanel('prescription')">
                    <span class="tg-attach-btn__icon is-rx" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
                    </span>
                    <span class="tg-attach-btn__label">دارو</span>
                    <span class="tg-attach-btn__count"><?php echo e($rxCount); ?></span>
                </button>
                <?php endif; ?>
            </div>

            <div class="mt-3 flex flex-wrap justify-center gap-2">
                <?php if (isset($component)) { $__componentOriginala024c575d9bc5bd40fe3d84386fcd6a3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala024c575d9bc5bd40fe3d84386fcd6a3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.row-toolbox','data' => ['name' => $patient->name,'mobile' => $patient->mobile,'nationalCode' => $patient->national_code,'meta' => 'پرونده بیمار','patientUrl' => route('patients.show', $patient),'sheetMode' => 'patient','canClinical' => $canClinical,'triggerLabel' => 'ابزار','class' => '!min-h-9 !px-3 !text-xs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('row-toolbox'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($patient->name),'mobile' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($patient->mobile),'national-code' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($patient->national_code),'meta' => 'پرونده بیمار','patient-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('patients.show', $patient)),'sheet-mode' => 'patient','can-clinical' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($canClinical),'trigger-label' => 'ابزار','class' => '!min-h-9 !px-3 !text-xs']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala024c575d9bc5bd40fe3d84386fcd6a3)): ?>
<?php $attributes = $__attributesOriginala024c575d9bc5bd40fe3d84386fcd6a3; ?>
<?php unset($__attributesOriginala024c575d9bc5bd40fe3d84386fcd6a3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala024c575d9bc5bd40fe3d84386fcd6a3)): ?>
<?php $component = $__componentOriginala024c575d9bc5bd40fe3d84386fcd6a3; ?>
<?php unset($__componentOriginala024c575d9bc5bd40fe3d84386fcd6a3); ?>
<?php endif; ?>
                <a href="<?php echo e(route('surgery-appointments.create', $patient)); ?>" class="btn-secondary !min-h-9 !px-3 !text-xs">نوبت عمل</a>
                <a href="<?php echo e(route('appointments.create', $patient)); ?>" class="btn-secondary !min-h-9 !px-3 !text-xs">ویزیت</a>
            </div>

            <?php if (isset($component)) { $__componentOriginaled60e996035c2b38ad3a5ffc286154b1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaled60e996035c2b38ad3a5ffc286154b1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.answer-launcher','data' => ['mobile' => $patient->mobile,'patientName' => $patient->name,'label' => 'ارسال پاسخ آماده','class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('answer-launcher'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mobile' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($patient->mobile),'patient-name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($patient->name),'label' => 'ارسال پاسخ آماده','class' => 'mt-2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaled60e996035c2b38ad3a5ffc286154b1)): ?>
<?php $attributes = $__attributesOriginaled60e996035c2b38ad3a5ffc286154b1; ?>
<?php unset($__attributesOriginaled60e996035c2b38ad3a5ffc286154b1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaled60e996035c2b38ad3a5ffc286154b1)): ?>
<?php $component = $__componentOriginaled60e996035c2b38ad3a5ffc286154b1; ?>
<?php unset($__componentOriginaled60e996035c2b38ad3a5ffc286154b1); ?>
<?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="tg-side__card space-y-3 text-sm">
        <div class="flex items-center justify-between gap-2">
            <span style="color: var(--muted);">کد ملی</span>
            <span class="font-mono font-semibold" style="color: var(--ink);" dir="ltr"><?php echo e($patient->national_code); ?></span>
        </div>
        <div class="flex items-center justify-between gap-2">
            <span style="color: var(--muted);">موبایل</span>
            <span class="font-mono font-semibold" style="color: var(--ink);" dir="ltr"><?php echo e($patient->mobile); ?></span>
        </div>
        <?php if($patient->age): ?>
            <div class="flex items-center justify-between gap-2">
                <span style="color: var(--muted);">سن</span>
                <span class="font-semibold" style="color: var(--ink);"><?php echo e($patient->age); ?> سال</span>
            </div>
        <?php endif; ?>
        <?php if($isStaff && \App\Support\FeatureFlags::enabled('features.accounting')): ?>
            <?php $patientBalance = \App\Support\PatientFinance::balance($patient); ?>
            <div class="flex items-center justify-between gap-2">
                <span style="color: var(--muted);">مانده حساب</span>
                <span class="font-mono font-bold <?php echo e($patientBalance > 0 ? 'text-red-600' : ''); ?>" style="<?php echo e($patientBalance <= 0 ? 'color: var(--ink);' : ''); ?>" dir="ltr">
                    <?php echo e(number_format($patientBalance)); ?>

                </span>
            </div>
        <?php endif; ?>
        <?php if(!$isStaff && \App\Support\FeatureFlags::enabled('features.patient_portal')): ?>
            <div class="rounded-lg border px-2.5 py-2 text-[11px]" style="border-color: var(--line); background: var(--panel-soft); color: var(--muted);">
                پرتال بیمار فعال است — نوبت‌ها و مدارک قابل مشاهده در همین پرونده.
            </div>
        <?php endif; ?>
    </div>

    <?php if($isStaff && \App\Support\FeatureFlags::enabled('features.eye_chart') && $examVisits->isNotEmpty()): ?>
    <div class="tg-side__card">
        <a href="<?php echo e(route('modules.eye-chart.index', ['patient' => $patient->id])); ?>" class="btn-secondary w-full !py-2 !text-xs text-center">
            جدول بینایی این بیمار
        </a>
    </div>
    <?php endif; ?>

    <?php if($isStaff && auth()->user()?->isAdmin() && ! str_starts_with((string) $patient->national_code, 'DEL-')): ?>
    <div class="tg-side__card space-y-2">
        <h3 class="text-sm font-bold text-red-700">حذف امن پرونده</h3>
        <p class="text-[11px] leading-5" style="color: var(--muted);">هویت ناشناس می‌شود، فایل‌های بالینی پاک می‌شوند، نوبت‌ها برای آمار می‌مانند. غیرقابل بازگشت.</p>
        <form method="POST" action="<?php echo e(route('patients.secure-erase', $patient)); ?>" onsubmit="return confirm('حذف امن قطعی است. ادامه؟')">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
            <input type="text" name="confirm_name" class="field-input w-full !text-xs" placeholder="نام بیمار را برای تأیید بنویسید" required>
            <button type="submit" class="btn-secondary mt-2 w-full !py-1.5 !text-xs text-red-700">اجرای حذف امن</button>
        </form>
    </div>
    <?php endif; ?>

    <?php echo $__env->make('patients.partials.consent-panel', compact('patient', 'isStaff', 'canClinical'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php
        $bookingHistory = collect()
            ->merge($patient->appointments->map(fn ($a) => ['type' => 'visit', 'item' => $a, 'date' => optional($a->scheduled_date)->timestamp ?? 0]))
            ->merge($patient->surgeryAppointments->map(fn ($s) => ['type' => 'surgery', 'item' => $s, 'date' => optional($s->scheduled_date)->timestamp ?? 0]))
            ->sortByDesc('date')
            ->take(12)
            ->values();
    ?>

    <div class="tg-side__card space-y-2" x-data="{ openId: null }">
        <h3 class="text-sm font-bold" style="color: var(--ink);">تاریخچه نوبت‌ها</h3>
        <div class="tg-side__history space-y-2">
        <?php $__empty_1 = true; $__currentLoopData = $bookingHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $item = $row['item'];
                $rowKey = $row['type'].'-'.$item->id;
            ?>
            <div class="rounded-xl border text-xs overflow-hidden" style="border-color: var(--line); background: var(--panel-soft);">
                <button type="button" class="flex w-full items-center justify-between gap-2 px-3 py-2 text-right"
                        @click="openId = openId === <?php echo \Illuminate\Support\Js::from($rowKey)->toHtml() ?> ? null : <?php echo \Illuminate\Support\Js::from($rowKey)->toHtml() ?>">
                    <span class="flex min-w-0 flex-1 items-center gap-2">
                        <span class="font-bold shrink-0" style="<?php echo e($row['type'] === 'surgery' ? 'color:#c2410c' : 'color:var(--brand-dark)'); ?>">
                            <?php echo e($row['type'] === 'surgery' ? 'عمل' : 'ویزیت'); ?>

                        </span>
                        <span class="truncate" style="color: var(--muted);">
                            <?php echo e($row['type'] === 'surgery' ? ($item->surgery_type ?: 'عمل') : ($item->visit_type ?: 'ویزیت')); ?>

                        </span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 shrink-0">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold"
                              style="background:var(--panel);color:var(--ink)"><?php echo e($item->statusLabel()); ?></span>
                        <svg class="h-3.5 w-3.5 transition" style="color:var(--muted)" :class="openId === <?php echo \Illuminate\Support\Js::from($rowKey)->toHtml() ?> && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </span>
                </button>
                <div x-show="openId === <?php echo \Illuminate\Support\Js::from($rowKey)->toHtml() ?>" x-cloak class="border-t px-3 py-2 space-y-1" style="border-color: var(--line); color: var(--muted);">
                    <div class="flex items-center justify-between gap-2">
                        <span>تاریخ</span>
                        <span class="font-mono" dir="ltr"><?php echo e(jalali($item->scheduled_date, 'Y/m/d')); ?></span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span>ساعت/نوبت</span>
                        <span><?php echo e($item->scheduled_time ? \App\Support\SlotLabel::display((string) $item->scheduled_time) : '—'); ?></span>
                    </div>
                    <?php if($row['type'] === 'surgery' && $item->hospital): ?>
                        <div class="flex items-center justify-between gap-2">
                            <span>بیمارستان</span>
                            <span><?php echo e($item->hospital->name); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="text-xs" style="color: var(--muted);">نوبتی ثبت نشده است.</p>
        <?php endif; ?>
        </div>
    </div>

    <?php if($isStaff && \App\Support\FeatureFlags::enabled('features.followup_reminders')): ?>
    <div class="tg-side__card space-y-2">
        <div class="flex items-center justify-between gap-2">
            <h3 class="text-sm font-bold" style="color: var(--ink);">مراجعه‌های بعدی</h3>
            <span class="text-[11px] font-bold" style="color: var(--muted);"><?php echo e($pendingFollowUps->count()); ?></span>
        </div>
        <div class="space-y-2 max-h-48 overflow-auto">
            <?php $__empty_1 = true; $__currentLoopData = $pendingFollowUps->take(12); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="rounded-lg border px-2.5 py-2 text-[11px]" style="border-color: var(--line); background: var(--panel-soft);">
                    <div class="font-bold" style="color: var(--ink);" dir="ltr"><?php echo e(jalali($rem->due_date, 'Y/m/d')); ?></div>
                    <div style="color: var(--muted);">یادآوری: <?php echo e(jalali($rem->remind_at, 'Y/m/d')); ?></div>
                    <form method="POST" action="<?php echo e(route('followups.destroy', [$patient, $rem])); ?>" class="mt-1">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="text-[10px] font-bold text-red-600">لغو</button>
                    </form>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-xs" style="color: var(--muted);">موردی ثبت نشده.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\patients\partials\profile-sidebar.blade.php ENDPATH**/ ?>