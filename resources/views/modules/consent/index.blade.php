<x-app-layout>
    <x-slot name="header">
        <h2 class="page-title">رضایت‌نامه دیجیتال</h2>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-modules-dock :module-section="$moduleSection" />
            <x-flash />

            <div class="grid gap-4 lg:grid-cols-2">
                <form method="POST" action="{{ route('modules.consent.templates.store') }}" class="panel space-y-3 p-4">
                    @csrf
                    <h3 class="text-sm font-bold" style="color: var(--ink);">قالب جدید</h3>
                    <input type="text" name="title" class="field-input w-full" placeholder="عنوان" required maxlength="160">
                    <select name="kind" class="field-input w-full" required>
                        <option value="visit">ویزیت</option>
                        <option value="surgery">عمل</option>
                    </select>
                    <textarea name="body" rows="8" class="field-input w-full" placeholder="متن رضایت‌نامه" required maxlength="8000"></textarea>
                    <button type="submit" class="btn-primary w-full !py-2">ذخیره قالب</button>
                </form>

                <form method="POST" action="{{ route('modules.consent.records.store') }}" class="panel space-y-3 p-4">
                    @csrf
                    <h3 class="text-sm font-bold" style="color: var(--ink);">ثبت امضا در پرونده</h3>
                    <select name="patient_id" class="field-input w-full" required>
                        <option value="">بیمار…</option>
                        @foreach($patients as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <select name="consent_template_id" class="field-input w-full" required>
                        <option value="">قالب…</option>
                        @foreach($templates as $t)
                            <option value="{{ $t->id }}">{{ $t->title }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="signed_by_name" class="field-input w-full" placeholder="نام امضاکننده" required maxlength="160">
                    <button type="submit" class="btn-primary w-full !py-2">ثبت رضایت‌نامه</button>
                </form>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="panel p-4">
                    <h3 class="mb-2 text-sm font-bold" style="color: var(--ink);">قالب‌ها</h3>
                    <div class="space-y-2 max-h-80 overflow-auto">
                        @forelse($templates as $t)
                            <div class="rounded-lg border p-3 text-xs" style="border-color: var(--line);">
                                <div class="font-bold">{{ $t->title }}</div>
                                <div style="color: var(--muted);">{{ $t->kind === 'surgery' ? 'عمل' : 'ویزیت' }}</div>
                                <p class="mt-2 whitespace-pre-line leading-6">{{ \Illuminate\Support\Str::limit($t->body, 200) }}</p>
                            </div>
                        @empty
                            <p class="text-xs" style="color: var(--muted);">قالبی ثبت نشده.</p>
                        @endforelse
                    </div>
                </div>
                <div class="panel p-4">
                    <h3 class="mb-2 text-sm font-bold" style="color: var(--ink);">آخرین ثبت‌ها</h3>
                    <div class="space-y-2 max-h-80 overflow-auto">
                        @forelse($recent as $c)
                            <div class="rounded-lg border px-3 py-2 text-xs" style="border-color: var(--line);">
                                <div class="font-bold">{{ $c->patient?->name }}</div>
                                <div style="color: var(--muted);">{{ $c->template?->title }} · {{ $c->signed_by_name }}</div>
                                <div dir="ltr">{{ jalali($c->signed_at, 'Y/m/d H:i') }}</div>
                            </div>
                        @empty
                            <p class="text-xs" style="color: var(--muted);">ثبت نشده.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
