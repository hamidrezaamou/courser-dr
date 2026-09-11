<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the HIS ingest endpoints.
 *
 * Fails closed on purpose: if no key is configured the route is unusable
 * rather than open. These endpoints carry national codes and phone numbers,
 * so a "convenient" development fallback would be a live hole in production.
 */
class VerifyHisAgentKey
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('his.enabled')) {
            return $this->deny('HIS sync is disabled.');
        }

        $expected = (string) config('his.key', '');
        if ($expected === '') {
            return $this->deny('HIS agent key is not configured.');
        }

        $provided = (string) ($request->bearerToken() ?: $request->header('X-His-Key', ''));
        if ($provided === '' || ! hash_equals($expected, $provided)) {
            return $this->deny('Invalid agent key.');
        }

        $allowed = (array) config('his.allowed_ips', []);
        if ($allowed !== [] && ! in_array($request->ip(), $allowed, true)) {
            return $this->deny('Address not allowed.');
        }

        if (($failure = $this->verifySignature($request)) !== null) {
            return $this->deny($failure);
        }

        return $next($request);
    }

    /**
     * When a shared secret is configured the body must be signed and stamped.
     * The timestamp is what stops a captured request being replayed later.
     */
    private function verifySignature(Request $request): ?string
    {
        $secret = (string) config('his.secret', '');
        if ($secret === '') {
            return null;
        }

        $timestamp = (string) $request->header('X-His-Timestamp', '');
        if ($timestamp === '' || ! ctype_digit($timestamp)) {
            return 'Missing timestamp.';
        }

        $skew = abs(time() - (int) $timestamp);
        if ($skew > max(30, (int) config('his.max_skew_seconds', 300))) {
            return 'Timestamp outside the accepted window.';
        }

        $provided = (string) $request->header('X-His-Signature', '');
        $computed = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        if ($provided === '' || ! hash_equals($computed, $provided)) {
            return 'Invalid signature.';
        }

        return null;
    }

    private function deny(string $reason): Response
    {
        return response()->json(['message' => 'Unauthorized', 'reason' => $reason], 401);
    }
}
