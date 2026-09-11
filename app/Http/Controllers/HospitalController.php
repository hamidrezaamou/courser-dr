<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HospitalController extends Controller
{
    public function index(): View
    {
        $hospitals = Hospital::query()
            ->withCount('surgeryAppointments')
            ->orderByDesc('id')
            ->get();

        return view('hospitals.index', [
            'hospitals' => $hospitals,
            'settingsSection' => 'hospitals',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
        ], [
            'name.required' => 'لطفاً نام بیمارستان را وارد کنید.',
        ]);

        Hospital::create([
            'name' => trim($validated['name']),
            'address' => trim((string) ($validated['address'] ?? '')) ?: null,
            'phone' => trim((string) ($validated['phone'] ?? '')) ?: null,
        ]);

        return redirect()
            ->route('settings.hospitals')
            ->with('success', 'بیمارستان جدید با موفقیت اضافه شد.');
    }

    public function update(Request $request, Hospital $hospital): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
        ], [
            'name.required' => 'لطفاً نام بیمارستان را وارد کنید.',
        ]);

        $hospital->update([
            'name' => trim($validated['name']),
            'address' => trim((string) ($validated['address'] ?? '')) ?: null,
            'phone' => trim((string) ($validated['phone'] ?? '')) ?: null,
        ]);

        return redirect()
            ->route('settings.hospitals')
            ->with('success', 'اطلاعات بیمارستان به‌روزرسانی شد.');
    }

    public function destroy(Hospital $hospital): RedirectResponse
    {
        try {
            $hospital->delete();
        } catch (QueryException $e) {
            return redirect()
                ->route('settings.hospitals')
                ->with('error', 'این بیمارستان دارای نوبت ثبت‌شده است و قابل حذف نیست.');
        }

        return redirect()
            ->route('settings.hospitals')
            ->with('success', 'بیمارستان با موفقیت حذف شد.');
    }
}
