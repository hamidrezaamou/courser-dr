@if($isStaff && \App\Support\FeatureFlags::enabled('features.consent_forms'))
@php
    $consents = $patient->consents ?? collect();
    $consentTemplates = \App\Models\ConsentTemplate::query()->where('is_active', true)->orderBy('title')->get();
    $focusSurgeryId = (int) request('surgery', 0) ?: null;
@endphp
<div class="tg-side__card space-y-2" id="patient-consent-panel">
    <div class="flex items-center justify-between gap-2">
        <h3 class="text-sm font-bold" style="color: var(--ink);">رضایت‌نامه‌ها</h3>
        <span class="text-[11px] font-bold" style="color: var(--muted);">{{ $consents->count() }}</span>
    </div>

    @if($canClinical && $consentTemplates->isNotEmpty())
    <form method="POST" action="{{ route('modules.consent.records.store') }}" class="space-y-2 rounded-lg border p-2.5 text-xs" style="border-color: var(--line);">
        @csrf
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
        @if($focusSurgeryId)
            <input type="hidden" name="subject_type" value="surgery">
            <input type="hidden" name="subject_id" value="{{ $focusSurgeryId }}">
        @endif
        <select name="consent_template_id" class="field-input w-full !text-xs" required>
            <option value="">قالب رضایت…</option>
            @foreach($consentTemplates as $tpl)
                <option value="{{ $tpl->id }}">{{ $tpl->title }}</option>
            @endforeach
        </select>
        <input type="text" name="signed_by_name" class="field-input w-full !text-xs" placeholder="نام امضاکننده" required>
        <button type="submit" class="btn-primary w-full !py-1.5 !text-xs">ثبت رضایت</button>
    </form>
    @endif

    <div class="space-y-2 max-h-48 overflow-auto">
        @forelse($consents as $c)
            <div class="rounded-lg border px-2.5 py-2 text-[11px]" style="border-color: var(--line);">
                <div class="font-bold">{{ $c->template?->title }}</div>
                <div style="color: var(--muted);">{{ $c->signed_by_name }} · {{ jalali($c->signed_at, 'Y/m/d') }}</div>
                <a href="{{ route('modules.consent.print', $c) }}" target="_blank" class="mt-1 inline-block font-bold" style="color: var(--brand-dark);">چاپ</a>
            </div>
        @empty
            <p class="text-xs" style="color: var(--muted);">رضایت‌نامه ثبت نشده.</p>
        @endforelse
    </div>
</div>
@endif
