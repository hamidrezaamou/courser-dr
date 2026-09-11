@php
    $bucket = $bucket ?? 'today';
    $counts = $counts ?? [];
    $kinds = $kinds ?? [];
    $methods = $methods ?? [];
    $outcomes = $outcomes ?? [];
    $staff = $staff ?? collect();
    $hospitals = $hospitals ?? collect();
    $surgeryTypes = $surgeryTypes ?? collect();
    $bucketUrl = function (string $key) {
        return route('followups.index', array_merge(request()->except(['page', 'bucket']), ['bucket' => $key]));
    };
    $filterKeys = ['q', 'hospital_id', 'kind', 'method', 'assigned_to', 'surgery_type_id', 'status', 'done', 'source', 'from', 'to'];
    $activeFilterCount = collect($filterKeys)->filter(fn ($key) => filled(request($key)))->count();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="page-title">پیگیری بیماران</h2>
            <div class="flex flex-wrap gap-2">
                @if(auth()->user()?->canAccessClinicSettings())
                    <a href="{{ route('settings.follow-ups') }}" class="btn-ghost !py-1.5 !text-xs">الگوها و تنظیمات</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div
        class="py-4 sm:py-8"
        x-data="{ filtersOpen: false }"
        x-init="if (window.OverlayHistory) window.OverlayHistory.bindWatch($data, 'filtersOpen', 'followup-filters')"
    >
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <div class="report-chips report-no-print">
                <a href="{{ $bucketUrl('due') }}" class="report-chip {{ $bucket === 'due' ? 'is-active' : '' }}">باید پیگیری شوند {{ fa_digits($counts['due'] ?? 0) }}</a>
                <a href="{{ $bucketUrl('today') }}" class="report-chip {{ $bucket === 'today' ? 'is-active' : '' }}">امروز {{ fa_digits($counts['today'] ?? 0) }}</a>
                <a href="{{ $bucketUrl('overdue') }}" class="report-chip {{ $bucket === 'overdue' ? 'is-active' : '' }}">عقب‌افتاده {{ fa_digits($counts['overdue'] ?? 0) }}</a>
                <a href="{{ $bucketUrl('upcoming') }}" class="report-chip {{ $bucket === 'upcoming' ? 'is-active' : '' }}">آینده {{ fa_digits($counts['upcoming'] ?? 0) }}</a>
                <a href="{{ $bucketUrl('in_progress') }}" class="report-chip {{ $bucket === 'in_progress' ? 'is-active' : '' }}">در حال انجام {{ fa_digits($counts['in_progress'] ?? 0) }}</a>
                <a href="{{ $bucketUrl('done') }}" class="report-chip {{ $bucket === 'done' ? 'is-active' : '' }}">انجام‌شده امروز {{ fa_digits($counts['done'] ?? 0) }}</a>
                <a href="{{ $bucketUrl('all') }}" class="report-chip {{ $bucket === 'all' ? 'is-active' : '' }}">همه</a>
                <button type="button" class="report-chip report-chip--sheet {{ $activeFilterCount ? 'is-active' : '' }}" @click="filtersOpen = true">
                    فیلترها@if($activeFilterCount) {{ fa_digits($activeFilterCount) }}@endif
                </button>
                @if($activeFilterCount)
                    <a href="{{ route('followups.index', ['bucket' => $bucket]) }}" class="report-chip report-chip--ghost">حذف فیلتر</a>
                @endif
            </div>

            <form method="GET" action="{{ route('followups.index') }}" class="panel p-4 report-no-print hidden sm:block">
                <input type="hidden" name="bucket" value="{{ $bucket }}">
                <div class="report-filters__inline">
                    <label class="report-filters__field">
                        <span>جستجو</span>
                        <input type="text" name="q" value="{{ request('q') }}" class="field-input" placeholder="نام / موبایل / کد ملی">
                    </label>
                    <label class="report-filters__field">
                        <span>بیمارستان</span>
                        <select name="hospital_id" class="field-input">
                            <option value="">همه بیمارستان‌ها</option>
                            @foreach($hospitals as $hospital)
                                <option value="{{ $hospital->id }}" @selected((string) request('hospital_id') === (string) $hospital->id)>{{ $hospital->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="report-filters__field">
                        <span>نوع پیگیری</span>
                        <select name="kind" class="field-input">
                            <option value="">همه</option>
                            @foreach($kinds as $slug => $label)
                                <option value="{{ $slug }}" @selected(request('kind') === $slug)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="report-filters__field">
                        <span>روش</span>
                        <select name="method" class="field-input">
                            <option value="">همه</option>
                            @foreach($methods as $slug => $label)
                                <option value="{{ $slug }}" @selected(request('method') === $slug)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="report-filters__field">
                        <span>مسئول</span>
                        <select name="assigned_to" class="field-input">
                            <option value="">همه</option>
                            <option value="me" @selected(request('assigned_to') === 'me')>من</option>
                            <option value="none" @selected(request('assigned_to') === 'none')>بدون مسئول</option>
                            @foreach($staff as $user)
                                <option value="{{ $user->id }}" @selected((string) request('assigned_to') === (string) $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="report-filters__field">
                        <span>نوع عمل</span>
                        <select name="surgery_type_id" class="field-input">
                            <option value="">همه</option>
                            @foreach($surgeryTypes as $type)
                                <option value="{{ $type->id }}" @selected((string) request('surgery_type_id') === (string) $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="report-filters__field">
                        <span>وضعیت</span>
                        <select name="status" class="field-input">
                            <option value="">همه</option>
                            @foreach(\App\Support\FollowUpStatus::all() as $st)
                                <option value="{{ $st }}" @selected(request('status') === $st)>{{ \App\Support\FollowUpStatus::label($st) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="report-filters__field">
                        <span>انجام</span>
                        <select name="done" class="field-input">
                            <option value="">همه</option>
                            <option value="0" @selected(request('done') === '0')>انجام‌نشده</option>
                            <option value="1" @selected(request('done') === '1')>انجام‌شده</option>
                        </select>
                    </label>
                    <label class="report-filters__field">
                        <span>منبع</span>
                        <select name="source" class="field-input">
                            <option value="">همه</option>
                            <option value="template" @selected(request('source') === 'template')>از الگو</option>
                            <option value="reminder" @selected(request('source') === 'reminder')>یادآوری مراجعه</option>
                            <option value="manual" @selected(request('source') === 'manual')>دستی</option>
                            <option value="retry" @selected(request('source') === 'retry')>پیگیری مجدد</option>
                        </select>
                    </label>
                    <div class="report-filters__field report-filters__field--date">
                        <x-jalali-date-input name="from" :value="request('from')" label="از تاریخ" :required="false" placeholder="از تاریخ" />
                    </div>
                    <div class="report-filters__field report-filters__field--date">
                        <x-jalali-date-input name="to" :value="request('to')" label="تا تاریخ" :required="false" placeholder="تا تاریخ" />
                    </div>
                    <div class="report-filters__field" style="justify-content:end">
                        <span class="opacity-0 pointer-events-none">اعمال</span>
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="btn-primary !py-1.5 !text-xs">اعمال فیلتر</button>
                            <a href="{{ route('followups.index', ['bucket' => $bucket]) }}" class="btn-secondary !py-1.5 !text-xs">پاک کردن</a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="filter-sheet sm:hidden" x-show="filtersOpen" x-cloak @click.self="filtersOpen = false">
                <div class="filter-sheet__panel">
                    <div class="filter-sheet__head">
                        <h3>فیلتر پیگیری</h3>
                        <button type="button" class="tg-tool" @click="filtersOpen = false" aria-label="بستن"><x-icon-close /></button>
                    </div>
                    <form method="GET" action="{{ route('followups.index') }}" class="space-y-3 report-filter-compact">
                        <input type="hidden" name="bucket" value="{{ $bucket }}">
                        <div>
                            <x-input-label value="جستجو" />
                            <input type="text" name="q" value="{{ request('q') }}" class="field-input mt-1 !min-h-[2.25rem] !py-1.5 text-sm" placeholder="نام / موبایل / کد ملی">
                        </div>
                        <div>
                            <x-input-label value="بیمارستان" />
                            <select name="hospital_id" class="field-input mt-1 !min-h-[2.25rem] !py-1.5 text-sm">
                                <option value="">همه بیمارستان‌ها</option>
                                @foreach($hospitals as $hospital)
                                    <option value="{{ $hospital->id }}" @selected((string) request('hospital_id') === (string) $hospital->id)>{{ $hospital->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label value="نوع پیگیری" />
                            <select name="kind" class="field-input mt-1 !min-h-[2.25rem] !py-1.5 text-sm">
                                <option value="">همه</option>
                                @foreach($kinds as $slug => $label)
                                    <option value="{{ $slug }}" @selected(request('kind') === $slug)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label value="روش" />
                            <select name="method" class="field-input mt-1 !min-h-[2.25rem] !py-1.5 text-sm">
                                <option value="">همه</option>
                                @foreach($methods as $slug => $label)
                                    <option value="{{ $slug }}" @selected(request('method') === $slug)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label value="مسئول" />
                            <select name="assigned_to" class="field-input mt-1 !min-h-[2.25rem] !py-1.5 text-sm">
                                <option value="">همه</option>
                                <option value="me" @selected(request('assigned_to') === 'me')>من</option>
                                <option value="none" @selected(request('assigned_to') === 'none')>بدون مسئول</option>
                                @foreach($staff as $user)
                                    <option value="{{ $user->id }}" @selected((string) request('assigned_to') === (string) $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label value="نوع عمل" />
                            <select name="surgery_type_id" class="field-input mt-1 !min-h-[2.25rem] !py-1.5 text-sm">
                                <option value="">همه</option>
                                @foreach($surgeryTypes as $type)
                                    <option value="{{ $type->id }}" @selected((string) request('surgery_type_id') === (string) $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label value="وضعیت" />
                            <select name="status" class="field-input mt-1 !min-h-[2.25rem] !py-1.5 text-sm">
                                <option value="">همه</option>
                                @foreach(\App\Support\FollowUpStatus::all() as $st)
                                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ \App\Support\FollowUpStatus::label($st) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label value="انجام" />
                            <select name="done" class="field-input mt-1 !min-h-[2.25rem] !py-1.5 text-sm">
                                <option value="">همه</option>
                                <option value="0" @selected(request('done') === '0')>انجام‌نشده</option>
                                <option value="1" @selected(request('done') === '1')>انجام‌شده</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label value="منبع" />
                            <select name="source" class="field-input mt-1 !min-h-[2.25rem] !py-1.5 text-sm">
                                <option value="">همه</option>
                                <option value="template" @selected(request('source') === 'template')>از الگو</option>
                                <option value="reminder" @selected(request('source') === 'reminder')>یادآوری مراجعه</option>
                                <option value="manual" @selected(request('source') === 'manual')>دستی</option>
                                <option value="retry" @selected(request('source') === 'retry')>پیگیری مجدد</option>
                            </select>
                        </div>
                        <x-jalali-date-input name="from" :value="request('from')" label="از تاریخ" :required="false" class="!min-h-[2.25rem] !py-1.5 text-sm" />
                        <x-jalali-date-input name="to" :value="request('to')" label="تا تاریخ" :required="false" class="!min-h-[2.25rem] !py-1.5 text-sm" />
                        <div class="flex gap-2 pt-1">
                            <button type="submit" class="btn-primary flex-1 !py-2 !text-xs">بستن و اعمال</button>
                            <a href="{{ route('followups.index', ['bucket' => $bucket]) }}" class="btn-ghost !py-2 !text-xs">حذف</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="space-y-2">
                @forelse($followUps as $item)
                    @include('follow-ups.partials.card', [
                        'item' => $item,
                        'kinds' => $kinds,
                        'methods' => $methods,
                        'outcomes' => $outcomes,
                        'staff' => $staff,
                    ])
                @empty
                    <div class="panel p-6 text-sm" style="color: var(--muted);">پیگیری‌ای در این نما نیست.</div>
                @endforelse

                <x-list-pager :paginator="$followUps" label="پیگیری" />
            </div>
        </div>
    </div>

    <x-row-toolbox-modal />
    <x-answer-panel mobile="" patient-name="" />
</x-app-layout>
