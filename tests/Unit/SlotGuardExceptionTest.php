<?php

namespace Tests\Unit;

use App\Models\Hospital;
use App\Models\Patient;
use App\Models\SurgeryAppointment;
use App\Support\BookingStatus;
use App\Support\SlotGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SlotGuardExceptionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function non_exception_rejects_occupied_surgery_slot(): void
    {
        [$hospital] = $this->seedHoldingSurgery('10:00');

        $this->expectException(ValidationException::class);
        SlotGuard::assertSurgerySlotFree('2026-08-22', '10:00', (int) $hospital->id, null, false);
    }

    #[Test]
    public function exception_true_allows_overlapping_surgery_slot(): void
    {
        [$hospital] = $this->seedHoldingSurgery('11:00');

        SlotGuard::assertSurgerySlotFree('2026-08-22', '11:00', (int) $hospital->id, null, true);

        $this->assertTrue(true);
    }

    /**
     * @return array{0: Hospital, 1: SurgeryAppointment}
     */
    private function seedHoldingSurgery(string $time): array
    {
        $patient = Patient::query()->create([
            'name' => 'بیمار تست',
            'national_code' => '0012345678',
            'mobile' => '09120000001',
        ]);

        $hospital = Hospital::query()->create([
            'name' => 'بیمارستان تست',
        ]);

        $surgery = SurgeryAppointment::query()->create([
            'patient_id' => $patient->id,
            'hospital_id' => $hospital->id,
            'patient_name' => $patient->name,
            'national_code' => $patient->national_code,
            'mobile' => $patient->mobile,
            'surgery_type' => 'کاتاراکت',
            'scheduled_date' => '2026-08-22',
            'scheduled_time' => $time,
            'status' => BookingStatus::CONFIRMED,
            'is_exception' => false,
        ]);

        return [$hospital, $surgery];
    }
}
