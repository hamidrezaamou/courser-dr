<?php
    $buildPayload = function ($patient) {
        $digits = preg_replace('/\D+/', '', (string) $patient->mobile) ?? '';
        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }
        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '0'.$digits;
        }
        $telHref = preg_match('/^09\d{9}$/', $digits) ? 'tel:+98'.substr($digits, 1) : null;
        $smsHref = preg_match('/^09\d{9}$/', $digits) ? 'sms:+98'.substr($digits, 1) : null;

        return [
            'json' => json_encode([
                'sheetMode' => 'patient',
                'name' => $patient->name,
                'mobile' => $patient->mobile,
                'nationalCode' => $patient->national_code,
                'meta' => trim(
                    ($patient->upcoming_visits_count ? $patient->upcoming_visits_count.' ویزیت' : '').
                    ($patient->upcoming_visits_count && $patient->upcoming_surgeries_count ? ' · ' : '').
                    ($patient->upcoming_surgeries_count ? $patient->upcoming_surgeries_count.' عمل' : '')
                ) ?: null,
                'patientUrl' => route('patients.show', $patient),
                'surgeryUrl' => route('surgery-appointments.create', $patient),
                'visitUrl' => route('appointments.create', $patient),
                'telHref' => $telHref,
                'smsHref' => $smsHref,
                'smsEnabled' => (bool) config('reminders.sms.enabled') && config('reminders.sms.driver') === 'smsir',
                'canClinical' => auth()->check() && auth()->user()->canManageClinical(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    };
?>

<?php if($patients->isEmpty()): ?>
    <div class="patient-empty">
        <div class="patient-empty__icon">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0" />
            </svg>
        </div>
        <p class="patient-empty__title">بیماری یافت نشد</p>
        <p class="patient-empty__hint">نام، کد ملی یا موبایل دیگری امتحان کنید.</p>
    </div>
<?php else: ?>
    <div class="patient-view patient-view--cards">
        <div class="patient-grid grid grid-cols-1 gap-3 p-3 md:grid-cols-2 md:gap-4 lg:grid-cols-3 xl:grid-cols-4">
            <?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $initial = mb_substr($patient->name, 0, 1);
                    $payload = $buildPayload($patient);
                    $surgeryTotal = (int) ($patient->surgeries_count ?? 0);
                ?>
                <article class="patient-card">
                    <span class="patient-card__corner"><?php echo e($initial); ?></span>
                    <div class="patient-card__top">
                        <div class="patient-card__info">
                            <h4 class="patient-card__name"><?php echo e($patient->name); ?></h4>
                            <p class="patient-card__code ltr-data" dir="ltr"><?php echo e($patient->national_code); ?></p>
                            <p class="patient-card__phone ltr-data" dir="ltr"><?php echo e($patient->mobile); ?></p>
                        </div>
                        <div class="patient-card__avatar" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0" />
                            </svg>
                        </div>
                    </div>
                    <div class="patient-card__divider"></div>
                    <div class="patient-card__foot">
                        <div class="patient-card__actions">
                            <button type="button" class="patient-card__act patient-card__act--tool row-toolbox-trigger" title="ابزار" aria-label="ابزار" data-toolbox-trigger data-toolbox-b64="<?php echo e(base64_encode($payload['json'])); ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                                </svg>
                            </button>
                            <a href="<?php echo e(route('appointments.create', $patient)); ?>" class="patient-card__act patient-card__act--edit" title="ثبت ویزیت" aria-label="ثبت ویزیت">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L8.832 17.82a4.5 4.5 0 01-1.897 1.13l-3.096.91 1.007-3.015a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                                </svg>
                            </a>
                            <a href="<?php echo e(route('patients.show', $patient)); ?>" class="patient-card__act patient-card__act--file" title="پرونده بیمار" aria-label="پرونده بیمار">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </a>
                        </div>
                        <span class="patient-card__surgery-badge">تعداد اعمال: <?php echo e(number_format($surgeryTotal)); ?></span>
                    </div>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    <div class="patient-view patient-view--list" hidden>
        <div class="patient-list flex flex-col">
            <?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php $payload = $buildPayload($patient); ?>
                <div
                    role="button"
                    tabindex="0"
                    class="patient-row flex w-full cursor-pointer items-center gap-3 border-0 border-b px-4 py-3 text-right transition last:border-b-0"
                    style="border-color: var(--line); color: inherit;"
                    data-search="<?php echo e(strtolower($patient->name.' '.$patient->national_code.' '.$patient->mobile)); ?>"
                    data-toolbox-b64="<?php echo e(base64_encode($payload['json'])); ?>"
                >
                    <div class="patient-row__avatar flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-sm font-extrabold text-white shadow-sm"><?php echo e(mb_substr($patient->name, 0, 1)); ?></div>
                    <div class="patient-row__body min-w-0 flex-1">
                        <div class="patient-row__name truncate text-sm font-bold" style="color: var(--ink);"><?php echo e($patient->name); ?></div>
                        <div class="patient-row__meta mt-0.5 truncate text-[11px]" style="color: var(--muted);">
                            <span dir="ltr"><?php echo e($patient->national_code); ?></span>
                            <span class="patient-row__dot mx-1 opacity-50">·</span>
                            <span dir="ltr"><?php echo e($patient->mobile); ?></span>
                        </div>
                    </div>
                    <div class="patient-row__badges hidden shrink-0 flex-wrap gap-1 sm:flex">
                        <?php if($patient->upcoming_visits_count > 0): ?>
                            <span class="patient-badge patient-badge--visit rounded-full px-2 py-0.5 text-[10px] font-extrabold"><?php echo e($patient->upcoming_visits_count); ?> ویزیت</span>
                        <?php endif; ?>
                        <?php if($patient->upcoming_surgeries_count > 0): ?>
                            <span class="patient-badge patient-badge--surgery rounded-full px-2 py-0.5 text-[10px] font-extrabold"><?php echo e($patient->upcoming_surgeries_count); ?> عمل</span>
                        <?php endif; ?>
                    </div>
                    <svg class="patient-row__chevron h-4 w-4 shrink-0 opacity-50" style="color: var(--muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    <?php if($patients->hasPages()): ?>
        <div class="patient-results__pagination">
            <?php echo e($patients->links()); ?>

        </div>
    <?php endif; ?>
<?php endif; ?>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\dashboard\partials\results-body.blade.php ENDPATH**/ ?>