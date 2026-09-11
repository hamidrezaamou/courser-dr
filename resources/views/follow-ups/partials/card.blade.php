@php
    $item = $item ?? null;
    $kinds = $kinds ?? [];
    $methods = $methods ?? [];
    $outcomes = $outcomes ?? [];
    $staff = $staff ?? collect();
    $display = $item->displayStatus();
    $patient = $item->patient;
    $hospitalName = $item->hospital?->name;
    $toolboxPayload = $patient ? \App\Support\ToolboxPayload::fromFollowUp($item) : null;
    $tone = match ($display) {
        'overdue' => '#b91c1c',
        'due' => '#b45309',
        'in_progress' => '#1d4ed8',
        'done' => '#047857',
        'cancelled', 'rejected', 'failed' => 'var(--muted)',
        default => 'var(--ink)',
    };
@endphp

<article class="panel space-y-3 p-4" x-data="{ openResult: false, openRetry: false }">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-sm font-bold" style="color: var(--ink);">{{ $item->title }}</h3>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-extrabold" style="background: var(--panel-soft); color: {{ $tone }};">{{ $item->statusLabel() }}</span>
                <span class="text-[10px] font-bold" style="color: var(--muted);">{{ $item->sourceLabel() }}</span>
            </div>
            <div class="mt-1 text-[11px]" style="color: var(--muted);">
                @if($item->patient)
                    <a href="{{ route('patients.show', ['patient' => $item->patient, 'open' => 'followup']) }}" class="font-bold" style="color: var(--brand-dark);">{{ $item->patient->name }}</a>
                    · <span dir="ltr">{{ $item->patient->mobile }}</span>
                @endif
                @if($item->surgeryType)
                    · {{ $item->surgeryType->name }}{{ $item->surgerySubtype ? ' · '.$item->surgerySubtype->name : '' }}
                @endif
                @if($hospitalName)
                    · بیمارستان {{ $hospitalName }}
                @endif
            </div>
            <div class="mt-1 text-[11px] font-bold" dir="ltr" style="color: var(--ink);">{{ $item->dueJalali() }}</div>
            <div class="mt-1 text-[11px]" style="color: var(--muted);">
                {{ $item->kindLabel() }} · {{ $item->methodLabel() }}
                @if($item->assignee) · مسئول: {{ $item->assignee->name }} @else · بدون مسئول @endif
                @if($item->parent) · ادامه #{{ $item->parent_id }} @endif
            </div>
            @if($item->description)
                <p class="mt-2 text-xs leading-6" style="color: var(--ink);">{{ $item->description }}</p>
            @endif
            @if($item->outcome)
                <p class="mt-1 text-[11px] font-bold" style="color: var(--brand-dark);">نتیجه: {{ $item->outcomeLabel() }}</p>
            @endif
            @if($item->outcome_notes)
                <p class="text-[11px]" style="color: var(--muted);">{{ $item->outcome_notes }}</p>
            @endif
        </div>
        @if($patient)
            <div class="flex flex-wrap items-center gap-1">
                <x-phone-call :mobile="$patient->mobile" />
                <button
                    type="button"
                    class="row-toolbox-trigger touch-action !min-h-8 !px-2.5 !py-1 !text-[11px]"
                    style="background:var(--panel-soft);color:var(--ink)"
                    data-toolbox-trigger
                    data-toolbox-b64="{{ \App\Support\ToolboxPayload::encode($toolboxPayload) }}"
                    @click.stop
                >ابزار</button>
            </div>
        @endif
    </div>

    @if($item->isOpen())
        <div class="flex flex-wrap gap-1">
            @if($item->status === \App\Support\FollowUpStatus::PENDING)
                <form method="POST" action="{{ route('followups.start', $item) }}">
                    @csrf
                    <button type="submit" class="btn-secondary !px-2 !py-1 !text-[10px]">شروع</button>
                </form>
            @endif
            <button type="button" class="btn-primary !px-2 !py-1 !text-[10px]" @click="openResult = !openResult; openRetry = false">ثبت نتیجه</button>
            <button type="button" class="btn-secondary !px-2 !py-1 !text-[10px]" @click="openRetry = !openRetry; openResult = false">پیگیری مجدد</button>
            <form method="POST" action="{{ route('followups.cancel', $item) }}" onsubmit="return confirm('این پیگیری لغو شود؟')">
                @csrf
                <button type="submit" class="btn-secondary !px-2 !py-1 !text-[10px]" style="color:#b91c1c">لغو</button>
            </form>
        </div>

        <form method="POST" action="{{ route('followups.complete', $item) }}" class="space-y-2 rounded-xl border p-3" style="border-color: var(--line); background: var(--panel-soft);" x-show="openResult" x-cloak>
            @csrf
            <select name="outcome" class="field-input w-full" required>
                <option value="">نتیجه</option>
                @foreach($outcomes as $slug => $label)
                    <option value="{{ $slug }}">{{ $label }}</option>
                @endforeach
            </select>
            <textarea name="outcome_notes" rows="2" class="field-input w-full" placeholder="توضیحات نتیجه"></textarea>
            <div class="flex flex-wrap gap-1">
                <button type="submit" class="btn-primary !py-1.5 !text-xs">ذخیره نتیجه</button>
                <button type="submit" form="fu-fail-{{ $item->id }}" class="btn-secondary !py-1.5 !text-xs">ناموفق</button>
            </div>
        </form>
        <form id="fu-fail-{{ $item->id }}" method="POST" action="{{ route('followups.fail', $item) }}">
            @csrf
            <input type="hidden" name="status" value="failed">
        </form>

        <form method="POST" action="{{ route('followups.retry', $item) }}" class="space-y-2 rounded-xl border p-3" style="border-color: var(--line); background: var(--panel-soft);" x-show="openRetry" x-cloak>
            @csrf
            <div class="grid gap-2 sm:grid-cols-2">
                <select name="days_after" class="field-input">
                    <option value="">بعد از چند روز؟</option>
                    <option value="1">۱ روز بعد</option>
                    <option value="2" selected>۲ روز بعد</option>
                    <option value="7">۱ هفته بعد</option>
                    <option value="30">۱ ماه بعد</option>
                </select>
                <x-jalali-date-input name="due_date" :required="false" label="" placeholder="یا تاریخ مشخص" />
            </div>
            <select name="assigned_to" class="field-input w-full">
                <option value="">همان مسئول</option>
                @foreach($staff as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
            <textarea name="notes" rows="2" class="field-input w-full" placeholder="یادداشت پیگیری مجدد"></textarea>
            <button type="submit" class="btn-primary !py-1.5 !text-xs">ساخت پیگیری بعدی</button>
        </form>
    @endif
</article>
