<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve(['bodyClass' => 'is-dashboard'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div class="dash-header">
            <h2 class="dash-header__title">جستجوی بیمار</h2>
            <div class="dash-header__actions">
                <a href="<?php echo e(route('surgery-appointments.register')); ?>" class="btn-secondary btn-secondary--warm btn-primary--compact">
                    ثبت عمل
                </a>
                <a href="<?php echo e(route('patients.create')); ?>" class="btn-primary btn-primary--compact">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span class="hidden sm:inline">ثبت بیمار</span>
                    <span class="sm:hidden">ثبت</span>
                </a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="dash-page"
         x-data="patientSearch(<?php echo \Illuminate\Support\Js::from($search)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from(route('dashboard'))->toHtml() ?>)"
         x-init="boot()">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <?php if (isset($component)) { $__componentOriginal5168fdb0c14fd91c6598264bc4be63f2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.flash','data' => ['class' => 'fade-up mb-3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flash'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'fade-up mb-3']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2)): ?>
<?php $attributes = $__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2; ?>
<?php unset($__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5168fdb0c14fd91c6598264bc4be63f2)): ?>
<?php $component = $__componentOriginal5168fdb0c14fd91c6598264bc4be63f2; ?>
<?php unset($__componentOriginal5168fdb0c14fd91c6598264bc4be63f2); ?>
<?php endif; ?>

            <div class="dash-search panel fade-up">
                <label class="dash-search__wrap" :class="loading && 'is-loading'">
                    <svg class="dash-search__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                    </svg>
                    <input
                        type="search"
                        class="dash-search__input"
                        placeholder="نام، کد ملی یا موبایل..."
                        x-model="q"
                        @input.debounce.300ms="search()"
                        autocomplete="off"
                        enterkeyhint="search"
                    >
                    <button type="button" class="dash-search__clear" x-show="q.length > 0" x-cloak @click="clear()"><?php if (isset($component)) { $__componentOriginal3baa94417da9f5dcb14f5f04da758571 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3baa94417da9f5dcb14f5f04da758571 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon-close','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon-close'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3baa94417da9f5dcb14f5f04da758571)): ?>
<?php $attributes = $__attributesOriginal3baa94417da9f5dcb14f5f04da758571; ?>
<?php unset($__attributesOriginal3baa94417da9f5dcb14f5f04da758571); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3baa94417da9f5dcb14f5f04da758571)): ?>
<?php $component = $__componentOriginal3baa94417da9f5dcb14f5f04da758571; ?>
<?php unset($__componentOriginal3baa94417da9f5dcb14f5f04da758571); ?>
<?php endif; ?></button>
                    <span class="dash-search__spinner" x-show="loading" x-cloak></span>
                </label>
            </div>

            <div class="patient-results panel fade-up-delay">
                <div class="patient-results__head">
                    <div class="patient-results__head-row">
                        <div class="patient-view-toggle" role="group" aria-label="نوع نمایش پرونده‌ها">
                            <button type="button"
                                    class="patient-view-toggle__btn"
                                    :class="viewMode === 'list' && 'is-active'"
                                    @click="setView('list')"
                                    aria-label="نمای لیست"
                                    title="نمای لیست">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm0 5.25h.007v.008H3.75V12zm0 5.25h.007v.008H3.75v-.008z" />
                                </svg>
                            </button>
                            <button type="button"
                                    class="patient-view-toggle__btn"
                                    :class="viewMode === 'cards' && 'is-active'"
                                    @click="setView('cards')"
                                    aria-label="نمای کارت"
                                    title="نمای کارت">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 8.25V6zM13.5 6A2.25 2.25 0 0115.75 3.75H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25a2.25 2.25 0 01-2.25-2.25v-2.25z" />
                                </svg>
                            </button>
                        </div>
                        <div id="patient-results-head" class="patient-results__head-main">
                            <?php echo $__env->make('dashboard.partials.results-head', ['patients' => $patients, 'search' => $search, 'stats' => $stats], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                    </div>
                </div>
                <div id="patient-results">
                    <?php echo $__env->make('dashboard.partials.results-body', ['patients' => $patients, 'search' => $search], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($component)) { $__componentOriginal613f849e7314f037f8e781fcd5ad8d28 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal613f849e7314f037f8e781fcd5ad8d28 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.row-toolbox-modal','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('row-toolbox-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal613f849e7314f037f8e781fcd5ad8d28)): ?>
<?php $attributes = $__attributesOriginal613f849e7314f037f8e781fcd5ad8d28; ?>
<?php unset($__attributesOriginal613f849e7314f037f8e781fcd5ad8d28); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal613f849e7314f037f8e781fcd5ad8d28)): ?>
<?php $component = $__componentOriginal613f849e7314f037f8e781fcd5ad8d28; ?>
<?php unset($__componentOriginal613f849e7314f037f8e781fcd5ad8d28); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal6f8f92d3e0a08cbfb040316ff7e2f577 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6f8f92d3e0a08cbfb040316ff7e2f577 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.answer-panel','data' => ['mobile' => '','patientName' => '']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('answer-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mobile' => '','patient-name' => '']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6f8f92d3e0a08cbfb040316ff7e2f577)): ?>
<?php $attributes = $__attributesOriginal6f8f92d3e0a08cbfb040316ff7e2f577; ?>
<?php unset($__attributesOriginal6f8f92d3e0a08cbfb040316ff7e2f577); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6f8f92d3e0a08cbfb040316ff7e2f577)): ?>
<?php $component = $__componentOriginal6f8f92d3e0a08cbfb040316ff7e2f577; ?>
<?php unset($__componentOriginal6f8f92d3e0a08cbfb040316ff7e2f577); ?>
<?php endif; ?>

    <script>
        function patientSearch(initialQ, searchUrl) {
            return {
                q: initialQ || '',
                loading: false,
                viewMode: 'cards',
                boot() {
                    try {
                        const saved = localStorage.getItem('patientListView');
                        if (saved === 'list' || saved === 'cards') this.viewMode = saved;
                    } catch (e) {}
                    this.applyView();
                    if ((this.q || '').trim()) this.search();
                },
                setView(mode) {
                    this.viewMode = mode === 'list' ? 'list' : 'cards';
                    try { localStorage.setItem('patientListView', this.viewMode); } catch (e) {}
                    this.applyView();
                },
                applyView() {
                    const root = document.getElementById('patient-results');
                    if (!root) return;
                    const cards = root.querySelector('.patient-view--cards');
                    const list = root.querySelector('.patient-view--list');
                    if (cards) cards.hidden = this.viewMode !== 'cards';
                    if (list) list.hidden = this.viewMode !== 'list';
                },
                async search() {
                    this.loading = true;
                    const url = new URL(searchUrl, window.location.origin);
                    const term = (this.q || '').trim();
                    if (term) {
                        url.searchParams.set('search', term);
                    } else {
                        url.searchParams.delete('search');
                    }
                    window.history.replaceState({}, '', url);

                    try {
                        const res = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html',
                            },
                        });
                        if (!res.ok) return;
                        const html = await res.text();
                        const temp = document.createElement('div');
                        temp.innerHTML = html;
                        const head = temp.querySelector('#patient-ajax-head');
                        const body = temp.querySelector('#patient-ajax-body');
                        const headTarget = document.getElementById('patient-results-head');
                        const bodyTarget = document.getElementById('patient-results');
                        if (head && headTarget) headTarget.innerHTML = head.innerHTML;
                        if (body && bodyTarget) bodyTarget.innerHTML = body.innerHTML;
                        this.applyView();
                    } finally {
                        this.loading = false;
                    }
                },
                clear() {
                    this.q = '';
                    this.search();
                },
            };
        }
    </script>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\dashboard.blade.php ENDPATH**/ ?>