<?php

namespace App\Http\Middleware;

use App\Models\MobileToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobile
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = MobileToken::findValid($request->bearerToken());

        if (! $token || ! $token->user) {
            return response()->json([
                'ok' => false,
                'message' => 'وارد نشده‌اید. دوباره وارد شوید.',
            ], 401);
        }

        $token->touchUsage();
        Auth::setUser($token->user);
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('mobileToken', $token);

        return $next($request);
    }
}
