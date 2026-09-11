<nav
    x-data="{
        open: false,
        dark: document.documentElement.classList.contains('dark'),
        toggleTheme() {
            this.dark = !this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        }
    }"
    class="nav-shell"
>
    @php
        $roleLabel = match(Auth::user()->role) {
            'doctor' => 'پزشک',
            'assistant' => 'منشی / دستیار',
            'admin' => 'مدیر',
            'patient' => 'بیمار',
            default => Auth::user()->role,
        };
        $navQuickLinks = Auth::user()->role === 'patient'
            ? []
            : \App\Support\NavQuickLinks::all();
        $staffMessagesAvailable = \Illuminate\Support\Facades\Route::has('staff-messages.index')
            && \Illuminate\Support\Facades\Route::has('staff-messages.alert-summary');
        $msgUnread = Auth::user()->isStaff() && $staffMessagesAvailable
            ? \App\Support\StaffNoteAlerts::unreadCount(Auth::id())
            : 0;
    @endphp

    <div class="max-w-[90rem] mx-auto px-3 sm:px-6 lg:px-8">
        <div class="nav-shell__row flex justify-between">
            <div class="flex min-w-0 items-center gap-2 sm:gap-3 lg:gap-6">
                <a href="{{ auth()->user()->role === 'patient' ? route('my-profile') : route('dashboard') }}" class="brand-mark shrink-0">
                    <span class="brand-mark__icon">
                        <svg class="h-4 w-4 sm:h-5 sm:w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-4.35-7-10a4 4 0 017-2.65A4 4 0 0119 11c0 5.65-7 10-7 10z" />
                        </svg>
                    </span>
                    <span class="hidden md:block text-right">
                        <span class="brand-mark__title block">آرشیو بیمار</span>
                        <span class="brand-mark__subtitle block">پرونده الکترونیک</span>
                    </span>
                </a>

                <div class="nav-desktop-links hidden lg:flex items-center gap-0.5 xl:gap-1">
                    @if(auth()->user()->role === 'patient')
                        <a href="{{ route('my-profile') }}"
                           class="nav-link {{ request()->routeIs('my-profile') ? 'is-active' : '' }}">
                            پرونده من
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}"
                           class="nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                            داشبورد
                        </a>
                        <a href="{{ route('appointments.board') }}"
                           class="nav-link {{ request()->routeIs('appointments.board') ? 'is-active' : '' }}">
                            نوبت‌ها
                        </a>
                        @if(\App\Support\FeatureFlags::enabled('features.clinic_floor'))
                        <a href="{{ route('clinic.floor') }}"
                           class="nav-link {{ request()->routeIs('clinic.floor') ? 'is-active' : '' }}">
                            صف مطب
                        </a>
                        @endif
                        <a href="{{ route('reports.index') }}"
                           class="nav-link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}">
                            گزارشات
                        </a>
                        @if(\App\Support\PatientFollowUps::isAvailable())
                        <a href="{{ route('followups.index') }}"
                           class="nav-link {{ request()->routeIs('followups.index') || request()->routeIs('settings.follow-ups') ? 'is-active' : '' }}">
                            پیگیری
                            @php $fuBadge = \App\Support\PatientFollowUps::openDueCount(); @endphp
                            @if($fuBadge > 0)
                                <span class="ms-1 rounded-full px-1.5 text-[10px] font-extrabold" style="background: var(--brand-soft); color: var(--brand-dark);">{{ fa_digits($fuBadge) }}</span>
                            @endif
                        </a>
                        @endif
                        @if($staffMessagesAvailable)
                        <a href="{{ route('staff-messages.index') }}"
                           class="nav-link {{ request()->routeIs('staff-messages.*') ? 'is-active' : '' }}">
                            پیام‌ها
                            @if($msgUnread > 0)
                                <span class="ms-1 rounded-full px-1.5 text-[10px] font-extrabold" style="background: #dc2626; color: #fff;">{{ fa_digits($msgUnread) }}</span>
                            @endif
                        </a>
                        @endif
                        <a href="{{ route('surgery-appointments.register') }}"
                           class="nav-link {{ request()->routeIs('surgery-appointments.register*') ? 'is-active' : '' }}"
                           onclick="window.open(this.href, 'surgery-register'); return false;">
                            ثبت عمل
                        </a>
                        <a href="{{ route('appointments.register') }}"
                           class="nav-link {{ request()->routeIs('appointments.register*') ? 'is-active' : '' }}">
                            ثبت ویزیت
                        </a>
                        <a href="{{ route('patients.create') }}"
                           class="nav-link {{ request()->routeIs('patients.create') ? 'is-active' : '' }}">
                            ثبت بیمار
                        </a>
                        @if(auth()->user()->canAccessClinicSettings())
                            <a href="{{ route('settings.index') }}"
                               class="nav-link {{ request()->routeIs('settings.*', 'hospitals.*', 'drugs.*', 'surgery-types.*', 'times.*') ? 'is-active' : '' }}">
                                تنظیمات
                            </a>
                        @endif
                        @if(auth()->user()->canAccessModules() && \App\Support\ModuleRegistry::anyEnabled())
                            <a href="{{ route('modules.hub') }}"
                               class="nav-link {{ request()->routeIs('modules.*', 'admin.modules') ? 'is-active' : '' }}">
                                ماژول‌ها
                            </a>
                        @endif
                        @if(auth()->user()->canManageSettings())
                        <div class="nav-spark" x-data="{ open: false }" @keydown.escape.window="open = false">
                            <button
                                type="button"
                                class="nav-spark__btn"
                                :class="open && 'is-open'"
                                @click="open = ! open"
                                :aria-expanded="open.toString()"
                                aria-haspopup="true"
                            >
                                <svg class="nav-spark__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 3.2l1.6 4.4 4.6.2-3.6 2.9 1.2 4.4L12 12.8 8.2 15.1l1.2-4.4-3.6-2.9 4.6-.2L12 3.2z" fill="currentColor" opacity=".95"/>
                                    <path d="M18.2 4.4l.55 1.5 1.55.07-1.22.98.4 1.5-1.28-.78-1.28.78.4-1.5-1.22-.98 1.55-.07.55-1.5z" fill="currentColor" opacity=".7"/>
                                </svg>
                                <span>ویژه</span>
                            </button>
                            <div
                                x-show="open"
                                x-cloak
                                x-transition.origin.top.right
                                @click.outside="open = false"
                                class="nav-spark__panel"
                                role="menu"
                            >
                                <div class="nav-spark__head">میانبرهای ویژه</div>
                                <a href="{{ route('prints.index') }}" class="nav-spark__link" role="menuitem">پرینت‌ها</a>
                                @if(\App\Support\FeatureFlags::enabled('features.ready_answers'))
                                <a href="{{ route('ready-answers.index') }}" class="nav-spark__link" role="menuitem">پاسخ‌های آماده</a>
                                @endif
                                @forelse($navQuickLinks as $link)
                                    <a
                                        href="{{ $link['url'] }}"
                                        class="nav-spark__link"
                                        role="menuitem"
                                        @if(\App\Support\NavQuickLinks::isExternal($link['url'])) target="_blank" rel="noopener noreferrer" @endif
                                    >
                                        <span>{{ $link['title'] }}</span>
                                        @if(\App\Support\NavQuickLinks::isExternal($link['url']))
                                            <svg class="nav-spark__ext" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"/><path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"/></svg>
                                        @endif
                                    </a>
                                @empty
                                    <p class="nav-spark__empty">هنوز لینکی ثبت نشده است.</p>
                                    <a href="{{ route('admin.settings.quick-links') }}" class="nav-spark__link nav-spark__link--setup" role="menuitem">
                                        تنظیم لینک‌ها در مدیریت
                                    </a>
                                @endforelse
                            </div>
                        </div>
                        @endif
                    @endif
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                @if(auth()->user()->isStaff() && $staffMessagesAvailable)
                    <div
                        x-data="{
                            latest: 0,
                            booted: false,
                            toast: '',
                            async refresh() {
                                try {
                                    const r = await fetch(@js(route('staff-messages.alert-summary')), {
                                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                        credentials: 'same-origin'
                                    });
                                    if (!r.ok) return;
                                    const d = await r.json();
                                    const next = Number(d.latest_id || 0);
                                    const count = Number(d.unread || 0);
                                    if (this.booted && next > this.latest && count > 0) {
                                        this.toast = (d.actor_name || 'همکار') + ' برای ' + (d.patient_name || 'بیمار') + ' پیام گذاشت';
                                        window.setTimeout(() => { this.toast = ''; }, 8000);
                                    }
                                    this.booted = true;
                                    this.latest = next;
                                } catch (e) {}
                            }
                        }"
                        x-init="refresh(); setInterval(() => refresh(), 40000)"
                    >
                        <a
                            href="{{ route('staff-messages.index') }}"
                            class="msg-live"
                            x-show="toast"
                            x-cloak
                            x-transition
                            x-text="toast"
                        ></a>
                    </div>
                @endif
                <button
                    type="button"
                    class="pp-theme-btn nav-icon-btn"
                    @click="toggleTheme()"
                    :title="dark ? 'حالت روشن' : 'حالت تاریک'"
                    aria-label="تغییر تم"
                >
                    <svg x-show="!dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                    </svg>
                    <svg x-show="dark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                    </svg>
                </button>

                <div class="hidden lg:block rounded-xl border px-3 py-1.5 text-left max-w-[10rem]" style="border-color: var(--line); background: var(--panel-soft);">
                    <div class="truncate text-sm font-semibold" style="color: var(--ink);">{{ Auth::user()->name }}</div>
                    <div class="truncate text-[11px]" style="color: var(--muted);">{{ $roleLabel }}</div>
                </div>

                <div class="hidden lg:block">
                    <x-dropdown align="left" width="48">
                        <x-slot name="trigger">
                            <button class="{{ auth()->user()->canManageSettings() ? 'btn-primary btn-primary--compact' : 'btn-ghost !px-3' }}">
                                {{ auth()->user()->canManageSettings() ? 'مدیریت کل سایت' : 'حساب' }}
                                <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            @if(auth()->user()->canManageSettings())
                                <x-dropdown-link :href="route('admin.index')">
                                    نمای کلی مدیریت
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.users.index')">
                                    کاربران و نقش‌ها
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.settings.communications')">
                                    ارتباطات
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.settings.brand')">
                                    برند و چاپ
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.settings.system')">
                                    سامانه / بک‌آپ
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.settings.features')">
                                    قابلیت‌ها
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.settings.support')">
                                    پشتیبانی
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.settings.quick-links')">
                                    لینک‌های ویژه هدر
                                </x-dropdown-link>
                                @if(auth()->user()->canAccessModules() && \App\Support\ModuleRegistry::anyEnabled())
                                <x-dropdown-link :href="route('modules.hub')">
                                    ماژول‌های پیشرفته
                                </x-dropdown-link>
                                @endif
                            @endif
                            <x-dropdown-link :href="route('prints.index')">
                                پرینت‌ها
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('profile.edit')">
                                پروفایل
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    خروج
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>

                <div class="lg:hidden">
                    <button
                        type="button"
                        @click="open = ! open"
                        class="nav-icon-btn"
                        style="color: var(--muted); background: var(--panel-soft);"
                        aria-label="منو"
                    >
                        <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden lg:hidden border-t" style="border-color: var(--line); background: var(--panel);">
        <div class="max-h-[min(88dvh,40rem)] overflow-y-auto" x-ref="mobileMenuScroll">
        <div class="pt-2 pb-3 space-y-1 px-3">
            @if(auth()->user()->role === 'patient')
                <x-responsive-nav-link :href="route('my-profile')" :active="request()->routeIs('my-profile')">
                    پرونده من
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    داشبورد
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('appointments.board')" :active="request()->routeIs('appointments.board')">
                    نوبت‌ها
                </x-responsive-nav-link>
                @if(\App\Support\FeatureFlags::enabled('features.clinic_floor'))
                <x-responsive-nav-link :href="route('clinic.floor')" :active="request()->routeIs('clinic.floor')">
                    صف مطب
                </x-responsive-nav-link>
                @endif
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                    گزارشات
                </x-responsive-nav-link>
                @if(\App\Support\PatientFollowUps::isAvailable())
                <x-responsive-nav-link :href="route('followups.index')" :active="request()->routeIs('followups.index') || request()->routeIs('settings.follow-ups')">
                    پیگیری
                </x-responsive-nav-link>
                @endif
                @if($staffMessagesAvailable)
                    <x-responsive-nav-link :href="route('staff-messages.index')" :active="request()->routeIs('staff-messages.*')">
                        پیام‌های پرونده
                        @if($msgUnread > 0)
                            <span class="ms-1 rounded-full px-1.5 text-[10px] font-extrabold" style="background: #dc2626; color: #fff;">{{ fa_digits($msgUnread) }}</span>
                        @endif
                    </x-responsive-nav-link>
                @endif
                <x-responsive-nav-link :href="route('prints.index')" :active="request()->routeIs('prints.*') || request()->routeIs('surgery-appointments.prints*')">
                    پرینت‌ها
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('surgery-appointments.register')" :active="request()->routeIs('surgery-appointments.register*')" onclick="window.open(this.href, 'surgery-register'); return false;">
                    ثبت عمل
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('appointments.register')" :active="request()->routeIs('appointments.register*')">
                    ثبت ویزیت
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('patients.create')" :active="request()->routeIs('patients.create')">
                    ثبت بیمار
                </x-responsive-nav-link>
                @if(auth()->user()->canAccessClinicSettings())
                    <x-responsive-nav-link :href="route('settings.index')" :active="request()->routeIs('settings.*', 'hospitals.*', 'drugs.*', 'surgery-types.*', 'times.*')">
                        تنظیمات
                    </x-responsive-nav-link>
                @endif
                @if(auth()->user()->canAccessModules() && \App\Support\ModuleRegistry::anyEnabled())
                    <x-responsive-nav-link :href="route('modules.hub')" :active="request()->routeIs('modules.*', 'admin.modules')">
                        ماژول‌ها
                    </x-responsive-nav-link>
                @endif
                @if(auth()->user()->canManageSettings())
                <div class="nav-spark nav-spark--mobile px-1 pt-1" x-data="{ open: false }">
                    <button type="button" class="nav-spark__btn nav-spark__btn--block" @click="open = ! open; if (open) { $nextTick(() => { $refs.sparkList && $refs.sparkList.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); }) }" :aria-expanded="open.toString()">
                        <svg class="nav-spark__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 3.2l1.6 4.4 4.6.2-3.6 2.9 1.2 4.4L12 12.8 8.2 15.1l1.2-4.4-3.6-2.9 4.6-.2L12 3.2z" fill="currentColor" opacity=".95"/>
                        </svg>
                        <span>ویژه</span>
                    </button>
                    <div x-show="open" x-cloak x-ref="sparkList" x-transition class="nav-spark__mobile-list">
                        <a href="{{ route('prints.index') }}" class="nav-spark__link">پرینت‌ها</a>
                        @if(\App\Support\FeatureFlags::enabled('features.ready_answers'))
                        <a href="{{ route('ready-answers.index') }}" class="nav-spark__link">پاسخ‌های آماده</a>
                        @endif
                        @forelse($navQuickLinks as $link)
                            <a
                                href="{{ $link['url'] }}"
                                class="nav-spark__link"
                                @if(\App\Support\NavQuickLinks::isExternal($link['url'])) target="_blank" rel="noopener noreferrer" @endif
                            >{{ $link['title'] }}</a>
                        @empty
                            <p class="nav-spark__empty">هنوز لینکی ثبت نشده است.</p>
                            <a href="{{ route('admin.settings.quick-links') }}" class="nav-spark__link nav-spark__link--setup">تنظیم لینک‌ها</a>
                        @endforelse
                    </div>
                </div>
                @endif
            @endif
        </div>

        <div class="pt-4 pb-4 border-t" style="border-color: var(--line);">
            <div class="px-4">
                <div class="font-semibold text-base" style="color: var(--ink);">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm" style="color: var(--muted);">{{ $roleLabel }}</div>
            </div>

            <div class="mt-3 space-y-1 px-3">
                @if(auth()->user()->canManageSettings())
                    <x-responsive-nav-link :href="route('admin.index')" :active="request()->routeIs('admin.*')">
                        مدیریت کل سایت
                    </x-responsive-nav-link>
                @endif
                <x-responsive-nav-link :href="route('profile.edit')">
                    پروفایل
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        خروج
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
        </div>
    </div>
</nav>
