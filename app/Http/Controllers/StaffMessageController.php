<?php

namespace App\Http\Controllers;

use App\Models\StaffNoteNotification;
use App\Support\Jalali;
use App\Support\ListPagination;
use App\Support\StaffNoteAlerts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffMessageController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isStaff(), 403);
        StaffNoteAlerts::ensureTables();

        $filter = (string) $request->query('filter', 'unread');
        if (! in_array($filter, ['unread', 'all'], true)) {
            $filter = 'unread';
        }

        $query = StaffNoteNotification::query()
            ->with(['patient', 'actor', 'note'])
            ->where('user_id', $request->user()->id)
            ->orderByRaw('CASE WHEN read_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('id');

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        $items = $query->paginate(ListPagination::perPage($request))->withQueryString();
        $unread = StaffNoteAlerts::unreadCount($request->user()->id);

        return view('messages.index', [
            'items' => $items,
            'filter' => $filter,
            'unread' => $unread,
        ]);
    }

    public function alertSummary(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isStaff(), 403);
        if (! StaffNoteAlerts::isAvailable()) {
            return response()->json([
                'ok' => true,
                'unread' => 0,
                'latest_id' => 0,
            ]);
        }

        $userId = (int) $request->user()->id;
        $unread = StaffNoteAlerts::unreadCount($userId);
        $latest = StaffNoteNotification::query()
            ->with(['patient', 'actor', 'note'])
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->orderByDesc('id')
            ->first();

        $preview = '';
        $patientName = '';
        $actorName = '';
        if ($latest) {
            $patientName = (string) ($latest->patient?->name ?? 'بیمار');
            $actorName = (string) ($latest->actor?->name ?? 'همکار');
            $preview = StaffNoteAlerts::preview((string) ($latest->note?->note ?? ''));
        }

        return response()->json([
            'ok' => true,
            'unread' => $unread,
            'latest_id' => (int) ($latest?->id ?? 0),
            'latest_note_id' => (int) ($latest?->internal_note_id ?? 0),
            'patient_id' => (int) ($latest?->patient_id ?? 0),
            'patient_name' => $patientName,
            'actor_name' => $actorName,
            'preview' => $preview,
            'open_path' => $this->openPath($latest),
            'title' => $unread > 0 ? 'پیام جدید در پرونده' : 'پیام پرونده',
            'body' => $unread > 0
                ? $actorName.' برای '.$patientName.' پیام گذاشت'
                : 'پیام خوانده‌نشده‌ای نیست',
            'date_jalali' => Jalali::format(now(), 'Y/m/d H:i'),
        ]);
    }

    private function openPath(?StaffNoteNotification $latest): string
    {
        $patientId = (int) ($latest?->patient_id ?? 0);
        if ($patientId < 1) {
            return '/messages';
        }

        $noteId = (int) ($latest?->internal_note_id ?? 0);
        $path = '/patients/'.$patientId;

        return $noteId > 0 ? $path.'?note='.$noteId : $path;
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isStaff(), 403);
        StaffNoteAlerts::markAllRead((int) $request->user()->id);

        return back()->with('success', 'همه پیام‌ها خوانده شد.');
    }
}
