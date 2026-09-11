<?php

namespace App\Http\Controllers;

use App\Support\ToolboxPrefs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ToolboxPrefsController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->isStaff(), 403);

        $validated = $request->validate([
            'toolbox' => ['required', 'array'],
            'toolbox.style' => ['nullable', 'string', 'in:soft,filled,outline'],
            'toolbox.accent' => ['nullable', 'string', 'max:7'],
            'toolbox.items' => ['nullable', 'array', 'max:40'],
            'toolbox.items.*.id' => ['nullable', 'string', 'max:40'],
            'toolbox.items.*.kind' => ['nullable', 'string', 'max:20'],
            'toolbox.items.*.label' => ['nullable', 'string', 'max:60'],
            'toolbox.items.*.mobile' => ['nullable', 'string', 'max:20'],
            'toolbox.items.*.body' => ['nullable', 'string', 'max:400'],
            'toolbox.items.*.style' => ['nullable', 'string', 'in:soft,filled,outline'],
            'toolbox.items.*.accent' => ['nullable', 'string', 'max:7'],
            'toolbox.items.*.span' => ['nullable', 'string', 'in:full,half'],
            'booking_success' => ['required', 'array'],
            'booking_success.style' => ['nullable', 'string', 'in:soft,filled,outline'],
            'booking_success.accent' => ['nullable', 'string', 'max:7'],
            'booking_success.items' => ['nullable', 'array', 'max:40'],
            'booking_success.items.*.id' => ['nullable', 'string', 'max:40'],
            'booking_success.items.*.kind' => ['nullable', 'string', 'max:20'],
            'booking_success.items.*.label' => ['nullable', 'string', 'max:60'],
            'booking_success.items.*.mobile' => ['nullable', 'string', 'max:20'],
            'booking_success.items.*.body' => ['nullable', 'string', 'max:400'],
            'booking_success.items.*.style' => ['nullable', 'string', 'in:soft,filled,outline'],
            'booking_success.items.*.accent' => ['nullable', 'string', 'max:7'],
            'booking_success.items.*.span' => ['nullable', 'string', 'in:full,half'],
        ]);

        $prefs = ToolboxPrefs::normalize($validated);
        $user->forceFill(['toolbox_prefs' => $prefs])->save();

        return response()->json([
            'message' => 'چیدمان ابزار ذخیره شد.',
            'prefs' => $prefs,
        ]);
    }
}
