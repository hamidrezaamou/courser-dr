<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\FinancialTransaction;
use App\Models\HisSyncLog;
use App\Models\HisSyncState;
use App\Models\Patient;
use App\Models\Visit;
use App\Support\His\HisResources;
use Illuminate\View\View;

class HisSyncMonitorController extends Controller
{
    /** A resource that has not reported in this long is treated as broken. */
    private const STALE_MINUTES = 30;

    public function index(): View
    {
        $states = HisSyncState::query()
            ->get()
            ->keyBy('resource');

        $rows = [];
        foreach (HisResources::names() as $resource) {
            $state = $states->get($resource);

            $rows[] = [
                'resource' => $resource,
                'label' => HisResources::label($resource),
                'state' => $state,
                'stale' => $state === null || $state->isStale(self::STALE_MINUTES),
                'imported' => (int) ($state->rows_imported ?? 0),
            ];
        }

        return view('admin.his.index', [
            'rows' => $rows,
            'logs' => HisSyncLog::query()->latest('id')->limit(30)->get(),
            'counts' => $this->counts(),
            'enabled' => (bool) config('his.enabled'),
            'keyConfigured' => (string) config('his.key', '') !== '',
            'signed' => (string) config('his.secret', '') !== '',
            'staleMinutes' => self::STALE_MINUTES,
        ]);
    }

    /**
     * @return array<string, array{his: int, manual: int}>
     */
    private function counts(): array
    {
        $models = [
            'patients' => Patient::class,
            'appointments' => Appointment::class,
            'visits' => Visit::class,
            'finance' => FinancialTransaction::class,
        ];

        $counts = [];
        foreach ($models as $key => $model) {
            $counts[$key] = [
                'his' => $model::query()->fromHis()->count(),
                'manual' => $model::query()->enteredHere()->count(),
            ];
        }

        return $counts;
    }
}
