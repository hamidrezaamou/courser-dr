<x-app-layout body-class="is-admin">
    <x-slot name="header">
        <div class="admin-header">
            <div>
                <p class="admin-header__eyebrow">مدیریت کل سایت</p>
                <h2 class="admin-header__title">برند و چاپ</h2>
            </div>
            <div class="admin-header__actions">
                <a href="{{ route('prints.index') }}" class="btn-secondary btn-primary--compact">پرینت‌ها</a>
            </div>
        </div>
    </x-slot>

    @push('head')
        @include('admin.settings.partials.print-editor-script')
        <style>
            .print-editor-shell { display: grid; grid-template-columns: 1fr; gap: 1rem; }
            @@media (min-width: 1024px) { .print-editor-shell { grid-template-columns: 220px 1fr 300px; } }
            .print-type-btn { width: 100%; text-align: right; border: 1px solid var(--line); background: #fff; border-radius: .75rem; padding: .65rem .75rem; font-size: .8rem; font-weight: 700; cursor: pointer; }
            .print-type-btn.is-active { border-color: var(--primary); background: var(--primary-50, #eff6ff); color: var(--primary); }
            .print-type-btn small { display: block; font-weight: 500; color: var(--muted); margin-top: .2rem; }
            .print-mode-tabs { display: flex; gap: .35rem; margin-bottom: .75rem; flex-wrap: wrap; }
            .print-mode-tab { border: 1px solid var(--line); background: #fff; border-radius: .65rem; padding: .4rem .7rem; font-size: .75rem; font-weight: 800; cursor: pointer; }
            .print-mode-tab.is-active { border-color: var(--brand); background: color-mix(in srgb, var(--brand) 10%, #fff); color: var(--brand-dark); }
            .print-tag-grid { display: flex; flex-wrap: wrap; gap: .35rem; }
            .print-tag-btn { border: 1px dashed var(--line); background: #fff; border-radius: 999px; padding: .25rem .55rem; font-size: .68rem; font-family: ui-monospace, monospace; cursor: pointer; direction: ltr; }
            .print-tag-btn:hover { border-color: var(--primary); color: var(--primary); }
            .print-editor-toolbar { display: flex; flex-wrap: wrap; gap: .35rem; align-items: center; border: 1px solid var(--line); border-bottom: 0; border-radius: .75rem .75rem 0 0; background: linear-gradient(180deg, #fbfbfc, #f3f4f6); padding: .45rem .55rem; }
            .print-editor-toolbar button,
            .print-editor-toolbar select,
            .print-editor-toolbar label.print-tb-file { border: 1px solid var(--line); background: #fff; border-radius: .4rem; padding: .22rem .45rem; font-size: .72rem; cursor: pointer; }
            .print-editor-toolbar label.print-tb-file { position: relative; overflow: hidden; display: inline-flex; align-items: center; }
            .print-editor-toolbar button { font-weight: 800; min-width: 1.7rem; }
            .print-editor-toolbar .print-tb-sep { width: 1px; height: 1.2rem; background: var(--line); margin: 0 .15rem; }
            .print-editor-toolbar input[type="color"] { width: 1.85rem; height: 1.55rem; padding: 0; border: 1px solid var(--line); border-radius: .35rem; background: #fff; cursor: pointer; }
            .print-editor-components { display: flex; flex-wrap: wrap; gap: .35rem; border: 1px solid var(--line); border-top: 0; background: #fff; padding: .45rem .55rem; }
            .print-editor-components button { border: 1px dashed color-mix(in srgb, var(--brand) 35%, var(--line)); background: color-mix(in srgb, var(--brand-soft) 35%, #fff); border-radius: .5rem; padding: .28rem .55rem; font-size: .68rem; font-weight: 800; cursor: pointer; color: var(--brand-dark); }
            .print-editor-components button:hover { border-style: solid; background: color-mix(in srgb, var(--brand-soft) 70%, #fff); }
            .print-sheet-opts { display: flex; flex-wrap: wrap; gap: .75rem 1rem; align-items: center; margin-bottom: .65rem; padding: .55rem .7rem; border: 1px solid var(--line); border-radius: .75rem; background: #fff; font-size: .72rem; font-weight: 700; }
            .print-sheet-opts label { display: inline-flex; align-items: center; gap: .35rem; cursor: pointer; }
            .print-native-editor { min-height: 360px; border: 1px solid var(--line); border-radius: 0 0 .75rem .75rem; background: #fff; padding: .85rem 1rem; font-family: Vazirmatn, Tahoma, sans-serif; font-size: 14px; line-height: 2; direction: rtl; text-align: right; outline: none; }
            .print-native-editor:focus { box-shadow: inset 0 0 0 2px color-mix(in srgb, var(--primary) 25%, transparent); }
            .print-native-editor .print-tag-token { direction: ltr; unicode-bidi: isolate; font-family: ui-monospace, monospace; background: #eff6ff; border-radius: .25rem; padding: 0 .15rem; }
            .print-native-editor .print-sheet-header,
            .print-native-editor .print-sheet-footer { border: 1px dashed #cbd5e1; border-radius: .55rem; padding: .65rem .75rem; margin: .35rem 0 .75rem; background: #f8fafc; }
            .print-native-editor .print-sheet-header { display: flex; align-items: center; gap: .75rem; justify-content: space-between; }
            .print-native-editor .print-sheet-header img,
            .print-native-editor .print-brand-img { max-height: 64px; max-width: 160px; object-fit: contain; }
            .print-native-editor .print-inline-img { max-width: 100%; height: auto; border-radius: .35rem; }
            .print-native-editor .print-hr { border: 0; border-top: 2px solid #111; margin: .85rem 0; }
            .print-native-editor .print-info-box { border: 1px solid #111; border-radius: .5rem; padding: .65rem .8rem; margin: .5rem 0; }
            .print-native-editor table.print-table { width: 100%; border-collapse: collapse; margin: .5rem 0; }
            .print-native-editor table.print-table td { border: 1px solid #111; padding: .35rem .5rem; min-width: 4rem; vertical-align: top; }
            .print-preview-box { border: 1px solid var(--line); border-radius: .75rem; background: #fff; padding: 1rem; min-height: 200px; font-size: 13px; line-height: 2; direction: rtl; text-align: right; }
            .print-preview-box .print-title { text-align: center; font-weight: 800; font-size: 17px; }
            .print-preview-box .print-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 12px; margin: 10px 0; }
            .print-preview-box .print-sheet-header { display: flex; align-items: center; gap: .75rem; justify-content: space-between; margin-bottom: .75rem; padding-bottom: .5rem; border-bottom: 2px solid #111; }
            .print-preview-box .print-sheet-footer { margin-top: .75rem; padding-top: .5rem; border-top: 1px solid #999; font-size: 12px; color: #444; }
            .print-preview-box .print-brand-img, .print-preview-box .print-sheet-header img { max-height: 56px; max-width: 140px; }
            .print-preview-box .print-inline-img { max-width: 100%; height: auto; }
            .print-preview-box table.print-table { width: 100%; border-collapse: collapse; }
            .print-preview-box table.print-table td { border: 1px solid #111; padding: .3rem .45rem; }
            /* ===== Overlay (slide) editor — redesigned ===== */
            .print-overlay-toolbar { display: flex; flex-wrap: wrap; gap: .3rem; align-items: center; padding: .5rem .6rem; border: 1px solid var(--line); border-radius: .75rem; background: linear-gradient(180deg,#fbfbfc,#f3f4f6); }
            .print-overlay-toolbar .grp { display: flex; gap: .3rem; align-items: center; padding-inline-end: .55rem; margin-inline-end: .55rem; border-inline-end: 1px solid var(--line); }
            .print-overlay-toolbar .grp:last-child { border-inline-end: 0; padding-inline-end: 0; margin-inline-end: 0; }
            .print-overlay-toolbar button, .print-overlay-toolbar label.print-tb-file { display: inline-flex; align-items: center; gap: .3rem; border: 1px solid var(--line); background: #fff; border-radius: .55rem; padding: .38rem .6rem; font-size: .72rem; font-weight: 800; cursor: pointer; color: var(--ink, #111); transition: border-color .12s, background .12s; position: relative; overflow: hidden; }
            .print-overlay-toolbar button:hover, .print-overlay-toolbar label.print-tb-file:hover { border-color: var(--brand); background: color-mix(in srgb, var(--brand) 6%, #fff); }
            .print-overlay-toolbar button:disabled { opacity: .4; cursor: not-allowed; }
            .print-overlay-toolbar button.is-danger:hover { border-color: #dc2626; color: #dc2626; background: #fef2f2; }
            .print-overlay-toolbar .zoom-group { gap: .2rem; }
            .print-overlay-toolbar .zoom-group button { padding: .32rem .55rem; min-width: 1.8rem; justify-content: center; }
            .print-overlay-toolbar .zoom-group strong { font-size: .7rem; min-width: 2.8rem; text-align: center; font-variant-numeric: tabular-nums; color: var(--muted); cursor: pointer; }
            .print-overlay-toolbar label.switch { display: inline-flex; align-items: center; gap: .3rem; font-size: .7rem; font-weight: 700; color: var(--muted); cursor: pointer; border: 1px dashed var(--line); border-radius: .55rem; padding: .35rem .55rem; background: #fff; }

            .print-overlay-workspace { display: grid; grid-template-columns: 1fr; gap: .75rem; }
            @@media (min-width: 900px) { .print-overlay-workspace { grid-template-columns: 1fr 210px; } }

            .print-overlay-editor { position: relative; border: 1px solid var(--line); border-radius: .9rem; background: repeating-linear-gradient(45deg,#eceef1,#eceef1 10px,#e4e7eb 10px,#e4e7eb 20px); overflow: auto; padding: 1.25rem; max-height: 74vh; }
            .print-overlay-editor__stage-wrap { display: flex; justify-content: center; min-width: max-content; }
            .print-overlay-editor__stage { position: relative; width: 440px; flex: none; aspect-ratio: 210 / 297; background: #fff; box-shadow: 0 1px 2px rgba(15,23,42,.08), 0 18px 40px -12px rgba(15,23,42,.35); overflow: hidden; touch-action: none; }
            .print-overlay-editor__stage.show-grid { background-image: linear-gradient(rgba(15,23,42,.06) 1px, transparent 1px), linear-gradient(90deg, rgba(15,23,42,.06) 1px, transparent 1px); background-size: 5% 5%; }
            .print-overlay-editor__bg { width: 100%; height: 100%; object-fit: contain; display: block; pointer-events: none; position: absolute; inset: 0; }
            .print-overlay-editor__pdf { width: 100%; height: 100%; border: 0; pointer-events: none; position: absolute; inset: 0; }
            .print-overlay-editor__blank { position: absolute; inset: 0; background: #fff; pointer-events: none; }
            .print-overlay-editor__blank::after { content: 'برگه خالی — پس‌زمینه‌ای انتخاب نشده'; position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; text-align: center; padding: 1rem; font-size: .68rem; font-weight: 700; color: #cbd5e1; pointer-events: none; }
            .print-overlay-editor__obj { position: absolute; box-sizing: border-box; cursor: move; user-select: none; overflow: hidden; z-index: 2; line-height: 1.35; transition: outline-color .1s; }
            .print-overlay-editor__obj:hover { outline: 1.5px dashed color-mix(in srgb, var(--brand) 55%, transparent); }
            .print-overlay-editor__obj.is-selected { outline: 2px solid var(--brand); outline-offset: 1px; z-index: 20 !important; box-shadow: 0 0 0 4px color-mix(in srgb, var(--brand) 14%, transparent); }
            .print-overlay-editor__obj.is-editing { cursor: text; outline: 2px solid #059669; }
            .print-overlay-editor__obj.is-legacy { transform: translate(-50%, -50%); white-space: nowrap; width: auto !important; height: auto !important; padding: .15rem .45rem; border-radius: .35rem; background: rgba(255,255,255,.92); border: 1px dashed var(--brand); font-family: ui-monospace, monospace; }
            .print-overlay-editor__obj--image img { width: 100%; height: 100%; object-fit: contain; display: block; pointer-events: none; }
            .print-overlay-editor__obj--text, .print-overlay-editor__obj--tag { display: flex; align-items: center; }
            .print-overlay-editor__obj [contenteditable="true"] { cursor: text; outline: none; width: 100%; }
            .print-overlay-editor__handle { position: absolute; width: 11px; height: 11px; border-radius: 3px; background: #fff; border: 2px solid var(--brand); box-shadow: 0 1px 3px rgba(15,23,42,.35); z-index: 25; }
            .print-overlay-editor__handle[data-dir="nw"] { top: -6px; left: -6px; cursor: nwse-resize; }
            .print-overlay-editor__handle[data-dir="n"]  { top: -6px; left: 50%; margin-left: -5.5px; cursor: ns-resize; }
            .print-overlay-editor__handle[data-dir="ne"] { top: -6px; right: -6px; cursor: nesw-resize; }
            .print-overlay-editor__handle[data-dir="e"]  { top: 50%; right: -6px; margin-top: -5.5px; cursor: ew-resize; }
            .print-overlay-editor__handle[data-dir="se"] { bottom: -6px; right: -6px; cursor: nwse-resize; }
            .print-overlay-editor__handle[data-dir="s"]  { bottom: -6px; left: 50%; margin-left: -5.5px; cursor: ns-resize; }
            .print-overlay-editor__handle[data-dir="sw"] { bottom: -6px; left: -6px; cursor: nesw-resize; }
            .print-overlay-editor__handle[data-dir="w"]  { top: 50%; left: -6px; margin-top: -5.5px; cursor: ew-resize; }
            .print-overlay-guide { position: absolute; background: #ec4899; z-index: 30; pointer-events: none; }
            .print-overlay-guide--v { top: 0; bottom: 0; width: 1px; }
            .print-overlay-guide--h { left: 0; right: 0; height: 1px; }

            .print-overlay-layers { border: 1px solid var(--line); border-radius: .75rem; background: #fff; padding: .55rem; display: flex; flex-direction: column; gap: .3rem; max-height: 74vh; overflow: auto; }
            .print-overlay-layers h4 { font-size: .7rem; font-weight: 800; color: var(--muted); margin-bottom: .1rem; }
            .print-overlay-layer { display: flex; align-items: center; gap: .4rem; border: 1px solid var(--line); border-radius: .55rem; padding: .35rem .45rem; font-size: .7rem; cursor: pointer; background: #fff; }
            .print-overlay-layer.is-active { border-color: var(--brand); background: color-mix(in srgb, var(--brand) 8%, #fff); }
            .print-overlay-layer .ico { flex: none; width: 1.4rem; height: 1.4rem; display: flex; align-items: center; justify-content: center; border-radius: .4rem; background: #f1f5f9; font-size: .78rem; }
            .print-overlay-layer .lbl { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 700; }
            .print-overlay-layer .acts { display: flex; gap: .1rem; flex: none; }
            .print-overlay-layer .acts button { border: 0; background: transparent; padding: .1rem .3rem; font-size: .72rem; cursor: pointer; color: var(--muted); border-radius: .3rem; }
            .print-overlay-layer .acts button:hover { background: #f1f5f9; color: var(--ink); }
            .print-overlay-layers__empty { font-size: .68rem; color: var(--muted); text-align: center; padding: 1rem .5rem; }

            .print-overlay-inspector { border: 1px solid var(--line); border-radius: .75rem; background: #fff; padding: .65rem; display: grid; gap: .45rem; }
            .print-overlay-inspector label { display: grid; gap: .2rem; font-size: .68rem; font-weight: 800; color: var(--muted); }
            .print-overlay-inspector .field-input { min-height: 2rem; font-size: .75rem; }
            .print-overlay-size { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
            .print-overlay-size button { border: 1px solid var(--line); background: #fff; border-radius: .5rem; min-width: 2rem; padding: .2rem .45rem; font-size: .8rem; font-weight: 800; cursor: pointer; }
            .print-overlay-size strong { font-variant-numeric: tabular-nums; min-width: 3.2rem; text-align: center; font-size: .78rem; }
            .print-overlay-hint { font-size: .68rem; color: var(--muted); line-height: 1.7; }
            .print-subtype-box { border: 1px solid var(--line); border-radius: .75rem; padding: .65rem; background: #fff; max-height: 220px; overflow: auto; }
            .print-subtype-item { display: flex; align-items: center; gap: .4rem; font-size: .72rem; margin-bottom: .25rem; }
            .print-tag-mode { display: flex; gap: .35rem; margin-bottom: .5rem; }
            .print-tag-mode button { flex: 1; border: 1px solid var(--line); background: #fff; border-radius: .55rem; padding: .35rem; font-size: .68rem; font-weight: 700; cursor: pointer; }
            .print-tag-mode button.is-active { border-color: var(--brand); color: var(--brand-dark); background: color-mix(in srgb, var(--brand) 8%, #fff); }
        </style>
    @endpush
    <div class="admin-page">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-admin-dock :admin-section="'brand'" />
            <x-flash />

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="admin-panel space-y-3">
                    <h3 class="text-sm font-extrabold">وضعیت چاپ</h3>
                    @if($storageWritable)
                        <p class="text-xs text-emerald-700">پوشهٔ آپلود قابل نوشتن است.</p>
                    @else
                        <p class="text-xs font-bold text-red-600">پوشهٔ آپلود قابل نوشتن نیست.</p>
                        <p class="admin-panel__hint ltr-data" dir="ltr">{{ $storageRoot }}</p>
                    @endif
                    @if($qrSample)
                        <img src="{{ $qrSample }}" alt="QR" class="h-16 w-16">
                    @endif
                </div>

                <form method="POST" action="{{ route('admin.settings.brand.update') }}" enctype="multipart/form-data" class="admin-panel space-y-3">
                    @csrf
                    @method('PUT')
                    <h3 class="text-sm font-extrabold">برند کلینیک</h3>
                    <div>
                        <x-input-label value="نام پزشک" />
                        <x-text-input name="doctor_name" class="mt-1 block w-full" :value="old('doctor_name', $doctorName)" required />
                    </div>
                    <div>
                        <x-input-label value="تلفن" />
                        <x-text-input name="phone" class="mt-1 block w-full ltr-data" dir="ltr" :value="old('phone', $phone)" />
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <x-input-label value="لوگو" />
                            @if($logoUrl)<img src="{{ $logoUrl }}" class="my-1 max-h-10">@endif
                            <input type="file" name="logo" accept="image/*" class="field-input mt-1 w-full text-xs">
                        </div>
                        <div>
                            <x-input-label value="امضا" />
                            @if($signatureUrl)<img src="{{ $signatureUrl }}" class="my-1 max-h-10">@endif
                            <input type="file" name="signature" accept="image/*" class="field-input mt-1 w-full text-xs">
                        </div>
                        <div>
                            <x-input-label value="مهر" />
                            @if($stampUrl ?? null)<img src="{{ $stampUrl }}" class="my-1 max-h-10">@endif
                            <input type="file" name="stamp" accept="image/*" class="field-input mt-1 w-full text-xs">
                        </div>
                    </div>
                    <div>
                        <x-input-label value="فاصله هدر چاپ (px)" />
                        <x-text-input name="print_header_spacer" type="number" min="0" max="400" class="mt-1 block w-32" :value="old('print_header_spacer', $headerSpacer)" required />
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-primary !py-2 !px-4 !text-sm">ذخیره برند</button>
                    </div>
                </form>
            </div>

            <script>
                window.__printEditorBoot = {
                    types: @json($printTypes),
                    tags: @json($printTags),
                    templates: @json($printTemplates),
                    defaults: @json($printDefaults),
                    templateMeta: @json($printTemplateMeta),
                    surgeryTypes: @json($surgeryTypesForPrint),
                    hospitals: @json($hospitalsForPrint),
                    brandAssets: @json($printBrandAssets),
                };
                window.printTemplateEditorBoot = function printTemplateEditorBoot() {
                    var b = window.__printEditorBoot || {};
                    if (typeof window.printTemplateEditor !== 'function') {
                        console.error('printTemplateEditor is missing');
                        return { init() {}, syncAll() {} };
                    }
                    return window.printTemplateEditor(
                        b.types || [],
                        b.tags || [],
                        b.templates || {},
                        b.defaults || {},
                        b.templateMeta || {},
                        b.surgeryTypes || [],
                        b.hospitals || [],
                        b.brandAssets || {}
                    );
                };
            </script>
            <form method="POST" action="{{ route('admin.settings.brand.print-templates') }}" class="admin-panel space-y-4" id="print-templates-form" enctype="multipart/form-data"
                  x-data="window.printTemplateEditorBoot()"
                  @submit="syncAll()">
                @csrf
                @method('PUT')
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="admin-panel__title">طراحی قالب پرینت</h3>
                        <p class="admin-panel__hint">برگه را مثل اسلاید طراحی کنید: فونت، اندازه، رنگ، سربرگ/پاورقی، لوگو و عکس. تگ‌ها هنگام چاپ با اطلاعات بیمار پر می‌شوند.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="btn-secondary !text-xs !px-3 !py-1.5" @click="addCustomForm()">+ فرم جدید</button>
                        <button type="button" class="btn-secondary !text-xs !px-3 !py-1.5" x-show="isCustomActive()" @click="removeCustomForm()">حذف این فرم</button>
                        <button type="button" class="btn-secondary !text-xs !px-3 !py-1.5" x-show="!isCustomActive()" @click="loadDefault()">بازگشت به پیش‌فرض</button>
                        <button type="button" class="btn-secondary !text-xs !px-3 !py-1.5" @click="updatePreview()">پیش‌نمایش</button>
                        <button type="submit" class="btn-primary !text-xs !px-3 !py-1.5">ذخیره قالب‌ها</button>
                    </div>
                </div>

                <div class="print-editor-shell">
                    <aside class="space-y-2">
                        <template x-for="type in allTypes" :key="type.key">
                            <button type="button" class="print-type-btn" :class="active === type.key && 'is-active'" @click="switchType(type.key)">
                                <span x-text="type.label"></span>
                                <small x-text="type.desc"></small>
                                <small x-show="type.custom" style="color:#059669">● فرم سفارشی</small>
                            </button>
                        </template>
                    </aside>

                    <div>
                        <template x-if="isCustomActive()">
                            <div class="mb-3 grid gap-2 sm:grid-cols-2">
                                <div>
                                    <label class="text-xs font-bold">نام فرم</label>
                                    <input type="text" class="field-input mt-1 w-full text-sm" x-model="customMeta[active].label" @input="updateTypeLabel(active)">
                                </div>
                                <div>
                                    <label class="text-xs font-bold">توضیح کوتاه</label>
                                    <input type="text" class="field-input mt-1 w-full text-sm" x-model="customMeta[active].desc" @input="updateTypeDesc(active)">
                                </div>
                            </div>
                        </template>

                        <div class="print-mode-tabs">
                            <button type="button" class="print-mode-tab" :class="currentMode() === 'html' && 'is-active'" @click="setMode('html')">متن پیوسته (Word)</button>
                            <button type="button" class="print-mode-tab" :class="currentMode() === 'overlay' && 'is-active'" @click="setMode('overlay')">اسلاید پاورپوینت (باکس / درگ)</button>
                        </div>

                        <div :class="currentMode() === 'html' ? '' : 'hidden'" data-print-html-panel>
                            <div class="print-sheet-opts">
                                <label>
                                    <input type="checkbox" x-model="metaFor(active).sheet.show_header_spacer" @change="dirty[active]=true">
                                    فاصله بالای برگه (برای سربرگ فیزیکی)
                                </label>
                                <label>
                                    <input type="checkbox" x-model="metaFor(active).sheet.show_signature" @change="dirty[active]=true">
                                    بلوک امضای پزشک در پایین برگه
                                </label>
                            </div>
                            <div class="print-editor-toolbar">
                                <select title="سبک پاراگراف" @mousedown.prevent @change="formatCmd('formatBlock', $event.target.value); $event.target.selectedIndex = 0">
                                    <option value="" disabled selected>سبک</option>
                                    <option value="p">عادی</option>
                                    <option value="h2">عنوان بزرگ</option>
                                    <option value="h3">زیرعنوان</option>
                                </select>
                                <select title="فونت" @mousedown.prevent @change="applyFontFamily($event.target.value); $event.target.selectedIndex = 0">
                                    <option value="" disabled selected>فونت</option>
                                    <option value="Vazirmatn, Tahoma, sans-serif">وزیرمتن</option>
                                    <option value="Tahoma, sans-serif">تahoma</option>
                                    <option value="Arial, Helvetica, sans-serif">Arial</option>
                                    <option value="'Times New Roman', Times, serif">Times</option>
                                    <option value="'Courier New', monospace">Courier</option>
                                </select>
                                <select title="اندازه" @mousedown.prevent @change="applyFontSize($event.target.value); $event.target.selectedIndex = 0">
                                    <option value="" disabled selected>اندازه</option>
                                    <option value="11px">کوچک</option>
                                    <option value="13px">معمولی</option>
                                    <option value="15px">متوسط</option>
                                    <option value="18px">بزرگ</option>
                                    <option value="22px">خیلی بزرگ</option>
                                    <option value="28px">تیتر</option>
                                </select>
                                <span class="print-tb-sep"></span>
                                <button type="button" title="پررنگ" @mousedown.prevent="formatCmd('bold')">B</button>
                                <button type="button" title="ایتالیک" @mousedown.prevent="formatCmd('italic')"><i>I</i></button>
                                <button type="button" title="زیرخط" @mousedown.prevent="formatCmd('underline')"><u>U</u></button>
                                <button type="button" title="خط‌خورده" @mousedown.prevent="formatCmd('strikeThrough')"><s>S</s></button>
                                <input type="color" title="رنگ متن" value="#111111" @mousedown.prevent @input="applyForeColor($event.target.value)">
                                <input type="color" title="پس‌زمینه متن" value="#fff59d" @mousedown.prevent @input="applyHiliteColor($event.target.value)">
                                <span class="print-tb-sep"></span>
                                <button type="button" title="فهرست شماره‌دار" @mousedown.prevent="formatCmd('insertOrderedList')">1.</button>
                                <button type="button" title="فهرست نقطه‌ای" @mousedown.prevent="formatCmd('insertUnorderedList')">•</button>
                                <button type="button" @mousedown.prevent="formatCmd('justifyRight')">راست</button>
                                <button type="button" @mousedown.prevent="formatCmd('justifyCenter')">وسط</button>
                                <button type="button" @mousedown.prevent="formatCmd('justifyLeft')">چپ</button>
                                <button type="button" @mousedown.prevent="setEditorDirection('rtl')">RTL</button>
                                <button type="button" @mousedown.prevent="setEditorDirection('ltr')">LTR</button>
                                <span class="print-tb-sep"></span>
                                <button type="button" title="بازگشت" @mousedown.prevent="formatCmd('undo')">↶</button>
                                <button type="button" title="جلو" @mousedown.prevent="formatCmd('redo')">↷</button>
                                <button type="button" title="پاک‌کردن استایل" @mousedown.prevent="formatCmd('removeFormat')">پاک‌سازی</button>
                                <label class="print-tb-file" title="درج عکس در برگه">
                                    📷 عکس
                                    <input type="file" accept="image/*" style="position:absolute;width:1px;height:1px;opacity:0;overflow:hidden" @change="onInsertImageFile($event)">
                                </label>
                            </div>
                            <div class="print-editor-components">
                                <button type="button" @mousedown.prevent="insertComponent('header')">سربرگ کلینیک</button>
                                <button type="button" @mousedown.prevent="insertComponent('footer')">پاورقی برگه</button>
                                <button type="button" @mousedown.prevent="insertComponent('logo')">لوگو</button>
                                <button type="button" @mousedown.prevent="insertComponent('signature')">امضا</button>
                                <button type="button" @mousedown.prevent="insertComponent('stamp')">مهر</button>
                                <button type="button" @mousedown.prevent="insertComponent('hr')">خط جداکننده</button>
                                <button type="button" @mousedown.prevent="insertComponent('info')">باکس اطلاعات</button>
                                <button type="button" @mousedown.prevent="insertComponent('grid')">جدول ۲×۲</button>
                                <button type="button" @mousedown.prevent="insertComponent('title')">تیتر وسط</button>
                            </div>
                            <div class="print-native-editor"
                                 id="print-native-editor"
                                 x-ref="printEditor"
                                 contenteditable="true"
                                 spellcheck="false"
                                 dir="rtl"
                                 @input="onEditorInput()"
                                 @mouseup="saveNativeRange()"
                                 @keyup="saveNativeRange()"
                                 @paste="onEditorPaste($event)"></div>
                        </div>

                        <div :class="currentMode() === 'overlay' ? 'space-y-3' : 'hidden'">
                            <div class="print-sheet-opts">
                                <label>
                                    <input type="checkbox" x-model="metaFor(active).sheet.show_header_spacer" @change="dirty[active]=true">
                                    فاصله بالای برگه
                                </label>
                                <label>
                                    <input type="checkbox" x-model="metaFor(active).sheet.show_signature" @change="dirty[active]=true">
                                    امضای پزشک پایین برگه
                                </label>
                            </div>
                            <div class="print-overlay-toolbar">
                                <div class="grp">
                                    <button type="button" @click="addCanvasText()">📝 متن</button>
                                    <label class="print-tb-file">
                                        🖼️ عکس
                                        <input type="file" accept="image/*" style="position:absolute;inset:0;opacity:0;cursor:pointer" @change="addCanvasImage($event)">
                                    </label>
                                    <button type="button" @click="addCanvasBrand('logo')">لوگو</button>
                                    <button type="button" @click="addCanvasBrand('signature')">امضا</button>
                                    <button type="button" @click="addCanvasBrand('stamp')">مهر</button>
                                </div>
                                <div class="grp">
                                    <button type="button" title="تکرار (Ctrl+D)" :disabled="selectedTagIdx === null" @click="duplicateSelected()">⧉ تکرار</button>
                                    <button type="button" :disabled="selectedTagIdx === null" @click="bringSelectedForward()">جلو</button>
                                    <button type="button" :disabled="selectedTagIdx === null" @click="sendSelectedBack()">عقب</button>
                                    <button type="button" class="is-danger" :disabled="selectedTagIdx === null" @click="removeSelectedTag()">حذف</button>
                                </div>
                                <div class="grp zoom-group">
                                    <button type="button" title="کوچک‌نمایی" @click="zoomOut()">−</button>
                                    <strong @click="zoomReset()" title="بازنشانی به ۱۰۰٪" x-text="Math.round(overlayZoom*100)+'%'"></strong>
                                    <button type="button" title="بزرگ‌نمایی" @click="zoomIn()">+</button>
                                </div>
                                <div class="grp">
                                    <label class="switch">
                                        <input type="checkbox" x-model="overlayShowGrid">
                                        شبکه راهنما
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-bold">پس‌زمینه اختیاری (عکس / PDF فرم خالی)</label>
                                <input type="file" class="field-input mt-1 w-full text-xs" accept="image/*,.pdf,application/pdf" @change="onOverlayFile($event)">
                                <p class="print-overlay-hint mt-1">برگه را بکشید، از هندل‌های گوشه/وسط تغییر اندازه دهید — خطوط راهنمای صورتی به‌طور خودکار برای هم‌ترازی نمایش داده می‌شوند. برای ویرایش متنِ باکس، دو‌بار کلیک کنید. Ctrl+D تکرار می‌کند، Delete حذف می‌کند، فلش‌ها جابه‌جا می‌کنند.</p>
                            </div>
                            <div class="print-overlay-workspace">
                                <div class="print-overlay-editor">
                                    <div class="print-overlay-editor__stage-wrap">
                                        <div class="print-overlay-editor__stage" :class="{'show-grid': overlayShowGrid}" :style="'width:' + stageWidthPx() + ';'" x-ref="overlayStage"
                                             @dragover.prevent="onOverlayDragOver($event)"
                                             @drop.prevent="onOverlayDrop($event)"
                                             @click.self="selectedTagIdx = null">
                                            <template x-if="overlayBackgroundUrl(active) && metaFor(active).overlay.background_type === 'pdf'">
                                                <embed :src="overlayBackgroundUrl(active) + '#toolbar=0&navpanes=0'" type="application/pdf" class="print-overlay-editor__pdf">
                                            </template>
                                            <template x-if="overlayBackgroundUrl(active) && metaFor(active).overlay.background_type !== 'pdf'">
                                                <img :src="overlayBackgroundUrl(active)" alt="" class="print-overlay-editor__bg">
                                            </template>
                                            <template x-if="!overlayBackgroundUrl(active)">
                                                <div class="print-overlay-editor__blank"></div>
                                            </template>
                                            <template x-for="gv in overlayGuides.v" :key="'gv-'+gv">
                                                <div class="print-overlay-guide print-overlay-guide--v" :style="'left:'+gv+'%'"></div>
                                            </template>
                                            <template x-for="gh in overlayGuides.h" :key="'gh-'+gh">
                                                <div class="print-overlay-guide print-overlay-guide--h" :style="'top:'+gh+'%'"></div>
                                            </template>
                                            <template x-for="(tag, idx) in metaFor(active).overlay.tags" :key="active + '-obj-' + idx + '-' + (tag.type || 'tag')">
                                                <div class="print-overlay-editor__obj"
                                                     :class="{
                                                        'is-selected': selectedTagIdx === idx,
                                                        'is-editing': editingTextIdx === idx,
                                                        'is-legacy': !!tag.legacy_center,
                                                        'print-overlay-editor__obj--image': (tag.type || 'tag') === 'image',
                                                        'print-overlay-editor__obj--text': (tag.type || 'tag') === 'text',
                                                        'print-overlay-editor__obj--tag': (tag.type || 'tag') === 'tag'
                                                     }"
                                                     :style="canvasObjectStyle(tag)"
                                                     @mousedown.prevent="startOverlayDrag($event, idx)"
                                                     @dblclick.stop="startEditText(idx)"
                                                     @wheel.prevent="onOverlayTagWheel($event, idx)">
                                                    <template x-if="(tag.type || 'tag') === 'image'">
                                                        <img :src="canvasImageSrc(tag)" alt="">
                                                    </template>
                                                    <template x-if="(tag.type || 'tag') === 'text' && editingTextIdx !== idx">
                                                        <span x-text="tag.text || 'متن'" style="white-space:pre-wrap;width:100%"></span>
                                                    </template>
                                                    <template x-if="(tag.type || 'tag') === 'text' && editingTextIdx === idx">
                                                        <div contenteditable="true" style="white-space:pre-wrap"
                                                             @click.stop @mousedown.stop
                                                             @blur="finishEditText(idx, $event)"
                                                             @keydown.escape="$event.target.blur()"
                                                             x-init="$nextTick(() => { $el.innerText = tag.text || ''; $el.focus(); placeCaretEnd($el); })"></div>
                                                    </template>
                                                    <template x-if="(tag.type || 'tag') === 'tag'">
                                                        <span x-text="'{' + tag.key + '}'"></span>
                                                    </template>
                                                    <template x-if="selectedTagIdx === idx && !tag.legacy_center">
                                                        <template x-for="dir in resizeHandles" :key="'h-'+dir">
                                                            <i class="print-overlay-editor__handle" :data-dir="dir" @mousedown.stop.prevent="startOverlayResize($event, idx, dir)"></i>
                                                        </template>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div class="print-overlay-layers">
                                    <h4>لایه‌ها (<span x-text="metaFor(active).overlay.tags.length"></span>)</h4>
                                    <template x-for="row in layerList()" :key="'layer-'+row.idx">
                                        <div class="print-overlay-layer" :class="{'is-active': selectedTagIdx === row.idx}" @click="selectLayer(row.idx)">
                                            <span class="ico" x-text="layerIcon(row.tag)"></span>
                                            <span class="lbl" x-text="layerLabel(row.tag)"></span>
                                            <span class="acts">
                                                <button type="button" title="جلوتر" @click.stop="moveLayerUp(row.idx)">▲</button>
                                                <button type="button" title="عقب‌تر" @click.stop="moveLayerDown(row.idx)">▼</button>
                                                <button type="button" title="حذف" @click.stop="removeLayer(row.idx)" style="color:#b91c1c">✕</button>
                                            </span>
                                        </div>
                                    </template>
                                    <p class="print-overlay-layers__empty" x-show="!metaFor(active).overlay.tags.length">هنوز آبجکتی اضافه نشده. از نوار بالا شروع کنید.</p>
                                </div>
                            </div>

                            <div class="print-overlay-inspector" x-show="selectedTagIdx !== null && selectedOverlayTag()" x-cloak>
                                <div class="text-xs font-extrabold" style="color:var(--ink)">تنظیمات آبجکت انتخاب‌شده</div>
                                <template x-if="selectedOverlayTag() && selectedOverlayTag().type === 'text'">
                                    <label>
                                        متن باکس
                                        <textarea class="field-input" rows="3" :value="selectedOverlayTag().text" @input="selectedOverlayTag().text = $event.target.value; dirty[active]=true; updatePreview()"></textarea>
                                    </label>
                                </template>
                                <template x-if="selectedOverlayTag()">
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        <label>
                                            فونت
                                            <select class="field-input" :value="selectedOverlayTag().fontFamily" @change="selectedOverlayTag().fontFamily = $event.target.value; dirty[active]=true; updatePreview()">
                                                <option value="Vazirmatn, Tahoma, sans-serif">وزیرمتن</option>
                                                <option value="Tahoma, sans-serif">تahoma</option>
                                                <option value="Arial, Helvetica, sans-serif">Arial</option>
                                                <option value="'Times New Roman', Times, serif">Times</option>
                                            </select>
                                        </label>
                                        <label>
                                            اندازه فونت
                                            <input type="number" min="8" max="72" class="field-input" :value="selectedOverlayTag().fontSize" @input="selectedOverlayTag().fontSize = Number($event.target.value || 14); dirty[active]=true; updatePreview()">
                                        </label>
                                        <label>
                                            رنگ متن
                                            <input type="color" class="field-input" :value="selectedOverlayTag().color || '#111111'" @input="selectedOverlayTag().color = $event.target.value; dirty[active]=true; updatePreview()">
                                        </label>
                                        <label>
                                            پس‌زمینه باکس
                                            <input type="color" class="field-input" :value="(!selectedOverlayTag().bg || selectedOverlayTag().bg === 'transparent') ? '#ffffff' : selectedOverlayTag().bg" @input="selectedOverlayTag().bg = $event.target.value; dirty[active]=true; updatePreview()">
                                        </label>
                                        <label>
                                            تراز
                                            <select class="field-input" :value="selectedOverlayTag().align || 'rtl'" @change="selectedOverlayTag().align = $event.target.value; dirty[active]=true; updatePreview()">
                                                <option value="rtl">راست</option>
                                                <option value="center">وسط</option>
                                                <option value="ltr">چپ</option>
                                            </select>
                                        </label>
                                        <label class="!flex !flex-row items-center gap-2 mt-5">
                                            <input type="checkbox" :checked="!!selectedOverlayTag().border" @change="selectedOverlayTag().border = $event.target.checked; dirty[active]=true; updatePreview()">
                                            حاشیه باکس
                                        </label>
                                    </div>
                                </template>
                                <div class="print-overlay-size">
                                    <span class="text-[11px] font-bold">جابه‌جایی ریز</span>
                                    <button type="button" @click="nudgeSelected(-1,0)">←</button>
                                    <button type="button" @click="nudgeSelected(1,0)">→</button>
                                    <button type="button" @click="nudgeSelected(0,-1)">↑</button>
                                    <button type="button" @click="nudgeSelected(0,1)">↓</button>
                                    <span class="text-[11px] font-bold mr-2">عرض/ارتفاع</span>
                                    <button type="button" @click="nudgeSelectedSize(-2,-2)">−</button>
                                    <button type="button" @click="nudgeSelectedSize(2,2)">+</button>
                                </div>
                            </div>
                        </div>

                        <div id="template-meta-hidden"></div>
                        <div id="overlay-file-inputs"></div>

                        @foreach(\App\Support\PrintTemplateEngine::builtinTypes() as $type)
                            <textarea name="templates[{{ $type['key'] }}]" id="tpl-{{ $type['key'] }}" hidden></textarea>
                            <input type="hidden" name="reset_{{ $type['key'] }}" :value="resets['{{ $type['key'] }}'] ? 1 : 0">
                        @endforeach
                        <div id="custom-forms-hidden"></div>
                    </div>

                    <aside class="space-y-3">
                        <div>
                            <h4 class="text-xs font-extrabold mb-2">حالت تگ</h4>
                            <div class="print-tag-mode">
                                <button type="button" :class="tagMode === 'insert' && 'is-active'" @click="tagMode = 'insert'" x-show="currentMode() === 'html'">کلیک → درج در متن</button>
                                <button type="button" :class="tagMode === 'drag' && 'is-active'" @click="tagMode = 'drag'" x-show="currentMode() === 'overlay'">درگ → روی اسلاید</button>
                            </div>
                            <div class="print-tag-grid">
                                @foreach($printTags as $tag)
                                    <button type="button" class="print-tag-btn"
                                            :draggable="currentMode() === 'overlay'"
                                            @mousedown.stop="onTagMouseDown($event, '{{ $tag['key'] }}')"
                                            @dragstart.stop="onTagDragStart($event, '{{ $tag['key'] }}')"
                                            title="{{ $tag['label'] }}">{ {{ $tag['key'] }} }</button>
                                @endforeach
                            </div>
                            <p class="mt-2 text-[11px]" style="color: var(--muted);">در اسلاید پاورپوینت: تگ را بکشید روی برگه؛ باکس متن و عکس را جابه‌جا و ریسایز کنید.</p>
                        </div>

                        <div>
                            <h4 class="text-xs font-extrabold mb-2">زیرگروه‌های مرتبط</h4>
                            <p class="mb-2 text-[11px]" style="color:var(--muted)">خالی = برای همه زیرگروه‌ها. اگر تیک بزنید، فقط همان زیرگروه در پرینت بیمار می‌آید.</p>
                            <div class="print-subtype-box">
                                <template x-for="stype in surgeryTypes" :key="'st-'+stype.id">
                                    <div class="mb-2">
                                        <div class="text-[11px] font-bold mb-1" x-text="stype.name"></div>
                                        <label class="print-subtype-item" x-show="stype.has_general">
                                            <input type="checkbox" :checked="subtypeSelected(stype.id, 'general')" @change="toggleSubtype(stype.id, 'general')">
                                            <span>عمومی</span>
                                        </label>
                                        <template x-for="sub in (stype.subtypes || [])" :key="'sub-'+sub.id">
                                            <label class="print-subtype-item">
                                                <input type="checkbox" :checked="subtypeSelected(stype.id, sub.id)" @change="toggleSubtype(stype.id, sub.id)">
                                                <span x-text="sub.name"></span>
                                            </label>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-extrabold mb-2">بیمارستان‌های مرتبط</h4>
                            <p class="mb-2 text-[11px]" style="color:var(--muted)">خالی = برای همه بیمارستان‌ها. هر بیمارستان فرم جدا دارد؛ تیک بزنید تا فقط برای همان بیمارستان نمایش داده شود.</p>
                            <div class="print-subtype-box">
                                <template x-for="hospital in hospitals" :key="'h-'+hospital.id">
                                    <label class="print-subtype-item">
                                        <input type="checkbox" :checked="hospitalSelected(hospital.id)" @change="toggleHospital(hospital.id)">
                                        <span x-text="hospital.name"></span>
                                    </label>
                                </template>
                                <p class="mt-1 text-[11px]" style="color:var(--muted)" x-show="!hospitals.length">بیمارستانی ثبت نشده است.</p>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-extrabold mb-2">پیش‌نمایش (نمونه)</h4>
                            <div class="print-preview-box" x-html="previewHtml"></div>
                        </div>
                    </aside>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
