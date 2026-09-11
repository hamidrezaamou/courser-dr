<?php

namespace App\Http\Controllers;

use App\Models\SurgeryChecklistTemplate;
use App\Models\SurgerySubtype;
use App\Models\SurgeryType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SurgeryTypeController extends Controller
{
    public function index(): View
    {
        SurgeryType::ensureCooldownColumn();
        SurgerySubtype::ensureCooldownColumn();
        $types = SurgeryType::query()->ordered()->with(['subtypes' => fn ($q) => $q->ordered()])->get();

        return view('surgery-types.index', [
            'types' => $types,
            'settingsSection' => 'surgery-types',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:surgery_types,name'],
        ], [
            'name.required' => 'نام نوع عمل را وارد کنید.',
            'name.unique' => 'این نوع عمل قبلاً ثبت شده است.',
        ]);

        $maxOrder = (int) SurgeryType::query()->max('sort_order');

        SurgeryType::create([
            'name' => trim($validated['name']),
            'is_active' => true,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()
            ->route('settings.surgery-types')
            ->with('success', 'نوع عمل اضافه شد.');
    }

    public function update(Request $request, SurgeryType $surgeryType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:surgery_types,name,'.$surgeryType->id],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $surgeryType->update([
            'name' => trim($validated['name']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('settings.surgery-types')
            ->with('success', 'نوع عمل به‌روزرسانی شد.');
    }

    public function updateCooldown(Request $request, SurgeryType $surgeryType): RedirectResponse
    {
        SurgeryType::ensureCooldownColumn();

        $validated = $request->validate([
            'cooldown_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ]);

        $days = $validated['cooldown_days'] ?? null;
        $surgeryType->update([
            'cooldown_days' => ($days === null || $days === '' || (int) $days === 0) ? null : (int) $days,
        ]);

        return redirect()
            ->route('settings.surgery-types')
            ->with('success', $surgeryType->cooldown_days
                ? 'محدودیت کل نوع «'.$surgeryType->name.'» برای همه زیرگروه‌ها ذخیره شد.'
                : 'محدودیت کل نوع «'.$surgeryType->name.'» برداشته شد.');
    }

    public function destroy(SurgeryType $surgeryType): RedirectResponse
    {
        $surgeryType->delete();

        return redirect()
            ->route('settings.surgery-types')
            ->with('success', 'نوع عمل حذف شد.');
    }

    public function storeSubtype(Request $request, SurgeryType $surgeryType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $max = (int) $surgeryType->subtypes()->max('sort_order');
        $surgeryType->subtypes()->create([
            'name' => trim($validated['name']),
            'is_active' => true,
            'sort_order' => $max + 1,
        ]);

        return redirect()
            ->route('settings.surgery-types')
            ->with('success', 'زیرگروه «'.$validated['name'].'» اضافه شد.');
    }

    public function updateSubtype(Request $request, SurgerySubtype $surgerySubtype): RedirectResponse
    {
        SurgerySubtype::ensureCooldownColumn();

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'cooldown_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ]);

        $payload = [];
        if (array_key_exists('name', $validated) && trim((string) ($validated['name'] ?? '')) !== '') {
            $payload['name'] = trim((string) $validated['name']);
        }
        if (array_key_exists('cooldown_days', $validated)) {
            $days = $validated['cooldown_days'];
            $payload['cooldown_days'] = ($days === null || $days === '' || (int) $days === 0) ? null : (int) $days;
        }
        if ($payload !== []) {
            $surgerySubtype->update($payload);
        }

        return redirect()
            ->route('settings.surgery-types')
            ->with('success', 'محدودیت زیرگروه «'.$surgerySubtype->name.'» ذخیره شد.');
    }

    public function destroySubtype(SurgerySubtype $surgerySubtype): RedirectResponse
    {
        $surgerySubtype->delete();

        return redirect()
            ->route('settings.surgery-types')
            ->with('success', 'زیرگروه حذف شد.');
    }
}
