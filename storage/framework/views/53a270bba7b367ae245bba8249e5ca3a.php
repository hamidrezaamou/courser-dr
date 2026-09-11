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
    <div
        class="py-4 sm:py-6"
        x-data="surgeryBookingForm({
            optionsUrl: <?php echo \Illuminate\Support\Js::from(route('appointments.surgery-options'))->toHtml() ?>,
            slotsUrl: <?php echo \Illuminate\Support\Js::from(route('appointments.slots'))->toHtml() ?>,
            hospitals: <?php echo \Illuminate\Support\Js::from(($hospitals ?? collect())->map(fn ($h) => ['id' => $h->id, 'name' => $h->name])->values())->toHtml() ?>,
            initialDate: <?php echo \Illuminate\Support\Js::from(old('scheduled_date'))->toHtml() ?>,
            initialTime: <?php echo \Illuminate\Support\Js::from(old('scheduled_time'))->toHtml() ?>,
            initialHospitalId: <?php echo \Illuminate\Support\Js::from(old('hospital_id'))->toHtml() ?>,
            initialTypeId: <?php echo \Illuminate\Support\Js::from(old('surgery_type_id'))->toHtml() ?>,
            initialSubtypeId: <?php echo \Illuminate\Support\Js::from(old('surgery_subtype_id'))->toHtml() ?>,
            initialTypeLabel: <?php echo \Illuminate\Support\Js::from(old('surgery_type'))->toHtml() ?>,
            initialException: <?php echo \Illuminate\Support\Js::from((bool) old('is_exception'))->toHtml() ?>,
        })"
    >
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form
                method="POST"
                action="<?php echo e(route('surgery-appointments.store', $patient)); ?>"
                enctype="multipart/form-data"
                class="panel fade-up space-y-5 p-4 sm:p-6"
                @submit="prepareSubmit($event)"
            >
                <?php echo csrf_field(); ?>

                <?php echo $__env->make('surgery-appointments.partials.form-fields', [
                    'patient' => $patient,
                    'showUploads' => true,
                    'dateAsSelect' => true,
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn-primary btn-accent-warm">ثبت نوبت عمل</button>
                    <a href="<?php echo e(route('patients.show', $patient)); ?>" class="btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>

    <?php echo $__env->make('surgery-appointments.partials.booking-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\surgery-appointments\create.blade.php ENDPATH**/ ?>