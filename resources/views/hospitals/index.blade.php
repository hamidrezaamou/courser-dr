<x-app-layout>
    <x-slot name="header">
        <div class="page-header--compact flex items-center justify-between gap-3">
            <h2 class="page-title">بیمارستان‌ها</h2>
            <a href="{{ route('dashboard') }}" class="btn-ghost hidden sm:inline-flex">داشبورد</a>
        </div>
    </x-slot>

    <div class="settings-page-body">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            <x-settings-dock :settings-section="'hospitals'" />

            <x-flash />

            <div class="panel overflow-hidden p-0 hospitals-hero"
                 style="background: linear-gradient(135deg, var(--brand-dark), var(--brand)); color: #fff;">
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 sm:gap-4 sm:px-7 sm:py-6">
                    <div class="flex items-center gap-3 sm:gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25 sm:h-12 sm:w-12">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15v18h-15V3zM9 7.5h1.5M9 11.25h1.5M9 15h1.5M13.5 7.5H15M13.5 11.25H15M13.5 15H15" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold sm:text-lg">مراکز ثبت‌شده</h3>
                            <p class="mt-0.5 hidden text-sm text-white/85 sm:block">نام، آدرس و تلفن در ثبت نوبت عمل و قالب‌های پرینت استفاده می‌شود.</p>
                        </div>
                    </div>
                    <div class="rounded-2xl bg-white/15 px-4 py-2 text-center ring-1 ring-white/20 sm:px-5 sm:py-3">
                        <div class="text-xl font-extrabold leading-none sm:text-2xl">{{ $hospitals->count() }}</div>
                        <div class="mt-1 text-[10px] text-white/90 sm:text-xs">مرکز</div>
                    </div>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-1 xl:grid-cols-[380px_minmax(0,1fr)]">
                <section class="panel p-5 sm:p-6">
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-bold" style="color: var(--ink);">
                        <svg class="h-4 w-4" style="color: var(--brand);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        افزودن بیمارستان جدید
                    </h3>
                    <form method="POST" action="{{ route('hospitals.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="name" value="نام بیمارستان / مرکز جراحی" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autocomplete="off" placeholder="مثال: بیمارستان دی" />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>
                        <div>
                            <x-input-label for="address" value="آدرس (برای پرینت)" />
                            <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address')" autocomplete="off" placeholder="مثال: مشهد، بلوار …" />
                            <x-input-error class="mt-2" :messages="$errors->get('address')" />
                        </div>
                        <div>
                            <x-input-label for="phone" value="تلفن (برای پرینت)" />
                            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full ltr-data" dir="ltr" :value="old('phone')" autocomplete="off" placeholder="051-12345678" />
                            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                        </div>
                        <button type="submit" class="btn-primary w-full !py-3">ذخیره در سیستم</button>
                    </form>
                </section>

                <section class="panel overflow-hidden p-0">
                    <div class="flex items-center justify-between border-b px-5 py-4" style="border-color: var(--line);">
                        <h3 class="text-sm font-bold" style="color: var(--ink);">لیست مراکز طرف قرارداد</h3>
                        <span class="rounded-full px-3 py-1 text-xs font-bold" style="background: var(--brand-soft); color: var(--brand-dark);">
                            {{ $hospitals->count() }} مرکز
                        </span>
                    </div>

                    @if ($hospitals->isEmpty())
                        <div class="pp-empty">
                            هنوز هیچ بیمارستانی ثبت نشده است.<br>
                            از فرم کنار صفحه اولین مرکز را اضافه کنید.
                        </div>
                    @else
                        <div class="divide-y" style="border-color: var(--line);">
                            @foreach ($hospitals as $hospital)
                                <details class="group px-5 py-3">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <span class="inline-flex min-w-[2rem] shrink-0 items-center justify-center rounded-lg px-2 py-1 text-xs font-bold" style="background: var(--panel-soft); color: var(--muted);">
                                                {{ $hospital->id }}
                                            </span>
                                            <div class="min-w-0">
                                                <strong class="block truncate" style="color: var(--ink);">{{ $hospital->name }}</strong>
                                                @if($hospital->address || $hospital->phone)
                                                    <span class="mt-0.5 block truncate text-xs" style="color: var(--muted);">
                                                        {{ $hospital->address ?: '—' }}
                                                        @if($hospital->phone)
                                                            · <span dir="ltr">{{ $hospital->phone }}</span>
                                                        @endif
                                                    </span>
                                                @else
                                                    <span class="mt-0.5 block text-xs" style="color: var(--muted);">آدرس و تلفن ثبت نشده</span>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="shrink-0 text-xs" style="color: var(--muted);">{{ $hospital->surgery_appointments_count }} نوبت</span>
                                    </summary>
                                    <form method="POST" action="{{ route('hospitals.update', $hospital) }}" class="mt-3 space-y-2 border-t pt-3" style="border-color: var(--line);">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="name" value="{{ $hospital->name }}" class="field-input !py-2 text-sm" required placeholder="نام بیمارستان">
                                        <input type="text" name="address" value="{{ $hospital->address }}" class="field-input !py-2 text-sm" placeholder="آدرس">
                                        <input type="text" name="phone" value="{{ $hospital->phone }}" class="field-input !py-2 text-sm ltr-data" dir="ltr" placeholder="تلفن">
                                        <div class="flex flex-wrap gap-2">
                                            <button type="submit" class="btn-secondary !py-2 !text-xs">ذخیره</button>
                                        </div>
                                    </form>
                                    <form method="POST" action="{{ route('hospitals.destroy', $hospital) }}" class="mt-2" onsubmit="return confirm('آیا از حذف این بیمارستان مطمئن هستید؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-bold text-red-600">حذف</button>
                                    </form>
                                </details>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>

</x-app-layout>
