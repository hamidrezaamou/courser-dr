@php
    [$jy, $jm] = \App\Support\Jalali::toJalali(
        (int) now()->format('Y'),
        (int) now()->format('m'),
        (int) now()->format('d')
    );
@endphp
@foreach ($grouped as $month => $days)
    <details class="times-month" @if ((int) $month === (int) $jm) open @endif>
        <summary class="times-month__summary">
            <span class="times-month__title">
                <svg class="times-month__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
                </svg>
                {{ $monthNames[(int) $month] ?? $month }}
            </span>
            <span class="times-month__count">{{ $days->count() }} روز</span>
        </summary>
        <div class="times-month__body">
            <div class="flex flex-wrap gap-3">
                @foreach ($days as $day)
                    @php
                        $total = $day->totalSlots();
                        $booked = $bookedCounts[$day->id] ?? 0;
                        $free = max(0, $total - $booked);
                        $full = $booked >= $total && $total > 0;
                    @endphp
                    <div class="min-w-[7.5rem] flex-1 cursor-pointer rounded-2xl border p-3 text-center sm:min-w-[96px] sm:flex-none {{ $full ? 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/30' : 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-900 dark:bg-emerald-950/20' }}"
                         data-schedule-delete-id="{{ $day->id }}"
                         data-sms-text="{{ e($day->smsText()) }}"
                         :class="isDeleteSelected({{ $day->id }}) ? 'ring-2 ring-rose-400' : ''"
                         @click="if (!$event.target.closest('input, button, textarea, select, label, a, form, details, summary')) toggleDeleteId({{ $day->id }})">
                        <label class="mb-1 flex items-center justify-center gap-1 text-[10px] font-bold text-slate-600">
                            <input type="checkbox" :checked="isDeleteSelected({{ $day->id }})" @change="toggleDeleteId({{ $day->id }})">
                            انتخاب
                        </label>
                        <div class="text-[11px]" style="color: var(--muted);">{{ $day->weekday }}</div>
                        <div class="text-lg font-extrabold" style="color: var(--ink);">{{ $day->day }}</div>
                        @if($day->surgerySubtype || $day->surgeryType)
                            <div class="mt-1 text-[10px] leading-tight" style="color:var(--muted)">
                                {{ $day->surgeryType?->name }}
                                @if($day->surgerySubtype) · {{ $day->surgerySubtype->name }} @endif
                            </div>
                        @endif
                        <div class="mt-1 text-[10px] font-semibold {{ $full ? 'text-red-700' : 'text-emerald-700' }}">
                            {{ $full ? 'تکمیل' : $free.' خالی از '.$total }}
                        </div>
                        <div class="mt-1 text-[10px] font-bold" style="color: var(--brand);">
                            {{ count($day->times()) }} تایم
                        </div>
                        <div onclick="event.stopPropagation()">
                            @include('times.partials.sms-edit', ['schedule' => $day])
                        </div>
                        <form method="POST" action="{{ route('times.destroy', $day) }}" class="mt-2" onsubmit="return confirm('حذف این روز؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-[11px] font-bold text-red-600">حذف</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </details>
@endforeach
