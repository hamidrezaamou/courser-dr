<?php

namespace App\Http\Middleware;

use App\Support\FeatureFlags;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        abort_unless(FeatureFlags::enabled($feature), 404);

        return $next($request);
    }
}
