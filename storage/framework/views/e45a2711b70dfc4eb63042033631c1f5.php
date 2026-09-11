<?php
    [$jy, $jm] = \App\Support\Jalali::toJalali(
        (int) now()->format('Y'),
        (int) now()->format('m'),
        (int) now()->format('d')
    );
?>
<?php $__currentLoopData = $grouped; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $month => $days): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <details class="times-month" <?php if((int) $month === (int) $jm): ?> open <?php endif; ?>>
        <summary class="times-month__summary">
            <span class="times-month__title">
                <svg class="times-month__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
                </svg>
                <?php echo e($monthNames[(int) $month] ?? $month); ?>

            </span>
            <span class="times-month__count"><?php echo e($days->count()); ?> روز</span>
        </summary>
        <div class="times-month__body">
            <div class="flex flex-wrap gap-3">
                <?php $__currentLoopData = $days; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $total = $day->totalSlots();
                        $booked = $bookedCounts[$day->id] ?? 0;
                        $free = max(0, $total - $booked);
                        $full = $booked >= $total && $total > 0;
                    ?>
                    <div class="min-w-[7.5rem] flex-1 rounded-2xl border p-3 text-center sm:min-w-[96px] sm:flex-none <?php echo e($full ? 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/30' : 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-900 dark:bg-emerald-950/20'); ?>">
                        <div class="text-[11px]" style="color: var(--muted);"><?php echo e($day->weekday); ?></div>
                        <div class="text-lg font-extrabold" style="color: var(--ink);"><?php echo e($day->day); ?></div>
                        <?php if($day->surgerySubtype || $day->surgeryType): ?>
                            <div class="mt-1 text-[10px] leading-tight" style="color:var(--muted)">
                                <?php echo e($day->surgeryType?->name); ?>

                                <?php if($day->surgerySubtype): ?> · <?php echo e($day->surgerySubtype->name); ?> <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="mt-1 text-[10px] font-semibold <?php echo e($full ? 'text-red-700' : 'text-emerald-700'); ?>">
                            <?php echo e($full ? 'تکمیل' : $free.' خالی از '.$total); ?>

                        </div>
                        <div class="mt-1 text-[10px] font-bold" style="color: var(--brand);">
                            <?php echo e(count($day->times())); ?> تایم
                        </div>
                        <div onclick="event.stopPropagation()">
                            <?php echo $__env->make('times.partials.sms-edit', ['schedule' => $day], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                        <form method="POST" action="<?php echo e(route('times.destroy', $day)); ?>" class="mt-2" onsubmit="return confirm('حذف این روز؟')">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="text-[11px] font-bold text-red-600">حذف</button>
                        </form>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </details>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\times\partials\day-cards.blade.php ENDPATH**/ ?>