<?php

namespace App\Support;

class EyeSide
{
    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return [
            ['value' => 'OD', 'label' => 'راست (OD)'],
            ['value' => 'OS', 'label' => 'چپ (OS)'],
            ['value' => 'OU', 'label' => 'هر دو (OU)'],
        ];
    }

    public static function normalize(?string $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $map = [
            'OD' => 'OD',
            'OS' => 'OS',
            'OU' => 'OU',
            'راست' => 'OD',
            'چپ' => 'OS',
            'دو طرفه' => 'OU',
            'دوطرفه' => 'OU',
            'هر دو' => 'OU',
            'right' => 'OD',
            'left' => 'OS',
            'both' => 'OU',
        ];

        $upper = strtoupper($raw);

        return $map[$raw] ?? $map[$upper] ?? $upper;
    }

    public static function label(?string $value): string
    {
        $normalized = self::normalize($value);
        foreach (self::options() as $option) {
            if ($option['value'] === $normalized) {
                return $option['label'];
            }
        }

        return $value !== null && $value !== '' ? $value : '—';
    }

    public static function overlaps(?string $left, ?string $right): bool
    {
        $a = self::normalize($left);
        $b = self::normalize($right);
        if ($a === null || $b === null) {
            return false;
        }
        if ($a === 'OU' || $b === 'OU') {
            return true;
        }

        return $a === $b;
    }
}
