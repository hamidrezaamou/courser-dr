<?php
    $catalogForJs = collect($widgets)->map(fn ($w) => [
        'id' => $w['id'],
        'label' => $w['label'],
        'description' => $w['description'],
        'span' => $w['span'],
        'icon' => $w['icon'],
    ])->values()->all();

    $layoutIds = collect($layout)->pluck('id')->all();
    $renderOrder = array_values(array_unique(array_merge($layoutIds, array_keys($widgets))));
?>
<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve(['bodyClass' => 'is-admin'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div class="admin-header">
            <div>
                <p class="admin-header__eyebrow">مرکز فرمان</p>
                <h2 class="admin-header__title">مدیریت کل سایت</h2>
            </div>
            <div class="admin-header__actions">
                <a href="<?php echo e(route('dashboard')); ?>" class="btn-ghost !text-xs !px-3 !py-1.5">داشبورد</a>
                <a href="<?php echo e(route('admin.users.create')); ?>" class="btn-primary btn-primary--compact">+ کاربر جدید</a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div
        class="admin-page"
        x-data="adminDashBoard(<?php echo \Illuminate\Support\Js::from([
            'layout' => $layout,
            'catalog' => $catalogForJs,
            'saveUrl' => route('admin.layout.update'),
            'resetUrl' => route('admin.layout.reset'),
        ])->toHtml() ?>)"
    >
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <?php if (isset($component)) { $__componentOriginal0f5b24ef0b91ecd95fa5272f30464615 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0f5b24ef0b91ecd95fa5272f30464615 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-dock','data' => ['adminSection' => 'overview']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-dock'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['admin-section' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('overview')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0f5b24ef0b91ecd95fa5272f30464615)): ?>
<?php $attributes = $__attributesOriginal0f5b24ef0b91ecd95fa5272f30464615; ?>
<?php unset($__attributesOriginal0f5b24ef0b91ecd95fa5272f30464615); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0f5b24ef0b91ecd95fa5272f30464615)): ?>
<?php $component = $__componentOriginal0f5b24ef0b91ecd95fa5272f30464615; ?>
<?php unset($__componentOriginal0f5b24ef0b91ecd95fa5272f30464615); ?>
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

            <div class="admin-dash-toolbar">
                <div class="admin-dash-toolbar__info">
                    <span class="admin-dash-toolbar__label" x-text="editing ? 'حالت ویرایش چیدمان' : 'نمای کلی'"></span>
                    <span class="admin-dash-toolbar__hint" x-show="editing" x-cloak>بکشید، اندازه را عوض کنید، حذف یا اضافه کنید</span>
                </div>
                <div class="admin-dash-toolbar__actions">
                    <template x-if="editing">
                        <div class="admin-dash-toolbar__edit-group">
                            <button type="button" class="admin-dash-btn admin-dash-btn--ghost" @click="resetLayout()" :disabled="saving">
                                بازنشانی
                            </button>
                            <button
                                type="button"
                                class="admin-dash-btn admin-dash-btn--accent"
                                @click="sheetOpen = true"
                                :disabled="available().length === 0"
                            >
                                <svg class="admin-dash-btn__icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 4a1 1 0 011 1v4h4a1 1 0 110 2h-4v4a1 1 0 11-2 0v-4H5a1 1 0 110-2h4V5a1 1 0 011-1z"/></svg>
                                افزودن ویجت
                            </button>
                            <button type="button" class="admin-dash-btn admin-dash-btn--primary" @click="editing = false" :disabled="saving">
                                تمام
                            </button>
                        </div>
                    </template>
                    <template x-if="!editing">
                        <button type="button" class="admin-dash-btn admin-dash-btn--ghost" @click="editing = true">
                            <svg class="admin-dash-btn__icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M4 5a1 1 0 011-1h2a1 1 0 010 2H5v2a1 1 0 01-2 0V5zm12 0a1 1 0 00-1-1h-2a1 1 0 100 2h2v2a1 1 0 102 0V5zM4 15a1 1 0 001 1h2a1 1 0 100-2H5v-2a1 1 0 10-2 0v2zm12 0a1 1 0 01-1 1h-2a1 1 0 110-2h2v-2a1 1 0 112 0v2z"/></svg>
                            ویرایش چیدمان
                        </button>
                    </template>
                    <span class="admin-dash-save" x-show="saving" x-cloak>در حال ذخیره…</span>
                    <span class="admin-dash-save admin-dash-save--ok" x-show="savedFlash" x-cloak x-transition>ذخیره شد</span>
                </div>
            </div>

            <div
                class="admin-dash-board"
                :class="{ 'is-editing': editing }"
                @dragover.prevent="onDragOver($event)"
            >
                <?php $__currentLoopData = $renderOrder; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $widgetId): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $meta = $widgets[$widgetId] ?? null;
                        if (! $meta) {
                            continue;
                        }
                        $onBoard = in_array($widgetId, $layoutIds, true);
                    ?>
                    <div
                        class="admin-dash-widget"
                        data-widget-id="<?php echo e($widgetId); ?>"
                        :class="{
                            'admin-dash-widget--full': spanOf('<?php echo e($widgetId); ?>') === 'full',
                            'admin-dash-widget--half': spanOf('<?php echo e($widgetId); ?>') !== 'full',
                            'is-dragging': dragId === '<?php echo e($widgetId); ?>',
                            'is-drop-target': dropId === '<?php echo e($widgetId); ?>' && dragId !== '<?php echo e($widgetId); ?>',
                        }"
                        x-show="hasWidget('<?php echo e($widgetId); ?>')"
                        <?php if(! $onBoard): ?> x-cloak style="display: none;" <?php endif; ?>
                        @dragenter.prevent="onDragEnter('<?php echo e($widgetId); ?>')"
                        @drop.prevent="onDrop('<?php echo e($widgetId); ?>')"
                    >
                        <div class="admin-dash-widget__chrome" x-show="editing" x-cloak>
                            <button
                                type="button"
                                class="admin-dash-handle"
                                title="جابه‌جایی"
                                aria-label="جابه‌جایی ویجت"
                                draggable="true"
                                @dragstart.stop="onDragStart('<?php echo e($widgetId); ?>', $event)"
                                @dragend.stop="onDragEnd()"
                            >
                                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M7 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm0 6a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm0 6a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm9-12a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm0 6a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm0 6a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/></svg>
                            </button>
                            <span class="admin-dash-widget__name"><?php echo e($meta['label']); ?></span>
                            <button
                                type="button"
                                class="admin-dash-span"
                                title="تغییر اندازه"
                                @click.stop="toggleSpan('<?php echo e($widgetId); ?>')"
                                x-text="spanOf('<?php echo e($widgetId); ?>') === 'full' ? 'تمام‌عرض' : 'نیم‌عرض'"
                            ></button>
                            <div class="admin-dash-nudge">
                                <button type="button" class="admin-dash-nudge__btn" title="بالا" aria-label="جابه‌جایی به بالا" @click.stop="moveWidget('<?php echo e($widgetId); ?>', -1)">
                                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
                                </button>
                                <button type="button" class="admin-dash-nudge__btn" title="پایین" aria-label="جابه‌جایی به پایین" @click.stop="moveWidget('<?php echo e($widgetId); ?>', 1)">
                                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                </button>
                            </div>
                            <button
                                type="button"
                                class="admin-dash-remove"
                                title="حذف از صفحه"
                                aria-label="حذف ویجت"
                                @click.stop="removeWidget('<?php echo e($widgetId); ?>')"
                            >
                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                        <div class="admin-dash-widget__body">
                            <?php echo $__env->make('admin.widgets.'.$widgetId, ['widgetData' => $widgetData], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <div class="admin-dash-empty-board" x-show="layout.length === 0" x-cloak>
                <p>هیچ ویجتی روی صفحه نیست.</p>
                <button type="button" class="admin-dash-btn admin-dash-btn--accent" @click="editing = true; sheetOpen = true">افزودن ویجت</button>
            </div>
        </div>

        <div
            class="admin-dash-sheet"
            x-show="sheetOpen"
            x-cloak
            @keydown.escape.window="sheetOpen && (sheetOpen = false)"
        >
            <div class="admin-dash-sheet__backdrop" @click="sheetOpen = false"></div>
            <div class="admin-dash-sheet__panel" role="dialog" aria-modal="true" aria-labelledby="admin-dash-sheet-title" @click.stop>
                <div class="admin-dash-sheet__head">
                    <div>
                        <p class="admin-dash-sheet__eyebrow">کاتالوگ</p>
                        <h3 id="admin-dash-sheet-title" class="admin-dash-sheet__title">افزودن ویجت</h3>
                    </div>
                    <button type="button" class="admin-dash-sheet__close" @click="sheetOpen = false" aria-label="بستن">
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                </div>
                <p class="admin-dash-sheet__hint" x-show="available().length === 0">همه ویجت‌های موجود روی صفحه هستند.</p>
                <div class="admin-dash-sheet__list">
                    <template x-for="item in available()" :key="item.id">
                        <button type="button" class="admin-dash-catalog-item" @click="addWidget(item)">
                            <span class="admin-dash-catalog-item__glyph" aria-hidden="true">+</span>
                            <span class="admin-dash-catalog-item__text">
                                <strong x-text="item.label"></strong>
                                <span x-text="item.description"></span>
                            </span>
                            <span class="admin-dash-catalog-item__add">افزودن</span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <script>
        function adminDashBoard(cfg) {
            const normalize = (rows) => {
                if (!Array.isArray(rows)) return [];
                return rows.map((row) => {
                    if (typeof row === 'string') return { id: row, span: 'half' };
                    return {
                        id: row.id,
                        span: row.span === 'full' ? 'full' : 'half',
                    };
                }).filter((row) => !!row.id);
            };

            return {
                layout: normalize(cfg.layout),
                catalog: Array.isArray(cfg.catalog) ? cfg.catalog : [],
                saveUrl: cfg.saveUrl,
                resetUrl: cfg.resetUrl,
                editing: false,
                sheetOpen: false,
                saving: false,
                savedFlash: false,
                dragId: null,
                dropId: null,
                _saveTimer: null,
                _flashTimer: null,

                ids() {
                    return this.layout.map((row) => row.id);
                },

                hasWidget(id) {
                    return this.ids().includes(id);
                },

                spanOf(id) {
                    const row = this.layout.find((item) => item.id === id);
                    if (row) return row.span === 'full' ? 'full' : 'half';
                    const meta = this.catalog.find((item) => item.id === id);
                    return meta?.span === 'full' ? 'full' : 'half';
                },

                available() {
                    const on = new Set(this.ids());
                    return this.catalog.filter((w) => !on.has(w.id));
                },

                onDragStart(id, event) {
                    if (!this.editing) {
                        event.preventDefault();
                        return;
                    }
                    this.dragId = id;
                    event.dataTransfer.effectAllowed = 'move';
                    try { event.dataTransfer.setData('text/plain', id); } catch (_) {}
                },

                onDragEnter(id) {
                    if (!this.editing || !this.dragId || this.dragId === id) return;
                    this.dropId = id;
                    this.moveBefore(this.dragId, id);
                },

                onDragOver(event) {
                    if (!this.editing || !this.dragId) return;
                    event.dataTransfer.dropEffect = 'move';
                },

                onDrop(id) {
                    if (!this.editing || !this.dragId) return;
                    this.moveBefore(this.dragId, id);
                    this.persistSoon();
                    this.onDragEnd();
                },

                onDragEnd() {
                    this.dragId = null;
                    this.dropId = null;
                    if (this.editing) this.persistSoon();
                },

                moveBefore(fromId, toId) {
                    if (fromId === toId) return;
                    const moving = this.layout.find((row) => row.id === fromId);
                    if (!moving) return;
                    const next = this.layout.filter((row) => row.id !== fromId);
                    const toIndex = next.findIndex((row) => row.id === toId);
                    if (toIndex === -1) next.push(moving);
                    else next.splice(toIndex, 0, moving);
                    this.layout = next;
                    this.syncDomOrder();
                },

                moveWidget(id, delta) {
                    const index = this.layout.findIndex((row) => row.id === id);
                    if (index < 0) return;
                    const target = index + delta;
                    if (target < 0 || target >= this.layout.length) return;
                    const next = this.layout.slice();
                    const [item] = next.splice(index, 1);
                    next.splice(target, 0, item);
                    this.layout = next;
                    this.syncDomOrder();
                    this.persistSoon();
                },

                toggleSpan(id) {
                    this.layout = this.layout.map((row) => {
                        if (row.id !== id) return row;
                        return { id: row.id, span: row.span === 'full' ? 'half' : 'full' };
                    });
                    this.persistSoon();
                },

                syncDomOrder() {
                    const board = this.$root.querySelector('.admin-dash-board');
                    if (!board) return;
                    this.layout.forEach((row) => {
                        const el = board.querySelector('[data-widget-id="' + row.id + '"]');
                        if (el) board.appendChild(el);
                    });
                },

                addWidget(item) {
                    const id = typeof item === 'string' ? item : item.id;
                    if (!id || this.hasWidget(id)) return;
                    const span = (typeof item === 'object' && item.span === 'full') ? 'full' : 'half';
                    this.layout = this.layout.concat([{ id, span }]);
                    this.$nextTick(() => this.syncDomOrder());
                    this.sheetOpen = false;
                    this.persist();
                },

                removeWidget(id) {
                    this.layout = this.layout.filter((row) => row.id !== id);
                    this.persist();
                },

                async resetLayout() {
                    if (this.saving) return;
                    this.saving = true;
                    try {
                        const res = await fetch(this.resetUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        if (!res.ok) throw new Error('reset failed');
                        const data = await res.json();
                        this.layout = normalize(data.layout);
                        this.$nextTick(() => this.syncDomOrder());
                        this.flashSaved();
                    } catch (e) {
                        console.error(e);
                        alert('بازنشانی چیدمان ناموفق بود.');
                    } finally {
                        this.saving = false;
                    }
                },

                persistSoon() {
                    clearTimeout(this._saveTimer);
                    this._saveTimer = setTimeout(() => this.persist(), 120);
                },

                async persist() {
                    if (this.saving) {
                        this.persistSoon();
                        return;
                    }
                    this.saving = true;
                    try {
                        const res = await fetch(this.saveUrl, {
                            method: 'PUT',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ layout: this.layout }),
                        });
                        if (!res.ok) throw new Error('save failed');
                        this.flashSaved();
                    } catch (e) {
                        console.error(e);
                        alert('ذخیره چیدمان ناموفق بود.');
                    } finally {
                        this.saving = false;
                    }
                },

                flashSaved() {
                    this.savedFlash = true;
                    clearTimeout(this._flashTimer);
                    this._flashTimer = setTimeout(() => { this.savedFlash = false; }, 1600);
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\admin\index.blade.php ENDPATH**/ ?>