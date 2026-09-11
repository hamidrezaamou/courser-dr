@php
    $careFollowUps = $careFollowUps ?? collect();
    $kinds = $followUpKinds ?? [];
    $methods = $followUpMethods ?? [];
    $outcomes = $followUpOutcomes ?? [];
    $staff = $followUpStaff ?? collect();
    $today = now()->toDateString();
    $openItems = $careFollowUps->filter(fn ($i) => $i->isOpen());
    $todayItems = $openItems->filter(fn ($i) => optional($i->due_at)?->toDateString() === $today);
    $overdueItems = $openItems->filter(fn ($i) => optional($i->due_at)?->toDateString() < $today);
    $upcomingItems = $openItems->filter(fn ($i) => optional($i->due_at)?->toDateString() > $today);
    $doneItems = $careFollowUps->where('status', \App\Support\FollowUpStatus::DONE);
    $surgeryItems = $careFollowUps->whereNotNull('surgery_appointment_id');
@endphp

<div class="space-y-4">
    <p class="text-[11px] leading-5" style="color: var(--muted);">
        پیگیری‌های این پرونده از الگوهای عمل یا پیگیری گروهی ساخته می‌شوند.
    </p>

    @foreach ([
        ['پیگیری‌های امروز', $todayItems],
        ['عقب‌افتاده', $overdueItems],
        ['آینده', $upcomingItems],
        ['انجام‌شده', $doneItems],
        ['مرتبط با عمل', $surgeryItems],
    ] as [$label, $group])
        <div>
            <h4 class="mb-2 text-xs font-extrabold" style="color: var(--muted);">{{ $label }} · {{ fa_digits($group->count()) }}</h4>
            <div class="space-y-2">
                @forelse($group as $item)
                    @include('follow-ups.partials.card', compact('item', 'kinds', 'methods', 'outcomes', 'staff'))
                @empty
                    <p class="text-[11px]" style="color: var(--muted);">موردی نیست.</p>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
