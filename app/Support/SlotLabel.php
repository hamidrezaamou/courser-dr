<?php

namespace App\Support;

class SlotLabel
{
    /**
     * Normalize a submitted slot to a DB-safe token.
     * Clock → H:i:s ; Queue → Q01..Q99
     */
    public static function normalize(string $value): string
    {
        $value = trim($value);

        if (preg_match('/^Q0*(\d{1,2})$/i', $value, $m)) {
            return sprintf('Q%02d', (int) $m[1]);
        }

        if (preg_match('/^نوبت\s*(\d{1,2})$/u', $value, $m)) {
            return sprintf('Q%02d', (int) $m[1]);
        }

        if (preg_match('/^queue[:\s-]*(\d{1,2})$/i', $value, $m)) {
            return sprintf('Q%02d', (int) $m[1]);
        }

        if (preg_match('/^\d{1,2}:\d{2}/', $value, $matches)) {
            return \Carbon\Carbon::createFromFormat('H:i', substr($matches[0], 0, 5))->format('H:i:s');
        }

        return \Carbon\Carbon::parse($value)->format('H:i:s');
    }

    public static function isQueue(string $value): bool
    {
        return (bool) preg_match('/^Q\d{2}$/i', trim($value));
    }

    public static function display(string $value): string
    {
        $value = trim($value);

        if (preg_match('/^Q0*(\d{1,2})$/i', $value, $m)) {
            return 'نوبت '.(int) $m[1];
        }

        // Legacy: queue encoded as 00:NN:00
        if (preg_match('/^00:(\d{2}):00$/', $value, $m) && (int) $m[1] >= 1 && (int) $m[1] <= 59) {
            return 'نوبت '.(int) $m[1];
        }

        if (preg_match('/^نوبت\s*(\d{1,2})$/u', $value, $m)) {
            return 'نوبت '.(int) $m[1];
        }

        if (preg_match('/^\d{1,2}:\d{2}/', $value, $matches)) {
            return substr($matches[0], 0, 5);
        }

        return $value;
    }

    /**
     * Build queue slot tokens نوبت 1..n as Q01..Qn
     *
     * @return list<string>
     */
    public static function queueTokens(int $count): array
    {
        $count = max(1, min(99, $count));
        $out = [];
        for ($i = 1; $i <= $count; $i++) {
            $out[] = sprintf('Q%02d', $i);
        }

        return $out;
    }

    /**
     * @param  list<string>  $rawLines
     * @return list<string>
     */
    public static function normalizeList(array $rawLines, string $mode = 'time'): array
    {
        if ($mode === 'queue') {
            $nums = [];
            foreach ($rawLines as $line) {
                $line = trim((string) $line);
                if ($line === '') {
                    continue;
                }
                if (preg_match('/(\d{1,2})/u', $line, $m)) {
                    $nums[] = (int) $m[1];
                }
            }
            $nums = array_values(array_unique($nums));
            sort($nums);
            if ($nums === []) {
                return self::queueTokens(10);
            }

            return array_map(fn ($n) => sprintf('Q%02d', $n), $nums);
        }

        return collect($rawLines)
            ->map(fn ($t) => trim((string) $t))
            ->filter()
            ->map(fn ($t) => self::normalize($t))
            // store clock as H:i in schedule JSON for readability
            ->map(function ($t) {
                if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $t)) {
                    return substr($t, 0, 5);
                }

                return $t;
            })
            ->unique()
            ->values()
            ->all();
    }
}
