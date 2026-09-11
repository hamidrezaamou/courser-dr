<section class="admin-panel admin-dash-panel">
    <div class="admin-panel__head">
        <h3 class="admin-panel__title">میان‌برهای مدیریت</h3>
    </div>
    <div class="admin-shortcuts">
        <a href="{{ route('admin.users.index') }}" class="admin-shortcut">کاربران و نقش‌ها</a>
        <a href="{{ route('admin.settings.communications') }}" class="admin-shortcut">ارتباطات</a>
        <a href="{{ route('admin.settings.brand') }}" class="admin-shortcut">برند و چاپ</a>
        <a href="{{ route('admin.settings.system') }}" class="admin-shortcut">سامانه / بک‌آپ</a>
        <a href="{{ route('admin.settings.features') }}" class="admin-shortcut">قابلیت‌ها</a>
        <a href="{{ route('admin.settings.quick-links') }}" class="admin-shortcut">لینک‌های ویژه هدر</a>
        @foreach(\App\Support\ModuleRegistry::enabled() as $module)
            <a href="{{ route($module['route']) }}" class="admin-shortcut">{{ $module['label'] }}</a>
        @endforeach
        <a href="{{ route('settings.times') }}" class="admin-shortcut">تایم‌ها</a>
        <a href="{{ route('settings.hospitals') }}" class="admin-shortcut">بیمارستان‌ها</a>
        <a href="{{ route('settings.surgery-types') }}" class="admin-shortcut">انواع عمل</a>
        @if(\App\Support\PatientFollowUps::isAvailable())
            <a href="{{ route('followups.index') }}" class="admin-shortcut">پیگیری بیماران</a>
            <a href="{{ route('settings.follow-ups') }}" class="admin-shortcut">الگوی پیگیری</a>
        @endif
        <a href="{{ route('settings.drugs') }}" class="admin-shortcut">داروها</a>
        <a href="{{ route('activity-logs.index') }}" class="admin-shortcut">لاگ ممیزی</a>
        <a href="{{ route('reports.index') }}" class="admin-shortcut">گزارشات</a>
        <a href="{{ route('appointments.board') }}" class="admin-shortcut">نوبت‌ها</a>
    </div>
</section>
