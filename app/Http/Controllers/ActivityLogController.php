<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\FeatureFlags;
use App\Support\Jalali;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->resolveFilters($request);

        $logs = $this->filteredQuery($filters)
            ->with('user')
            ->latest()
            ->paginate(40)
            ->withQueryString();

        return view('activity-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'users' => $this->staffUsers(),
            'subjectTypes' => $this->subjectTypeOptions(),
            'canExport' => FeatureFlags::enabled('features.audit_export'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        if (! FeatureFlags::enabled('features.audit_export')) {
            throw new HttpException(403, 'خروجی ممیزی غیرفعال است.');
        }

        $filters = $this->resolveFilters($request);
        $query = $this->filteredQuery($filters)->with('user')->latest();

        ActivityLogger::log(auth()->user(), 'exported', null, [
            'section' => 'audit',
            'filters' => array_filter($filters, fn ($v) => $v !== '' && $v !== null),
        ]);

        $filename = 'activity-logs-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'id',
                'created_at',
                'jalali',
                'user',
                'action',
                'subject_type',
                'subject_id',
                'ip',
                'old_values',
                'new_values',
            ]);

            $query->chunk(200, function ($logs) use ($out) {
                foreach ($logs as $log) {
                    fputcsv($out, [
                        $log->id,
                        optional($log->created_at)?->toDateTimeString(),
                        jalali($log->created_at, 'Y/m/d H:i'),
                        $log->user?->name ?? 'سیستم',
                        $log->action,
                        class_basename((string) $log->subject_type),
                        $log->subject_id,
                        $log->ip_address,
                        $log->old_values ? json_encode($log->old_values, JSON_UNESCAPED_UNICODE) : '',
                        $log->new_values ? json_encode($log->new_values, JSON_UNESCAPED_UNICODE) : '',
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{action: string, user_id: string, subject_type: string, from: string, to: string, q: string, from_g: ?string, to_g: ?string}
     */
    private function resolveFilters(Request $request): array
    {
        $fromJalali = trim($request->string('from')->toString());
        $toJalali = trim($request->string('to')->toString());
        $fromG = null;
        $toG = null;

        if ($fromJalali !== '' || $toJalali !== '') {
            if ($fromJalali === '') {
                $fromJalali = $toJalali;
            }
            if ($toJalali === '') {
                $toJalali = $fromJalali;
            }

            try {
                $fromG = Jalali::parseJalaliDate($fromJalali)->startOfDay()->toDateTimeString();
                $toG = Jalali::parseJalaliDate($toJalali)->endOfDay()->toDateTimeString();
                if ($fromG > $toG) {
                    [$fromG, $toG] = [$toG, $fromG];
                    [$fromJalali, $toJalali] = [$toJalali, $fromJalali];
                }
            } catch (\Throwable) {
                $fromJalali = '';
                $toJalali = '';
                $fromG = null;
                $toG = null;
            }
        }

        $action = $request->string('action')->toString();
        $allowedActions = [
            'created', 'updated', 'status_changed', 'deleted', 'reminder_sent', 'exported',
            'viewed', 'login', 'logout', 'secure_erased',
        ];
        if ($action !== '' && ! in_array($action, $allowedActions, true)) {
            $action = '';
        }

        return [
            'action' => $action,
            'user_id' => $request->string('user_id')->toString(),
            'subject_type' => $request->string('subject_type')->toString(),
            'from' => $fromJalali,
            'to' => $toJalali,
            'q' => trim($request->string('q')->toString()),
            'from_g' => $fromG,
            'to_g' => $toG,
        ];
    }

    /**
     * @param  array{action: string, user_id: string, subject_type: string, from: string, to: string, q: string, from_g: ?string, to_g: ?string}  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        return ActivityLog::query()
            ->when($filters['action'] !== '', fn ($q) => $q->where('action', $filters['action']))
            ->when($filters['user_id'] !== '', fn ($q) => $q->where('user_id', (int) $filters['user_id']))
            ->when($filters['subject_type'] !== '', function ($q) use ($filters) {
                $needle = $filters['subject_type'];
                $q->where(function ($inner) use ($needle) {
                    $inner->where('subject_type', $needle)
                        ->orWhere('subject_type', 'like', '%\\'.$needle)
                        ->orWhere('subject_type', 'like', '%/'.$needle);
                });
            })
            ->when($filters['from_g'], fn ($q) => $q->where('created_at', '>=', $filters['from_g']))
            ->when($filters['to_g'], fn ($q) => $q->where('created_at', '<=', $filters['to_g']))
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $term = '%'.$filters['q'].'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('ip_address', 'like', $term)
                        ->orWhere('action', 'like', $term)
                        ->orWhere('subject_type', 'like', $term)
                        ->orWhere('old_values', 'like', $term)
                        ->orWhere('new_values', 'like', $term);
                });
            });
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function staffUsers()
    {
        return User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_DOCTOR, User::ROLE_ASSISTANT])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return array<string, string>
     */
    private function subjectTypeOptions(): array
    {
        $types = ActivityLog::query()
            ->select('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->filter()
            ->mapWithKeys(fn ($type) => [(string) $type => class_basename((string) $type)])
            ->all();

        asort($types);

        return $types;
    }
}
