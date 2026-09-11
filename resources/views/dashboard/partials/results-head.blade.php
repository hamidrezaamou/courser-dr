@php
    $stats = $stats ?? ['total' => $patients->total(), 'new_today' => 0, 'upcoming_surgery' => 0];
@endphp

<div class="patient-stats">
    <div class="patient-stat">
        <span class="patient-stat__value">{{ number_format($stats['total']) }}</span>
        <span class="patient-stat__label">پرونده</span>
    </div>
    <div class="patient-stat patient-stat--accent">
        <span class="patient-stat__value">{{ number_format($stats['new_today']) }}</span>
        <span class="patient-stat__label">پرونده جدید امروز</span>
    </div>
    <div class="patient-stat">
        <span class="patient-stat__value">{{ number_format($stats['upcoming_surgery']) }}</span>
        <span class="patient-stat__label">عمل پیش‌رو</span>
    </div>
</div>

@if ($search !== '')
    <p class="patient-results__query mt-2 text-xs font-bold" style="color: var(--brand-dark);">
        نتیجه جستجو · «{{ $search }}»
        <span id="patient-visible-count" class="sr-only">{{ $patients->total() }}</span>
    </p>
@else
    <span id="patient-visible-count" class="sr-only">{{ $patients->total() }}</span>
@endif
