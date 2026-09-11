@props([
    'model',
    'type' => 'visit', // visit|surgery
    'showStatus' => true,
    'variant' => 'default', // default|icons|board|floor
    'patientUrl' => null,
])

@php
    $status = $model->status;
    $label = \App\Support\BookingStatus::label($status);
    $scheduled = \App\Support\BookingStatus::SCHEDULED;
    $confirmed = \App\Support\BookingStatus::CONFIRMED;
    $waiting = \App\Support\BookingStatus::WAITING;
    $ready = \App\Support\BookingStatus::READY;
    $inConsult = \App\Support\BookingStatus::IN_CONSULT;
    $done = \App\Support\BookingStatus::DONE;
    $cancelled = \App\Support\BookingStatus::CANCELLED;
    $noShow = \App\Support\BookingStatus::NO_SHOW;

    $boardLabels = [
        $scheduled => 'در انتظار',
        $confirmed => 'تایید شده',
        $waiting => 'حضور / در صف',
        $ready => 'ارجاع به پزشک',
        $inConsult => 'نزد پزشک',
        $done => 'انجام شده',
        $cancelled => 'لغو شده',
        $noShow => 'عدم حضور',
    ];
    $displayLabel = in_array($variant, ['icons', 'board', 'floor'], true) ? ($boardLabels[$status] ?? $label) : $label;

    $tones = [
        $scheduled => 'tone tone--scheduled',
        $confirmed => 'tone tone--confirmed',
        $waiting => 'tone tone--waiting',
        $ready => 'tone tone--ready',
        $inConsult => 'tone tone--in-consult',
        $done => 'tone tone--done',
        $cancelled => 'tone tone--cancelled',
        $noShow => 'tone tone--no-show',
    ];
    $tone = $tones[$status] ?? 'tone tone--neutral';
    $routeName = $type === 'surgery' ? 'surgery-appointments.status' : 'appointments.status';
    $editRoute = $type === 'surgery'
        ? route('surgery-appointments.edit', $model)
        : route('appointments.edit', $model);

    $actionLabels = [
        $confirmed => 'تأیید',
        $waiting => 'حضور بیمار',
        $ready => 'ارجاع به پزشک',
        $inConsult => 'شروع ویزیت',
        $done => 'پایان / انجام شد',
        $cancelled => 'لغو',
        $scheduled => 'بازگشت به ثبت‌شده',
        $noShow => 'عدم حضور',
    ];
    $actionHints = [
        $confirmed => 'نوبت تأیید شود و برای بیمار قطعی گردد؟',
        $waiting => 'بیمار در مطب حضور دارد و وارد صف شود؟',
        $ready => 'بیمار به پزشک ارجاع شود؟',
        $inConsult => 'ویزیت نزد پزشک شروع شود؟',
        $done => 'وضعیت به «انجام شد» تغییر کند؟',
        $cancelled => 'نوبت لغو شود؟ این کار قابل بازگشت است ولی ظرفیت آزاد می‌شود.',
        $scheduled => 'نوبت به حالت ثبت‌شده برگردد؟',
        $noShow => 'بیمار حاضر نشد و وضعیت «عدم حضور» ثبت شود؟ ظرفیت آزاد می‌شود.',
    ];
    // When moving from in_consult back to ready, label differs
    if ($status === $inConsult) {
        $actionLabels[$ready] = 'بازگشت به ارجاع';
        $actionHints[$ready] = 'بیمار به صف ارجاع برگردد؟';
    }
    if ($status === $ready) {
        $actionLabels[$waiting] = 'بازگشت به صف';
        $actionHints[$waiting] = 'بیمار به صف انتظار برگردد؟';
    }
    if ($status === $waiting) {
        $actionLabels[$confirmed] = 'خروج از صف';
        $actionHints[$confirmed] = 'بیمار از صف خارج شود؟';
    }

    $isStaff = auth()->check() && in_array(auth()->user()->role, ['doctor', 'admin', 'assistant'], true);
    $actionTone = [
        $confirmed => 'evt-act--confirm',
        $waiting => 'evt-act--waiting',
        $ready => 'evt-act--ready',
        $inConsult => 'evt-act--consult',
        $done => 'evt-act--done',
        $cancelled => 'evt-act--cancel',
        $scheduled => 'evt-act--revert',
        $noShow => 'evt-act--cancel',
    ];
    $icons = [
        $confirmed => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />',
        $waiting => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />',
        $ready => '<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />',
        $inConsult => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.678 50.678 0 00-2.658-.813A59.958 59.958 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.908.276-1.813.567-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />',
        $done => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        $cancelled => '<path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />',
        $scheduled => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 15l-3-3m0 0l3-3m-3 3h12" />',
        $noShow => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />',
    ];
    $trashIcon = '<path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />';
    $profileIcon = '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />';
    $editIcon = '<path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L8.832 17.82a4.5 4.5 0 01-1.897 1.13l-3.096.91 1.007-3.015a4.5 4.5 0 011.13-1.897L16.863 4.487z"/>';
    $allowed = $isStaff ? $model->allowedStatusTransitions() : [];
    $can = fn (string $s) => in_array($s, $allowed, true);

    // Floor primary CTA order
    $floorPrimary = match ($status) {
        $scheduled, $confirmed => $waiting,
        $waiting => $ready,
        $ready => $inConsult,
        $inConsult => $done,
        default => null,
    };
@endphp

@if($showStatus)
    <span class="{{ $tone }} board-status inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold">
        {{ $displayLabel }}
    </span>
@endif

@if($isStaff)
    <div class="{{ $showStatus && $variant === 'default' ? 'mt-2 ' : '' }}{{ $variant === 'board' ? 'board-act-grid' : ($variant === 'icons' ? 'board-card__acts' : ($variant === 'floor' ? 'floor-card__acts' : 'flex flex-wrap gap-2')) }}" x-data="{
        open: false,
        title: '',
        message: '',
        action: '',
        busy: false,
        ajax: {{ $variant === 'floor' ? 'true' : 'false' }},
        ask(title, message, action) {
            this.title = title;
            this.message = message;
            this.action = action;
            this.open = true;
        },
        async confirm() {
            if (!this.action || this.busy) return;
            const form = this.$refs['form_' + this.action];
            this.open = false;
            if (!form) return;
            if (!this.ajax) {
                form.submit();
                return;
            }
            this.busy = true;
            try {
                const body = new FormData(form);
                const { data } = await window.axios.post(form.action, body, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                window.dispatchEvent(new CustomEvent('floor-changed'));
                if (data && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
            } catch (err) {
                form.submit();
            } finally {
                this.busy = false;
            }
        }
    }">
        @foreach($allowed as $next)
            <form method="POST" action="{{ route($routeName, $model) }}" class="hidden" x-ref="form_{{ $next }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $next }}">
            </form>
        @endforeach

        @if ($variant === 'floor')
            @php
                $floorTone = [
                    $waiting => 'floor-cta--arrive',
                    $ready => 'floor-cta--refer',
                    $inConsult => 'floor-cta--start',
                    $done => 'floor-cta--finish',
                    $confirmed => 'floor-cta--confirm',
                    $scheduled => 'floor-cta--back',
                ];
            @endphp
            @if($floorPrimary && $can($floorPrimary))
                <button type="button"
                        class="floor-cta {{ $floorTone[$floorPrimary] ?? 'floor-cta--arrive' }}"
                        @click="ask(@js($actionLabels[$floorPrimary] ?? \App\Support\BookingStatus::label($floorPrimary)), @js($actionHints[$floorPrimary] ?? 'این تغییر اعمال شود؟'), @js($floorPrimary))">
                    {{ $actionLabels[$floorPrimary] ?? \App\Support\BookingStatus::label($floorPrimary) }}
                </button>
            @endif

            @if($patientUrl)
                <a href="{{ $patientUrl }}" class="floor-cta floor-cta--ghost">پرونده</a>
            @endif

            @foreach($allowed as $next)
                @continue($next === $floorPrimary || $next === $cancelled || $next === $noShow)
                <button type="button"
                        class="floor-cta floor-cta--ghost"
                        @click="ask(@js($actionLabels[$next] ?? \App\Support\BookingStatus::label($next)), @js($actionHints[$next] ?? 'این تغییر اعمال شود؟'), @js($next))">
                    {{ $actionLabels[$next] ?? \App\Support\BookingStatus::label($next) }}
                </button>
            @endforeach

            @if($can($noShow))
                <button type="button"
                        class="floor-cta floor-cta--ghost"
                        @click="ask(@js($actionLabels[$noShow]), @js($actionHints[$noShow]), @js($noShow))">
                    عدم حضور
                </button>
            @endif

            @if($can($cancelled))
                <button type="button"
                        class="floor-cta floor-cta--danger"
                        @click="ask(@js($actionLabels[$cancelled]), @js($actionHints[$cancelled]), @js($cancelled))">
                    لغو
                </button>
            @endif
        @elseif ($variant === 'board')
            @if($patientUrl)
                <a href="{{ $patientUrl }}" class="board-icon-btn board-icon-btn--profile" title="پرونده بیمار" aria-label="پرونده بیمار">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">{!! $profileIcon !!}</svg>
                </a>
            @else
                <span class="board-icon-btn is-ghost" aria-hidden="true"></span>
            @endif

            @if($status !== $done && $status !== $noShow)
                <a href="{{ $editRoute }}" class="board-icon-btn evt-act--edit" title="ویرایش / جابه‌جایی" aria-label="ویرایش / جابه‌جایی">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">{!! $editIcon !!}</svg>
                </a>
            @else
                <span class="board-icon-btn is-ghost" aria-hidden="true"></span>
            @endif

            @if($can($scheduled))
                <button type="button" class="board-icon-btn evt-act--revert" title="{{ $actionLabels[$scheduled] }}" aria-label="{{ $actionLabels[$scheduled] }}"
                        @click="ask(@js($actionLabels[$scheduled]), @js($actionHints[$scheduled]), @js($scheduled))">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">{!! $icons[$scheduled] !!}</svg>
                </button>
            @else
                <span class="board-icon-btn is-ghost" aria-hidden="true"></span>
            @endif

            @if($can($noShow))
                <button type="button" class="board-icon-btn evt-act--cancel" title="{{ $actionLabels[$noShow] }}" aria-label="{{ $actionLabels[$noShow] }}"
                        @click="ask(@js($actionLabels[$noShow]), @js($actionHints[$noShow]), @js($noShow))">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">{!! $icons[$noShow] !!}</svg>
                </button>
            @elseif($can($cancelled))
                <button type="button" class="board-icon-btn board-icon-btn--trash" title="لغو نوبت" aria-label="لغو نوبت"
                        @click="ask(@js($actionLabels[$cancelled]), @js($actionHints[$cancelled]), @js($cancelled))">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">{!! $trashIcon !!}</svg>
                </button>
            @else
                <span class="board-icon-btn is-ghost" aria-hidden="true"></span>
            @endif

            @if($floorPrimary && $can($floorPrimary))
                <button type="button" class="board-icon-btn {{ $actionTone[$floorPrimary] ?? 'evt-act--done' }}" title="{{ $actionLabels[$floorPrimary] }}" aria-label="{{ $actionLabels[$floorPrimary] }}"
                        @click="ask(@js($actionLabels[$floorPrimary]), @js($actionHints[$floorPrimary] ?? 'این تغییر اعمال شود؟'), @js($floorPrimary))">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">{!! ($icons[$floorPrimary] ?? $icons[$done]) !!}</svg>
                </button>
            @elseif($can($done))
                <button type="button" class="board-icon-btn evt-act--done" title="{{ $actionLabels[$done] }}" aria-label="{{ $actionLabels[$done] }}"
                        @click="ask(@js($actionLabels[$done]), @js($actionHints[$done]), @js($done))">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">{!! $icons[$done] !!}</svg>
                </button>
            @else
                <span class="board-icon-btn is-ghost" aria-hidden="true"></span>
            @endif

            @if($can($confirmed) && ! $floorPrimary)
                <button type="button" class="board-icon-btn evt-act--confirm" title="{{ $actionLabels[$confirmed] }}" aria-label="{{ $actionLabels[$confirmed] }}"
                        @click="ask(@js($actionLabels[$confirmed]), @js($actionHints[$confirmed]), @js($confirmed))">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">{!! $icons[$confirmed] !!}</svg>
                </button>
            @else
                <span class="board-icon-btn is-ghost" aria-hidden="true"></span>
            @endif
        @else
            @foreach($allowed as $next)
                @if ($variant === 'icons')
                    <button type="button"
                            class="board-icon-btn {{ $actionTone[$next] ?? '' }}"
                            title="{{ $actionLabels[$next] ?? \App\Support\BookingStatus::label($next) }}"
                            aria-label="{{ $actionLabels[$next] ?? \App\Support\BookingStatus::label($next) }}"
                            @click="ask(@js($actionLabels[$next] ?? \App\Support\BookingStatus::label($next)), @js($actionHints[$next] ?? 'این تغییر اعمال شود؟'), @js($next))">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">{!! ($icons[$next] ?? $icons[$done]) !!}</svg>
                    </button>
                @else
                    <button type="button"
                            class="touch-action {{ $actionTone[$next] ?? '' }}"
                            style="background:var(--panel-soft);color:var(--ink)"
                            @click="ask(@js($actionLabels[$next] ?? \App\Support\BookingStatus::label($next)), @js($actionHints[$next] ?? 'این تغییر اعمال شود؟'), @js($next))">
                        {{ $actionLabels[$next] ?? \App\Support\BookingStatus::label($next) }}
                    </button>
                @endif
            @endforeach
            @if($status !== $done)
                @if ($variant === 'icons')
                    <a href="{{ $editRoute }}" class="board-icon-btn evt-act--edit" title="ویرایش / جابه‌جایی" aria-label="ویرایش / جابه‌جایی">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">{!! $editIcon !!}</svg>
                    </a>
                @else
                    <a href="{{ $editRoute }}" class="touch-action evt-act--edit" style="background:var(--brand-soft);color:var(--brand-dark)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5 ms-0 me-1" aria-hidden="true">{!! $editIcon !!}</svg>
                        ویرایش / جابه‌جایی
                    </a>
                @endif
            @endif
        @endif

        <template x-teleport="body">
            <div class="confirm-modal" x-show="open" x-cloak @keydown.escape.window="open = false">
                <div class="confirm-modal__backdrop" @click="open = false"></div>
                <div class="confirm-modal__card" role="dialog" aria-modal="true">
                    <div class="confirm-modal__icon" aria-hidden="true">!</div>
                    <h3 class="confirm-modal__title" x-text="title"></h3>
                    <p class="confirm-modal__text" x-text="message"></p>
                    <div class="confirm-modal__actions">
                        <button type="button" class="btn-secondary" @click="open = false">انصراف</button>
                        <button type="button" class="btn-primary" @click="confirm()">بله، انجام بده</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endif
