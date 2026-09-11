<x-app-layout>

    <x-slot name="header">

        <div class="page-header--compact flex items-center justify-between gap-3">

            <h2 class="page-title">مدیریت بیماران</h2>

            <a href="{{ route('patients.create') }}" class="btn-primary btn-primary--compact">+ ثبت بیمار</a>

        </div>

    </x-slot>



    <div class="settings-page-body" x-data="patientManage()">

        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">

            <x-settings-dock :settings-section="'patients'" />

            <x-flash />



            <div class="panel p-5 sm:p-6">

                <h3 class="mb-3 text-sm font-bold" style="color: var(--ink);">واردسازی گروهی از JSON</h3>

                <p class="mb-4 text-sm" style="color: var(--muted);">

                    فایلی با فیلدهای نام، کد ملی و موبایل آپلود کنید. ردیف‌های تکراری رد می‌شوند.

                </p>

                <form method="POST" action="{{ route('patients.import-json') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">

                    @csrf

                    <div class="min-w-[220px] flex-1">

                        <input name="json_file" type="file" accept=".json,application/json,.txt" class="field-input" required>

                        <x-input-error :messages="$errors->get('json_file')" />

                    </div>

                    <button type="submit" class="btn-secondary !py-2 !px-4 !text-sm"

                            onclick="return confirm('پرونده‌های جدید از فایل JSON وارد شوند؟')">

                        وارد کردن

                    </button>

                </form>

            </div>



            <form method="GET" action="{{ route('settings.patients') }}" class="admin-filter panel">

                <div class="admin-filter__field flex-1">

                    <label class="admin-filter__label">جستجو</label>

                    <input type="search" name="q" value="{{ $q }}" class="field-input" placeholder="نام، کد ملی یا موبایل...">

                </div>

                <label class="flex items-center gap-2 text-xs font-bold whitespace-nowrap" style="color: var(--ink);">

                    <input type="checkbox" name="show_erased" value="1" class="rounded" @checked($showErased)>

                    نمایش حذف‌شده‌ها

                </label>

                <button type="submit" class="btn-primary !py-2 !px-4 !text-sm">جستجو</button>

            </form>



            <section class="admin-panel !p-0 overflow-hidden">

                @if ($patients->isEmpty())

                    <p class="admin-empty">بیماری پیدا نشد.</p>

                @else

                    <div class="overflow-x-auto">

                        <table class="admin-table">

                            <thead>

                                <tr>

                                    <th>نام</th>

                                    <th>کد ملی</th>

                                    <th>موبایل</th>

                                    <th>سن</th>

                                    <th>ثبت</th>

                                    <th></th>

                                </tr>

                            </thead>

                            <tbody>

                                @foreach ($patients as $row)

                                    @php

                                        $isErased = $row->isSecureErased();

                                    @endphp

                                    <tr @class(['opacity-70' => $isErased])>

                                        <td>

                                            <strong>{{ $row->name }}</strong>

                                            @if($isErased)

                                                <span class="mr-1 inline-block rounded px-1.5 py-0.5 text-[10px] font-bold text-amber-800" style="background:#fef3c7;">حذف امن</span>

                                            @endif

                                        </td>

                                        <td class="ltr-data" dir="ltr">{{ $isErased ? '—' : ($row->national_code ?: '—') }}</td>

                                        <td class="ltr-data" dir="ltr">{{ $isErased ? '—' : ($row->mobile ?: '—') }}</td>

                                        <td>{{ $row->age ?: '—' }}</td>

                                        <td class="ltr-data" dir="ltr">{{ jalali($row->created_at, 'Y/m/d') }}</td>

                                        <td class="admin-table__acts">

                                            @unless($isErased)

                                                <a href="{{ route('patients.show', $row) }}" class="btn-ghost !py-1 !px-2.5 !text-[11px]">پرونده</a>

                                                <a href="{{ route('patients.edit', ['patient' => $row, 'q' => $q]) }}" class="btn-secondary !py-1 !px-2.5 !text-[11px]">ویرایش</a>

                                                <button type="button"

                                                        class="btn-ghost !py-1 !px-2 !text-[11px]"

                                                        style="color: var(--danger);"

                                                        @click="openDelete({{ $row->id }}, @js($row->name), @js(route('patients.secure-erase', $row)))">

                                                    حذف

                                                </button>

                                            @else

                                                <span class="text-[11px]" style="color: var(--muted);">فقط ممیزی</span>

                                            @endunless

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                    <div class="border-t px-4 py-3" style="border-color: var(--line);">

                        {{ $patients->links() }}

                    </div>

                @endif

            </section>

        </div>



        <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" style="background: rgba(15,23,42,.55);">

            <div class="w-full max-w-md rounded-2xl border bg-white p-5 shadow-xl" style="border-color: var(--line);" @click.outside="closeDelete()">

                <h3 class="text-sm font-extrabold text-red-700">حذف امن پرونده</h3>

                <p class="mt-2 text-xs leading-6" style="color: var(--muted);">

                    هویت بیمار ناشناس می‌شود و فایل‌های بالینی پاک می‌شوند. رکورد نوبت‌ها برای آمار باقی می‌ماند.

                    پس از حذف، بیمار از لیست عادی پنهان می‌شود.

                </p>

                <p class="mt-3 text-xs font-bold" style="color: var(--ink);">

                    برای تأیید، نام «<span x-text="deleteName"></span>» را دقیقاً بنویسید:

                </p>

                <form method="POST" :action="deleteUrl" class="mt-3 space-y-3" @submit="return confirm('حذف امن قطعی است. ادامه؟')">

                    @csrf

                    @method('DELETE')

                    <input type="hidden" name="q" value="{{ $q }}">

                    <input type="hidden" name="page" value="{{ request('page') }}">

                    @if($showErased)

                        <input type="hidden" name="show_erased" value="1">

                    @endif

                    <input type="text" name="confirm_name" class="field-input !text-sm" x-model="confirmName" required autocomplete="off" :placeholder="'نام «' + deleteName + '»'">

                    @error('confirm_name')

                        <p class="text-xs font-bold text-red-600">{{ $message }}</p>

                    @enderror

                    <div class="flex gap-2">

                        <button type="button" class="btn-secondary flex-1 !py-2 !text-xs" @click="closeDelete()">انصراف</button>

                        <button type="submit" class="btn-secondary flex-1 !py-2 !text-xs text-red-700">اجرای حذف امن</button>

                    </div>

                </form>

            </div>

        </div>

    </div>



    @push('scripts')

        <script>

            function patientManage() {

                return {

                    deleteOpen: {{ (session('delete_patient_id') || $errors->has('confirm_name')) ? 'true' : 'false' }},

                    deleteId: {{ session('delete_patient_id') ? (int) session('delete_patient_id') : 'null' }},

                    deleteName: @js(old('confirm_name') ? '' : ''),

                    deleteUrl: '',

                    confirmName: @js(old('confirm_name', '')),

                    openDelete(id, name, url) {

                        this.deleteId = id;

                        this.deleteName = name;

                        this.deleteUrl = url;

                        this.confirmName = '';

                        this.deleteOpen = true;

                    },

                    closeDelete() {

                        this.deleteOpen = false;

                        this.confirmName = '';

                    },

                    init() {
                        @if(session('delete_patient_id') && session('delete_patient_name'))
                            this.deleteName = @js(session('delete_patient_name'));
                            this.deleteUrl = @js(route('patients.secure-erase', ['patient' => session('delete_patient_id')]));
                        @endif
                    },

                };

            }

        </script>

    @endpush

</x-app-layout>

