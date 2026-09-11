<?php

namespace App\Support\His;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Shared batch loop for every HIS resource.
 *
 * One bad row must never lose the rest of the batch, so each row runs in its
 * own transaction and failures are collected rather than thrown.
 */
abstract class HisImporter
{
    /**
     * @param  array<string, mixed>  $row
     * @return string one of created|updated|skipped
     */
    abstract protected function importRow(array $row): string;

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function import(array $rows): HisImportResult
    {
        $result = new HisImportResult();

        foreach ($rows as $index => $row) {
            $result->received++;
            $hisId = isset($row['his_id']) ? (string) $row['his_id'] : null;

            try {
                $outcome = DB::transaction(fn () => $this->importRow($row));
                $result->record($outcome);
                $this->advanceWatermark($result, $row);
            } catch (Throwable $error) {
                $result->fail((int) $index, $hisId, $error->getMessage());
            }
        }

        return $result;
    }

    /**
     * The watermark only moves across rows that actually landed, so a failure
     * mid-batch cannot cause the agent to skip past unimported records.
     *
     * @param  array<string, mixed>  $row
     */
    private function advanceWatermark(HisImportResult $result, array $row): void
    {
        $hisId = isset($row['his_id']) ? (string) $row['his_id'] : null;
        if ($hisId !== null && ($result->lastId === null || $this->idIsAfter($hisId, $result->lastId))) {
            $result->lastId = $hisId;
        }

        $changed = isset($row['changed_at']) ? trim((string) $row['changed_at']) : '';
        if ($changed !== '' && ($result->lastChangedAt === null || $changed > $result->lastChangedAt)) {
            $result->lastChangedAt = $changed;
        }
    }

    /** HIS ids are usually numeric but may be GUIDs, so handle both. */
    private function idIsAfter(string $candidate, string $current): bool
    {
        if (ctype_digit($candidate) && ctype_digit($current)) {
            return (int) $candidate > (int) $current;
        }

        return strcmp($candidate, $current) > 0;
    }

    protected function text(mixed $value, ?int $limit = null): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        return $limit === null ? $text : mb_substr($text, 0, $limit);
    }
}
