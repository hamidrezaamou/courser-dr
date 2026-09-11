@php
    $emergencyOnly = $emergencyOnly ?? false;
    $sort = $sort ?? 'date';
    $dir = $dir ?? 'desc';
    $reportNotes = $reportNotes ?? collect();
    $surgeryTypes = $surgeryTypes ?? collect();
    $surgeryTypeId = $surgeryTypeId ?? null;
    $surgerySubtypeId = $surgerySubtypeId ?? null;
    $subtypeIsGeneral = $subtypeIsGeneral ?? false;
    $selectedHospital = $selectedHospital ?? null;
    $selectedSurgeryType = $selectedSurgeryType ?? null;
    $hospitalId = $hospitalId ?? null;

    $sortUrl = function (string $column) use ($sort, $dir) {
        $nextDir = ($sort === $column && $dir === 'asc') ? 'desc' : 'asc';
        if ($sort !== $column) {
            $nextDir = in_array($column, ['date', 'id'], true) ? 'desc' : 'asc';
        }

        return route('reports.index', array_merge(request()->except(['page']), [
            'sort' => $column,
            'dir' => $nextDir,
        ]));
    };

    $sortClass = function (string $column) use ($sort, $dir) {
        if ($sort !== $column) {
            return 'report-th-sort';
        }

        return 'report-th-sort is-active is-'.$dir;
    };

    $todayJ = \App\Support\Jalali::format(now(), 'Y/m/d');
    $yesterdayJ = \App\Support\Jalali::format(now()->subDay(), 'Y/m/d');
    $tomorrowJ = \App\Support\Jalali::format(now()->addDay(), 'Y/m/d');
    $dayUrl = function (string $date) {
        return route('reports.index', array_merge(request()->except(['page', 'from', 'to']), [
            'from' => $date,
            'to' => $date,
        ]));
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2 report-no-print">
            <h2 class="page-title !text-base sm:!text-lg">گزارش نوبت‌ها</h2>
            <div class="report-chips">
                @if ($canExport ?? false)
                    <a href="{{ route('reports.export', request()->query()) }}" class="report-chip">خروجی Excel</a>
                @endif
                <a href="{{ route('prints.index') }}" class="report-chip">پرینت‌ها</a>
                <button type="button" class="report-chip is-active" onclick="window.print()">چاپ</button>
            </div>
        </div>
    </x-slot>

    @php
        $reportBulkMap = [];
        foreach ($rows as $bulkRow) {
            $bulkItem = $bulkRow['item'] ?? null;
            if (! $bulkItem) {
                continue;
            }
            $bulkKey = ($bulkRow['type'] ?? 'visit').':'.$bulkItem->id;
            $reportBulkMap[$bulkKey] = array_merge(
                \App\Support\ToolboxPayload::fromBoardRow($bulkRow),
                ['sheetMode' => 'report']
            );
        }
    @endphp
    <script type="application/json" id="report-bulk-map">{!! json_encode($reportBulkMap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <div
        class="py-4 sm:py-8 report-page"
        :class="{
            'report-page--cards': viewMode === 'card',
            'report-page--list': viewMode !== 'card',
            'report-page--bulk': bulkMode,
        }"
        x-data="reportPage({
            storeUrl: @js(route('reports.notes.store')),
            showUrl: @js(route('reports.notes.show')),
            noteBase: @js(url('/reports/notes')),
            csrf: @js(csrf_token()),
            clinic: @js(config('app.name', 'مطب')),
            today: @js(\App\Support\Jalali::format(now(), 'Y/m/d')),
            userId: {{ (int) auth()->id() }},
            kind: @js($kind),
            surgeryTypeId: @js($surgeryTypeId ? (string) $surgeryTypeId : ''),
            surgerySubtypeId: @js($subtypeIsGeneral ? 'general' : ($surgerySubtypeId ? (string) $surgerySubtypeId : '')),
            surgeryTypes: @js($surgeryTypes->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'subtypes' => $t->subtypes->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
            ])->values()),
            defaultTags: [
                { token: '{نام}', label: 'نام بیمار' },
                { token: '{موبایل}', label: 'موبایل' },
                { token: '{موبایل۲}', label: 'موبایل دوم' },
                { token: '{کدملی}', label: 'کد ملی' },
                { token: '{تاریخ}', label: 'تاریخ نوبت' },
                { token: '{امروز}', label: 'تاریخ امروز' },
                { token: '{مطب}', label: 'نام مطب' },
            ],
        })"
        @open-report-note.window="openNote($event.detail)"
        @click="const btn = $event.target.closest && $event.target.closest('[data-report-view]'); if (btn) setViewMode(btn.getAttribute('data-report-view'))"
    >
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <div class="report-no-print">
                <form method="GET" action="{{ route('reports.index') }}" id="report-filter-form" class="panel calendar-host report-filters">
                    @if (request('per_page'))
                        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                    @endif
                    @if ($sort ?? false)
                        <input type="hidden" name="sort" value="{{ $sort }}">
                    @endif
                    @if ($dir ?? false)
                        <input type="hidden" name="dir" value="{{ $dir }}">
                    @endif
                    @if ($emergencyOnly)
                        <input type="hidden" name="emergency" value="1">
                    @endif

                    <div class="report-filters__toolbar">
                        <div class="report-filters__search">
                            <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 103.39 9.84l3.63 3.64a.75.75 0 101.06-1.06l-3.63-3.64A5.5 5.5 0 009 3.5zM5 9a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd"/>
                            </svg>
                            <input
                                type="search"
                                name="q"
                                id="report-search-input"
                                value="{{ $q }}"
                                placeholder="جستجو نام، موبایل، کد ملی…"
                                autocomplete="off"
                            >
                        </div>

                        <div class="report-chips">
                            <a href="{{ $dayUrl($yesterdayJ) }}" class="report-chip {{ $fromJalali === $yesterdayJ && $toJalali === $yesterdayJ ? 'is-active' : '' }}">دیروز</a>
                            <a href="{{ $dayUrl($todayJ) }}" class="report-chip {{ $fromJalali === $todayJ && $toJalali === $todayJ ? 'is-active' : '' }}">امروز</a>
                            <a href="{{ $dayUrl($tomorrowJ) }}" class="report-chip {{ $fromJalali === $tomorrowJ && $toJalali === $tomorrowJ ? 'is-active' : '' }}">فردا</a>
                            @if ($emergencyOnly)
                                <a href="{{ route('reports.index', request()->except(['emergency', 'page'])) }}" class="report-chip report-chip--danger is-active">اورژانسی ×</a>
                            @else
                                <a href="{{ route('reports.index', array_merge(request()->except(['page']), ['emergency' => 1, 'kind' => 'surgery'])) }}" class="report-chip report-chip--danger">اورژانس</a>
                            @endif
                            <button type="button" class="report-chip report-chip--sheet" @click="filtersOpen = true">فیلترها</button>
                            <a href="{{ route('reports.index') }}" class="report-chip report-chip--ghost">حذف فیلتر</a>
                        </div>
                    </div>

                    <div class="report-filters__inline">
                        <div class="report-filters__field report-filters__field--date">
                            <x-jalali-date-input name="from" :value="$fromJalali" label="از تاریخ" :required="false" :allow-past="true" :allow-friday="true" placeholder="انتخاب تاریخ" />
                        </div>
                        <div class="report-filters__field report-filters__field--date">
                            <x-jalali-date-input name="to" :value="$toJalali" label="تا تاریخ" :required="false" :allow-past="true" :allow-friday="true" placeholder="انتخاب تاریخ" />
                        </div>
                        <label class="report-filters__field">
                            <span>نوع نوبت</span>
                            <select name="kind" class="field-input" x-model="kind" @disabled($emergencyOnly) @change="if (kind === 'visit') { surgeryTypeId = ''; surgerySubtypeId = ''; }">
                                <option value="all" @selected($kind === 'all')>همه</option>
                                <option value="visit" @selected($kind === 'visit')>ویزیت</option>
                                <option value="surgery" @selected($kind === 'surgery')>عمل</option>
                            </select>
                        </label>
                        <label class="report-filters__field">
                            <span>وضعیت</span>
                            <select name="status" class="field-input">
                                @foreach ($statusLabels as $value => $label)
                                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="report-filters__field">
                            <span>مرکز</span>
                            <select name="hospital_id" class="field-input">
                                <option value="">همه مراکز</option>
                                @foreach ($hospitals as $hospital)
                                    <option value="{{ $hospital->id }}" @selected((string) $hospitalId === (string) $hospital->id)>{{ $hospital->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="report-filters__field" x-show="kind !== 'visit'" x-cloak>
                            <span>نوع عمل</span>
                            <select name="surgery_type_id" class="field-input" x-model="surgeryTypeId" @change="surgerySubtypeId = ''">
                                <option value="">همه انواع</option>
                                @foreach ($surgeryTypes as $type)
                                    <option value="{{ $type->id }}" @selected((string) $surgeryTypeId === (string) $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="report-filters__field" x-show="kind !== 'visit' && surgeryTypeId" x-cloak>
                            <span>زیرگروه</span>
                            <select name="surgery_subtype_id" class="field-input" x-model="surgerySubtypeId">
                                <option value="">همه زیرگروه‌ها</option>
                                <option value="general">عمومی</option>
                                <template x-for="sub in currentSubtypes()" :key="'sub-'+sub.id">
                                    <option :value="String(sub.id)" x-text="sub.name"></option>
                                </template>
                            </select>
                        </label>
                    </div>
                </form>

                <div class="filter-sheet sm:hidden" x-show="filtersOpen" x-cloak @click.self="filtersOpen = false">
                    <div class="filter-sheet__panel">
                        <div class="filter-sheet__head">
                            <h3>فیلتر گزارش</h3>
                            <button type="button" class="tg-tool" @click="filtersOpen = false"><x-icon-close /></button>
                        </div>
                        <form method="GET" action="{{ route('reports.index') }}" class="space-y-3 report-filter-compact">
                            @if (request('per_page'))
                                <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                            @endif
                            @if ($q ?? false)
                                <input type="hidden" name="q" value="{{ $q }}">
                            @endif
                            @if ($emergencyOnly)
                                <input type="hidden" name="emergency" value="1">
                            @endif
                            <x-jalali-date-input name="from" :value="$fromJalali" label="از تاریخ" :allow-past="true" :allow-friday="true" class="!min-h-[2.25rem] !py-1.5 text-sm" />
                            <x-jalali-date-input name="to" :value="$toJalali" label="تا تاریخ" :allow-past="true" :allow-friday="true" class="!min-h-[2.25rem] !py-1.5 text-sm" />
                            <div>
                                <x-input-label value="نوع" />
                                <select name="kind" class="field-input mt-1 !min-h-[2.25rem] !py-1.5 text-sm" x-model="kind" @change="if (kind === 'visit') { surgeryTypeId = ''; surgerySubtypeId = ''; }">
                                    <option value="all">همه</option>
                                    <option value="visit">ویزیت</option>
                                    <option value="surgery">عمل</option>
                                </select>
                            </div>
                            <div>
                                <x-input-label value="وضعیت" />
                                <select name="status" class="field-input mt-1 !min-h-[2.25rem] !py-1.5 text-sm">
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label value="بیمارستان" />
                                <select name="hospital_id" class="field-input mt-1 !min-h-[2.25rem] !py-1.5 text-sm">
                                    <option value="">همه مراکز</option>
                                    @foreach ($hospitals as $hospital)
                                        <option value="{{ $hospital->id }}" @selected((string) $hospitalId === (string) $hospital->id)>{{ $hospital->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div x-show="kind !== 'visit'" x-cloak>
                                <x-input-label value="نوع عمل" />
                                <select name="surgery_type_id" class="field-input mt-1 !min-h-[2.25rem] !py-1.5 text-sm" x-model="surgeryTypeId" @change="surgerySubtypeId = ''">
                                    <option value="">همه انواع</option>
                                    @foreach ($surgeryTypes as $type)
                                        <option value="{{ $type->id }}" @selected((string) $surgeryTypeId === (string) $type->id)>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div x-show="kind !== 'visit' && surgeryTypeId" x-cloak>
                                <x-input-label value="زیرگروه" />
                                <select name="surgery_subtype_id" class="field-input mt-1 !min-h-[2.25rem] !py-1.5 text-sm" x-model="surgerySubtypeId">
                                    <option value="">همه زیرگروه‌ها</option>
                                    <option value="general">عمومی</option>
                                    <template x-for="sub in currentSubtypes()" :key="sub.id">
                                        <option :value="String(sub.id)" x-text="sub.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <a href="{{ $dayUrl($yesterdayJ) }}" class="btn-secondary !px-2 !py-1 !text-[11px]">روز قبل</a>
                                <a href="{{ $dayUrl($todayJ) }}" class="btn-secondary !px-2 !py-1 !text-[11px]">امروز</a>
                                <a href="{{ $dayUrl($tomorrowJ) }}" class="btn-secondary !px-2 !py-1 !text-[11px]">روز بعد</a>
                            </div>
                            <div class="flex gap-2 pt-1">
                                <button type="submit" class="btn-primary flex-1 !py-2 !text-xs">بستن و اعمال</button>
                                <a href="{{ route('reports.index') }}" class="btn-ghost !py-2 !text-xs">حذف</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div id="report-results" class="space-y-4">
            <div class="report-print-header">
                @php
                    $printTitle = 'گزارش نوبت‌ها';
                    if (! empty($selectedHospital)) {
                        $centerName = trim((string) $selectedHospital->name);
                        $hasPlaceWord = (bool) preg_match('/^(بیمارستان|بيمارستان|کلینیک|كلينيك|مطب)\p{Z}*/u', $centerName);
                        $printTitle = 'گزارش نوبت‌های '.($hasPlaceWord ? $centerName : 'بیمارستان '.$centerName);
                    } elseif (($kind ?? '') === 'visit') {
                        $printTitle = 'گزارش نوبت‌های ویزیت';
                    } elseif (($kind ?? '') === 'surgery') {
                        $printTitle = 'گزارش نوبت‌های عمل';
                    }
                    if (! empty($selectedSurgeryType)) {
                        $printTitle .= ' · '.$selectedSurgeryType->name;
                    }
                @endphp
                <h1>{{ $printTitle }}@if($emergencyOnly) · اورژانسی@endif</h1>
                @if ($hasDateFilter)
                    <div class="subtitle" dir="ltr">{{ $fromJalali }} — {{ $toJalali }}</div>
                @else
                    <div class="subtitle">همه تاریخ‌ها</div>
                @endif
                <div class="print-date">تاریخ چاپ: {{ jalali(now(), 'Y/m/d H:i') }} · جمع {{ $rows->count() }} مورد</div>
            </div>

            @if ($hospitalStats->isNotEmpty())
                <section class="report-hosp-stats report-no-print panel overflow-hidden p-0">
                    <details>
                        <summary class="border-b px-4 py-3 text-sm font-bold cursor-pointer select-none" style="border-color: var(--line); color: var(--ink);">
                            آمار عمل به تفکیک بیمارستان ({{ $hospitalStats->count() }} مرکز)
                        </summary>
                        <div class="overflow-x-auto">
                            <table class="report-mini-table">
                                <thead>
                                    <tr>
                                        <th>بیمارستان</th>
                                        <th>ثبت</th>
                                        <th>تأیید</th>
                                        <th>انجام</th>
                                        <th>لغو</th>
                                        <th>جمع</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($hospitalStats as $stat)
                                        <tr>
                                            <td class="font-bold">{{ $stat['hospital']->name }}</td>
                                            <td>{{ $stat['counts']['scheduled'] }}</td>
                                            <td>{{ $stat['counts']['confirmed'] }}</td>
                                            <td>{{ $stat['counts']['done'] }}</td>
                                            <td>{{ $stat['counts']['cancelled'] }}</td>
                                            <td class="font-extrabold">{{ $stat['total'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                </section>
            @endif

            <section class="report-table-panel">
                <div class="report-table-toolbar report-no-print">
                    <div class="report-counts">
                        <span class="report-count is-visit">ویزیت <b>{{ number_format($summary['visit']) }}</b></span>
                        <span class="report-count is-surgery">عمل <b>{{ number_format($summary['surgery']) }}</b></span>
                    </div>
                    <div class="report-table-toolbar__end">
                        <button type="button"
                                class="report-chip"
                                :class="bulkMode && 'is-active'"
                                @click="toggleBulk()">
                            <span x-show="!bulkMode">انتخاب گروهی</span>
                            <span x-show="bulkMode" x-cloak>پایان انتخاب</span>
                        </button>
                        <div class="patient-view-toggle report-no-print" role="group" aria-label="نوع نمایش">
                            <button type="button"
                                    class="patient-view-toggle__btn"
                                    data-report-view="list"
                                    title="نمای لیست"
                                    aria-label="نمای لیست">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm0 5.25h.007v.008H3.75V12zm0 5.25h.007v.008H3.75v-.008z" />
                                </svg>
                            </button>
                            <button type="button"
                                    class="patient-view-toggle__btn"
                                    data-report-view="card"
                                    title="نمای کارت"
                                    aria-label="نمای کارت">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 8.25V6zM13.5 6A2.25 2.25 0 0115.75 3.75H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25a2.25 2.25 0 01-2.25-2.25v-2.25z" />
                                </svg>
                            </button>
                        </div>
                        <span>{{ number_format($rows->total()) }} مورد</span>
                    </div>
                </div>

                @if ($rows->isEmpty())
                    <div class="pp-empty">موردی یافت نشد.</div>
                @else
                    <div class="report-table-wrap">
                        <table class="report-table report-table--compact">
                            <thead>
                                <tr>
                                    <th class="col-bulk report-no-print">
                                        <button type="button"
                                                class="report-bulk-toggle"
                                                :class="{
                                                    'is-on': allPageSelected(),
                                                    'is-partial': somePageSelected() && !allPageSelected(),
                                                }"
                                                @click.stop="toggleAll(!allPageSelected())"
                                                :aria-pressed="allPageSelected() ? 'true' : 'false'"
                                                title="انتخاب همه این صفحه"
                                                aria-label="انتخاب همه این صفحه">
                                            <span class="report-bulk-toggle__ui" aria-hidden="true"></span>
                                        </button>
                                    </th>
                                    <th class="col-num">#</th>
                                    <th class="col-id">
                                        <a class="{{ $sortClass('id') }}" href="{{ $sortUrl('id') }}">ID</a>
                                    </th>
                                    <th class="col-kind">
                                        <a class="{{ $sortClass('type') }}" href="{{ $sortUrl('type') }}">نوع</a>
                                    </th>
                                    <th>
                                        <a class="{{ $sortClass('patient') }}" href="{{ $sortUrl('patient') }}">بیمار</a>
                                    </th>
                                    <th>
                                        <a class="{{ $sortClass('national_code') }}" href="{{ $sortUrl('national_code') }}">کد ملی</a>
                                    </th>
                                    <th>
                                        <a class="{{ $sortClass('center') }}" href="{{ $sortUrl('center') }}">مرکز / جزئیات</a>
                                    </th>
                                    <th>
                                        <a class="{{ $sortClass('date') }}" href="{{ $sortUrl('date') }}">تاریخ</a>
                                    </th>
                                    <th>
                                        <a class="{{ $sortClass('time') }}" href="{{ $sortUrl('time') }}">نوبت/ساعت</a>
                                    </th>
                                    <th>
                                        <a class="{{ $sortClass('mobile') }}" href="{{ $sortUrl('mobile') }}">تلفن</a>
                                    </th>
                                    <th>
                                        <a class="{{ $sortClass('status') }}" href="{{ $sortUrl('status') }}">وضعیت</a>
                                    </th>
                                    <th class="col-note">توضیحات</th>
                                    <th class="col-actions report-no-print">عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $index => $row)
                                    @php
                                        $item = $row['item'];
                                        $time = $item->scheduled_time ? \App\Support\SlotLabel::display((string) $item->scheduled_time) : '—';
                                        $dateJalali = jalali($item->scheduled_date, 'Y/m/d');
                                        $rowStatus = $item->status;
                                        $statusLabel = $statusLabels[$rowStatus] ?? \App\Support\BookingStatus::label($rowStatus);
                                        $noteKey = $row['type'].':'.$item->id;
                                        $noteThread = $reportNotes->get($noteKey) ?: collect();
                                        $noteCount = $noteThread->count();
                                        if ($noteCount === 0 && trim((string) ($item->notes ?? '')) !== '') {
                                            $noteCount = 1;
                                        }
                                        $hasReportNote = $noteCount > 0;
                                        $notePrint = $noteThread
                                            ->where('include_in_print', true)
                                            ->pluck('body')
                                            ->filter()
                                            ->implode("\n");
                                        $weekday = jalali_weekday($item->scheduled_date);
                                        $mobileSecondary = trim((string) ($item->mobile_secondary ?? $item->patient?->mobile_secondary ?? ''));
                                        $toolboxPayload = array_merge(
                                            \App\Support\ToolboxPayload::fromBoardRow($row),
                                            ['sheetMode' => 'report']
                                        );
                                        if ($row['type'] === 'surgery') {
                                            $typeLabel = trim((string) ($item->surgery_type ?: 'عمل'));
                                            $subtypeName = trim((string) ($item->surgerySubtype?->name ?? ''));
                                            $eyeSide = trim((string) ($item->eye_side ?? ''));
                                            $detailParts = [$typeLabel];
                                            if ($subtypeName !== '' && mb_stripos($typeLabel, $subtypeName) === false) {
                                                $detailParts[] = $subtypeName;
                                            }
                                            $detailSoFar = implode(' ', $detailParts);
                                            if ($eyeSide !== '' && mb_stripos($detailSoFar, $eyeSide) === false) {
                                                $detailParts[] = $eyeSide;
                                            }
                                            $detail = implode(' · ', $detailParts);
                                            $center = $row['hospital'] ?: '—';
                                            $meta = $detail.' · '.$dateJalali.' '.$time.($row['hospital'] ? ' · '.$row['hospital'] : '');
                                            $smsBody = \App\Support\AppointmentSms::forItem($item, 'surgery');
                                            $editUrl = route('surgery-appointments.edit', $item);
                                            $printUrl = route('surgery-appointments.prints', $item);
                                        } else {
                                            $detail = trim(($item->visit_type ?: 'ویزیت').($item->reason ? ' · '.$item->reason : ''));
                                            $center = 'ویزیت مطب';
                                            $meta = $detail.' · '.$dateJalali.' '.$time;
                                            $smsBody = \App\Support\AppointmentSms::forItem($item, 'visit');
                                            $editUrl = route('appointments.edit', $item);
                                            $printUrl = null;
                                        }
                                    @endphp
                                    <tr class="report-bulk-host"
                                        data-bulk-host="{{ $row['type'] }}:{{ $item->id }}"
                                        :class="{ 'is-bulk-selected': isSelected(@js($row['type'].':'.$item->id)) }"
                                        @click="onBulkHostClick($event, @js($row['type'].':'.$item->id))">
                                        <td class="col-bulk report-no-print">
                                            <button type="button"
                                                    class="report-bulk-toggle"
                                                    data-bulk-key="{{ $row['type'] }}:{{ $item->id }}"
                                                    :class="{ 'is-on': isSelected(@js($row['type'].':'.$item->id)) }"
                                                    :aria-pressed="isSelected(@js($row['type'].':'.$item->id)) ? 'true' : 'false'"
                                                    @click.stop="toggleKey(@js($row['type'].':'.$item->id))"
                                                    aria-label="انتخاب {{ $item->patient_name }}">
                                                <span class="report-bulk-toggle__ui" aria-hidden="true"></span>
                                            </button>
                                        </td>
                                        <td class="col-num">{{ $rows->firstItem() + $index }}</td>
                                        <td class="col-id ltr-data">{{ $item->id }}</td>
                                        <td class="col-kind">
                                            <span class="report-kind {{ $row['type'] === 'surgery' ? 'is-surgery' : 'is-visit' }}">
                                                {{ $row['type'] === 'surgery' ? 'عمل' : 'ویزیت' }}
                                            </span>
                                            @if($row['type'] === 'surgery' && $item->is_emergency)
                                                <span class="report-emergency-badge">اورژانس</span>
                                            @endif
                                        </td>
                                        <td class="col-name">
                                            @php $reportToolboxB64 = \App\Support\ToolboxPayload::encode($toolboxPayload); @endphp
                                            <button type="button"
                                                    class="report-name-toolbox"
                                                    data-toolbox-b64="{{ $reportToolboxB64 }}">{{ $item->patient_name }}</button>
                                        </td>
                                        <td class="mono ltr-data">{{ $item->national_code }}</td>
                                        <td class="col-center {{ $hospitalId ? 'is-hospital-scoped' : '' }}">
                                            <div class="col-detail-main">{{ $center }}</div>
                                            <div class="col-detail-sub">{{ $detail }}</div>
                                        </td>
                                        <td>
                                            <div class="mono ltr-data">{{ $dateJalali }}</div>
                                            @if ($weekday !== '')
                                                <div class="col-detail-sub">{{ $weekday }}</div>
                                            @endif
                                        </td>
                                        <td class="mono strong-slot ltr-data">{{ $time }}</td>
                                        <td class="report-phones">
                                            <div class="report-phones__list report-no-print">
                                                @if ($item->mobile)
                                                    <span dir="ltr">{{ $item->mobile }}</span>
                                                @endif
                                                @if ($mobileSecondary !== '')
                                                    <span dir="ltr">{{ $mobileSecondary }}</span>
                                                @endif
                                            </div>
                                            <div class="report-phones__print report-print-only">
                                                @if ($item->mobile)
                                                    <span dir="ltr">{{ grouped_mobile($item->mobile) }}</span>
                                                @endif
                                                @if ($mobileSecondary !== '')
                                                    <span dir="ltr">{{ grouped_mobile($mobileSecondary) }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="report-status is-{{ $rowStatus }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td class="col-note">
                                            <div class="report-no-print">
                                            @if ($hasReportNote)
                                                <button
                                                    type="button"
                                                    class="report-note-icon"
                                                    data-note-key="{{ $noteKey }}"
                                                    title="مشاهده توضیحات"
                                                    @click="openNote({
                                                        subjectType: @js($row['type']),
                                                        subjectId: {{ $item->id }},
                                                        patientId: {{ (int) $item->patient_id }},
                                                        patientName: @js($item->patient_name),
                                                        mobile: @js($item->mobile),
                                                        mobileSecondary: @js($mobileSecondary),
                                                        nationalCode: @js($item->national_code),
                                                        meta: @js($meta),
                                                        date: @js($dateJalali),
                                                    })"
                                                >
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10.5h8M8 14h5m8-2c0 4.556-4.03 8.25-9 8.25a9.76 9.76 0 01-2.51-.326l-4.24 1.326 1.35-3.63A7.98 7.98 0 013 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                                                    </svg>
                                                    <span class="report-note-count">{{ $noteCount }}</span>
                                                </button>
                                            @endif
                                            </div>
                                            <div class="report-print-only report-note-print" data-note-print="{{ $noteKey }}">{{ $notePrint }}</div>
                                        </td>
                                        <td class="col-actions report-no-print">
                                            @php
                                                $reportTypeIds = $row['type'] === 'surgery'
                                                    ? \App\Support\SurgeryChecklist::resolveTypeIds($item)
                                                    : ['type_id' => null, 'subtype_id' => null];
                                                $reportHasChecklist = $row['type'] === 'surgery' && \App\Support\SurgeryChecklist::isAvailable();
                                            @endphp
                                            <x-row-toolbox
                                                :name="$item->patient_name"
                                                :mobile="$item->mobile"
                                                :mobile-secondary="$mobileSecondary"
                                                :national-code="$item->national_code"
                                                :meta="$meta"
                                                :patient-url="route('patients.show', $item->patient_id)"
                                                :edit-url="$editUrl"
                                                :print-url="$printUrl"
                                                :sms-body="$smsBody"
                                                sheet-mode="report"
                                                :subject-type="$row['type']"
                                                :subject-id="$item->id"
                                                :patient-id="$item->patient_id"
                                                :date-label="$dateJalali"
                                                :surgery-appointment-id="$row['type'] === 'surgery' ? $item->id : null"
                                                :surgery-type-id="$reportTypeIds['type_id'] ?? null"
                                                :surgery-subtype-id="$reportTypeIds['subtype_id'] ?? null"
                                                :has-surgery-checklist="$reportHasChecklist"
                                                :can-change-status="$toolboxPayload['canChangeStatus'] ?? false"
                                                :status="$toolboxPayload['status'] ?? null"
                                                :status-label="$toolboxPayload['statusLabel'] ?? null"
                                                :status-url="$toolboxPayload['statusUrl'] ?? null"
                                                :status-actions="$toolboxPayload['statusActions'] ?? []"
                                                :answer-booking="$toolboxPayload['answerBooking'] ?? null"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="report-cards report-no-print">
                        @foreach ($rows as $index => $row)
                            @include('reports.partials.result-card', ['row' => $row, 'index' => $index])
                        @endforeach
                    </div>
                    <x-list-pager :paginator="$rows" class="report-no-print" />
                    <div class="report-bulk-bar report-no-print" x-show="bulkMode" x-cloak>
                        <div class="report-bulk-bar__info">
                            <strong x-text="selectedCountFa() + ' مورد انتخاب شده'"></strong>
                            <span x-show="selectedCount() === 0">از لیست یا کارت‌ها انتخاب کنید</span>
                            <span class="report-bulk-bar__err" x-show="bulkError" x-text="bulkError" x-cloak></span>
                        </div>
                        <div class="report-bulk-bar__acts">
                            <button type="button"
                                    class="btn-secondary !py-1.5 !text-xs"
                                    @click="toggleAll(!allPageSelected())"
                                    x-text="allPageSelected() ? 'لغو همه صفحه' : 'انتخاب همه صفحه'"></button>
                            <button type="button" class="btn-secondary !py-1.5 !text-xs" @click="clearSelected()" :disabled="selectedCount() === 0">پاک کردن</button>
                            <button type="button"
                                    class="btn-primary !py-1.5 !text-xs"
                                    data-bulk-toolbox
                                    :disabled="selectedCount() === 0"
                                    @click.stop.prevent="openBulkToolbox()">ابزار گروهی</button>
                        </div>
                    </div>
                @endif
            </section>
            </div>
        </div>

        <div class="rn-overlay" x-show="open" x-cloak @click.self="closeNote()" @keydown.escape.window="if (open) closeNote()">
            <div class="rn-sheet rn-sheet--chat" role="dialog" aria-modal="true">
                <div class="rn-sheet__handle" aria-hidden="true"></div>
                <header class="rn-head">
                    <div>
                        <h3>گفتگوی گزارش</h3>
                        <p x-text="patientName || '—'"></p>
                    </div>
                    <button type="button" class="rn-close" @click="closeNote()" aria-label="بستن">×</button>
                </header>
                <div class="rn-meta" x-show="meta" x-text="meta"></div>
                <div class="rn-chat" x-ref="chat">
                    <p class="rn-chat-empty" x-show="!loadingChat && messages.length === 0">هنوز پیامی نیست. اولین پیام را شما بنویسید.</p>
                    <p class="rn-chat-empty" x-show="loadingChat">در حال بارگذاری…</p>
                    <template x-for="msg in messages" :key="msg.id">
                        <article class="rn-bubble" :class="{ 'is-mine': msg.mine, 'is-theirs': !msg.mine, 'is-print': msg.include_in_print, 'is-pinned': msg.pinned }">
                            <p class="rn-bubble__pin" x-show="msg.pinned">
                                <span>پین شده</span>
                                <span x-show="msg.sourceLabel" x-text="msg.sourceLabel"></span>
                            </p>
                            <p class="rn-bubble__text" x-show="!isEditing(msg)" x-text="msg.body"></p>
                            <textarea
                                class="rn-bubble__edit field-input"
                                x-show="isEditing(msg)"
                                x-cloak
                                x-model="editBody"
                                rows="3"
                                @keydown.enter.prevent="if (!$event.shiftKey) saveEdit(msg)"
                            ></textarea>
                            <div class="rn-bubble__foot">
                                <span class="rn-bubble__author" x-text="msg.author"></span>
                                <span class="rn-bubble__time" dir="ltr">
                                    <span x-text="msg.date"></span>
                                    <span x-text="msg.time"></span>
                                    <em x-show="msg.edited">ویرایش‌شده</em>
                                    <em class="is-print-flag" x-show="msg.include_in_print">در چاپ</em>
                                </span>
                            </div>
                            <div class="rn-bubble__acts">
                                <div class="rn-bubble__acts-row" x-show="isEditing(msg)">
                                    <button type="button" @click.stop="saveEdit(msg)">ذخیره</button>
                                    <button type="button" @click.stop="cancelEdit()">انصراف</button>
                                </div>
                                <div class="rn-bubble__acts-row" x-show="!isEditing(msg)">
                                    <button
                                        type="button"
                                        class="is-print-btn"
                                        :class="msg.include_in_print && 'is-on'"
                                        @click.stop="togglePrint(msg)"
                                        x-text="msg.include_in_print ? 'حذف از چاپ' : 'ثبت در چاپ'"
                                    ></button>
                                    <span class="rn-bubble__acts-own">
                                        <button type="button" @click.stop="startEdit(msg)">ویرایش</button>
                                        <button type="button" class="is-danger" @click.stop="deleteMessage(msg)">حذف</button>
                                    </span>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>
                <div class="rn-composer">
                    <div class="rn-tags">
                        <template x-for="tag in tags" :key="tag.token + ':' + tag.label">
                            <span class="rn-tag-wrap">
                                <button type="button" class="rn-tag" @click="insertTag(tag.token)" x-text="tag.label"></button>
                                <button type="button" class="rn-tag-x" @click.stop="removeTag(tag)" aria-label="حذف تگ">×</button>
                            </span>
                        </template>
                    </div>
                    <div class="rn-tag-add">
                        <input class="field-input rn-tag-add__input" x-model="newTag" maxlength="40" placeholder="تگ جدید…" @keydown.enter.prevent="addTag()">
                        <button type="button" class="btn-secondary !py-1.5 !px-3 !text-xs" @click="addTag()">افزودن تگ</button>
                    </div>
                    <textarea class="rn-body field-input" rows="3" x-model="body" x-ref="body" placeholder="پیام خود را بنویسید…" @keydown.enter="onComposerKey($event)"></textarea>
                    <div class="rn-actions">
                        <button type="button" class="btn-primary" :disabled="saving" @click="saveNote()" x-text="saving ? 'در حال ارسال...' : 'ارسال'"></button>
                        <button type="button" class="btn-secondary" @click="closeNote()">بستن</button>
                    </div>
                    <p class="rn-msg" x-show="message" x-text="message" x-cloak></p>
                </div>
            </div>
        </div>
    </div>

    <x-row-toolbox-modal />
    <x-answer-panel mobile="" patient-name="" />

    <script>
        function reportPage(cfg) {
            return {
                filtersOpen: false,
                viewMode: 'list',
                bulkMode: false,
                selected: {},
                bulkError: '',
                bulkMap: {},
                kind: cfg.kind || 'all',
                surgeryTypeId: cfg.surgeryTypeId || '',
                surgerySubtypeId: cfg.surgerySubtypeId || '',
                surgeryTypes: Array.isArray(cfg.surgeryTypes) ? cfg.surgeryTypes : [],
                open: false,
                saving: false,
                loadingChat: false,
                message: '',
                subjectType: '',
                subjectId: null,
                patientId: null,
                patientName: '',
                mobile: '',
                mobileSecondary: '',
                nationalCode: '',
                meta: '',
                dateLabel: '',
                body: '',
                messages: [],
                editingId: null,
                editBody: '',
                newTag: '',
                tags: [],
                init() {
                    try {
                        const mapEl = document.getElementById('report-bulk-map');
                        if (mapEl && mapEl.textContent) {
                            const parsed = JSON.parse(mapEl.textContent);
                            if (parsed && typeof parsed === 'object') this.bulkMap = parsed;
                        }
                    } catch (e) {
                        this.bulkMap = {};
                    }
                    this.tags = this.loadTags();
                    this.$watch('open', (on) => {
                        if (on) {
                            document.documentElement.style.overflow = 'hidden';
                            document.body.style.overflow = 'hidden';
                            return;
                        }
                        if (document.querySelector('.row-toolbox-overlay.is-open')) return;
                        document.documentElement.style.overflow = '';
                        document.body.style.overflow = '';
                    });
                    if (window.OverlayHistory) {
                        window.OverlayHistory.bindWatch(this, 'open', 'report-note');
                        window.OverlayHistory.bindWatch(this, 'filtersOpen', 'report-filters');
                    }
                    try {
                        const saved = localStorage.getItem('reports.viewMode');
                        if (saved === 'card' || saved === 'list') {
                            this.viewMode = saved;
                        } else if (window.matchMedia && window.matchMedia('(max-width: 720px)').matches) {
                            this.viewMode = 'card';
                        }
                    } catch (e) {}
                },
                setViewMode(mode) {
                    this.viewMode = mode === 'card' ? 'card' : 'list';
                    try { localStorage.setItem('reports.viewMode', this.viewMode); } catch (e) {}
                },
                faNum(n) {
                    return String(n).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);
                },
                pageKeys() {
                    return Object.keys(this.bulkMap || {});
                },
                isSelected(key) {
                    return !!this.selected[key];
                },
                selectedCount() {
                    return Object.keys(this.selected).length;
                },
                selectedCountFa() {
                    return this.faNum(this.selectedCount());
                },
                allPageSelected() {
                    const keys = this.pageKeys();
                    return keys.length > 0 && keys.every((key) => this.isSelected(key));
                },
                somePageSelected() {
                    return this.pageKeys().some((key) => this.isSelected(key));
                },
                toggleKey(key) {
                    if (!key || !this.bulkMap[key]) return;
                    const next = Object.assign({}, this.selected);
                    if (next[key]) delete next[key];
                    else next[key] = true;
                    this.selected = next;
                    this.bulkError = '';
                },
                setSelected(key, on) {
                    if (!key || !this.bulkMap[key]) return;
                    const next = Object.assign({}, this.selected);
                    if (on) next[key] = true;
                    else delete next[key];
                    this.selected = next;
                    this.bulkError = '';
                },
                toggleAll(on) {
                    const page = this.pageKeys();
                    if (!page.length) {
                        this.bulkError = 'ردیفی برای انتخاب در این صفحه نیست.';
                        return;
                    }
                    const next = Object.assign({}, this.selected);
                    page.forEach((key) => {
                        if (on) next[key] = true;
                        else delete next[key];
                    });
                    this.selected = next;
                    this.bulkError = '';
                },
                clearSelected() {
                    this.selected = {};
                    this.bulkError = '';
                },
                toggleBulk() {
                    this.bulkMode = !this.bulkMode;
                    this.bulkError = '';
                    if (!this.bulkMode) this.selected = {};
                },
                onBulkHostClick(event, key) {
                    if (!this.bulkMode || !key) return;
                    if (event.target.closest('a, button, input, label, select, textarea, .row-toolbox-trigger, [data-bulk-toolbox], .report-bulk-toggle')) return;
                    this.toggleKey(key);
                },
                collectSelectedPayloads() {
                    return Object.keys(this.selected)
                        .filter((key) => this.selected[key] && this.bulkMap[key])
                        .map((key) => this.bulkMap[key]);
                },
                openBulkToolbox() {
                    this.bulkError = '';
                    if (!this.selectedCount()) {
                        this.bulkError = 'اول حداقل یک مورد را انتخاب کنید.';
                        return;
                    }
                    const items = this.collectSelectedPayloads();
                    if (!items.length) {
                        this.bulkError = 'اطلاعات این صفحه ناقص است. یک‌بار صفحه را تازه کنید.';
                        return;
                    }
                    const statusMap = {};
                    items.forEach((row) => {
                        (row.statusActions || []).forEach((action) => {
                            if (!action || !action.id) return;
                            if (!statusMap[action.id]) statusMap[action.id] = Object.assign({}, action);
                        });
                    });
                    const detail = {
                        bulk: true,
                        bulkItems: items,
                        sheetMode: 'report',
                        name: 'عملیات گروهی',
                        meta: this.faNum(items.length) + ' مورد انتخاب شده',
                        smsEnabled: items.some((row) => row.smsEnabled && row.mobile),
                        readyAnswersEnabled: items.some((row) => row.readyAnswersEnabled !== false),
                        canChangeStatus: items.some((row) => row.canChangeStatus && row.statusUrl),
                        statusActions: Object.values(statusMap),
                        statusLabel: 'چند وضعیت',
                    };
                    if (window.RowToolbox && typeof window.RowToolbox.open === 'function') {
                        window.RowToolbox.open(detail);
                        return;
                    }
                    window.dispatchEvent(new CustomEvent('open-row-toolbox', { detail: detail }));
                },
                currentSubtypes() {
                    const id = String(this.surgeryTypeId || '');
                    const type = (this.surgeryTypes || []).find((item) => String(item.id) === id);
                    return type ? (type.subtypes || []) : [];
                },
                defaultTagList() {
                    return (cfg.defaultTags || []).map((t) => ({ token: t.token, label: t.label }));
                },
                loadTags() {
                    try {
                        const raw = localStorage.getItem('reportNoteTags.v1');
                        if (raw) {
                            const parsed = JSON.parse(raw);
                            if (Array.isArray(parsed) && parsed.length) {
                                return parsed.filter((t) => t && t.label && t.token);
                            }
                        }
                    } catch (e) {}
                    return this.defaultTagList();
                },
                persistTags() {
                    try { localStorage.setItem('reportNoteTags.v1', JSON.stringify(this.tags)); } catch (e) {}
                },
                addTag() {
                    const label = String(this.newTag || '').trim();
                    if (!label) return;
                    if (this.tags.some((t) => t.label === label || t.token === label)) {
                        this.newTag = '';
                        return;
                    }
                    this.tags.push({ token: label, label: label });
                    this.newTag = '';
                    this.persistTags();
                },
                removeTag(tag) {
                    this.tags = this.tags.filter((t) => t.token !== tag.token || t.label !== tag.label);
                    if (!this.tags.length) this.tags = this.defaultTagList();
                    this.persistTags();
                },
                openNote(detail) {
                    detail = detail || {};
                    this.subjectType = detail.subjectType || '';
                    this.subjectId = detail.subjectId || null;
                    this.patientId = detail.patientId || null;
                    this.patientName = detail.patientName || '';
                    this.mobile = detail.mobile || '';
                    this.mobileSecondary = detail.mobileSecondary || '';
                    this.nationalCode = detail.nationalCode || '';
                    this.meta = detail.meta || '';
                    this.dateLabel = detail.date || '';
                    this.body = '';
                    this.message = '';
                    this.messages = [];
                    this.editingId = null;
                    this.editBody = '';
                    this.open = true;
                    this.fetchMessages();
                    this.$nextTick(() => {
                        if (this.$refs.body) this.$refs.body.focus();
                    });
                },
                closeNote() {
                    this.open = false;
                    this.editingId = null;
                },
                isEditing(msg) {
                    return msg && Number(this.editingId) === Number(msg.id);
                },
                noteUrl(msg, action) {
                    const id = msg && msg.id ? msg.id : '';
                    return cfg.noteBase.replace(/\/$/, '') + '/' + id + (action ? '/' + action : '');
                },
                syncPrintCell() {
                    const key = this.subjectType + ':' + this.subjectId;
                    const cell = document.querySelector('[data-note-print="' + key + '"]');
                    if (cell) {
                        const printed = this.messages
                            .filter((m) => m && m.include_in_print && m.body)
                            .map((m) => m.body)
                            .join('\n');
                        cell.textContent = printed;
                    }
                    const btn = document.querySelector('.report-note-icon[data-note-key="' + key + '"]');
                    if (!btn) return;
                    let badge = btn.querySelector('.report-note-count');
                    const count = (this.messages || []).length;
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'report-note-count';
                        btn.appendChild(badge);
                    }
                    badge.textContent = String(count);
                    badge.hidden = count < 1;
                },
                preview(text) {
                    const map = {
                        'نام': this.patientName,
                        'موبایل': this.mobile,
                        'موبایل۲': this.mobileSecondary,
                        'کدملی': this.nationalCode,
                        'تاریخ': this.dateLabel,
                        'امروز': cfg.today,
                        'مطب': cfg.clinic,
                    };
                    return String(text || '').replace(/\{([^}]+)\}/g, (match, key) => {
                        const value = map[String(key).trim()];
                        return value ? String(value) : match;
                    });
                },
                insertTag(token) {
                    const el = this.$refs.body;
                    const current = this.body || '';
                    if (!el) {
                        this.body = current + token;
                        return;
                    }
                    const start = el.selectionStart ?? current.length;
                    const end = el.selectionEnd ?? current.length;
                    this.body = current.slice(0, start) + token + current.slice(end);
                    this.$nextTick(() => {
                        el.focus();
                        const pos = start + token.length;
                        el.setSelectionRange(pos, pos);
                    });
                },
                onComposerKey(e) {
                    if (e.shiftKey || e.isComposing || e.key !== 'Enter') return;
                    e.preventDefault();
                    this.saveNote();
                },
                apiError(res, data, fallback) {
                    if (data && data.message) return data.message;
                    if (data && data.errors) {
                        const first = Object.values(data.errors)[0];
                        if (Array.isArray(first) && first[0]) return first[0];
                    }
                    if (res && res.status >= 500) return 'خطای سرور در ذخیره پیام. یک‌بار دیگر ارسال کنید.';
                    return fallback;
                },
                scrollChat() {
                    this.$nextTick(() => {
                        const box = this.$refs.chat;
                        if (box) box.scrollTop = box.scrollHeight;
                    });
                },
                sortMessages(list) {
                    return (list || []).slice().sort((a, b) => {
                        const pin = Number(!!b.pinned) - Number(!!a.pinned);
                        if (pin !== 0) return pin;
                        return Number(a.id || 0) - Number(b.id || 0);
                    });
                },
                async fetchMessages() {
                    if (!this.subjectType || !this.subjectId) return;
                    this.loadingChat = true;
                    try {
                        const url = cfg.showUrl
                            + '?subject_type=' + encodeURIComponent(this.subjectType)
                            + '&subject_id=' + encodeURIComponent(this.subjectId);
                        const res = await fetch(url, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        const data = await res.json();
                        this.messages = this.sortMessages(Array.isArray(data.messages) ? data.messages : []);
                        this.syncPrintCell();
                        this.scrollChat();
                    } catch (e) {
                        this.message = 'بارگذاری گفتگو ناموفق بود.';
                    } finally {
                        this.loadingChat = false;
                    }
                },
                async saveNote() {
                    if (!this.subjectType || !this.subjectId) {
                        this.message = 'ردیف گزارش مشخص نیست.';
                        return;
                    }
                    const text = this.preview(this.body).trim();
                    if (!text) {
                        this.message = 'متن پیام را بنویسید.';
                        return;
                    }
                    this.saving = true;
                    this.message = '';
                    try {
                        const res = await fetch(cfg.storeUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': cfg.csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                subject_type: this.subjectType,
                                subject_id: this.subjectId,
                                patient_id: this.patientId || null,
                                body: text,
                            }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) throw new Error(this.apiError(res, data, 'ارسال ناموفق بود'));
                        if (data.note) {
                            this.messages.push(data.note);
                            this.messages = this.sortMessages(this.messages);
                        }
                        this.body = '';
                        this.scrollChat();
                    } catch (e) {
                        this.message = e.message || 'خطا در ارسال پیام';
                    } finally {
                        this.saving = false;
                    }
                },
                startEdit(msg) {
                    if (!msg) return;
                    this.editingId = Number(msg.id);
                    this.editBody = msg.body;
                    this.message = '';
                },
                cancelEdit() {
                    this.editingId = null;
                    this.editBody = '';
                },
                async saveEdit(msg) {
                    const text = String(this.editBody || '').trim();
                    if (!text) {
                        this.message = 'متن خالی ذخیره نمی‌شود.';
                        return;
                    }
                    this.message = '';
                    try {
                        const res = await fetch(this.noteUrl(msg, 'update'), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': cfg.csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ body: text }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) throw new Error(this.apiError(res, data, 'ویرایش ناموفق بود'));
                        const idx = this.messages.findIndex((m) => Number(m.id) === Number(msg.id));
                        if (idx >= 0 && data.note) this.messages.splice(idx, 1, data.note);
                        this.cancelEdit();
                        this.syncPrintCell();
                    } catch (e) {
                        this.message = e.message || 'خطا در ویرایش';
                    }
                },
                async deleteMessage(msg) {
                    if (!msg) return;
                    if (!confirm('این پیام حذف شود؟')) return;
                    this.message = '';
                    try {
                        const res = await fetch(this.noteUrl(msg, 'delete'), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': cfg.csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) throw new Error(this.apiError(res, data, 'حذف ناموفق بود'));
                        this.messages = this.messages.filter((m) => Number(m.id) !== Number(msg.id));
                        this.syncPrintCell();
                    } catch (e) {
                        this.message = e.message || 'خطا در حذف';
                    }
                },
                async togglePrint(msg) {
                    if (!msg) return;
                    this.message = '';
                    try {
                        const res = await fetch(this.noteUrl(msg, 'print'), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': cfg.csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ include_in_print: !msg.include_in_print }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) throw new Error(this.apiError(res, data, 'ثبت چاپ ناموفق بود'));
                        const idx = this.messages.findIndex((m) => Number(m.id) === Number(msg.id));
                        if (idx >= 0 && data.note) this.messages.splice(idx, 1, data.note);
                        else if (idx >= 0) this.messages[idx].include_in_print = !msg.include_in_print;
                        this.syncPrintCell();
                    } catch (e) {
                        this.message = e.message || 'خطا در ثبت چاپ';
                    }
                },
            };
        }
    </script>
    <script>
        (function () {
            var KEY = 'reports.table.sort';
            var params = new URLSearchParams(location.search);
            if (!params.get('sort')) {
                try {
                    var saved = JSON.parse(localStorage.getItem(KEY) || 'null');
                    if (saved && saved.sort) {
                        params.set('sort', saved.sort);
                        params.set('dir', saved.dir || 'desc');
                        location.replace(location.pathname + '?' + params.toString());
                        return;
                    }
                } catch (e) {}
            } else {
                try {
                    localStorage.setItem(KEY, JSON.stringify({
                        sort: params.get('sort'),
                        dir: params.get('dir') || 'desc',
                    }));
                } catch (e) {}
            }

            var form = document.getElementById('report-filter-form');
            var search = document.getElementById('report-search-input');
            var timer;
            var composing = false;
            var inflight = null;

            function reportQueryUrl() {
                var url = new URL(form.action, window.location.origin);
                var data = new FormData(form);
                data.forEach(function (value, key) {
                    if (key === 'q') return;
                    if (value === null || String(value).trim() === '') {
                        url.searchParams.delete(key);
                        return;
                    }
                    url.searchParams.set(key, String(value));
                });
                url.searchParams.delete('page');
                var q = search ? String(search.value || '').trim() : '';
                if (q) url.searchParams.set('q', q);
                else url.searchParams.delete('q');
                return url;
            }

            function applyReportFilters(push) {
                if (!form) return;
                var url = reportQueryUrl();
                if (inflight) inflight.abort();
                inflight = new AbortController();
                var results = document.getElementById('report-results');
                if (results) results.classList.add('is-loading');
                fetch(url.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    signal: inflight.signal,
                    credentials: 'same-origin',
                }).then(function (res) { return res.text(); }).then(function (html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var next = doc.querySelector('#report-results');
                    var cur = document.getElementById('report-results');
                    if (next && cur) cur.replaceWith(next);
                    if (push) history.pushState({}, '', url);
                    else history.replaceState({}, '', url);
                    var exportBtn = document.querySelector('a[href*="reports/export"]');
                    if (exportBtn) {
                        var exportUrl = new URL(exportBtn.getAttribute('href'), window.location.origin);
                        url.searchParams.forEach(function (value, key) {
                            exportUrl.searchParams.set(key, value);
                        });
                        ['from', 'to', 'kind', 'status', 'hospital_id', 'surgery_type_id', 'surgery_subtype_id', 'q', 'emergency', 'sort', 'dir'].forEach(function (key) {
                            if (!url.searchParams.has(key)) exportUrl.searchParams.delete(key);
                        });
                        exportBtn.setAttribute('href', exportUrl.pathname + exportUrl.search);
                    }
                }).catch(function (err) {
                    if (err && err.name === 'AbortError') return;
                }).finally(function () {
                    var cur = document.getElementById('report-results');
                    if (cur) cur.classList.remove('is-loading');
                });
            }

            if (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    applyReportFilters(false);
                });
                form.addEventListener('change', function (event) {
                    if (event.target && event.target.id === 'report-search-input') return;
                    applyReportFilters(false);
                });
            }

            if (search && form) {
                search.addEventListener('compositionstart', function () { composing = true; });
                search.addEventListener('compositionend', function () {
                    composing = false;
                    clearTimeout(timer);
                    timer = setTimeout(function () { applyReportFilters(false); }, 280);
                });
                search.addEventListener('input', function () {
                    if (composing) return;
                    clearTimeout(timer);
                    timer = setTimeout(function () { applyReportFilters(false); }, 280);
                });
            }
        })();
    </script>
</x-app-layout>
