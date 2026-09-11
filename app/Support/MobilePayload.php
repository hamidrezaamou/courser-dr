<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\SurgeryAppointment;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Collection;

class MobilePayload
{
    /**
     * @return array<string, mixed>
     */
    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'national_code' => $user->national_code,
            'mobile' => $user->mobile,
            'role' => $user->role,
            'role_label' => match ($user->role) {
                User::ROLE_ADMIN => 'مدیر',
                User::ROLE_DOCTOR => 'پزشک',
                User::ROLE_ASSISTANT => 'منشی',
                User::ROLE_PATIENT => 'بیمار',
                default => $user->role,
            },
            'photo_url' => $user->photoUrl(),
            'initial' => $user->photoInitial(),
            'is_staff' => $user->isStaff(),
            'can_manage_clinical' => $user->canManageClinical(),
            'can_manage_appointments' => $user->canManageAppointments(),
            'can_edit_patient' => $user->canEditPatient(),
            'can_view_reports' => $user->canViewReports(),
            'can_manage_settings' => $user->canManageSettings(),
            'can_access_clinic_settings' => $user->canAccessClinicSettings(),
            'can_access_modules' => $user->canAccessModules(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function clinic(): array
    {
        return [
            'name' => (string) config('app.name', 'آرشیو بیمار'),
            'doctor_name' => ClinicBrand::doctorName(),
            'phone' => ClinicBrand::phone(),
            'logo_url' => ClinicBrand::logoUrl(),
            'clinic_label' => (string) SiteSettings::effective(
                'services.website_api.clinic_label',
                config('services.website_api.clinic_label', 'مطب')
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function patientCard(Patient $patient): array
    {
        return [
            'id' => $patient->id,
            'name' => $patient->name,
            'national_code' => $patient->national_code,
            'mobile' => $patient->mobile,
            'mobile_secondary' => $patient->mobile_secondary,
            'age' => $patient->age,
            'photo_url' => $patient->photoUrl(),
            'initial' => $patient->photoInitial(),
            'upcoming_visits_count' => (int) ($patient->upcoming_visits_count ?? 0),
            'upcoming_surgeries_count' => (int) ($patient->upcoming_surgeries_count ?? 0),
            'surgeries_count' => (int) ($patient->surgeries_count ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function appointment(Appointment $item, bool $includePrivateNotes = true): array
    {
        $date = optional($item->scheduled_date)?->toDateString();
        $mobileSecondary = $item->mobile_secondary ?? $item->patient?->mobile_secondary;

        return [
            'id' => $item->id,
            'kind' => 'visit',
            'patient_id' => $item->patient_id,
            'patient_name' => $item->patient_name,
            'national_code' => $item->national_code,
            'mobile' => $item->mobile,
            'mobile_secondary' => $mobileSecondary ?: null,
            'age' => $item->age,
            'title' => $item->visit_type ?: 'ویزیت',
            'visit_type' => $item->visit_type,
            'reason' => $item->reason,
            'subtitle' => $item->reason,
            'scheduled_date' => $date,
            'scheduled_date_jalali' => $date ? Jalali::format($date, 'Y/m/d') : '',
            'weekday' => $date ? Jalali::weekdayName($date) : '',
            'scheduled_time' => (string) $item->scheduled_time,
            'scheduled_time_label' => SlotLabel::display((string) $item->scheduled_time),
            'status' => $item->status,
            'status_label' => BookingStatus::label((string) $item->status),
            'notes' => $includePrivateNotes ? $item->notes : null,
            'sms_body' => AppointmentSms::forItem($item, 'visit'),
            'source' => $item->source ?? 'manual',
            'locked' => ($item->source ?? 'manual') === 'his',
            'actions' => BookingStatus::toolboxActionsFor((string) $item->status),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function surgery(SurgeryAppointment $item, bool $includePrivateNotes = true): array
    {
        $date = optional($item->scheduled_date)?->toDateString();
        $mobileSecondary = $item->mobile_secondary ?? $item->patient?->mobile_secondary;
        $subtypeName = trim((string) ($item->surgerySubtype?->name ?? ''));

        return [
            'id' => $item->id,
            'kind' => 'surgery',
            'patient_id' => $item->patient_id,
            'patient_name' => $item->patient_name,
            'national_code' => $item->national_code,
            'mobile' => $item->mobile,
            'mobile_secondary' => $mobileSecondary ?: null,
            'age' => $item->age,
            'title' => $item->surgery_type ?: 'عمل',
            'subtitle' => $item->hospital?->name,
            'hospital_id' => $item->hospital_id,
            'hospital_name' => $item->hospital?->name,
            'surgery_type_id' => $item->surgery_type_id,
            'surgery_subtype_id' => $item->surgery_subtype_id,
            'surgery_subtype_name' => $subtypeName !== '' ? $subtypeName : null,
            'eye_side' => $item->eye_side,
            'eye_side_label' => EyeSide::label($item->eye_side),
            'surgeon_name' => $item->surgeon_name,
            'scheduled_date' => $date,
            'scheduled_date_jalali' => $date ? Jalali::format($date, 'Y/m/d') : '',
            'weekday' => $date ? Jalali::weekdayName($date) : '',
            'scheduled_time' => (string) $item->scheduled_time,
            'scheduled_time_label' => SlotLabel::display((string) $item->scheduled_time),
            'status' => $item->status,
            'status_label' => BookingStatus::label((string) $item->status),
            'notes' => $includePrivateNotes ? $item->notes : null,
            'sms_body' => AppointmentSms::forItem($item, 'surgery'),
            'is_emergency' => (bool) $item->is_emergency,
            'is_exception' => (bool) $item->is_exception,
            'source' => $item->source ?? 'manual',
            'locked' => ($item->source ?? 'manual') === 'his',
            'actions' => BookingStatus::toolboxActionsFor((string) $item->status),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function visit(Visit $visit, bool $clinical): array
    {
        $payload = [
            'id' => $visit->id,
            'kind' => 'exam',
            'created_at' => optional($visit->created_at)?->toIso8601String(),
            'created_at_jalali' => $visit->created_at ? Jalali::format($visit->created_at, 'Y/m/d H:i') : '',
            'has_voice' => filled($visit->voice_path),
            'has_drawing' => filled($visit->drawing_path),
            'voice_url' => filled($visit->voice_path) ? asset('storage/'.$visit->voice_path) : null,
            'drawing_url' => filled($visit->drawing_path) ? asset('storage/'.$visit->drawing_path) : null,
            'creator_name' => $visit->creator?->name,
        ];

        if ($clinical) {
            $payload['history'] = $visit->history;
            $payload['examination'] = $visit->examination;
            $payload['diagnosis'] = $visit->diagnosis;
            $payload['treatment'] = $visit->treatment;
            $payload['next_instruction'] = $visit->next_instruction;
            $payload['eye_side'] = $visit->eye_side;
            $payload['eye_side_label'] = EyeSide::label($visit->eye_side);
            $payload['va_right'] = $visit->va_right;
            $payload['va_left'] = $visit->va_left;
            $payload['iop_right'] = $visit->iop_right;
            $payload['iop_left'] = $visit->iop_left;
        } else {
            $payload['summary'] = $visit->hasExamContent() ? 'معاینه ثبت شده' : 'ثبت بالینی';
        }

        return $payload;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function timeline(Patient $patient, User $viewer): array
    {
        $clinical = $viewer->canManageClinical() || $viewer->isStaff();
        $items = collect();

        foreach ($patient->visits as $visit) {
            $items->push([
                'id' => 'visit-'.$visit->id,
                'type' => 'visit',
                'sort' => optional($visit->created_at)?->timestamp ?? 0,
                'payload' => self::visit($visit, $clinical),
            ]);
        }

        foreach ($patient->appointments as $appointment) {
            $items->push([
                'id' => 'appointment-'.$appointment->id,
                'type' => 'appointment',
                'sort' => optional($appointment->scheduled_date)?->timestamp ?? 0,
                'payload' => self::appointment($appointment, $clinical),
            ]);
        }

        foreach ($patient->surgeryAppointments as $surgery) {
            $items->push([
                'id' => 'surgery-'.$surgery->id,
                'type' => 'surgery',
                'sort' => optional($surgery->scheduled_date)?->timestamp ?? 0,
                'payload' => self::surgery($surgery, $clinical),
            ]);
        }

        if ($clinical) {
            foreach ($patient->internalNotes as $note) {
                $items->push([
                    'id' => 'note-'.$note->id,
                    'type' => 'note',
                    'sort' => optional($note->created_at)?->timestamp ?? 0,
                    'payload' => [
                        'id' => $note->id,
                        'note' => $note->note,
                        'creator_name' => $note->user?->name,
                        'created_at_jalali' => $note->created_at ? Jalali::format($note->created_at, 'Y/m/d H:i') : '',
                    ],
                ]);
            }
        }

        foreach ($patient->medicalDocuments as $doc) {
            $items->push([
                'id' => 'doc-'.$doc->id,
                'type' => 'document',
                'sort' => optional($doc->created_at)?->timestamp ?? 0,
                'payload' => [
                    'id' => $doc->id,
                    'type' => $doc->type,
                    'description' => $doc->description,
                    'url' => filled($doc->file_path) ? asset('storage/'.$doc->file_path) : null,
                    'created_at_jalali' => $doc->created_at ? Jalali::format($doc->created_at, 'Y/m/d H:i') : '',
                ],
            ]);
        }

        foreach ($patient->prescriptions as $rx) {
            $items->push([
                'id' => 'rx-'.$rx->id,
                'type' => 'prescription',
                'sort' => optional($rx->created_at)?->timestamp ?? 0,
                'payload' => [
                    'id' => $rx->id,
                    'created_at_jalali' => $rx->created_at ? Jalali::format($rx->created_at, 'Y/m/d H:i') : '',
                    'items' => $clinical
                        ? $rx->items->map(fn ($item) => [
                            'drug' => $item->drug?->name ?: $item->drug_name,
                            'dosage' => $item->dosage,
                            'frequency' => $item->frequency,
                            'instructions' => $item->instructions,
                        ])->values()->all()
                        : [],
                    'summary' => 'نسخه دارو',
                ],
            ]);
        }

        return $items
            ->sortByDesc('sort')
            ->values()
            ->take(80)
            ->all();
    }

    /**
     * @param  Collection<int, mixed>  $errors
     */
    public static function firstErrorMessage($errors): string
    {
        $flat = collect($errors)->flatten();

        return (string) ($flat->first() ?: 'اطلاعات واردشده معتبر نیست.');
    }
}
