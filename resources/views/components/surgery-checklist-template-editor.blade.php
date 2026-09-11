@props([
    'typeId',
    'subtypeId' => null,
    'label',
])

<div
    class="scl-template"
    x-data="{
        typeId: {{ (int) $typeId }},
        subtypeId: {{ $subtypeId ? (int) $subtypeId : 'null' }},
        label: @js($label),
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
        <summary class="scl-template__summary">چک‌لیست: {{ $label }}</summary>
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
