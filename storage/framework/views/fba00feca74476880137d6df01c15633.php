<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>آرشیو بیمار | سامانه داخلی کلینیک</title>
        <meta name="description" content="سامانه داخلی پرونده الکترونیک کلینیک — دسترسی محدود کارکنان.">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="landing-body font-sans antialiased">
        <div class="landing">
            <div class="landing__mesh" aria-hidden="true"></div>
            <div class="landing__orb landing__orb--a" aria-hidden="true"></div>
            <div class="landing__orb landing__orb--b" aria-hidden="true"></div>

            <section class="landing-hero">
                <header class="landing-top">
                    <span class="landing-top__badge">سامانه داخلی · دسترسی کارکنان</span>
                    <nav class="landing-top__nav">
                        <?php if(Route::has('login')): ?>
                            <?php if(auth()->guard()->check()): ?>
                                <a href="<?php echo e(auth()->user()->role === 'patient' ? route('my-profile') : route('dashboard')); ?>" class="landing-top__link">ورود به پنل</a>
                            <?php else: ?>
                                <a href="<?php echo e(route('login')); ?>" class="landing-top__link landing-top__link--solid">ورود کارکنان</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </nav>
                </header>

                <div class="landing-hero__stage">
                    <div class="landing-hero__copy landing-reveal">
                        <p class="landing-brand">آرشیو بیمار</p>
                        <h1 class="landing-hero__title">پنل روزانه تیم درمان</h1>
                        <p class="landing-hero__lead">
                            جستجوی پرونده، نوبت ویزیت و عمل، گالری مدارک و پیامک — همان فضای کاری داخل کلینیک.
                        </p>

                        <div class="landing-hero__cta">
                            <?php if(Route::has('login')): ?>
                                <?php if(auth()->guard()->check()): ?>
                                    <a href="<?php echo e(auth()->user()->role === 'patient' ? route('my-profile') : route('dashboard')); ?>" class="landing-btn landing-btn--primary">ادامه در پنل</a>
                                <?php else: ?>
                                    <a href="<?php echo e(route('login')); ?>" class="landing-btn landing-btn--primary">ورود به سامانه</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="landing-hero__visual landing-reveal landing-reveal--late" aria-hidden="true">
                        <div class="lp-shot lp-shot--desk">
                            <div class="lp-shot__chrome">
                                <span></span><span></span><span></span>
                                <strong>سامانه داخلی کلینیک</strong>
                                <em>دکتر / منشی</em>
                            </div>
                            <div class="lp-shot__body">
                                <aside class="lp-shot__nav">
                                    <b>آرشیو</b>
                                    <i class="is-on">جستجوی بیمار</i>
                                    <i>نوبت امروز</i>
                                    <i>تایم‌ها</i>
                                    <i>گزارشات</i>
                                    <i>تنظیمات</i>
                                </aside>
                                <div class="lp-shot__main">
                                    <div class="lp-shot__search">نام، کد ملی یا موبایل…</div>
                                    <table class="lp-shot__table">
                                        <thead>
                                            <tr><th>بیمار</th><th>نوبت</th><th></th></tr>
                                        </thead>
                                        <tbody>
                                            <tr class="is-on">
                                                <td>رضا محمدی</td>
                                                <td>پیگیری آب‌سیاه</td>
                                                <td>پرونده</td>
                                            </tr>
                                            <tr>
                                                <td>سارا احمدی</td>
                                                <td>ویزیت ۱۰:۳۰</td>
                                                <td>ویزیت</td>
                                            </tr>
                                            <tr>
                                                <td>مریم کریمی</td>
                                                <td>عمل · نور</td>
                                                <td>عمل</td>
                                            </tr>
                                            <tr>
                                                <td>حسین نوری</td>
                                                <td>اورژانس</td>
                                                <td>عمل</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div class="lp-shot__dock">
                                        <span>معاینه</span>
                                        <span>وایت‌برد</span>
                                        <span>گالری</span>
                                        <span>نسخه</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="landing-shots">
                <div class="landing-shots__inner">
                    <p class="landing-shots__kicker">نمای داخل پنل</p>
                    <h2 class="landing-section__title">همان صفحه‌هایی که تیم هر روز باز می‌کند</h2>

                    <div class="landing-shots__grid">
                        <article class="lp-shot lp-shot--board">
                            <div class="lp-shot__chrome">
                                <span></span><span></span><span></span>
                                <strong>وایت‌برد نوبت</strong>
                            </div>
                            <ul class="lp-board">
                                <li><time>۰۹:۰۰</time><div><b>سارا احمدی</b><small>ویزیت کنترل</small></div><em class="is-ok">تأیید</em></li>
                                <li><time>۰۹:۳۰</time><div><b>رضا محمدی</b><small>پیگیری آب‌سیاه</small></div><em>در صف</em></li>
                                <li class="is-on"><time>۱۰:۳۰</time><div><b>مریم کریمی</b><small>کاتاراکت · مهر</small></div><em class="is-ok">تأیید</em></li>
                                <li><time>۱۱:۱۵</time><div><b>حسین نوری</b><small>اورژانس</small></div><em class="is-hot">اورژانس</em></li>
                            </ul>
                        </article>

                        <article class="lp-shot lp-shot--file">
                            <div class="lp-shot__chrome">
                                <span></span><span></span><span></span>
                                <strong>پرونده بیمار</strong>
                            </div>
                            <div class="lp-file">
                                <div class="lp-file__head">
                                    <div class="lp-file__avatar">س</div>
                                    <div>
                                        <b>سارا احمدی</b>
                                        <small>کد ملی ۰۰۱۲۳۴۵۶۷۸</small>
                                    </div>
                                </div>
                                <div class="lp-file__tabs">
                                    <span class="is-on">معاینه</span>
                                    <span>گالری</span>
                                    <span>عمل</span>
                                </div>
                                <div class="lp-file__note">
                                    حدت بینایی بهتر شده · قطره ادامه · کنترل دو هفته دیگر
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section class="landing-section landing-flow">
                <div class="landing-section__inner">
                    <h2 class="landing-section__title">مسیر کار روزانه</h2>
                    <p class="landing-section__lead">از ورود بیمار تا پیگیری بعد از عمل، بدون جابه‌جایی بین چند ابزار.</p>

                    <ol class="landing-steps">
                        <li>
                            <span>۱</span>
                            <div>
                                <strong>پیدا کردن پرونده</strong>
                                <p>با نام، کد ملی یا موبایل — پرونده در چند ثانیه جلوی چشم است.</p>
                            </div>
                        </li>
                        <li>
                            <span>۲</span>
                            <div>
                                <strong>معاینه و مدارک</strong>
                                <p>متن، صدا، وایت‌برد و گالری تصاویر در یک پرونده یکپارچه.</p>
                            </div>
                        </li>
                        <li>
                            <span>۳</span>
                            <div>
                                <strong>نوبت و یادآوری</strong>
                                <p>ویزیت و عمل را زمان‌بندی کنید؛ یادآوری از همان سامانه می‌رود.</p>
                            </div>
                        </li>
                    </ol>
                </div>
            </section>

            <section class="landing-section landing-capabilities">
                <div class="landing-section__inner landing-section__inner--wide">
                    <h2 class="landing-section__title">برای کل تیم کلینیک</h2>
                    <p class="landing-section__lead">نقش‌ها جدا، پرونده مشترک — هر نفر فقط آنچه لازم دارد می‌بیند.</p>

                    <div class="landing-roles">
                        <article>
                            <strong>پزشک</strong>
                            <p>معاینه، وایت‌برد، نسخه و پیگیری بالینی در یک نما.</p>
                        </article>
                        <article>
                            <strong>منشی</strong>
                            <p>ثبت نوبت، تماس، پیامک و گزارش‌های روزانه بدون پیچیدگی.</p>
                        </article>
                        <article>
                            <strong>مدیر</strong>
                            <p>تنظیمات مراکز، تایم‌ها، دسترسی‌ها و نمای کلی عملکرد.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="landing-close">
                <div class="landing-close__inner">
                    <p class="landing-close__brand">آرشیو بیمار</p>
                    <h2 class="landing-close__title">ورود فقط برای کارکنان کلینیک</h2>
                    <p class="landing-close__lead">دسترسی نقش‌محور، مناسب کار روزمره تیم درمان.</p>
                    <div class="landing-close__cta">
                        <?php if(Route::has('login')): ?>
                            <?php if(auth()->guard()->check()): ?>
                                <a href="<?php echo e(auth()->user()->role === 'patient' ? route('my-profile') : route('dashboard')); ?>" class="landing-btn landing-btn--primary">ورود به پنل</a>
                            <?php else: ?>
                                <a href="<?php echo e(route('login')); ?>" class="landing-btn landing-btn--primary">ورود به سامانه</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <footer class="landing-foot">
                <span>آرشیو بیمار</span>
                <span aria-hidden="true">·</span>
                <span>سامانه داخلی پرونده الکترونیک کلینیک</span>
            </footer>
        </div>
    </body>
</html>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\welcome.blade.php ENDPATH**/ ?>