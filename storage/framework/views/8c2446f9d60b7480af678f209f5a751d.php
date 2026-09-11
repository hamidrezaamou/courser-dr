<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'typeId',
    'subtypeId' => null,
    'label',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'typeId',
    'subtypeId' => null,
    'label',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div
    class="scl-template"
    x-data="{
        typeId: <?php echo e((int) $typeId); ?>,
        subtypeId: <?php echo e($subtypeId ? (int) $subtypeId : 'null'); ?>,
        label: <?php echo \Illuminate\Support\Js::from($label)->toHtml() ?>,
        items: [],
        loading: true,
        saving: false,
        msg: '',
        async load() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ surgery_type_id: this.typeId });
                if (this.subtypeId) params.set('surgery_subtype_id', this.subtypeId);
                const res = await fetch('/surgery-checklist-templates?' + params.toString(), {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.items = (data.items || []).map(i => i.label);
                if (!this.items.length) this.items = [''];
            } catch (e) {
                this.msg = 'خطا در بارگذاری';
            } finally {
                this.loading = false;
            }
        },
        addRow() { this.items.push(''); },
        removeRow(i) { this.items.splice(i, 1); if (!this.items.length) this.items = ['']; },
        async save() {
            this.saving = true;
            this.msg = '';
            try {
                const res = await fetch('/surgery-checklist-templates', {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                    },
                    body: JSON.stringify({
                        surgery_type_id: this.typeId,
                        surgery_subtype_id: this.subtypeId,
                        items: this.items.filter(l => l.trim()).map(l => ({ label: l.trim() })),
                    }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'خطا');
                this.msg = data.message || 'ذخیره شد';
            } catch (e) {
                this.msg = e.message || 'خطا';
            } finally {
                this.saving = false;
            }
        }
    }"
    x-init="load()"
>
    <details class="scl-template__box">
        <summary class="scl-template__summary">چک‌لیست: <?php echo e($label); ?></summary>
        <div class="scl-template__body">
            <p class="scl-template__hint">موارد جدید بعد از ذخیره، هنگام باز کردن مجدد چک‌لیست بیماران قبلی هم اضافه می‌شوند (تیک‌ها و موارد دستی حفظ می‌شوند).</p>
            <template x-if="loading">
                <p class="text-xs" style="color:var(--muted)">بارگذاری…</p>
            </template>
            <div class="space-y-2" x-show="!loading">
                <template x-for="(row, index) in items" :key="index">
                    <div class="flex gap-2">
                        <input type="text" class="field-input flex-1" x-model="items[index]" placeholder="مورد چک‌لیست">
                        <button type="button" class="btn-secondary !px-2" @click="removeRow(index)">×</button>
                    </div>
                </template>
                <div class="flex flex-wrap gap-2 pt-1">
                    <button type="button" class="btn-secondary !py-1.5 !text-xs" @click="addRow()">+ مورد</button>
                    <button type="button" class="btn-primary !py-1.5 !text-xs" @click="save()" :disabled="saving" x-text="saving ? '…' : 'ذخیره چک‌لیست'"></button>
                </div>
                <p class="text-xs font-bold" style="color:var(--brand)" x-show="msg" x-text="msg"></p>
            </div>
        </div>
    </details>
</div>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\components\surgery-checklist-template-editor.blade.php ENDPATH**/ ?>