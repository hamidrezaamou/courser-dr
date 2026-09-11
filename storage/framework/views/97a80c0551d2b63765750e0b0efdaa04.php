<?php
    $item = $row['item'];
    $isSurgery = $row['type'] === 'surgery';
    $toolboxPayload = \App\Support\ToolboxPayload::fromBoardRow($row);
    $toolboxB64 = \App\Support\ToolboxPayload::encode($toolboxPayload);
    $time = $item->scheduled_time ? \App\Support\SlotLabel::display((string) $item->scheduled_time) : '—';
    $turnLabel = preg_match('/^نوبت\s*\d+/u', $time) ? $time : ('نوبت '.($turn ?? 1));
    $statusClass = match ($item->status) {
        'confirmed' => 'is-confirmed',
        'waiting' => 'is-queue',
        'ready' => 'is-ready',
        'in_consult' => 'is-consult',
        'cancelled' => 'is-cancelled',
        'done' => 'is-done',
        'pending_approval' => 'is-pending-approval',
        default => 'is-waiting',
    };
    $statusText = \App\Support\BookingStatus::label($item->status);
    $dateJalaliRow = jalali($item->scheduled_date, 'Y/m/d');
    $cardId = ($isSurgery ? 'surgery' : 'visit').'-'.$item->id;
    if ($isSurgery) {
        $place = $item->hospital?->name ?: '—';
        $kindLine = trim(($item->surgery_type ?: 'عمل').($item->eye_side ? ' · '.$item->eye_side : ''));
        $meta = trim($kindLine.' · '.$place.' · '.$dateJalaliRow.' '.$time);
        $smsBody = \App\Support\AppointmentSms::forItem($item, 'surgery');
        $editUrl = route('surgery-appointments.edit', $item);
        $printUrl = route('surgery-appointments.prints', $item);
        $typeLabel = 'surgery';
        $typeBadge = 'عمل';
    } else {
        $place = 'کلینیک';
        $kindLine = trim(($item->visit_type ?: 'ویزیت').($item->reason ? ' · '.$item->reason : ''));
        $meta = trim($kindLine.' · '.$dateJalaliRow.' '.$time);
        $smsBody = \App\Support\AppointmentSms::forItem($item, 'visit');
        $editUrl = route('appointments.edit', $item);
        $printUrl = null;
        $typeLabel = 'visit';
        $typeBadge = 'ویزیت';
    }
    $digits = preg_replace('/\D+/', '', (string) $item->mobile) ?? '';
    if (str_starts_with($digits, '98') && strlen($digits) === 12) {
        $digits = '0'.substr($digits, 2);
    }
    if (str_starts_with($digits, '9') && strlen($digits) === 10) {
        $digits = '0'.$digits;
    }
    $telHref = preg_match('/^09\d{9}$/', $digits) ? 'tel:+98'.substr($digits, 1) : null;
    $typeIds = $isSurgery ? \App\Support\SurgeryChecklist::resolveTypeIds($item) : ['type_id' => null, 'subtype_id' => null];
    $hasChecklist = $isSurgery && \App\Support\SurgeryChecklist::isAvailable();
    $billingEnabled = \App\Support\FeatureFlags::enabled('features.billing_insurance');
    $billingRecord = $billingEnabled ? \App\Support\ModuleFinance::billingFor($item) : null;
    $consentEnabled = \App\Support\FeatureFlags::enabled('features.consent_forms');
    $isPendingApproval = ! $isSurgery && $item->status === \App\Support\BookingStatus::PENDING_APPROVAL;
?>
<article class="board-card <?php echo e($statusClass); ?>" :class="{ 'is-open': isBoardCardOpen(<?php echo \Illuminate\Support\Js::from($cardId)->toHtml() ?>) }">
    <div class="board-card__turn" title="<?php echo e($time); ?>">
        <span><?php echo e($turnLabel); ?></span>
    </div>

    <div class="board-card__shell">
        <div class="board-card__bar" role="button" tabindex="0"
             @click="toggleBoardCard(<?php echo \Illuminate\Support\Js::from($cardId)->toHtml() ?>)"
             @keydown.enter.prevent="toggleBoardCard(<?php echo \Illuminate\Support\Js::from($cardId)->toHtml() ?>)"
             @keydown.space.prevent="toggleBoardCard(<?php echo \Illuminate\Support\Js::from($cardId)->toHtml() ?>)"
             :aria-expanded="isBoardCardOpen(<?php echo \Illuminate\Support\Js::from($cardId)->toHtml() ?>) ? 'true' : 'false'">
            <div class="board-card__bar-main">
                <span role="button"
                      tabindex="0"
                      class="board-card__name board-card__name--toolbox"
                      data-toolbox-b64="<?php echo e($toolboxB64); ?>"
                      @click.stop><?php echo e($item->patient_name); ?></span>
                <p class="board-card__kind"><?php echo e($kindLine); ?></p>
            </div>
            <div class="board-card__bar-side">
                <span class="board-card__type"><?php echo e($typeBadge); ?></span>
                <span class="board-card__badge"><?php echo e($statusText); ?></span>
                <span class="board-card__bar-when" dir="ltr"><?php echo e($dateJalaliRow); ?> · <?php echo e($time); ?></span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="board-card__chevron" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                </svg>
            </div>
        </div>

        <div class="board-card__drawer" x-show="isBoardCardOpen(<?php echo \Illuminate\Support\Js::from($cardId)->toHtml() ?>)" x-cloak>
            <div class="board-card__body">
                <div class="board-card__identity">
                    <p class="board-card__place"><?php echo e($place); ?></p>
                    <p class="board-card__id ltr-data"><span dir="ltr"><?php echo e($item->national_code); ?></span></p>
                </div>

                <div class="board-card__mid">
                    <p class="board-card__date" dir="ltr"><?php echo e($dateJalaliRow); ?></p>
                    <p class="board-card__slot" dir="ltr"><?php echo e($time); ?></p>
                    <?php if($telHref): ?>
                        <a href="<?php echo e($telHref); ?>" class="board-card__phone" dir="ltr">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                            </svg>
                            <?php echo e($item->mobile); ?>

                        </a>
                    <?php else: ?>
                        <span class="board-card__phone is-muted" dir="ltr"><?php echo e($item->mobile ?: '—'); ?></span>
                    <?php endif; ?>
                </div>

                <div class="board-card__actions">
                    <?php if($isPendingApproval && \App\Support\FeatureFlags::enabled('features.online_booking_approval')): ?>
                        <div class="flex flex-wrap gap-1 mb-2 w-full">
                            <form method="POST" action="<?php echo e(route('modules.approval.approve', $item)); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn-primary !px-2 !py-1 !text-[10px]">تأیید آنلاین</button>
                            </form>
                            <form method="POST" action="<?php echo e(route('modules.approval.reject', $item)); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn-secondary !px-2 !py-1 !text-[10px]">رد</button>
                            </form>
                        </div>
                    <?php endif; ?>
                    <?php if($billingEnabled): ?>
                        <div class="mb-2 w-full text-[10px]" style="color: var(--muted);">
                            <?php if($billingRecord): ?>
                                صورتحساب: <?php echo e($billingRecord->tariff?->name ?? '—'); ?> ·
                                <?php echo e(match($billingRecord->settlement_status) { 'paid' => 'تسویه', 'partial' => 'جزئی', default => 'باز' }); ?>

                            <?php else: ?>
                                صورتحساب ثبت نشده
                            <?php endif; ?>
                            <form method="POST" action="<?php echo e(route('modules.billing.from-billable')); ?>" class="mt-1">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="billable_type" value="<?php echo e($isSurgery ? 'surgery' : 'visit'); ?>">
                                <input type="hidden" name="billable_id" value="<?php echo e($item->id); ?>">
                                <button type="submit" class="btn-secondary !px-2 !py-1 !text-[10px]">ثبت/به‌روز صورتحساب</button>
                            </form>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal8d75880d81198efb280b50e0bab6c1de = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8d75880d81198efb280b50e0bab6c1de = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.booking-status-actions','data' => ['model' => $item,'type' => $typeLabel,'variant' => 'board','showStatus' => false,'patientUrl' => route('patients.show', $item->patient_id)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('booking-status-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item),'type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($typeLabel),'variant' => 'board','show-status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'patient-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('patients.show', $item->patient_id))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8d75880d81198efb280b50e0bab6c1de)): ?>
<?php $attributes = $__attributesOriginal8d75880d81198efb280b50e0bab6c1de; ?>
<?php unset($__attributesOriginal8d75880d81198efb280b50e0bab6c1de); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8d75880d81198efb280b50e0bab6c1de)): ?>
<?php $component = $__componentOriginal8d75880d81198efb280b50e0bab6c1de; ?>
<?php unset($__componentOriginal8d75880d81198efb280b50e0bab6c1de); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginala024c575d9bc5bd40fe3d84386fcd6a3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala024c575d9bc5bd40fe3d84386fcd6a3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.row-toolbox','data' => ['name' => $item->patient_name,'mobile' => $item->mobile,'nationalCode' => $item->national_code,'meta' => $meta,'patientUrl' => route('patients.show', $item->patient_id),'editUrl' => $editUrl,'printUrl' => $printUrl,'smsBody' => $smsBody,'subjectType' => $typeLabel,'subjectId' => $item->id,'patientId' => $item->patient_id,'dateLabel' => $dateJalaliRow,'surgeryAppointmentId' => $isSurgery ? $item->id : null,'surgeryTypeId' => $typeIds['type_id'] ?? null,'surgerySubtypeId' => $typeIds['subtype_id'] ?? null,'hasSurgeryChecklist' => $hasChecklist,'triggerLabel' => 'ابزار','class' => 'board-toolbox-trigger']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('row-toolbox'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->patient_name),'mobile' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->mobile),'national-code' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->national_code),'meta' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($meta),'patient-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('patients.show', $item->patient_id)),'edit-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($editUrl),'print-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($printUrl),'sms-body' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($smsBody),'subject-type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($typeLabel),'subject-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->id),'patient-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item->patient_id),'date-label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($dateJalaliRow),'surgery-appointment-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isSurgery ? $item->id : null),'surgery-type-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($typeIds['type_id'] ?? null),'surgery-subtype-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($typeIds['subtype_id'] ?? null),'has-surgery-checklist' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hasChecklist),'trigger-label' => 'ابزار','class' => 'board-toolbox-trigger']); ?>
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
                    <?php if($consentEnabled && $isSurgery): ?>
                        <a href="<?php echo e(route('patients.show', $item->patient_id)); ?>?open=consent&surgery=<?php echo e($item->id); ?>" class="btn-secondary mt-2 w-full !py-1.5 !text-[10px] text-center">رضایت عمل</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</article>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\appointments\partials\board-card.blade.php ENDPATH**/ ?>