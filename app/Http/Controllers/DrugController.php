<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DrugController extends Controller
{
    public function index(): View
    {
        $drugs = Drug::query()->ordered()->get();

        return view('drugs.index', [
            'drugs' => $drugs,
            'settingsSection' => 'drugs',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'dosage_form' => ['nullable', 'string', 'max:100'],
            'default_dosage' => ['nullable', 'string', 'max:100'],
            'default_frequency' => ['nullable', 'string', 'max:100'],
            'default_duration' => ['nullable', 'string', 'max:100'],
            'default_instructions' => ['nullable', 'string', 'max:500'],
        ]);

        Drug::create([
            'name' => trim($validated['name']),
            'generic_name' => $validated['generic_name'] ?? null,
            'dosage_form' => $validated['dosage_form'] ?? null,
            'default_dosage' => $validated['default_dosage'] ?? null,
            'default_frequency' => $validated['default_frequency'] ?? null,
            'default_duration' => $validated['default_duration'] ?? null,
            'default_instructions' => $validated['default_instructions'] ?? null,
            'sort_order' => (int) Drug::query()->max('sort_order') + 1,
        ]);

        return redirect()
            ->route('settings.drugs')
            ->with('success', 'دارو با موفقیت ثبت شد.');
    }

    public function update(Request $request, Drug $drug): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'dosage_form' => ['nullable', 'string', 'max:100'],
            'default_dosage' => ['nullable', 'string', 'max:100'],
            'default_frequency' => ['nullable', 'string', 'max:100'],
            'default_duration' => ['nullable', 'string', 'max:100'],
            'default_instructions' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $drug->update([
            'name' => trim($validated['name']),
            'generic_name' => $validated['generic_name'] ?? null,
            'dosage_form' => $validated['dosage_form'] ?? null,
            'default_dosage' => $validated['default_dosage'] ?? null,
            'default_frequency' => $validated['default_frequency'] ?? null,
            'default_duration' => $validated['default_duration'] ?? null,
            'default_instructions' => $validated['default_instructions'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('settings.drugs')
            ->with('success', 'دارو به‌روزرسانی شد.');
    }

    public function destroy(Drug $drug): RedirectResponse
    {
        $drug->delete();

        return redirect()
            ->route('settings.drugs')
            ->with('success', 'دارو حذف شد.');
    }
}
