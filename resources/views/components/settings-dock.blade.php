{{-- ناوبری تنظیمات: تب بالای صفحه، مثل بقیه بخش‌ها --}}
@php
    $section = $settingsSection ?? 'times';
@endphp

<nav class="settings-dock" aria-label="منوی تنظیمات">
    <a href="{{ route('settings.times') }}" class="settings-dock__btn {{ $section === 'times' ? 'is-active' : '' }}">
        <svg class="settings-dock__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
        <span class="settings-dock__label">تایم‌ها</span>
    </a>
    <a href="{{ route('settings.hospitals') }}" class="settings-dock__btn {{ $section === 'hospitals' ? 'is-active' : '' }}">
        <svg class="settings-dock__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M6 21V5a1 1 0 011-1h10a1 1 0 011 1v16M9 8h.01M12 8h.01M15 8h.01M9 12h.01M12 12h.01M15 12h.01M9 16h.01M12 16h.01M15 16h.01"/></svg>
        <span class="settings-dock__label">بیمارستان</span>
    </a>
    <a href="{{ route('settings.surgery-types') }}" class="settings-dock__btn {{ $section === 'surgery-types' ? 'is-active' : '' }}">
        <svg class="settings-dock__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
        <span class="settings-dock__label">انواع عمل</span>
    </a>
    <a href="{{ route('settings.surgery-program-groups') }}" class="settings-dock__btn {{ $section === 'program-groups' ? 'is-active' : '' }}">
        <svg class="settings-dock__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="7" height="7" rx="1.5"/><rect x="14" y="4" width="7" height="7" rx="1.5"/><rect x="3" y="15" width="7" height="7" rx="1.5"/><path stroke-linecap="round" d="M17 15v7M14 18h7"/></svg>
        <span class="settings-dock__label">برنامه‌ها</span>
    </a>
    @if(\App\Support\PatientFollowUps::isAvailable())
    <a href="{{ route('settings.follow-ups') }}" class="settings-dock__btn {{ $section === 'follow-ups' ? 'is-active' : '' }}">
        <svg class="settings-dock__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span class="settings-dock__label">پیگیری</span>
    </a>
    @endif
    <a href="{{ route('settings.contacts') }}" class="settings-dock__btn {{ $section === 'contacts' ? 'is-active' : '' }}">
        <svg class="settings-dock__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
        <span class="settings-dock__label">مخاطبین</span>
    </a>
    <a href="{{ route('settings.drugs') }}" class="settings-dock__btn {{ $section === 'drugs' ? 'is-active' : '' }}">
        <svg class="settings-dock__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
        <span class="settings-dock__label">داروها</span>
    </a>
    @if(auth()->user()?->canManageSettings())
        <a href="{{ route('settings.patients') }}" class="settings-dock__btn {{ $section === 'patients' ? 'is-active' : '' }}">
            <svg class="settings-dock__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="settings-dock__label">بیماران</span>
        </a>
    @endif
</nav>
