<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\FinancialTransaction;
use App\Models\FollowUpReminder;
use App\Models\PatientFollowUp;
use App\Models\InternalNote;
use App\Models\MedicalDocument;
use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\Prescription;
use App\Models\SurgeryAppointment;
use App\Models\Visit;
use App\Models\WaitingListEntry;
use Illuminate\Support\Facades\DB;

class PatientSecureErase
{
    /**
     * Irreversibly anonymize identity and remove clinical artifacts.
     * Appointment shells remain for capacity/stats with redacted names.
     */
    public static function erase(Patient $patient, ?int $actorId = null): void
    {
        $patient->loadMissing(['medicalDocuments', 'visits', 'prescriptions']);

        DB::transaction(function () use ($patient, $actorId) {
            $snapshot = [
                'patient_id' => $patient->id,
                'name' => $patient->name,
                'national_code' => $patient->national_code,
                'mobile' => $patient->mobile,
                'by' => $actorId,
            ];

            foreach ($patient->medicalDocuments as $doc) {
                self::deletePublicFile($doc->file_path);
                $doc->delete();
            }

            self::deletePublicFile($patient->photo_path);

            foreach ($patient->visits as $visit) {
                self::deletePublicFile($visit->drawing_path);
                self::deletePublicFile($visit->voice_path);
                $visit->delete();
            }

            foreach ($patient->prescriptions as $rx) {
                $rx->items()->delete();
                $rx->delete();
            }

            $patient->internalNotes()->delete();
            $patient->followUpReminders()->delete();

            if (class_exists(PatientFollowUp::class) && \Illuminate\Support\Facades\Schema::hasTable('patient_follow_ups')) {
                PatientFollowUp::query()->where('patient_id', $patient->id)->delete();
            }

            if (class_exists(PatientConsent::class)) {
                PatientConsent::query()->where('patient_id', $patient->id)->delete();
            }

            if (class_exists(FinancialTransaction::class)) {
                FinancialTransaction::query()->where('patient_id', $patient->id)->delete();
            }

            if (class_exists(WaitingListEntry::class)) {
                WaitingListEntry::query()->where('patient_id', $patient->id)->update([
                    'patient_id' => null,
                    'patient_name' => 'حذف‌شده',
                    'mobile' => '—',
                    'national_code' => null,
                ]);
            }

            $visitWipe = [
                'patient_name' => 'بیمار حذف‌شده',
                'national_code' => null,
                'mobile' => '—',
                'notes' => null,
                'reason' => null,
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('appointments', 'mobile_secondary')) {
                $visitWipe['mobile_secondary'] = null;
            }
            Appointment::query()->where('patient_id', $patient->id)->update($visitWipe);

            SurgeryAppointment::query()->where('patient_id', $patient->id)->update([
                'patient_name' => 'بیمار حذف‌شده',
                'national_code' => null,
                'mobile' => '—',
                'mobile_secondary' => null,
                'notes' => null,
            ]);

            $token = 'DEL-'.$patient->id.'-'.now()->format('YmdHis');

            $wipe = [
                'name' => 'حذف‌شده',
                'national_code' => $token,
                'mobile' => '00000000000',
                'age' => null,
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn($patient->getTable(), 'photo_path')) {
                $wipe['photo_path'] = null;
            }
            $patient->update($wipe);

            ActivityLogger::log($patient, 'secure_erased', $snapshot, [
                'token' => $token,
                'policy' => 'anonymize_keep_booking_shells',
            ]);
        });
    }

    private static function deletePublicFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        PublicStorage::delete($path);
    }
}
