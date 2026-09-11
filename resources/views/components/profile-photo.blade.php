@props([
    'url' => null,
    'initial' => '؟',
    'uploadUrl' => '',
    'deleteUrl' => '',
    'canEdit' => false,
    'title' => 'عکس پروفایل',
])

@php
    $canEdit = (bool) $canEdit;
@endphp

<div
    {{ $attributes->merge(['class' => 'pp-photo']) }}
    x-data="profilePhotoPicker({
        url: @js($url),
        uploadUrl: @js($uploadUrl),
        deleteUrl: @js($deleteUrl),
        canEdit: @js($canEdit),
        csrf: @js(csrf_token()),
    })"
    @patient-photo-updated.window="if ($event.detail && $event.detail.scope === 'sync') { url = $event.detail.url || ''; preview = url; }"
>
    @if($canEdit)
    <button
        type="button"
        class="pp-photo__face"
        @click="open = true"
        title="ویرایش عکس پروفایل"
    >
        <img class="pp-photo__img" :src="url || ''" x-show="url" alt="">
        <span class="pp-photo__initial" x-show="!url">{{ $initial }}</span>
        <span class="pp-photo__badge" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><circle cx="12" cy="13.5" r="3.25"/></svg>
        </span>
    </button>
    @else
    <div class="pp-photo__face">
        <img class="js-header-photo pp-photo__img" src="{{ $url }}" alt="" @if(! $url) hidden @endif>
        <span class="js-header-photo-fallback pp-photo__initial" @if($url) hidden @endif>{{ $initial }}</span>
    </div>
    @endif

    @if($canEdit)
    <template x-teleport="body">
        <div class="pp-photo-overlay" x-show="open" x-cloak @click.self="close()" @keydown.escape.window="if (open) close()">
            <div class="pp-photo-sheet" role="dialog" aria-modal="true" aria-label="{{ $title }}">
                <div class="pp-photo-sheet__head">
                    <div>
                        <h3>{{ $title }}</h3>
                        <p>افزودن، جایگزینی یا حذف عکس. حجم هنگام انتخاب کم می‌شود.</p>
                    </div>
                    <button type="button" class="pp-photo-sheet__close" @click="close()" aria-label="بستن">×</button>
                </div>

                <div class="pp-photo-sheet__preview">
                    <img :src="preview || url || ''" x-show="preview || url" alt="">
                    <span x-show="!(preview || url)">{{ $initial }}</span>
                </div>

                <p class="pp-photo-sheet__hint" x-show="hint" x-text="hint"></p>
                <p class="pp-photo-sheet__err" x-show="error" x-text="error"></p>

                <input type="file" accept="image/*" capture="environment" class="sr-only" x-ref="camera" @change="pick($event, 'camera')">
                <input type="file" accept="image/*" class="sr-only" x-ref="gallery" @change="pick($event, 'gallery')">

                <div class="pp-photo-sheet__actions">
                    <button type="button" class="btn-primary" :disabled="busy" @click="$refs.camera.click()">دوربین</button>
                    <button type="button" class="btn-secondary" :disabled="busy" @click="$refs.gallery.click()">گالری</button>
                    <button type="button" class="btn-secondary" :disabled="busy || !file" x-show="file" x-cloak @click="save()">ذخیره عکس</button>
                    <button type="button" class="btn-ghost text-red-600" :disabled="busy || !(url || preview)" x-show="url && !file" @click="remove()">حذف</button>
                </div>
            </div>
        </div>
    </template>
    @endif
</div>

@once
<script>
function profilePhotoPicker(cfg) {
    function csrf() {
        return cfg.csrf || (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    }
    function formatBytes(n) {
        n = Number(n) || 0;
        if (n < 1024) return n + ' بایت';
        if (n < 1024 * 1024) return (n / 1024).toFixed(0) + ' کیلوبایت';
        return (n / (1024 * 1024)).toFixed(1) + ' مگابایت';
    }
    async function compressImage(file) {
        const maxEdge = 900;
        const quality = 0.72;
        try {
            const bitmap = await createImageBitmap(file);
            const scale = Math.min(1, maxEdge / Math.max(bitmap.width, bitmap.height));
            const w = Math.max(1, Math.round(bitmap.width * scale));
            const h = Math.max(1, Math.round(bitmap.height * scale));
            const canvas = document.createElement('canvas');
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, w, h);
            ctx.drawImage(bitmap, 0, 0, w, h);
            if (bitmap.close) bitmap.close();
            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
            if (!blob) return file;
            if (blob.size >= file.size && file.size < 180000 && scale >= 1) return file;
            return new File([blob], 'photo.jpg', { type: 'image/jpeg', lastModified: Date.now() });
        } catch (e) {
            return file;
        }
    }
    return {
        open: false,
        busy: false,
        url: cfg.url || '',
        preview: cfg.url || '',
        file: null,
        hint: '',
        error: '',
        canEdit: !!cfg.canEdit,
        histId: 'profile-photo-' + Math.random().toString(36).slice(2, 10),
        init() {
            if (window.OverlayHistory) {
                window.OverlayHistory.bindWatch(this, 'open', this.histId);
            }
        },
        openModal() { if (this.canEdit) this.open = true; },
        close() {
            this.open = false;
            this.file = null;
            this.preview = this.url;
            this.hint = '';
            this.error = '';
        },
        async pick(event, source) {
            const picked = (event.target.files && event.target.files[0]) || null;
            event.target.value = '';
            if (!picked) return;
            this.error = '';
            this.busy = true;
            this.hint = 'در حال کم‌کردن حجم…';
            const original = picked.size;
            const compressed = await compressImage(picked);
            this.file = compressed;
            if (this.preview && this.preview.indexOf('blob:') === 0) URL.revokeObjectURL(this.preview);
            this.preview = URL.createObjectURL(compressed);
            this.hint = (source === 'camera' ? 'عکس دوربین' : 'از گالری')
                + ' · ' + formatBytes(original) + ' ← ' + formatBytes(compressed.size);
            this.busy = false;
        },
        async save() {
            if (!this.file || !cfg.uploadUrl) return;
            this.busy = true;
            this.error = '';
            try {
                const body = new FormData();
                body.append('photo', this.file);
                const res = await fetch(cfg.uploadUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                    },
                    body,
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    this.error = (data && (data.message || (data.errors && data.errors.photo && data.errors.photo[0]))) || 'ذخیره نشد.';
                    return;
                }
                this.url = data.url || '';
                this.preview = this.url;
                this.file = null;
                this.hint = data.message || 'ذخیره شد.';
                this.syncFaces(this.url);
                window.dispatchEvent(new CustomEvent('patient-photo-updated', { detail: { url: this.url, scope: 'sync' } }));
            } catch (e) {
                this.error = 'ارتباط با سرور برقرار نشد.';
            } finally {
                this.busy = false;
            }
        },
        async remove() {
            if (!cfg.deleteUrl || !confirm('عکس پروفایل حذف شود؟')) return;
            this.busy = true;
            this.error = '';
            try {
                const res = await fetch(cfg.deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                    },
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    this.error = (data && data.message) || 'حذف نشد.';
                    return;
                }
                this.url = '';
                this.preview = '';
                this.file = null;
                this.hint = data.message || 'حذف شد.';
                this.syncFaces('');
                window.dispatchEvent(new CustomEvent('patient-photo-updated', { detail: { url: '', scope: 'sync' } }));
            } catch (e) {
                this.error = 'ارتباط با سرور برقرار نشد.';
            } finally {
                this.busy = false;
            }
        },
        syncFaces(url) {
            document.querySelectorAll('.js-header-photo').forEach((img) => {
                if (url) {
                    img.src = url;
                    img.hidden = false;
                } else {
                    img.removeAttribute('src');
                    img.hidden = true;
                }
            });
            document.querySelectorAll('.js-header-photo-fallback').forEach((el) => {
                el.hidden = !!url;
            });
        },
    };
}
</script>
@endonce
