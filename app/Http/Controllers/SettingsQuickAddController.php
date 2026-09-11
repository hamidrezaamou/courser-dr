<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Models\SurgerySubtype;
use App\Models\SurgeryType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsQuickAddController extends Controller
{
    public function storeHospital(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'نام بیمارستان الزامی است.',
        ]);

        $hospital = Hospital::create(['name' => trim($validated['name'])]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'id' => $hospital->id,
                'name' => $hospital->name,
                'message' => 'بیمارستان اضافه شد.',
            ]);
        }

        return back()->with('success', 'بیمارستان اضافه شد.');
    }

    public function storeSurgeryType(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $max = (int) SurgeryType::query()->max('sort_order');
        $type = SurgeryType::create([
            'name' => trim($validated['name']),
            'is_active' => true,
            'sort_order' => $max + 1,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'id' => $type->id,
                'name' => $type->name,
                'subtypes' => [],
                'message' => 'نوع عمل اضافه شد.',
            ]);
        }

        return back()->with('success', 'نوع عمل اضافه شد.');
    }

    public function storeSurgerySubtype(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'surgery_type_id' => ['required', 'integer', 'exists:surgery_types,id'],
            'name' => ['required', 'string', 'max:100'],
        ]);

        $max = (int) SurgerySubtype::query()
            ->where('surgery_type_id', $validated['surgery_type_id'])
            ->max('sort_order');

        $subtype = SurgerySubtype::create([
            'surgery_type_id' => (int) $validated['surgery_type_id'],
            'name' => trim($validated['name']),
            'is_active' => true,
            'sort_order' => $max + 1,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'id' => $subtype->id,
                'name' => $subtype->name,
                'surgery_type_id' => $subtype->surgery_type_id,
                'message' => 'زیرگروه اضافه شد.',
            ]);
        }

        return back()->with('success', 'زیرگروه اضافه شد.');
    }
}
