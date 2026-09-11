<x-app-layout>
    <x-slot name="header">
        <div class="page-header--compact flex items-center justify-between gap-3">
            <h2 class="page-title">انواع عمل</h2>
            <a href="{{ route('settings.times', ['kind' => 'surgery']) }}" class="btn-ghost hidden sm:inline-flex">تایم‌ها</a>
        </div>
    </x-slot>

    <div class="settings-page-body">
        <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
            <x-settings-dock :settings-section="'surgery-types'" />

            <p class="text-sm" style="color: var(--muted);">
                برای ظرفیت مستقل چند نوع عمل،
                <a href="{{ route('settings.surgery-program-groups') }}" class="font-bold underline" style="color: var(--brand-dark);">گروه برنامه نوبت</a>
                بسازید.
            </p>

            <x-flash />

            <div class="grid gap-5 lg:grid-cols-1 xl:grid-cols-[340px_minmax(0,1fr)]">
                <section class="panel p-5">
                    <h3 class="mb-4 text-sm font-bold" style="color: var(--ink);">افزودن نوع عمل</h3>
                    <form method="POST" action="{{ route('surgery-types.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="name" value="نام نوع عمل" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required placeholder="مثال: آب مروارید" />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>
                        <button type="submit" class="btn-primary w-full !py-3">ذخیره نوع</button>
                    </form>
                </section>

                <section class="panel overflow-hidden p-0">
                    <div class="flex items-center justify-between border-b px-5 py-4" style="border-color: var(--line);">
                        <h3 class="text-sm font-bold" style="color: var(--ink);">لیست انواع و زیرگروه‌ها</h3>
                        <span class="rounded-full px-3 py-1 text-xs font-bold" style="background: var(--brand-soft); color: var(--brand-dark);">
                            {{ $types->count() }} نوع
                        </span>
                    </div>

                    @if ($types->isEmpty())
                        <div class="pp-empty">هنوز نوعی ثبت نشده.</div>
                    @else
                        <div class="divide-y" style="border-color: var(--line);">
                            @foreach ($types as $type)
                                <div class="space-y-3 p-4" x-data="{ typeLockOpen: false }">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <form method="POST" action="{{ route('surgery-types.update', $type) }}" class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="name" value="{{ old('name', $type->name) }}" class="field-input min-w-0 flex-1" required>
                                            <label class="flex items-center gap-2 text-xs font-bold whitespace-nowrap" style="color: var(--muted);">
                                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $type->is_active))>
                                                فعال
                                            </label>
                                            <button type="submit" class="touch-action shrink-0" style="background:var(--panel-soft);color:var(--ink)">ذخیره</button>
                                        </form>
                                        <div class="flex flex-wrap items-center gap-2 sm:shrink-0">
                                            <button type="button" class="touch-action text-xs font-extrabold" style="background:color-mix(in srgb,#f59e0b 16%,var(--panel-soft));color:#92400e" @click="typeLockOpen = !typeLockOpen">
                                                @if($type->cooldown_days)
                                                    قفل کل نوع · هر {{ fa_digits($type->cooldown_days) }} روز
                                                @else
                                                    محدودیت کل نوع
                                                @endif
                                            </button>
                                            <form method="POST" action="{{ route('surgery-types.destroy', $type) }}" onsubmit="return confirm('حذف نوع و همه زیرگروه‌ها؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="touch-action w-full text-red-600 sm:w-auto" style="background:color-mix(in srgb, #fee2e2 80%, transparent)">حذف</button>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border p-3" style="border-color:color-mix(in srgb,#f59e0b 35%,var(--line));background:color-mix(in srgb,#fffbeb 70%,var(--panel))" x-show="typeLockOpen" x-cloak>
                                        <form method="POST" action="{{ route('surgery-types.cooldown.update', $type) }}" class="space-y-2">
                                            @csrf
                                            @method('PUT')
                                            <label class="block text-xs font-bold" style="color:var(--ink)">فاصله حداقل (روز) برای همه زیرگروه‌های «{{ $type->name }}»</label>
                                            <div class="flex gap-2">
                                                <input type="number" name="cooldown_days" min="0" max="3650" class="field-input" dir="ltr" inputmode="numeric" placeholder="بدون محدودیت" value="{{ $type->cooldown_days ?: '' }}">
                                                <button type="submit" class="btn-secondary shrink-0 !px-3">ذخیره</button>
                                            </div>
                                            <p class="text-[11px] leading-5" style="color:var(--muted)">اگر عدد بگذارید (مثلاً ۳۰)، همان چشم نباید زودتر از این مدت هیچ زیرگروهی از این عمل را بگیرد — مثلاً هر نوع تزریق با تزریق قبلی تداخل دارد. خالی یا ۰ یعنی قفل کل نوع برداشته می‌شود و می‌توانید روی تک‌زیرگروه محدودیت بگذارید.</p>
                                        </form>
                                    </div>

                                    <div class="rounded-xl border p-3" style="border-color:var(--line);background:var(--panel-soft)">
                                        <div class="mb-2 flex items-center justify-between gap-2">
                                            <p class="text-xs font-bold" style="color:var(--muted)">
                                                @if($type->cooldown_days)
                                                    زیرگروه‌ها — محدودیت روی کل نوع است (هر {{ fa_digits($type->cooldown_days) }} روز)
                                                @else
                                                    زیرگروه‌ها — برای محدودیت تک‌زیرگروه روی نام کلیک کنید
                                                @endif
                                            </p>
                                        </div>
                                        <ul class="mb-3 space-y-2">
                                            @forelse ($type->subtypes as $subtype)
                                                <li class="rounded-lg px-2 py-1.5 text-sm" style="background:var(--panel);color:var(--ink)" x-data="{ open: false }">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <button type="button" class="min-w-0 flex-1 cursor-pointer text-right font-bold" style="color:inherit" @click="open = !open">
                                                            <span>{{ $subtype->name }}</span>
                                                            @if($type->cooldown_days)
                                                                <span class="mr-2 inline-flex rounded-full px-2 py-0.5 text-[10px] font-extrabold" style="background:color-mix(in srgb,#f59e0b 18%,transparent);color:#92400e">
                                                                    از کل نوع
                                                                </span>
                                                            @elseif($subtype->cooldown_days)
                                                                <span class="mr-2 inline-flex rounded-full px-2 py-0.5 text-[10px] font-extrabold" style="background:color-mix(in srgb,#f59e0b 18%,transparent);color:#92400e">
                                                                    هر {{ fa_digits($subtype->cooldown_days) }} روز
                                                                </span>
                                                            @endif
                                                        </button>
                                                        <form method="POST" action="{{ route('surgery-types.subtypes.destroy', $subtype) }}" onsubmit="return confirm('حذف زیرگروه؟')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-xs text-rose-500">حذف</button>
                                                        </form>
                                                    </div>
                                                    <div class="mt-2 rounded-xl border p-3" style="border-color:var(--line);background:var(--panel-soft)" x-show="open" x-cloak>
                                                        @if($type->cooldown_days)
                                                            <p class="text-[11px] leading-5" style="color:var(--muted)">محدودیت این زیرگروه از قفل کل نوع «{{ $type->name }}» می‌آید (هر {{ fa_digits($type->cooldown_days) }} روز برای همان چشم). برای تغییر، دکمه «قفل کل نوع» را بزنید. اگر قفل نوع را خالی کنید، می‌توانید اینجا جداگانه عدد بگذارید.</p>
                                                        @else
                                                        <form method="POST" action="{{ route('surgery-types.subtypes.update', $subtype) }}" class="space-y-2">
                                                            @csrf
                                                            @method('PUT')
                                                            <label class="block text-xs font-bold" style="color:var(--ink)">فاصله حداقل (روز) فقط برای این زیرگروه</label>
                                                            <div class="flex gap-2">
                                                                <input type="number" name="cooldown_days" min="0" max="3650" class="field-input" dir="ltr" inputmode="numeric" placeholder="بدون محدودیت" value="{{ $subtype->cooldown_days ?: '' }}">
                                                                <button type="submit" class="btn-secondary shrink-0 !px-3">ذخیره</button>
                                                            </div>
                                                            <p class="text-[11px] leading-5" style="color:var(--muted)">مثلاً ۳۰ یعنی همان چشم نباید زودتر از ۳۰ روز دوباره همین زیرگروه را بگیرد. زیرگروه‌های دیگر این عمل جدا هستند مگر قفل کل نوع را بگذارید.</p>
                                                        </form>
                                                        @endif
                                                    </div>
                                                </li>
                                            @empty
                                                <li class="text-xs" style="color:var(--muted)">هنوز زیرگروهی نیست.</li>
                                            @endforelse
                                        </ul>
                                        <form method="POST" action="{{ route('surgery-types.subtypes.store', $type) }}" class="flex gap-2">
                                            @csrf
                                            <input type="text" name="name" class="field-input" placeholder="زیرگروه جدید + " required>
                                            <button type="submit" class="btn-secondary shrink-0 !px-3" title="افزودن">+</button>
                                        </form>

                                        @if(\App\Support\SurgeryChecklist::isAvailable())
                                        <x-surgery-checklist-template-editor
                                            :type-id="$type->id"
                                            :label="$type->name.' (عمومی)'"
                                        />

                                        @foreach ($type->subtypes as $subtype)
                                            <x-surgery-checklist-template-editor
                                                :type-id="$type->id"
                                                :subtype-id="$subtype->id"
                                                :label="$type->name.' · '.$subtype->name"
                                            />
                                        @endforeach
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>

</x-app-layout>
