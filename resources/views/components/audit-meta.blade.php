@props(['creator' => null, 'editor' => null, 'createdAt' => null, 'updatedAt' => null, 'wasEdited' => false])

@php
    $showEdited = $wasEdited || ($editor && $updatedAt && $createdAt && $updatedAt != $createdAt);
@endphp

@if ($creator || $showEdited)
    <div class="tg-audit-meta text-[10px]" style="color: var(--muted);">
        @if ($creator)
            <span>ثبت: {{ $creator->name ?? '—' }}</span>
        @endif
        @if ($showEdited)
            <span @if($creator) class="mx-1" @endif>
                ویرایش: <span dir="ltr">{{ jalali($updatedAt, 'Y/m/d H:i') }}</span>
                @if ($editor) · {{ $editor->name }} @endif
            </span>
        @endif
    </div>
@endif
