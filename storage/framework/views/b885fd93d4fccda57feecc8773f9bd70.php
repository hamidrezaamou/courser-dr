<?php
    $cardForms = [];
    foreach ($cards as $entry) {
        $card = $entry['card'];
        $cardForms[$card->id] = [
            'id' => $card->id,
            'title' => $card->title,
            'card_type' => $card->card_type,
            'source' => $card->source,
            'metric' => $card->metric,
            'filter_service' => $card->filter_service ?? '',
            'filter_type' => $card->filter_type ?? '',
            'partner_name' => $card->partner_name ?? '',
            'partner_percent' => $card->partner_percent,
            'range_mode' => $card->range_mode,
            'from' => $card->from_date ? jalali($card->from_date, 'Y/m/d') : $entry['result']['from'],
            'to' => $card->to_date ? jalali($card->to_date, 'Y/m/d') : $entry['result']['to'],
            'color' => $card->color,
            'text_color' => $card->text_color,
            'show_count' => $card->show_count,
            'show_avg' => $card->show_avg,
            'show_total' => $card->show_total,
        ];
    }
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
        <h2 class="page-title">حسابداری</h2>
     <?php $__env->endSlot(); ?>

    <div class="py-4 sm:py-8" x-data="accountingCards({
            tab: <?php echo \Illuminate\Support\Js::from($activeTab)->toHtml() ?>,
            sources: <?php echo \Illuminate\Support\Js::from($cardSources)->toHtml() ?>,
            options: <?php echo \Illuminate\Support\Js::from($cardFilterOptions)->toHtml() ?>,
            forms: <?php echo \Illuminate\Support\Js::from($cardForms)->toHtml() ?>,
            years: <?php echo \Illuminate\Support\Js::from(array_values($jalaliYears))->toHtml() ?>,
            today: <?php echo \Illuminate\Support\Js::from(jalali(now(), 'Y/m/d'))->toHtml() ?>,
        })">
        <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
            <?php if (isset($component)) { $__componentOriginal62b620b00b7a9e8b56ff6c9127baa696 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal62b620b00b7a9e8b56ff6c9127baa696 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modules-dock','data' => ['moduleSection' => $moduleSection]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modules-dock'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['module-section' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($moduleSection)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal62b620b00b7a9e8b56ff6c9127baa696)): ?>
<?php $attributes = $__attributesOriginal62b620b00b7a9e8b56ff6c9127baa696; ?>
<?php unset($__attributesOriginal62b620b00b7a9e8b56ff6c9127baa696); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal62b620b00b7a9e8b56ff6c9127baa696)): ?>
<?php $component = $__componentOriginal62b620b00b7a9e8b56ff6c9127baa696; ?>
<?php unset($__componentOriginal62b620b00b7a9e8b56ff6c9127baa696); ?>
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

            <div class="rc-tabs report-no-print">
                <button type="button" class="rc-tab" :class="tab === 'cards' && 'is-active'" @click="tab = 'cards'">کارت‌های گزارش</button>
                <button type="button" class="rc-tab" :class="tab === 'ledger' && 'is-active'" @click="tab = 'ledger'">دفتر روزانه</button>
                <button type="button" class="rc-tab" :class="tab === 'his' && 'is-active'" @click="tab = 'his'">صندوق HIS</button>
            </div>

            
            <div x-show="tab === 'cards'" x-cloak class="space-y-4">
                <div class="rc-toolbar report-no-print">
                    <?php if($canManageCards): ?>
                        <button type="button" class="btn-primary !px-3 !py-1.5 !text-xs" @click="newCard('metric')">افزودن کارت</button>
                        <button type="button" class="btn-secondary !px-3 !py-1.5 !text-xs" @click="newCard('partner')">کارت شراکت</button>
                        <button type="button" class="btn-secondary !px-3 !py-1.5 !text-xs" :class="editMode && '!border-red-400 !text-red-600'" @click="editMode = ! editMode">
                            <span x-text="editMode ? 'پایان ویرایش' : 'ویرایش کارت‌ها'"></span>
                        </button>
                        <?php if($cards === []): ?>
                            <form method="POST" action="<?php echo e(route('modules.accounting.cards.presets')); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn-secondary !px-3 !py-1.5 !text-xs">ساخت کارت‌های پیش‌فرض</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                    <span class="grow"></span>
                    <a href="<?php echo e(route('modules.accounting.cards.export')); ?>" class="btn-secondary !px-3 !py-1.5 !text-xs">خروجی CSV کارت‌ها</a>
                    <button type="button" class="btn-secondary !px-3 !py-1.5 !text-xs" @click="window.print()">چاپ</button>
                </div>

                <div class="rc-grid">
                    <?php $__empty_1 = true; $__currentLoopData = $cards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $card = $entry['card'];
                            $result = $entry['result'];
                            $serviceLabel = $cardFilterOptions[$card->source]['service'][$card->filter_service] ?? $card->filter_service;
                            $typeLabel = $cardFilterOptions[$card->source]['type'][$card->filter_type] ?? $card->filter_type;
                        ?>
                        <div class="rc-card" style="--rc-color: <?php echo e($card->color); ?>; color: <?php echo e($card->text_color); ?>;">
                            <div class="rc-card__top">
                                <span class="rc-card__title"><?php echo e($card->title); ?></span>
                                <span class="rc-card__badge">
                                    <?php echo e($card->isPartner() ? 'شراکت '.$card->partner_percent.'٪' : ($cardMetrics[$card->metric] ?? $card->metric)); ?>

                                </span>
                            </div>

                            <?php if($card->isPartner()): ?>
                                <div class="rc-card__partner"><?php echo e($card->partner_name); ?></div>
                            <?php endif; ?>

                            <div class="rc-card__value" dir="ltr"><?php echo e($result['display']); ?></div>

                            <?php if($result['details'] !== []): ?>
                                <div class="rc-card__sub"><?php echo e(implode(' · ', $result['details'])); ?></div>
                            <?php endif; ?>

                            <div class="rc-card__meta">
                                <?php echo e($cardSources[$card->source]['label'] ?? $card->source); ?><?php if($serviceLabel): ?> · <?php echo e($serviceLabel); ?><?php endif; ?> <?php if($typeLabel): ?> · <?php echo e($typeLabel); ?><?php endif; ?>
                                <br>
                                <?php echo e($result['range_label']); ?> · <span dir="ltr"><?php echo e($result['from']); ?> — <?php echo e($result['to']); ?></span>
                            </div>

                            <?php if($canManageCards): ?>
                                <div class="rc-card__actions" x-show="editMode" x-cloak>
                                    <button type="button" class="rc-card__btn" @click="editCard(<?php echo e($card->id); ?>)">ویرایش</button>
                                    <form method="POST" action="<?php echo e(route('modules.accounting.cards.destroy', $card)); ?>"
                                          onsubmit="return confirm('این کارت حذف شود؟');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="rc-card__btn rc-card__btn--danger">حذف</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="rc-empty">
                            هنوز کارتی ساخته نشده است.
                            <?php if($canManageCards): ?>
                                با «افزودن کارت» یک شاخص دلخواه (دریافت، بدهکاری، تعداد ویزیت یا عمل) با بازهٔ تاریخ اختصاصی بسازید.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div x-show="tab === 'ledger'" x-cloak class="space-y-4">
                <div class="flex justify-end">
                    <a href="<?php echo e(route('modules.accounting.export', ['from' => $dateJalali, 'to' => $dateJalali])); ?>" class="btn-secondary !px-3 !py-1.5 !text-xs">خروجی CSV امروز</a>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <div class="panel p-4 lg:col-span-2">
                        <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
                            <input type="hidden" name="tab" value="ledger">
                            <div>
                                <label class="text-xs font-bold" style="color: var(--muted);">تاریخ (شمسی)</label>
                                <input type="text" name="date" value="<?php echo e($dateJalali); ?>" class="field-input mt-1 w-40" placeholder="1404/06/03" dir="ltr">
                            </div>
                            <button type="submit" class="btn-secondary !py-2">نمایش</button>
                        </form>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border p-3" style="border-color: var(--line); background: var(--panel-soft);">
                                <div class="text-xs" style="color: var(--muted);">دریافت امروز</div>
                                <div class="mt-1 text-lg font-bold" style="color: var(--brand-dark);" dir="ltr"><?php echo e(number_format($todayIncome)); ?> <span class="text-xs">تومان</span></div>
                            </div>
                            <div class="rounded-xl border p-3" style="border-color: var(--line); background: var(--panel-soft);">
                                <div class="text-xs" style="color: var(--muted);">بدهکاری ثبت‌شده</div>
                                <div class="mt-1 text-lg font-bold" style="color: var(--warn);" dir="ltr"><?php echo e(number_format($todayChargesTotal)); ?> <span class="text-xs">تومان</span></div>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <div>
                                <h3 class="mb-2 text-sm font-bold" style="color: var(--ink);">دریافت‌ها</h3>
                                <div class="space-y-2 max-h-72 overflow-auto">
                                    <?php $__empty_1 = true; $__currentLoopData = $todayPayments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <div class="rounded-lg border px-3 py-2 text-xs" style="border-color: var(--line);">
                                            <div class="font-bold"><?php echo e($tx->patient?->name); ?></div>
                                            <div style="color: var(--muted);"><?php echo e($tx->label); ?> · <?php echo e($tx->method); ?></div>
                                            <div class="font-mono font-bold" dir="ltr"><?php echo e(number_format($tx->amount)); ?></div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <p class="text-xs" style="color: var(--muted);">دریافتی ثبت نشده.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <h3 class="mb-2 text-sm font-bold" style="color: var(--ink);">بدهکاری‌ها</h3>
                                <div class="space-y-2 max-h-72 overflow-auto">
                                    <?php $__empty_1 = true; $__currentLoopData = $todayCharges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <div class="rounded-lg border px-3 py-2 text-xs" style="border-color: var(--line);">
                                            <div class="font-bold"><?php echo e($tx->patient?->name); ?></div>
                                            <div style="color: var(--muted);"><?php echo e($tx->label); ?></div>
                                            <div class="font-mono font-bold" dir="ltr"><?php echo e(number_format($tx->amount)); ?></div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <p class="text-xs" style="color: var(--muted);">بدهکاری ثبت نشده.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <form method="POST" action="<?php echo e(route('modules.accounting.store')); ?>" class="panel space-y-3 p-4">
                            <?php echo csrf_field(); ?>
                            <h3 class="text-sm font-bold" style="color: var(--ink);">ثبت تراکنش</h3>
                            <div>
                                <label class="text-xs font-bold" style="color: var(--muted);">بیمار</label>
                                <select name="patient_id" class="field-input mt-1 w-full" required>
                                    <option value="">انتخاب…</option>
                                    <?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($p->id); ?>" <?php if(old('patient_id') == $p->id): echo 'selected'; endif; ?>><?php echo e($p->name); ?> — <?php echo e($p->mobile); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs font-bold" style="color: var(--muted);">نوع</label>
                                    <select name="type" class="field-input mt-1 w-full" required>
                                        <option value="payment">دریافت</option>
                                        <option value="charge">بدهکاری</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-bold" style="color: var(--muted);">مبلغ (تومان)</label>
                                    <input type="number" name="amount" class="field-input mt-1 w-full" min="1" required dir="ltr">
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-bold" style="color: var(--muted);">شرح</label>
                                <input type="text" name="label" class="field-input mt-1 w-full" required maxlength="160">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs font-bold" style="color: var(--muted);">تاریخ</label>
                                    <input type="text" name="transaction_date" value="<?php echo e(old('transaction_date', $dateJalali)); ?>" class="field-input mt-1 w-full" required dir="ltr">
                                </div>
                                <div>
                                    <label class="text-xs font-bold" style="color: var(--muted);">روش (دریافت)</label>
                                    <select name="method" class="field-input mt-1 w-full">
                                        <option value="cash">نقد</option>
                                        <option value="pos">کارتخوان</option>
                                        <option value="wallet">کیف‌پول</option>
                                        <option value="transfer">انتقال</option>
                                        <option value="card">کارت</option>
                                    </select>
                                </div>
                            </div>
                            <textarea name="notes" rows="2" class="field-input w-full" placeholder="یادداشت اختیاری"></textarea>
                            <button type="submit" class="btn-primary w-full !py-2">ثبت</button>
                        </form>

                        <div class="panel p-4">
                            <h3 class="mb-2 text-sm font-bold" style="color: var(--ink);">بیماران بدهکار</h3>
                            <div class="space-y-2 max-h-64 overflow-auto">
                                <?php $__empty_1 = true; $__currentLoopData = $debtors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <a href="<?php echo e(route('patients.show', $row['patient'])); ?>" class="flex items-center justify-between rounded-lg border px-3 py-2 text-xs hover:bg-[var(--panel-soft)]" style="border-color: var(--line);">
                                        <span class="font-bold"><?php echo e($row['patient']->name); ?></span>
                                        <span class="font-mono font-bold text-red-600" dir="ltr"><?php echo e(number_format($row['balance'])); ?></span>
                                    </a>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <p class="text-xs" style="color: var(--muted);">بدهی ثبت‌شده‌ای نیست.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div x-show="tab === 'his'" x-cloak class="space-y-4">
                <p class="text-xs" style="color: var(--muted);">
                    پرداخت‌های واقعی HIS از <span dir="ltr">CashPayment / Payment</span> همگام‌سازی می‌شوند (کارتخوان / نقد / کیف‌پول). ویرایش فقط در HIS.
                </p>

                <form method="GET" class="panel flex flex-wrap items-end gap-2 p-3 report-no-print">
                    <input type="hidden" name="tab" value="his">
                    <div>
                        <label class="text-xs font-bold" style="color: var(--muted);">از تاریخ</label>
                        <input type="text" name="his_from" value="<?php echo e($hisFromJalali); ?>" class="field-input mt-1 w-36" dir="ltr" placeholder="1404/06/01">
                    </div>
                    <div>
                        <label class="text-xs font-bold" style="color: var(--muted);">تا تاریخ</label>
                        <input type="text" name="his_to" value="<?php echo e($hisToJalali); ?>" class="field-input mt-1 w-36" dir="ltr" placeholder="1404/06/31">
                    </div>
                    <div>
                        <label class="text-xs font-bold" style="color: var(--muted);">روش</label>
                        <select name="his_method" class="field-input mt-1 w-36">
                            <option value="">همه</option>
                            <?php $__currentLoopData = $hisMethodLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($key); ?>" <?php if($hisMethod === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="grow min-w-[10rem]">
                        <label class="text-xs font-bold" style="color: var(--muted);">جستجو</label>
                        <input type="text" name="his_q" value="<?php echo e($hisQuery); ?>" class="field-input mt-1 w-full" placeholder="نام، موبایل، کد ملی، پذیرش…">
                    </div>
                    <button type="submit" class="btn-primary !py-2 !px-4">اعمال</button>
                    <a href="<?php echo e(route('modules.accounting.index', ['tab' => 'his'])); ?>" class="btn-secondary !py-2 !px-3">پاک‌سازی</a>
                </form>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-xl border p-3" style="border-color: var(--line); background: var(--panel-soft);">
                        <div class="text-xs" style="color: var(--muted);">تعداد</div>
                        <div class="mt-1 text-lg font-bold" dir="ltr"><?php echo e(number_format($hisSummary['count'])); ?></div>
                    </div>
                    <div class="rounded-xl border p-3" style="border-color: var(--line); background: var(--panel-soft);">
                        <div class="text-xs" style="color: var(--muted);">جمع کل</div>
                        <div class="mt-1 text-lg font-bold" style="color: var(--brand-dark);" dir="ltr"><?php echo e(number_format($hisSummary['total'])); ?></div>
                    </div>
                    <div class="rounded-xl border p-3" style="border-color: var(--line); background: var(--panel-soft);">
                        <div class="text-xs" style="color: var(--muted);">کارتخوان</div>
                        <div class="mt-1 text-lg font-bold" dir="ltr"><?php echo e(number_format($hisSummary['pos'])); ?></div>
                    </div>
                    <div class="rounded-xl border p-3" style="border-color: var(--line); background: var(--panel-soft);">
                        <div class="text-xs" style="color: var(--muted);">نقد</div>
                        <div class="mt-1 text-lg font-bold" dir="ltr"><?php echo e(number_format($hisSummary['cash'])); ?></div>
                    </div>
                    <div class="rounded-xl border p-3" style="border-color: var(--line); background: var(--panel-soft);">
                        <div class="text-xs" style="color: var(--muted);">کیف‌پول</div>
                        <div class="mt-1 text-lg font-bold" dir="ltr"><?php echo e(number_format($hisSummary['wallet'])); ?></div>
                    </div>
                </div>

                <div class="panel overflow-x-auto p-0">
                    <table class="w-full min-w-[40rem] text-xs">
                        <thead>
                            <tr style="background: var(--panel-soft); color: var(--muted);">
                                <th class="px-3 py-2 text-right font-bold">تاریخ</th>
                                <th class="px-3 py-2 text-right font-bold">بیمار</th>
                                <th class="px-3 py-2 text-right font-bold">روش</th>
                                <th class="px-3 py-2 text-right font-bold">شرح</th>
                                <th class="px-3 py-2 text-left font-bold" dir="ltr">مبلغ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $hisPayments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr style="border-top: 1px solid var(--line);">
                                    <td class="px-3 py-2 whitespace-nowrap" dir="ltr"><?php echo e(jalali($tx->transaction_date, 'Y/m/d')); ?></td>
                                    <td class="px-3 py-2">
                                        <div class="font-bold"><?php echo e($tx->patient?->name ?? '—'); ?></div>
                                        <div style="color: var(--muted);" dir="ltr"><?php echo e($tx->patient?->mobile); ?></div>
                                    </td>
                                    <td class="px-3 py-2"><?php echo e($hisMethodLabels[$tx->method] ?? ($tx->method ?: '—')); ?></td>
                                    <td class="px-3 py-2">
                                        <div><?php echo e($tx->label); ?></div>
                                        <?php if($tx->notes): ?>
                                            <div style="color: var(--muted);"><?php echo e($tx->notes); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-2 font-mono font-bold whitespace-nowrap" dir="ltr"><?php echo e(number_format($tx->amount)); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="5" class="px-3 py-8 text-center" style="color: var(--muted);">
                                        هنوز پرداخت HIS در این بازه نیست. عامل را با کوئری CashPayment/Payment همگام کنید.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if($hisPayments->count() >= 500): ?>
                    <p class="text-xs" style="color: var(--muted);">حداکثر ۵۰۰ ردیف نمایش داده می‌شود؛ بازه یا جستجو را تنگ‌تر کنید.</p>
                <?php endif; ?>
            </div>
        </div>

        
        <?php if($canManageCards): ?>
            <div class="rc-modal" x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false" @click.self="modalOpen = false">
                <form method="POST" class="rc-modal__panel" :action="formAction()">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="_method" :value="form.id ? 'PUT' : 'POST'">
                    <input type="hidden" name="card_type" :value="form.card_type">
                    <input type="hidden" name="from_jalali" :value="fromJalali()">
                    <input type="hidden" name="to_jalali" :value="toJalali()">

                    <div class="rc-modal__head">
                        <h3 class="rc-modal__title" x-text="modalTitle()"></h3>
                        <button type="button" class="scl-close" @click="modalOpen = false">×</button>
                    </div>

                    <div class="space-y-2.5">
                        <div>
                            <label class="form-label">عنوان کارت</label>
                            <input type="text" name="title" class="field-input w-full" maxlength="120" required
                                   x-model="form.title" placeholder="مثال: دریافت نقدی مرداد">
                        </div>

                        <div x-show="form.card_type === 'partner'" class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="form-label">نام همکار</label>
                                <input type="text" name="partner_name" class="field-input w-full" maxlength="120" x-model="form.partner_name">
                            </div>
                            <div>
                                <label class="form-label">درصد شراکت</label>
                                <input type="number" name="partner_percent" class="field-input w-full" min="0" max="100" dir="ltr" x-model="form.partner_percent">
                            </div>
                        </div>

                        <div>
                            <label class="form-label">منبع داده</label>
                            <select name="source" class="field-input w-full" x-model="form.source"
                                    @change="form.filter_service = ''; form.filter_type = ''">
                                <?php $__currentLoopData = $cardSources; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($key); ?>"><?php echo e($meta['label']); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <p class="form-hint text-xs" style="color: var(--muted);" x-text="sources[form.source]?.hint"></p>
                        </div>

                        <div x-show="form.card_type !== 'partner'">
                            <label class="form-label">شاخص</label>
                            <select name="metric" class="field-input w-full" x-model="form.metric">
                                <?php $__currentLoopData = $cardMetrics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($key); ?>"><?php echo e($label); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <div x-show="hasServiceFilter()">
                            <label class="form-label" x-text="sources[form.source]?.service_label"></label>
                            <select name="filter_service" class="field-input w-full" x-model="form.filter_service">
                                <option value="">همه</option>
                                <template x-for="[value, label] in Object.entries(serviceOptions())" :key="value">
                                    <option :value="value" x-text="label"></option>
                                </template>
                            </select>
                        </div>

                        <div x-show="hasTypeFilter()">
                            <label class="form-label" x-text="sources[form.source]?.type_label"></label>
                            <select name="filter_type" class="field-input w-full" x-model="form.filter_type">
                                <option value="">همه</option>
                                <template x-for="[value, label] in Object.entries(typeOptions())" :key="value">
                                    <option :value="value" x-text="label"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="rc-section">بازهٔ زمانی</div>

                    <div class="space-y-2.5">
                        <select name="range_mode" class="field-input w-full" x-model="form.range_mode">
                            <?php $__currentLoopData = $cardRangeModes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($key); ?>"><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>

                        <div x-show="form.range_mode === 'custom'" class="space-y-2">
                            <div>
                                <label class="form-label">از تاریخ</label>
                                <div class="rc-date-row">
                                    <select class="field-input" x-model="form.from_y">
                                        <template x-for="y in years" :key="y"><option :value="y" x-text="y"></option></template>
                                    </select>
                                    <span>/</span>
                                    <select class="field-input" x-model="form.from_m">
                                        <template x-for="m in 12" :key="m"><option :value="m" x-text="pad(m)"></option></template>
                                    </select>
                                    <span>/</span>
                                    <select class="field-input" x-model="form.from_d">
                                        <template x-for="d in daysIn(form.from_y, form.from_m)" :key="d"><option :value="d" x-text="pad(d)"></option></template>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">تا تاریخ</label>
                                <div class="rc-date-row">
                                    <select class="field-input" x-model="form.to_y">
                                        <template x-for="y in years" :key="y"><option :value="y" x-text="y"></option></template>
                                    </select>
                                    <span>/</span>
                                    <select class="field-input" x-model="form.to_m">
                                        <template x-for="m in 12" :key="m"><option :value="m" x-text="pad(m)"></option></template>
                                    </select>
                                    <span>/</span>
                                    <select class="field-input" x-model="form.to_d">
                                        <template x-for="d in daysIn(form.to_y, form.to_m)" :key="d"><option :value="d" x-text="pad(d)"></option></template>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rc-section">نمایش</div>

                    <div class="space-y-2.5">
                        <div class="flex flex-wrap gap-4 text-xs font-bold" style="color: var(--ink);">
                            <label class="flex items-center gap-1.5">
                                <input type="checkbox" name="show_count" value="1" x-model="form.show_count"> تعداد
                            </label>
                            <label class="flex items-center gap-1.5">
                                <input type="checkbox" name="show_avg" value="1" x-model="form.show_avg"> میانگین
                            </label>
                            <label class="flex items-center gap-1.5">
                                <input type="checkbox" name="show_total" value="1" x-model="form.show_total"> مبلغ کل
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="form-label">رنگ کارت</label>
                                <input type="color" name="color" class="h-10 w-full rounded-lg border" style="border-color: var(--line);" x-model="form.color">
                            </div>
                            <div>
                                <label class="form-label">رنگ متن</label>
                                <input type="color" name="text_color" class="h-10 w-full rounded-lg border" style="border-color: var(--line);" x-model="form.text_color">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <button type="submit" class="btn-primary flex-1 !py-2">ذخیره کارت</button>
                        <button type="button" class="btn-secondary !py-2" @click="modalOpen = false">انصراف</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function accountingCards(config) {
            const blank = {
                id: null,
                title: '',
                card_type: 'metric',
                source: 'payments',
                metric: 'total',
                filter_service: '',
                filter_type: '',
                partner_name: '',
                partner_percent: 30,
                range_mode: 'this_month',
                from_y: config.years[2] ?? config.years[0],
                from_m: 1,
                from_d: 1,
                to_y: config.years[2] ?? config.years[0],
                to_m: 12,
                to_d: 29,
                color: '#4f86be',
                text_color: '#ffffff',
                show_count: true,
                show_avg: false,
                show_total: true,
            };

            return {
                tab: config.tab,
                sources: config.sources,
                options: config.options,
                forms: config.forms,
                years: config.years,
                editMode: false,
                modalOpen: false,
                form: { ...blank },

                pad(n) { return String(n).padStart(2, '0'); },

                daysIn(year, month) {
                    const m = parseInt(month) || 1;
                    if (m <= 6) return 31;
                    if (m <= 11) return 30;
                    return [1, 5, 9, 13, 17, 22, 26, 30].includes((parseInt(year) || 0) % 33) ? 30 : 29;
                },

                splitDate(value, fallbackYear) {
                    const parts = String(value || '').split('/');
                    return {
                        y: parseInt(parts[0]) || fallbackYear,
                        m: parseInt(parts[1]) || 1,
                        d: parseInt(parts[2]) || 1,
                    };
                },

                fromJalali() { return `${this.form.from_y}/${this.pad(this.form.from_m)}/${this.pad(this.form.from_d)}`; },
                toJalali() { return `${this.form.to_y}/${this.pad(this.form.to_m)}/${this.pad(this.form.to_d)}`; },

                serviceOptions() { return this.options[this.form.source]?.service ?? {}; },
                typeOptions() { return this.options[this.form.source]?.type ?? {}; },
                hasServiceFilter() { return Boolean(this.sources[this.form.source]?.service_label); },
                hasTypeFilter() { return Boolean(this.sources[this.form.source]?.type_label); },

                modalTitle() {
                    if (this.form.card_type === 'partner') {
                        return this.form.id ? 'ویرایش کارت شراکت' : 'کارت شراکت جدید';
                    }
                    return this.form.id ? 'ویرایش کارت' : 'کارت گزارش جدید';
                },

                formAction() {
                    return this.form.id
                        ? '<?php echo e(url('modules/accounting/cards')); ?>/' + this.form.id
                        : '<?php echo e(route('modules.accounting.cards.store')); ?>';
                },

                newCard(type) {
                    const today = this.splitDate(config.today, this.years[2] ?? this.years[0]);
                    this.form = {
                        ...blank,
                        card_type: type,
                        from_y: today.y,
                        to_y: today.y,
                        title: type === 'partner' ? 'سهم همکار' : '',
                        color: type === 'partner' ? '#7c5cd6' : '#4f86be',
                    };
                    this.modalOpen = true;
                },

                editCard(id) {
                    const saved = this.forms[id];
                    if (! saved) return;
                    const from = this.splitDate(saved.from, this.years[2] ?? this.years[0]);
                    const to = this.splitDate(saved.to, this.years[2] ?? this.years[0]);
                    this.form = {
                        ...blank,
                        ...saved,
                        from_y: from.y, from_m: from.m, from_d: from.d,
                        to_y: to.y, to_m: to.m, to_d: to.d,
                    };
                    this.modalOpen = true;
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\modules\accounting\index.blade.php ENDPATH**/ ?>