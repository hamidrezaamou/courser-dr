<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        \App\Models\User::ensurePhotoColumn();

        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function updatePhoto(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        \App\Models\User::ensurePhotoColumn();

        $request->validate([
            'photo' => ['required', 'file', 'image', 'max:10240'],
        ], [
            'photo.required' => 'یک عکس انتخاب کنید.',
            'photo.image' => 'فقط فایل تصویری مجاز است.',
            'photo.max' => 'حجم عکس نباید بیشتر از ۱۰ مگابایت باشد.',
        ]);

        \App\Support\ProfilePhoto::store($request->user(), $request->file('photo'), 'user-photos');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'url' => $request->user()->fresh()->photoUrl(),
                'message' => 'عکس پروفایل ذخیره شد.',
            ]);
        }

        return Redirect::route('profile.edit')->with('status', 'photo-updated');
    }

    public function destroyPhoto(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        \App\Models\User::ensurePhotoColumn();
        \App\Support\ProfilePhoto::destroy($request->user());

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'url' => null,
                'message' => 'عکس پروفایل حذف شد.',
            ]);
        }

        return Redirect::route('profile.edit')->with('status', 'photo-deleted');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        \App\Models\User::ensurePhotoColumn();
        \App\Support\ProfilePhoto::destroy($user);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
