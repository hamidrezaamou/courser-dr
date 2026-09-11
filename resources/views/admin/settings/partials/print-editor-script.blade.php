<script>
window.printTemplateEditor = function printTemplateEditor(types, tags, templates, defaults, templateMeta, surgeryTypes, hospitals, brandAssets) {
    const tagDefs = tags || [];
    const brand = brandAssets || {};
    const storageBase = @js(rtrim(asset('storage'), '/') . '/');
    const minTagSize = 0.4;
    const maxTagSize = 3.5;

    function defaultSheet() {
        return { show_signature: true, show_header_spacer: true };
    }

    function defaultMeta() {
        return {
            mode: 'html',
            subtype_ids: [],
            hospital_ids: [],
            sheet: defaultSheet(),
            overlay: { background: '', background_type: 'image', tags: [] },
        };
    }

    return {
        allTypes: (types || []).map((t) => Object.assign({}, t)),
        active: types[0]?.key || 'hospital',
        templates: Object.assign({}, templates || {}),
        defaults: defaults || {},
        templateMeta: Object.assign({}, templateMeta || {}),
        surgeryTypes: surgeryTypes || [],
        hospitals: hospitals || [],
        brandAssets: brand,
        customMeta: {},
        dirty: {},
        resets: {},
        previewHtml: '',
        savedRange: null,
        tagMode: 'insert',
        dragTagKey: '',
        overlayDrag: null,
        selectedTagIdx: null,
        overlayPreviewUrls: {},
        overlayPendingFiles: {},

        init() {
            this.allTypes.forEach((t) => {
                if (t.custom) {
                    this.customMeta[t.key] = { label: t.label, desc: t.desc || '' };
                }
                if (!this.templateMeta[t.key]) {
                    this.templateMeta[t.key] = defaultMeta();
                }
                if (!this.templateMeta[t.key].sheet) {
                    this.templateMeta[t.key].sheet = defaultSheet();
                }
                if (typeof this.templateMeta[t.key].sheet.show_signature !== 'boolean') {
                    this.templateMeta[t.key].sheet.show_signature = true;
                }
                if (typeof this.templateMeta[t.key].sheet.show_header_spacer !== 'boolean') {
                    this.templateMeta[t.key].sheet.show_header_spacer = true;
                }
                if (!Array.isArray(this.templateMeta[t.key].hospital_ids)) {
                    this.templateMeta[t.key].hospital_ids = [];
                }
                (this.templateMeta[t.key].overlay?.tags || []).forEach((tag) => {
                    if (tag && (tag.size == null || tag.size === '')) tag.size = 1;
                });
            });

            document.addEventListener('selectionchange', () => this.saveNativeRange());
            window.addEventListener('mousemove', (e) => this.onOverlayDragMove(e));
            window.addEventListener('mouseup', () => this.onOverlayDragEnd());
            window.addEventListener('keydown', (e) => this.onOverlayKey(e));

            this.$nextTick(() => {
                this.loadEditor(this.active);
                this.updatePreview();
            });
        },

        editorEl() {
            return this.$refs.printEditor || document.getElementById('print-native-editor');
        },

        saveNativeRange() {
            const editor = this.editorEl();
            if (!editor) return;
            const sel = window.getSelection();
            if (!sel || !sel.rangeCount) return;
            const range = sel.getRangeAt(0);
            const node = range.commonAncestorContainer;
            if (editor === node || editor.contains(node)) {
                try {
                    this.savedRange = range.cloneRange();
                } catch (err) {}
            }
        },

        restoreNativeRange() {
            const editor = this.editorEl();
            if (!editor || !this.savedRange) return false;
            try {
                editor.focus();
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(this.savedRange);
                return true;
            } catch (err) {
                return false;
            }
        },

        editorHtml() {
            const el = this.editorEl();
            return el ? el.innerHTML : '';
        },

        setEditorHtml(html) {
            const el = this.editorEl();
            if (!el) return;
            el.innerHTML = html || '<p><br></p>';
            this.savedRange = null;
        },

        onEditorInput() {
            this.dirty[this.active] = true;
            this.templates[this.active] = this.editorHtml();
            this.saveNativeRange();
            this.updatePreview();
        },

        formatCmd(cmd, value) {
            this.restoreNativeRange();
            const editor = this.editorEl();
            if (editor) editor.focus();
            try { document.execCommand('styleWithCSS', false, true); } catch (err) {}
            document.execCommand(cmd, false, value || null);
            this.onEditorInput();
        },

        wrapSelectionStyle(styles) {
            this.restoreNativeRange();
            const editor = this.editorEl();
            if (!editor) return;
            editor.focus();
            const sel = window.getSelection();
            if (!sel || !sel.rangeCount) return;
            const range = sel.getRangeAt(0);
            if (!editor.contains(range.commonAncestorContainer) && editor !== range.commonAncestorContainer) return;

            let span;
            if (range.collapsed) {
                span = document.createElement('span');
                Object.assign(span.style, styles);
                span.appendChild(document.createTextNode('\u200b'));
                range.insertNode(span);
                const after = document.createRange();
                after.selectNodeContents(span);
                after.collapse(false);
                sel.removeAllRanges();
                sel.addRange(after);
            } else {
                span = document.createElement('span');
                Object.assign(span.style, styles);
                try {
                    range.surroundContents(span);
                } catch (err) {
                    const frag = range.extractContents();
                    span.appendChild(frag);
                    range.insertNode(span);
                }
            }
            this.savedRange = sel.getRangeAt(0).cloneRange();
            this.onEditorInput();
        },

        applyFontFamily(family) {
            if (!family) return;
            this.wrapSelectionStyle({ fontFamily: family });
        },

        applyFontSize(size) {
            if (!size) return;
            this.wrapSelectionStyle({ fontSize: size });
        },

        applyForeColor(color) {
            if (!color) return;
            this.formatCmd('foreColor', color);
        },

        applyHiliteColor(color) {
            if (!color) return;
            if (!document.execCommand('hiliteColor', false, color)) {
                this.formatCmd('backColor', color);
            } else {
                this.onEditorInput();
            }
        },

        insertHtmlAtCursor(html) {
            this.restoreNativeRange();
            const editor = this.editorEl();
            if (!editor) return;
            editor.focus();
            const sel = window.getSelection();
            let range = null;
            if (sel && sel.rangeCount) {
                const current = sel.getRangeAt(0);
                if (editor === current.startContainer || editor.contains(current.startContainer)) {
                    range = current;
                }
            }
            if (!range) {
                range = document.createRange();
                range.selectNodeContents(editor);
                range.collapse(false);
                sel.removeAllRanges();
                sel.addRange(range);
            }
            range.deleteContents();
            const temp = document.createElement('div');
            temp.innerHTML = html;
            const frag = document.createDocumentFragment();
            let last = null;
            while (temp.firstChild) {
                last = frag.appendChild(temp.firstChild);
            }
            range.insertNode(frag);
            if (last) {
                const after = document.createRange();
                after.setStartAfter(last);
                after.collapse(true);
                sel.removeAllRanges();
                sel.addRange(after);
                this.savedRange = after.cloneRange();
            }
            this.onEditorInput();
        },

        brandImgHtml(src, alt, cls) {
            if (!src) return '<span style="color:#b91c1c;font-size:12px">[' + alt + ' در برند تنظیم نشده]</span>';
            return '<img src="' + src + '" alt="' + alt + '" class="' + (cls || 'print-brand-img') + '">';
        },

        insertComponent(kind) {
            const doctor = this.brandAssets.doctorName || 'نام پزشک';
            const phone = this.brandAssets.phone || '';
            let html = '';
            if (kind === 'header') {
                html = '<div class="print-sheet-header" contenteditable="true">'
                    + '<div>' + this.brandImgHtml(this.brandAssets.logo, 'لوگو') + '</div>'
                    + '<div style="text-align:center;flex:1">'
                    + '<div style="font-size:18px;font-weight:800">' + doctor + '</div>'
                    + (phone ? '<div style="font-size:12px;color:#555">' + phone + '</div>' : '')
                    + '<div style="font-size:12px;color:#666">{hospital}</div>'
                    + '</div>'
                    + '<div style="font-size:12px;direction:ltr;text-align:left">{today}</div>'
                    + '</div><p><br></p>';
            } else if (kind === 'footer') {
                html = '<div class="print-sheet-footer" contenteditable="true">'
                    + '<div style="display:flex;justify-content:space-between;gap:1rem;align-items:center">'
                    + '<span>تلفن کلینیک: ' + (phone || '{clinicPhone}') + '</span>'
                    + '<span>بیمار: {name} · تاریخ عمل: {date}</span>'
                    + '</div></div>';
            } else if (kind === 'logo') {
                html = this.brandImgHtml(this.brandAssets.logo, 'لوگو');
            } else if (kind === 'signature') {
                html = this.brandImgHtml(this.brandAssets.signature, 'امضا');
            } else if (kind === 'stamp') {
                html = this.brandImgHtml(this.brandAssets.stamp, 'مهر');
            } else if (kind === 'hr') {
                html = '<hr class="print-hr"><p><br></p>';
            } else if (kind === 'info') {
                html = '<div class="print-info-box"><p><strong>نام بیمار:</strong> {name}</p>'
                    + '<p><strong>کد ملی:</strong> {nationalCode}</p>'
                    + '<p><strong>موبایل:</strong> {mobile}</p>'
                    + '<p><strong>تاریخ عمل:</strong> {date} · <strong>نوع عمل:</strong> {surgeryType}</p></div><p><br></p>';
            } else if (kind === 'grid') {
                html = '<table class="print-table"><tr><td><br></td><td><br></td></tr><tr><td><br></td><td><br></td></tr></table><p><br></p>';
            } else if (kind === 'title') {
                html = '<div class="print-title" style="text-align:center;font-size:20px;font-weight:800;margin:0 0 12px">عنوان برگه</div><p><br></p>';
            }
            if (html) this.insertHtmlAtCursor(html);
        },

        onInsertImageFile(e) {
            const file = e.target.files && e.target.files[0];
            e.target.value = '';
            if (!file) return;
            if (!file.type || file.type.indexOf('image/') !== 0) {
                alert('فقط فایل تصویری مجاز است.');
                return;
            }
            if (file.size > 700 * 1024) {
                alert('حجم عکس حداکثر حدود ۷۰۰ کیلوبایت باشد تا قالب سبک بماند.');
                return;
            }
            const reader = new FileReader();
            reader.onload = () => {
                const src = String(reader.result || '');
                if (!src) return;
                this.insertHtmlAtCursor('<p style="text-align:center"><img src="' + src + '" alt="" class="print-inline-img" style="max-width:100%;height:auto"></p><p><br></p>');
            };
            reader.readAsDataURL(file);
        },

        onEditorPaste(e) {
            const items = e.clipboardData && e.clipboardData.items;
            if (!items) return;
            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                if (item.type && item.type.indexOf('image/') === 0) {
                    e.preventDefault();
                    const file = item.getAsFile();
                    if (!file) return;
                    if (file.size > 700 * 1024) {
                        alert('حجم عکس حداکثر حدود ۷۰۰ کیلوبایت باشد.');
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = () => {
                        this.insertHtmlAtCursor('<p style="text-align:center"><img src="' + reader.result + '" alt="" class="print-inline-img" style="max-width:100%;height:auto"></p><p><br></p>');
                    };
                    reader.readAsDataURL(file);
                    return;
                }
            }
        },

        setEditorDirection(dir) {
            const editor = this.editorEl();
            if (!editor) return;
            this.restoreNativeRange();
            editor.setAttribute('dir', dir);
            editor.style.direction = dir;
            editor.style.textAlign = dir === 'rtl' ? 'right' : 'left';
            this.formatCmd('justify' + (dir === 'rtl' ? 'Right' : 'Left'));
        },

        metaFor(key) {
            if (!this.templateMeta[key]) {
                this.templateMeta[key] = defaultMeta();
            }
            if (!this.templateMeta[key].overlay) {
                this.templateMeta[key].overlay = { background: '', background_type: 'image', tags: [] };
            }
            if (!Array.isArray(this.templateMeta[key].overlay.tags)) {
                this.templateMeta[key].overlay.tags = [];
            }
            if (!this.templateMeta[key].sheet) {
                this.templateMeta[key].sheet = defaultSheet();
            }
            return this.templateMeta[key];
        },

        currentMode() {
            return this.metaFor(this.active).mode === 'overlay' ? 'overlay' : 'html';
        },

        setMode(mode) {
            if (this.currentMode() === 'html') {
                this.templates[this.active] = this.editorHtml();
            }
            this.metaFor(this.active).mode = mode === 'overlay' ? 'overlay' : 'html';
            this.tagMode = mode === 'overlay' ? 'drag' : 'insert';
            this.selectedTagIdx = null;
            if (mode === 'html') {
                this.$nextTick(() => this.loadEditor(this.active));
            }
            this.updatePreview();
        },

        isCustomActive() {
            return String(this.active).indexOf('custom_') === 0;
        },

        switchType(key) {
            if (this.currentMode() === 'html') {
                this.templates[this.active] = this.editorHtml();
            }
            this.active = key;
            this.selectedTagIdx = null;
            this.$nextTick(() => {
                this.loadEditor(key);
                this.updatePreview();
            });
        },

        loadEditor(key) {
            if (this.currentMode() !== 'html') return;
            const html = this.templates[key] || this.defaults[key] || '<p><br></p>';
            this.setEditorHtml(html);
            const editor = this.editorEl();
            if (editor) {
                editor.setAttribute('dir', 'rtl');
                editor.style.direction = 'rtl';
                editor.style.textAlign = 'right';
            }
        },

        overlayBackgroundUrl(key) {
            if (this.overlayPreviewUrls[key]) return this.overlayPreviewUrls[key];
            const bg = this.metaFor(key).overlay.background || '';
            if (!bg) return '';
            if (bg.startsWith('http') || bg.startsWith('/')) return bg;
            return storageBase + bg.replace(/^\/+/, '');
        },

        onTagMouseDown(e, key) {
            if (this.currentMode() !== 'html') return;
            e.preventDefault();
            e.stopPropagation();
            this.insertTag(key);
        },

        onTagDragStart(e, key) {
            if (this.currentMode() !== 'overlay') {
                e.preventDefault();
                return;
            }
            this.dragTagKey = key;
            if (e.dataTransfer) {
                e.dataTransfer.setData('text/plain', key);
                e.dataTransfer.effectAllowed = 'copy';
            }
            this.tagMode = 'drag';
        },

        onOverlayDragOver(e) {
            if (this.currentMode() !== 'overlay') return;
            e.preventDefault();
            if (e.dataTransfer) e.dataTransfer.dropEffect = 'copy';
        },

        onOverlayDrop(e) {
            if (this.currentMode() !== 'overlay') return;
            e.preventDefault();
            const key = this.dragTagKey || (e.dataTransfer ? e.dataTransfer.getData('text/plain') : '');
            if (!key) return;

            const stage = this.$refs.overlayStage;
            if (!stage) return;
            const rect = stage.getBoundingClientRect();
            if (!rect.width || !rect.height) return;

            const x = Math.max(0, Math.min(92, ((e.clientX - rect.left) / rect.width) * 100));
            const y = Math.max(0, Math.min(95, ((e.clientY - rect.top) / rect.height) * 100));

            this.metaFor(this.active).overlay.tags.push(this.newCanvasObject('tag', {
                key,
                x: Math.round(x * 10) / 10,
                y: Math.round(y * 10) / 10,
                w: 24,
                h: 6,
            }));
            this.selectedTagIdx = this.metaFor(this.active).overlay.tags.length - 1;
            this.dirty[this.active] = true;
            this.dragTagKey = '';
            this.updatePreview();
        },

        newCanvasObject(type, overrides = {}) {
            const z = (this.metaFor(this.active).overlay.tags || []).length + 1;
            const base = {
                type,
                x: 18,
                y: 18,
                w: type === 'image' ? 32 : 36,
                h: type === 'image' ? 18 : 10,
                align: 'rtl',
                fontSize: type === 'tag' ? 13 : 14,
                fontFamily: 'Vazirmatn, Tahoma, sans-serif',
                fontWeight: '700',
                color: '#111111',
                bg: type === 'image' ? 'transparent' : '#ffffff',
                border: type !== 'image',
                z,
                size: 1,
                legacy_center: false,
                text: 'متن خود را بنویسید',
                key: '',
                src: '',
            };
            return Object.assign(base, overrides, { type });
        },

        addCanvasText() {
            this.metaFor(this.active).overlay.tags.push(this.newCanvasObject('text', {
                text: 'متن باکس — اینجا بنویسید',
                x: 20,
                y: 22,
                w: 40,
                h: 12,
            }));
            this.selectedTagIdx = this.metaFor(this.active).overlay.tags.length - 1;
            this.dirty[this.active] = true;
            this.updatePreview();
        },

        addCanvasImage(e) {
            const file = e.target.files && e.target.files[0];
            e.target.value = '';
            if (!file || !file.type || file.type.indexOf('image/') !== 0) return;
            if (file.size > 700 * 1024) {
                alert('حجم عکس حداکثر حدود ۷۰۰ کیلوبایت باشد.');
                return;
            }
            const reader = new FileReader();
            reader.onload = () => {
                this.metaFor(this.active).overlay.tags.push(this.newCanvasObject('image', {
                    src: String(reader.result || ''),
                    x: 25,
                    y: 25,
                    w: 35,
                    h: 22,
                    border: false,
                    bg: 'transparent',
                }));
                this.selectedTagIdx = this.metaFor(this.active).overlay.tags.length - 1;
                this.dirty[this.active] = true;
                this.updatePreview();
            };
            reader.readAsDataURL(file);
        },

        addCanvasBrand(kind) {
            const src = kind === 'logo' ? this.brandAssets.logo
                : (kind === 'signature' ? this.brandAssets.signature : this.brandAssets.stamp);
            if (!src) {
                alert('ابتدا در بخش برند، ' + (kind === 'logo' ? 'لوگو' : (kind === 'signature' ? 'امضا' : 'مهر')) + ' را آپلود کنید.');
                return;
            }
            this.metaFor(this.active).overlay.tags.push(this.newCanvasObject('image', {
                src,
                x: kind === 'logo' ? 8 : 65,
                y: kind === 'logo' ? 6 : 78,
                w: 22,
                h: 12,
                border: false,
                bg: 'transparent',
            }));
            this.selectedTagIdx = this.metaFor(this.active).overlay.tags.length - 1;
            this.dirty[this.active] = true;
            this.updatePreview();
        },

        canvasImageSrc(tag) {
            const src = (tag && tag.src) || '';
            if (!src) return '';
            if (src.startsWith('http') || src.startsWith('/') || src.startsWith('data:')) return src;
            return storageBase + src.replace(/^\/+/, '');
        },

        canvasObjectStyle(tag) {
            if (!tag) return '';
            if (tag.legacy_center) {
                const size = this.clampTagSize(tag.size);
                return 'left:' + tag.x + '%;top:' + tag.y + '%;direction:' + (tag.align || 'rtl')
                    + ';font-size:' + (size * 12) + 'px;z-index:' + (tag.z || 1) + ';';
            }
            const align = tag.align || 'rtl';
            return 'left:' + (tag.x || 0) + '%;top:' + (tag.y || 0) + '%;width:' + (tag.w || 20) + '%;height:' + (tag.h || 8) + '%;'
                + 'z-index:' + (tag.z || 1) + ';'
                + 'font-size:' + (tag.fontSize || 14) + 'px;'
                + 'font-family:' + (tag.fontFamily || 'Vazirmatn, Tahoma, sans-serif') + ';'
                + 'font-weight:' + (tag.fontWeight || '700') + ';'
                + 'color:' + (tag.color || '#111') + ';'
                + 'background:' + (tag.bg || 'transparent') + ';'
                + 'text-align:' + (align === 'center' ? 'center' : (align === 'ltr' ? 'left' : 'right')) + ';'
                + 'direction:' + (align === 'ltr' ? 'ltr' : 'rtl') + ';'
                + (tag.border ? 'border:1px solid #111;border-radius:4px;padding:4px 6px;' : 'border:0;padding:0;');
        },

        editCanvasObject(idx) {
            const tag = this.metaFor(this.active).overlay.tags[idx];
            if (!tag || tag.type !== 'text') return;
            this.selectedTagIdx = idx;
            const next = prompt('متن باکس:', tag.text || '');
            if (next === null) return;
            tag.text = next;
            this.dirty[this.active] = true;
            this.updatePreview();
        },

        nudgeSelected(dx, dy) {
            const tag = this.selectedOverlayTag();
            if (!tag) return;
            tag.x = Math.max(0, Math.min(98, Number(tag.x || 0) + dx));
            tag.y = Math.max(0, Math.min(98, Number(tag.y || 0) + dy));
            this.dirty[this.active] = true;
            this.updatePreview();
        },

        nudgeSelectedSize(dw, dh) {
            const tag = this.selectedOverlayTag();
            if (!tag || tag.legacy_center) {
                this.nudgeTagSize(dw > 0 ? 0.1 : -0.1);
                return;
            }
            tag.w = Math.max(4, Math.min(100, Number(tag.w || 20) + dw));
            tag.h = Math.max(3, Math.min(100, Number(tag.h || 8) + dh));
            this.dirty[this.active] = true;
            this.updatePreview();
        },

        bringSelectedForward() {
            const tag = this.selectedOverlayTag();
            if (!tag) return;
            tag.z = Number(tag.z || 1) + 1;
            this.dirty[this.active] = true;
            this.updatePreview();
        },

        sendSelectedBack() {
            const tag = this.selectedOverlayTag();
            if (!tag) return;
            tag.z = Math.max(1, Number(tag.z || 1) - 1);
            this.dirty[this.active] = true;
            this.updatePreview();
        },

        overlayTagStyle(tag) {
            return this.canvasObjectStyle(tag);
        },

        clampTagSize(size) {
            const n = Number(size);
            if (!n || n <= 0) return 1;
            return Math.round(Math.max(minTagSize, Math.min(maxTagSize, n)) * 100) / 100;
        },

        selectedOverlayTag() {
            if (this.selectedTagIdx === null || this.selectedTagIdx === undefined) return null;
            return this.metaFor(this.active).overlay.tags[this.selectedTagIdx] || null;
        },

        tagSizeLabel() {
            const tag = this.selectedOverlayTag();
            if (!tag) return '100%';
            if (!tag.legacy_center) return Math.round(tag.w || 20) + '×' + Math.round(tag.h || 8);
            return Math.round(this.clampTagSize(tag.size || 1) * 100) + '%';
        },

        setTagSize(idx, size) {
            const tag = this.metaFor(this.active).overlay.tags[idx];
            if (!tag) return;
            tag.size = this.clampTagSize(size);
            this.dirty[this.active] = true;
            this.updatePreview();
        },

        nudgeTagSize(delta) {
            const tag = this.selectedOverlayTag();
            if (!tag) return;
            if (tag.legacy_center) {
                this.setTagSize(this.selectedTagIdx, this.clampTagSize(tag.size || 1) + delta);
                return;
            }
            this.nudgeSelectedSize(delta > 0 ? 2 : -2, delta > 0 ? 1 : -1);
        },

        onOverlayTagWheel(e, idx) {
            this.selectedTagIdx = idx;
            const tag = this.metaFor(this.active).overlay.tags[idx];
            if (!tag) return;
            if (tag.legacy_center) {
                const step = e.deltaY < 0 ? 0.08 : -0.08;
                this.setTagSize(idx, this.clampTagSize(tag.size || 1) + step);
                return;
            }
            const step = e.deltaY < 0 ? 1.5 : -1.5;
            tag.w = Math.max(4, Math.min(100, Number(tag.w || 20) + step));
            tag.h = Math.max(3, Math.min(100, Number(tag.h || 8) + step));
            this.dirty[this.active] = true;
            this.updatePreview();
        },

        startOverlayDrag(e, idx) {
            this.selectedTagIdx = idx;
            const stage = this.$refs.overlayStage;
            if (!stage) return;
            const rect = stage.getBoundingClientRect();
            const tag = this.metaFor(this.active).overlay.tags[idx];
            this.overlayDrag = {
                idx,
                mode: 'move',
                offsetX: ((e.clientX - rect.left) / rect.width) * 100 - Number(tag.x || 0),
                offsetY: ((e.clientY - rect.top) / rect.height) * 100 - Number(tag.y || 0),
            };
        },

        startOverlayResize(e, idx) {
            const tag = this.metaFor(this.active).overlay.tags[idx];
            this.selectedTagIdx = idx;
            this.overlayDrag = {
                idx,
                mode: 'resize',
                startX: e.clientX,
                startY: e.clientY,
                startW: Number(tag.w || 20),
                startH: Number(tag.h || 8),
                startSize: this.clampTagSize(tag ? tag.size : 1),
                legacy: !!tag.legacy_center,
            };
        },

        onOverlayDragMove(e) {
            if (!this.overlayDrag) return;
            const tag = this.metaFor(this.active).overlay.tags[this.overlayDrag.idx];
            if (!tag) return;
            const stage = this.$refs.overlayStage;
            if (!stage) return;
            const rect = stage.getBoundingClientRect();
            if (!rect.width || !rect.height) return;

            if (this.overlayDrag.mode === 'resize') {
                if (this.overlayDrag.legacy) {
                    const dy = this.overlayDrag.startY - e.clientY;
                    tag.size = this.clampTagSize(this.overlayDrag.startSize + (dy / 140));
                    return;
                }
                const dw = ((e.clientX - this.overlayDrag.startX) / rect.width) * 100;
                const dh = ((e.clientY - this.overlayDrag.startY) / rect.height) * 100;
                tag.w = Math.max(4, Math.min(100 - Number(tag.x || 0), this.overlayDrag.startW + dw));
                tag.h = Math.max(3, Math.min(100 - Number(tag.y || 0), this.overlayDrag.startH + dh));
                return;
            }

            const x = ((e.clientX - rect.left) / rect.width) * 100 - (this.overlayDrag.offsetX || 0);
            const y = ((e.clientY - rect.top) / rect.height) * 100 - (this.overlayDrag.offsetY || 0);
            const maxX = tag.legacy_center ? 100 : Math.max(0, 100 - Number(tag.w || 0));
            const maxY = tag.legacy_center ? 100 : Math.max(0, 100 - Number(tag.h || 0));
            tag.x = Math.round(Math.max(0, Math.min(maxX, x)) * 10) / 10;
            tag.y = Math.round(Math.max(0, Math.min(maxY, y)) * 10) / 10;
        },

        onOverlayDragEnd() {
            if (this.overlayDrag) {
                this.dirty[this.active] = true;
                this.overlayDrag = null;
                this.updatePreview();
            }
        },

        onOverlayKey(e) {
            if (this.currentMode() !== 'overlay' || this.selectedTagIdx === null) return;
            const target = e.target;
            if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable || target.tagName === 'SELECT')) return;
            if (e.key === 'Delete' || e.key === 'Backspace') {
                e.preventDefault();
                this.removeSelectedTag();
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                this.nudgeSelected(e.shiftKey ? -3 : -1, 0);
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                this.nudgeSelected(e.shiftKey ? 3 : 1, 0);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.nudgeSelected(0, e.shiftKey ? -3 : -1);
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.nudgeSelected(0, e.shiftKey ? 3 : 1);
            } else if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                this.nudgeTagSize(0.1);
            } else if (e.key === '-' || e.key === '_') {
                e.preventDefault();
                this.nudgeTagSize(-0.1);
            }
        },

        removeSelectedTag() {
            const tags = this.metaFor(this.active).overlay.tags;
            if (this.selectedTagIdx === null || !tags[this.selectedTagIdx]) return;
            tags.splice(this.selectedTagIdx, 1);
            this.selectedTagIdx = null;
            this.dirty[this.active] = true;
            this.updatePreview();
        },

        insertTag(key) {
            const editor = this.editorEl();
            if (!editor) return;
            const token = '{' + key + '}';
            const sel = window.getSelection();
            let range = null;

            if (sel && sel.rangeCount) {
                const current = sel.getRangeAt(0);
                if (editor === current.startContainer || editor.contains(current.startContainer)) {
                    range = current;
                }
            }

            if (!range && this.restoreNativeRange() && sel.rangeCount) {
                range = sel.getRangeAt(0);
            }

            if (!range) {
                editor.focus();
                range = document.createRange();
                range.selectNodeContents(editor);
                range.collapse(false);
                sel.removeAllRanges();
                sel.addRange(range);
            }

            range.deleteContents();
            const node = document.createElement('span');
            node.setAttribute('dir', 'ltr');
            node.setAttribute('class', 'print-tag-token');
            node.textContent = token;
            range.insertNode(node);

            const after = document.createRange();
            after.setStartAfter(node);
            after.collapse(true);
            sel.removeAllRanges();
            sel.addRange(after);
            this.savedRange = after.cloneRange();
            this.onEditorInput();
        },

        onOverlayFile(e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
            this.metaFor(this.active).overlay.background_type = isPdf ? 'pdf' : 'image';
            this.overlayPreviewUrls[this.active] = URL.createObjectURL(file);
            this.overlayPendingFiles[this.active] = file;
            this.dirty[this.active] = true;
            this.updatePreview();
        },

        subtypeSelected(typeId, subId) {
            const ids = this.metaFor(this.active).subtype_ids || [];
            const val = subId === 'general' ? 'general' : Number(subId);
            return ids.some((x) => String(x) === String(val));
        },

        toggleSubtype(typeId, subId) {
            const meta = this.metaFor(this.active);
            const val = subId === 'general' ? 'general' : Number(subId);
            let ids = [...(meta.subtype_ids || [])];
            const idx = ids.findIndex((x) => String(x) === String(val));
            if (idx >= 0) ids.splice(idx, 1);
            else ids.push(val);
            meta.subtype_ids = ids;
            this.dirty[this.active] = true;
        },

        hospitalSelected(hospitalId) {
            const ids = this.metaFor(this.active).hospital_ids || [];
            return ids.some((x) => Number(x) === Number(hospitalId));
        },

        toggleHospital(hospitalId) {
            const meta = this.metaFor(this.active);
            const val = Number(hospitalId);
            let ids = [...(meta.hospital_ids || [])];
            const idx = ids.findIndex((x) => Number(x) === val);
            if (idx >= 0) ids.splice(idx, 1);
            else ids.push(val);
            meta.hospital_ids = ids;
            this.dirty[this.active] = true;
        },

        addCustomForm() {
            const label = prompt('نام فرم جدید:', 'فرم سفارشی');
            if (label === null) return;
            const id = 'custom_' + Math.random().toString(16).slice(2, 10);
            const def = this.defaults[id] || '<p class="print-body">متن فرم — {name} · {date}</p>';
            this.allTypes.push({ key: id, label: (label.trim() || 'فرم سفارشی'), desc: 'فرم سفارشی', custom: true });
            this.customMeta[id] = { label: label.trim() || 'فرم سفارشی', desc: '' };
            this.templates[id] = def;
            this.templateMeta[id] = { mode: 'html', subtype_ids: [], hospital_ids: [], sheet: { show_signature: true, show_header_spacer: true }, overlay: { background: '', background_type: 'image', tags: [] } };
            this.dirty[id] = true;
            this.switchType(id);
        },

        removeCustomForm() {
            if (!this.isCustomActive()) return;
            if (!confirm('این فرم سفارشی حذف شود؟')) return;
            const id = this.active;
            this.allTypes = this.allTypes.filter((t) => t.key !== id);
            delete this.templates[id];
            delete this.customMeta[id];
            delete this.templateMeta[id];
            this.dirty[id] = true;
            this.active = this.allTypes[0]?.key || 'hospital';
            this.loadEditor(this.active);
            this.updatePreview();
        },

        updateTypeLabel(id) {
            const t = this.allTypes.find((x) => x.key === id);
            if (t && this.customMeta[id]) t.label = this.customMeta[id].label;
        },

        updateTypeDesc(id) {
            const t = this.allTypes.find((x) => x.key === id);
            if (t && this.customMeta[id]) t.desc = this.customMeta[id].desc;
        },

        syncHiddenBuiltin(key) {
            const el = document.getElementById('tpl-' + key);
            if (!el) return;
            if (this.resets[key]) { el.value = ''; return; }
            if (this.metaFor(key).mode === 'overlay') { el.value = ''; return; }
            if (this.dirty[key] || this.templates[key]) el.value = this.templates[key] || '';
            else el.value = '';
        },

        appendMetaHiddenInputs(box, key) {
            const meta = this.metaFor(key);
            const add = (name, value) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value == null ? '' : String(value);
                box.appendChild(input);
            };
            add('template_meta[' + key + '][mode]', meta.mode || 'html');
            add('template_meta[' + key + '][sheet][show_signature]', meta.sheet && meta.sheet.show_signature === false ? '0' : '1');
            add('template_meta[' + key + '][sheet][show_header_spacer]', meta.sheet && meta.sheet.show_header_spacer === false ? '0' : '1');
            (meta.subtype_ids || []).forEach((sid) => add('template_meta[' + key + '][subtype_ids][]', sid));
            (meta.hospital_ids || []).forEach((hid) => add('template_meta[' + key + '][hospital_ids][]', hid));
            add('template_meta[' + key + '][overlay][background]', meta.overlay.background || '');
            add('template_meta[' + key + '][overlay][background_type]', meta.overlay.background_type || 'image');
            (meta.overlay.tags || []).forEach((tag, tidx) => {
                const prefix = 'template_meta[' + key + '][overlay][tags][' + tidx + ']';
                add(prefix + '[type]', tag.type || (tag.src ? 'image' : (tag.key ? 'tag' : 'text')));
                add(prefix + '[key]', tag.key || '');
                add(prefix + '[text]', tag.text || '');
                add(prefix + '[src]', tag.src || '');
                add(prefix + '[x]', tag.x);
                add(prefix + '[y]', tag.y);
                add(prefix + '[w]', tag.w || 20);
                add(prefix + '[h]', tag.h || 8);
                add(prefix + '[align]', tag.align || 'rtl');
                add(prefix + '[size]', this.clampTagSize(tag.size));
                add(prefix + '[fontSize]', tag.fontSize || 14);
                add(prefix + '[fontFamily]', tag.fontFamily || 'Vazirmatn, Tahoma, sans-serif');
                add(prefix + '[fontWeight]', tag.fontWeight || '700');
                add(prefix + '[color]', tag.color || '#111111');
                add(prefix + '[bg]', tag.bg || 'transparent');
                add(prefix + '[border]', tag.border ? '1' : '0');
                add(prefix + '[z]', tag.z || 1);
                add(prefix + '[legacy_center]', tag.legacy_center ? '1' : '0');
            });
        },

        syncAll() {
            if (this.currentMode() === 'html') {
                this.templates[this.active] = this.editorHtml();
            }
            this.dirty[this.active] = true;

            this.allTypes.filter((t) => !t.custom).forEach((t) => this.syncHiddenBuiltin(t.key));

            const customBox = document.getElementById('custom-forms-hidden');
            if (customBox) {
                customBox.innerHTML = '';
                let idx = 0;
                this.allTypes.filter((t) => t.custom).forEach((t) => {
                    const body = this.metaFor(t.key).mode === 'overlay' ? '' : (this.templates[t.key] || '');
                    const meta = this.customMeta[t.key] || { label: t.label, desc: t.desc || '' };
                    [['id', t.key], ['label', meta.label || t.label], ['desc', meta.desc || ''], ['body', body]].forEach((pair) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'custom_forms[' + idx + '][' + pair[0] + ']';
                        input.value = pair[1];
                        customBox.appendChild(input);
                    });
                    idx++;
                });
            }

            const metaBox = document.getElementById('template-meta-hidden');
            if (metaBox) {
                metaBox.innerHTML = '';
                this.allTypes.forEach((t) => this.appendMetaHiddenInputs(metaBox, t.key));
            }

            const fileBox = document.getElementById('overlay-file-inputs');
            if (fileBox) {
                fileBox.innerHTML = '';
                Object.keys(this.overlayPendingFiles).forEach((key) => {
                    const file = this.overlayPendingFiles[key];
                    if (!file) return;
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.name = 'overlay_backgrounds[' + key + ']';
                    input.hidden = true;
                    if (typeof DataTransfer !== 'undefined') {
                        try {
                            const dt = new DataTransfer();
                            dt.items.add(file);
                            input.files = dt.files;
                        } catch (err) {
                            console.warn('File attach failed for', key, err);
                        }
                    }
                    fileBox.appendChild(input);
                });
            }
        },

        loadDefault() {
            if (this.isCustomActive()) return;
            if (!confirm('قالب این برگه به پیش‌فرض سیستم برگردد؟')) return;
            this.resets[this.active] = true;
            this.dirty[this.active] = true;
            delete this.templates[this.active];
            this.metaFor(this.active).mode = 'html';
            this.metaFor(this.active).overlay = { background: '', background_type: 'image', tags: [] };
            this.loadEditor(this.active);
            this.updatePreview();
        },

        updatePreview() {
            if (this.currentMode() === 'overlay') {
                const tags = (this.metaFor(this.active).overlay.tags || []).slice().sort((a, b) => (a.z || 1) - (b.z || 1));
                const objs = tags.map((t) => {
                    if (t.legacy_center && (t.type || 'tag') === 'tag') {
                        const sample = tagDefs.find((x) => x.key === t.key);
                        const val = sample ? sample.sample : '{' + t.key + '}';
                        return '<span style="position:absolute;left:' + t.x + '%;top:' + t.y + '%;transform:translate(-50%,-50%);font-size:' + (this.clampTagSize(t.size) * 12) + 'px;background:rgba(255,255,255,.9);padding:2px 6px;border-radius:4px;">' + val + '</span>';
                    }
                    const style = this.canvasObjectStyle(t);
                    if ((t.type || 'tag') === 'image') {
                        return '<div style="position:absolute;' + style + '"><img src="' + this.canvasImageSrc(t) + '" style="width:100%;height:100%;object-fit:contain"></div>';
                    }
                    if ((t.type || 'tag') === 'text') {
                        return '<div style="position:absolute;' + style + '">' + String(t.text || '').replace(/</g, '&lt;').replace(/\n/g, '<br>') + '</div>';
                    }
                    const sample = tagDefs.find((x) => x.key === t.key);
                    const val = sample ? sample.sample : '{' + t.key + '}';
                    return '<div style="position:absolute;' + style + '">' + val + '</div>';
                }).join('');
                const bg = this.overlayBackgroundUrl(this.active);
                this.previewHtml = '<div style="position:relative;width:100%;aspect-ratio:210/297;background:#fff;border:1px solid #ddd;overflow:hidden;">'
                    + (bg ? '<img src="' + bg + '" style="position:absolute;inset:0;width:100%;height:100%;object-fit:contain;opacity:.9;">' : '')
                    + objs + '</div>';
                return;
            }
            let html = this.templates[this.active] || this.editorHtml() || this.defaults[this.active] || '';
            tagDefs.forEach((t) => {
                html = html.split('{' + t.key + '}').join('<strong>' + t.sample + '</strong>');
            });
            this.previewHtml = html;
        },
    };
}
</script>
