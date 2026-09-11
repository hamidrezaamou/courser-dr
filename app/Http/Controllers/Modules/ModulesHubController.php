<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Support\ModuleRegistry;
use Illuminate\View\View;

class ModulesHubController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->canAccessModules(), 403);
        abort_unless(ModuleRegistry::anyEnabled(), 404);

        return view('modules.hub', [
            'moduleSection' => 'hub',
            'modules' => ModuleRegistry::enabled(),
        ]);
    }
}
