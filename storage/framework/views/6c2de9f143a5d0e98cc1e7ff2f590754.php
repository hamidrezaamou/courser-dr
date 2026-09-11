<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title' => 'ورود']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['title' => 'ورود']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e($title ?? 'ورود'); ?> | آرشیو بیمار</title>

        <script>
            (function () {
                try {
                    var theme = localStorage.getItem('theme');
                    if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                        document.documentElement.classList.add('dark');
                    }
                } catch (e) {}
            })();
        </script>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="font-sans antialiased">
        <div class="guest-shell" x-data="{ dark: document.documentElement.classList.contains('dark') }">
            <button
                type="button"
                class="guest-theme"
                @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('theme', dark ? 'dark' : 'light')"
                :aria-label="dark ? 'حالت روشن' : 'حالت تیره'"
                :title="dark ? 'حالت روشن' : 'حالت تیره'"
            >
                <svg x-show="!dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z" />
                </svg>
                <svg x-show="dark" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4V2m0 20v-2m8-8h2M2 12h2m13.7-5.7l1.4-1.4M4.9 19.1l1.4-1.4m11.4 1.4l1.4 1.4M4.9 4.9l1.4 1.4M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </button>

            <div class="guest-stage fade-up">
                <div class="guest-brand">
                    <div class="guest-brand__icon float-soft">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-4.35-7-10a4 4 0 017-2.65A4 4 0 0119 11c0 5.65-7 10-7 10z" />
                            <circle cx="12" cy="11" r="1.6" fill="currentColor" stroke="none" />
                        </svg>
                    </div>
                    <h1 class="guest-brand__title">آرشیو بیمار</h1>
                    <p class="guest-brand__sub">سامانه پرونده الکترونیک کلینیک</p>
                </div>

                <div class="guest-card">
                    <?php echo e($slot); ?>

                </div>

                <p class="guest-foot">دسترسی امن · مناسب قلم نوری · طراحی برای تیم درمان</p>
            </div>
        </div>
    </body>
</html>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\layouts\guest.blade.php ENDPATH**/ ?>