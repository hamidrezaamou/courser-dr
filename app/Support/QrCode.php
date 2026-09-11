<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Self-contained QR Code (Model 2) encoder — byte mode, error correction level M.
 *
 * Written in-house on purpose: the clinic host cannot reach external QR services
 * and composer packages are not always installable there. Supports versions 1–10,
 * which is 213 bytes of payload — far more than a prescription reference needs.
 */
class QrCode
{
    /** Error correction table for level M: [ecPerBlock, g1Blocks, g1Data, g2Blocks, g2Data]. */
    private const EC_TABLE = [
        1 => [10, 1, 16, 0, 0],
        2 => [16, 1, 28, 0, 0],
        3 => [26, 1, 44, 0, 0],
        4 => [18, 2, 32, 0, 0],
        5 => [24, 2, 43, 0, 0],
        6 => [16, 4, 27, 0, 0],
        7 => [18, 4, 31, 0, 0],
        8 => [22, 2, 38, 2, 39],
        9 => [22, 3, 36, 2, 37],
        10 => [26, 4, 43, 1, 44],
    ];

    /** Alignment pattern centre coordinates per version. */
    private const ALIGNMENT = [
        1 => [],
        2 => [6, 18],
        3 => [6, 22],
        4 => [6, 26],
        5 => [6, 30],
        6 => [6, 34],
        7 => [6, 22, 38],
        8 => [6, 24, 42],
        9 => [6, 26, 46],
        10 => [6, 28, 50],
    ];

    /** @var list<int>|null */
    private static ?array $expTable = null;

    /** @var array<int, int>|null */
    private static ?array $logTable = null;

    /**
     * Build the QR matrix for a payload.
     *
     * @return list<list<bool>> row-major grid, true = dark module
     */
    public static function matrix(string $payload): array
    {
        $bytes = array_values(unpack('C*', $payload) ?: []);
        if ($bytes === []) {
            throw new InvalidArgumentException('QR payload is empty.');
        }

        $version = self::pickVersion(count($bytes));
        [$ecPerBlock, $g1Blocks, $g1Data, $g2Blocks, $g2Data] = self::EC_TABLE[$version];
        $dataCapacity = ($g1Blocks * $g1Data) + ($g2Blocks * $g2Data);

        $codewords = self::encodeData($bytes, $version, $dataCapacity);
        $interleaved = self::interleave($codewords, $ecPerBlock, $g1Blocks, $g1Data, $g2Blocks, $g2Data);

        $size = 17 + (4 * $version);
        $modules = array_fill(0, $size, array_fill(0, $size, false));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        self::drawFunctionPatterns($modules, $reserved, $size, $version);
        self::drawData($modules, $reserved, $size, $interleaved);

        $best = null;
        $bestPenalty = PHP_INT_MAX;

        for ($mask = 0; $mask < 8; $mask++) {
            $candidate = self::applyMask($modules, $reserved, $size, $mask);
            self::drawFormatBits($candidate, $size, $mask);
            $penalty = self::penalty($candidate, $size);

            if ($penalty < $bestPenalty) {
                $bestPenalty = $penalty;
                $best = $candidate;
            }
        }

        return $best;
    }

    private static function pickVersion(int $length): int
    {
        foreach (self::EC_TABLE as $version => [$ecPerBlock, $g1Blocks, $g1Data, $g2Blocks, $g2Data]) {
            $capacityBits = (($g1Blocks * $g1Data) + ($g2Blocks * $g2Data)) * 8;
            $neededBits = 4 + self::charCountBits($version) + ($length * 8);

            if ($neededBits <= $capacityBits) {
                return $version;
            }
        }

        throw new InvalidArgumentException('QR payload is too long (max 213 bytes).');
    }

    private static function charCountBits(int $version): int
    {
        return $version <= 9 ? 8 : 16;
    }

    /**
     * @param  list<int>  $bytes
     * @return list<int> data codewords, padded to capacity
     */
    private static function encodeData(array $bytes, int $version, int $dataCapacity): array
    {
        $bits = [];

        // Mode indicator: byte mode.
        self::pushBits($bits, 0b0100, 4);
        self::pushBits($bits, count($bytes), self::charCountBits($version));

        foreach ($bytes as $byte) {
            self::pushBits($bits, $byte, 8);
        }

        // Terminator, then pad to a whole codeword.
        $capacityBits = $dataCapacity * 8;
        $terminator = min(4, $capacityBits - count($bits));
        self::pushBits($bits, 0, $terminator);

        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        $codewords = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $value = 0;
            for ($j = 0; $j < 8; $j++) {
                $value = ($value << 1) | $bits[$i + $j];
            }
            $codewords[] = $value;
        }

        // Alternating pad bytes until the block is full.
        $pad = [0xEC, 0x11];
        $index = 0;
        while (count($codewords) < $dataCapacity) {
            $codewords[] = $pad[$index % 2];
            $index++;
        }

        return $codewords;
    }

    /**
     * @param  list<int>  $bits
     */
    private static function pushBits(array &$bits, int $value, int $length): void
    {
        for ($i = $length - 1; $i >= 0; $i--) {
            $bits[] = ($value >> $i) & 1;
        }
    }

    /**
     * Split into blocks, append Reed-Solomon codewords, then interleave both halves.
     *
     * @param  list<int>  $codewords
     * @return list<int>
     */
    private static function interleave(array $codewords, int $ecPerBlock, int $g1Blocks, int $g1Data, int $g2Blocks, int $g2Data): array
    {
        $dataBlocks = [];
        $ecBlocks = [];
        $offset = 0;

        foreach ([[$g1Blocks, $g1Data], [$g2Blocks, $g2Data]] as [$blocks, $length]) {
            for ($i = 0; $i < $blocks; $i++) {
                $block = array_slice($codewords, $offset, $length);
                $offset += $length;
                $dataBlocks[] = $block;
                $ecBlocks[] = self::errorCorrection($block, $ecPerBlock);
            }
        }

        $out = [];
        $longest = max($g1Data, $g2Data);

        for ($i = 0; $i < $longest; $i++) {
            foreach ($dataBlocks as $block) {
                if ($i < count($block)) {
                    $out[] = $block[$i];
                }
            }
        }

        for ($i = 0; $i < $ecPerBlock; $i++) {
            foreach ($ecBlocks as $block) {
                $out[] = $block[$i];
            }
        }

        return $out;
    }

    /**
     * @param  list<int>  $data
     * @return list<int>
     */
    public static function errorCorrection(array $data, int $ecLength): array
    {
        $generator = self::generatorPoly($ecLength);
        $buffer = array_merge($data, array_fill(0, $ecLength, 0));

        for ($i = 0, $n = count($data); $i < $n; $i++) {
            $factor = $buffer[$i];
            if ($factor === 0) {
                continue;
            }

            foreach ($generator as $j => $coefficient) {
                $buffer[$i + $j] ^= self::gfMultiply($coefficient, $factor);
            }
        }

        return array_values(array_slice($buffer, count($data), $ecLength));
    }

    /**
     * @return list<int> coefficients, highest degree first
     */
    private static function generatorPoly(int $degree): array
    {
        self::initTables();
        $poly = [1];

        for ($i = 0; $i < $degree; $i++) {
            $poly = self::polyMultiply($poly, [1, self::$expTable[$i]]);
        }

        return $poly;
    }

    /**
     * @param  list<int>  $a
     * @param  list<int>  $b
     * @return list<int>
     */
    private static function polyMultiply(array $a, array $b): array
    {
        $out = array_fill(0, count($a) + count($b) - 1, 0);

        foreach ($a as $i => $av) {
            foreach ($b as $j => $bv) {
                $out[$i + $j] ^= self::gfMultiply($av, $bv);
            }
        }

        return $out;
    }

    private static function gfMultiply(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }

        self::initTables();

        return self::$expTable[self::$logTable[$a] + self::$logTable[$b]];
    }

    private static function initTables(): void
    {
        if (self::$expTable !== null) {
            return;
        }

        $exp = [];
        $log = [];
        $x = 1;

        for ($i = 0; $i < 255; $i++) {
            $exp[$i] = $x;
            $log[$x] = $i;
            $x <<= 1;
            if ($x >= 256) {
                $x ^= 0x11D;
            }
        }

        for ($i = 255; $i < 512; $i++) {
            $exp[$i] = $exp[$i - 255];
        }

        self::$expTable = $exp;
        self::$logTable = $log;
    }

    /**
     * @param  list<list<bool>>  $modules
     * @param  list<list<bool>>  $reserved
     */
    private static function drawFunctionPatterns(array &$modules, array &$reserved, int $size, int $version): void
    {
        foreach ([[0, 0], [0, $size - 7], [$size - 7, 0]] as [$row, $col]) {
            self::drawFinder($modules, $reserved, $size, $row, $col);
        }

        // Timing patterns.
        for ($i = 8; $i < $size - 8; $i++) {
            $dark = $i % 2 === 0;
            $modules[6][$i] = $dark;
            $reserved[6][$i] = true;
            $modules[$i][6] = $dark;
            $reserved[$i][6] = true;
        }

        $centres = self::ALIGNMENT[$version];
        foreach ($centres as $row) {
            foreach ($centres as $col) {
                $nearFinder = ($row === 6 && $col === 6)
                    || ($row === 6 && $col === $size - 7)
                    || ($row === $size - 7 && $col === 6);

                if (! $nearFinder) {
                    self::drawAlignment($modules, $reserved, $row, $col);
                }
            }
        }

        // Reserve the format information areas.
        for ($i = 0; $i <= 8; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
        }
        for ($i = 0; $i < 8; $i++) {
            $reserved[8][$size - 1 - $i] = true;
            $reserved[$size - 1 - $i][8] = true;
        }

        // The always-dark module.
        $modules[$size - 8][8] = true;
        $reserved[$size - 8][8] = true;

        if ($version >= 7) {
            self::drawVersionBits($modules, $reserved, $size, $version);
        }
    }

    /**
     * @param  list<list<bool>>  $modules
     * @param  list<list<bool>>  $reserved
     */
    private static function drawFinder(array &$modules, array &$reserved, int $size, int $row, int $col): void
    {
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                $rr = $row + $r;
                $cc = $col + $c;

                if ($rr < 0 || $rr >= $size || $cc < 0 || $cc >= $size) {
                    continue;
                }

                $onRing = ($r >= 0 && $r <= 6 && ($c === 0 || $c === 6))
                    || ($c >= 0 && $c <= 6 && ($r === 0 || $r === 6));
                $inCore = $r >= 2 && $r <= 4 && $c >= 2 && $c <= 4;

                $modules[$rr][$cc] = $onRing || $inCore;
                $reserved[$rr][$cc] = true;
            }
        }
    }

    /**
     * @param  list<list<bool>>  $modules
     * @param  list<list<bool>>  $reserved
     */
    private static function drawAlignment(array &$modules, array &$reserved, int $row, int $col): void
    {
        for ($r = -2; $r <= 2; $r++) {
            for ($c = -2; $c <= 2; $c++) {
                $modules[$row + $r][$col + $c] = max(abs($r), abs($c)) !== 1;
                $reserved[$row + $r][$col + $c] = true;
            }
        }
    }

    /**
     * @param  list<list<bool>>  $modules
     * @param  list<list<bool>>  $reserved
     */
    private static function drawVersionBits(array &$modules, array &$reserved, int $size, int $version): void
    {
        $remainder = $version;
        for ($i = 0; $i < 12; $i++) {
            $remainder = ($remainder << 1) ^ (($remainder >> 11) * 0x1F25);
        }
        $bits = ($version << 12) | $remainder;

        for ($i = 0; $i < 18; $i++) {
            $bit = (($bits >> $i) & 1) === 1;
            $a = $size - 11 + ($i % 3);
            $b = intdiv($i, 3);

            $modules[$b][$a] = $bit;
            $reserved[$b][$a] = true;
            $modules[$a][$b] = $bit;
            $reserved[$a][$b] = true;
        }
    }

    /**
     * Zigzag placement of the codeword bit stream, skipping function modules.
     *
     * @param  list<list<bool>>  $modules
     * @param  list<list<bool>>  $reserved
     * @param  list<int>  $codewords
     */
    private static function drawData(array &$modules, array $reserved, int $size, array $codewords): void
    {
        $totalBits = count($codewords) * 8;
        $index = 0;

        for ($right = $size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }

            for ($vertical = 0; $vertical < $size; $vertical++) {
                for ($j = 0; $j < 2; $j++) {
                    $col = $right - $j;
                    $upward = (($right + 1) & 2) === 0;
                    $row = $upward ? $size - 1 - $vertical : $vertical;

                    if ($reserved[$row][$col] || $index >= $totalBits) {
                        continue;
                    }

                    $byte = $codewords[$index >> 3];
                    $modules[$row][$col] = (($byte >> (7 - ($index & 7))) & 1) === 1;
                    $index++;
                }
            }
        }
    }

    /**
     * @param  list<list<bool>>  $modules
     * @param  list<list<bool>>  $reserved
     * @return list<list<bool>>
     */
    private static function applyMask(array $modules, array $reserved, int $size, int $mask): array
    {
        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size; $col++) {
                if ($reserved[$row][$col]) {
                    continue;
                }

                $flip = match ($mask) {
                    0 => ($row + $col) % 2 === 0,
                    1 => $row % 2 === 0,
                    2 => $col % 3 === 0,
                    3 => ($row + $col) % 3 === 0,
                    4 => (intdiv($row, 2) + intdiv($col, 3)) % 2 === 0,
                    5 => (($row * $col) % 2) + (($row * $col) % 3) === 0,
                    6 => ((($row * $col) % 2) + (($row * $col) % 3)) % 2 === 0,
                    default => ((($row + $col) % 2) + (($row * $col) % 3)) % 2 === 0,
                };

                if ($flip) {
                    $modules[$row][$col] = ! $modules[$row][$col];
                }
            }
        }

        return $modules;
    }

    /**
     * @param  list<list<bool>>  $modules
     */
    private static function drawFormatBits(array &$modules, int $size, int $mask): void
    {
        // Error correction level M is 0b00, so the 5-bit value is just the mask.
        $data = $mask;
        $remainder = $data;
        for ($i = 0; $i < 10; $i++) {
            $remainder = ($remainder << 1) ^ (($remainder >> 9) * 0x537);
        }
        $bits = (($data << 10) | $remainder) ^ 0x5412;

        $bit = static fn (int $i): bool => (($bits >> $i) & 1) === 1;

        for ($i = 0; $i <= 5; $i++) {
            $modules[$i][8] = $bit($i);
        }
        $modules[7][8] = $bit(6);
        $modules[8][8] = $bit(7);
        $modules[8][7] = $bit(8);
        for ($i = 9; $i < 15; $i++) {
            $modules[8][14 - $i] = $bit($i);
        }

        for ($i = 0; $i < 8; $i++) {
            $modules[8][$size - 1 - $i] = $bit($i);
        }
        for ($i = 8; $i < 15; $i++) {
            $modules[$size - 15 + $i][8] = $bit($i);
        }

        $modules[$size - 8][8] = true;
    }

    /**
     * The four penalty rules from the specification; lower is better.
     *
     * @param  list<list<bool>>  $modules
     */
    private static function penalty(array $modules, int $size): int
    {
        $score = 0;

        // Rule 1 — runs of five or more identical modules.
        for ($i = 0; $i < $size; $i++) {
            $score += self::runPenalty(self::row($modules, $i));
            $score += self::runPenalty(self::column($modules, $i, $size));
        }

        // Rule 2 — 2x2 blocks of one colour.
        for ($row = 0; $row < $size - 1; $row++) {
            for ($col = 0; $col < $size - 1; $col++) {
                $value = $modules[$row][$col];
                if ($value === $modules[$row][$col + 1]
                    && $value === $modules[$row + 1][$col]
                    && $value === $modules[$row + 1][$col + 1]) {
                    $score += 3;
                }
            }
        }

        // Rule 3 — finder-like 1:1:3:1:1 sequences.
        for ($i = 0; $i < $size; $i++) {
            $score += self::finderPenalty(self::row($modules, $i));
            $score += self::finderPenalty(self::column($modules, $i, $size));
        }

        // Rule 4 — deviation from a 50% dark ratio.
        $dark = 0;
        foreach ($modules as $row) {
            foreach ($row as $value) {
                if ($value) {
                    $dark++;
                }
            }
        }

        $total = $size * $size;
        $score += 10 * intdiv((int) floor(abs(($dark * 100 / $total) - 50)), 5);

        return $score;
    }

    /**
     * @param  list<list<bool>>  $modules
     * @return list<bool>
     */
    private static function row(array $modules, int $index): array
    {
        return $modules[$index];
    }

    /**
     * @param  list<list<bool>>  $modules
     * @return list<bool>
     */
    private static function column(array $modules, int $index, int $size): array
    {
        $out = [];
        for ($row = 0; $row < $size; $row++) {
            $out[] = $modules[$row][$index];
        }

        return $out;
    }

    /**
     * @param  list<bool>  $line
     */
    private static function runPenalty(array $line): int
    {
        $score = 0;
        $runLength = 1;

        for ($i = 1, $n = count($line); $i < $n; $i++) {
            if ($line[$i] === $line[$i - 1]) {
                $runLength++;

                continue;
            }

            if ($runLength >= 5) {
                $score += 3 + ($runLength - 5);
            }
            $runLength = 1;
        }

        if ($runLength >= 5) {
            $score += 3 + ($runLength - 5);
        }

        return $score;
    }

    /**
     * @param  list<bool>  $line
     */
    private static function finderPenalty(array $line): int
    {
        $pattern = [true, false, true, true, true, false, true];
        $light = [false, false, false, false];
        $score = 0;
        $n = count($line);

        for ($i = 0; $i + 7 <= $n; $i++) {
            if (array_slice($line, $i, 7) !== $pattern) {
                continue;
            }

            $before = $i >= 4 && array_slice($line, $i - 4, 4) === $light;
            $after = $i + 11 <= $n && array_slice($line, $i + 7, 4) === $light;

            if ($before || $after) {
                $score += 40;
            }
        }

        return $score;
    }
}
