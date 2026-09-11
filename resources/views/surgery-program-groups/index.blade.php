<x-app-layout>
    <x-slot name="header">
        <div class="page-header--compact flex items-center justify-between gap-3">
            <h2 class="page-title">گروه‌های برنامه نوبت عمل</h2>
            <a href="{{ route('settings.times', ['kind' => 'surgery']) }}" class="btn-ghost hidden sm:inline-flex">تایم‌های عمل</a>
        </div>
    </x-slot>

    <div class="settings-page-body">
        <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
            <x-settings-dock :settings-section="'program-groups'" />
            <x-flash />

            <p class="text-sm leading-7" style="color: var(--muted);">
                هر گروه برنامه ظرفیت مستقل خودش را دارد. اگر گروه A در بیمارستان فارابی ۱۰ نوبت داشته باشد و پر شود،
                ظرفیت گروه B در همان بیمارستان عوض نمی‌شود. یک نوع عمل یا زیرگروه فقط در یک گروه می‌تواند باشد.
            </p>

            <div class="grid gap-5 lg:grid-cols-1 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
                <section class="panel p-5">
                    <h3 class="mb-4 text-sm font-bold" style="color: var(--ink);">افزودن گروه برنامه</h3>
                    <form method="POST" action="{{ route('surgery-program-groups.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="name" value="نام گروه" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required placeholder="مثال: گروه A" />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>
                        <div>
                            <p class="mb-2 text-xs font-bold" style="color: var(--ink);">نوع‌ها و زیرگروه‌ها</p>
                            <x-input-error class="mb-2" :messages="$errors->get('members')" />
                            <div class="max-h-[28rem] space-y-2 overflow-auto rounded-xl border p-3" style="border-color: var(--line); background: var(--panel-soft);">
                                @forelse ($types as $type)
                                    <details class="rounded-xl border px-3 py-2" style="border-color: var(--line); background: var(--panel);" open>
                                        <summary class="flex cursor-pointer list-none items-center gap-2 text-sm font-bold" style="color: var(--ink);">
                                            <label class="flex items-center gap-2" @click.stop>
                                                <input type="checkbox" name="members[]" value="type:{{ $type->id }}" @checked(collect(old('members', []))->contains('type:'.$type->id))>
                                                کل نوع «{{ $type->name }}»
                                            </label>
                                        </summary>
                                        <ul class="mt-2 space-y-1 pr-6">
                                            @forelse ($type->subtypes as $subtype)
                                                <li>
                                                    <label class="flex items-center gap-2 text-sm" style="color: var(--ink);">
                                                        <input type="checkbox" name="members[]" value="subtype:{{ $subtype->id }}" @checked(collect(old('members', []))->contains('subtype:'.$subtype->id))>
                                                        {{ $subtype->name }}
                                                    </label>
                                                </li>
                                            @empty
                                                <li class="text-xs" style="color: var(--muted);">زیرگروهی ندارد — با انتخاب کل نوع اضافه می‌شود.</li>
                                            @endforelse
                                        </ul>
                                    </details>
                                @empty
                                    <p class="text-xs" style="color: var(--muted);">اول در بخش انواع عمل، نوع و زیرگروه بسازید.</p>
                                @endforelse
                            </div>
                        </div>
                        <button type="submit" class="btn-primary w-full !py-3">ذخیره گروه</button>
                    </form>
                </section>

                <section class="panel overflow-hidden p-0">
                    <div class="flex items-center justify-between border-b px-5 py-4" style="border-color: var(--line);">
                        <h3 class="text-sm font-bold" style="color: var(--ink);">گروه‌های ثبت‌شده</h3>
                        <span class="rounded-full px-3 py-1 text-xs font-bold" style="background: var(--brand-soft); color: var(--brand-dark);">{{ $groups->count() }} گروه</span>
                    </div>
                    @if ($groups->isEmpty())
                        <div class="pp-empty">هنوز گروه برنامه‌ای نیست.</div>
                    @else
                        <div class="divide-y" style="border-color: var(--line);">
                            @foreach ($groups as $group)
                                <details class="p-4" @if($errors->any() && (int) old('edit_id') === (int) $group->id) open @endif>
                                    <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <strong style="color: var(--ink);">{{ $group->name }}</strong>
                                            <span class="mr-2 text-xs" style="color: var(--muted);">{{ $group->is_active ? 'فعال' : 'غیرفعال' }}</span>
                                        </div>
                                        <span class="text-xs" style="color: var(--muted);">{{ $group->items->count() }} مورد</span>
                                    </summary>
                                    <ul class="mt-3 flex flex-wrap gap-1.5">
                                        @foreach ($group->items as $item)
                                            <li class="rounded-full px-2.5 py-1 text-[11px] font-bold" style="background: var(--brand-soft); color: var(--brand-dark);">
                                                {{ $item->surgeryType?->name }}
                                                @if($item->surgery_subtype_id)
                                                    · {{ $item->surgerySubtype?->name }}
                                                @else
                                                    · همه زیرگروه‌ها
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                    <form method="POST" action="{{ route('surgery-program-groups.update.post', $group) }}" class="mt-4 space-y-3">
                                        @csrf
                                        <input type="hidden" name="edit_id" value="{{ $group->id }}">
                                        <div>
                                            <x-input-label :for="'gname-'.$group->id" value="نام گروه" />
                                            <input id="gname-{{ $group->id }}" type="text" name="name" value="{{ old('edit_id') == $group->id ? old('name', $group->name) : $group->name }}" class="field-input mt-1" required>
                                        </div>
                                        <label class="flex items-center gap-2 text-xs font-bold" style="color: var(--muted);">
                                            <input type="checkbox" name="is_active" value="1" @checked($group->is_active)>
                                            فعال
                                        </label>
                                        <div class="max-h-64 space-y-2 overflow-auto rounded-xl border p-3" style="border-color: var(--line); background: var(--panel-soft);">
                                            @php
                                                $selected = old('edit_id') == $group->id
                                                    ? collect(old('members', []))
                                                    : $group->items->map(function ($item) {
                                                        return $item->surgery_subtype_id
                                                            ? 'subtype:'.$item->surgery_subtype_id
                                                            : 'type:'.$item->surgery_type_id;
                                                    });
                                            @endphp
                                            @foreach ($types as $type)
                                                <div>
                                                    <label class="flex items-center gap-2 text-sm font-bold" style="color: var(--ink);">
                                                        <input type="checkbox" name="members[]" value="type:{{ $type->id }}" @checked($selected->contains('type:'.$type->id))>
                                                        کل نوع «{{ $type->name }}»
                                                    </label>
                                                    <ul class="mt-1 space-y-1 pr-6">
                                                        @foreach ($type->subtypes as $subtype)
                                                            <li>
                                                                <label class="flex items-center gap-2 text-sm" style="color: var(--ink);">
                                                                    <input type="checkbox" name="members[]" value="subtype:{{ $subtype->id }}" @checked($selected->contains('subtype:'.$subtype->id))>
                                                                    {{ $subtype->name }}
                                                                </label>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="submit" class="btn-primary !py-2 !px-4">ذخیره تغییرات</button>
                                        </div>
                                    </form>
                                    <form method="POST" action="{{ route('surgery-program-groups.destroy.post', $group) }}" class="mt-2" onsubmit="return confirm('حذف این گروه برنامه؟ ظرفیت روزهایش هم حذف می‌شود.')">
                                        @csrf
                                        <button type="submit" class="touch-action text-red-600" style="background:color-mix(in srgb, #fee2e2 80%, transparent)">حذف گروه</button>
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
