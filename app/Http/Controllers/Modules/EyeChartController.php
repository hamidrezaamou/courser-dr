<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Visit;
use App\Support\ModuleRegistry;
use Illuminate\View\View;

class EyeChartController extends Controller
{
    public function index(): View
    {
        $patientFilter = request()->integer('patient') ?: null;

        $visitsQuery = Visit::query()
            ->with('patient')
            ->where(function ($query) {
                $query->whereNotNull('va_right')
                    ->orWhereNotNull('va_left');
            })
            ->latest();

        if ($patientFilter) {
            $visitsQuery->where('patient_id', $patientFilter);
        }

        $visits = $visitsQuery->limit(40)->get();

        $patients = Patient::query()
            ->whereHas('visits', function ($query) {
                $query->whereNotNull('va_right')->orWhereNotNull('va_left');
            })
            ->withCount('visits')
            ->orderBy('name')
            ->limit(30)
            ->get();

        return view('modules.eye-chart.index', [
            'moduleSection' => ModuleRegistry::definitions()['eye_chart']['section'],
            'visits' => $visits,
            'patients' => $patients,
            'patientFilter' => $patientFilter,
        ]);
    }
}
