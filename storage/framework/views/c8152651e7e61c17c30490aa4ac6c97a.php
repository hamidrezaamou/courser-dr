<?php
    $isStaff = auth()->user()->role !== 'patient';
    $canClinical = $isStaff && auth()->user()->canManageClinical();
    $isEmbed = request()->boolean('embed');
    $whiteboardEnabled = \App\Support\FeatureFlags::enabled('features.whiteboard');
    $followupEnabled = \App\Support\FeatureFlags::enabled('features.followup_reminders');
    $openRequest = request('open');
    $openMediaTab = in_array($openRequest, ['photos', 'drawings', 'exams', 'rx'], true)
        ? $openRequest
        : 'photos';
    $openPanel = session(
        'active_tab',
        ($errors->has('file') || $errors->has('type') || $errors->has('description'))
            ? 'upload'
            : ($errors->has('note') ? 'note' : (($canClinical && $errors->any()) ? 'exam' : null))
    );
    if ($openRequest === 'photos' || $openRequest === 'drawings' || $openRequest === 'exams' || $openRequest === 'rx') {
        $openPanel = 'media';
    } elseif ($openRequest === 'whiteboard' && $whiteboardEnabled) {
        // In embed/toolbox flow, whiteboard browsing uses the media tab.
        $openPanel = $isEmbed ? 'media' : 'whiteboard';
        if ($isEmbed) {
            $openMediaTab = 'drawings';
        }
    } elseif ($openRequest === 'prescription' && $canClinical) {
        $openPanel = $isEmbed ? 'media' : 'prescription';
        if ($isEmbed) {
            $openMediaTab = 'rx';
        }
    } elseif ($openRequest === 'exam' && $canClinical) {
        $openPanel = $isEmbed ? 'media' : 'exam';
        if ($isEmbed) {
            $openMediaTab = 'exams';
        }
    } elseif ($openRequest === 'postop' && $isStaff) {
        $openPanel = 'postop';
    } elseif ($openRequest === 'consent' && $isStaff && \App\Support\FeatureFlags::enabled('features.consent_forms')) {
        $openPanel = null;
    }

    $focusVisitId = (int) request('visit', 0) ?: null;
    $focusSurgeryId = (int) request('surgery', 0) ?: null;

    // Embed mode always lands on the tabbed clinical workspace.
    if ($isEmbed && $isStaff && $openPanel === null) {
        $openPanel = 'media';
    }

    $embedQuery = $isEmbed
        ? ['embed' => 1, 'open' => request('open', 'photos')]
        : [];

    $galleryPayload = $patient->medicalDocuments->values()->map(fn ($d) => [
        'src' => asset('storage/'.$d->file_path),
        'title' => $d->type,
        'date' => jalali($d->created_at, 'Y/m/d'),
        'deleteUrl' => $isStaff
            ? route('documents.destroy', array_merge([
                'patient' => $patient,
                'document' => $d,
            ], $embedQuery))
            : null,
    ])->values();

    $drawingGalleryPayload = $patient->visits->whereNotNull('drawing_path')->values()->map(fn ($v) => [
        'src' => asset('storage/'.$v->drawing_path),
        'title' => 'وایت‌برد',
        'date' => jalali($v->created_at, 'Y/m/d H:i'),
        'deleteUrl' => $canClinical
            ? route('visits.destroy', array_merge([
                'patient' => $patient,
                'visit' => $v,
            ], $embedQuery))
            : null,
        'editVisitId' => $canClinical ? $v->id : null,
    ])->values();

    $timeline = collect();

    foreach ($patient->visits as $visit) {
        $timeline->push([
            'type' => 'visit',
            'at' => $visit->created_at,
            'item' => $visit,
        ]);
    }

    foreach ($patient->medicalDocuments as $document) {
        $timeline->push([
            'type' => 'document',
            'at' => $document->created_at,
            'item' => $document,
        ]);
    }

    if ($isStaff) {
        foreach ($patient->internalNotes as $note) {
            $timeline->push([
                'type' => 'note',
                'at' => $note->created_at,
                'item' => $note,
            ]);
        }

        foreach ($patient->prescriptions ?? [] as $prescription) {
            $timeline->push([
                'type' => 'prescription',
                'at' => $prescription->created_at,
                'item' => $prescription,
            ]);
        }
    }

    foreach ($patient->appointments as $appointment) {
        $timeline->push([
            'type' => 'appointment',
            'at' => $appointment->created_at,
            'item' => $appointment,
        ]);
    }

    foreach ($patient->surgeryAppointments as $surgery) {
        $timeline->push([
            'type' => 'surgery_created',
            'at' => $surgery->created_at,
            'item' => $surgery,
        ]);

        if ($surgery->holdsSlot() && $surgery->scheduled_date) {
            $dueAt = \Carbon\Carbon::parse($surgery->scheduled_date->format('Y-m-d').' 09:00:00');
            $timeline->push([
                'type' => 'surgery_due',
                'at' => $dueAt,
                'item' => $surgery,
            ]);
        }
    }

    $timeline = $timeline->sortBy('at')->values();
    $timelineByDate = $timeline->groupBy(fn ($row) => jalali($row['at'], 'Y/m/d'));

    $visitCount = $patient->visits->count();
    $docCount = $patient->medicalDocuments->count();
    $noteCount = $isStaff ? $patient->internalNotes->count() : 0;
    $voiceCount = $patient->visits->whereNotNull('voice_path')->count();
    $drawingCount = $patient->visits->whereNotNull('drawing_path')->count();
    $activityTotal = max(1, $visitCount + $docCount + $noteCount);

    $drawingVisits = $patient->visits->whereNotNull('drawing_path')->values();
    $examVisits = $patient->visits->filter(function ($v) {
        return $v->hasExamContent();
    })->values();
    $examVisitsPayload = $examVisits->map(fn ($v) => [
        'id' => $v->id,
        'at' => jalali($v->created_at, 'Y/m/d H:i'),
        'history' => $v->history,
        'examination' => $v->examination,
        'diagnosis' => $v->diagnosis,
        'treatment' => $v->treatment,
        'next_instruction' => $v->next_instruction,
        'eye_side' => $v->eye_side,
        'va_right' => $v->va_right,
        'va_left' => $v->va_left,
        'iop_right' => $v->iop_right,
        'iop_left' => $v->iop_left,
    ])->values();
    $activeVisitId = $focusVisitId
        ?: optional($patient->visits->first(fn ($v) => $v->appointment_id || $v->surgery_appointment_id))->id;
    $rxCount = ($isStaff && isset($patient->prescriptions)) ? $patient->prescriptions->count() : 0;
    $drugsCatalog = $drugsCatalog ?? collect();
    $pendingFollowUps = $patient->followUpReminders ?? collect();
?>

<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve(['bodyClass' => $isEmbed ? 'is-patient-chat is-patient-embed' : 'is-patient-chat'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <div
        class="tg-layout"
        x-data="{
            panel: <?php echo \Illuminate\Support\Js::from($openPanel)->toHtml() ?>,
            mediaTab: <?php echo \Illuminate\Support\Js::from($openMediaTab)->toHtml() ?>,
            isEmbed: <?php echo \Illuminate\Support\Js::from($isEmbed)->toHtml() ?>,
            composerMode: 'exam',
            quickToolsOpen: false,
            editingVisitId: null,
            editingDrawingSrc: null,
            surgeryCards: {},
            examPreview: null,
            activeVisitId: <?php echo \Illuminate\Support\Js::from($activeVisitId)->toHtml() ?>,
            focusSurgeryId: <?php echo \Illuminate\Support\Js::from($focusSurgeryId)->toHtml() ?>,
            openPanel(name) {
                if (name !== 'whiteboard') {
                    this.editingVisitId = null;
                    this.editingDrawingSrc = null;
                }
                if (name === 'exam') {
                    window.dispatchEvent(new CustomEvent('exam-reset'));
                }
                this.panel = name;
                document.body.style.overflow = 'hidden';
                if (name === 'whiteboard') {
                    setTimeout(function () { window.dispatchEvent(new CustomEvent('whiteboard-open')); }, 80);
                }
            },
            openDrawingEditor(visitId, src) {
                this.editingVisitId = visitId;
                this.editingDrawingSrc = src;
                this.panel = 'whiteboard';
                document.body.style.overflow = 'hidden';
                window.dispatchEvent(new CustomEvent('whiteboard-load', { detail: { src, visitId } }));
                setTimeout(function () { window.dispatchEvent(new CustomEvent('whiteboard-open')); }, 80);
            },
            openMedia(tab) {
                this.mediaTab = tab;
                this.panel = 'media';
                document.body.style.overflow = 'hidden';
            },
            closePanel() {
                // In embed iframe: nested drawers return to media; media close asks parent to dismiss.
                if (this.isEmbed) {
                    if (this.panel && this.panel !== 'media') {
                        this.panel = 'media';
                        return;
                    }
                    if (window.parent && window.parent !== window) {
                        window.parent.postMessage({ type: 'patient-workspace-close' }, '*');
                    }
                    return;
                }
                this.panel = null;
                document.body.style.overflow = '';
            },
            jumpToItem(id) {
                this.closePanel();
                this.$nextTick(() => {
                    const el = document.getElementById(id);
                    const feed = document.getElementById('tg-feed');
                    if (el && feed) {
                        const top = el.offsetTop - feed.offsetTop - 12;
                        feed.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
                    }
                });
            },
            openPrescriptionEdit(id) {
                this.panel = 'prescription';
                document.body.style.overflow = 'hidden';
                this.$nextTick(() => window.dispatchEvent(new CustomEvent('prescription-edit', { detail: id })));
            },
            openExamEdit(id) {
                this.panel = 'exam';
                document.body.style.overflow = 'hidden';
                this.$nextTick(() => window.dispatchEvent(new CustomEvent('exam-edit', { detail: id })));
            },
            toggleSurgeryCard(key) {
                this.surgeryCards[key] = !this.surgeryCards[key];
            },
            isSurgeryCardOpen(key) {
                return !!this.surgeryCards[key];
            },
            openExamPreview(visit) {
                this.examPreview = visit || null;
                this.panel = 'exam-view';
                document.body.style.overflow = 'hidden';
            }
        }"
        x-init="
            window.PatientWorkspace = {
                openMedia: (tab) => openMedia(tab),
                openPanel: (name) => openPanel(name),
                close: () => closePanel(),
            };
            $nextTick(() => {
                const feed = document.getElementById('tg-feed');
                if (feed) feed.scrollTop = feed.scrollHeight;
            });
            if (panel) document.body.style.overflow = 'hidden';
        "
        @keydown.escape.window="if (panel) closePanel()"
        @whiteboard-edit-request.window="openDrawingEditor($event.detail.visitId, $event.detail.src)"
        @patient-workspace.window="
            const a = $event.detail && $event.detail.action;
            if (!a) return;
            if (a === 'photos' || a === 'drawings' || a === 'exams' || a === 'rx') openMedia(a);
            else if (a === 'whiteboard') openPanel('whiteboard');
            else if (a === 'prescription') openPanel('prescription');
            else openPanel(a);
        "
    >
        
        <aside class="tg-side fade-up-delay hidden lg:flex">
            <?php echo $__env->make('patients.partials.profile-sidebar', compact('patient', 'isStaff', 'canClinical', 'visitCount', 'docCount', 'noteCount', 'voiceCount', 'drawingCount', 'activityTotal', 'examVisits', 'rxCount', 'pendingFollowUps'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </aside>

        
        <section class="tg-chat fade-up">
            <header class="tg-chat__header cursor-pointer select-none" role="button" tabindex="0" title="برگشت به بالا"
                    @click="document.getElementById('tg-feed')?.scrollTo({ top: 0, behavior: 'smooth' })"
                    @keydown.enter.prevent="document.getElementById('tg-feed')?.scrollTo({ top: 0, behavior: 'smooth' })">
                <div class="flex min-w-0 items-center gap-1.5 sm:gap-3">
                    <?php if($isStaff): ?>
                        <a href="<?php echo e(route('dashboard')); ?>" class="tg-tool" title="بازگشت" @click.stop>
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    <?php endif; ?>
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[10px] font-extrabold text-white sm:h-11 sm:w-11 sm:text-sm"
                         style="background: linear-gradient(145deg, var(--brand-light), var(--brand-dark));">
                        <?php echo e(mb_substr($patient->name, 0, 1)); ?>

                    </div>
                    <div class="min-w-0">
                        <h1 class="truncate text-[11px] font-bold sm:text-base" style="color: var(--ink);">
                            <?php echo e($patient->name); ?>

                            <?php if (isset($component)) { $__componentOriginala5388e344e09a7a88b70ee1e4efb6af7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala5388e344e09a7a88b70ee1e4efb6af7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.his-badge','data' => ['model' => $patient,'class' => 'align-middle']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('his-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($patient),'class' => 'align-middle']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala5388e344e09a7a88b70ee1e4efb6af7)): ?>
<?php $attributes = $__attributesOriginala5388e344e09a7a88b70ee1e4efb6af7; ?>
<?php unset($__attributesOriginala5388e344e09a7a88b70ee1e4efb6af7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala5388e344e09a7a88b70ee1e4efb6af7)): ?>
<?php $component = $__componentOriginala5388e344e09a7a88b70ee1e4efb6af7; ?>
<?php unset($__componentOriginala5388e344e09a7a88b70ee1e4efb6af7); ?>
<?php endif; ?>
                        </h1>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-0.5" @click.stop>
                    <?php if($isStaff && $followupEnabled): ?>
                        <div class="relative" x-data="{ openFollowMenu: <?php echo \Illuminate\Support\Js::from($openPanel === 'postop')->toHtml() ?> }" @click.outside="openFollowMenu = false">
                            <button type="button" class="tg-tool" title="مراجعه بعدی" @click="openFollowMenu = !openFollowMenu">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                            <div x-show="openFollowMenu" x-cloak class="absolute left-0 z-30 mt-2 w-52 rounded-xl border p-2 shadow-lg"
                                 style="border-color: var(--line); background: var(--panel);">
                                <form method="POST" action="<?php echo e(route('followups.store', $patient)); ?>" class="space-y-2">
                                    <?php echo csrf_field(); ?>
                                    <label class="text-[11px] font-bold block" style="color: var(--ink);">مراجعه بعدی</label>
                                    <select name="days_after" class="field-input !py-1.5 !text-xs">
                                        <option value="1">فردا (۱ روز)</option>
                                        <option value="7" selected>یک هفته دیگه</option>
                                        <option value="10">۱۰ روز دیگه</option>
                                        <option value="15">۱۵ روز دیگه</option>
                                        <option value="30">یک ماه بعد</option>
                                        <option value="60">دو ماه بعد</option>
                                        <option value="90">سه ماه بعد</option>
                                    </select>
                                    <button type="submit" class="btn-primary w-full !py-1.5 !text-xs">ثبت یادآوری</button>
                                </form>
                                <div class="mt-2 border-t pt-2" style="border-color: var(--line);">
                                    <p class="mb-1 text-[10px] font-bold" style="color: var(--muted);">پس از عمل (چندتایی)</p>
                                    <form method="POST" action="<?php echo e(route('followups.store', $patient)); ?>" class="space-y-1.5">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="label_prefix" value="پس از عمل">
                                        <?php if($focusSurgeryId): ?>
                                            <input type="hidden" name="surgery_appointment_id" value="<?php echo e($focusSurgeryId); ?>">
                                        <?php endif; ?>
                                        <label class="flex items-center gap-1.5 text-[11px]"><input type="checkbox" name="intervals[]" value="1" checked> ۱ روز</label>
                                        <label class="flex items-center gap-1.5 text-[11px]"><input type="checkbox" name="intervals[]" value="7" checked> ۱ هفته</label>
                                        <label class="flex items-center gap-1.5 text-[11px]"><input type="checkbox" name="intervals[]" value="30" checked> ۱ ماه</label>
                                        <button type="submit" class="btn-secondary w-full !py-1.5 !text-xs">ثبت پیگیری پس از عمل</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <button
                        type="button"
                        class="tg-tool"
                        title="پاسخ‌های آماده"
                        data-answer-launch
                        data-answer-mobile="<?php echo e($patient->mobile); ?>"
                        data-answer-name="<?php echo e($patient->name); ?>"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3.75h9m-9 3.75H12m7.5-10.5h-15A2.25 2.25 0 002.25 7.5v9A2.25 2.25 0 004.5 18.75h15a2.25 2.25 0 002.25-2.25v-9A2.25 2.25 0 0019.5 5.25z" />
                        </svg>
                    </button>
                    <button type="button" class="tg-tool lg:hidden" title="تغییر تم"
                            onclick="(function(){var d=document.documentElement;d.classList.toggle('dark');localStorage.setItem('theme',d.classList.contains('dark')?'dark':'light');})()">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                        </svg>
                    </button>
                    <button type="button" class="tg-tool lg:hidden" @click="openPanel('profile')" aria-label="مشخصات بیمار" title="مشخصات">
                        <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                    </button>
                </div>
            </header>

            <?php if(session('success')): ?>
                <?php if (isset($component)) { $__componentOriginal5168fdb0c14fd91c6598264bc4be63f2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.flash','data' => ['type' => 'success','class' => 'mx-4 mt-3','xData' => '{ show: true }','xInit' => 'setTimeout(() => show = false, 4000)','xShow' => 'show','xTransition.opacity' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flash'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'success','class' => 'mx-4 mt-3','x-data' => '{ show: true }','x-init' => 'setTimeout(() => show = false, 4000)','x-show' => 'show','x-transition.opacity' => true]); ?><?php echo e(session('success')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2)): ?>
<?php $attributes = $__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2; ?>
<?php unset($__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5168fdb0c14fd91c6598264bc4be63f2)): ?>
<?php $component = $__componentOriginal5168fdb0c14fd91c6598264bc4be63f2; ?>
<?php unset($__componentOriginal5168fdb0c14fd91c6598264bc4be63f2); ?>
<?php endif; ?>
            <?php endif; ?>

            <div id="tg-feed" class="tg-chat__feed">
                <div class="tg-pill">شروع پرونده بیمار</div>

                <?php $__empty_1 = true; $__currentLoopData = $timelineByDate; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dateLabel => $rows): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="tg-pill"><?php echo e($dateLabel); ?></div>

                    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($row['type'] === 'visit'): ?>
                            <?php $visit = $row['item']; ?>
                            <?php
                                $examFields = collect([
                                    'history' => 'شرح حال',
                                    'examination' => 'معاینه',
                                    'diagnosis' => 'تشخیص',
                                    'treatment' => 'درمان',
                                    'next_instruction' => 'دستور بعدی',
                                    'eye_side' => 'چشم',
                                    'va_right' => 'VA راست',
                                    'va_left' => 'VA چپ',
                                    'iop_right' => 'IOP راست',
                                    'iop_left' => 'IOP چپ',
                                ])->filter(fn ($label, $field) => filled($visit->{$field}));
                                $drawingIndex = $visit->drawing_path
                                    ? $drawingVisits->search(fn ($d) => $d->id === $visit->id)
                                    : false;
                                $drawingIndex = $drawingIndex === false ? 0 : $drawingIndex;
                            ?>
                            <article id="timeline-visit-<?php echo e($visit->id); ?>" class="tg-bubble tg-bubble--staff">
                                <div class="tg-bubble__meta">
                                    <span class="tg-bubble__tag">
                                        <?php if($visit->voice_path && ! $visit->history && ! $visit->examination): ?>
                                            ویس معاینه
                                        <?php elseif($visit->drawing_path && ! $visit->history && ! $visit->examination): ?>
                                            وایت‌برد
                                        <?php else: ?>
                                            معاینه
                                        <?php endif; ?>
                                    </span>
                                    <span dir="ltr"><?php echo e(jalali($visit->created_at, 'Y/m/d H:i')); ?></span>
                                </div>

                                <?php if($visit->drawing_path): ?>
                                    <div class="relative mb-3">
                                        <button type="button" class="block w-full overflow-hidden rounded-xl text-right"
                                                onclick="PatientGallery.openDrawing(<?php echo e($drawingIndex); ?>)"
                                                title="بزرگ‌نمایی وایت‌برد">
                                            <img src="<?php echo e(asset('storage/'.$visit->drawing_path)); ?>" alt="نقاشی معاینه"
                                                 class="max-h-64 w-full rounded-xl object-contain bg-white transition hover:opacity-95">
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <?php if($visit->voice_path): ?>
                                    <div class="mb-3 rounded-xl border p-2" style="border-color: var(--line); background: var(--panel);">
                                        <div class="mb-2 flex items-center gap-2 text-xs font-semibold" style="color: var(--brand-dark);">
                                            <span class="flex items-center gap-2"><span class="pp-voice-pulse"></span>پیام صوتی</span>
                                        </div>
                                        <audio controls class="w-full">
                                            <source src="<?php echo e(asset('storage/'.$visit->voice_path)); ?>">
                                        </audio>
                                    </div>
                                <?php endif; ?>

                                <?php if($examFields->isNotEmpty()): ?>
                                    <div class="tg-exam-fields">
                                        <?php $__currentLoopData = $examFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="tg-exam-row">
                                                <div class="tg-exam-row__label"><?php echo e($label); ?></div>
                                                <div class="tg-exam-row__value"><?php echo e($visit->{$field}); ?></div>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if(! $visit->drawing_path && ! $visit->voice_path && $examFields->isEmpty()): ?>
                                    <p style="color: var(--muted);">معاینه بدون جزئیات ثبت شد.</p>
                                <?php endif; ?>

                                <?php if($canClinical): ?>
                                    <?php if (isset($component)) { $__componentOriginal0496c7dcd238f7a6420c821a372fe0ae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0496c7dcd238f7a6420c821a372fe0ae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.item-actions','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('item-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                                        <?php if($visit->drawing_path): ?>
                                            <button type="button" class="aa-chip aa-chip--zoom"
                                                    onclick="this.closest('details')?.removeAttribute('open'); PatientGallery.openDrawing(<?php echo e($drawingIndex); ?>)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14zM11 8v6m-3-3h6"/></svg>
                                                بزرگ‌نمایی
                                            </button>
                                            <button type="button" class="aa-chip aa-chip--edit"
                                                    onclick="this.closest('details')?.removeAttribute('open')"
                                                    @click="openDrawingEditor(<?php echo e($visit->id); ?>, <?php echo \Illuminate\Support\Js::from(asset('storage/'.$visit->drawing_path))->toHtml() ?>)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L8.832 17.82a4.5 4.5 0 01-1.897 1.13l-3.096.91 1.007-3.015a4.5 4.5 0 011.13-1.897L16.863 4.487z"/></svg>
                                                ویرایش
                                            </button>
                                        <?php endif; ?>
                                        <?php if($examFields->isNotEmpty()): ?>
                                            <button type="button" class="aa-chip aa-chip--view"
                                                    onclick="this.closest('details')?.removeAttribute('open')"
                                                    @click='openExamPreview(<?php echo \Illuminate\Support\Js::from([
                                                        "id" => $visit->id,
                                                        "at" => jalali($visit->created_at, "Y/m/d H:i"),
                                                        "history" => $visit->history,
                                                        "examination" => $visit->examination,
                                                        "diagnosis" => $visit->diagnosis,
                                                        "treatment" => $visit->treatment,
                                                    ])->toHtml() ?>)'>
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                مشاهده
                                            </button>
                                            <button type="button" class="aa-chip aa-chip--edit"
                                                    onclick="this.closest('details')?.removeAttribute('open')"
                                                    @click="openExamEdit(<?php echo e($visit->id); ?>)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L8.832 17.82a4.5 4.5 0 01-1.897 1.13l-3.096.91 1.007-3.015a4.5 4.5 0 011.13-1.897L16.863 4.487z"/></svg>
                                                ویرایش
                                            </button>
                                        <?php endif; ?>
                                        <form method="POST" action="<?php echo e(route('visits.destroy', [$patient, $visit])); ?>"
                                              onsubmit="return confirm('این مورد حذف شود؟')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="aa-chip aa-chip--delete">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                حذف
                                            </button>
                                        </form>
                                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0496c7dcd238f7a6420c821a372fe0ae)): ?>
<?php $attributes = $__attributesOriginal0496c7dcd238f7a6420c821a372fe0ae; ?>
<?php unset($__attributesOriginal0496c7dcd238f7a6420c821a372fe0ae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0496c7dcd238f7a6420c821a372fe0ae)): ?>
<?php $component = $__componentOriginal0496c7dcd238f7a6420c821a372fe0ae; ?>
<?php unset($__componentOriginal0496c7dcd238f7a6420c821a372fe0ae); ?>
<?php endif; ?>
                                <?php endif; ?>

                                <?php if (isset($component)) { $__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.audit-meta','data' => ['creator' => $visit->creator,'editor' => $visit->editor,'createdAt' => $visit->created_at,'updatedAt' => $visit->updated_at,'wasEdited' => $visit->wasEdited()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('audit-meta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['creator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($visit->creator),'editor' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($visit->editor),'created-at' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($visit->created_at),'updated-at' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($visit->updated_at),'was-edited' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($visit->wasEdited())]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18)): ?>
<?php $attributes = $__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18; ?>
<?php unset($__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18)): ?>
<?php $component = $__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18; ?>
<?php unset($__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18); ?>
<?php endif; ?>
                            </article>
                        <?php elseif($row['type'] === 'document'): ?>
                            <?php $document = $row['item']; $docIndex = $patient->medicalDocuments->search(fn ($d) => $d->id === $document->id); ?>
                            <article id="timeline-document-<?php echo e($document->id); ?>" class="tg-bubble tg-bubble--staff">
                                <div class="tg-bubble__meta">
                                    <span class="tg-bubble__tag">تصویر · <?php echo e($document->type); ?></span>
                                    <span dir="ltr"><?php echo e(jalali($document->created_at, 'Y/m/d H:i')); ?></span>
                                </div>
                                <button type="button" class="block w-full overflow-hidden rounded-xl text-right" onclick="PatientGallery.open(<?php echo e($docIndex === false ? 0 : $docIndex); ?>)">
                                    <img src="<?php echo e(asset('storage/'.$document->file_path)); ?>" alt="<?php echo e($document->type); ?>"
                                         class="max-h-56 w-full object-cover transition hover:opacity-95">
                                </button>
                                <?php if($document->description): ?>
                                    <p class="mt-2 text-xs" style="color: var(--muted);"><?php echo e($document->description); ?></p>
                                <?php endif; ?>
                                <?php if (isset($component)) { $__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.audit-meta','data' => ['creator' => $document->creator,'createdAt' => $document->created_at]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('audit-meta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['creator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($document->creator),'created-at' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($document->created_at)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18)): ?>
<?php $attributes = $__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18; ?>
<?php unset($__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18)): ?>
<?php $component = $__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18; ?>
<?php unset($__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18); ?>
<?php endif; ?>
                            </article>
                        <?php elseif($row['type'] === 'prescription'): ?>
                            <?php $prescription = $row['item']; ?>
                            <article id="timeline-prescription-<?php echo e($prescription->id); ?>" class="tg-bubble tg-bubble--staff">
                                <div class="tg-bubble__meta">
                                    <span class="tg-bubble__tag" style="background:#ecfdf5;color:#047857;">نسخه دارو</span>
                                    <span dir="ltr"><?php echo e(jalali($prescription->created_at, 'Y/m/d H:i')); ?></span>
                                </div>
                                <div class="space-y-2 text-xs">
                                    <?php $__currentLoopData = $prescription->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="rounded-lg border px-2.5 py-2" style="border-color: var(--line); background: var(--panel);">
                                            <div class="font-bold" style="color: var(--ink);"><?php echo e($item->drug_name); ?></div>
                                            <div class="mt-1 space-y-0.5" style="color: var(--muted);">
                                                <?php if($item->usage_type): ?><div>نوع: <?php echo e($item->usage_type); ?></div><?php endif; ?>
                                                <?php if($item->dosage): ?><div>دوز: <?php echo e($item->dosage); ?></div><?php endif; ?>
                                                <?php if($item->frequency): ?><div>دفعات: <?php echo e($item->frequency); ?></div><?php endif; ?>
                                                <?php if($item->meal_timing): ?><div>زمان: <?php echo e($item->mealTimingLabel()); ?></div><?php endif; ?>
                                                <?php if($item->duration): ?><div>مدت: <?php echo e($item->duration); ?></div><?php endif; ?>
                                                <?php if($item->instructions): ?><div><?php echo e($item->instructions); ?></div><?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                                <?php if($prescription->notes): ?>
                                    <p class="mt-2 text-xs" style="color: var(--muted);"><?php echo e($prescription->notes); ?></p>
                                <?php endif; ?>
                                <?php if($isStaff): ?>
                                    <?php if (isset($component)) { $__componentOriginal0496c7dcd238f7a6420c821a372fe0ae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0496c7dcd238f7a6420c821a372fe0ae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.item-actions','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('item-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                                        <a href="<?php echo e(route('prescriptions.print', [$patient, $prescription])); ?>" target="_blank" class="aa-chip">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            چاپ نسخه
                                        </a>
                                        <?php if($canClinical): ?>
                                        <button type="button" class="aa-chip aa-chip--edit"
                                                onclick="this.closest('details')?.removeAttribute('open')"
                                                @click="openPrescriptionEdit(<?php echo e($prescription->id); ?>)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L8.832 17.82a4.5 4.5 0 01-1.897 1.13l-3.096.91 1.007-3.015a4.5 4.5 0 011.13-1.897L16.863 4.487z"/></svg>
                                            ویرایش
                                        </button>
                                        <form method="POST" action="<?php echo e(route('prescriptions.destroy', [$patient, $prescription])); ?>"
                                              onsubmit="return confirm('این نسخه حذف شود؟')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="aa-chip aa-chip--delete">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                حذف
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0496c7dcd238f7a6420c821a372fe0ae)): ?>
<?php $attributes = $__attributesOriginal0496c7dcd238f7a6420c821a372fe0ae; ?>
<?php unset($__attributesOriginal0496c7dcd238f7a6420c821a372fe0ae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0496c7dcd238f7a6420c821a372fe0ae)): ?>
<?php $component = $__componentOriginal0496c7dcd238f7a6420c821a372fe0ae; ?>
<?php unset($__componentOriginal0496c7dcd238f7a6420c821a372fe0ae); ?>
<?php endif; ?>
                                <?php endif; ?>
                                <?php if (isset($component)) { $__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.audit-meta','data' => ['creator' => $prescription->creator,'editor' => $prescription->editor,'createdAt' => $prescription->created_at,'updatedAt' => $prescription->updated_at,'wasEdited' => $prescription->updated_by !== null]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('audit-meta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['creator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($prescription->creator),'editor' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($prescription->editor),'created-at' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($prescription->created_at),'updated-at' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($prescription->updated_at),'was-edited' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($prescription->updated_by !== null)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18)): ?>
<?php $attributes = $__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18; ?>
<?php unset($__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18)): ?>
<?php $component = $__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18; ?>
<?php unset($__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18); ?>
<?php endif; ?>
                            </article>
                        <?php elseif($row['type'] === 'note'): ?>
                            <?php $note = $row['item']; ?>
                            <article class="tg-bubble" x-data="{ editing: false }">
                                <div class="tg-bubble__meta">
                                    <span class="tg-bubble__tag" style="background: color-mix(in srgb, var(--warn) 15%, transparent); color: var(--warn);">یادداشت داخلی</span>
                                    <span><?php echo e($note->user->name ?? 'کاربر'); ?> · <span dir="ltr"><?php echo e(jalali($note->created_at, 'Y/m/d H:i')); ?></span></span>
                                </div>
                                <div x-show="!editing" class="whitespace-pre-line text-sm leading-7"><?php echo e($note->note); ?></div>
                                <form method="POST" action="<?php echo e(route('notes.update', [$patient, $note])); ?>" class="space-y-2" x-show="editing" x-cloak>
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PUT'); ?>
                                    <textarea name="note" rows="3" class="field-input" x-ref="noteInput"><?php echo e($note->note); ?></textarea>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="submit" class="aa-chip aa-chip--save">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                            ذخیره یادداشت
                                        </button>
                                        <button type="button" class="aa-chip aa-chip--cancel" @click="editing = false">انصراف</button>
                                    </div>
                                </form>
                                <div x-show="!editing">
                                    <?php if (isset($component)) { $__componentOriginal0496c7dcd238f7a6420c821a372fe0ae = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0496c7dcd238f7a6420c821a372fe0ae = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.item-actions','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('item-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                                        <button type="button" class="aa-chip aa-chip--edit"
                                                onclick="this.closest('details')?.removeAttribute('open')"
                                                @click="editing = true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L8.832 17.82a4.5 4.5 0 01-1.897 1.13l-3.096.91 1.007-3.015a4.5 4.5 0 011.13-1.897L16.863 4.487z"/></svg>
                                            ویرایش
                                        </button>
                                        <form method="POST" action="<?php echo e(route('notes.destroy', [$patient, $note])); ?>"
                                              onsubmit="return confirm('یادداشت حذف شود؟')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="aa-chip aa-chip--delete">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                حذف
                                            </button>
                                        </form>
                                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0496c7dcd238f7a6420c821a372fe0ae)): ?>
<?php $attributes = $__attributesOriginal0496c7dcd238f7a6420c821a372fe0ae; ?>
<?php unset($__attributesOriginal0496c7dcd238f7a6420c821a372fe0ae); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0496c7dcd238f7a6420c821a372fe0ae)): ?>
<?php $component = $__componentOriginal0496c7dcd238f7a6420c821a372fe0ae; ?>
<?php unset($__componentOriginal0496c7dcd238f7a6420c821a372fe0ae); ?>
<?php endif; ?>
                                </div>
                            </article>
                        <?php elseif($row['type'] === 'appointment'): ?>
                            <?php
                                $appointment = $row['item'];
                                $vKey = 'visit-'.$appointment->id;
                                $vClosed = in_array($appointment->status, ['done', 'cancelled'], true);
                            ?>
                            <article class="tg-bubble tg-bubble--staff !p-0 !border-0 !bg-transparent !shadow-none evt-wrap">
                                <div class="evt-card evt-card--visit <?php if($appointment->status === 'done'): ?> evt-card--done <?php elseif($appointment->status === 'cancelled'): ?> evt-card--cancelled <?php endif; ?>"
                                     :class="{ 'is-open': isSurgeryCardOpen(<?php echo \Illuminate\Support\Js::from($vKey)->toHtml() ?>) }">
                                    <button type="button" class="evt-card__bar"
                                            @click="toggleSurgeryCard(<?php echo \Illuminate\Support\Js::from($vKey)->toHtml() ?>)"
                                            :aria-expanded="isSurgeryCardOpen(<?php echo \Illuminate\Support\Js::from($vKey)->toHtml() ?>) ? 'true' : 'false'">
                                        <span class="evt-card__icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 18.75V7.5a2.25 2.25 0 012.25-2.25h12a2.25 2.25 0 012.25 2.25v11.25m-16.5 0A2.25 2.25 0 006 21h12a2.25 2.25 0 002.25-2.25m-16.5 0V10.5h16.5v8.25"/>
                                                <path stroke-linecap="round" d="M8.25 14.25h3"/>
                                            </svg>
                                        </span>
                                        <div class="evt-card__bar-main">
                                            <span class="evt-card__kicker"><i class="evt-card__kicker-dot"></i> نوبت ویزیت</span>
                                            <span class="evt-card__title"><?php echo e($appointment->patient_name); ?></span>
                                            <span class="evt-card__sub evt-card__sub--bar">
                                                <?php echo e($appointment->visit_type ?: 'ویزیت'); ?><?php if($appointment->reason): ?> · <?php echo e($appointment->reason); ?><?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="evt-card__bar-side">
                                            <span class="evt-tag evt-tag--status-<?php echo e($appointment->status); ?>"><?php echo e(\App\Support\BookingStatus::label($appointment->status)); ?></span>
                                            <span class="evt-card__bar-when" dir="ltr">
                                                <?php echo e(jalali($appointment->scheduled_date, 'Y/m/d')); ?>

                                                · <?php echo e($appointment->scheduled_time ? \App\Support\SlotLabel::display((string) $appointment->scheduled_time) : '—'); ?>

                                            </span>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="evt-card__chevron" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                            </svg>
                                        </div>
                                    </button>

                                    <div class="evt-card__drawer" x-show="isSurgeryCardOpen(<?php echo \Illuminate\Support\Js::from($vKey)->toHtml() ?>)" x-cloak>
                                        <div class="evt-when">
                                            <div class="evt-when__group">
                                                <div class="evt-when__item">
                                                    <span class="evt-when__label">تاریخ نوبت</span>
                                                    <span class="evt-when__value" dir="ltr"><?php echo e(jalali($appointment->scheduled_date, 'Y/m/d')); ?></span>
                                                </div>
                                                <div class="evt-when__item">
                                                    <span class="evt-when__label">ساعت</span>
                                                    <span class="evt-when__value"><?php echo e($appointment->scheduled_time ? \App\Support\SlotLabel::display((string) $appointment->scheduled_time) : '—'); ?></span>
                                                </div>
                                                <div class="evt-when__item">
                                                    <span class="evt-when__label">ثبت</span>
                                                    <span class="evt-when__value" dir="ltr" style="font-size:.78rem"><?php echo e(jalali($appointment->created_at, 'Y/m/d H:i')); ?></span>
                                                </div>
                                                <div class="evt-when__item">
                                                    <span class="evt-when__label">ثبت‌کننده</span>
                                                    <span class="evt-when__value" style="font-size:.78rem"><?php echo e($appointment->creator?->name ?: ($appointment->isFromHis() ? 'HIS' : '—')); ?></span>
                                                </div>
                                            </div>
                                            <?php if (! ($vClosed)): ?>
                                                <?php if (isset($component)) { $__componentOriginalff2895b2642a610cc01fe86a6a482898 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalff2895b2642a610cc01fe86a6a482898 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.day-countdown','data' => ['date' => $appointment->scheduled_date]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('day-countdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['date' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($appointment->scheduled_date)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalff2895b2642a610cc01fe86a6a482898)): ?>
<?php $attributes = $__attributesOriginalff2895b2642a610cc01fe86a6a482898; ?>
<?php unset($__attributesOriginalff2895b2642a610cc01fe86a6a482898); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalff2895b2642a610cc01fe86a6a482898)): ?>
<?php $component = $__componentOriginalff2895b2642a610cc01fe86a6a482898; ?>
<?php unset($__componentOriginalff2895b2642a610cc01fe86a6a482898); ?>
<?php endif; ?>
                                            <?php endif; ?>
                                        </div>

                                        <dl class="evt-facts">
                                            <div class="evt-facts__row">
                                                <dt>کد ملی</dt>
                                                <dd dir="ltr"><?php echo e($appointment->national_code ?: '—'); ?></dd>
                                            </div>
                                            <div class="evt-facts__row">
                                                <dt>موبایل</dt>
                                                <dd dir="ltr">
                                                    <?php if($appointment->mobile): ?>
                                                        <a href="tel:<?php echo e($appointment->mobile); ?>" class="hover:underline"><?php echo e($appointment->mobile); ?></a>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </dd>
                                            </div>
                                            <?php if($appointment->notes): ?>
                                                <div class="evt-facts__row evt-facts__row--full">
                                                    <dt>یادداشت</dt>
                                                    <dd><?php echo e($appointment->notes); ?></dd>
                                                </div>
                                            <?php endif; ?>
                                        </dl>

                                        <div class="evt-card__foot">
                                            <?php if (isset($component)) { $__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.audit-meta','data' => ['creator' => $appointment->creator,'createdAt' => $appointment->created_at]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('audit-meta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['creator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($appointment->creator),'created-at' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($appointment->created_at)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18)): ?>
<?php $attributes = $__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18; ?>
<?php unset($__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18)): ?>
<?php $component = $__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18; ?>
<?php unset($__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18); ?>
<?php endif; ?>
                                            <?php if (isset($component)) { $__componentOriginal8d75880d81198efb280b50e0bab6c1de = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8d75880d81198efb280b50e0bab6c1de = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.booking-status-actions','data' => ['model' => $appointment,'type' => 'visit','showStatus' => false]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('booking-status-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($appointment),'type' => 'visit','show-status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8d75880d81198efb280b50e0bab6c1de)): ?>
<?php $attributes = $__attributesOriginal8d75880d81198efb280b50e0bab6c1de; ?>
<?php unset($__attributesOriginal8d75880d81198efb280b50e0bab6c1de); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8d75880d81198efb280b50e0bab6c1de)): ?>
<?php $component = $__componentOriginal8d75880d81198efb280b50e0bab6c1de; ?>
<?php unset($__componentOriginal8d75880d81198efb280b50e0bab6c1de); ?>
<?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php elseif($row['type'] === 'surgery_created' || $row['type'] === 'surgery_due'): ?>
                            <?php
                                $surgery = $row['item'];
                                $isDue = $row['type'] === 'surgery_due';
                                $sKey = $row['type'].'-'.$surgery->id.'-'.jalali($row['at'], 'Ymd');
                                $sClosed = in_array($surgery->status, ['done', 'cancelled'], true);
                            ?>
                            <article class="tg-bubble tg-bubble--staff !p-0 !border-0 !bg-transparent !shadow-none evt-wrap">
                                <div class="evt-card <?php echo e($isDue ? 'evt-card--due' : 'evt-card--surgery'); ?> <?php if($surgery->status === 'done'): ?> evt-card--done <?php elseif($surgery->status === 'cancelled'): ?> evt-card--cancelled <?php endif; ?>"
                                     :class="{ 'is-open': isSurgeryCardOpen(<?php echo \Illuminate\Support\Js::from($sKey)->toHtml() ?>) }">
                                    <button type="button" class="evt-card__bar"
                                            @click="toggleSurgeryCard(<?php echo \Illuminate\Support\Js::from($sKey)->toHtml() ?>)"
                                            :aria-expanded="isSurgeryCardOpen(<?php echo \Illuminate\Support\Js::from($sKey)->toHtml() ?>) ? 'true' : 'false'">
                                        <span class="evt-card__icon" aria-hidden="true">
                                            <?php if($isDue): ?>
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                                </svg>
                                            <?php else: ?>
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L4.2 15.3"/>
                                                </svg>
                                            <?php endif; ?>
                                        </span>
                                        <div class="evt-card__bar-main">
                                            <span class="evt-card__kicker"><i class="evt-card__kicker-dot"></i> <?php echo e($isDue ? 'سررسید عمل' : 'ثبت نوبت عمل'); ?></span>
                                            <span class="evt-card__title"><?php echo e($surgery->surgery_type); ?></span>
                                            <span class="evt-card__sub evt-card__sub--bar">
                                                <?php if($surgery->eye_side): ?> <?php echo e($surgery->eye_side); ?> <?php endif; ?>
                                                <?php if($surgery->hospital): ?> <?php if($surgery->eye_side): ?> · <?php endif; ?> <?php echo e($surgery->hospital->name); ?> <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="evt-card__bar-side">
                                            <span class="evt-tag evt-tag--status-<?php echo e($surgery->status); ?>"><?php echo e(\App\Support\BookingStatus::label($surgery->status)); ?></span>
                                            <?php if($surgery->is_emergency): ?>
                                                <span class="evt-tag evt-tag--emergency">اورژانس</span>
                                            <?php endif; ?>
                                            <span class="evt-card__bar-when" dir="ltr"><?php echo e(jalali($surgery->scheduled_date, 'Y/m/d')); ?></span>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="evt-card__chevron" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                            </svg>
                                        </div>
                                    </button>

                                    <div class="evt-card__drawer" x-show="isSurgeryCardOpen(<?php echo \Illuminate\Support\Js::from($sKey)->toHtml() ?>)" x-cloak>
                                        <div class="evt-tags">
                                            <?php if($surgery->is_exception): ?>
                                                <span class="evt-tag evt-tag--exception">استثنا</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="evt-when">
                                            <div class="evt-when__group">
                                                <div class="evt-when__item">
                                                    <span class="evt-when__label">تاریخ عمل</span>
                                                    <span class="evt-when__value" dir="ltr"><?php echo e(jalali($surgery->scheduled_date, 'Y/m/d')); ?></span>
                                                </div>
                                                <?php if($surgery->scheduled_time): ?>
                                                    <div class="evt-when__item">
                                                        <span class="evt-when__label">ساعت</span>
                                                        <span class="evt-when__value"><?php echo e(\App\Support\SlotLabel::display((string) $surgery->scheduled_time)); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if($surgery->surgeon_name): ?>
                                                    <div class="evt-when__item">
                                                        <span class="evt-when__label">جراح</span>
                                                        <span class="evt-when__value" style="font-size:.78rem"><?php echo e($surgery->surgeon_name); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (! ($isDue)): ?>
                                                    <div class="evt-when__item">
                                                        <span class="evt-when__label">ثبت</span>
                                                        <span class="evt-when__value" dir="ltr" style="font-size:.78rem"><?php echo e(jalali($surgery->created_at, 'Y/m/d H:i')); ?></span>
                                                    </div>
                                                    <div class="evt-when__item">
                                                        <span class="evt-when__label">ثبت‌کننده</span>
                                                        <span class="evt-when__value" style="font-size:.78rem"><?php echo e($surgery->creator?->name ?: ($surgery->isFromHis() ? 'HIS' : '—')); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (! ($sClosed)): ?>
                                                <?php if (isset($component)) { $__componentOriginalff2895b2642a610cc01fe86a6a482898 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalff2895b2642a610cc01fe86a6a482898 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.day-countdown','data' => ['date' => $surgery->scheduled_date]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('day-countdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['date' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($surgery->scheduled_date)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalff2895b2642a610cc01fe86a6a482898)): ?>
<?php $attributes = $__attributesOriginalff2895b2642a610cc01fe86a6a482898; ?>
<?php unset($__attributesOriginalff2895b2642a610cc01fe86a6a482898); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalff2895b2642a610cc01fe86a6a482898)): ?>
<?php $component = $__componentOriginalff2895b2642a610cc01fe86a6a482898; ?>
<?php unset($__componentOriginalff2895b2642a610cc01fe86a6a482898); ?>
<?php endif; ?>
                                            <?php endif; ?>
                                        </div>

                                        <dl class="evt-facts">
                                            <?php if($surgery->hospital): ?>
                                                <div class="evt-facts__row">
                                                    <dt>بیمارستان</dt>
                                                    <dd><?php echo e($surgery->hospital->name); ?></dd>
                                                </div>
                                            <?php endif; ?>
                                            <div class="evt-facts__row">
                                                <dt>موبایل</dt>
                                                <dd dir="ltr">
                                                    <?php if($surgery->mobile): ?>
                                                        <a href="tel:<?php echo e($surgery->mobile); ?>" class="hover:underline"><?php echo e($surgery->mobile); ?></a>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </dd>
                                            </div>
                                            <?php if($surgery->notes): ?>
                                                <div class="evt-facts__row evt-facts__row--full">
                                                    <dt>یادداشت</dt>
                                                    <dd><?php echo e($surgery->notes); ?></dd>
                                                </div>
                                            <?php endif; ?>
                                        </dl>

                                        <?php if(\App\Support\SurgeryChecklist::isAvailable()): ?>
                                            <?php echo $__env->make('patients.partials.surgery-checklist', ['checklist' => $surgery->checklist], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                        <?php endif; ?>

                                        <div class="evt-card__foot">
                                            <?php if (! ($isDue)): ?>
                                                <?php if (isset($component)) { $__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.audit-meta','data' => ['creator' => $surgery->creator,'createdAt' => $surgery->created_at]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('audit-meta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['creator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($surgery->creator),'created-at' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($surgery->created_at)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18)): ?>
<?php $attributes = $__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18; ?>
<?php unset($__attributesOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18)): ?>
<?php $component = $__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18; ?>
<?php unset($__componentOriginal7eaa3f2bafe4fa80dbf7cd0d30806c18); ?>
<?php endif; ?>
                                            <?php endif; ?>
                                            <?php if (isset($component)) { $__componentOriginal8d75880d81198efb280b50e0bab6c1de = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8d75880d81198efb280b50e0bab6c1de = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.booking-status-actions','data' => ['model' => $surgery,'type' => 'surgery','showStatus' => false]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('booking-status-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['model' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($surgery),'type' => 'surgery','show-status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8d75880d81198efb280b50e0bab6c1de)): ?>
<?php $attributes = $__attributesOriginal8d75880d81198efb280b50e0bab6c1de; ?>
<?php unset($__attributesOriginal8d75880d81198efb280b50e0bab6c1de); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8d75880d81198efb280b50e0bab6c1de)): ?>
<?php $component = $__componentOriginal8d75880d81198efb280b50e0bab6c1de; ?>
<?php unset($__componentOriginal8d75880d81198efb280b50e0bab6c1de); ?>
<?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="tg-bubble tg-bubble--system">
                        هنوز چیزی در پرونده ثبت نشده. از نوار پایین معاینه، ویس یا تصویر اضافه کنید.
                    </div>
                <?php endif; ?>
            </div>

            
            <?php if($canClinical): ?>
                <div class="tg-composer">
                    <form
                        method="POST"
                        :action="composerMode === 'note' ? <?php echo \Illuminate\Support\Js::from(route('notes.store', $patient))->toHtml() ?> : <?php echo \Illuminate\Support\Js::from(route('visits.store', $patient))->toHtml() ?>"
                        class="space-y-2"
                    >
                        <?php echo csrf_field(); ?>
                        <div class="tg-composer__bar">
                            <div class="tg-composer__tools tg-composer__tools--fab" aria-label="ابزارها">
                                <button type="button" class="tg-tool tg-tool--voice" title="ضبط ویس" id="start-voice-btn" aria-label="ضبط ویس">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
                                    </svg>
                                </button>
                                <button type="button" class="tg-tool tg-tool--voice hidden" id="stop-voice-btn" title="پایان ضبط" aria-label="پایان ضبط">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
                                </button>
                                <div class="tg-tools-menu-wrap" @click.outside="quickToolsOpen = false">
                                    <button type="button" class="tg-tool tg-tool--menu" title="ابزارهای بیشتر" aria-label="ابزارهای بیشتر"
                                            @click="quickToolsOpen = !quickToolsOpen">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                    </button>
                                    <div class="tg-tools-menu" x-show="quickToolsOpen" x-cloak x-transition.origin.bottom.right>
                                        <button type="button" class="tg-tool" title="آپلود تصویر" aria-label="آپلود تصویر"
                                                @click="quickToolsOpen = false; openPanel('upload')">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                                            </svg>
                                        </button>
                                        <?php if($whiteboardEnabled): ?>
                                        <button type="button" class="tg-tool" title="وایت‌برد" aria-label="وایت‌برد"
                                                @click="quickToolsOpen = false; openPanel('whiteboard')">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-10.94.546 10.94-.546a4.5 4.5 0 001.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                        <button type="button" class="tg-tool" title="فرم کامل معاینه" aria-label="فرم کامل معاینه"
                                                @click="quickToolsOpen = false; openPanel('exam')">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </button>
                                        <button type="button" class="tg-tool" :class="composerMode === 'note' && 'is-active'"
                                                :title="composerMode === 'note' ? 'حالت یادداشت داخلی' : 'حالت معاینه متنی'"
                                                @click="quickToolsOpen = false; composerMode = composerMode === 'note' ? 'exam' : 'note'" aria-label="تغییر حالت نوشتار">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.042.806a1.125 1.125 0 01-1.37-1.37l.806-2.042a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <textarea
                                name="examination"
                                x-bind:name="composerMode === 'note' ? 'note' : 'examination'"
                                rows="1"
                                required
                                class="tg-composer__input"
                                :placeholder="composerMode === 'note' ? 'یادداشت داخلی...' : 'یادداشت کوتاه معاینه...'"
                            ><?php echo e(old('note', old('examination'))); ?></textarea>

                            <button type="submit" class="tg-composer__send" title="ارسال" aria-label="ارسال">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                                </svg>
                            </button>
                        </div>
                        <p id="voice-status" class="text-[11px]" style="color: var(--muted);"></p>
                        <div id="voice-preview" class="hidden rounded-xl border p-2 text-xs" style="border-color: var(--line); background: var(--panel-soft);">
                            <p class="mb-2 font-bold" style="color: var(--ink);">پیش‌نمایش ویس — ارسال شود؟</p>
                            <audio id="voice-preview-audio" controls class="mb-2 w-full"></audio>
                            <div class="flex gap-2">
                                <button type="button" class="btn-primary !py-1.5 !text-xs" id="send-voice-btn">ارسال ویس</button>
                                <button type="button" class="btn-secondary !py-1.5 !text-xs" id="discard-voice-btn">حذف</button>
                            </div>
                        </div>
                        <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('examination')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('examination'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('note')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('note'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                    </form>
                </div>
            <?php elseif($isStaff): ?>
                <div class="tg-composer">
                    <div class="tg-composer-actions">
                        <button type="button" class="btn-secondary" @click="openPanel('upload')">آپلود تصویر پزشکی</button>
                        <a href="<?php echo e(route('appointments.create', $patient)); ?>" class="btn-secondary">ثبت ویزیت</a>
                        <a href="<?php echo e(route('surgery-appointments.create', $patient)); ?>" class="btn-secondary">ثبت نوبت عمل</a>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        
            <div class="tg-drawer lg:hidden" x-show="panel === 'profile'" x-cloak @click.self="closePanel()" x-transition.opacity>
            <div class="tg-drawer__panel">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <div class="h-1.5 w-10 shrink-0 rounded-full sm:hidden" style="background: var(--line);"></div>
                        <h3 class="truncate font-bold" style="color: var(--ink);">مشخصات بیمار</h3>
                    </div>
                    <button type="button" class="tg-tool shrink-0" @click="closePanel()" aria-label="بستن"><?php if (isset($component)) { $__componentOriginal3baa94417da9f5dcb14f5f04da758571 = $component; } ?>
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
                </div>
                <?php echo $__env->make('patients.partials.profile-sidebar', compact('patient', 'isStaff', 'canClinical', 'visitCount', 'docCount', 'noteCount', 'voiceCount', 'drawingCount', 'activityTotal', 'examVisits', 'rxCount', 'pendingFollowUps'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>

        
        <?php if($isStaff): ?>
        <div class="tg-drawer" x-show="panel === 'media'" x-cloak @click.self="closePanel()">
            <div class="tg-drawer__panel max-w-xl">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <div class="tg-media-tabs">
                        <button type="button" class="tg-media-tab" :class="mediaTab === 'photos' && 'is-active'" @click="mediaTab = 'photos'">عکس</button>
                        <button type="button" class="tg-media-tab" :class="mediaTab === 'drawings' && 'is-active'" @click="mediaTab = 'drawings'">وایت‌برد</button>
                        <button type="button" class="tg-media-tab" :class="mediaTab === 'exams' && 'is-active'" @click="mediaTab = 'exams'">معاینه</button>
                        <button type="button" class="tg-media-tab" :class="mediaTab === 'rx' && 'is-active'" @click="mediaTab = 'rx'">دارو</button>
                    </div>
                    <button type="button" class="tg-tool shrink-0" @click="closePanel()"><?php if (isset($component)) { $__componentOriginal3baa94417da9f5dcb14f5f04da758571 = $component; } ?>
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
                </div>

                <div x-show="mediaTab === 'photos'" x-cloak>
                    <?php if($canClinical): ?>
                        <button type="button" class="btn-secondary mb-3 w-full !py-2 !text-xs" @click="openPanel('upload')">+ افزودن عکس</button>
                    <?php endif; ?>
                    <?php if($patient->medicalDocuments->isEmpty()): ?>
                        <p class="pp-empty text-sm">هنوز عکسی ثبت نشده.</p>
                    <?php else: ?>
                        <div class="tg-media-grid">
                            <?php $__currentLoopData = $patient->medicalDocuments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $docIndex => $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <button type="button" class="tg-media-thumb" onclick="PatientGallery.open(<?php echo e($docIndex); ?>)" title="<?php echo e($document->type); ?>">
                                    <img src="<?php echo e(asset('storage/'.$document->file_path)); ?>" alt="<?php echo e($document->type); ?>">
                                    <span class="tg-media-thumb__meta" dir="ltr"><?php echo e(jalali($document->created_at, 'Y/m/d')); ?></span>
                                </button>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div x-show="mediaTab === 'drawings'" x-cloak>
                    <?php if($canClinical && $whiteboardEnabled): ?>
                        <button type="button" class="btn-secondary mb-3 w-full !py-2 !text-xs" @click="openPanel('whiteboard')">+ وایت‌برد جدید</button>
                    <?php endif; ?>
                    <?php if($drawingVisits->isEmpty()): ?>
                        <p class="pp-empty text-sm">وایت‌بردی ثبت نشده.</p>
                    <?php else: ?>
                        <div class="tg-media-grid">
                            <?php $__currentLoopData = $drawingVisits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $drawingIndex => $drawing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <button type="button" class="tg-media-thumb" onclick="PatientGallery.openDrawing(<?php echo e($drawingIndex); ?>)" title="بزرگ‌نمایی وایت‌برد">
                                    <img src="<?php echo e(asset('storage/'.$drawing->drawing_path)); ?>" alt="وایت‌برد">
                                    <span class="tg-media-thumb__meta" dir="ltr"><?php echo e(jalali($drawing->created_at, 'Y/m/d H:i')); ?></span>
                                </button>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div x-show="mediaTab === 'exams'" x-cloak>
                    <?php if($canClinical): ?>
                        <button type="button" class="btn-secondary mb-3 w-full !py-2 !text-xs" @click="openPanel('exam')">+ معاینه جدید</button>
                    <?php endif; ?>
                    <?php if($examVisits->isEmpty()): ?>
                        <p class="pp-empty text-sm">معاینه متنی ثبت نشده.</p>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php $__currentLoopData = $examVisits->sortByDesc('created_at'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $examVisit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $preview = $examVisit->examination ?: $examVisit->diagnosis ?: $examVisit->history ?: $examVisit->treatment;
                                    $preview = \Illuminate\Support\Str::limit((string) $preview, 80);
                                ?>
                                <button type="button" class="tg-media-list-item"
                                        @click='openExamPreview(<?php echo \Illuminate\Support\Js::from([
                                            "id" => $examVisit->id,
                                            "at" => jalali($examVisit->created_at, "Y/m/d H:i"),
                                            "history" => $examVisit->history,
                                            "examination" => $examVisit->examination,
                                            "diagnosis" => $examVisit->diagnosis,
                                            "treatment" => $examVisit->treatment,
                                        ])->toHtml() ?>)'>
                                    <span class="tg-media-list-item__date" dir="ltr"><?php echo e(jalali($examVisit->created_at, 'Y/m/d H:i')); ?></span>
                                    <span class="tg-media-list-item__text"><?php echo e($preview ?: 'معاینه'); ?></span>
                                </button>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div x-show="mediaTab === 'rx'" x-cloak>
                    <?php if($canClinical): ?>
                        <button type="button" class="btn-secondary mb-3 w-full !py-2 !text-xs" @click="openPanel('prescription')">+ نسخه جدید</button>
                    <?php endif; ?>
                    <?php if(($patient->prescriptions ?? collect())->isEmpty()): ?>
                        <p class="pp-empty text-sm">نسخه‌ای ثبت نشده.</p>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php $__currentLoopData = $patient->prescriptions ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="rounded-xl border p-2" style="border-color: var(--line); background: var(--panel-soft);">
                                    <button type="button" class="tg-media-list-item !border-0 !bg-transparent !p-0 w-full"
                                            @click="isEmbed ? openPrescriptionEdit(<?php echo e($rx->id); ?>) : jumpToItem('timeline-prescription-<?php echo e($rx->id); ?>')">
                                        <span class="tg-media-list-item__date" dir="ltr"><?php echo e(jalali($rx->created_at, 'Y/m/d H:i')); ?></span>
                                        <span class="tg-media-list-item__text"><?php echo e($rx->items->pluck('drug_name')->join(' · ')); ?></span>
                                    </button>
                                    <a href="<?php echo e(route('prescriptions.print', [$patient, $rx])); ?>" target="_blank" class="btn-secondary mt-2 inline-flex !px-3 !py-1.5 !text-[11px]">
                                        چاپ نسخه
                                    </a>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="tg-drawer" x-show="panel === 'exam-view'" x-cloak @click.self="closePanel()">
            <div class="tg-drawer__panel max-w-3xl">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <div>
                        <h3 class="font-bold" style="color: var(--ink);">نمایش معاینه</h3>
                        <p class="text-xs" style="color: var(--muted);" dir="ltr" x-text="examPreview?.at || ''"></p>
                    </div>
                    <button type="button" class="tg-tool shrink-0" @click="closePanel()"><?php if (isset($component)) { $__componentOriginal3baa94417da9f5dcb14f5f04da758571 = $component; } ?>
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
                </div>
                <div class="space-y-3 text-sm">
                    <template x-for="field in [
                        {key:'history', label:'شرح حال'},
                        {key:'examination', label:'معاینه'},
                        {key:'diagnosis', label:'تشخیص'},
                        {key:'treatment', label:'درمان'},
                        {key:'next_instruction', label:'دستور بعدی'},
                        {key:'eye_side', label:'چشم'},
                        {key:'va_right', label:'VA راست'},
                        {key:'va_left', label:'VA چپ'},
                        {key:'iop_right', label:'IOP راست'},
                        {key:'iop_left', label:'IOP چپ'}
                    ]" :key="field.key">
                        <div x-show="examPreview && examPreview[field.key]" class="rounded-xl border p-3" style="border-color: var(--line); background: var(--panel-soft);">
                            <div class="mb-1 text-xs font-bold" style="color: var(--brand-dark);" x-text="field.label"></div>
                            <div class="whitespace-pre-line leading-7" x-text="examPreview[field.key]"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <?php if($isStaff): ?>
            <?php if($canClinical): ?>
            <?php echo $__env->make('patients.partials.prescription-drawer', ['patient' => $patient, 'drugsCatalog' => $drugsCatalog], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            
            <div class="tg-drawer" x-show="panel === 'exam'" x-cloak @click.self="closePanel()">
                <div class="tg-drawer__panel" x-data="examForm(<?php echo \Illuminate\Support\Js::from(route('visits.store', $patient))->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($examVisitsPayload)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($focusVisitId)->toHtml() ?>)"
                     @exam-edit.window="loadVisit($event.detail)" @exam-reset.window="resetForm()">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold" style="color: var(--ink);" x-text="editingId ? 'ویرایش معاینه' : 'ثبت معاینه کامل'"></h3>
                            <p class="text-xs" style="color: var(--muted);">شرح حال، معاینه، تشخیص و درمان</p>
                        </div>
                        <button type="button" class="tg-tool" @click="closePanel()"><?php if (isset($component)) { $__componentOriginal3baa94417da9f5dcb14f5f04da758571 = $component; } ?>
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
                    </div>
                    <form method="POST" :action="formAction" class="grid gap-4 sm:grid-cols-2">
                        <?php echo csrf_field(); ?>
                        <template x-if="editingId">
                            <input type="hidden" name="_method" value="PUT">
                        </template>
                        <div class="sm:col-span-2">
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'history','value' => 'شرح حال']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'history','value' => 'شرح حال']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                            <textarea id="history" name="history" rows="3" class="field-input" x-model="fields.history" x-ref="history" @focus="activeField = 'history'"></textarea>
                        </div>
                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'examination_full','value' => 'معاینه']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'examination_full','value' => 'معاینه']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                            <textarea id="examination_full" name="examination" rows="4" class="field-input" x-model="fields.examination" x-ref="examination" @focus="activeField = 'examination'"></textarea>
                        </div>
                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'diagnosis','value' => 'تشخیص']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'diagnosis','value' => 'تشخیص']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                            <textarea id="diagnosis" name="diagnosis" rows="4" class="field-input" x-model="fields.diagnosis" x-ref="diagnosis" @focus="activeField = 'diagnosis'"></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'treatment','value' => 'درمان']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'treatment','value' => 'درمان']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                            <textarea id="treatment" name="treatment" rows="3" class="field-input" x-model="fields.treatment" x-ref="treatment" @focus="activeField = 'treatment'"></textarea>
                        </div>

                        <div class="sm:col-span-2">
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'next_instruction','value' => 'دستور بعدی / پیگیری']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'next_instruction','value' => 'دستور بعدی / پیگیری']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                            <textarea id="next_instruction" name="next_instruction" rows="2" class="field-input" x-model="fields.next_instruction" x-ref="next_instruction" @focus="activeField = 'next_instruction'" placeholder="مثلاً کنترل یک هفته بعد، قطره…"></textarea>
                        </div>

                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'eye_side','value' => 'چشم']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'eye_side','value' => 'چشم']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                            <select id="eye_side" name="eye_side" class="field-input" x-model="fields.eye_side">
                                <option value="">—</option>
                                <?php $__currentLoopData = \App\Support\EyeSide::options(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $side): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($side['value']); ?>"><?php echo e($side['label']); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'va_right','value' => 'VA راست']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'va_right','value' => 'VA راست']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                                <input id="va_right" name="va_right" type="text" class="field-input ltr-data" dir="ltr" x-model="fields.va_right" placeholder="مثلاً 10/10">
                            </div>
                            <div>
                                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'va_left','value' => 'VA چپ']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'va_left','value' => 'VA چپ']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                                <input id="va_left" name="va_left" type="text" class="field-input ltr-data" dir="ltr" x-model="fields.va_left" placeholder="مثلاً 8/10">
                            </div>
                            <div>
                                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'iop_right','value' => 'IOP راست']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'iop_right','value' => 'IOP راست']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                                <input id="iop_right" name="iop_right" type="text" class="field-input ltr-data" dir="ltr" x-model="fields.iop_right" placeholder="mmHg">
                            </div>
                            <div>
                                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'iop_left','value' => 'IOP چپ']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'iop_left','value' => 'IOP چپ']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                                <input id="iop_left" name="iop_left" type="text" class="field-input ltr-data" dir="ltr" x-model="fields.iop_left" placeholder="mmHg">
                            </div>
                        </div>

                        <?php if($activeVisitId): ?>
                            <input type="hidden" name="appointment_id" value="<?php echo e(optional($patient->visits->firstWhere('id', $activeVisitId))->appointment_id); ?>">
                            <input type="hidden" name="surgery_appointment_id" value="<?php echo e(optional($patient->visits->firstWhere('id', $activeVisitId))->surgery_appointment_id); ?>">
                        <?php elseif($focusSurgeryId): ?>
                            <input type="hidden" name="surgery_appointment_id" value="<?php echo e($focusSurgeryId); ?>">
                        <?php endif; ?>

                        <div class="sm:col-span-2 rounded-2xl border p-3" style="border-color: var(--line); background: var(--panel-soft);">
                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs font-bold" style="color: var(--ink);">تگ سریع</p>
                                <p class="text-[11px]" style="color: var(--muted);">
                                    درج در:
                                    <strong x-text="fieldLabel(activeField)"></strong>
                                </p>
                            </div>
                            <div class="exam-tags">
                                <template x-for="(tag, index) in tagList" :key="index + '-' + tag">
                                    <span class="exam-tag-chip">
                                        <button type="button" class="exam-tag" @click="insert(tag)" x-text="tag"></button>
                                        <button type="button" class="exam-tag-remove" @click="removeTag(index)" title="حذف تگ" aria-label="حذف">×</button>
                                    </span>
                                </template>
                                <button type="button" class="exam-tag exam-tag--add" @click="addTag()">+ افزودن</button>
                            </div>
                        </div>

                        <div class="sm:col-span-2 flex gap-2">
                            <button type="submit" class="btn-primary" x-text="editingId ? 'ذخیره ویرایش' : 'ثبت معاینه'"></button>
                            <button type="button" class="btn-secondary" @click="closePanel()">بستن</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            
            <div class="tg-drawer" x-show="panel === 'upload'" x-cloak @click.self="closePanel()">
                <div class="tg-drawer__panel max-w-xl">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold" style="color: var(--ink);">آپلود تصویر پزشکی</h3>
                            <p class="text-xs" style="color: var(--muted);">از گالری انتخاب کنید یا با دوربین گوشی بگیرید</p>
                        </div>
                        <button type="button" class="tg-tool" @click="closePanel()"><?php if (isset($component)) { $__componentOriginal3baa94417da9f5dcb14f5f04da758571 = $component; } ?>
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
                    </div>
                    <form
                        method="POST"
                        action="<?php echo e(route('documents.store', $patient)); ?>"
                        enctype="multipart/form-data"
                        class="space-y-4"
                        x-data="docUploadForm()"
                        @submit="prepareSubmit($event)"
                    >
                        <?php echo csrf_field(); ?>
                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'type','value' => 'نوع تصویر']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'type','value' => 'نوع تصویر']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                            <select id="type" name="type" required class="field-input">
                                <option value="" disabled <?php echo e(old('type') ? '' : 'selected'); ?>>انتخاب کنید</option>
                                <?php $__currentLoopData = ['Fundus', 'OCT', 'آنژیو', 'عکس خارجی', 'اسکن', 'نسخه', 'سایر']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($type); ?>" <?php if(old('type') === $type): echo 'selected'; endif; ?>><?php echo e($type); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['class' => 'mt-2','messages' => $errors->get('type')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-2','messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('type'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                        </div>

                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['value' => 'تصویر']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'تصویر']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                            <input type="file" name="files[]" accept="image/*" multiple class="sr-only" x-ref="files" tabindex="-1" aria-hidden="true">

                            
                            <input type="file" accept="image/*" capture="environment" multiple class="sr-only" x-ref="camera" @change="pick($event, 'camera')">
                            <input type="file" accept="image/*" multiple class="sr-only" x-ref="gallery" @change="pick($event, 'gallery')">

                            <div class="doc-upload-sources mt-2">
                                <button type="button" class="doc-upload-source" @click="$refs.camera.click()">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 8.5A2.5 2.5 0 016.5 6h2l1.2-1.6A1.5 1.5 0 0110.9 4h2.2a1.5 1.5 0 011.2.6L15.5 6h2A2.5 2.5 0 0120 8.5v9A2.5 2.5 0 0117.5 20h-11A2.5 2.5 0 014 17.5v-9z"/>
                                        <circle cx="12" cy="13" r="3.25"/>
                                    </svg>
                                    <span class="doc-upload-source__title">دوربین</span>
                                    <span class="doc-upload-source__hint">عکس بگیرید</span>
                                </button>
                                <button type="button" class="doc-upload-source" @click="$refs.gallery.click()">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                        <rect x="3.5" y="5" width="17" height="14" rx="2"/>
                                        <circle cx="9" cy="10.5" r="1.5"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 17l3.5-3.5L13 16l2-2 3.5 3"/>
                                    </svg>
                                    <span class="doc-upload-source__title">گالری</span>
                                    <span class="doc-upload-source__hint">از گوشی انتخاب کنید</span>
                                </button>
                            </div>

                            <div class="doc-upload-preview mt-3" x-show="previewUrl" x-cloak>
                                <img :src="previewUrl" alt="پیش‌نمایش تصویر">
                                <div class="doc-upload-preview__meta">
                                    <strong x-text="fileName"></strong>
                                    <span x-text="sourceLabel"></span>
                                    <button type="button" class="doc-upload-preview__clear" @click="clearFile()">حذف</button>
                                </div>
                            </div>
                            <p class="mt-2 text-xs" style="color:var(--muted)" x-show="filesCount > 1" x-text="filesCount + ' فایل انتخاب شده'" x-cloak></p>
                            <p class="mt-2 text-xs text-rose-600" x-show="fileError" x-text="fileError" x-cloak></p>
                            <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['class' => 'mt-2','messages' => $errors->get('files')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-2','messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('files'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['class' => 'mt-2','messages' => $errors->get('files.*')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-2','messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('files.*'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                        </div>

                        <div>
                            <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'description','value' => 'توضیحات']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'description','value' => 'توضیحات']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'description','name' => 'description','type' => 'text','class' => 'mt-1 block w-full','value' => old('description')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'description','name' => 'description','type' => 'text','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('description'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="btn-primary" :disabled="submitting" x-text="submitting ? 'در حال آپلود...' : 'آپلود'"></button>
                            <button type="button" class="btn-secondary" @click="closePanel()">بستن</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if($canClinical && $whiteboardEnabled): ?>
            
            <div class="tg-drawer tg-drawer--whiteboard" x-show="panel === 'whiteboard'" x-cloak @click.self="closePanel()">
                <div class="tg-drawer__panel tg-whiteboard-panel" id="whiteboard-panel">
                    <div class="mb-2 flex items-center justify-end gap-2">
                        <button type="button" class="tg-tool" id="whiteboard-fullscreen-btn" title="تمام‌صفحه" aria-label="تمام‌صفحه">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/></svg>
                        </button>
                        <button type="button" class="tg-tool" @click="closePanel()"><?php if (isset($component)) { $__componentOriginal3baa94417da9f5dcb14f5f04da758571 = $component; } ?>
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
                    </div>
                    <div class="mb-3 flex flex-wrap items-center gap-2 overflow-x-auto" id="canvas-toolbar">
                        <button type="button" data-tool="pen" class="pp-tool canvas-tool-btn is-active">قلم</button>
                        <button type="button" data-tool="eraser" class="pp-tool canvas-tool-btn">پاک‌کن</button>
                        <button type="button" id="undo-canvas-btn" class="pp-tool" title="واگرد (Ctrl+Z)" aria-label="واگرد" disabled>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14L4 9l5-5"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 9h11a5 5 0 010 10h-3"/></svg>
                        </button>
                        <button type="button" id="redo-canvas-btn" class="pp-tool" title="ازنو (Ctrl+Shift+Z)" aria-label="ازنو" disabled>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 14l5-5-5-5"/><path stroke-linecap="round" stroke-linejoin="round" d="M20 9H9a5 5 0 000 10h3"/></svg>
                        </button>
                        <label class="pp-tool flex items-center gap-2">
                            ضخامت
                            <input id="pen-size" type="range" min="1" max="28" value="3" class="w-24" style="accent-color: var(--brand);">
                            <span id="pen-size-label" class="w-6 text-center font-semibold">3</span>
                        </label>
                        <label class="pp-tool flex items-center gap-2">
                            رنگ
                            <input id="pen-color" type="color" value="#2e3f50" class="h-6 w-8 cursor-pointer rounded border-0 bg-transparent p-0">
                        </label>
                    </div>
                    <div class="tg-whiteboard-stage" id="whiteboard-stage">
                        <canvas id="exam-canvas"
                                class="pp-canvas tg-whiteboard-canvas"
                                data-patient-id="<?php echo e($patient->id); ?>"
                                data-store-url="<?php echo e(route('visits.store-drawing')); ?>"></canvas>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" id="clear-canvas-btn" class="btn-secondary">پاک کردن</button>
                        <button type="button" id="save-canvas-btn" class="btn-primary">ذخیره در پرونده</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    
    <div id="pg-modal" class="pg-modal" style="display:none;">
        <div class="pg-modal__box">
            <div class="pg-modal__head">
                <button type="button" class="pg-modal__close" onclick="PatientGallery.close()">&times;</button>
                <strong id="pg-heading">گالری تصاویر</strong>
            </div>
            <div class="pg-modal__meta">
                <span><?php echo e($patient->name); ?></span>
                <span id="pg-date" dir="ltr"></span>
            </div>
            <div class="pg-modal__actions">
                <?php if($isStaff): ?>
                    <form id="pg-delete-form" method="POST" action="#" onsubmit="return confirm('این مورد حذف شود؟')">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <?php if($isEmbed): ?>
                            <input type="hidden" name="embed" value="1">
                            <input type="hidden" name="open" value="<?php echo e(request('open', 'photos')); ?>">
                        <?php endif; ?>
                        <button type="submit" class="btn-danger !py-2 !text-xs">حذف</button>
                    </form>
                <?php endif; ?>
                <?php if($canClinical): ?>
                    <button type="button" id="pg-edit-drawing" class="btn-secondary !py-2 !text-xs" style="display:none;" onclick="PatientGallery.editCurrent()">ویرایش وایت‌برد</button>
                <?php endif; ?>
                <div class="pg-zoom-controls" aria-label="بزرگ‌نمایی">
                    <button type="button" class="pg-zoom-btn" onclick="PatientGallery.zoomOut()" title="کوچک‌نمایی">−</button>
                    <button type="button" class="pg-zoom-btn" onclick="PatientGallery.resetZoom()" title="اندازه اصلی" id="pg-zoom-label">۱۰۰٪</button>
                    <button type="button" class="pg-zoom-btn" onclick="PatientGallery.zoomIn()" title="بزرگ‌نمایی">+</button>
                </div>
                <span id="pg-title" class="pg-modal__title"></span>
            </div>
            <div class="pg-modal__stage" id="pg-stage">
                <button type="button" class="pg-modal__nav pg-modal__nav--prev" onclick="PatientGallery.prev()">‹</button>
                <div class="pg-modal__viewport" id="pg-viewport">
                    <img id="pg-image" src="" alt="" draggable="false">
                </div>
                <button type="button" class="pg-modal__nav pg-modal__nav--next" onclick="PatientGallery.next()">›</button>
            </div>
            <p id="pg-counter" class="pg-modal__counter"></p>
            <div id="pg-thumbs" class="pg-modal__thumbs"></div>
        </div>
    </div>

    <style>
        .pg-modal { position:fixed; inset:0; z-index:99999; background:rgba(21,27,35,.78); align-items:center; justify-content:center; padding:12px; }
        .pg-modal__box { width:100%; max-width:920px; max-height:94vh; overflow:auto; background:var(--panel); color:var(--ink); border-radius:24px; box-shadow:var(--shadow-lg); border:1px solid var(--line); }
        .pg-modal__head { display:flex; align-items:center; justify-content:space-between; padding:16px 18px 8px; }
        .pg-modal__close { width:36px; height:36px; border:0; border-radius:999px; background:var(--panel-soft); color:var(--ink); cursor:pointer; font-size:20px; }
        .pg-modal__meta { display:flex; justify-content:space-between; gap:12px; padding:0 18px 12px; font-size:13px; color:var(--muted); }
        .pg-modal__actions { display:flex; gap:8px; padding:0 18px 12px; flex-wrap:wrap; align-items:center; }
        .pg-modal__title { margin-inline-start:auto; color:var(--brand); font-size:13px; font-weight:700; }
        .pg-zoom-controls { display:inline-flex; align-items:center; gap:4px; border:1px solid var(--line); border-radius:999px; padding:2px; background:var(--panel-soft); }
        .pg-zoom-btn { min-width:2rem; height:2rem; border:0; border-radius:999px; background:transparent; color:var(--ink); cursor:pointer; font-size:14px; font-weight:700; padding:0 .55rem; }
        .pg-zoom-btn:hover { background:color-mix(in srgb, var(--brand) 12%, transparent); color:var(--brand-dark); }
        .pg-modal__stage { position:relative; margin:0 18px 12px; background:#0f172a; border-radius:20px; min-height:280px; display:flex; align-items:center; justify-content:center; padding:8px; overflow:hidden; touch-action:none; }
        .pg-modal__viewport { width:100%; height:62vh; max-height:520px; display:flex; align-items:center; justify-content:center; overflow:hidden; cursor:grab; user-select:none; }
        .pg-modal__viewport.is-dragging { cursor:grabbing; }
        .pg-modal__viewport img { max-width:100%; max-height:100%; object-fit:contain; border-radius:12px; background:#fff; transform-origin:center center; transition:transform .05s linear; will-change:transform; pointer-events:none; }
        .pg-modal__nav { position:absolute; top:50%; transform:translateY(-50%); z-index:2; width:40px; height:40px; border:0; border-radius:999px; background:rgba(15,23,42,.72); color:#fff; cursor:pointer; font-size:22px; }
        .pg-modal__nav--prev { right:10px; } .pg-modal__nav--next { left:10px; }
        .pg-modal__counter { text-align:center; font-size:12px; color:var(--muted); margin:0 0 10px; }
        .pg-modal__thumbs { display:flex; gap:8px; overflow-x:auto; padding:0 18px 18px; }

        .doc-upload-sources {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .75rem;
        }
        .doc-upload-source {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            min-height: 6.5rem;
            padding: 1rem .75rem;
            border-radius: 1rem;
            border: 1px dashed color-mix(in srgb, var(--brand) 35%, var(--line));
            background: color-mix(in srgb, var(--panel-soft) 80%, transparent);
            color: var(--ink);
            cursor: pointer;
            transition: .15s ease;
            text-align: center;
        }
        .doc-upload-source:hover {
            border-color: var(--brand);
            background: color-mix(in srgb, var(--brand) 8%, var(--panel));
        }
        .doc-upload-source svg { width: 1.75rem; height: 1.75rem; color: var(--brand-dark); }
        .doc-upload-source__title { font-size: .875rem; font-weight: 800; }
        .doc-upload-source__hint { font-size: .7rem; color: var(--muted); font-weight: 600; }
        .doc-upload-preview {
            display: flex;
            gap: .75rem;
            align-items: center;
            padding: .75rem;
            border-radius: 1rem;
            border: 1px solid var(--line);
            background: var(--panel);
        }
        .doc-upload-preview img {
            width: 4.5rem;
            height: 4.5rem;
            object-fit: cover;
            border-radius: .75rem;
            flex-shrink: 0;
            background: var(--panel-soft);
        }
        .doc-upload-preview__meta {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: .2rem;
            font-size: .75rem;
            color: var(--muted);
        }
        .doc-upload-preview__meta strong {
            color: var(--ink);
            font-size: .8rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .doc-upload-preview__clear {
            align-self: flex-start;
            margin-top: .25rem;
            color: var(--danger, #b42318);
            font-weight: 700;
            font-size: .75rem;
            background: none;
            border: 0;
            padding: 0;
            cursor: pointer;
        }
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        .exam-tags {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            justify-content: flex-end;
            max-width: 100%;
        }
        .exam-tag-chip {
            display: inline-flex;
            align-items: center;
            gap: 0;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: var(--panel-soft);
            overflow: hidden;
        }
        .exam-tag {
            border: 0;
            background: transparent;
            color: var(--ink);
            border-radius: 0;
            padding: .25rem .5rem .25rem .55rem;
            font-size: .68rem;
            font-weight: 700;
            cursor: pointer;
            transition: .12s ease;
        }
        .exam-tag-chip:hover { border-color: var(--brand); background: color-mix(in srgb, var(--brand) 10%, var(--panel-soft)); }
        .exam-tag:hover { color: var(--brand-dark); }
        .exam-tag-remove {
            border: 0;
            border-right: 1px solid var(--line);
            background: transparent;
            color: var(--muted);
            width: 1.35rem;
            height: 100%;
            min-height: 1.5rem;
            font-size: .85rem;
            line-height: 1;
            cursor: pointer;
            padding: 0;
        }
        .exam-tag-remove:hover { color: #dc2626; background: color-mix(in srgb, #fee2e2 60%, transparent); }
        .exam-tag--add {
            border: 1px dashed var(--line);
            background: var(--panel-soft);
            color: var(--brand-dark);
            border-radius: 999px;
            padding: .25rem .65rem;
            font-size: .68rem;
            font-weight: 700;
            cursor: pointer;
        }
        .exam-tag--add:hover { border-color: var(--brand); background: color-mix(in srgb, var(--brand) 12%, var(--panel)); }

        .tg-drawer--whiteboard .tg-drawer__panel {
            max-width: none;
            width: min(100vw, 960px);
            display: flex;
            flex-direction: column;
        }
        .tg-whiteboard-panel:fullscreen,
        .tg-whiteboard-panel.is-fullscreen {
            width: 100vw !important;
            height: 100dvh !important;
            max-width: none !important;
            border-radius: 0 !important;
            display: flex;
            flex-direction: column;
            padding: 0.75rem !important;
            background: var(--panel);
        }
        /* The stage owns the box; JS sizes the canvas backing stores to match it.
           Both canvases are stacked, so the live stroke never repaints the ink. */
        .tg-whiteboard-stage {
            position: relative;
            flex: 1;
            min-height: min(52dvh, 420px);
            width: 100%;
            border: 2px solid var(--line);
            border-radius: 1rem;
            background: #fff;
            overflow: hidden;
            -webkit-user-select: none;
            user-select: none;
            overscroll-behavior: none;
            touch-action: none;
        }

        .tg-whiteboard-panel:fullscreen .tg-whiteboard-stage,
        .tg-whiteboard-panel.is-fullscreen .tg-whiteboard-stage {
            min-height: 0;
            flex: 1;
        }

        .tg-whiteboard-stage > canvas {
            position: absolute;
            inset: 0;
            display: block;
            width: 100% !important;
            height: 100% !important;
            min-height: 0 !important;
            max-height: none !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent;
            touch-action: none;
            -webkit-touch-callout: none;
        }

        .tg-whiteboard-stage > .tg-whiteboard-canvas {
            cursor: crosshair;
        }

        /* The live layer must never swallow a pointer event. */
        .tg-whiteboard-stage > .tg-whiteboard-live {
            pointer-events: none;
        }

        .tg-whiteboard-stage.is-erasing > .tg-whiteboard-canvas {
            cursor: none;
        }

        .tg-whiteboard-cursor {
            position: absolute;
            top: 0;
            left: 0;
            display: none;
            border: 1.5px solid rgba(17, 24, 39, .55);
            background: rgba(148, 163, 184, .18);
            border-radius: 9999px;
            pointer-events: none;
            z-index: 2;
        }

        .tg-whiteboard-stage.is-erasing > .tg-whiteboard-cursor.is-visible {
            display: block;
        }

        #canvas-toolbar .pp-tool:disabled {
            opacity: .38;
            cursor: not-allowed;
        }

        @media (pointer: coarse) {
            .tg-whiteboard-stage {
                min-height: min(58dvh, 520px);
            }
            #canvas-toolbar .pp-tool {
                min-height: 44px;
                padding-inline: 0.85rem;
            }
            #pen-size {
                width: 7rem;
            }
        }

        .tg-attach-row {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .5rem;
        }
        .tg-attach-row--4 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .tg-attach-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .25rem;
            padding: .5rem .25rem;
            border: 0;
            background: transparent;
            cursor: pointer;
            text-align: center;
        }
        .tg-attach-btn__icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border-radius: 999px;
            color: #fff;
        }
        .tg-attach-btn__icon svg { width: 1.35rem; height: 1.35rem; }
        .tg-attach-btn__icon.is-photo { background: linear-gradient(145deg, #22c55e, #15803d); }
        .tg-attach-btn__icon.is-draw { background: linear-gradient(145deg, #f59e0b, #d97706); }
        .tg-attach-btn__icon.is-exam { background: linear-gradient(145deg, var(--brand), var(--brand-dark)); }
        .tg-attach-btn__icon.is-rx { background: linear-gradient(145deg, #10b981, #047857); }
        .tg-attach-btn__label {
            font-size: .65rem;
            font-weight: 800;
            color: var(--ink);
            line-height: 1.2;
        }
        .tg-attach-btn__count {
            font-size: .6rem;
            font-weight: 700;
            color: var(--muted);
        }

        .tg-media-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
        }
        .tg-media-tab {
            border: 1px solid var(--line);
            background: var(--panel-soft);
            color: var(--muted);
            border-radius: 999px;
            padding: .35rem .75rem;
            font-size: .72rem;
            font-weight: 800;
            cursor: pointer;
        }
        .tg-media-tab.is-active {
            background: var(--brand-dark);
            border-color: transparent;
            color: #fff;
        }
        .tg-media-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .5rem;
        }
        .tg-media-thumb {
            position: relative;
            aspect-ratio: 1;
            overflow: hidden;
            border-radius: .75rem;
            border: 1px solid var(--line);
            padding: 0;
            background: var(--panel-soft);
            cursor: pointer;
        }
        .tg-media-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .tg-media-thumb__meta {
            position: absolute;
            inset-inline: 0;
            bottom: 0;
            padding: .2rem .35rem;
            font-size: .55rem;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(transparent, rgba(0,0,0,.65));
        }
        .tg-media-list-item {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: .2rem;
            width: 100%;
            text-align: right;
            padding: .65rem .75rem;
            border-radius: .85rem;
            border: 1px solid var(--line);
            background: var(--panel-soft);
            cursor: pointer;
        }
        .tg-media-list-item__date {
            font-size: .65rem;
            font-weight: 800;
            color: var(--brand-dark);
        }
        .tg-media-list-item__text {
            font-size: .75rem;
            color: var(--ink);
            line-height: 1.45;
        }
        @media (max-width: 640px) {
            .tg-drawer--whiteboard .tg-drawer__panel {
                width: 100%;
                min-height: 100dvh;
                border-radius: 0;
            }
            .tg-whiteboard-stage {
                min-height: min(58vh, 520px);
            }
        }
    </style>

    <script>
        function examForm(storeUrl, visits, focusVisitId) {
            const defaultTags = ['کاهش بینایی', 'درد چشم', 'قرمزی', 'تاری دید', 'VA نرمال', 'IOP نرمال', 'قرنیه شفاف', 'خشکی چشم', 'آب مروارید', 'اشک مصنوعی', 'پیگیری ۱ هفته'];
            const key = 'examQuickTags.v3';
            let tagList = [];
            try {
                const stored = localStorage.getItem(key);
                tagList = stored ? JSON.parse(stored) : null;
                if (!Array.isArray(tagList) || !tagList.length) {
                    tagList = [...defaultTags];
                }
            } catch (e) {
                tagList = [...defaultTags];
            }
            const labels = {
                history: 'شرح حال',
                examination: 'معاینه',
                diagnosis: 'تشخیص',
                treatment: 'درمان',
                next_instruction: 'دستور بعدی',
            };
            const emptyFields = () => ({
                history: '',
                examination: '',
                diagnosis: '',
                treatment: '',
                next_instruction: '',
                eye_side: '',
                va_right: '',
                va_left: '',
                iop_right: '',
                iop_left: '',
            });

            return {
                storeUrl: storeUrl,
                visits: visits || [],
                editingId: focusVisitId || null,
                fields: emptyFields(),
                activeField: 'examination',
                tagList,
                persistTags() {
                    localStorage.setItem(key, JSON.stringify(this.tagList));
                },
                get formAction() {
                    if (!this.editingId) return this.storeUrl;
                    return this.storeUrl + '/' + this.editingId;
                },
                fieldLabel(field) {
                    return labels[field] || 'معاینه';
                },
                resetForm() {
                    this.editingId = null;
                    this.fields = emptyFields();
                },
                loadVisit(id) {
                    const visit = this.visits.find((v) => v.id === id);
                    if (!visit) {
                        this.editingId = id || null;
                        this.fields = emptyFields();
                        return;
                    }
                    this.editingId = visit.id;
                    this.fields = {
                        history: visit.history || '',
                        examination: visit.examination || '',
                        diagnosis: visit.diagnosis || '',
                        treatment: visit.treatment || '',
                        next_instruction: visit.next_instruction || '',
                        eye_side: visit.eye_side || '',
                        va_right: visit.va_right || '',
                        va_left: visit.va_left || '',
                        iop_right: visit.iop_right || '',
                        iop_left: visit.iop_left || '',
                    };
                },
                init() {
                    if (focusVisitId) this.loadVisit(focusVisitId);
                },
                insert(text) {
                    const field = this.activeField || 'examination';
                    const el = this.$refs[field];
                    if (!el) return;
                    const start = el.selectionStart ?? el.value.length;
                    const end = el.selectionEnd ?? el.value.length;
                    const before = el.value.slice(0, start);
                    const after = el.value.slice(end);
                    const pad = before && !/\s$/.test(before) ? ' ' : '';
                    const next = before + pad + text + after;
                    this.fields[field] = next;
                    el.focus();
                    const pos = (before + pad + text).length;
                    el.setSelectionRange(pos, pos);
                },
                addTag() {
                    const text = prompt('متن تگ جدید را وارد کنید:');
                    if (!text || !text.trim()) return;
                    const value = text.trim();
                    if (!this.tagList.includes(value)) {
                        this.tagList.push(value);
                        this.persistTags();
                    }
                    this.insert(value);
                },
                removeTag(index) {
                    if (index < 0 || index >= this.tagList.length) return;
                    this.tagList.splice(index, 1);
                    this.persistTags();
                },
            };
        }
    </script>

    <script>
        // Close answer-app style action bars when clicking outside
        document.addEventListener('click', function (e) {
            document.querySelectorAll('details.aa-actions[open]').forEach(function (el) {
                if (!el.contains(e.target)) el.removeAttribute('open');
            });
        });
    </script>

    <script>
        window.PatientGallery = (function () {
            var collections = {
                photos: <?php echo json_encode($galleryPayload, 15, 512) ?>,
                drawings: <?php echo json_encode($drawingGalleryPayload, 15, 512) ?>,
            };
            var mode = 'photos';
            var items = collections.photos;
            var index = 0;
            var scale = 1;
            var tx = 0;
            var ty = 0;
            var dragging = false;
            var lastX = 0;
            var lastY = 0;
            var pinchStartDist = 0;
            var pinchStartScale = 1;
            var lastTap = 0;

            var modal = document.getElementById('pg-modal');
            var img = document.getElementById('pg-image');
            var title = document.getElementById('pg-title');
            var date = document.getElementById('pg-date');
            var counter = document.getElementById('pg-counter');
            var thumbs = document.getElementById('pg-thumbs');
            var deleteForm = document.getElementById('pg-delete-form');
            var heading = document.getElementById('pg-heading');
            var editBtn = document.getElementById('pg-edit-drawing');
            var zoomLabel = document.getElementById('pg-zoom-label');
            var viewport = document.getElementById('pg-viewport');

            function applyTransform() {
                if (!img) return;
                img.style.transform = 'translate(' + tx + 'px,' + ty + 'px) scale(' + scale + ')';
                if (zoomLabel) zoomLabel.textContent = Math.round(scale * 100) + '٪';
                if (viewport) viewport.style.cursor = scale > 1.01 ? (dragging ? 'grabbing' : 'grab') : 'default';
            }

            function resetZoom() {
                scale = 1;
                tx = 0;
                ty = 0;
                applyTransform();
            }

            function setZoom(next) {
                scale = Math.min(5, Math.max(1, next));
                if (scale === 1) {
                    tx = 0;
                    ty = 0;
                }
                applyTransform();
            }

            function zoomIn() { setZoom(scale + 0.35); }
            function zoomOut() { setZoom(scale - 0.35); }

            function currentItem() {
                return items[index] || null;
            }

            function render() {
                if (!items.length) return;
                var item = currentItem();
                resetZoom();
                img.src = item.src;
                title.textContent = item.title || '';
                date.textContent = item.date || '';
                counter.textContent = (index + 1) + ' از ' + items.length;
                if (heading) heading.textContent = mode === 'drawings' ? 'وایت‌بردها' : 'گالری تصاویر';
                if (deleteForm) {
                    deleteForm.action = item.deleteUrl || '#';
                    deleteForm.style.display = item.deleteUrl ? '' : 'none';
                }
                if (editBtn) {
                    var canEdit = mode === 'drawings' && item.editVisitId;
                    editBtn.style.display = canEdit ? '' : 'none';
                }
                thumbs.innerHTML = '';
                items.forEach(function (it, n) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.style.cssText = 'width:56px;height:56px;padding:0;border-radius:12px;overflow:hidden;cursor:pointer;border:2px solid ' + (n === index ? 'var(--brand)' : 'transparent') + ';background:transparent;';
                    b.innerHTML = '<img src="' + it.src + '" style="width:100%;height:100%;object-fit:cover;background:#fff;">';
                    b.onclick = function () { index = n; render(); };
                    thumbs.appendChild(b);
                });
            }

            function openWith(collectionName, i) {
                mode = collectionName;
                items = collections[collectionName] || [];
                if (!items.length) {
                    alert(collectionName === 'drawings' ? 'وایت‌بردی وجود ندارد' : 'تصویری وجود ندارد');
                    return;
                }
                index = Math.min(Math.max(Number(i) || 0, 0), items.length - 1);
                if (modal.parentElement !== document.body) document.body.appendChild(modal);
                render();
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }

            function open(i) { openWith('photos', i); }
            function openDrawing(i) { openWith('drawings', i); }

            function close() {
                modal.style.display = 'none';
                img.src = '';
                resetZoom();
                document.body.style.overflow = '';
            }

            function prev() {
                if (!items.length) return;
                index = (index - 1 + items.length) % items.length;
                render();
            }

            function next() {
                if (!items.length) return;
                index = (index + 1) % items.length;
                render();
            }

            function editCurrent() {
                var item = currentItem();
                if (!item || !item.editVisitId) return;
                close();
                window.dispatchEvent(new CustomEvent('whiteboard-edit-request', {
                    detail: { visitId: item.editVisitId, src: item.src }
                }));
            }

            function pointerDistance(a, b) {
                var dx = a.clientX - b.clientX;
                var dy = a.clientY - b.clientY;
                return Math.hypot(dx, dy);
            }

            if (viewport) {
                viewport.addEventListener('wheel', function (e) {
                    if (modal.style.display !== 'flex') return;
                    e.preventDefault();
                    setZoom(scale + (e.deltaY < 0 ? 0.2 : -0.2));
                }, { passive: false });

                viewport.addEventListener('dblclick', function () {
                    if (scale > 1.05) resetZoom();
                    else setZoom(2.2);
                });

                viewport.addEventListener('pointerdown', function (e) {
                    if (e.pointerType === 'touch') return;
                    if (scale <= 1.01) return;
                    dragging = true;
                    lastX = e.clientX;
                    lastY = e.clientY;
                    viewport.classList.add('is-dragging');
                    viewport.setPointerCapture(e.pointerId);
                });

                viewport.addEventListener('pointermove', function (e) {
                    if (!dragging) return;
                    tx += e.clientX - lastX;
                    ty += e.clientY - lastY;
                    lastX = e.clientX;
                    lastY = e.clientY;
                    applyTransform();
                });

                function endDrag(e) {
                    if (!dragging) return;
                    dragging = false;
                    viewport.classList.remove('is-dragging');
                    try { viewport.releasePointerCapture(e.pointerId); } catch (err) {}
                }
                viewport.addEventListener('pointerup', endDrag);
                viewport.addEventListener('pointercancel', endDrag);

                viewport.addEventListener('touchstart', function (e) {
                    if (e.touches.length === 2) {
                        pinchStartDist = pointerDistance(e.touches[0], e.touches[1]);
                        pinchStartScale = scale;
                    } else if (e.touches.length === 1) {
                        var now = Date.now();
                        if (now - lastTap < 280) {
                            if (scale > 1.05) resetZoom();
                            else setZoom(2.2);
                            lastTap = 0;
                        } else {
                            lastTap = now;
                        }
                        lastX = e.touches[0].clientX;
                        lastY = e.touches[0].clientY;
                        dragging = scale > 1.01;
                    }
                }, { passive: true });

                viewport.addEventListener('touchmove', function (e) {
                    if (e.touches.length === 2) {
                        e.preventDefault();
                        var dist = pointerDistance(e.touches[0], e.touches[1]);
                        if (pinchStartDist > 0) setZoom(pinchStartScale * (dist / pinchStartDist));
                    } else if (e.touches.length === 1 && dragging) {
                        e.preventDefault();
                        tx += e.touches[0].clientX - lastX;
                        ty += e.touches[0].clientY - lastY;
                        lastX = e.touches[0].clientX;
                        lastY = e.touches[0].clientY;
                        applyTransform();
                    }
                }, { passive: false });

                viewport.addEventListener('touchend', function (e) {
                    if (e.touches.length < 2) pinchStartDist = 0;
                    if (e.touches.length === 0) dragging = false;
                });
            }

            modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
            document.addEventListener('keydown', function (e) {
                if (modal.style.display !== 'flex') return;
                if (e.key === 'Escape') close();
                if (e.key === 'ArrowLeft') next();
                if (e.key === 'ArrowRight') prev();
                if (e.key === '+' || e.key === '=') zoomIn();
                if (e.key === '-') zoomOut();
                if (e.key === '0') resetZoom();
            });

            return {
                open: open,
                openDrawing: openDrawing,
                close: close,
                prev: prev,
                next: next,
                zoomIn: zoomIn,
                zoomOut: zoomOut,
                resetZoom: resetZoom,
                editCurrent: editCurrent,
            };
        })();
    </script>

    <?php if($isStaff): ?>
    <script>
        function docUploadForm() {
            return {
                previewUrl: null,
                fileName: '',
                sourceLabel: '',
                filesCount: 0,
                fileError: '',
                submitting: false,
                pick(event, source) {
                    const picked = Array.from(event.target.files || []);
                    event.target.value = '';
                    this.fileError = '';
                    if (!picked.length) return;

                    try {
                        const dt = new DataTransfer();
                        const existing = Array.from((this.$refs.files && this.$refs.files.files) ? this.$refs.files.files : []);
                        const merged = [...existing, ...picked];
                        const maxBytes = 10 * 1024 * 1024;
                        for (const file of merged) {
                            if (!file.type || !file.type.startsWith('image/')) {
                                this.fileError = 'فقط فایل تصویری مجاز است.';
                                return;
                            }
                            if (file.size > maxBytes) {
                                this.fileError = 'حجم هر تصویر نباید بیشتر از ۱۰ مگابایت باشد.';
                                return;
                            }
                            dt.items.add(file);
                        }
                        this.$refs.files.files = dt.files;
                        this.filesCount = dt.files.length;
                    } catch (e) {
                        this.fileError = 'مرورگر از انتخاب این تصویر پشتیبانی نمی‌کند.';
                        return;
                    }

                    const file = this.$refs.files.files[0];
                    if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                    this.previewUrl = URL.createObjectURL(file);
                    this.fileName = file.name || (source === 'camera' ? 'عکس دوربین' : 'تصویر');
                    this.sourceLabel = source === 'camera' ? 'گرفته‌شده با دوربین' : 'انتخاب از گالری';
                },
                clearFile() {
                    if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                    this.previewUrl = null;
                    this.fileName = '';
                    this.sourceLabel = '';
                    this.filesCount = 0;
                    this.fileError = '';
                    if (this.$refs.files) this.$refs.files.value = '';
                },
                prepareSubmit(e) {
                    if (this.submitting) {
                        e.preventDefault();
                        return;
                    }
                    if (!this.$refs.files || !this.$refs.files.files || !this.$refs.files.files.length) {
                        e.preventDefault();
                        this.fileError = 'لطفاً با دوربین عکس بگیرید یا از گالری انتخاب کنید.';
                        return;
                    }
                    this.submitting = true;
                },
            };
        }
    </script>
    <script>
        (function () {
            const startBtn = document.getElementById('start-voice-btn');
            const stopBtn = document.getElementById('stop-voice-btn');
            const statusEl = document.getElementById('voice-status');
            const previewBox = document.getElementById('voice-preview');
            const previewAudio = document.getElementById('voice-preview-audio');
            const sendBtn = document.getElementById('send-voice-btn');
            const discardBtn = document.getElementById('discard-voice-btn');
            if (!startBtn || !stopBtn) return;
            const patientId = <?php echo json_encode($patient->id, 15, 512) ?>;
            const storeUrl = <?php echo json_encode(route('visits.store-voice'), 15, 512) ?>;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            let mediaRecorder = null, audioChunks = [], mediaStream = null, pendingBlob = null, pendingExtension = 'webm', uploading = false;

            function setStatus(text, recording) {
                if (statusEl) statusEl.textContent = text;
                startBtn.classList.toggle('is-recording', !!recording);
                startBtn.classList.toggle('hidden', !!recording);
                stopBtn.classList.toggle('hidden', !recording);
            }

            function clearPreview() {
                pendingBlob = null;
                if (previewAudio) previewAudio.removeAttribute('src');
                if (previewBox) previewBox.classList.add('hidden');
                setStatus('', false);
            }

            async function uploadVoice() {
                if (!pendingBlob || uploading) return;
                if (!confirm('ویس معاینه ارسال شود؟')) return;
                uploading = true;
                if (sendBtn) { sendBtn.disabled = true; sendBtn.textContent = 'در حال ارسال...'; }
                setStatus('در حال ذخیره ویس...', false);
                try {
                    const formData = new FormData();
                    formData.append('patient_id', patientId);
                    formData.append('audio', pendingBlob, 'recording.' + pendingExtension);
                    const response = await fetch(storeUrl, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData,
                    });
                    if (!response.ok) throw new Error('خطا در ذخیره صدا');
                    window.location.reload();
                } catch (error) {
                    alert(error.message || 'خطا در ذخیره صدا');
                    uploading = false;
                    if (sendBtn) { sendBtn.disabled = false; sendBtn.textContent = 'ارسال ویس'; }
                    setStatus('', false);
                }
            }

            startBtn.addEventListener('click', async function () {
                if (!navigator.mediaDevices?.getUserMedia) return alert('مرورگر از ضبط پشتیبانی نمی‌کند');
                clearPreview();
                try {
                    mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    audioChunks = [];
                    const types = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4'];
                    let mimeType = '';
                    for (const t of types) {
                        if (MediaRecorder.isTypeSupported(t)) { mimeType = t; break; }
                    }
                    mediaRecorder = mimeType ? new MediaRecorder(mediaStream, { mimeType }) : new MediaRecorder(mediaStream);
                    mediaRecorder.ondataavailable = function (e) {
                        if (e.data?.size) audioChunks.push(e.data);
                    };
                    mediaRecorder.onstop = function () {
                        const blobType = mediaRecorder.mimeType || 'audio/webm';
                        pendingExtension = blobType.includes('mp4') ? 'mp4' : 'webm';
                        pendingBlob = new Blob(audioChunks, { type: blobType });
                        mediaStream?.getTracks().forEach(function (t) { t.stop(); });
                        mediaStream = null;
                        setStatus('پیش‌نمایش ویس آماده است', false);
                        if (previewAudio && previewBox) {
                            previewAudio.src = URL.createObjectURL(pendingBlob);
                            previewBox.classList.remove('hidden');
                        }
                    };
                    mediaRecorder.start();
                    setStatus('در حال ضبط... برای پایان روی توقف بزنید', true);
                } catch (e) {
                    alert('دسترسی به میکروفون ممکن نشد');
                    setStatus('', false);
                }
            });

            stopBtn.addEventListener('click', function () {
                if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
            });

            if (sendBtn) sendBtn.addEventListener('click', uploadVoice);
            if (discardBtn) discardBtn.addEventListener('click', clearPreview);
        })();
    </script>
    <?php endif; ?>

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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.answer-panel','data' => ['mobile' => $patient->mobile,'patientName' => $patient->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('answer-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mobile' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($patient->mobile),'patient-name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($patient->name)]); ?>
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
    <?php if(request('open') === 'consent'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('patient-consent-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    </script>
    <?php endif; ?>
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
<?php /**PATH C:\Users\elahe\Downloads\Compressed\app_3\resources\views\patients\show.blade.php ENDPATH**/ ?>