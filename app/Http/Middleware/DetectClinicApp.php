<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class DetectClinicApp
{
    public function handle(Request $request, Closure $next): Response
    {
        $isApp = str_contains((string) $request->userAgent(), 'PatientArchiveApp');
        View::share('isClinicApp', $isApp);

        return $next($request);
    }
}
