@php
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
    } elseif ($openRequest === 'followup' && $isStaff && $followupEnabled) {
        $openPanel = 'followup';
    } elseif ($openRequest === 'consent' && $isStaff && \App\Support\FeatureFlags::enabled('features.consent_forms')) {
        $openPanel = null;
    }

    $focusVisitId = (int) request('visit', 0) ?: null;
    $focusSurgeryId = (int) request('surgery', 0) ?: null;
    $focusNoteId = (int) request('note', 0) ?: null;

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
        'rotateUrl' => $isStaff
            ? route('documents.rotate', array_merge([
                'patient' => $patient,
                'document' => $d,
            ], $embedQuery))
            : null,
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
    $careFollowUps = $patient->followUps ?? collect();
    $followUpKinds = $followupEnabled ? \App\Services\PatientFollowUpService::kinds() : [];
    $followUpMethods = $followupEnabled ? \App\Services\PatientFollowUpService::methods() : [];
    $followUpOutcomes = $followupEnabled ? \App\Services\PatientFollowUpService::outcomes() : [];
    $followUpStaff = $followupEnabled
        ? \App\Models\User::query()->whereIn('role', ['admin', 'doctor', 'assistant'])->orderBy('name')->get(['id', 'name'])
        : collect();
    $followUpHospitals = $isStaff && $followupEnabled
        ? \App\Models\Hospital::query()->orderBy('name')->get(['id', 'name'])
        : collect();
@endphp

<x-app-layout :body-class="$isEmbed ? 'is-patient-chat is-patient-embed' : 'is-patient-chat'">
    <div
        class="tg-layout"
        @paste.capture="onPatientPaste($event)"
        x-data="{
            panel: @js($openPanel),
            mediaTab: @js($openMediaTab),
            isEmbed: @js($isEmbed),
            quickToolsOpen: false,
            editingVisitId: null,
            editingDrawingSrc: null,
            surgeryCards: {},
            examPreview: null,
            activeVisitId: @js($activeVisitId),
            focusSurgeryId: @js($focusSurgeryId),
            canUpload: @js($isStaff),
            panelStack: [],
            pushPanel(name) {
                if (!name || this.panel === name) return;
                if (this.panel) this.panelStack.push(this.panel);
                this.panel = name;
                document.body.style.overflow = 'hidden';
                if (window.OverlayHistory && !this.isEmbed) {
                    window.OverlayHistory.push('patient:' + name, () => this.popPanel(true));
                }
            },
            popPanel(fromHistory) {
                const closing = this.panel;
                if (!closing) return;
                if (this.panelStack.length) {
                    this.panel = this.panelStack.pop();
                } else {
                    this.panel = null;
                    document.body.style.overflow = '';
                }
                if (!fromHistory && window.OverlayHistory && !this.isEmbed) {
                    window.OverlayHistory.dismiss('patient:' + closing);
                }
            },
            openPanel(name) {
                if (name !== 'whiteboard') {
                    this.editingVisitId = null;
                    this.editingDrawingSrc = null;
                }
                if (name === 'exam') {
                    window.dispatchEvent(new CustomEvent('exam-reset'));
                }
                this.pushPanel(name);
                if (name === 'whiteboard') {
                    setTimeout(function () { window.dispatchEvent(new CustomEvent('whiteboard-open')); }, 80);
                }
            },
            openDrawingEditor(visitId, src) {
                this.editingVisitId = visitId;
                this.editingDrawingSrc = src;
                this.pushPanel('whiteboard');
                window.dispatchEvent(new CustomEvent('whiteboard-load', { detail: { src, visitId } }));
                setTimeout(function () { window.dispatchEvent(new CustomEvent('whiteboard-open')); }, 80);
            },
            openMedia(tab) {
                this.mediaTab = tab;
                this.pushPanel('media');
            },
            onPatientPaste(event) {
                if (!this.canUpload) return;
                const bag = event.clipboardData;
                if (!bag) return;
                const files = [];
                const fromList = bag.files && bag.files.length ? Array.from(bag.files) : [];
                fromList.forEach(function (file) {
                    if (file && file.type && file.type.indexOf('image/') === 0) files.push(file);
                });
                if (!files.length && bag.items) {
                    Array.from(bag.items).forEach(function (item) {
                        if (item.kind === 'file' && item.type && item.type.indexOf('image/') === 0) {
                            const file = item.getAsFile();
                            if (file) files.push(file);
                        }
                    });
                }
                if (!files.length) return;
                event.preventDefault();
                this.openPanel('upload');
                this.$nextTick(function () {
                    window.dispatchEvent(new CustomEvent('patient-upload-files', {
                        detail: { files: files, source: 'paste' },
                    }));
                    const typeSelect = document.getElementById('type');
                    if (typeSelect) typeSelect.focus();
                });
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
                this.popPanel(false);
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
                this.pushPanel('prescription');
                this.$nextTick(() => window.dispatchEvent(new CustomEvent('prescription-edit', { detail: id })));
            },
            openExamEdit(id) {
                this.pushPanel('exam');
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
                this.pushPanel('exam-view');
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
                const focusNoteId = {{ (int) ($focusNoteId ?? 0) }};
                if (focusNoteId) {
                    jumpToItem('note-' + focusNoteId);
                    window.setTimeout(() => jumpToItem('note-' + focusNoteId), 180);
                    return;
                }
                if (feed) feed.scrollTop = feed.scrollHeight;
            });
            if (panel) document.body.style.overflow = 'hidden';
            if (panel && window.OverlayHistory && !isEmbed) {
                window.OverlayHistory.push('patient:' + panel, () => popPanel(true));
            }
        "
        @keydown.escape.window="if (window.PatientGallery && window.PatientGallery.isOpen && window.PatientGallery.isOpen()) return; if (panel) closePanel()"
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
        {{-- Sidebar (first in DOM → right column in RTL) --}}
        <aside class="tg-side fade-up-delay hidden lg:flex">
            @include('patients.partials.profile-sidebar', compact('patient', 'isStaff', 'canClinical', 'visitCount', 'docCount', 'noteCount', 'voiceCount', 'drawingCount', 'activityTotal', 'examVisits', 'rxCount', 'pendingFollowUps', 'careFollowUps'))
        </aside>

        {{-- Main chat --}}
        <section class="tg-chat fade-up">
            <header class="tg-chat__header cursor-pointer select-none" role="button" tabindex="0" title="برگشت به بالا"
                    @click="document.getElementById('tg-feed')?.scrollTo({ top: 0, behavior: 'smooth' })"
                    @keydown.enter.prevent="document.getElementById('tg-feed')?.scrollTo({ top: 0, behavior: 'smooth' })">
                <div class="flex min-w-0 items-center gap-1.5 sm:gap-3">
                    @if($isStaff)
                        <a href="{{ route('dashboard') }}" class="tg-tool" title="بازگشت" @click.stop>
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    @endif
                    <div class="relative flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-full text-[10px] font-extrabold text-white sm:h-11 sm:w-11 sm:text-sm"
                         style="background: linear-gradient(145deg, var(--brand-light), var(--brand-dark));">
                        <img class="js-header-photo h-full w-full object-cover" src="{{ $patient->photoUrl() }}" alt="" @if(! $patient->photoUrl()) hidden @endif>
                        <span class="js-header-photo-fallback" @if($patient->photoUrl()) hidden @endif>{{ $patient->photoInitial() }}</span>
                    </div>
                    <div class="min-w-0">
                        <h1 class="truncate text-[11px] font-bold sm:text-base" style="color: var(--ink);">
                            {{ $patient->name }}
                            <x-his-badge :model="$patient" class="align-middle" />
                        </h1>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-0.5" @click.stop>
                    @if($isStaff && $followupEnabled)
                        <div class="relative" x-data="{ openFollowMenu: @js($openPanel === 'postop') }" @click.outside="openFollowMenu = false">
                            <button type="button" class="tg-tool" title="مراجعه بعدی" @click="openFollowMenu = !openFollowMenu">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                            <div x-show="openFollowMenu" x-cloak class="absolute left-0 z-30 mt-2 w-52 rounded-xl border p-2 shadow-lg"
                                 style="border-color: var(--line); background: var(--panel);">
                                <form method="POST" action="{{ route('followups.store', $patient) }}" class="space-y-2">
                                    @csrf
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
                                    <form method="POST" action="{{ route('followups.store', $patient) }}" class="space-y-1.5">
                                        @csrf
                                        <input type="hidden" name="label_prefix" value="پس از عمل">
                                        @if($focusSurgeryId)
                                            <input type="hidden" name="surgery_appointment_id" value="{{ $focusSurgeryId }}">
                                        @endif
                                        <label class="flex items-center gap-1.5 text-[11px]"><input type="checkbox" name="intervals[]" value="1" checked> ۱ روز</label>
                                        <label class="flex items-center gap-1.5 text-[11px]"><input type="checkbox" name="intervals[]" value="7" checked> ۱ هفته</label>
                                        <label class="flex items-center gap-1.5 text-[11px]"><input type="checkbox" name="intervals[]" value="30" checked> ۱ ماه</label>
                                    <button type="submit" class="btn-secondary w-full !py-1.5 !text-xs">ثبت پیگیری پس از عمل</button>
                                </form>
                            </div>
                            @if(\App\Support\PatientFollowUps::isAvailable())
                                <button type="button" class="btn-primary mt-2 w-full !py-1.5 !text-xs" @click="openFollowMenu = false; openPanel('followup')">همه پیگیری‌ها</button>
                            @endif
                            </div>
                        </div>
                    @endif
                    @if($isStaff)
                    <button
                        type="button"
                        class="tg-tool"
                        title="پاسخ‌های آماده"
                        data-answer-launch
                        data-answer-mobile="{{ $patient->mobile }}"
                        data-answer-name="{{ $patient->name }}"
                        data-answer-patient-id="{{ $patient->id }}"
                        data-answer-national-code="{{ $patient->national_code }}"
                        data-answer-mobile-secondary="{{ $patient->mobile_secondary }}"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3.75h9m-9 3.75H12m7.5-10.5h-15A2.25 2.25 0 002.25 7.5v9A2.25 2.25 0 004.5 18.75h15a2.25 2.25 0 002.25-2.25v-9A2.25 2.25 0 0019.5 5.25z" />
                        </svg>
                    </button>
                    @endif
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

            @if (session('success'))
                <x-flash type="success" class="mx-4 mt-3"
                         x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)"
                         x-show="show" x-transition.opacity>{{ session('success') }}</x-flash>
            @endif

            <div id="tg-feed" class="tg-chat__feed">
                <div class="tg-pill">شروع پرونده بیمار</div>

                @forelse ($timelineByDate as $dateLabel => $rows)
                    <div class="tg-pill">{{ $dateLabel }}</div>

                    @foreach ($rows as $row)
                        @if ($row['type'] === 'visit')
                            @php $visit = $row['item']; @endphp
                            @php
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
                            @endphp
                            <article id="timeline-visit-{{ $visit->id }}" class="tg-bubble tg-bubble--staff">
                                <div class="tg-bubble__meta">
                                    <span class="tg-bubble__tag">
                                        @if ($visit->voice_path && ! $visit->history && ! $visit->examination)
                                            ویس معاینه
                                        @elseif ($visit->drawing_path && ! $visit->history && ! $visit->examination)
                                            وایت‌برد
                                        @else
                                            معاینه
                                        @endif
                                    </span>
                                    <span dir="ltr">{{ jalali($visit->created_at, 'Y/m/d H:i') }}</span>
                                </div>

                                @if ($visit->drawing_path)
                                    <div class="relative mb-3">
                                        <button type="button" class="block w-full overflow-hidden rounded-xl text-right"
                                                onclick="PatientGallery.openDrawing({{ $drawingIndex }})"
                                                title="بزرگ‌نمایی وایت‌برد">
                                            <img src="{{ asset('storage/'.$visit->drawing_path) }}" alt="نقاشی معاینه"
                                                 class="max-h-64 w-full rounded-xl object-contain bg-white transition hover:opacity-95">
                                        </button>
                                    </div>
                                @endif

                                @if ($visit->voice_path)
                                    <div class="mb-3 rounded-xl border p-2" style="border-color: var(--line); background: var(--panel);">
                                        <div class="mb-2 flex items-center gap-2 text-xs font-semibold" style="color: var(--brand-dark);">
                                            <span class="flex items-center gap-2"><span class="pp-voice-pulse"></span>پیام صوتی</span>
                                        </div>
                                        <audio controls class="w-full">
                                            <source src="{{ asset('storage/'.$visit->voice_path) }}">
                                        </audio>
                                    </div>
                                @endif

                                @if ($examFields->isNotEmpty())
                                    <div class="tg-exam-fields">
                                        @foreach ($examFields as $field => $label)
                                            <div class="tg-exam-row">
                                                <div class="tg-exam-row__label">{{ $label }}</div>
                                                <div class="tg-exam-row__value">{{ $visit->{$field} }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if (! $visit->drawing_path && ! $visit->voice_path && $examFields->isEmpty())
                                    <p style="color: var(--muted);">معاینه بدون جزئیات ثبت شد.</p>
                                @endif

                                @if($canClinical)
                                    <x-item-actions>
                                        @if ($visit->drawing_path)
                                            <button type="button" class="aa-chip aa-chip--zoom"
                                                    onclick="this.closest('details')?.removeAttribute('open'); PatientGallery.openDrawing({{ $drawingIndex }})">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14zM11 8v6m-3-3h6"/></svg>
                                                بزرگ‌نمایی
                                            </button>
                                            <button type="button" class="aa-chip aa-chip--edit"
                                                    onclick="this.closest('details')?.removeAttribute('open')"
                                                    @click="openDrawingEditor({{ $visit->id }}, @js(asset('storage/'.$visit->drawing_path)))">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L8.832 17.82a4.5 4.5 0 01-1.897 1.13l-3.096.91 1.007-3.015a4.5 4.5 0 011.13-1.897L16.863 4.487z"/></svg>
                                                ویرایش
                                            </button>
                                        @endif
                                        @if ($examFields->isNotEmpty())
                                            <button type="button" class="aa-chip aa-chip--view"
                                                    onclick="this.closest('details')?.removeAttribute('open')"
                                                    @click='openExamPreview(@js([
                                                        "id" => $visit->id,
                                                        "at" => jalali($visit->created_at, "Y/m/d H:i"),
                                                        "history" => $visit->history,
                                                        "examination" => $visit->examination,
                                                        "diagnosis" => $visit->diagnosis,
                                                        "treatment" => $visit->treatment,
                                                    ]))'>
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                مشاهده
                                            </button>
                                            <button type="button" class="aa-chip aa-chip--edit"
                                                    onclick="this.closest('details')?.removeAttribute('open')"
                                                    @click="openExamEdit({{ $visit->id }})">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L8.832 17.82a4.5 4.5 0 01-1.897 1.13l-3.096.91 1.007-3.015a4.5 4.5 0 011.13-1.897L16.863 4.487z"/></svg>
                                                ویرایش
                                            </button>
                                        @endif
                                        <form method="POST" action="{{ route('visits.destroy', [$patient, $visit]) }}"
                                              onsubmit="return confirm('این مورد حذف شود؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="aa-chip aa-chip--delete">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                حذف
                                            </button>
                                        </form>
                                    </x-item-actions>
                                @endif

                                <x-audit-meta
                                    :creator="$visit->creator"
                                    :editor="$visit->editor"
                                    :created-at="$visit->created_at"
                                    :updated-at="$visit->updated_at"
                                    :was-edited="$visit->wasEdited()"
                                />
                            </article>
                        @elseif ($row['type'] === 'document')
                            @php $document = $row['item']; $docIndex = $patient->medicalDocuments->search(fn ($d) => $d->id === $document->id); @endphp
                            <article id="timeline-document-{{ $document->id }}" class="tg-bubble tg-bubble--staff">
                                <div class="tg-bubble__meta">
                                    <span class="tg-bubble__tag">تصویر · {{ $document->type }}</span>
                                    <span dir="ltr">{{ jalali($document->created_at, 'Y/m/d H:i') }}</span>
                                </div>
                                <button type="button" class="tg-photo" onclick="PatientGallery.open({{ $docIndex === false ? 0 : $docIndex }})">
                                    <img src="{{ asset('storage/'.$document->file_path) }}" alt="{{ $document->type }}" class="tg-photo__img">
                                </button>
                                @if ($document->description)
                                    <p class="mt-2 text-xs" style="color: var(--muted);">{{ $document->description }}</p>
                                @endif
                                <x-audit-meta
                                    :creator="$document->creator"
                                    :created-at="$document->created_at"
                                />
                            </article>
                        @elseif ($row['type'] === 'prescription')
                            @php $prescription = $row['item']; @endphp
                            <article id="timeline-prescription-{{ $prescription->id }}" class="tg-bubble tg-bubble--staff">
                                <div class="tg-bubble__meta">
                                    <span class="tg-bubble__tag" style="background:#ecfdf5;color:#047857;">نسخه دارو</span>
                                    <span dir="ltr">{{ jalali($prescription->created_at, 'Y/m/d H:i') }}</span>
                                </div>
                                <div class="space-y-2 text-xs">
                                    @foreach ($prescription->items as $item)
                                        <div class="rounded-lg border px-2.5 py-2" style="border-color: var(--line); background: var(--panel);">
                                            <div class="font-bold" style="color: var(--ink);">{{ $item->drug_name }}</div>
                                            <div class="mt-1 space-y-0.5" style="color: var(--muted);">
                                                @if($item->usage_type)<div>نوع: {{ $item->usage_type }}</div>@endif
                                                @if($item->dosage)<div>دوز: {{ $item->dosage }}</div>@endif
                                                @if($item->frequency)<div>دفعات: {{ $item->frequency }}</div>@endif
                                                @if($item->meal_timing)<div>زمان: {{ $item->mealTimingLabel() }}</div>@endif
                                                @if($item->duration)<div>مدت: {{ $item->duration }}</div>@endif
                                                @if($item->instructions)<div>{{ $item->instructions }}</div>@endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @if($prescription->notes)
                                    <p class="mt-2 text-xs" style="color: var(--muted);">{{ $prescription->notes }}</p>
                                @endif
                                @if($isStaff)
                                    <x-item-actions>
                                        <a href="{{ route('prescriptions.print', [$patient, $prescription]) }}" target="_blank" class="aa-chip">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            چاپ نسخه
                                        </a>
                                        @if($canClinical)
                                        <button type="button" class="aa-chip aa-chip--edit"
                                                onclick="this.closest('details')?.removeAttribute('open')"
                                                @click="openPrescriptionEdit({{ $prescription->id }})">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L8.832 17.82a4.5 4.5 0 01-1.897 1.13l-3.096.91 1.007-3.015a4.5 4.5 0 011.13-1.897L16.863 4.487z"/></svg>
                                            ویرایش
                                        </button>
                                        <form method="POST" action="{{ route('prescriptions.destroy', [$patient, $prescription]) }}"
                                              onsubmit="return confirm('این نسخه حذف شود؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="aa-chip aa-chip--delete">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                حذف
                                            </button>
                                        </form>
                                        @endif
                                    </x-item-actions>
                                @endif
                                <x-audit-meta
                                    :creator="$prescription->creator"
                                    :editor="$prescription->editor"
                                    :created-at="$prescription->created_at"
                                    :updated-at="$prescription->updated_at"
                                    :was-edited="$prescription->updated_by !== null"
                                />
                            </article>
                        @elseif ($row['type'] === 'note')
                            @php $note = $row['item']; @endphp
                            <article id="note-{{ $note->id }}" class="tg-bubble {{ (int) ($focusNoteId ?? 0) === (int) $note->id ? 'is-note-focus' : '' }}" x-data="{ editing: false }">
                                <div class="tg-bubble__meta">
                                    <span class="tg-bubble__tag" style="background: color-mix(in srgb, var(--warn) 15%, transparent); color: var(--warn);">{{ $note->user->name ?? 'کاربر' }}</span>
                                    <span dir="ltr">{{ jalali($note->created_at, 'Y/m/d H:i') }}</span>
                                </div>
                                <div x-show="!editing" class="whitespace-pre-line text-sm leading-7">{{ $note->note }}</div>
                                <form method="POST" action="{{ route('notes.update', [$patient, $note]) }}" class="space-y-2" x-show="editing" x-cloak>
                                    @csrf
                                    @method('PUT')
                                    <textarea name="note" rows="3" class="field-input" x-ref="noteInput">{{ $note->note }}</textarea>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="submit" class="aa-chip aa-chip--save">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                            ذخیره پیام
                                        </button>
                                        <button type="button" class="aa-chip aa-chip--cancel" @click="editing = false">انصراف</button>
                                    </div>
                                </form>
                                <div x-show="!editing">
                                    <x-item-actions>
                                        <button type="button" class="aa-chip aa-chip--edit"
                                                onclick="this.closest('details')?.removeAttribute('open')"
                                                @click="editing = true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L8.832 17.82a4.5 4.5 0 01-1.897 1.13l-3.096.91 1.007-3.015a4.5 4.5 0 011.13-1.897L16.863 4.487z"/></svg>
                                            ویرایش
                                        </button>
                                        <form method="POST" action="{{ route('notes.destroy', [$patient, $note]) }}"
                                              onsubmit="return confirm('پیام حذف شود؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="aa-chip aa-chip--delete">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                حذف
                                            </button>
                                        </form>
                                    </x-item-actions>
                                </div>
                            </article>
                        @elseif ($row['type'] === 'appointment')
                            @php
                                $appointment = $row['item'];
                                $vKey = 'visit-'.$appointment->id;
                                $vClosed = in_array($appointment->status, ['done', 'cancelled'], true);
                            @endphp
                            <article class="tg-bubble tg-bubble--staff !p-0 !border-0 !bg-transparent !shadow-none evt-wrap">
                                <div class="evt-card evt-card--visit @if($appointment->status === 'done') evt-card--done @elseif($appointment->status === 'cancelled') evt-card--cancelled @endif"
                                     :class="{ 'is-open': isSurgeryCardOpen(@js($vKey)) }">
                                    <button type="button" class="evt-card__bar"
                                            @click="toggleSurgeryCard(@js($vKey))"
                                            :aria-expanded="isSurgeryCardOpen(@js($vKey)) ? 'true' : 'false'">
                                        <span class="evt-card__icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 18.75V7.5a2.25 2.25 0 012.25-2.25h12a2.25 2.25 0 012.25 2.25v11.25m-16.5 0A2.25 2.25 0 006 21h12a2.25 2.25 0 002.25-2.25m-16.5 0V10.5h16.5v8.25"/>
                                                <path stroke-linecap="round" d="M8.25 14.25h3"/>
                                            </svg>
                                        </span>
                                        <div class="evt-card__bar-main">
                                            <span class="evt-card__kicker"><i class="evt-card__kicker-dot"></i> نوبت ویزیت</span>
                                            <span class="evt-card__title">{{ $appointment->patient_name }}</span>
                                            <span class="evt-card__sub evt-card__sub--bar">
                                                {{ $appointment->visit_type ?: 'ویزیت' }}@if($appointment->reason) · {{ $appointment->reason }}@endif
                                            </span>
                                        </div>
                                        <div class="evt-card__bar-side">
                                            <span class="evt-tag evt-tag--status-{{ $appointment->status }}">{{ \App\Support\BookingStatus::label($appointment->status) }}</span>
                                            <span class="evt-card__bar-when" dir="ltr">
                                                {{ jalali($appointment->scheduled_date, 'Y/m/d') }}
                                                · {{ $appointment->scheduled_time ? \App\Support\SlotLabel::display((string) $appointment->scheduled_time) : '—' }}
                                            </span>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="evt-card__chevron" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                            </svg>
                                        </div>
                                    </button>

                                    <div class="evt-card__drawer" x-show="isSurgeryCardOpen(@js($vKey))" x-cloak>
                                        <div class="evt-when">
                                            <div class="evt-when__group">
                                                <div class="evt-when__item">
                                                    <span class="evt-when__label">تاریخ نوبت</span>
                                                    <span class="evt-when__value" dir="ltr">{{ jalali($appointment->scheduled_date, 'Y/m/d') }}</span>
                                                </div>
                                                <div class="evt-when__item">
                                                    <span class="evt-when__label">ساعت</span>
                                                    <span class="evt-when__value">{{ $appointment->scheduled_time ? \App\Support\SlotLabel::display((string) $appointment->scheduled_time) : '—' }}</span>
                                                </div>
                                                <div class="evt-when__item">
                                                    <span class="evt-when__label">ثبت</span>
                                                    <span class="evt-when__value" dir="ltr" style="font-size:.78rem">{{ jalali($appointment->created_at, 'Y/m/d H:i') }}</span>
                                                </div>
                                                <div class="evt-when__item">
                                                    <span class="evt-when__label">ثبت‌کننده</span>
                                                    <span class="evt-when__value" style="font-size:.78rem">{{ $appointment->creator?->name ?: ($appointment->isFromHis() ? 'HIS' : '—') }}</span>
                                                </div>
                                            </div>
                                            @unless($vClosed)
                                                <x-day-countdown :date="$appointment->scheduled_date" />
                                            @endunless
                                        </div>

                                        <dl class="evt-facts">
                                            <div class="evt-facts__row">
                                                <dt>کد ملی</dt>
                                                <dd dir="ltr">{{ $appointment->national_code ?: '—' }}</dd>
                                            </div>
                                            <div class="evt-facts__row">
                                                <dt>موبایل</dt>
                                                <dd dir="ltr">
                                                    @if($appointment->mobile)
                                                        <a href="tel:{{ $appointment->mobile }}" class="hover:underline">{{ $appointment->mobile }}</a>
                                                    @else
                                                        —
                                                    @endif
                                                </dd>
                                            </div>
                                            @if($isStaff && $appointment->notes)
                                                <div class="evt-facts__row evt-facts__row--full">
                                                    <dt>یادداشت</dt>
                                                    <dd>{{ $appointment->notes }}</dd>
                                                </div>
                                            @endif
                                        </dl>

                                        <div class="evt-card__foot">
                                            <x-audit-meta :creator="$appointment->creator" :created-at="$appointment->created_at" />
                                            <x-booking-status-actions :model="$appointment" type="visit" :show-status="false" />
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @elseif ($row['type'] === 'surgery_created' || $row['type'] === 'surgery_due')
                            @php
                                $surgery = $row['item'];
                                $isDue = $row['type'] === 'surgery_due';
                                $sKey = $row['type'].'-'.$surgery->id.'-'.jalali($row['at'], 'Ymd');
                                $sClosed = in_array($surgery->status, ['done', 'cancelled'], true);
                            @endphp
                            <article class="tg-bubble tg-bubble--staff !p-0 !border-0 !bg-transparent !shadow-none evt-wrap">
                                <div class="evt-card {{ $isDue ? 'evt-card--due' : 'evt-card--surgery' }} @if($surgery->status === 'done') evt-card--done @elseif($surgery->status === 'cancelled') evt-card--cancelled @endif"
                                     :class="{ 'is-open': isSurgeryCardOpen(@js($sKey)) }">
                                    <button type="button" class="evt-card__bar"
                                            @click="toggleSurgeryCard(@js($sKey))"
                                            :aria-expanded="isSurgeryCardOpen(@js($sKey)) ? 'true' : 'false'">
                                        <span class="evt-card__icon" aria-hidden="true">
                                            @if($isDue)
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                                </svg>
                                            @else
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L4.2 15.3"/>
                                                </svg>
                                            @endif
                                        </span>
                                        <div class="evt-card__bar-main">
                                            <span class="evt-card__kicker"><i class="evt-card__kicker-dot"></i> {{ $isDue ? 'سررسید عمل' : 'ثبت نوبت عمل' }}</span>
                                            <span class="evt-card__title">{{ $surgery->surgery_type }}</span>
                                            <span class="evt-card__sub evt-card__sub--bar">
                                                @if($surgery->eye_side) {{ $surgery->eye_side }} @endif
                                                @if($surgery->hospital) @if($surgery->eye_side) · @endif {{ $surgery->hospital->name }} @endif
                                            </span>
                                        </div>
                                        <div class="evt-card__bar-side">
                                            <span class="evt-tag evt-tag--status-{{ $surgery->status }}">{{ \App\Support\BookingStatus::label($surgery->status) }}</span>
                                            @if($surgery->is_emergency)
                                                <span class="evt-tag evt-tag--emergency">اورژانس</span>
                                            @endif
                                            <span class="evt-card__bar-when" dir="ltr">{{ jalali($surgery->scheduled_date, 'Y/m/d') }}</span>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="evt-card__chevron" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                            </svg>
                                        </div>
                                    </button>

                                    <div class="evt-card__drawer" x-show="isSurgeryCardOpen(@js($sKey))" x-cloak>
                                        <div class="evt-tags">
                                            @if($surgery->is_exception)
                                                <span class="evt-tag evt-tag--exception">استثنا</span>
                                            @endif
                                        </div>

                                        <div class="evt-when">
                                            <div class="evt-when__group">
                                                <div class="evt-when__item">
                                                    <span class="evt-when__label">تاریخ عمل</span>
                                                    <span class="evt-when__value" dir="ltr">{{ jalali($surgery->scheduled_date, 'Y/m/d') }}</span>
                                                </div>
                                                @if($surgery->scheduled_time)
                                                    <div class="evt-when__item">
                                                        <span class="evt-when__label">ساعت</span>
                                                        <span class="evt-when__value">{{ \App\Support\SlotLabel::display((string) $surgery->scheduled_time) }}</span>
                                                    </div>
                                                @endif
                                                @if($surgery->surgeon_name)
                                                    <div class="evt-when__item">
                                                        <span class="evt-when__label">جراح</span>
                                                        <span class="evt-when__value" style="font-size:.78rem">{{ $surgery->surgeon_name }}</span>
                                                    </div>
                                                @endif
                                                @unless($isDue)
                                                    <div class="evt-when__item">
                                                        <span class="evt-when__label">ثبت</span>
                                                        <span class="evt-when__value" dir="ltr" style="font-size:.78rem">{{ jalali($surgery->created_at, 'Y/m/d H:i') }}</span>
                                                    </div>
                                                    <div class="evt-when__item">
                                                        <span class="evt-when__label">ثبت‌کننده</span>
                                                        <span class="evt-when__value" style="font-size:.78rem">{{ $surgery->creator?->name ?: ($surgery->isFromHis() ? 'HIS' : '—') }}</span>
                                                    </div>
                                                @endunless
                                            </div>
                                            @unless($sClosed)
                                                <x-day-countdown :date="$surgery->scheduled_date" />
                                            @endunless
                                        </div>

                                        <dl class="evt-facts">
                                            @if($surgery->hospital)
                                                <div class="evt-facts__row">
                                                    <dt>بیمارستان</dt>
                                                    <dd>{{ $surgery->hospital->name }}</dd>
                                                </div>
                                            @endif
                                            <div class="evt-facts__row">
                                                <dt>موبایل</dt>
                                                <dd dir="ltr">
                                                    @if($surgery->mobile)
                                                        <a href="tel:{{ $surgery->mobile }}" class="hover:underline">{{ $surgery->mobile }}</a>
                                                    @else
                                                        —
                                                    @endif
                                                </dd>
                                            </div>
                                            @if($isStaff && $surgery->notes)
                                                <div class="evt-facts__row evt-facts__row--full">
                                                    <dt>یادداشت</dt>
                                                    <dd>{{ $surgery->notes }}</dd>
                                                </div>
                                            @endif
                                        </dl>

                                        @if(\App\Support\SurgeryChecklist::isAvailable())
                                            @include('patients.partials.surgery-checklist', ['checklist' => $surgery->checklist])
                                        @endif

                                        <div class="evt-card__foot">
                                            @unless($isDue)
                                                <x-audit-meta :creator="$surgery->creator" :created-at="$surgery->created_at" />
                                            @endunless
                                            <x-booking-status-actions :model="$surgery" type="surgery" :show-status="false" />
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endif
                    @endforeach
                @empty
                    <div class="tg-bubble tg-bubble--system">
                        هنوز چیزی در پرونده ثبت نشده. از نوار پایین معاینه، ویس یا تصویر اضافه کنید.
                    </div>
                @endforelse
            </div>

            {{-- Composer --}}
            @if($canClinical)
                <div class="tg-composer">
                    <form
                        method="POST"
                        action="{{ route('notes.store', $patient) }}"
                        class="space-y-2"
                    >
                        @csrf
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
                                        @if($whiteboardEnabled)
                                        <button type="button" class="tg-tool" title="وایت‌برد" aria-label="وایت‌برد"
                                                @click="quickToolsOpen = false; openPanel('whiteboard')">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-10.94.546 10.94-.546a4.5 4.5 0 001.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>
                                        @endif
                                        <button type="button" class="tg-tool" title="فرم کامل معاینه" aria-label="فرم کامل معاینه"
                                                @click="quickToolsOpen = false; openPanel('exam')">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <textarea
                                name="note"
                                rows="1"
                                required
                                class="tg-composer__input"
                                placeholder="پیام برای پزشک، منشی و مدیر..."
                                @keydown.enter="if ($event.shiftKey) { $event.preventDefault(); $el.form.requestSubmit(); }"
                            >{{ old('note') }}</textarea>

                            <button type="submit" class="tg-composer__send" title="ارسال (Shift+Enter)" aria-label="ارسال">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                                </svg>
                            </button>
                        </div>
                        <p id="voice-status" class="text-[11px]" style="color: var(--muted);"></p>
                        <p class="text-[11px]" style="color: var(--muted);">Enter خط جدید · Shift+Enter ارسال · برای عکس Ctrl+V یا ابزار آپلود</p>
                        <div id="voice-preview" class="hidden rounded-xl border p-2 text-xs" style="border-color: var(--line); background: var(--panel-soft);">
                            <p class="mb-2 font-bold" style="color: var(--ink);">پیش‌نمایش ویس — ارسال شود؟</p>
                            <audio id="voice-preview-audio" controls class="mb-2 w-full"></audio>
                            <div class="flex gap-2">
                                <button type="button" class="btn-primary !py-1.5 !text-xs" id="send-voice-btn">ارسال ویس</button>
                                <button type="button" class="btn-secondary !py-1.5 !text-xs" id="discard-voice-btn">حذف</button>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('note')" />
                    </form>
                </div>
            @elseif($isStaff)
                <div class="tg-composer">
                    <form method="POST" action="{{ route('notes.store', $patient) }}" class="mb-3 space-y-2">
                        @csrf
                        <div class="tg-composer__bar">
                            <textarea
                                name="note"
                                rows="1"
                                required
                                class="tg-composer__input"
                                placeholder="پیام برای پزشک، منشی و مدیر..."
                                @keydown.enter="if ($event.shiftKey) { $event.preventDefault(); $el.form.requestSubmit(); }"
                            >{{ old('note') }}</textarea>
                            <button type="submit" class="tg-composer__send" title="ارسال (Shift+Enter)" aria-label="ارسال پیام">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                                </svg>
                            </button>
                        </div>
                        <p class="text-[11px]" style="color: var(--muted);">Enter خط جدید · Shift+Enter ارسال</p>
                        <x-input-error :messages="$errors->get('note')" />
                    </form>
                    <div class="tg-composer-actions">
                        <button type="button" class="btn-secondary" @click="openPanel('upload')">آپلود تصویر پزشکی</button>
                        @if(auth()->user()?->canEditPatient() && ! str_starts_with((string) $patient->national_code, 'DEL-'))
                            <a href="{{ route('patients.edit', ['patient' => $patient, 'from_file' => 1]) }}" class="btn-secondary">ویرایش بیمار</a>
                        @endif
                        <a href="{{ route('appointments.create', $patient) }}" class="btn-secondary">ثبت ویزیت</a>
                        <a href="{{ route('surgery-appointments.create', $patient) }}" class="btn-secondary" onclick="window.open(this.href, 'surgery-register'); return false;">ثبت نوبت عمل</a>
                        @if($followupEnabled && \App\Support\PatientFollowUps::isAvailable())
                            <button type="button" class="btn-secondary" @click="openPanel('followup')">پیگیری‌ها</button>
                        @endif
                    </div>
                </div>
            @endif
        </section>

        {{-- Mobile profile drawer --}}
            <div class="tg-drawer lg:hidden" x-show="panel === 'profile'" x-cloak @click.self="closePanel()" x-transition.opacity>
            <div class="tg-drawer__panel">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <div class="h-1.5 w-10 shrink-0 rounded-full sm:hidden" style="background: var(--line);"></div>
                        <h3 class="truncate font-bold" style="color: var(--ink);">مشخصات بیمار</h3>
                    </div>
                    <button type="button" class="tg-tool shrink-0" @click="closePanel()" aria-label="بستن"><x-icon-close /></button>
                </div>
                @include('patients.partials.profile-sidebar', compact('patient', 'isStaff', 'canClinical', 'visitCount', 'docCount', 'noteCount', 'voiceCount', 'drawingCount', 'activityTotal', 'examVisits', 'rxCount', 'pendingFollowUps', 'careFollowUps'))
            </div>
        </div>

        @if($isStaff && $followupEnabled && \App\Support\PatientFollowUps::isAvailable())
        <div class="tg-drawer" x-show="panel === 'followup'" x-cloak @click.self="closePanel()">
            <div class="tg-drawer__panel max-w-xl">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="font-bold" style="color: var(--ink);">پیگیری‌های بیمار</h3>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('followups.index', ['q' => $patient->national_code ?: $patient->name]) }}" class="btn-ghost !py-1 !text-[11px]">داشبورد</a>
                        <button type="button" class="tg-tool shrink-0" @click="closePanel()"><x-icon-close /></button>
                    </div>
                </div>
                @include('patients.partials.follow-ups')
            </div>
        </div>
        @endif

        {{-- Media browser (Telegram-style) --}}
        @if($isStaff)
        <div class="tg-drawer" x-show="panel === 'media'" x-cloak @click.self="closePanel()">
            <div class="tg-drawer__panel max-w-xl">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <div class="tg-media-tabs">
                        <button type="button" class="tg-media-tab" :class="mediaTab === 'photos' && 'is-active'" @click="mediaTab = 'photos'">عکس</button>
                        <button type="button" class="tg-media-tab" :class="mediaTab === 'drawings' && 'is-active'" @click="mediaTab = 'drawings'">وایت‌برد</button>
                        <button type="button" class="tg-media-tab" :class="mediaTab === 'exams' && 'is-active'" @click="mediaTab = 'exams'">معاینه</button>
                        <button type="button" class="tg-media-tab" :class="mediaTab === 'rx' && 'is-active'" @click="mediaTab = 'rx'">دارو</button>
                    </div>
                    <button type="button" class="tg-tool shrink-0" @click="closePanel()"><x-icon-close /></button>
                </div>

                <div x-show="mediaTab === 'photos'" x-cloak>
                    @if($canClinical)
                        <button type="button" class="btn-secondary mb-3 w-full !py-2 !text-xs" @click="openPanel('upload')">+ افزودن عکس</button>
                    @endif
                    @if ($patient->medicalDocuments->isEmpty())
                        <p class="pp-empty text-sm">هنوز عکسی ثبت نشده.</p>
                    @else
                        <div class="tg-media-grid">
                            @foreach ($patient->medicalDocuments as $docIndex => $document)
                                <button type="button" class="tg-media-thumb" onclick="PatientGallery.open({{ $docIndex }})" title="{{ $document->type }}">
                                    <img src="{{ asset('storage/'.$document->file_path) }}" alt="{{ $document->type }}">
                                    <span class="tg-media-thumb__meta" dir="ltr">{{ jalali($document->created_at, 'Y/m/d') }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div x-show="mediaTab === 'drawings'" x-cloak>
                    @if($canClinical && $whiteboardEnabled)
                        <button type="button" class="btn-secondary mb-3 w-full !py-2 !text-xs" @click="openPanel('whiteboard')">+ وایت‌برد جدید</button>
                    @endif
                    @if ($drawingVisits->isEmpty())
                        <p class="pp-empty text-sm">وایت‌بردی ثبت نشده.</p>
                    @else
                        <div class="tg-media-grid">
                            @foreach ($drawingVisits as $drawingIndex => $drawing)
                                <button type="button" class="tg-media-thumb" onclick="PatientGallery.openDrawing({{ $drawingIndex }})" title="بزرگ‌نمایی وایت‌برد">
                                    <img src="{{ asset('storage/'.$drawing->drawing_path) }}" alt="وایت‌برد">
                                    <span class="tg-media-thumb__meta" dir="ltr">{{ jalali($drawing->created_at, 'Y/m/d H:i') }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div x-show="mediaTab === 'exams'" x-cloak>
                    @if($canClinical)
                        <button type="button" class="btn-secondary mb-3 w-full !py-2 !text-xs" @click="openPanel('exam')">+ معاینه جدید</button>
                    @endif
                    @if ($examVisits->isEmpty())
                        <p class="pp-empty text-sm">معاینه متنی ثبت نشده.</p>
                    @else
                        <div class="space-y-2">
                            @foreach ($examVisits->sortByDesc('created_at') as $examVisit)
                                @php
                                    $preview = $examVisit->examination ?: $examVisit->diagnosis ?: $examVisit->history ?: $examVisit->treatment;
                                    $preview = \Illuminate\Support\Str::limit((string) $preview, 80);
                                @endphp
                                <button type="button" class="tg-media-list-item"
                                        @click='openExamPreview(@js([
                                            "id" => $examVisit->id,
                                            "at" => jalali($examVisit->created_at, "Y/m/d H:i"),
                                            "history" => $examVisit->history,
                                            "examination" => $examVisit->examination,
                                            "diagnosis" => $examVisit->diagnosis,
                                            "treatment" => $examVisit->treatment,
                                        ]))'>
                                    <span class="tg-media-list-item__date" dir="ltr">{{ jalali($examVisit->created_at, 'Y/m/d H:i') }}</span>
                                    <span class="tg-media-list-item__text">{{ $preview ?: 'معاینه' }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div x-show="mediaTab === 'rx'" x-cloak>
                    @if($canClinical)
                        <button type="button" class="btn-secondary mb-3 w-full !py-2 !text-xs" @click="openPanel('prescription')">+ نسخه جدید</button>
                    @endif
                    @if (($patient->prescriptions ?? collect())->isEmpty())
                        <p class="pp-empty text-sm">نسخه‌ای ثبت نشده.</p>
                    @else
                        <div class="space-y-2">
                            @foreach ($patient->prescriptions ?? [] as $rx)
                                <div class="rounded-xl border p-2" style="border-color: var(--line); background: var(--panel-soft);">
                                    <button type="button" class="tg-media-list-item !border-0 !bg-transparent !p-0 w-full"
                                            @click="isEmbed ? openPrescriptionEdit({{ $rx->id }}) : jumpToItem('timeline-prescription-{{ $rx->id }}')">
                                        <span class="tg-media-list-item__date" dir="ltr">{{ jalali($rx->created_at, 'Y/m/d H:i') }}</span>
                                        <span class="tg-media-list-item__text">{{ $rx->items->pluck('drug_name')->join(' · ') }}</span>
                                    </button>
                                    <a href="{{ route('prescriptions.print', [$patient, $rx]) }}" target="_blank" class="btn-secondary mt-2 inline-flex !px-3 !py-1.5 !text-[11px]">
                                        چاپ نسخه
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <div class="tg-drawer" x-show="panel === 'exam-view'" x-cloak @click.self="closePanel()">
            <div class="tg-drawer__panel max-w-3xl">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <div>
                        <h3 class="font-bold" style="color: var(--ink);">نمایش معاینه</h3>
                        <p class="text-xs" style="color: var(--muted);" dir="ltr" x-text="examPreview?.at || ''"></p>
                    </div>
                    <button type="button" class="tg-tool shrink-0" @click="closePanel()"><x-icon-close /></button>
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

        @if($isStaff)
            @if($canClinical)
            @include('patients.partials.prescription-drawer', ['patient' => $patient, 'drugsCatalog' => $drugsCatalog])
            {{-- Exam drawer --}}
            <div class="tg-drawer" x-show="panel === 'exam'" x-cloak @click.self="closePanel()">
                <div class="tg-drawer__panel" x-data="examForm(@js(route('visits.store', $patient)), @js($examVisitsPayload), @js($focusVisitId))"
                     @exam-edit.window="loadVisit($event.detail)" @exam-reset.window="resetForm()">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold" style="color: var(--ink);" x-text="editingId ? 'ویرایش معاینه' : 'ثبت معاینه کامل'"></h3>
                            <p class="text-xs" style="color: var(--muted);">شرح حال، معاینه، تشخیص و درمان</p>
                        </div>
                        <button type="button" class="tg-tool" @click="closePanel()"><x-icon-close /></button>
                    </div>
                    <form method="POST" :action="formAction" class="grid gap-4 sm:grid-cols-2">
                        @csrf
                        <template x-if="editingId">
                            <input type="hidden" name="_method" value="PUT">
                        </template>
                        <div class="sm:col-span-2">
                            <x-input-label for="history" value="شرح حال" />
                            <textarea id="history" name="history" rows="3" class="field-input" x-model="fields.history" x-ref="history" @focus="activeField = 'history'"></textarea>
                        </div>
                        <div>
                            <x-input-label for="examination_full" value="معاینه" />
                            <textarea id="examination_full" name="examination" rows="4" class="field-input" x-model="fields.examination" x-ref="examination" @focus="activeField = 'examination'"></textarea>
                        </div>
                        <div>
                            <x-input-label for="diagnosis" value="تشخیص" />
                            <textarea id="diagnosis" name="diagnosis" rows="4" class="field-input" x-model="fields.diagnosis" x-ref="diagnosis" @focus="activeField = 'diagnosis'"></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="treatment" value="درمان" />
                            <textarea id="treatment" name="treatment" rows="3" class="field-input" x-model="fields.treatment" x-ref="treatment" @focus="activeField = 'treatment'"></textarea>
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="next_instruction" value="دستور بعدی / پیگیری" />
                            <textarea id="next_instruction" name="next_instruction" rows="2" class="field-input" x-model="fields.next_instruction" x-ref="next_instruction" @focus="activeField = 'next_instruction'" placeholder="مثلاً کنترل یک هفته بعد، قطره…"></textarea>
                        </div>

                        <div>
                            <x-input-label for="eye_side" value="چشم" />
                            <select id="eye_side" name="eye_side" class="field-input" x-model="fields.eye_side">
                                <option value="">—</option>
                                @foreach (\App\Support\EyeSide::options() as $side)
                                    <option value="{{ $side['value'] }}">{{ $side['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <x-input-label for="va_right" value="VA راست" />
                                <input id="va_right" name="va_right" type="text" class="field-input ltr-data" dir="ltr" x-model="fields.va_right" placeholder="مثلاً 10/10">
                            </div>
                            <div>
                                <x-input-label for="va_left" value="VA چپ" />
                                <input id="va_left" name="va_left" type="text" class="field-input ltr-data" dir="ltr" x-model="fields.va_left" placeholder="مثلاً 8/10">
                            </div>
                            <div>
                                <x-input-label for="iop_right" value="IOP راست" />
                                <input id="iop_right" name="iop_right" type="text" class="field-input ltr-data" dir="ltr" x-model="fields.iop_right" placeholder="mmHg">
                            </div>
                            <div>
                                <x-input-label for="iop_left" value="IOP چپ" />
                                <input id="iop_left" name="iop_left" type="text" class="field-input ltr-data" dir="ltr" x-model="fields.iop_left" placeholder="mmHg">
                            </div>
                        </div>

                        @if($activeVisitId)
                            <input type="hidden" name="appointment_id" value="{{ optional($patient->visits->firstWhere('id', $activeVisitId))->appointment_id }}">
                            <input type="hidden" name="surgery_appointment_id" value="{{ optional($patient->visits->firstWhere('id', $activeVisitId))->surgery_appointment_id }}">
                        @elseif($focusSurgeryId)
                            <input type="hidden" name="surgery_appointment_id" value="{{ $focusSurgeryId }}">
                        @endif

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
            @endif

            {{-- Upload drawer --}}
            <div class="tg-drawer" x-show="panel === 'upload'" x-cloak @click.self="closePanel()">
                <div class="tg-drawer__panel max-w-xl">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold" style="color: var(--ink);">آپلود تصویر پزشکی</h3>
                            <p class="text-xs" style="color: var(--muted);">دوربین، گالری، یا چسباندن عکس با Ctrl+V در پرونده</p>
                        </div>
                        <button type="button" class="tg-tool" @click="closePanel()"><x-icon-close /></button>
                    </div>
                    <form
                        method="POST"
                        action="{{ route('documents.store', $patient) }}"
                        enctype="multipart/form-data"
                        class="space-y-4"
                        x-data="docUploadForm()"
                        @submit="prepareSubmit($event)"
                        @patient-upload-files.window="addFiles($event.detail.files, $event.detail.source || 'paste')"
                    >
                        @csrf
                        <div>
                            <x-input-label for="type" value="نوع تصویر" />
                            <select id="type" name="type" required class="field-input">
                                <option value="" disabled {{ old('type') ? '' : 'selected' }}>انتخاب کنید</option>
                                @foreach (['Fundus', 'OCT', 'آنژیو', 'عکس خارجی', 'اسکن', 'نسخه', 'سایر'] as $type)
                                    <option value="{{ $type }}" @selected(old('type') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('type')" />
                        </div>

                        <div>
                            <x-input-label value="تصویر" />
                            <input type="file" name="files[]" accept="image/*" multiple class="sr-only" x-ref="files" tabindex="-1" aria-hidden="true">

                            {{-- Pickers: camera opens rear camera on phones; gallery is library --}}
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

                            <div class="doc-upload-preview mt-3" x-show="previews.length" x-cloak>
                                <template x-for="(item, index) in previews" :key="item.url + index">
                                    <img :src="item.url" :alt="item.name" class="doc-upload-preview__img">
                                </template>
                                <div class="doc-upload-preview__meta">
                                    <strong x-text="fileName"></strong>
                                    <span x-text="sourceLabel"></span>
                                    <span x-show="filesCount > 1" x-text="filesCount + ' فایل آماده آپلود'"></span>
                                    <button type="button" class="doc-upload-preview__clear" @click="clearFile()">حذف</button>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-rose-600" x-show="fileError" x-text="fileError" x-cloak></p>
                            <x-input-error class="mt-2" :messages="$errors->get('files')" />
                            <x-input-error class="mt-2" :messages="$errors->get('files.*')" />
                        </div>

                        <div>
                            <x-input-label for="description" value="توضیحات" />
                            <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description')" />
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="btn-primary" :disabled="submitting" x-text="submitting ? 'در حال آپلود...' : 'آپلود'"></button>
                            <button type="button" class="btn-secondary" @click="closePanel()">بستن</button>
                        </div>
                    </form>
                </div>
            </div>

            @if($canClinical && $whiteboardEnabled)
            {{-- Whiteboard drawer --}}
            <div class="tg-drawer tg-drawer--whiteboard" x-show="panel === 'whiteboard'" x-cloak @click.self="closePanel()">
                <div class="tg-drawer__panel tg-whiteboard-panel" id="whiteboard-panel">
                    <div class="mb-2 flex items-center justify-end gap-2">
                        <button type="button" class="tg-tool" id="whiteboard-fullscreen-btn" title="تمام‌صفحه" aria-label="تمام‌صفحه">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/></svg>
                        </button>
                        <button type="button" class="tg-tool" @click="closePanel()"><x-icon-close /></button>
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
                                data-patient-id="{{ $patient->id }}"
                                data-store-url="{{ route('visits.store-drawing') }}"></canvas>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" id="clear-canvas-btn" class="btn-secondary">پاک کردن</button>
                        <button type="button" id="save-canvas-btn" class="btn-primary">ذخیره در پرونده</button>
                    </div>
                </div>
            </div>
            @endif
        @endif
    </div>

    {{-- Telegram-style photo viewer: always viewport-sized, even when opened from the toolbox iframe --}}
    <div id="pg-modal" class="pg-modal" hidden>
        <button type="button" class="pg-modal__close" data-pg-close aria-label="بستن">&times;</button>
        <div class="pg-modal__box">
            <div class="pg-modal__chrome pg-modal__chrome--top">
                <div class="pg-modal__head">
                    <strong id="pg-heading">گالری تصاویر</strong>
                    <span id="pg-title" class="pg-modal__title"></span>
                </div>
                <div class="pg-modal__meta">
                    <span>{{ $patient->name }}</span>
                    <span id="pg-date" dir="ltr"></span>
                </div>
                <div class="pg-modal__actions">
                    @if($isStaff)
                        <form id="pg-delete-form" method="POST" action="#">
                            @csrf
                            @method('DELETE')
                            @if($isEmbed)
                                <input type="hidden" name="embed" value="1">
                                <input type="hidden" name="open" value="{{ request('open', 'photos') }}">
                            @endif
                            <button type="submit" class="pg-modal__text-btn pg-modal__text-btn--danger">حذف</button>
                        </form>
                    @endif
                    @if($canClinical)
                        <button type="button" id="pg-edit-drawing" class="pg-modal__text-btn" style="display:none;" onclick="PatientGallery.editCurrent()">ویرایش وایت‌برد</button>
                    @endif
                    <div class="pg-zoom-controls" aria-label="بزرگ‌نمایی و چرخش">
                        <button type="button" class="pg-zoom-btn" onclick="PatientGallery.zoomOut()" title="کوچک‌نمایی">−</button>
                        <button type="button" class="pg-zoom-btn" onclick="PatientGallery.resetZoom()" title="اندازه اصلی" id="pg-zoom-label">۱۰۰٪</button>
                        <button type="button" class="pg-zoom-btn" onclick="PatientGallery.zoomIn()" title="بزرگ‌نمایی">+</button>
                    </div>
                    <div class="pg-zoom-controls" id="pg-rotate-controls" aria-label="چرخش">
                        <button type="button" class="pg-zoom-btn" onclick="PatientGallery.rotate('ccw')" title="چرخش به چپ (عمودی / افقی)">↺</button>
                        <button type="button" class="pg-zoom-btn" onclick="PatientGallery.rotate('cw')" title="چرخش به راست (عمودی / افقی)">↻</button>
                    </div>
                </div>
            </div>
            <div class="pg-modal__stage" id="pg-stage">
                <button type="button" class="pg-modal__nav pg-modal__nav--prev" onclick="PatientGallery.prev()" aria-label="قبلی">‹</button>
                <div class="pg-modal__viewport" id="pg-viewport">
                    <img id="pg-image" src="" alt="" draggable="false">
                </div>
                <button type="button" class="pg-modal__nav pg-modal__nav--next" onclick="PatientGallery.next()" aria-label="بعدی">›</button>
            </div>
            <div class="pg-modal__chrome pg-modal__chrome--bottom">
                <p id="pg-counter" class="pg-modal__counter"></p>
                <div id="pg-thumbs" class="pg-modal__thumbs"></div>
            </div>
        </div>
    </div>

    <style id="pg-viewer-css">
        html.pg-viewer-open,
        body.pg-viewer-open { overflow: hidden !important; }

        .pg-modal {
            position: fixed !important;
            inset: 0 !important;
            z-index: 400000 !important;
            display: none;
            width: 100vw !important;
            width: 100dvw !important;
            height: 100vh !important;
            height: 100dvh !important;
            max-width: none !important;
            max-height: none !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: #000 !important;
            color: #fff !important;
            box-shadow: none !important;
        }
        .pg-modal.is-open { display: flex !important; flex-direction: column !important; }

        .pg-modal__box {
            display: flex !important;
            flex-direction: column !important;
            flex: 1 1 auto !important;
            width: 100% !important;
            height: 100% !important;
            max-width: none !important;
            max-height: none !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
            background: #000 !important;
            color: #fff !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }

        .pg-modal__close {
            position: absolute !important;
            top: max(14px, env(safe-area-inset-top, 0px)) !important;
            inset-inline-start: max(14px, env(safe-area-inset-left, 0px), env(safe-area-inset-right, 0px)) !important;
            z-index: 30 !important;
            width: 48px !important;
            height: 48px !important;
            border: 0 !important;
            border-radius: 999px !important;
            background: rgba(255,255,255,.16) !important;
            color: #fff !important;
            cursor: pointer !important;
            pointer-events: auto !important;
            font-size: 32px !important;
            font-weight: 300 !important;
            line-height: 1 !important;
            display: grid !important;
            place-items: center !important;
            box-shadow: 0 0 0 1px rgba(255,255,255,.18) !important;
        }
        .pg-modal__close:hover { background: rgba(255,255,255,.28) !important; }
        .pg-modal__close:focus-visible { outline: 2px solid #fff; outline-offset: 3px; }

        .pg-modal__chrome {
            position: absolute;
            z-index: 4;
            left: 0;
            right: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
            pointer-events: none;
            background: linear-gradient(to bottom, rgba(0,0,0,.72), rgba(0,0,0,0));
            padding: max(16px, env(safe-area-inset-top, 0px)) 16px 28px;
            padding-inline-start: 72px;
            transition: opacity .18s ease;
        }
        .pg-modal__chrome--bottom {
            top: auto;
            bottom: 0;
            background: linear-gradient(to top, rgba(0,0,0,.78), rgba(0,0,0,0));
            padding: 28px 16px max(16px, env(safe-area-inset-bottom, 0px));
            align-items: center;
        }
        .pg-modal__chrome > * { pointer-events: auto; }

        .pg-modal__head {
            display: flex;
            align-items: baseline;
            justify-content: flex-end;
            gap: 10px;
            padding: 0 !important;
            color: #fff;
        }
        .pg-modal__head strong { font-size: 15px; font-weight: 800; }
        .pg-modal__meta {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 0 !important;
            font-size: 13px;
            color: rgba(255,255,255,.78) !important;
        }
        .pg-modal__actions {
            display: flex;
            gap: 8px;
            padding: 8px 0 0 !important;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
        }
        .pg-modal__title { color: rgba(255,255,255,.7) !important; font-size: 13px; font-weight: 700; }
        .pg-modal__text-btn {
            min-height: 36px;
            padding: 0 14px;
            border: 0;
            border-radius: 999px;
            background: rgba(255,255,255,.14);
            color: #fff;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
        }
        .pg-modal__text-btn--danger { background: rgba(220, 38, 38, .88); }
        .pg-zoom-controls {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            border: 0;
            border-radius: 999px;
            padding: 2px;
            background: rgba(255,255,255,.14);
        }
        .pg-zoom-btn {
            min-width: 2.1rem;
            height: 2.1rem;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: #fff;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            padding: 0 .55rem;
        }
        .pg-zoom-btn:hover { background: rgba(255,255,255,.16); }

        .pg-modal__stage {
            position: relative !important;
            flex: 1 1 auto !important;
            margin: 0 !important;
            padding: 0 !important;
            min-height: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            overflow: hidden !important;
            background: #000 !important;
            border-radius: 0 !important;
            touch-action: none;
        }
        .pg-modal__viewport {
            width: 100% !important;
            height: 100% !important;
            max-height: none !important;
            min-height: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            overflow: hidden !important;
            cursor: grab;
            user-select: none;
        }
        .pg-modal__viewport.is-dragging { cursor: grabbing; }
        .pg-modal__viewport img,
        .pg-modal__stage img {
            max-width: none !important;
            max-height: none !important;
            width: auto !important;
            height: auto !important;
            object-fit: contain !important;
            border-radius: 0 !important;
            background: transparent !important;
            transform-origin: center center;
            will-change: transform;
            pointer-events: none;
            image-orientation: from-image;
        }
        .pg-modal__nav {
            position: absolute !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            z-index: 3 !important;
            width: 52px !important;
            height: 52px !important;
            border: 0 !important;
            border-radius: 999px !important;
            background: rgba(0,0,0,.45) !important;
            color: #fff !important;
            cursor: pointer !important;
            font-size: 34px !important;
            line-height: 1 !important;
            display: grid !important;
            place-items: center !important;
        }
        .pg-modal__nav--prev { right: max(10px, env(safe-area-inset-right, 0px)) !important; }
        .pg-modal__nav--next { left: max(10px, env(safe-area-inset-left, 0px)) !important; }
        .pg-modal.is-single .pg-modal__nav { display: none !important; }
        .pg-modal__counter {
            text-align: center;
            font-size: 13px;
            color: rgba(255,255,255,.85) !important;
            margin: 0 0 8px !important;
            font-variant-numeric: tabular-nums;
        }
        .pg-modal__thumbs {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 0 !important;
            max-width: min(100%, 42rem);
            scrollbar-width: none;
        }
        .pg-modal__thumbs::-webkit-scrollbar { display: none; }
        .pg-modal.is-chrome-off .pg-modal__chrome,
        .pg-modal.is-chrome-off .pg-modal__nav { opacity: 0; pointer-events: none; }
        .pg-modal.is-chrome-off .pg-modal__close { opacity: 1; pointer-events: auto; }
    </style>

    <style>
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
        .doc-upload-preview img,
        .doc-upload-preview__img {
            width: auto;
            height: auto;
            max-width: 5.5rem;
            max-height: 5.5rem;
            object-fit: contain;
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
        .tg-photo {
            display: block;
            margin-top: .4rem;
            padding: 0;
            border: 0;
            background: transparent;
            cursor: zoom-in;
            max-width: 100%;
            text-align: right;
        }
        .tg-photo__img,
        .tg-photo img {
            display: block;
            width: auto;
            height: auto;
            max-width: min(100%, 16.25rem);
            max-height: 17.5rem;
            object-fit: contain;
            object-position: center;
            border-radius: .85rem;
            background: color-mix(in srgb, var(--panel-soft) 88%, #0f172a 6%);
            image-orientation: from-image;
        }
        @media (min-width: 640px) {
            .tg-photo__img,
            .tg-photo img {
                max-width: min(100%, 17.5rem);
                max-height: 19rem;
            }
        }
        .tg-media-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .4rem;
        }
        .tg-media-thumb {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 5.25rem;
            max-height: 8.75rem;
            overflow: hidden;
            border-radius: .75rem;
            border: 1px solid var(--line);
            padding: 0;
            background: #111827;
            cursor: pointer;
        }
        .tg-media-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            image-orientation: from-image;
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
                photos: @json($galleryPayload),
                drawings: @json($drawingGalleryPayload),
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
            var swipeX = 0;
            var swipeY = 0;
            var swipeMoved = false;
            var didSwipe = false;
            var chromeOff = false;
            var keysBound = false;
            var rotation = 0;
            var rotating = false;
            var rotateQueue = [];

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
            var rotateControls = document.getElementById('pg-rotate-controls');
            var viewport = document.getElementById('pg-viewport');
            var modalHome = modal ? modal.parentNode : null;
            var hostDocBound = null;
            var controlsBound = false;
            var dirty = false;
            var deleting = false;
            var unloading = false;
            var api;

            function hostWin() {
                try {
                    if (window.parent && window.parent !== window && window.parent.document && window.parent.document.body) {
                        return window.parent;
                    }
                } catch (err) {}
                try {
                    if (window.top && window.top.document && window.top.document.body) return window.top;
                } catch (err2) {}
                return window;
            }

            function isOpen() {
                return !!(modal && modal.classList.contains('is-open'));
            }

            function onViewportResize() {
                if (isOpen()) applyTransform(false);
            }

            function csrfToken() {
                var input = deleteForm && deleteForm.querySelector('input[name="_token"]');
                if (input && input.value) return input.value;
                var meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.getAttribute('content') : '';
            }

            function lockScroll(on) {
                var docs = [document];
                var host = hostWin();
                if (host.document && host.document !== document) docs.push(host.document);
                docs.forEach(function (doc) {
                    if (!doc || !doc.body) return;
                    doc.documentElement.classList.toggle('pg-viewer-open', on);
                    doc.body.classList.toggle('pg-viewer-open', on);
                    doc.body.style.overflow = on ? 'hidden' : '';
                });
            }

            function onHostPointer(e) {
                if (!isOpen()) return;
                var t = e.target;
                if (!t || !t.closest) return;
                if (t.closest('[data-pg-close], .pg-modal__close')) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (e.stopImmediatePropagation) e.stopImmediatePropagation();
                    close();
                }
            }

            function onHostSubmit(e) {
                var form = e.target;
                if (!form || form.id !== 'pg-delete-form') return;
                onDeleteSubmit(e);
            }

            function bindHostEvents(hostDoc) {
                if (!hostDoc || hostDoc === document) return;
                if (hostDocBound === hostDoc) return;
                unbindHostEvents();
                hostDoc.addEventListener('keydown', onKey, true);
                hostDoc.addEventListener('click', onHostPointer, true);
                hostDoc.addEventListener('submit', onHostSubmit, true);
                hostDocBound = hostDoc;
            }

            function unbindHostEvents() {
                if (!hostDocBound) return;
                try {
                    hostDocBound.removeEventListener('keydown', onKey, true);
                    hostDocBound.removeEventListener('click', onHostPointer, true);
                    hostDocBound.removeEventListener('submit', onHostSubmit, true);
                } catch (err) {}
                hostDocBound = null;
            }

            function publishApi(on) {
                var host = hostWin();
                if (!host || host === window) return;
                try {
                    if (on && api) host.PatientGallery = api;
                    else if (host.PatientGallery === api) host.PatientGallery = undefined;
                } catch (err) {}
            }

            function mountOnHost() {
                var host = hostWin();
                var hostDoc = host.document;
                var style = document.getElementById('pg-viewer-css');
                if (style && hostDoc !== document && !hostDoc.getElementById('pg-viewer-css')) {
                    hostDoc.head.appendChild(style.cloneNode(true));
                }
                if (modal && hostDoc.body && modal.parentNode !== hostDoc.body) {
                    hostDoc.body.appendChild(modal);
                    controlsBound = false;
                }
                bindControls();
                if (!keysBound) {
                    keysBound = true;
                    document.addEventListener('keydown', onKey, true);
                    document.addEventListener('submit', onHostSubmit, true);
                    document.addEventListener('click', onHostPointer, true);
                    window.addEventListener('resize', onViewportResize);
                }
                bindHostEvents(hostDoc);
                publishApi(true);
            }

            function unmountFromHost() {
                publishApi(false);
                unbindHostEvents();
                if (!modal) return;
                try {
                    if (modalHome && modalHome.isConnected) {
                        modalHome.appendChild(modal);
                    } else if (modal.parentNode) {
                        modal.parentNode.removeChild(modal);
                    }
                } catch (err) {
                    try { if (modal.parentNode) modal.parentNode.removeChild(modal); } catch (err2) {}
                }
            }

            function applyTransform(animate) {
                if (!img) return;
                var vw = (viewport && viewport.clientWidth) || window.innerWidth || 1;
                var vh = (viewport && viewport.clientHeight) || window.innerHeight || 1;
                var nw = img.naturalWidth || img.width || 1;
                var nh = img.naturalHeight || img.height || 1;
                var sideways = (rotation % 180) !== 0;
                var boxW = sideways ? nh : nw;
                var boxH = sideways ? nw : nh;
                var fit = Math.min(vw / boxW, vh / boxH);
                if (!isFinite(fit) || fit <= 0) fit = 1;
                img.style.maxWidth = 'none';
                img.style.maxHeight = 'none';
                img.style.width = nw + 'px';
                img.style.height = nh + 'px';
                img.style.transition = dragging ? 'none' : (animate ? 'transform 0.22s ease' : 'transform 0.05s linear');
                img.style.transform = 'translate(' + tx + 'px,' + ty + 'px) rotate(' + rotation + 'deg) scale(' + (fit * scale) + ')';
                img.classList.remove('is-sideways');
                if (zoomLabel) zoomLabel.textContent = Math.round(scale * 100) + '٪';
                if (viewport) viewport.style.cursor = scale > 1.01 ? (dragging ? 'grabbing' : 'grab') : 'zoom-in';
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

            function srcBase(value) {
                return String(value || '').split('?')[0];
            }

            function replaceSrcEverywhere(oldSrc, newSrc, skipEl) {
                var base = srcBase(oldSrc);
                if (!base || !newSrc) return;
                var docs = [document];
                try {
                    var host = hostWin();
                    if (host.document && host.document !== document) docs.push(host.document);
                } catch (err) {}
                docs.forEach(function (doc) {
                    if (!doc) return;
                    doc.querySelectorAll('img').forEach(function (el) {
                        if (skipEl && el === skipEl) return;
                        var current = el.getAttribute('src') || el.src || '';
                        if (srcBase(current) === base) el.src = newSrc;
                    });
                });
            }

            function paintThumbs() {
                if (!thumbs) return;
                thumbs.innerHTML = '';
                items.forEach(function (it, n) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.style.cssText = 'width:56px;height:56px;padding:0;border-radius:12px;overflow:hidden;cursor:pointer;border:2px solid ' + (n === index ? '#fff' : 'transparent') + ';background:transparent;flex:0 0 auto;';
                    b.innerHTML = '<img src="' + it.src + '" alt="" style="width:100%;height:100%;object-fit:cover;background:#111;">';
                    b.onclick = function () { index = n; render(); };
                    thumbs.appendChild(b);
                });
            }

            function rotate(dir) {
                var item = currentItem();
                if (!item || !item.rotateUrl) return;
                var step = dir === 'ccw' ? -90 : 90;
                rotation = (rotation + step + 360) % 360;
                applyTransform(true);
                rotateQueue.push(dir === 'ccw' ? 'ccw' : 'cw');
                flushRotateQueue();
            }

            function flushRotateQueue() {
                if (rotating || !rotateQueue.length) return;
                var item = currentItem();
                if (!item || !item.rotateUrl) {
                    rotateQueue = [];
                    return;
                }
                rotating = true;
                var dir = rotateQueue.shift();
                var step = dir === 'ccw' ? -90 : 90;
                var fd = new FormData();
                fd.append('_token', csrfToken());
                fd.append('direction', dir);
                fetch(item.rotateUrl, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                }).then(function (res) {
                    return res.json().catch(function () { return {}; }).then(function (data) {
                        return { ok: res.ok, data: data };
                    });
                }).then(function (pack) {
                    if (!pack.ok || !pack.data || !pack.data.src) throw new Error('fail');
                    var oldSrc = item.src;
                    item.src = pack.data.src;
                    dirty = true;
                    replaceSrcEverywhere(oldSrc, pack.data.src, img);
                    paintThumbs();
                }).catch(function () {
                    rotateQueue = [];
                    rotation = (rotation - step + 360) % 360;
                    applyTransform(true);
                    alert('چرخش انجام نشد. دوباره تلاش کنید.');
                }).finally(function () {
                    rotating = false;
                    flushRotateQueue();
                });
            }

            function currentItem() {
                return items[index] || null;
            }

            function applyChrome() {
                if (!modal) return;
                modal.classList.toggle('is-chrome-off', chromeOff);
                modal.classList.toggle('is-single', items.length < 2);
            }

            function render() {
                if (!items.length) return;
                var item = currentItem();
                rotateQueue = [];
                rotation = 0;
                resetZoom();
                if (img) {
                    img.onload = function () { applyTransform(false); };
                    img.src = item.src;
                    img.classList.remove('is-sideways');
                    if (img.complete && img.naturalWidth) applyTransform(false);
                }
                title.textContent = item.title || '';
                date.textContent = item.date || '';
                counter.textContent = (index + 1) + ' از ' + items.length;
                if (heading) heading.textContent = mode === 'drawings' ? 'وایت‌بردها' : 'گالری تصاویر';
                if (deleteForm) {
                    deleteForm.action = item.deleteUrl || '#';
                    deleteForm.style.display = item.deleteUrl ? '' : 'none';
                }
                if (rotateControls) {
                    rotateControls.style.display = item.rotateUrl ? '' : 'none';
                }
                if (editBtn) {
                    var canEdit = mode === 'drawings' && item.editVisitId;
                    editBtn.style.display = canEdit ? '' : 'none';
                }
                applyChrome();
                paintThumbs();
            }

            function openWith(collectionName, i) {
                mode = collectionName;
                items = collections[collectionName] || [];
                if (!items.length) {
                    alert(collectionName === 'drawings' ? 'وایت‌بردی وجود ندارد' : 'تصویری وجود ندارد');
                    return;
                }
                index = Math.min(Math.max(Number(i) || 0, 0), items.length - 1);
                chromeOff = false;
                mountOnHost();
                render();
                modal.hidden = false;
                modal.classList.add('is-open');
                lockScroll(true);
                if (window.OverlayHistory) {
                    window.OverlayHistory.push('patient-gallery', function () { close(true); });
                }
                var closeBtn = modal.querySelector('.pg-modal__close');
                if (closeBtn && closeBtn.focus) {
                    try { closeBtn.focus(); } catch (err) {}
                }
            }

            function open(i) { openWith('photos', i); }
            function openDrawing(i) { openWith('drawings', i); }

            function close(fromHistory) {
                var wasOpen = isOpen();
                if (modal) {
                    modal.classList.remove('is-open');
                    modal.hidden = true;
                }
                if (img) img.src = '';
                rotateQueue = [];
                rotation = 0;
                resetZoom();
                chromeOff = false;
                applyChrome();
                lockScroll(false);
                unmountFromHost();
                if (wasOpen && !fromHistory && window.OverlayHistory) {
                    window.OverlayHistory.dismiss('patient-gallery');
                }
                if (wasOpen && dirty && !unloading) {
                    dirty = false;
                    try { window.location.reload(); } catch (err) {}
                }
            }

            function bindControls() {
                if (!modal || controlsBound) return;
                controlsBound = true;
                var closeBtn = modal.querySelector('[data-pg-close], .pg-modal__close');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (e.stopImmediatePropagation) e.stopImmediatePropagation();
                        close();
                    });
                }
            }

            function onDeleteSubmit(e) {
                e.preventDefault();
                e.stopPropagation();
                if (deleting) return false;
                var item = currentItem();
                if (!item || !item.deleteUrl) return false;
                if (!window.confirm('این مورد حذف شود؟')) return false;
                deleting = true;
                var fd = new FormData(deleteForm);
                fd.set('_method', 'DELETE');
                fetch(item.deleteUrl, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                }).then(function (res) {
                    if (!res.ok) throw new Error('delete failed');
                    items.splice(index, 1);
                    collections[mode] = items;
                    dirty = true;
                    if (!items.length) {
                        close();
                        return;
                    }
                    if (index >= items.length) index = items.length - 1;
                    render();
                }).catch(function () {
                    alert('حذف انجام نشد. دوباره تلاش کنید.');
                }).finally(function () {
                    deleting = false;
                });
                return false;
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

            function onKey(e) {
                if (!isOpen()) return;
                if (e.key === 'Escape') {
                    e.preventDefault();
                    e.stopPropagation();
                    if (e.stopImmediatePropagation) e.stopImmediatePropagation();
                    close();
                    return;
                }
                if (e.key === 'ArrowLeft') next();
                if (e.key === 'ArrowRight') prev();
                if (e.key === '+' || e.key === '=') zoomIn();
                if (e.key === '-') zoomOut();
                if (e.key === '0') resetZoom();
                if (e.key === 'r' || e.key === 'R' || e.key === ']') rotate('cw');
                if (e.key === '[') rotate('ccw');
            }

            if (viewport) {
                viewport.addEventListener('wheel', function (e) {
                    if (!isOpen()) return;
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
                        swipeMoved = false;
                    } else if (e.touches.length === 1) {
                        var now = Date.now();
                        if (now - lastTap < 280) {
                            if (scale > 1.05) resetZoom();
                            else setZoom(2.2);
                            lastTap = 0;
                            didSwipe = true;
                        } else {
                            lastTap = now;
                        }
                        lastX = e.touches[0].clientX;
                        lastY = e.touches[0].clientY;
                        swipeX = lastX;
                        swipeY = lastY;
                        swipeMoved = false;
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
                    } else if (e.touches.length === 1 && scale <= 1.01) {
                        lastX = e.touches[0].clientX;
                        lastY = e.touches[0].clientY;
                        var mx = lastX - swipeX;
                        var my = lastY - swipeY;
                        if (Math.hypot(mx, my) > 14) swipeMoved = true;
                    }
                }, { passive: false });

                viewport.addEventListener('touchend', function (e) {
                    if (e.touches.length < 2) pinchStartDist = 0;
                    if (e.touches.length === 0) {
                        var endX = (e.changedTouches[0] && e.changedTouches[0].clientX) || lastX;
                        var endY = (e.changedTouches[0] && e.changedTouches[0].clientY) || lastY;
                        if (scale <= 1.01 && swipeMoved) {
                            var dx = endX - swipeX;
                            var dy = endY - swipeY;
                            if (Math.abs(dy) > 90 && Math.abs(dy) > Math.abs(dx) * 1.15 && dy > 0) {
                                didSwipe = true;
                                close();
                            } else if (items.length > 1 && Math.abs(dx) > 70 && Math.abs(dx) > Math.abs(dy)) {
                                didSwipe = true;
                                if (dx > 0) prev();
                                else next();
                            }
                        }
                        dragging = false;
                        swipeMoved = false;
                    }
                });

                viewport.addEventListener('click', function (e) {
                    if (!isOpen() || scale > 1.05) return;
                    if (e.detail > 1) return;
                    if (didSwipe) { didSwipe = false; return; }
                    chromeOff = !chromeOff;
                    applyChrome();
                });
            }

            api = {
                open: open,
                openDrawing: openDrawing,
                close: close,
                prev: prev,
                next: next,
                zoomIn: zoomIn,
                zoomOut: zoomOut,
                resetZoom: resetZoom,
                rotate: rotate,
                editCurrent: editCurrent,
                isOpen: isOpen,
            };

            window.addEventListener('pagehide', function () {
                unloading = true;
                close();
            });

            return api;
        })();
    </script>

    @if($isStaff)
    <script>
        function docUploadForm() {
            return {
                previewUrl: null,
                previews: [],
                fileName: '',
                sourceLabel: '',
                filesCount: 0,
                fileError: '',
                submitting: false,
                pick(event, source) {
                    const picked = Array.from(event.target.files || []);
                    event.target.value = '';
                    this.addFiles(picked, source);
                },
                addFiles(picked, source) {
                    picked = Array.from(picked || []);
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

                    this.refreshPreviews();
                    const file = this.$refs.files.files[0];
                    this.fileName = file.name || (source === 'camera' ? 'عکس دوربین' : (source === 'paste' ? 'عکس چسبانده‌شده' : 'تصویر'));
                    this.sourceLabel = source === 'camera'
                        ? 'گرفته‌شده با دوربین'
                        : (source === 'paste' ? 'چسبانده‌شده از کلیپ‌بورد — نوع را انتخاب کنید و تأیید کنید' : 'انتخاب از گالری');
                },
                refreshPreviews() {
                    this.previews.forEach(function (item) { URL.revokeObjectURL(item.url); });
                    this.previews = [];
                    const list = this.$refs.files && this.$refs.files.files ? Array.from(this.$refs.files.files) : [];
                    this.previews = list.slice(0, 4).map(function (file) {
                        return { url: URL.createObjectURL(file), name: file.name };
                    });
                    this.previewUrl = this.previews[0] ? this.previews[0].url : null;
                },
                clearFile() {
                    this.previews.forEach(function (item) { URL.revokeObjectURL(item.url); });
                    this.previews = [];
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
                        this.fileError = 'لطفاً با دوربین عکس بگیرید، از گالری انتخاب کنید یا عکس را بچسبانید.';
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
            const patientId = @json($patient->id);
            const storeUrl = @json(route('visits.store-voice'));
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
    @endif

    @if($isStaff)
    <x-row-toolbox-modal />
    <x-answer-panel :mobile="$patient->mobile" :patient-name="$patient->name" />
    @endif
    @if(request('open') === 'consent')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('patient-consent-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    </script>
    @endif
</x-app-layout>
