<?php

namespace App\Support;

/**
 * QR Code (ISO/IEC 18004 model 2) encoder — byte mode, versions 1..10,
 * error-correction level M with an automatic fallback to L for long payloads.
 *
 * Self-contained on purpose: no composer package, no GD extension and no
 * external web service, so printing keeps working on a cPanel host with no
 * outbound internet access.
 */
class QrEncoder
{
    /** ECC level bit patterns used inside the format information. */
    private const ECL_M = 0b00;

    private const ECL_L = 0b01;

    /** [ecCodewordsPerBlock, blocksGroup1, dataPerBlock1, blocksGroup2, dataPerBlock2] */
    private const BLOCKS_M = [
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

    private const BLOCKS_L = [
        1 => [7, 1, 19, 0, 0],
        2 => [10, 1, 34, 0, 0],
        3 => [15, 1, 55, 0, 0],
        4 => [20, 1, 80, 0, 0],
        5 => [26, 1, 108, 0, 0],
        6 => [18, 2, 68, 0, 0],
        7 => [20, 2, 78, 0, 0],
        8 => [24, 2, 97, 0, 0],
        9 => [30, 2, 116, 0, 0],
        10 => [18, 2, 68, 2, 69],
    ];

    /** Alignment pattern centre coordinates per version. */
    private const ALIGN = [
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

    private const PENALTY_N1 = 3;

    private const PENALTY_N2 = 3;

    private const PENALTY_N3 = 40;

    private const PENALTY_N4 = 10;

    /**
     * Encode a payload into a square matrix of booleans (true = dark module).
     *
     * @return list<list<bool>>|null null when the payload is too long for version 10
     */
    public static function matrix(string $payload): ?array
    {
        if ($payload === '') {
            return null;
        }

        $bytes = array_values(unpack('C*', $payload));
        $length = count($bytes);

        foreach ([[self::BLOCKS_M, self::ECL_M], [self::BLOCKS_L, self::ECL_L]] as [$table, $eclBits]) {
            foreach ($table as $version => $spec) {
                $dataCodewords = ($spec[1] * $spec[2]) + ($spec[3] * $spec[4]);
                $countBits = $version <= 9 ? 8 : 16;
                if (4 + $countBits + ($length * 8) <= $dataCodewords * 8) {
                    return self::build($bytes, $version, $spec, $eclBits);
                }
            }
        }

        return null;
    }

    /**
     * Render the payload as standalone SVG markup, ready to be inlined in HTML.
     */
    public static function svg(string $payload, int $sizePx = 140, int $quietZone = 4): ?string
    {
        $matrix = self::matrix($payload);
        if ($matrix === null) {
            return null;
        }

        $modules = count($matrix);
        $span = $modules + ($quietZone * 2);

        $path = '';
        foreach ($matrix as $y => $row) {
            foreach ($row as $x => $dark) {
                if ($dark) {
                    $path .= 'M'.($x + $quietZone).' '.($y + $quietZone).'h1v1h-1z';
                }
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$span.' '.$span.'"'
            .' width="'.$sizePx.'" height="'.$sizePx.'" shape-rendering="crispEdges" role="img">'
            .'<rect width="'.$span.'" height="'.$span.'" fill="#ffffff"/>'
            .'<path d="'.$path.'" fill="#000000"/>'
            .'</svg>';
    }

    /**
     * Render the payload as a PNG binary string. Requires the GD extension.
     */
    public static function png(string $payload, int $sizePx = 280, int $quietZone = 4): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $matrix = self::matrix($payload);
        if ($matrix === null) {
            return null;
        }

        $modules = count($matrix);
        $span = $modules + ($quietZone * 2);
        $scale = max(1, (int) floor($sizePx / $span));
        $pixels = $span * $scale;

        $image = imagecreatetruecolor($pixels, $pixels);
        if ($image === false) {
            return null;
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $pixels - 1, $pixels - 1, $white);

        foreach ($matrix as $y => $row) {
            foreach ($row as $x => $dark) {
                if (! $dark) {
                    continue;
                }
                $left = ($x + $quietZone) * $scale;
                $top = ($y + $quietZone) * $scale;
                imagefilledrectangle($image, $left, $top, $left + $scale - 1, $top + $scale - 1, $black);
            }
        }

        ob_start();
        imagepng($image);
        $binary = (string) ob_get_clean();
        imagedestroy($image);

        return $binary !== '' ? $binary : null;
    }

    /**
     * @param  list<int>  $bytes
     * @param  array{0:int,1:int,2:int,3:int,4:int}  $spec
     * @return list<list<bool>>
     */
    private static function build(array $bytes, int $version, array $spec, int $eclBits): array
    {
        [$ecPerBlock, $blocks1, $data1, $blocks2, $data2] = $spec;
        $dataCodewords = ($blocks1 * $data1) + ($blocks2 * $data2);
        $countBits = $version <= 9 ? 8 : 16;

        $bits = [];
        self::appendBits($bits, 0b0100, 4);
        self::appendBits($bits, count($bytes), $countBits);
        foreach ($bytes as $byte) {
            self::appendBits($bits, $byte, 8);
        }

        $capacity = $dataCodewords * 8;
        for ($i = 0, $terminator = min(4, $capacity - count($bits)); $i < $terminator; $i++) {
            $bits[] = 0;
        }
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }
        for ($pad = 0xEC; count($bits) < $capacity; $pad ^= 0xEC ^ 0x11) {
            self::appendBits($bits, $pad, 8);
        }

        $codewords = [];
        for ($i = 0, $n = count($bits); $i < $n; $i += 8) {
            $byte = 0;
            for ($j = 0; $j < 8; $j++) {
                $byte = ($byte << 1) | $bits[$i + $j];
            }
            $codewords[] = $byte;
        }

        $generator = self::rsGenerator($ecPerBlock);
        $blocks = [];
        $offset = 0;
        foreach ([[$blocks1, $data1], [$blocks2, $data2]] as [$count, $size]) {
            for ($i = 0; $i < $count; $i++) {
                $chunk = array_slice($codewords, $offset, $size);
                $offset += $size;
                $blocks[] = ['data' => $chunk, 'ec' => self::rsRemainder($chunk, $generator)];
            }
        }

        // Interleave: one codeword from each block per pass, shorter blocks simply run out first.
        $stream = [];
        $longest = max($data1, $data2);
        for ($i = 0; $i < $longest; $i++) {
            foreach ($blocks as $block) {
                if ($i < count($block['data'])) {
                    $stream[] = $block['data'][$i];
                }
            }
        }
        for ($i = 0; $i < $ecPerBlock; $i++) {
            foreach ($blocks as $block) {
                $stream[] = $block['ec'][$i];
            }
        }

        return self::plot($stream, $version, $eclBits);
    }

    /**
     * @param  list<int>  $stream
     * @return list<list<bool>>
     */
    private static function plot(array $stream, int $version, int $eclBits): array
    {
        $size = ($version * 4) + 17;
        $modules = array_fill(0, $size, array_fill(0, $size, false));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        for ($i = 0; $i < $size; $i++) {
            self::reserve($modules, $reserved, 6, $i, $i % 2 === 0);
            self::reserve($modules, $reserved, $i, 6, $i % 2 === 0);
        }

        foreach ([[3, 3], [$size - 4, 3], [3, $size - 4]] as [$fx, $fy]) {
            for ($dy = -4; $dy <= 4; $dy++) {
                for ($dx = -4; $dx <= 4; $dx++) {
                    $distance = max(abs($dx), abs($dy));
                    self::reserve($modules, $reserved, $fx + $dx, $fy + $dy, $distance !== 2 && $distance !== 4);
                }
            }
        }

        $centres = self::ALIGN[$version];
        $total = count($centres);
        for ($i = 0; $i < $total; $i++) {
            for ($j = 0; $j < $total; $j++) {
                $isFinderCorner = ($i === 0 && $j === 0)
                    || ($i === 0 && $j === $total - 1)
                    || ($i === $total - 1 && $j === 0);
                if ($isFinderCorner) {
                    continue;
                }
                for ($dy = -2; $dy <= 2; $dy++) {
                    for ($dx = -2; $dx <= 2; $dx++) {
                        self::reserve($modules, $reserved, $centres[$i] + $dx, $centres[$j] + $dy, max(abs($dx), abs($dy)) !== 1);
                    }
                }
            }
        }

        self::drawFormat($modules, $reserved, $size, $eclBits, 0);
        self::drawVersion($modules, $reserved, $size, $version);

        $bitIndex = 0;
        $totalBits = count($stream) * 8;
        for ($right = $size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            for ($vertical = 0; $vertical < $size; $vertical++) {
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    $upward = (($right + 1) & 2) === 0;
                    $y = $upward ? $size - 1 - $vertical : $vertical;
                    if (! $reserved[$y][$x] && $bitIndex < $totalBits) {
                        $modules[$y][$x] = (($stream[$bitIndex >> 3] >> (7 - ($bitIndex & 7))) & 1) === 1;
                        $bitIndex++;
                    }
                }
            }
        }

        $best = null;
        $bestPenalty = PHP_INT_MAX;
        for ($mask = 0; $mask < 8; $mask++) {
            $candidate = $modules;
            self::applyMask($candidate, $reserved, $size, $mask);
            self::drawFormat($candidate, $reserved, $size, $eclBits, $mask);
            $penalty = self::penalty($candidate, $size);
            if ($penalty < $bestPenalty) {
                $bestPenalty = $penalty;
                $best = $candidate;
            }
        }

        return $best ?? $modules;
    }

    /**
     * @param  list<list<bool>>  $modules
     * @param  list<list<bool>>  $reserved
     */
    private static function reserve(array &$modules, array &$reserved, int $x, int $y, bool $dark): void
    {
        $size = count($modules);
        if ($x < 0 || $y < 0 || $x >= $size || $y >= $size) {
            return;
        }

        $modules[$y][$x] = $dark;
        $reserved[$y][$x] = true;
    }

    /**
     * @param  list<list<bool>>  $modules
     * @param  list<list<bool>>  $reserved
     */
    private static function drawFormat(array &$modules, array &$reserved, int $size, int $eclBits, int $mask): void
    {
        $data = ($eclBits << 3) | $mask;
        $remainder = $data;
        for ($i = 0; $i < 10; $i++) {
            $remainder = ($remainder << 1) ^ ((($remainder >> 9) & 1) * 0x537);
        }
        $bits = ((($data << 10) | $remainder) ^ 0x5412) & 0x7FFF;

        for ($i = 0; $i <= 5; $i++) {
            self::reserve($modules, $reserved, 8, $i, self::bit($bits, $i));
        }
        self::reserve($modules, $reserved, 8, 7, self::bit($bits, 6));
        self::reserve($modules, $reserved, 8, 8, self::bit($bits, 7));
        self::reserve($modules, $reserved, 7, 8, self::bit($bits, 8));
        for ($i = 9; $i < 15; $i++) {
            self::reserve($modules, $reserved, 14 - $i, 8, self::bit($bits, $i));
        }

        for ($i = 0; $i < 8; $i++) {
            self::reserve($modules, $reserved, $size - 1 - $i, 8, self::bit($bits, $i));
        }
        for ($i = 8; $i < 15; $i++) {
            self::reserve($modules, $reserved, 8, $size - 15 + $i, self::bit($bits, $i));
        }
        self::reserve($modules, $reserved, 8, $size - 8, true);
    }

    /**
     * @param  list<list<bool>>  $modules
     * @param  list<list<bool>>  $reserved
     */
    private static function drawVersion(array &$modules, array &$reserved, int $size, int $version): void
    {
        if ($version < 7) {
            return;
        }

        $remainder = $version;
        for ($i = 0; $i < 12; $i++) {
            $remainder = ($remainder << 1) ^ ((($remainder >> 11) & 1) * 0x1F25);
        }
        $bits = (($version << 12) | $remainder) & 0x3FFFF;

        for ($i = 0; $i < 18; $i++) {
            $dark = self::bit($bits, $i);
            $a = $size - 11 + ($i % 3);
            $b = intdiv($i, 3);
            self::reserve($modules, $reserved, $a, $b, $dark);
            self::reserve($modules, $reserved, $b, $a, $dark);
        }
    }

    /**
     * @param  list<list<bool>>  $modules
     * @param  list<list<bool>>  $reserved
     */
    private static function applyMask(array &$modules, array $reserved, int $size, int $mask): void
    {
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($reserved[$y][$x]) {
                    continue;
                }

                $invert = match ($mask) {
                    0 => ($x + $y) % 2 === 0,
                    1 => $y % 2 === 0,
                    2 => $x % 3 === 0,
                    3 => ($x + $y) % 3 === 0,
                    4 => (intdiv($x, 3) + intdiv($y, 2)) % 2 === 0,
                    5 => (($x * $y) % 2) + (($x * $y) % 3) === 0,
                    6 => ((($x * $y) % 2) + (($x * $y) % 3)) % 2 === 0,
                    default => ((($x + $y) % 2) + (($x * $y) % 3)) % 2 === 0,
                };

                if ($invert) {
                    $modules[$y][$x] = ! $modules[$y][$x];
                }
            }
        }
    }

    /**
     * @param  list<list<bool>>  $modules
     */
    private static function penalty(array $modules, int $size): int
    {
        $score = 0;

        for ($y = 0; $y < $size; $y++) {
            $runColor = false;
            $runLength = 0;
            $history = array_fill(0, 7, 0);
            for ($x = 0; $x < $size; $x++) {
                if ($modules[$y][$x] === $runColor) {
                    $runLength++;
                    if ($runLength === 5) {
                        $score += self::PENALTY_N1;
                    } elseif ($runLength > 5) {
                        $score++;
                    }
                } else {
                    self::addRunHistory($history, $runLength, $size);
                    if (! $runColor) {
                        $score += self::countFinderLike($history) * self::PENALTY_N3;
                    }
                    $runColor = $modules[$y][$x];
                    $runLength = 1;
                }
            }
            $score += self::terminateRun($history, $runColor, $runLength, $size) * self::PENALTY_N3;
        }

        for ($x = 0; $x < $size; $x++) {
            $runColor = false;
            $runLength = 0;
            $history = array_fill(0, 7, 0);
            for ($y = 0; $y < $size; $y++) {
                if ($modules[$y][$x] === $runColor) {
                    $runLength++;
                    if ($runLength === 5) {
                        $score += self::PENALTY_N1;
                    } elseif ($runLength > 5) {
                        $score++;
                    }
                } else {
                    self::addRunHistory($history, $runLength, $size);
                    if (! $runColor) {
                        $score += self::countFinderLike($history) * self::PENALTY_N3;
                    }
                    $runColor = $modules[$y][$x];
                    $runLength = 1;
                }
            }
            $score += self::terminateRun($history, $runColor, $runLength, $size) * self::PENALTY_N3;
        }

        for ($y = 0; $y < $size - 1; $y++) {
            for ($x = 0; $x < $size - 1; $x++) {
                $color = $modules[$y][$x];
                if ($color === $modules[$y][$x + 1] && $color === $modules[$y + 1][$x] && $color === $modules[$y + 1][$x + 1]) {
                    $score += self::PENALTY_N2;
                }
            }
        }

        $dark = 0;
        foreach ($modules as $row) {
            foreach ($row as $module) {
                if ($module) {
                    $dark++;
                }
            }
        }
        $total = $size * $size;
        $k = intdiv(abs(($dark * 20) - ($total * 10)) + $total - 1, $total) - 1;

        return $score + ($k * self::PENALTY_N4);
    }

    /**
     * @param  list<int>  $history
     */
    private static function addRunHistory(array &$history, int $runLength, int $size): void
    {
        if ($history[0] === 0) {
            $runLength += $size;
        }

        array_pop($history);
        array_unshift($history, $runLength);
    }

    /**
     * @param  list<int>  $history
     */
    private static function terminateRun(array &$history, bool $runColor, int $runLength, int $size): int
    {
        if ($runColor) {
            self::addRunHistory($history, $runLength, $size);
            $runLength = 0;
        }
        self::addRunHistory($history, $runLength + $size, $size);

        return self::countFinderLike($history);
    }

    /**
     * Detect the 1:1:3:1:1 finder-like sequence that scanners can confuse with a real finder.
     *
     * @param  list<int>  $history
     */
    private static function countFinderLike(array $history): int
    {
        $n = $history[1];
        $core = $n > 0
            && $history[2] === $n
            && $history[3] === $n * 3
            && $history[4] === $n
            && $history[5] === $n;

        return ($core && $history[0] >= $n * 4 && $history[6] >= $n ? 1 : 0)
            + ($core && $history[6] >= $n * 4 && $history[0] >= $n ? 1 : 0);
    }

    /**
     * @return list<int>
     */
    private static function rsGenerator(int $degree): array
    {
        $result = array_fill(0, $degree, 0);
        $result[$degree - 1] = 1;

        $root = 1;
        for ($i = 0; $i < $degree; $i++) {
            for ($j = 0; $j < $degree; $j++) {
                $result[$j] = self::gfMultiply($result[$j], $root);
                if ($j + 1 < $degree) {
                    $result[$j] ^= $result[$j + 1];
                }
            }
            $root = self::gfMultiply($root, 0x02);
        }

        return $result;
    }

    /**
     * @param  list<int>  $data
     * @param  list<int>  $generator
     * @return list<int>
     */
    private static function rsRemainder(array $data, array $generator): array
    {
        $degree = count($generator);
        $result = array_fill(0, $degree, 0);

        foreach ($data as $byte) {
            $factor = $byte ^ (int) array_shift($result);
            $result[] = 0;
            for ($i = 0; $i < $degree; $i++) {
                $result[$i] ^= self::gfMultiply($generator[$i], $factor);
            }
        }

        return $result;
    }

    /** Multiply two field elements of GF(2^8) modulo x^8 + x^4 + x^3 + x^2 + 1. */
    private static function gfMultiply(int $x, int $y): int
    {
        $z = 0;
        for ($i = 7; $i >= 0; $i--) {
            $z = ($z << 1) ^ ((($z >> 7) & 1) * 0x11D);
            $z ^= ((($y >> $i) & 1) * $x);
        }

        return $z & 0xFF;
    }

    /**
     * @param  list<int>  $bits
     */
    private static function appendBits(array &$bits, int $value, int $length): void
    {
        for ($i = $length - 1; $i >= 0; $i--) {
            $bits[] = ($value >> $i) & 1;
        }
    }

    private static function bit(int $value, int $index): bool
    {
        return (($value >> $index) & 1) !== 0;
    }
}
