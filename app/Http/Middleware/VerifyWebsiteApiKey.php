<?php

namespace App\Http\Middleware;

use App\Support\SiteSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebsiteApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) SiteSettings::effective('services.website_api.key', '');

        if ($configured === '') {
            return response()->json([
                'message' => 'Unauthorized',
                'reason' => 'WEBSITE_API_KEY is not configured. Set it in Admin → Communications → Website booking API.',
            ], 401);
        }

        $provided = (string) (
            $request->bearerToken()
            ?: $request->header('X-Api-Key')
            ?: $request->query('api_key')
        );

        if ($provided === '' || ! hash_equals($configured, $provided)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
