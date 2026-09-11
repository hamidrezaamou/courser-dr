<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\ReadyAnswer;
use App\Services\Sms\SmsIrClient;
use App\Support\FeatureFlags;
use App\Support\MessageTags;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadyAnswerController extends Controller
{
    private const FIELDS = [
        'id', 'title', 'category', 'body', 'is_pinned', 'usage_count', 'last_used_at', 'created_at',
    ];

    public function index(Request $request): JsonResponse
    {
        $this->ensureEnabled();

        $q = trim($request->string('q')->toString());
        $category = trim($request->string('category')->toString());
        $sort = $request->string('sort')->toString() ?: 'smart';

        $answers = ReadyAnswer::query()
            ->search($q)
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->ordered($sort)
            ->get(self::FIELDS);

        return response()->json([
            'data' => $answers,
            'count' => $answers->count(),
            'total' => ReadyAnswer::query()->count(),
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureEnabled();

        $answer = ReadyAnswer::query()->create($this->validated($request) + [
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'پاسخ ذخیره شد.',
            'data' => $answer->only(self::FIELDS),
            'categories' => $this->categories(),
        ], 201);
    }

    public function update(Request $request, ReadyAnswer $readyAnswer): JsonResponse
    {
        $this->ensureEnabled();

        $readyAnswer->update($this->validated($request));

        return response()->json([
            'message' => 'پاسخ ویرایش شد.',
            'data' => $readyAnswer->only(self::FIELDS),
            'categories' => $this->categories(),
        ]);
    }

    public function destroy(ReadyAnswer $readyAnswer): JsonResponse
    {
        $this->ensureEnabled();

        $readyAnswer->delete();

        return response()->json([
            'message' => 'پاسخ حذف شد.',
            'categories' => $this->categories(),
        ]);
    }

    public function togglePin(ReadyAnswer $readyAnswer): JsonResponse
    {
        $this->ensureEnabled();

        $readyAnswer->update(['is_pinned' => ! $readyAnswer->is_pinned]);

        return response()->json([
            'message' => $readyAnswer->is_pinned ? 'به بالای فهرست سنجاق شد.' : 'سنجاق برداشته شد.',
            'data' => $readyAnswer->only(self::FIELDS),
        ]);
    }

    public function duplicate(Request $request, ReadyAnswer $readyAnswer): JsonResponse
    {
        $this->ensureEnabled();

        $copy = ReadyAnswer::query()->create([
            'title' => trim(($readyAnswer->title ?: 'پاسخ').' (رونوشت)'),
            'category' => $readyAnswer->category,
            'body' => $readyAnswer->body,
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'رونوشت ساخته شد.',
            'data' => $copy->only(self::FIELDS),
        ], 201);
    }

    /** The panel pings this after a copy or a native share so "most used" stays honest. */
    public function markUsed(ReadyAnswer $readyAnswer): JsonResponse
    {
        $this->ensureEnabled();

        $readyAnswer->markUsed();

        return response()->json(['data' => $readyAnswer->only(self::FIELDS)]);
    }

    public function send(Request $request, SmsIrClient $smsIr): JsonResponse
    {
        $this->ensureEnabled();

        if (! config('reminders.sms.enabled') || config('reminders.sms.driver') !== 'smsir') {
            return response()->json(['message' => 'ارسال پیامک از پنل در تنظیمات فعال نیست.'], 422);
        }

        $validated = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:1000'],
            'answer_id' => ['nullable', 'integer', 'exists:ready_answers,id'],
        ]);

        try {
            $smsIr->send($validated['mobile'], $validated['message']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (! empty($validated['answer_id'])) {
            ReadyAnswer::query()->find($validated['answer_id'])?->markUsed();
        }

        return response()->json(['message' => 'پیامک با موفقیت ارسال شد.']);
    }

    public function bookings(Patient $patient): JsonResponse
    {
        $this->ensureEnabled();

        $surgeries = $patient->surgeryAppointments()
            ->with(['hospital', 'patient'])
            ->orderByDesc('scheduled_date')
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(fn ($item) => MessageTags::bookingPayload($item, 'surgery'));

        $visits = $patient->appointments()
            ->with('patient')
            ->orderByDesc('scheduled_date')
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(fn ($item) => MessageTags::bookingPayload($item, 'visit'));

        $rows = $surgeries->concat($visits)
            ->sortByDesc(function (array $row) {
                $date = (string) ($row['vars']['date'] ?? '');
                $id = (int) ($row['id'] ?? 0);

                return sprintf('%s-%010d', $date, $id);
            })
            ->values()
            ->take(40)
            ->all();

        return response()->json(['data' => $rows]);
    }

    private function ensureEnabled(): void
    {
        if (! auth()->user()?->isStaff()) {
            abort(403, 'دسترسی به پاسخ‌های آماده فقط برای پرسنل است.');
        }

        if (! FeatureFlags::enabled('features.ready_answers')) {
            abort(403, 'پاسخ‌های آماده غیرفعال است.');
        }
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:60'],
            'body' => ['required', 'string', 'max:2000'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        return [
            'title' => trim((string) ($data['title'] ?? '')) ?: null,
            'category' => trim((string) ($data['category'] ?? '')) ?: null,
            'body' => $data['body'],
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
        ];
    }

    private function categories(): array
    {
        return ReadyAnswer::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }
}
