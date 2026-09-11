<x-app-layout>
    <x-slot name="header">
        <div class="page-header--compact flex items-center justify-between gap-3">
            <h2 class="page-title">حساب کاربری</h2>
            <a href="{{ route('dashboard') }}" class="btn-ghost hidden sm:inline-flex">داشبورد</a>
        </div>
    </x-slot>

    <div class="settings-page-body">
        <div class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">
            <div class="panel overflow-hidden p-0"
                 style="background: linear-gradient(135deg, var(--brand-dark), var(--brand)); color: #fff;">
                <div class="flex flex-wrap items-center gap-3 px-4 py-4 sm:gap-4 sm:px-7 sm:py-6">
                    <x-profile-photo
                        class="pp-photo--user"
                        :url="$user->photoUrl()"
                        :initial="$user->photoInitial()"
                        :upload-url="route('profile.photo.update')"
                        :delete-url="route('profile.photo.destroy')"
                        :can-edit="true"
                        title="عکس پروفایل"
                    />
                    <div class="min-w-0">
                        <h3 class="truncate text-base font-extrabold sm:text-lg">{{ $user->name }}</h3>
                        <p class="mt-0.5 truncate text-xs text-white/85 sm:text-sm" dir="ltr">{{ $user->email }}</p>
                    </div>
                </div>
            </div>

            <div class="panel p-5 sm:p-6">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="panel p-5 sm:p-6">
                @include('profile.partials.update-password-form')
            </div>

            <div class="panel p-5 sm:p-6">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
