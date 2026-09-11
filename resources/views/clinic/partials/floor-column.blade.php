@php
    /** @var \Illuminate\Support\Collection $rows */
@endphp
<section class="floor-col floor-col--{{ $tone }}">
    <header class="floor-col__head">
        <div>
            <h3 class="floor-col__title">{{ $title }}</h3>
            <p class="floor-col__hint">{{ $hint }}</p>
        </div>
        <span class="floor-col__count">{{ $rows->count() }}</span>
    </header>

    <div class="floor-col__list">
        @forelse($rows as $row)
            @include('clinic.partials.floor-card', ['row' => $row])
        @empty
            <p class="floor-col__empty">خالی</p>
        @endforelse
    </div>
</section>
