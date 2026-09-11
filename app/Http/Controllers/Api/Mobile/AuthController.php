<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileToken;
use App\Models\User;
use App\Support\Digits;
use App\Support\MobilePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'national_code' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $nationalCode = Digits::only($validated['national_code']);
        $throttleKey = Str::transliterate(Str::lower($nationalCode).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'national_code' => 'تعداد تلاش بیش از حد است. '.$seconds.' ثانیه صبر کنید.',
            ]);
        }

        $ok = Auth::attempt([
            'national_code' => $nationalCode,
            'password' => $validated['password'],
        ]);

        if (! $ok) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'national_code' => 'کد ملی یا رمز عبور نادرست است.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        /** @var User $user */
        $user = Auth::user();
        [, $plain] = MobileToken::issue($user, (string) ($validated['device_name'] ?? 'android'));

        return response()->json([
            'ok' => true,
            'token' => $plain,
            'token_type' => 'Bearer',
            'user' => MobilePayload::user($user),
            'clinic' => MobilePayload::clinic(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'user' => MobilePayload::user($request->user()),
            'clinic' => MobilePayload::clinic(),
        ]);
    }

    public function updateMe(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $validated['name'];
        if (array_key_exists('mobile', $validated) && $validated['mobile'] !== null && $validated['mobile'] !== '') {
            $user->mobile = $validated['mobile'];
        }
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        return response()->json([
            'ok' => true,
            'message' => 'حساب به‌روزرسانی شد.',
            'user' => MobilePayload::user($user->fresh()),
            'clinic' => MobilePayload::clinic(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->attributes->get('mobileToken');
        if ($token instanceof MobileToken) {
            $token->delete();
        }

        return response()->json([
            'ok' => true,
            'message' => 'خروج انجام شد.',
        ]);
    }
}
