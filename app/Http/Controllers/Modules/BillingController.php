<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\BillingRecord;
use App\Models\ServiceTariff;
use App\Models\SurgeryAppointment;
use App\Support\ModuleFinance;
use App\Support\ModuleRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(): View
    {
        return view('modules.billing.index', [
            'moduleSection' => ModuleRegistry::definitions()['billing']['section'],
            'tariffs' => ServiceTariff::query()->orderBy('kind')->orderBy('name')->get(),
            'records' => BillingRecord::query()
                ->with(['patient', 'tariff', 'billable'])
                ->latest()
                ->limit(50)
                ->get(),
            'recentVisits' => Appointment::query()
                ->with('patient')
                ->orderByDesc('scheduled_date')
                ->limit(30)
                ->get(['id', 'patient_id', 'patient_name', 'visit_type', 'scheduled_date']),
            'recentSurgeries' => SurgeryAppointment::query()
                ->with('patient')
                ->orderByDesc('scheduled_date')
                ->limit(30)
                ->get(['id', 'patient_id', 'patient_name', 'surgery_type', 'scheduled_date']),
        ]);
    }

    public function storeTariff(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'kind' => ['required', 'in:visit,surgery'],
            'amount' => ['required', 'integer', 'min:0'],
            'insurance_coverage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        ServiceTariff::create([
            'name' => $validated['name'],
            'kind' => $validated['kind'],
            'amount' => (int) $validated['amount'],
            'insurance_coverage' => (int) $validated['insurance_coverage'],
            'is_active' => true,
        ]);

        return back()->with('success', 'تعرفه ذخیره شد.');
    }

    public function storeRecord(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'billable_type' => ['required', 'in:visit,surgery'],
            'billable_id' => ['required', 'integer'],
            'service_tariff_id' => ['required', 'exists:service_tariffs,id'],
            'settlement_status' => ['required', 'in:open,partial,paid'],
        ]);

        $tariff = ServiceTariff::query()->findOrFail($validated['service_tariff_id']);

        if ($validated['billable_type'] === 'visit') {
            $billable = Appointment::query()->findOrFail($validated['billable_id']);
        } else {
            $billable = SurgeryAppointment::query()->findOrFail($validated['billable_id']);
        }

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
                'settlement_status' => $validated['settlement_status'],
            ]
        );

        return back()->with('success', 'صورتحساب ثبت شد.');
    }

    public function storeFromBillable(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'billable_type' => ['required', 'in:visit,surgery'],
            'billable_id' => ['required', 'integer'],
            'settlement_status' => ['nullable', 'in:open,partial,paid'],
        ]);

        if ($validated['billable_type'] === 'visit') {
            $billable = Appointment::query()->findOrFail($validated['billable_id']);
        } else {
            $billable = SurgeryAppointment::query()->findOrFail($validated['billable_id']);
        }

        ModuleFinance::syncBillable($billable);

        if ($request->filled('settlement_status')) {
            BillingRecord::query()
                ->where('billable_type', $billable::class)
                ->where('billable_id', $billable->id)
                ->update(['settlement_status' => $validated['settlement_status']]);
        }

        return back()->with('success', 'صورتحساب این نوبت ثبت/به‌روز شد.');
    }
}
