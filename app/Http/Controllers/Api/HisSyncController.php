<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HisSyncLog;
use App\Models\HisSyncState;
use App\Support\His\HisResources;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Ingest endpoint for the agent running on the clinic's HIS server.
 *
 * The agent only pushes; it never reads patient data back out. The website
 * keeps the watermark so a reinstalled agent resumes instead of replaying
 * everything from the beginning.
 */
class HisSyncController extends Controller
{
    /**
     * Tells the agent where the last successful import stopped.
     */
    public function cursor(string $resource): JsonResponse
    {
        if (! HisResources::exists($resource)) {
            return response()->json(['message' => "Unknown resource: {$resource}"], 404);
        }

        $state = HisSyncState::forResource($resource);

        return response()->json([
            'resource' => $resource,
            'last_id' => $state->last_id,
            'last_changed_at' => $state->last_changed_at?->toIso8601String(),
            'last_success_at' => $state->last_success_at?->toIso8601String(),
            'max_rows' => (int) config('his.max_rows_per_batch', 500),
        ]);
    }

    public function store(Request $request, string $resource): JsonResponse
    {
        if (! HisResources::exists($resource)) {
            return response()->json(['message' => "Unknown resource: {$resource}"], 404);
        }

        $maxRows = max(1, (int) config('his.max_rows_per_batch', 500));

        try {
            $data = $request->validate([
                'batch_id' => ['nullable', 'string', 'max:64'],
                'rows' => ['required', 'array', 'max:'.$maxRows],
                'rows.*' => ['array'],
            ]);
        } catch (ValidationException $error) {
            return response()->json([
                'message' => 'Invalid payload.',
                'errors' => $error->errors(),
            ], 422);
        }

        $batchId = $data['batch_id'] ?? (string) Str::uuid();
        $state = HisSyncState::forResource($resource);
        $startedAt = microtime(true);

        $result = HisResources::importer($resource)->import($data['rows']);

        $duration = (int) round((microtime(true) - $startedAt) * 1000);

        HisSyncLog::create([
            'resource' => $resource,
            'batch_id' => $batchId,
            'received' => $result->received,
            'created' => $result->created,
            'updated' => $result->updated,
            'skipped' => $result->skipped,
            'failed' => $result->failed,
            'duration_ms' => $duration,
            'errors' => $result->errors ?: null,
        ]);

        $state->last_run_at = now();
        $state->last_status = $result->failed > 0 ? 'partial' : 'ok';
        $state->last_message = $result->failed > 0
            ? $result->failed.' row(s) failed'
            : null;

        // Only move the watermark over rows that actually landed, otherwise a
        // failure mid-batch would be skipped forever on the next run.
        if ($result->lastId !== null) {
            $state->last_id = $result->lastId;
        }
        if ($result->lastChangedAt !== null) {
            $state->last_changed_at = $result->lastChangedAt;
        }

        if ($result->failed === 0) {
            $state->last_success_at = now();
        }

        $state->rows_imported += $result->created + $result->updated;
        $state->save();

        return response()->json([
            'resource' => $resource,
            'batch_id' => $batchId,
            'duration_ms' => $duration,
            'cursor' => [
                'last_id' => $state->last_id,
                'last_changed_at' => $state->last_changed_at?->toIso8601String(),
            ],
        ] + $result->toArray());
    }

    /**
     * Lets the agent prove the key and clock are good before pushing patient
     * data, so a misconfigured install fails on an empty request instead.
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'server_time' => now()->toIso8601String(),
            'resources' => HisResources::names(),
            'max_rows' => (int) config('his.max_rows_per_batch', 500),
        ]);
    }
}
