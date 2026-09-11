@php
    $filterQuery = array_filter([
        'kind' => $kind !== 'surgery' ? $kind : null,
        'hospital_id' => $hospitalId ?: null,
        'per_page' => request('per_page'),
    ], fn ($v) => $v !== null && $v !== '');
    $todayUrl = route('appointments.board', $filterQuery + ['date' => $todayJalali]);
    $tomorrowUrl = route('appointments.board', $filterQuery + ['date' => $tomorrowJalali]);
    $smsOn = config('reminders.sms.enabled') && config('reminders.sms.driver') === 'smsir';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="board-hero">
            <h2 class="board-hero__title">نوبت‌های فعال</h2>
            <div class="board-stats">
                <div class="board-stat">
                    کل نوبت‌های {{ $dateJalali === $todayJalali ? 'امروز' : 'این روز' }}:
                    <strong>{{ number_format($stats['total']) }}</strong>
                </div>
                <div class="board-stat board-stat--ok">
                    تایید شده:
                    <strong>{{ number_format($stats['confirmed']) }}</strong>
                </div>
                <div class="board-stat">
                    انجام‌شده:
                    <strong>{{ number_format($stats['done']) }}</strong>
                </div>
                <div class="board-stat board-stat--warn">
                    عدم حضور:
                    <strong>{{ number_format($stats['no_show']) }}</strong>
                </div>
                <div class="board-stat board-stat--danger">
                    لغو شده:
                    <strong>{{ number_format($stats['cancelled']) }}</strong>
                </div>
                @if(($approvalEnabled ?? false) && ($stats['pending_approval'] ?? 0) > 0)
                <div class="board-stat board-stat--warn">
                    در انتظار تأیید:
                    <strong>{{ number_format($stats['pending_approval']) }}</strong>
                </div>
                @endif
            </div>
            @if(($approvalEnabled ?? false) && ($globalPendingApproval ?? 0) > 0)
                <a href="{{ route('modules.approval.index') }}" class="btn-secondary !px-3 !py-1.5 !text-xs mt-2">
                    صف تأیید آنلاین ({{ $globalPendingApproval }})
                </a>
            @endif
        </div>
    </x-slot>

    <div class="board-page" x-data="{
        filtersOpen: false,
        remindersOn: @js($boardRemindersEnabled && $remindersEnabled),
        boardCards: {},
        toggleBoardCard(id) { this.boardCards[id] = !this.boardCards[id]; },
        isBoardCardOpen(id) { return !!this.boardCards[id]; }
    }">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <section class="board-panel board-panel--filters">
                <div class="board-mobile-bar sm:hidden">
                    <div class="board-mobile-bar__date">
                        <span class="board-mobile-bar__label" dir="ltr">{{ $dateJalali }}</span>
                        <div class="board-date-quick">
                            <a href="{{ $todayUrl }}" class="board-chip {{ $dateJalali === $todayJalali ? 'is-active' : '' }}">امروز</a>
                            <a href="{{ $tomorrowUrl }}" class="board-chip {{ $dateJalali === $tomorrowJalali ? 'is-active' : '' }}">فردا</a>
                        </div>
                    </div>
                    <button type="button" class="board-mobile-bar__filter" @click="filtersOpen = true">فیلترها</button>
                </div>

                <form method="GET" action="{{ route('appointments.board') }}" class="board-filters hidden sm:flex">
                    @if (request('per_page'))
                        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                    @endif

                    <div class="board-filters__date">
                        <span class="board-filters__label">تاریخ شمسی</span>
                        <div class="board-filters__date-row">
                            <x-jalali-date-input name="date" :value="$dateJalali" label="" :allow-past="true" :allow-friday="true" class="!min-h-[2.2rem] !py-1.5 !text-sm board-date-field" />
                            <a href="{{ $todayUrl }}" class="board-chip {{ $dateJalali === $todayJalali ? 'is-active' : '' }}">امروز</a>
                            <a href="{{ $tomorrowUrl }}" class="board-chip {{ $dateJalali === $tomorrowJalali ? 'is-active' : '' }}">فردا</a>
                        </div>
                    </div>

                    <div class="board-filters__field">
                        <span class="board-filters__label">نوع</span>
                        <select name="kind" class="field-input !min-h-[2.2rem] !py-1.5 text-sm" onchange="this.form.submit()">
                            <option value="surgery" @selected($kind === 'surgery')>عمل</option>
                            <option value="visit" @selected($kind === 'visit')>ویزیت</option>
                        </select>
                    </div>
                    @if($kind === 'surgery')
                    <div class="board-filters__field">
                        <span class="board-filters__label">بیمارستان</span>
                        <select name="hospital_id" class="field-input !min-h-[2.2rem] !py-1.5 text-sm" onchange="this.form.submit()">
                            <option value="">بیمارستان</option>
                            @foreach ($hospitals as $hospital)
                                <option value="{{ $hospital->id }}" @selected((string) $hospitalId === (string) $hospital->id)>{{ $hospital->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="board-filters__actions">
                        <button type="submit" class="btn-primary !py-2 !px-4 !text-sm">اعمال</button>
                        <a href="{{ route('appointments.board') }}" class="btn-secondary !py-2 !px-3 !text-sm">حذف</a>
                    </div>
                </form>
            </section>

            <div class="filter-sheet sm:hidden" x-show="filtersOpen" x-cloak @click.self="filtersOpen = false">
                <div class="filter-sheet__panel">
                    <div class="filter-sheet__head">
                        <h3>فیلتر نوبت‌ها</h3>
                        <button type="button" class="tg-tool" @click="filtersOpen = false"><x-icon-close /></button>
                    </div>
                    <form method="GET" action="{{ route('appointments.board') }}" class="space-y-3">
                        @if (request('per_page'))
                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                        @endif
                        <x-jalali-date-input name="date" :value="$dateJalali" label="تاریخ شمسی" :allow-past="true" :allow-friday="true" />
                        <div class="board-date-quick">
                            <a href="{{ $todayUrl }}" class="board-chip {{ $dateJalali === $todayJalali ? 'is-active' : '' }}">امروز</a>
                            <a href="{{ $tomorrowUrl }}" class="board-chip {{ $dateJalali === $tomorrowJalali ? 'is-active' : '' }}">فردا</a>
                        </div>
                        <div>
                            <x-input-label value="نوع" />
                            <select name="kind" class="field-input mt-1">
                                <option value="surgery" @selected($kind === 'surgery')>عمل</option>
                                <option value="visit" @selected($kind === 'visit')>ویزیت</option>
                            </select>
                        </div>
                        @if($kind === 'surgery')
                        <div>
                            <x-input-label value="بیمارستان" />
                            <select name="hospital_id" class="field-input mt-1">
                                <option value="">بیمارستان</option>
                                @foreach ($hospitals as $hospital)
                                    <option value="{{ $hospital->id }}" @selected((string) $hospitalId === (string) $hospital->id)>{{ $hospital->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="flex gap-2 pt-1">
                            <button type="submit" class="btn-primary flex-1 !py-2 !text-sm">اعمال</button>
                            <a href="{{ route('appointments.board') }}" class="btn-secondary !py-2 !px-3 !text-sm">حذف</a>
                        </div>
                    </form>
                </div>
            </div>

            @if($boardRemindersEnabled)
            <section class="board-remind-bar">
                <div class="board-remind-bar__copy">
                    <div class="board-remind-bar__title-row">
                        <h3 class="board-remind-bar__title">یادآوری پیامک</h3>
                        <form method="POST" action="{{ route('reminders.sms-toggle') }}" class="inline-flex" x-ref="reminderToggleForm">
                            @csrf
                            @method('PUT')
                            <label class="board-toggle" title="ارسال پیامک یادآوری برای همه روزها">
                                <input type="checkbox"
                                       @checked($smsRemindersEnabled)
                                       @change="$refs.reminderToggleForm.submit()"
                                       class="sr-only">
                                <span class="board-toggle__track"></span>
                            </label>
                        </form>
                    </div>
                    <p class="board-remind-bar__hint">
                        @if($smsRemindersEnabled)
                            ارسال پیامک یادآوری برای <strong>همه روزها</strong> فعال است.
                        @else
                            <span class="board-remind-bar__state">ارسال پیامک یادآوری برای همه روزها متوقف شده است.</span>
                        @endif
                        @if($smsOn)
                            <span class="board-remind-bar__state is-on">درایور SMS.ir فعال است.</span>
                        @elseif(config('reminders.sms.enabled') && config('reminders.sms.driver') === 'log')
                            <span class="board-remind-bar__state is-log">حالت لاگ — ارسال واقعی خاموش.</span>
                        @endif
                        @if($remindersEnabled)
                            <span class="board-remind-bar__state">زمان خودکار: {{ config('reminders.send_time', '18:00') }}</span>
                        @else
                            <span class="board-remind-bar__state">یادآوری خودکار در تنظیمات غیرفعال است.</span>
                        @endif
                    </p>
                </div>
                @if($remindersEnabled)
                <form method="POST" action="{{ route('reminders.send') }}" x-show="remindersOn" x-cloak>
                    @csrf
                    <input type="hidden" name="date" value="{{ $dateJalali }}">
                    <button type="submit" class="btn-primary board-remind-bar__btn"
                            onclick="return confirm('یادآوری برای تاریخ {{ $dateJalali }} ارسال شود؟')">
                        ارسال یادآوری دستی
                    </button>
                </form>
                @endif
            </section>
            @endif

            {{-- DOM: schedule first → RTL places it on the right (matches mock) --}}
            <div class="board-split" :class="{ 'board-split--solo': !remindersOn || !@js($boardRemindersEnabled) }">
                <section class="board-panel board-col board-col--schedule">
                    <div class="board-panel__head">
                        <h3 class="board-panel__title">
                            برنامه
                            <span class="board-panel__date" dir="ltr">{{ $dateJalali }}</span>
                        </h3>
                        <span class="board-count">{{ number_format($rows->total()) }}</span>
                    </div>

                    @if ($rows->isEmpty())
                        <p class="board-empty">برای این فیلتر نوبتی پیدا نشد.</p>
                    @else
                        <div class="board-schedule-list">
                            @foreach ($rows as $row)
                                @include('appointments.partials.board-card', ['row' => $row, 'turn' => $loop->iteration + (($rows->currentPage() - 1) * $rows->perPage())])
                            @endforeach
                        </div>
                        <x-list-pager :paginator="$rows" />
                    @endif
                </section>

                @if($boardRemindersEnabled)
                <section class="board-panel board-col board-col--remind" x-show="remindersOn" x-cloak>
                    <div class="board-panel__head">
                        <h3 class="board-panel__title">مدیریت یادآوری‌ها</h3>
                        <span class="board-count">{{ $reminderItems->count() }}</span>
                    </div>
                    @if ($reminderItems->isEmpty())
                        <p class="board-empty">موردی برای یادآوری در این روز نیست.</p>
                    @else
                        <div class="board-remind-list">
                            @foreach ($reminderItems as $rem)
                                @php $remId = 'rem-'.$loop->index; @endphp
                                <article class="board-remind-card" :class="{ 'is-open': isBoardCardOpen(@js($remId)) }">
                                    <div class="board-remind-card__bar" role="button" tabindex="0"
                                         @click="toggleBoardCard(@js($remId))"
                                         @keydown.enter.prevent="toggleBoardCard(@js($remId))"
                                         @keydown.space.prevent="toggleBoardCard(@js($remId))"
                                         :aria-expanded="isBoardCardOpen(@js($remId)) ? 'true' : 'false'">
                                        <div class="board-remind-card__bar-main min-w-0">
                                            <span role="button"
                                                  tabindex="0"
                                                  class="board-remind-card__name board-card__name--toolbox"
                                                  data-toolbox-b64="{{ $rem->toolbox_b64 ?? '' }}"
                                                  @click.stop>
                                                {{ $rem->name }}
                                                <span class="board-remind-card__time" dir="ltr">({{ $rem->time }})</span>
                                            </span>
                                            <span class="board-remind-card__note">{{ $rem->label }}{{ $rem->kind ? ' · '.$rem->kind : '' }}</span>
                                        </div>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="board-remind-card__chevron" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                        </svg>
                                    </div>
                                    <div class="board-remind-card__drawer" x-show="isBoardCardOpen(@js($remId))" x-cloak>
                                        <p class="board-remind-card__meta ltr-data">
                                            <span dir="ltr">{{ $rem->mobile ?: '—' }}</span>
                                        </p>
                                        <div class="board-remind-card__actions">
                                            <x-answer-launcher
                                                :mobile="(string) ($rem->mobile ?? '')"
                                                :patient-name="(string) ($rem->name ?? '')"
                                                :patient-id="$rem->patient_id ?? null"
                                                :national-code="$rem->national_code ?? null"
                                                :mobile-secondary="$rem->mobile_secondary ?? null"
                                                :booking="$rem->answer_booking ?? null"
                                                :label="$rem->label"
                                                variant="soft"
                                            />
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
                @endif
            </div>
        </div>
    </div>

    <x-row-toolbox-modal />
    <x-answer-panel mobile="" patient-name="" />
</x-app-layout>
