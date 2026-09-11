<?php

namespace App\Support\His;

class HisImportResult
{
    public int $received = 0;

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public int $failed = 0;

    /** @var list<array{row: int, his_id: string|null, error: string}> */
    public array $errors = [];

    public ?string $lastId = null;

    public ?string $lastChangedAt = null;

    public function record(string $outcome): void
    {
        match ($outcome) {
            'created' => $this->created++,
            'updated' => $this->updated++,
            default => $this->skipped++,
        };
    }

    public function fail(int $index, ?string $hisId, string $message): void
    {
        $this->failed++;

        // Keep the payload bounded; the counters still show the true totals.
        if (count($this->errors) < 25) {
            $this->errors[] = ['row' => $index, 'his_id' => $hisId, 'error' => $message];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'received' => $this->received,
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'failed' => $this->failed,
            'errors' => $this->errors,
        ];
    }
}
