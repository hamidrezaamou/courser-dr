<?php

require __DIR__.'/../vendor/autoload.php';

use App\Support\QrCode;

$fail = 0;
$check = function (string $label, bool $ok) use (&$fail) {
    printf("%-46s %s\n", $label, $ok ? 'OK' : 'FAIL');
    if (! $ok) {
        $fail++;
    }
};

// ---------------------------------------------------------------------------
// 1. Reed-Solomon against the well-known "HELLO WORLD" 1-M reference vector.
// ---------------------------------------------------------------------------
$refData = [32, 91, 11, 120, 209, 114, 220, 77, 67, 64, 236, 17, 236, 17, 236, 17];
$refEcc = [196, 35, 39, 119, 235, 215, 231, 226, 93, 23];
$check('reed-solomon reference vector', QrCode::errorCorrection($refData, 10) === $refEcc);

// ---------------------------------------------------------------------------
// 2. Structural + round-trip decode for a range of payloads.
// ---------------------------------------------------------------------------

/** Rebuild the reserved-module map straight from the spec, independent of size. */
function functionMap(int $size): array
{
    $version = ($size - 17) / 4;
    $res = array_fill(0, $size, array_fill(0, $size, false));

    foreach ([[0, 0], [0, $size - 7], [$size - 7, 0]] as [$r0, $c0]) {
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                $rr = $r0 + $r;
                $cc = $c0 + $c;
                if ($rr >= 0 && $rr < $size && $cc >= 0 && $cc < $size) {
                    $res[$rr][$cc] = true;
                }
            }
        }
    }

    for ($i = 0; $i < $size; $i++) {
        $res[6][$i] = true;
        $res[$i][6] = true;
    }

    $centres = [1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30],
        6 => [6, 34], 7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46], 10 => [6, 28, 50]][$version];

    foreach ($centres as $r0) {
        foreach ($centres as $c0) {
            if (($r0 === 6 && $c0 === 6) || ($r0 === 6 && $c0 === $size - 7) || ($r0 === $size - 7 && $c0 === 6)) {
                continue;
            }
            for ($r = -2; $r <= 2; $r++) {
                for ($c = -2; $c <= 2; $c++) {
                    $res[$r0 + $r][$c0 + $c] = true;
                }
            }
        }
    }

    for ($i = 0; $i <= 8; $i++) {
        $res[8][$i] = true;
        $res[$i][8] = true;
    }
    for ($i = 0; $i < 8; $i++) {
        $res[8][$size - 1 - $i] = true;
        $res[$size - 1 - $i][8] = true;
    }
    $res[$size - 8][8] = true;

    if ($version >= 7) {
        for ($i = 0; $i < 18; $i++) {
            $a = $size - 11 + ($i % 3);
            $b = intdiv($i, 3);
            $res[$b][$a] = true;
            $res[$a][$b] = true;
        }
    }

    return $res;
}

/** Read the format information back out and return the mask number. */
function readMask(array $m, int $size): int
{
    $bits = 0;
    for ($i = 0; $i <= 5; $i++) {
        $bits |= ((int) $m[$i][8]) << $i;
    }
    $bits |= ((int) $m[7][8]) << 6;
    $bits |= ((int) $m[8][8]) << 7;
    $bits |= ((int) $m[8][7]) << 8;
    for ($i = 9; $i < 15; $i++) {
        $bits |= ((int) $m[8][14 - $i]) << $i;
    }

    $data = ($bits ^ 0x5412) >> 10;

    // Verify the BCH remainder so a placement bug cannot slip through.
    $rem = $data;
    for ($i = 0; $i < 10; $i++) {
        $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
    }
    if (((($data << 10) | $rem) ^ 0x5412) !== $bits) {
        throw new RuntimeException('format info BCH mismatch');
    }
    if (($data >> 3) !== 0b00) {
        throw new RuntimeException('unexpected EC level '.($data >> 3));
    }

    return $data & 0b111;
}

function maskFlip(int $mask, int $row, int $col): bool
{
    return match ($mask) {
        0 => ($row + $col) % 2 === 0,
        1 => $row % 2 === 0,
        2 => $col % 3 === 0,
        3 => ($row + $col) % 3 === 0,
        4 => (intdiv($row, 2) + intdiv($col, 3)) % 2 === 0,
        5 => (($row * $col) % 2) + (($row * $col) % 3) === 0,
        6 => ((($row * $col) % 2) + (($row * $col) % 3)) % 2 === 0,
        default => ((($row + $col) % 2) + (($row * $col) % 3)) % 2 === 0,
    };
}

/** Full decode: unmask, read the zigzag, de-interleave, verify RS, return the payload. */
function decode(array $m, int $size): string
{
    $version = (int) (($size - 17) / 4);
    $res = functionMap($size);
    $mask = readMask($m, $size);

    for ($r = 0; $r < $size; $r++) {
        for ($c = 0; $c < $size; $c++) {
            if (! $res[$r][$c] && maskFlip($mask, $r, $c)) {
                $m[$r][$c] = ! $m[$r][$c];
            }
        }
    }

    $bits = [];
    for ($right = $size - 1; $right >= 1; $right -= 2) {
        if ($right === 6) {
            $right = 5;
        }
        for ($v = 0; $v < $size; $v++) {
            for ($j = 0; $j < 2; $j++) {
                $col = $right - $j;
                $upward = (($right + 1) & 2) === 0;
                $row = $upward ? $size - 1 - $v : $v;
                if (! $res[$row][$col]) {
                    $bits[] = (int) $m[$row][$col];
                }
            }
        }
    }

    $stream = [];
    for ($i = 0; $i + 8 <= count($bits); $i += 8) {
        $b = 0;
        for ($j = 0; $j < 8; $j++) {
            $b = ($b << 1) | $bits[$i + $j];
        }
        $stream[] = $b;
    }

    $table = [1 => [10, 1, 16, 0, 0], 2 => [16, 1, 28, 0, 0], 3 => [26, 1, 44, 0, 0],
        4 => [18, 2, 32, 0, 0], 5 => [24, 2, 43, 0, 0], 6 => [16, 4, 27, 0, 0],
        7 => [18, 4, 31, 0, 0], 8 => [22, 2, 38, 2, 39], 9 => [22, 3, 36, 2, 37],
        10 => [26, 4, 43, 1, 44]];
    [$ecLen, $g1n, $g1d, $g2n, $g2d] = $table[$version];

    // Undo the interleave.
    $lengths = array_merge(array_fill(0, $g1n, $g1d), array_fill(0, $g2n, $g2d));
    $blocks = array_fill(0, count($lengths), []);
    $idx = 0;
    for ($i = 0; $i < max($g1d, $g2d); $i++) {
        foreach ($lengths as $b => $len) {
            if ($i < $len) {
                $blocks[$b][$i] = $stream[$idx++];
            }
        }
    }
    $eccBlocks = array_fill(0, count($lengths), []);
    for ($i = 0; $i < $ecLen; $i++) {
        foreach ($lengths as $b => $len) {
            $eccBlocks[$b][$i] = $stream[$idx++];
        }
    }

    foreach ($blocks as $b => $block) {
        if (QrCode::errorCorrection(array_values($block), $ecLen) !== array_values($eccBlocks[$b])) {
            throw new RuntimeException("ECC mismatch in block {$b}");
        }
    }

    $data = array_merge(...array_map('array_values', $blocks));

    // Parse the bit stream back into bytes.
    $flat = [];
    foreach ($data as $byte) {
        for ($i = 7; $i >= 0; $i--) {
            $flat[] = ($byte >> $i) & 1;
        }
    }

    $take = function (int $n) use (&$flat): int {
        $v = 0;
        for ($i = 0; $i < $n; $i++) {
            $v = ($v << 1) | array_shift($flat);
        }

        return $v;
    };

    if ($take(4) !== 0b0100) {
        throw new RuntimeException('mode indicator is not byte mode');
    }

    $length = $take($version <= 9 ? 8 : 16);
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= chr($take(8));
    }

    return $out;
}

$payloads = [
    'RX:1:P1',
    'https://clinic.example.com/verify/rx/1042?h=9f2ab1',
    'نسخهٔ الکترونیک',
    str_repeat('A', 14),   // exactly fills version 1
    str_repeat('B', 15),   // forces version 2
    str_repeat('C', 213),  // maximum supported payload
];

foreach ($payloads as $payload) {
    $m = QrCode::matrix($payload);
    $size = count($m);
    $label = mb_strimwidth($payload, 0, 26, '…');

    $structural = $m[0][0] && $m[0][6] && $m[6][0] && $m[6][6] && ! $m[7][7]
        && $m[0][$size - 1] && $m[$size - 1][0]
        && $m[$size - 8][8]
        && $m[6][8] === true && $m[6][9] === false;

    try {
        $decoded = decode($m, $size);
        $ok = $decoded === $payload;
    } catch (Throwable $e) {
        $decoded = 'ERR: '.$e->getMessage();
        $ok = false;
    }

    $check(sprintf('v%-2d %-30s', ($size - 17) / 4, $label), $structural && $ok);
    if (! $ok) {
        echo "    got: ".substr((string) $decoded, 0, 80)."\n";
    }
}

// ---------------------------------------------------------------------------
// 3. Eyeball the smallest one.
// ---------------------------------------------------------------------------
echo "\n";
$m = QrCode::matrix('RX:1:P1');
foreach ($m as $row) {
    $line = '';
    foreach ($row as $cell) {
        $line .= $cell ? '##' : '  ';
    }
    echo $line."\n";
}

echo "\n".($fail === 0 ? "ALL PASS\n" : "{$fail} FAILURE(S)\n");
