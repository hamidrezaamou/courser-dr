<?php

namespace App\Http\Controllers;

use App\Models\InternalNote;
use App\Models\Patient;
use App\Support\StaffNoteAlerts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InternalNoteController extends Controller
{
    /**
     * Store a new internal note for the given patient.
     */
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        abort_unless($request->user()?->isStaff(), 403);

        $validated = $request->validate([
            'note' => ['required', 'string'],
        ]);

        $note = $patient->internalNotes()->create([
            'user_id' => auth()->id(),
            'note' => $validated['note'],
        ]);

        StaffNoteAlerts::notifyOthers($note);

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', 'پیام ثبت شد.');
    }

    public function update(Request $request, Patient $patient, InternalNote $note): RedirectResponse
    {
        abort_unless($request->user()?->isStaff(), 403);
        abort_unless($note->patient_id === $patient->id, 404);

        $validated = $request->validate([
            'note' => ['required', 'string'],
        ]);

        $note->update([
            'note' => $validated['note'],
        ]);

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', 'پیام ویرایش شد.');
    }

    public function destroy(Patient $patient, InternalNote $note): RedirectResponse
    {
        abort_unless(auth()->user()?->isStaff(), 403);
        abort_unless($note->patient_id === $patient->id, 404);

        $noteId = (int) $note->id;
        $note->delete();
        StaffNoteAlerts::forgetNote($noteId);

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', 'پیام حذف شد.');
    }
}
