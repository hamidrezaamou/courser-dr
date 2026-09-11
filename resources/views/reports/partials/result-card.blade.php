@php
    $item = $row['item'];
    $time = $item->scheduled_time ? \App\Support\SlotLabel::display((string) $item->scheduled_time) : '—';
    $dateJalali = jalali($item->scheduled_date, 'Y/m/d');
    $rowStatus = $item->status;
    $statusLabel = $statusLabels[$rowStatus] ?? \App\Support\BookingStatus::label($rowStatus);
    $noteKey = $row['type'].':'.$item->id;
    $noteThread = $reportNotes->get($noteKey) ?: collect();
    $noteCount = $noteThread->count();
    if ($noteCount === 0 && trim((string) ($item->notes ?? '')) !== '') {
        $noteCount = 1;
    }
    $hasReportNote = $noteCount > 0;
    $weekday = jalali_weekday($item->scheduled_date);
    $mobileSecondary = trim((string) ($item->mobile_secondary ?? $item->patient?->mobile_secondary ?? ''));
    $toolboxPayload = array_merge(
        \App\Support\ToolboxPayload::fromBoardRow($row),
        ['sheetMode' => 'report']
    );
    if ($row['type'] === 'surgery') {
        $typeLabel = trim((string) ($item->surgery_type ?: 'عمل'));
        $subtypeName = trim((string) ($item->surgerySubtype?->name ?? ''));
        $eyeSide = trim((string) ($item->eye_side ?? ''));
        $detailParts = [$typeLabel];
        if ($subtypeName !== '' && mb_stripos($typeLabel, $subtypeName) === false) {
            $detailParts[] = $subtypeName;
        }
        $detailSoFar = implode(' ', $detailParts);
        if ($eyeSide !== '' && mb_stripos($detailSoFar, $eyeSide) === false) {
            $detailParts[] = $eyeSide;
        }
        $detail = implode(' · ', $detailParts);
        $center = $row['hospital'] ?: '—';
        $meta = $detail.' · '.$dateJalali.' '.$time.($row['hospital'] ? ' · '.$row['hospital'] : '');
        $smsBody = \App\Support\AppointmentSms::forItem($item, 'surgery');
        $editUrl = route('surgery-appointments.edit', $item);
        $printUrl = route('surgery-appointments.prints', $item);
    } else {
        $detail = trim(($item->visit_type ?: 'ویزیت').($item->reason ? ' · '.$item->reason : ''));
        $center = 'ویزیت مطب';
        $meta = $detail.' · '.$dateJalali.' '.$time;
        $smsBody = \App\Support\AppointmentSms::forItem($item, 'visit');
        $editUrl = route('appointments.edit', $item);
        $printUrl = null;
    }
    $reportTypeIds = $row['type'] === 'surgery'
        ? \App\Support\SurgeryChecklist::resolveTypeIds($item)
        : ['type_id' => null, 'subtype_id' => null];
    $reportHasChecklist = $row['type'] === 'surgery' && \App\Support\SurgeryChecklist::isAvailable();
    $reportToolboxB64 = \App\Support\ToolboxPayload::encode($toolboxPayload);
    $noteDetail = [
        'subjectType' => $row['type'],
        'subjectId' => $item->id,
        'patientId' => (int) $item->patient_id,
        'patientName' => $item->patient_name,
        'mobile' => $item->mobile,
        'mobileSecondary' => $mobileSecondary,
        'nationalCode' => $item->national_code,
        'meta' => $meta,
        'date' => $dateJalali,
    ];
@endphp
<article class="report-card {{ $row['type'] === 'surgery' ? 'is-surgery' : 'is-visit' }} report-bulk-host"
        data-bulk-host="{{ $row['type'] }}:{{ $item->id }}"
        :class="{ 'is-bulk-selected': isSelected(@js($row['type'].':'.$item->id)) }"
        @click="onBulkHostClick($event, @js($row['type'].':'.$item->id))">
    <header class="report-card__head">
        <div class="report-card__kind">
            <span class="report-bulk-check-wrap report-no-print">
                <button type="button"
                        class="report-bulk-toggle"
                        data-bulk-key="{{ $row['type'] }}:{{ $item->id }}"
                        :class="{ 'is-on': isSelected(@js($row['type'].':'.$item->id)) }"
                        :aria-pressed="isSelected(@js($row['type'].':'.$item->id)) ? 'true' : 'false'"
                        @click.stop="toggleKey(@js($row['type'].':'.$item->id))"
                        aria-label="انتخاب {{ $item->patient_name }}">
                    <span class="report-bulk-toggle__ui" aria-hidden="true"></span>
                </button>
            </span>
            <span class="report-kind {{ $row['type'] === 'surgery' ? 'is-surgery' : 'is-visit' }}">
                {{ $row['type'] === 'surgery' ? 'عمل' : 'ویزیت' }}
            </span>
            @if($row['type'] === 'surgery' && $item->is_emergency)
                <span class="report-emergency-badge">اورژانس</span>
            @endif
        </div>
        <span class="report-status is-{{ $rowStatus }}">{{ $statusLabel }}</span>
    </header>
    <button type="button"
            class="report-card__name report-name-toolbox"
            data-toolbox-b64="{{ $reportToolboxB64 }}">{{ $item->patient_name }}</button>
    <p class="report-card__meta">
        <span class="mono ltr-data">{{ $dateJalali }}</span>
        @if ($weekday !== '')
            <span>{{ $weekday }}</span>
        @endif
        <span class="mono strong-slot ltr-data">{{ $time }}</span>
    </p>
    <p class="report-card__center">
        <strong>{{ $center }}</strong>
        <span>{{ $detail }}</span>
    </p>
    <div class="report-card__row">
        <span class="mono ltr-data">{{ $item->national_code }}</span>
        <div class="report-phones">
            @if ($item->mobile)
                <span dir="ltr">{{ $item->mobile }}</span>
            @endif
            @if ($mobileSecondary !== '')
                <span dir="ltr">{{ $mobileSecondary }}</span>
            @endif
        </div>
    </div>
    <footer class="report-card__foot">
        @if ($hasReportNote)
            <button
                type="button"
                class="report-note-icon"
                data-note-key="{{ $noteKey }}"
                title="مشاهده توضیحات"
                onclick='window.dispatchEvent(new CustomEvent("open-report-note", { detail: {!! json_encode($noteDetail, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!} }))'
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10.5h8M8 14h5m8-2c0 4.556-4.03 8.25-9 8.25a9.76 9.76 0 01-2.51-.326l-4.24 1.326 1.35-3.63A7.98 7.98 0 013 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                </svg>
                <span class="report-note-count">{{ $noteCount }}</span>
            </button>
        @else
            <button
                type="button"
                class="report-card__note-add"
                onclick='window.dispatchEvent(new CustomEvent("open-report-note", { detail: {!! json_encode($noteDetail, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!} }))'
            >یادداشت</button>
        @endif
        <x-row-toolbox
            :name="$item->patient_name"
            :mobile="$item->mobile"
            :mobile-secondary="$mobileSecondary"
            :national-code="$item->national_code"
            :meta="$meta"
            :patient-url="route('patients.show', $item->patient_id)"
            :edit-url="$editUrl"
            :print-url="$printUrl"
            :sms-body="$smsBody"
            sheet-mode="report"
            :subject-type="$row['type']"
            :subject-id="$item->id"
            :patient-id="$item->patient_id"
            :date-label="$dateJalali"
            :surgery-appointment-id="$row['type'] === 'surgery' ? $item->id : null"
            :surgery-type-id="$reportTypeIds['type_id'] ?? null"
            :surgery-subtype-id="$reportTypeIds['subtype_id'] ?? null"
            :has-surgery-checklist="$reportHasChecklist"
            :can-change-status="$toolboxPayload['canChangeStatus'] ?? false"
            :status="$toolboxPayload['status'] ?? null"
            :status-label="$toolboxPayload['statusLabel'] ?? null"
            :status-url="$toolboxPayload['statusUrl'] ?? null"
            :status-actions="$toolboxPayload['statusActions'] ?? []"
            :answer-booking="$toolboxPayload['answerBooking'] ?? null"
        />
    </footer>
</article>
