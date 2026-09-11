<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use App\Support\ModuleRegistry;
use Illuminate\View\View;

class PortalAdminController extends Controller
{
    public function index(): View
    {
        $portalCodes = User::query()
            ->where('role', User::ROLE_PATIENT)
            ->whereNotNull('national_code')
            ->pluck('national_code');

        $patientsWithPortal = Patient::query()
            ->whereIn('national_code', $portalCodes)
            ->count();

        return view('modules.portal.index', [
            'moduleSection' => ModuleRegistry::definitions()['portal']['section'],
            'portalPatients' => $patientsWithPortal,
            'totalPatients' => Patient::query()->count(),
        ]);
    }
}
