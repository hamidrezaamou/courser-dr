@php
    $featuredKeys = \App\Support\FeatureFlags::featuredKeys();
    $advancedFeatured = \App\Support\FeatureFlags::featuredKeysForGroup('advanced');
    $groupOrder = \App\Support\FeatureFlags::groupOrder();
    $onCount = collect($values)->filter()->count();
    $totalCount = count($definitions);
    $modulesOn = count(\App\Support\ModuleRegistry::enabled());
@endphp

<x-app-layout body-class="is-admin">
    <x-slot name="header">
        <div class="admin-header">
            <div>
                <p class="admin-header__eyebrow">مدیریت کل سایت</p>
                <h2 class="admin-header__title">قابلیت‌های اختیاری</h2>
            </div>
            <div class="admin-header__actions">
                <a href="{{ route('admin.index') }}" class="btn-ghost !text-xs !px-3 !py-1.5">نمای کلی</a>
            </div>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-admin-dock :admin-section="'features'" />
            <x-flash />

            <form method="POST" action="{{ route('admin.settings.features.update') }}" class="mx-auto max-w-3xl space-y-4">
                @csrf
                @method('PUT')

                <section class="admin-feature-summary">
                    <div>
                        <h3 class="admin-feature-summary__title">سوئیچ ماژول‌ها</h3>
                        <p class="admin-feature-summary__hint">
                            بخش‌های کلینیک و عمومی آماده‌اند. ماژول‌های پیشرفته پیش‌فرض خاموش‌اند —
                            با روشن کردن هر کدام، منوی «ماژول‌ها» و صفحهٔ مربوطه فعال می‌شود.
                        </p>
                    </div>
                    <div class="admin-feature-summary__stats">
                        <span class="admin-feature-summary__pill is-on">{{ $onCount }} فعال</span>
                        <span class="admin-feature-summary__pill is-off">{{ $totalCount - $onCount }} خاموش</span>
                        @if($modulesOn > 0)
                            <span class="admin-feature-summary__pill is-on">{{ $modulesOn }} ماژول فعال</span>
                        @endif
                    </div>
                </section>

                @foreach ($featuredKeys as $key)
                    @if(isset($definitions[$key]))
                        <x-admin-feature-card
                            :feature-key="$key"
                            :meta="$definitions[$key]"
                            :enabled="$values[$key] ?? $definitions[$key]['default']"
                            :featured="true"
                        />
                    @endif
                @endforeach

                @foreach ($groupOrder as $groupKey)
                    @php
                        $groupLabel = $groups[$groupKey] ?? $groupKey;
                        $groupDefs = collect($definitions)->filter(function ($meta, $key) use ($groupKey, $featuredKeys, $advancedFeatured) {
                            if (($meta['group'] ?? 'general') !== $groupKey) {
                                return false;
                            }
                            if (in_array($key, $featuredKeys, true)) {
                                return false;
                            }
                            if ($groupKey === 'advanced' && in_array($key, $advancedFeatured, true)) {
                                return false;
                            }

                            return true;
                        });
                        $isAdvanced = $groupKey === 'advanced';
                    @endphp

                    @if($groupDefs->isNotEmpty() || ($isAdvanced && $advancedFeatured !== []))
                        <section @class([
                            'admin-panel admin-feature-group',
                            'admin-feature-group--advanced' => $isAdvanced,
                        ])>
                            @if($isAdvanced)
                                <div class="admin-feature-group__intro">
                                    <div>
                                        <h3 class="admin-panel__title admin-feature-group__title">{{ $groupLabel }}</h3>
                                        <p class="admin-feature-group__lead">
                                            ماژول‌های اضافه برای مطب‌های بزرگ‌تر. خاموش = همان پنل فعلی.
                                            روشن = آماده برای فعال‌سازی در به‌روزرسانی بعد.
                                        </p>
                                    </div>
                                    <span class="admin-feature-group__tag">اختیاری</span>
                                </div>

                                @foreach ($advancedFeatured as $key)
                                    @if(isset($definitions[$key]))
                                        <x-admin-feature-card
                                            :feature-key="$key"
                                            :meta="$definitions[$key]"
                                            :enabled="$values[$key] ?? $definitions[$key]['default']"
                                            :featured="true"
                                        />
                                    @endif
                                @endforeach

                                @if($groupDefs->isNotEmpty())
                                    <div class="admin-feature-grid {{ $advancedFeatured !== [] ? 'mt-3' : '' }}">
                                        @foreach ($groupDefs as $key => $meta)
                                            <x-admin-feature-card
                                                :feature-key="$key"
                                                :meta="$meta"
                                                :enabled="$values[$key] ?? $meta['default']"
                                            />
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                <h3 class="admin-panel__title admin-feature-group__title">{{ $groupLabel }}</h3>
                                <div class="admin-feature-grid">
                                    @foreach ($groupDefs as $key => $meta)
                                        <x-admin-feature-card
                                            :feature-key="$key"
                                            :meta="$meta"
                                            :enabled="$values[$key] ?? $meta['default']"
                                        />
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    @endif
                @endforeach

                <div class="admin-feature-actions">
                    <button type="submit" class="btn-primary">ذخیره قابلیت‌ها</button>
                    <a href="{{ route('admin.settings.system') }}" class="btn-ghost !text-sm">وضعیت سامانه</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
