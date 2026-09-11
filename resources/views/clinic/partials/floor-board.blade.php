@if($isDoctor && $current)
    @php
        $cur = $current['item'];
        $curType = $current['type'];
        $curTime = $cur->scheduled_time ? \App\Support\SlotLabel::display((string) $cur->scheduled_time) : '—';
    @endphp
    <section class="floor-doctor">
        <div class="floor-doctor__label">
            {{ $cur->status === \App\Support\BookingStatus::IN_CONSULT ? 'بیمار فعلی نزد شما' : 'نفر بعدی برای ویزیت' }}
        </div>
        <div class="floor-doctor__main">
            <div>
                <h3 class="floor-doctor__name">{{ $cur->patient_name }}</h3>
                <p class="floor-doctor__meta">
                    <span class="floor-card__type {{ $curType === 'surgery' ? 'floor-card__type--surgery' : 'floor-card__type--visit' }}">
                        {{ $curType === 'surgery' ? 'عمل' : 'ویزیت' }}
                    </span>
                    · {{ $curType === 'surgery' ? ($cur->surgery_type ?: 'عمل') : ($cur->visit_type ?: 'ویزیت') }}
                    · {{ $curTime }}
                </p>
            </div>
            <x-booking-status-actions
                :model="$cur"
                :type="$curType"
                variant="floor"
                :show-status="false"
                :patient-url="route('patients.show', $cur->patient_id)"
            />
        </div>
    </section>
@elseif($isDoctor)
    <section class="floor-doctor floor-doctor--empty">
        <p>فعلاً بیماری برای ویزیت در صف ارجاع نیست.</p>
    </section>
@endif

<div class="floor-cols">
    @include('clinic.partials.floor-column', [
        'title' => 'نوبت‌های روز',
        'hint' => 'هنوز نیامده‌اند',
        'tone' => 'upcoming',
        'rows' => $upcoming,
    ])
    @include('clinic.partials.floor-column', [
        'title' => 'حضور / در صف',
        'hint' => 'منشی: ارجاع به پزشک',
        'tone' => 'waiting',
        'rows' => $waiting,
    ])
    @include('clinic.partials.floor-column', [
        'title' => 'ارجاع به پزشک',
        'hint' => 'آماده ورود به اتاق',
        'tone' => 'ready',
        'rows' => $ready,
    ])
    @include('clinic.partials.floor-column', [
        'title' => 'نزد پزشک',
        'hint' => 'در حال ویزیت',
        'tone' => 'consult',
        'rows' => $inConsult,
    ])
</div>
