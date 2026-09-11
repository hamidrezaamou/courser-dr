@php
    $todayUrl = route('clinic.floor', ['date' => $todayJalali, 'kind' => $kindFilter ?? 'all']);
    $liveUrl = route('clinic.floor.live', ['date' => $dateJalali, 'kind' => $kindFilter ?? 'all']);
    $kindFilter = $kindFilter ?? 'all';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="floor-hero">
            <div>
                <h2 class="floor-hero__title">صف مطب</h2>
                <p class="floor-hero__sub">حضور → صف → ارجاع به پزشک → ویزیت → پایان · به‌روزرسانی خودکار</p>
            </div>
            <form method="GET" action="{{ route('clinic.floor') }}" class="floor-hero__date" onchange="this.submit()">
                <x-jalali-date-input name="date" :value="$dateJalali" label="" :allow-past="true" :allow-friday="true" class="!min-h-[2.2rem] !py-1.5 !text-sm" />
                <input type="hidden" name="kind" value="{{ $kindFilter }}">
                <a href="{{ route('clinic.floor', ['date' => $todayJalali, 'kind' => $kindFilter]) }}" class="board-chip {{ $dateJalali === $todayJalali ? 'is-active' : '' }}">امروز</a>
                <button type="submit" class="board-chip">اعمال</button>
            </form>
        </div>
    </x-slot>

    <div
        class="floor-page"
        x-data="clinicFloorLive(@js(['url' => $liveUrl, 'version' => $version, 'interval' => 2000]))"
        @floor-changed.window="refresh(true)"
    >
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <div class="floor-kind-tabs" role="tablist" aria-label="فیلتر نوع نوبت">
                <a href="{{ route('clinic.floor', ['date' => $dateJalali, 'kind' => 'all']) }}"
                   class="floor-kind-tab {{ $kindFilter === 'all' ? 'is-active' : '' }}">همه</a>
                <a href="{{ route('clinic.floor', ['date' => $dateJalali, 'kind' => 'visit']) }}"
                   class="floor-kind-tab {{ $kindFilter === 'visit' ? 'is-active' : '' }}">ویزیت مطب</a>
                <a href="{{ route('clinic.floor', ['date' => $dateJalali, 'kind' => 'surgery']) }}"
                   class="floor-kind-tab {{ $kindFilter === 'surgery' ? 'is-active' : '' }}">عمل بیمارستان</a>
            </div>

            <div class="floor-live-bar" aria-live="polite">
                <span class="floor-live-dot" :class="{ 'is-on': connected, 'is-pulse': refreshing }"></span>
                <span x-text="statusText"></span>
            </div>

            <div x-ref="board" class="floor-live-board space-y-5">
                @include('clinic.partials.floor-board')
            </div>
        </div>
    </div>
</x-app-layout>
