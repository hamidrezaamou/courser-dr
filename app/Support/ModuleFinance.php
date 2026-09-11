<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\BillingRecord;
use App\Models\FinancialTransaction;
use App\Models\ServiceTariff;
use App\Models\SurgeryAppointment;
use Illuminate\Database\Eloquent\Model;

class ModuleFinance
{
    public static function syncBillable(Appointment|SurgeryAppointment $billable): void
    {
        if (! FeatureFlags::enabled('features.accounting') && ! FeatureFlags::enabled('features.billing_insurance')) {
            return;
        }

        $kind = $billable instanceof SurgeryAppointment ? 'surgery' : 'visit';
        $tariff = ServiceTariff::query()
            ->where('kind', $kind)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $tariff) {
            return;
        }

        if (FeatureFlags::enabled('features.billing_insurance')) {
            BillingRecord::updateOrCreate(
                [
                    'billable_type' => $billable::class,
                    'billable_id' => $billable->id,
                ],
                [
                    'patient_id' => $billable->patient_id,
                    'service_tariff_id' => $tariff->id,
                    'fee_amount' => $tariff->amount,
                    'insurance_share' => $tariff->insuranceShareAmount(),
                    'patient_share' => $tariff->patientShareAmount(),
                    'settlement_status' => 'open',
                ]
            );
        }

        if (! FeatureFlags::enabled('features.accounting')) {
            return;
        }

        $exists = FinancialTransaction::query()
            ->where('type', 'charge')
            ->where('reference_type', $billable::class)
            ->where('reference_id', $billable->id)
            ->exists();

        if ($exists) {
            return;
        }

        $label = $kind === 'surgery'
            ? 'بدهکاری عمل — '.($billable->surgery_type ?: 'عمل')
            : 'بدهکاری ویزیت — '.($billable->visit_type ?: 'ویزیت');

        FinancialTransaction::create([
            'patient_id' => $billable->patient_id,
            'type' => 'charge',
            'amount' => $tariff->patientShareAmount() ?: $tariff->amount,
            'label' => $label,
            'reference_type' => $billable::class,
            'reference_id' => $billable->id,
            'recorded_by' => auth()->id(),
            'transaction_date' => optional($billable->scheduled_date)->toDateString() ?: now()->toDateString(),
            'notes' => 'ثبت خودکار از نوبت',
        ]);
    }

    public static function billingFor(Model $billable): ?BillingRecord
    {
        if (! FeatureFlags::enabled('features.billing_insurance')) {
            return null;
        }

        return BillingRecord::query()
            ->with('tariff')
            ->where('billable_type', $billable::class)
            ->where('billable_id', $billable->id)
            ->first();
    }
}
