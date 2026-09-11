@php
    $item = $row['item'];
    $isSurgery = $row['type'] === 'surgery';
    $toolboxPayload = \App\Support\ToolboxPayload::fromBoardRow($row);
    $toolboxB64 = \App\Support\ToolboxPayload::encode($toolboxPayload);
    $time = $item->scheduled_time ? \App\Support\SlotLabel::display((string) $item->scheduled_time) : '—';
    $turnLabel = preg_match('/^نوبت\s*\d+/u', $time) ? $time : ('نوبت '.($turn ?? 1));
    $statusClass = match ($item->status) {
        'confirmed' => 'is-confirmed',
        'waiting' => 'is-queue',
        'ready' => 'is-ready',
        'in_consult' => 'is-consult',
        'cancelled' => 'is-cancelled',
        'done' => 'is-done',
        'pending_approval' => 'is-pending-approval',
        default => 'is-waiting',
    };
    $statusText = \App\Support\BookingStatus::label($item->status);
    $dateJalaliRow = jalali($item->scheduled_date, 'Y/m/d');
    $cardId = ($isSurgery ? 'surgery' : 'visit').'-'.$item->id;
    if ($isSurgery) {
        $place = $item->hospital?->name ?: '—';
        $kindLine = trim(($item->surgery_type ?: 'عمل').($item->eye_side ? ' · '.$item->eye_side : ''));
        $meta = trim($kindLine.' · '.$place.' · '.$dateJalaliRow.' '.$time);
        $smsBody = \App\Support\AppointmentSms::forItem($item, 'surgery');
        $editUrl = route('surgery-appointments.edit', $item);
        $printUrl = route('surgery-appointments.prints', $item);
        $typeLabel = 'surgery';
        $typeBadge = 'عمل';
    } else {
        $place = 'کلینیک';
        $kindLine = trim(($item->visit_type ?: 'ویزیت').($item->reason ? ' · '.$item->reason : ''));
        $meta = trim($kindLine.' · '.$dateJalaliRow.' '.$time);
        $smsBody = \App\Support\AppointmentSms::forItem($item, 'visit');
        $editUrl = route('appointments.edit', $item);
        $printUrl = null;
        $typeLabel = 'visit';
        $typeBadge = 'ویزیت';
    }
    $digits = preg_replace('/\D+/', '', (string) $item->mobile) ?? '';
    if (str_starts_with($digits, '98') && strlen($digits) === 12) {
        $digits = '0'.substr($digits, 2);
    }
    if (str_starts_with($digits, '9') && strlen($digits) === 10) {
        $digits = '0'.$digits;
    }
    $telHref = preg_match('/^09\d{9}$/', $digits) ? 'tel:+98'.substr($digits, 1) : null;
    $typeIds = $isSurgery ? \App\Support\SurgeryChecklist::resolveTypeIds($item) : ['type_id' => null, 'subtype_id' => null];
    $hasChecklist = $isSurgery && \App\Support\SurgeryChecklist::isAvailable();
    $billingEnabled = \App\Support\FeatureFlags::enabled('features.billing_insurance');
    $billingRecord = $billingEnabled ? \App\Support\ModuleFinance::billingFor($item) : null;
    $consentEnabled = \App\Support\FeatureFlags::enabled('features.consent_forms');
    $isPendingApproval = ! $isSurgery && $item->status === \App\Support\BookingStatus::PENDING_APPROVAL;
@endphp
<article class="board-card {{ $statusClass }}" :class="{ 'is-open': isBoardCardOpen(@js($cardId)) }">
    <div class="board-card__turn" title="{{ $time }}">
        <span>{{ $turnLabel }}</span>
    </div>

    <div class="board-card__shell">
        <div class="board-card__bar" role="button" tabindex="0"
             @click="toggleBoardCard(@js($cardId))"
             @keydown.enter.prevent="toggleBoardCard(@js($cardId))"
             @keydown.space.prevent="toggleBoardCard(@js($cardId))"
             :aria-expanded="isBoardCardOpen(@js($cardId)) ? 'true' : 'false'">
            <div class="board-card__bar-main">
                <span role="button"
                      tabindex="0"
                      class="board-card__name board-card__name--toolbox"
                      data-toolbox-b64="{{ $toolboxB64 }}"
                      @click.stop>{{ $item->patient_name }}</span>
                <p class="board-card__kind">{{ $kindLine }}</p>
            </div>
            <div class="board-card__bar-side">
                <span class="board-card__type">{{ $typeBadge }}</span>
                <span class="board-card__badge">{{ $statusText }}</span>
                <span class="board-card__bar-when" dir="ltr">{{ $dateJalaliRow }} · {{ $time }}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="board-card__chevron" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                </svg>
            </div>
        </div>

        <div class="board-card__drawer" x-show="isBoardCardOpen(@js($cardId))" x-cloak>
            <div class="board-card__body">
                <div class="board-card__identity">
                    <p class="board-card__place">{{ $place }}</p>
                    <p class="board-card__id ltr-data"><span dir="ltr">{{ $item->national_code }}</span></p>
                </div>

                <div class="board-card__mid">
                    <p class="board-card__date" dir="ltr">{{ $dateJalaliRow }}</p>
                    <p class="board-card__slot" dir="ltr">{{ $time }}</p>
                    @if($telHref)
                        <a href="{{ $telHref }}" class="board-card__phone" dir="ltr">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                            </svg>
                            {{ $item->mobile }}
                        </a>
                    @else
                        <span class="board-card__phone is-muted" dir="ltr">{{ $item->mobile ?: '—' }}</span>
                    @endif
                </div>

                <div class="board-card__actions">
                    @if($isPendingApproval && \App\Support\FeatureFlags::enabled('features.online_booking_approval'))
                        <div class="flex flex-wrap gap-1 mb-2 w-full">
                            <form method="POST" action="{{ route('modules.approval.approve', $item) }}">
                                @csrf
                                <button type="submit" class="btn-primary !px-2 !py-1 !text-[10px]">تأیید آنلاین</button>
                            </form>
                            <form method="POST" action="{{ route('modules.approval.reject', $item) }}">
                                @csrf
                                <button type="submit" class="btn-secondary !px-2 !py-1 !text-[10px]">رد</button>
                            </form>
                        </div>
                    @endif
                    @if($billingEnabled)
                        <div class="mb-2 w-full text-[10px]" style="color: var(--muted);">
                            @if($billingRecord)
                                صورتحساب: {{ $billingRecord->tariff?->name ?? '—' }} ·
                                {{ match($billingRecord->settlement_status) { 'paid' => 'تسویه', 'partial' => 'جزئی', default => 'باز' } }}
                            @else
                                صورتحساب ثبت نشده
                            @endif
                            <form method="POST" action="{{ route('modules.billing.from-billable') }}" class="mt-1">
                                @csrf
                                <input type="hidden" name="billable_type" value="{{ $isSurgery ? 'surgery' : 'visit' }}">
                                <input type="hidden" name="billable_id" value="{{ $item->id }}">
                                <button type="submit" class="btn-secondary !px-2 !py-1 !text-[10px]">ثبت/به‌روز صورتحساب</button>
                            </form>
                        </div>
                    @endif
                    <x-booking-status-actions
                        :model="$item"
                        :type="$typeLabel"
                        variant="board"
                        :show-status="false"
                        :patient-url="route('patients.show', $item->patient_id)"
                    />
                    <x-row-toolbox
                        :name="$item->patient_name"
                        :mobile="$item->mobile"
                        :national-code="$item->national_code"
                        :meta="$meta"
                        :patient-url="route('patients.show', $item->patient_id)"
                        :edit-url="$editUrl"
                        :print-url="$printUrl"
                        :sms-body="$smsBody"
                        :subject-type="$typeLabel"
                        :subject-id="$item->id"
                        :patient-id="$item->patient_id"
                        :date-label="$dateJalaliRow"
                        :surgery-appointment-id="$isSurgery ? $item->id : null"
                        :surgery-type-id="$typeIds['type_id'] ?? null"
                        :surgery-subtype-id="$typeIds['subtype_id'] ?? null"
                        :has-surgery-checklist="$hasChecklist"
                        :can-change-status="$toolboxPayload['canChangeStatus'] ?? false"
                        :status="$toolboxPayload['status'] ?? null"
                        :status-label="$toolboxPayload['statusLabel'] ?? null"
                        :status-url="$toolboxPayload['statusUrl'] ?? null"
                        :status-actions="$toolboxPayload['statusActions'] ?? []"
                        :answer-booking="$toolboxPayload['answerBooking'] ?? null"
                        trigger-label="ابزار"
                        class="board-toolbox-trigger"
                    />
                    @if($consentEnabled && $isSurgery)
                        <a href="{{ route('patients.show', $item->patient_id) }}?open=consent&surgery={{ $item->id }}" class="btn-secondary mt-2 w-full !py-1.5 !text-[10px] text-center">رضایت عمل</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</article>
