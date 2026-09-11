<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title>آرشیو بیمار | <?php echo e(config('app.name', 'Patient Archive')); ?></title>

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
    <body class="<?php echo \Illuminate\Support\Arr::toCssClasses(['font-sans antialiased', $bodyClass ?? '']); ?>" style="color: var(--ink);">
        <div class="app-shell relative">
            <div class="relative z-10">
                <?php echo $__env->make('layouts.navigation', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                <?php if(isset($header)): ?>
                    <header class="page-topbar border-b backdrop-blur-sm">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 fade-up">
                            <?php echo e($header); ?>

                        </div>
                    </header>
                <?php endif; ?>

                <main class="relative">
                    <?php echo e($slot); ?>

                </main>
            </div>
        </div>
        <script>
            (function () {
                var flashes = document.querySelectorAll('[data-auto-dismiss]');
                flashes.forEach(function (el) {
                    var ms = Number(el.getAttribute('data-auto-dismiss')) || 4000;
                    window.setTimeout(function () {
                        el.style.transition = 'opacity .28s ease, transform .28s ease, max-height .28s ease, margin .28s ease, padding .28s ease';
                        el.style.opacity = '0';
                        el.style.transform = 'translateY(-4px)';
                        el.style.maxHeight = '0';
                        el.style.marginTop = '0';
                        el.style.marginBottom = '0';
                        el.style.paddingTop = '0';
                        el.style.paddingBottom = '0';
                        window.setTimeout(function () { el.remove(); }, 300);
                    }, ms);
                });
            })();
        </script>
    </body>
</html>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\layouts\app.blade.php ENDPATH**/ ?>