<?php
    $prescriptionsPayload = ($patient->prescriptions ?? collect())->map(fn ($p) => [
        'id' => $p->id,
        'notes' => $p->notes,
        'items' => $p->items->map(fn ($i) => [
            'drug_id' => $i->drug_id,
            'drug_name' => $i->drug_name,
            'usage_type' => $i->usage_type,
            'dosage' => $i->dosage,
            'frequency' => $i->frequency,
            'meal_timing' => $i->meal_timing,
            'duration' => $i->duration,
            'instructions' => $i->instructions,
        ])->values(),
    ])->values();

    $drugsPayload = $drugsCatalog->map(fn ($d) => [
        'id' => $d->id,
        'name' => $d->name,
        'dosage_form' => $d->dosage_form,
        'default_dosage' => $d->default_dosage,
        'default_frequency' => $d->default_frequency,
        'default_duration' => $d->default_duration,
        'default_instructions' => $d->default_instructions,
    ])->values();
?>

<div class="tg-drawer" x-show="panel === 'prescription'" x-cloak @click.self="closePanel()">
    <div class="tg-drawer__panel max-w-xl" x-data="prescriptionForm(<?php echo \Illuminate\Support\Js::from($drugsPayload)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($prescriptionsPayload)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from(route('prescriptions.store', $patient))->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($activeVisitId)->toHtml() ?>)"
         @prescription-edit.window="loadPrescription($event.detail)">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-bold" style="color: var(--ink);" x-text="editingId ? 'ویرایش نسخه' : 'نسخه دارویی'"></h3>
            <button type="button" class="tg-tool" @click="closePanel()"><?php if (isset($component)) { $__componentOriginal3baa94417da9f5dcb14f5f04da758571 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3baa94417da9f5dcb14f5f04da758571 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon-close','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon-close'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3baa94417da9f5dcb14f5f04da758571)): ?>
<?php $attributes = $__attributesOriginal3baa94417da9f5dcb14f5f04da758571; ?>
<?php unset($__attributesOriginal3baa94417da9f5dcb14f5f04da758571); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3baa94417da9f5dcb14f5f04da758571)): ?>
<?php $component = $__componentOriginal3baa94417da9f5dcb14f5f04da758571; ?>
<?php unset($__componentOriginal3baa94417da9f5dcb14f5f04da758571); ?>
<?php endif; ?></button>
        </div>

        <form method="POST" :action="formAction" class="space-y-4" @submit="prepareSubmit">
            <?php echo csrf_field(); ?>
            <template x-if="editingId">
                <input type="hidden" name="_method" value="PUT">
            </template>
            <template x-if="!editingId && visitId">
                <input type="hidden" name="visit_id" :value="visitId">
            </template>

            <div class="space-y-3">
                <template x-for="(item, index) in items" :key="index">
                    <div class="rounded-xl border p-3" style="border-color: var(--line); background: var(--panel-soft);">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-bold" style="color: var(--ink);">دارو <span x-text="index + 1"></span></span>
                            <button type="button" class="text-[10px] font-bold text-red-600" x-show="items.length > 1" @click="removeItem(index)">حذف</button>
                        </div>
                        <div class="space-y-2">
                            <div class="rx-drug-picker" @click.outside="closeDrugPicker(index)">
                                <label class="text-[11px] font-bold" style="color: var(--muted);">انتخاب از فهرست</label>
                                <div class="relative mt-1">
                                    <input
                                        type="search"
                                        class="field-input !py-2 text-sm"
                                        placeholder="جستجوی دارو..."
                                        autocomplete="off"
                                        :value="drugQuery[index] || ''"
                                        @focus="openDrugPicker(index)"
                                        @input="onDrugQuery(index, $event.target.value)"
                                        @keydown.escape.prevent="closeDrugPicker(index)"
                                        @keydown.arrow-down.prevent="moveDrugHighlight(index, 1)"
                                        @keydown.arrow-up.prevent="moveDrugHighlight(index, -1)"
                                        @keydown.enter.prevent="selectHighlightedDrug(index)"
                                    >
                                    <div
                                        class="rx-drug-picker__menu"
                                        x-show="drugPickerIndex === index"
                                        x-cloak
                                        x-transition.opacity.duration.120ms
                                    >
                                        <template x-if="filteredDrugs(index).length === 0">
                                            <p class="rx-drug-picker__empty">دارویی پیدا نشد</p>
                                        </template>
                                        <template x-for="(drug, dIdx) in filteredDrugs(index)" :key="drug.id">
                                            <button
                                                type="button"
                                                class="rx-drug-picker__option"
                                                :class="drugHighlight[index] === dIdx && 'is-active'"
                                                @mousedown.prevent="applyDrug(index, drug.id)"
                                                @mouseenter="drugHighlight[index] = dIdx"
                                            >
                                                <span class="rx-drug-picker__name" x-text="drug.name"></span>
                                                <span class="rx-drug-picker__form" x-show="drug.dosage_form" x-text="drug.dosage_form"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" :name="'items['+index+'][drug_id]'" x-model="item.drug_id">
                            <input type="text" :name="'items['+index+'][drug_name]'" x-model="item.drug_name" class="field-input !py-2 text-sm" placeholder="نام دارو" required>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" :name="'items['+index+'][usage_type]'" x-model="item.usage_type" class="field-input !py-2 text-sm" placeholder="نوع مصرف">
                                <input type="text" :name="'items['+index+'][dosage]'" x-model="item.dosage" class="field-input !py-2 text-sm" placeholder="دوز">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" :name="'items['+index+'][frequency]'" x-model="item.frequency" class="field-input !py-2 text-sm" placeholder="دفعات مصرف">
                                <input type="text" :name="'items['+index+'][duration]'" x-model="item.duration" class="field-input !py-2 text-sm" placeholder="مدت">
                            </div>
                            <select :name="'items['+index+'][meal_timing]'" x-model="item.meal_timing" class="field-input !py-2 text-sm">
                                <option value="">زمان مصرف</option>
                                <option value="before_meal">قبل غذا</option>
                                <option value="after_meal">بعد غذا</option>
                                <option value="with_meal">همراه غذا</option>
                                <option value="empty_stomach">ناشتا</option>
                                <option value="anytime">هر زمان</option>
                            </select>
                            <input type="text" :name="'items['+index+'][instructions]'" x-model="item.instructions" class="field-input !py-2 text-sm" placeholder="توضیحات">
                        </div>
                    </div>
                </template>
            </div>

            <button type="button" class="btn-secondary w-full !py-2 !text-xs" @click="addItem()">+ داروی دیگر</button>

            <div>
                <label class="text-xs font-bold" style="color: var(--muted);">یادداشت نسخه</label>
                <textarea name="notes" rows="2" class="field-input mt-1" x-model="notes" placeholder="توضیح کلی..."></textarea>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn-primary flex-1" x-text="editingId ? 'ذخیره ویرایش' : 'ثبت نسخه'"></button>
                <a
                    x-show="editingId"
                    x-cloak
                    :href="'<?php echo e(url('/patients/'.$patient->id.'/prescriptions')); ?>/' + editingId + '/print'"
                    target="_blank"
                    class="btn-secondary inline-flex items-center !px-3"
                >چاپ</a>
                <button type="button" class="btn-secondary" @click="closePanel()">بستن</button>
            </div>
        </form>
    </div>
</div>

<script>
    function prescriptionForm(drugs, prescriptions, storeUrl, visitId) {
        const emptyItem = () => ({
            drug_id: '',
            drug_name: '',
            usage_type: '',
            dosage: '',
            frequency: '',
            meal_timing: '',
            duration: '',
            instructions: '',
        });

        return {
            drugs: drugs || [],
            prescriptions: prescriptions || [],
            storeUrl: storeUrl,
            visitId: visitId || null,
            editingId: null,
            notes: '',
            items: [emptyItem()],
            drugPickerIndex: null,
            drugQuery: [],
            drugHighlight: [],
            get formAction() {
                if (!this.editingId) return this.storeUrl;
                return this.storeUrl.replace(/\/prescriptions$/, '/prescriptions/' + this.editingId);
            },
            addItem() {
                this.items.push(emptyItem());
            },
            removeItem(index) {
                this.items.splice(index, 1);
                this.drugQuery.splice(index, 1);
                this.drugHighlight.splice(index, 1);
                if (this.drugPickerIndex === index) this.drugPickerIndex = null;
                else if (this.drugPickerIndex !== null && this.drugPickerIndex > index) this.drugPickerIndex -= 1;
            },
            openDrugPicker(index) {
                this.drugPickerIndex = index;
                if (this.drugQuery[index] === undefined || this.drugQuery[index] === null) {
                    this.drugQuery[index] = '';
                }
                this.drugHighlight[index] = 0;
            },
            closeDrugPicker(index) {
                if (this.drugPickerIndex === index) {
                    this.drugPickerIndex = null;
                }
            },
            onDrugQuery(index, value) {
                this.drugQuery[index] = value;
                this.drugHighlight[index] = 0;
                this.drugPickerIndex = index;
            },
            filteredDrugs(index) {
                const q = String(this.drugQuery[index] || '').trim().toLowerCase();
                const list = this.drugs || [];
                if (!q) return list.slice(0, 80);
                return list.filter((d) => {
                    const name = String(d.name || '').toLowerCase();
                    const form = String(d.dosage_form || '').toLowerCase();
                    return name.includes(q) || form.includes(q);
                }).slice(0, 80);
            },
            moveDrugHighlight(index, delta) {
                const list = this.filteredDrugs(index);
                if (!list.length) return;
                const cur = this.drugHighlight[index] || 0;
                this.drugHighlight[index] = (cur + delta + list.length) % list.length;
            },
            selectHighlightedDrug(index) {
                const list = this.filteredDrugs(index);
                const cur = this.drugHighlight[index] || 0;
                if (!list[cur]) return;
                this.applyDrug(index, list[cur].id);
            },
            applyDrug(index, drugId) {
                if (!drugId) return;
                const drug = this.drugs.find((d) => String(d.id) === String(drugId));
                if (!drug) return;
                this.items[index] = {
                    drug_id: drug.id,
                    drug_name: drug.name,
                    usage_type: drug.dosage_form || '',
                    dosage: drug.default_dosage || '',
                    frequency: drug.default_frequency || '',
                    meal_timing: '',
                    duration: drug.default_duration || '',
                    instructions: drug.default_instructions || '',
                };
                this.drugQuery[index] = drug.name;
                this.drugPickerIndex = null;
            },
            loadPrescription(id) {
                const rx = this.prescriptions.find((p) => p.id === id);
                if (!rx) return;
                this.editingId = rx.id;
                this.notes = rx.notes || '';
                this.items = (rx.items && rx.items.length) ? JSON.parse(JSON.stringify(rx.items)) : [emptyItem()];
                this.drugQuery = this.items.map((item) => item.drug_name || '');
                this.drugHighlight = this.items.map(() => 0);
                this.drugPickerIndex = null;
            },
            prepareSubmit(e) {
                if (!this.items.length || !this.items.some((i) => (i.drug_name || '').trim())) {
                    e.preventDefault();
                    alert('حداقل یک دارو وارد کنید');
                }
            },
        };
    }
</script>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\patients\partials\prescription-drawer.blade.php ENDPATH**/ ?>