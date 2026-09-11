<x-app-layout>
    <x-slot name="header">
        <div class="page-header--compact flex items-center justify-between gap-3">
            <h2 class="page-title">کاربران و نقش‌ها</h2>
            <a href="{{ route('admin.users.create') }}" class="btn-primary btn-primary--compact">+ کاربر جدید</a>
        </div>
    </x-slot>

    <div class="admin-page">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-admin-dock :admin-section="'users'" />
            <x-flash />

            <form method="GET" action="{{ route('admin.users.index') }}" class="admin-filter panel">
                <div class="admin-filter__field">
                    <label class="admin-filter__label">جستجو</label>
                    <input type="search" name="q" value="{{ $q }}" class="field-input" placeholder="نام، کد ملی یا موبایل...">
                </div>
                <div class="admin-filter__field">
                    <label class="admin-filter__label">نقش</label>
                    <select name="role" class="field-input">
                        <option value="">همه پرسنل</option>
                        @foreach ($roleLabels as $value => $label)
                            <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-primary !py-2 !px-4 !text-sm">اعمال</button>
            </form>

            <section class="admin-panel !p-0 overflow-hidden">
                @if ($users->isEmpty())
                    <p class="admin-empty">کاربری پیدا نشد.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>نام</th>
                                    <th>نقش</th>
                                    <th>کد ملی</th>
                                    <th>موبایل</th>
                                    <th>ثبت</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $row)
                                    <tr>
                                        <td>
                                            <strong>{{ $row->name }}</strong>
                                            @if ($row->id === auth()->id())
                                                <span class="admin-chip">شما</span>
                                            @endif
                                        </td>
                                        <td><span class="admin-role-badge admin-role-badge--{{ $row->role }}">{{ $roleLabels[$row->role] ?? $row->role }}</span></td>
                                        <td class="ltr-data" dir="ltr">{{ $row->national_code }}</td>
                                        <td class="ltr-data" dir="ltr">{{ $row->mobile ?: '—' }}</td>
                                        <td class="ltr-data" dir="ltr">{{ jalali($row->created_at, 'Y/m/d') }}</td>
                                        <td class="admin-table__acts">
                                            <a href="{{ route('admin.users.edit', $row) }}" class="btn-secondary !py-1 !px-2.5 !text-[11px]">ویرایش</a>
                                            @if ($row->id !== auth()->id())
                                                <form method="POST" action="{{ route('admin.users.destroy', $row) }}" onsubmit="return confirm('حذف کاربر «{{ $row->name }}»؟')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-ghost !py-1 !px-2 !text-[11px]" style="color: var(--danger);">حذف</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t px-4 py-3" style="border-color: var(--line);">
                        {{ $users->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
