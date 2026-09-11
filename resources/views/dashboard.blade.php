<x-app-layout body-class="is-dashboard">
    <x-slot name="header">
        <div class="dash-header">
            <h2 class="dash-header__title">جستجوی بیمار</h2>
            <div class="dash-header__actions">
                <a href="{{ route('surgery-appointments.register') }}" class="btn-secondary btn-secondary--warm btn-primary--compact" onclick="window.open(this.href, 'surgery-register'); return false;">
                    ثبت عمل
                </a>
                <a href="{{ route('patients.create') }}" class="btn-primary btn-primary--compact">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span class="hidden sm:inline">ثبت بیمار</span>
                    <span class="sm:hidden">ثبت</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="dash-page"
         x-data="patientSearch(@js($search), @js(route('dashboard')))"
         x-init="boot()">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-flash class="fade-up mb-3" />

            <div class="dash-search panel fade-up">
                <label class="dash-search__wrap" :class="loading && 'is-loading'">
                    <svg class="dash-search__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                    </svg>
                    <input
                        type="search"
                        class="dash-search__input"
                        placeholder="نام، کد ملی یا موبایل..."
                        x-model="q"
                        @input.debounce.300ms="search()"
                        autocomplete="off"
                        enterkeyhint="search"
                    >
                    <button type="button" class="dash-search__clear" x-show="q.length > 0" x-cloak @click="clear()"><x-icon-close /></button>
                    <span class="dash-search__spinner" x-show="loading" x-cloak></span>
                </label>
            </div>

            <div class="patient-results panel fade-up-delay">
                <div class="patient-results__head">
                    <div class="patient-results__head-row">
                        <div class="patient-view-toggle" role="group" aria-label="نوع نمایش پرونده‌ها">
                            <button type="button"
                                    class="patient-view-toggle__btn"
                                    :class="viewMode === 'list' && 'is-active'"
                                    @click="setView('list')"
                                    aria-label="نمای لیست"
                                    title="نمای لیست">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm0 5.25h.007v.008H3.75V12zm0 5.25h.007v.008H3.75v-.008z" />
                                </svg>
                            </button>
                            <button type="button"
                                    class="patient-view-toggle__btn"
                                    :class="viewMode === 'cards' && 'is-active'"
                                    @click="setView('cards')"
                                    aria-label="نمای کارت"
                                    title="نمای کارت">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 8.25V6zM13.5 6A2.25 2.25 0 0115.75 3.75H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25a2.25 2.25 0 01-2.25-2.25v-2.25z" />
                                </svg>
                            </button>
                        </div>
                        <div id="patient-results-head" class="patient-results__head-main">
                            @include('dashboard.partials.results-head', ['patients' => $patients, 'search' => $search, 'stats' => $stats])
                        </div>
                    </div>
                </div>
                <div id="patient-results">
                    @include('dashboard.partials.results-body', ['patients' => $patients, 'search' => $search])
                </div>
            </div>
        </div>
    </div>

    <x-row-toolbox-modal />
    <x-answer-panel mobile="" patient-name="" />

    <script>
        function patientSearch(initialQ, searchUrl) {
            return {
                q: initialQ || '',
                loading: false,
                viewMode: 'cards',
                boot() {
                    try {
                        const saved = localStorage.getItem('patientListView');
                        if (saved === 'list' || saved === 'cards') this.viewMode = saved;
                    } catch (e) {}
                    this.applyView();
                    if ((this.q || '').trim()) this.search();
                },
                setView(mode) {
                    this.viewMode = mode === 'list' ? 'list' : 'cards';
                    try { localStorage.setItem('patientListView', this.viewMode); } catch (e) {}
                    this.applyView();
                },
                applyView() {
                    const root = document.getElementById('patient-results');
                    if (!root) return;
                    const cards = root.querySelector('.patient-view--cards');
                    const list = root.querySelector('.patient-view--list');
                    if (cards) cards.hidden = this.viewMode !== 'cards';
                    if (list) list.hidden = this.viewMode !== 'list';
                },
                async search() {
                    this.loading = true;
                    const url = new URL(searchUrl, window.location.origin);
                    const term = (this.q || '').trim();
                    if (term) {
                        url.searchParams.set('search', term);
                    } else {
                        url.searchParams.delete('search');
                    }
                    window.history.replaceState({}, '', url);

                    try {
                        const res = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html',
                            },
                        });
                        if (!res.ok) return;
                        const html = await res.text();
                        const temp = document.createElement('div');
                        temp.innerHTML = html;
                        const head = temp.querySelector('#patient-ajax-head');
                        const body = temp.querySelector('#patient-ajax-body');
                        const headTarget = document.getElementById('patient-results-head');
                        const bodyTarget = document.getElementById('patient-results');
                        if (head && headTarget) headTarget.innerHTML = head.innerHTML;
                        if (body && bodyTarget) bodyTarget.innerHTML = body.innerHTML;
                        this.applyView();
                    } finally {
                        this.loading = false;
                    }
                },
                clear() {
                    this.q = '';
                    this.search();
                },
            };
        }
    </script>
</x-app-layout>
