@php
    $catalogForJs = collect($widgets)->map(fn ($w) => [
        'id' => $w['id'],
        'label' => $w['label'],
        'description' => $w['description'],
        'span' => $w['span'],
        'icon' => $w['icon'],
    ])->values()->all();

    $layoutIds = collect($layout)->pluck('id')->all();
    $renderOrder = array_values(array_unique(array_merge($layoutIds, array_keys($widgets))));
@endphp
<x-app-layout body-class="is-admin">
    <x-slot name="header">
        <div class="admin-header">
            <div>
                <p class="admin-header__eyebrow">مرکز فرمان</p>
                <h2 class="admin-header__title">مدیریت کل سایت</h2>
            </div>
            <div class="admin-header__actions">
                <span class="text-[11px] font-bold px-2 py-1 rounded-lg" style="background:var(--panel-soft);color:var(--muted);">v{{ \App\Support\AppVersion::current() }}</span>
                <a href="{{ route('dashboard') }}" class="btn-ghost !text-xs !px-3 !py-1.5">داشبورد</a>
                <a href="{{ route('admin.users.create') }}" class="btn-primary btn-primary--compact">+ کاربر جدید</a>
            </div>
        </div>
    </x-slot>

    <div
        class="admin-page"
        x-data="adminDashBoard(@js([
            'layout' => $layout,
            'catalog' => $catalogForJs,
            'saveUrl' => route('admin.layout.update'),
            'resetUrl' => route('admin.layout.reset'),
        ]))"
    >
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-admin-dock :admin-section="'overview'" />
            <x-flash />

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
                @foreach ($renderOrder as $widgetId)
                    @php
                        $meta = $widgets[$widgetId] ?? null;
                        if (! $meta) {
                            continue;
                        }
                        $onBoard = in_array($widgetId, $layoutIds, true);
                    @endphp
                    <div
                        class="admin-dash-widget"
                        data-widget-id="{{ $widgetId }}"
                        :class="{
                            'admin-dash-widget--full': spanOf('{{ $widgetId }}') === 'full',
                            'admin-dash-widget--half': spanOf('{{ $widgetId }}') !== 'full',
                            'is-dragging': dragId === '{{ $widgetId }}',
                            'is-drop-target': dropId === '{{ $widgetId }}' && dragId !== '{{ $widgetId }}',
                        }"
                        x-show="hasWidget('{{ $widgetId }}')"
                        @if (! $onBoard) x-cloak style="display: none;" @endif
                        @dragenter.prevent="onDragEnter('{{ $widgetId }}')"
                        @drop.prevent="onDrop('{{ $widgetId }}')"
                    >
                        <div class="admin-dash-widget__chrome" x-show="editing" x-cloak>
                            <button
                                type="button"
                                class="admin-dash-handle"
                                title="جابه‌جایی"
                                aria-label="جابه‌جایی ویجت"
                                draggable="true"
                                @dragstart.stop="onDragStart('{{ $widgetId }}', $event)"
                                @dragend.stop="onDragEnd()"
                            >
                                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M7 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm0 6a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm0 6a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm9-12a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm0 6a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm0 6a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/></svg>
                            </button>
                            <span class="admin-dash-widget__name">{{ $meta['label'] }}</span>
                            <button
                                type="button"
                                class="admin-dash-span"
                                title="تغییر اندازه"
                                @click.stop="toggleSpan('{{ $widgetId }}')"
                                x-text="spanOf('{{ $widgetId }}') === 'full' ? 'تمام‌عرض' : 'نیم‌عرض'"
                            ></button>
                            <div class="admin-dash-nudge">
                                <button type="button" class="admin-dash-nudge__btn" title="بالا" aria-label="جابه‌جایی به بالا" @click.stop="moveWidget('{{ $widgetId }}', -1)">
                                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
                                </button>
                                <button type="button" class="admin-dash-nudge__btn" title="پایین" aria-label="جابه‌جایی به پایین" @click.stop="moveWidget('{{ $widgetId }}', 1)">
                                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                </button>
                            </div>
                            <button
                                type="button"
                                class="admin-dash-remove"
                                title="حذف از صفحه"
                                aria-label="حذف ویجت"
                                @click.stop="removeWidget('{{ $widgetId }}')"
                            >
                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                        <div class="admin-dash-widget__body">
                            @include('admin.widgets.'.$widgetId, ['widgetData' => $widgetData])
                        </div>
                    </div>
                @endforeach
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
</x-app-layout>
