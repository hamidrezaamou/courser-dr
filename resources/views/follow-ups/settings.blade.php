<x-app-layout>
    <x-slot name="header">
        <div class="page-header--compact flex items-center justify-between gap-3">
            <h2 class="page-title">الگو و تنظیمات پیگیری</h2>
            <a href="{{ route('followups.index') }}" class="btn-ghost hidden sm:inline-flex">داشبورد پیگیری</a>
        </div>
    </x-slot>

    <div class="settings-page-body">
        <div class="mx-auto max-w-6xl space-y-5 px-4 sm:px-6 lg:px-8">
            <x-settings-dock :settings-section="'follow-ups'" />
            <x-flash />

            <p class="text-sm leading-7" style="color: var(--muted);">
                برای هر نوع یا زیرگروه عمل یک الگو بسازید. اگر زیرگروه الگو داشته باشد همان استفاده می‌شود، وگرنه الگوی عمومی نوع عمل.
                الگوی مخصوص بیمارستان بر الگوی «همه بیمارستان‌ها» اولویت دارد؛ بنابراین می‌توانید زمان‌بندی هر بیمارستان را جدا تنظیم کنید.
                با افزودن یا ذخیرهٔ مرحله، پیگیری برای نوبت‌های عمل و ویزیتِ موجود (از ۹۰ روز پیش به بعد) هم ساخته می‌شود؛ نوبت‌های بعدی هم خودکار می‌آیند.
            </p>

            <div class="grid gap-4 lg:grid-cols-2">
                @foreach (['kind' => ['انواع پیگیری', $kinds], 'method' => ['روش‌های انجام', $methods], 'outcome' => ['نتایج', $outcomes]] as $group => $pack)
                    @php [$title, $items] = $pack; @endphp
                    <section class="panel p-4 {{ $group === 'outcome' ? 'lg:col-span-2' : '' }}">
                        <h3 class="mb-3 text-sm font-bold" style="color: var(--ink);">{{ $title }}</h3>
                        <div class="space-y-2">
                            @foreach ($items as $row)
                                <form method="POST" action="{{ route('settings.follow-ups.catalog.update', $row) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="label" value="{{ $row->label }}" class="field-input min-w-0 flex-1" @disabled(! $canManage) required>
                                    <input type="number" name="sort_order" value="{{ $row->sort_order }}" class="field-input w-20" @disabled(! $canManage)>
                                    <label class="flex items-center gap-1 text-[11px] font-bold" style="color: var(--muted);">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked($row->is_active) @disabled(! $canManage)>
                                        فعال
                                    </label>
                                    @if($canManage)
                                        <button type="submit" class="btn-secondary !px-2 !py-1 !text-[10px]">ذخیره</button>
                                    @endif
                                </form>
                            @endforeach
                        </div>
                        @if($canManage)
                            <form method="POST" action="{{ route('settings.follow-ups.catalog.store') }}" class="mt-3 flex flex-wrap gap-2">
                                @csrf
                                <input type="hidden" name="group" value="{{ $group }}">
                                <input type="text" name="label" class="field-input flex-1" placeholder="برچسب جدید" required>
                                <input type="text" name="slug" class="field-input w-32" placeholder="slug" dir="ltr" required>
                                <button type="submit" class="btn-secondary !px-3">+</button>
                            </form>
                        @endif
                    </section>
                @endforeach
            </div>

            @if($canManage)
                <section class="panel p-4">
                    <h3 class="mb-3 text-sm font-bold" style="color: var(--ink);">الگوی جدید</h3>
                    <form method="POST" action="{{ route('settings.follow-ups.templates.store') }}" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-6" x-data="{ applies: @js(old('applies_to', 'surgery')), typeId: @js(old('surgery_type_id', '')) }">
                        @csrf
                        <input type="text" name="name" class="field-input" placeholder="نام الگو" required>
                        <select name="applies_to" class="field-input" x-model="applies">
                            <option value="surgery">عمل</option>
                            <option value="visit">ویزیت</option>
                        </select>
                        <select name="hospital_id" class="field-input" x-show="applies === 'surgery'" x-cloak>
                            <option value="">همه بیمارستان‌ها</option>
                            @foreach ($hospitals as $hospital)
                                <option value="{{ $hospital->id }}" @selected((string) old('hospital_id') === (string) $hospital->id)>{{ $hospital->name }}</option>
                            @endforeach
                        </select>
                        <select name="surgery_type_id" class="field-input" x-show="applies === 'surgery'" x-cloak x-model="typeId">
                            <option value="">نوع عمل</option>
                            @foreach ($surgeryTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <select name="surgery_subtype_id" class="field-input" x-show="applies === 'surgery'" x-cloak>
                            <option value="">زیرگروه (اختیاری / اولویت بالاتر)</option>
                            @foreach ($surgeryTypes as $type)
                                @foreach ($type->subtypes as $subtype)
                                    <option value="{{ $subtype->id }}">{{ $type->name }} · {{ $subtype->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        <button type="submit" class="btn-primary">ساخت الگو</button>
                        <textarea name="description" rows="2" class="field-input sm:col-span-2 lg:col-span-6" placeholder="توضیح الگو (اختیاری)">{{ old('description') }}</textarea>
                    </form>
                </section>
            @endif

            <div class="space-y-4">
                @forelse ($templates as $template)
                    <section class="panel overflow-hidden p-0">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b px-4 py-3" style="border-color: var(--line);">
                            <div>
                                <h3 class="text-sm font-bold" style="color: var(--ink);">{{ $template->name }}</h3>
                                <p class="text-[11px]" style="color: var(--muted);">{{ $template->bindingLabel() }} · {{ $template->steps->count() }} مرحله</p>
                            </div>
                            @if($canManage)
                                <form method="POST" action="{{ route('settings.follow-ups.templates.update', $template) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="name" value="{{ $template->name }}">
                                    @if($template->applies_to === \App\Models\FollowUpTemplate::APPLIES_SURGERY)
                                        <select name="hospital_id" class="field-input !min-h-8 !py-1 !text-[11px]">
                                            <option value="">همه بیمارستان‌ها</option>
                                            @foreach ($hospitals as $hospital)
                                                <option value="{{ $hospital->id }}" @selected((int) $template->hospital_id === (int) $hospital->id)>{{ $hospital->name }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                    <input type="hidden" name="is_active" value="0">
                                    <label class="flex items-center gap-1 text-[11px] font-bold">
                                        <input type="checkbox" name="is_active" value="1" @checked($template->is_active)>
                                        فعال
                                    </label>
                                    <button type="submit" class="btn-secondary !px-2 !py-1 !text-[10px]">ذخیره وضعیت</button>
                                </form>
                                <form method="POST" action="{{ route('settings.follow-ups.templates.destroy', $template) }}" onsubmit="return confirm('الگو و همه پیگیری‌های ساخته‌شده از آن حذف شوند؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-secondary !px-2 !py-1 !text-[10px]" style="color:#b91c1c">حذف الگو</button>
                                </form>
                            @endif
                        </div>
                        <div class="space-y-3 p-4">
                            @if($template->description)
                                <p class="text-xs" style="color: var(--muted);">{{ $template->description }}</p>
                            @endif
                            @foreach ($template->steps as $step)
                                @if($canManage)
                                    <form method="POST" action="{{ route('settings.follow-ups.steps.update', $step) }}" class="grid gap-2 rounded-xl border p-3 sm:grid-cols-2 lg:grid-cols-4" style="border-color: var(--line);">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="title" value="{{ $step->title }}" class="field-input lg:col-span-2" required>
                                        <select name="kind" class="field-input">
                                            @foreach ($kinds as $row)
                                                <option value="{{ $row->slug }}" @selected($step->kind === $row->slug)>{{ $row->label }}</option>
                                            @endforeach
                                        </select>
                                        <select name="method" class="field-input">
                                            @foreach ($methods as $row)
                                                <option value="{{ $row->slug }}" @selected($step->method === $row->slug)>{{ $row->label }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" name="offset_amount" value="{{ $step->offset_amount }}" class="field-input" min="0" required>
                                        <select name="offset_unit" class="field-input">
                                            @foreach ($units as $slug => $label)
                                                <option value="{{ $slug }}" @selected($step->offset_unit === $slug)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <select name="offset_direction" class="field-input">
                                            @foreach ($directions as $slug => $label)
                                                <option value="{{ $slug }}" @selected($step->offset_direction === $slug)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <select name="reference_event" class="field-input">
                                            @foreach ($referenceEvents as $slug => $label)
                                                <option value="{{ $slug }}" @selected($step->reference_event === $slug)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <select name="assigned_user_id" class="field-input lg:col-span-2">
                                            <option value="">مسئول پیش‌فرض ندارد</option>
                                            @foreach ($staff as $user)
                                                <option value="{{ $user->id }}" @selected((int) $step->assigned_user_id === (int) $user->id)>{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" name="sort_order" value="{{ $step->sort_order }}" class="field-input" placeholder="ترتیب">
                                        <div class="flex gap-2 lg:col-span-4">
                                            <button type="submit" class="btn-primary !py-1.5 !text-xs">ذخیره مرحله</button>
                                            <button type="submit" form="del-step-{{ $step->id }}" class="btn-secondary !py-1.5 !text-xs" style="color:#b91c1c">حذف مرحله</button>
                                        </div>
                                    </form>
                                    <form id="del-step-{{ $step->id }}" method="POST" action="{{ route('settings.follow-ups.steps.destroy', $step) }}">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @else
                                    <div class="rounded-xl border px-3 py-2 text-xs" style="border-color: var(--line);">
                                        <strong>{{ $step->title }}</strong>
                                        · {{ $step->timingLabel() }}
                                        · {{ $kinds->firstWhere('slug', $step->kind)?->label ?? $step->kind }}
                                    </div>
                                @endif
                            @endforeach

                            @if($canManage)
                                <form method="POST" action="{{ route('settings.follow-ups.steps.store', $template) }}" class="grid gap-2 rounded-xl border border-dashed p-3 sm:grid-cols-2 lg:grid-cols-4" style="border-color: var(--line);">
                                    @csrf
                                    <input type="text" name="title" class="field-input lg:col-span-2" placeholder="عنوان مرحله جدید" required>
                                    <select name="kind" class="field-input">
                                        @foreach ($kinds as $row)
                                            <option value="{{ $row->slug }}">{{ $row->label }}</option>
                                        @endforeach
                                    </select>
                                    <select name="method" class="field-input">
                                        @foreach ($methods as $row)
                                            <option value="{{ $row->slug }}">{{ $row->label }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" name="offset_amount" value="1" class="field-input" min="0" required>
                                    <select name="offset_unit" class="field-input">
                                        @foreach ($units as $slug => $label)
                                            <option value="{{ $slug }}" @selected($slug === 'day')>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <select name="offset_direction" class="field-input">
                                        @foreach ($directions as $slug => $label)
                                            <option value="{{ $slug }}" @selected($slug === 'after')>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <select name="reference_event" class="field-input">
                                        @foreach ($referenceEvents as $slug => $label)
                                            <option value="{{ $slug }}" @selected($slug === ($template->applies_to === 'visit' ? 'appointment_date' : 'surgery_date'))>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <select name="assigned_user_id" class="field-input lg:col-span-3">
                                        <option value="">مسئول پیش‌فرض ندارد</option>
                                        @foreach ($staff as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn-primary lg:col-span-4">افزودن مرحله</button>
                                </form>
                            @endif
                        </div>
                    </section>
                @empty
                    <div class="panel p-6 text-sm" style="color: var(--muted);">هنوز الگویی ساخته نشده.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
