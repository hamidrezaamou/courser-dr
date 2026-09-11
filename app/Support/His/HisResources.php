<?php

namespace App\Support\His;

use InvalidArgumentException;

class HisResources
{
    /** @var array<string, class-string<HisImporter>> */
    private const MAP = [
        'patients' => PatientImporter::class,
        'appointments' => AppointmentImporter::class,
        'visits' => VisitImporter::class,
        'finance' => FinanceImporter::class,
    ];

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::MAP);
    }

    public static function exists(string $resource): bool
    {
        return isset(self::MAP[$resource]);
    }

    public static function importer(string $resource): HisImporter
    {
        if (! self::exists($resource)) {
            throw new InvalidArgumentException("Unknown HIS resource: {$resource}");
        }

        $class = self::MAP[$resource];

        return new $class();
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'patients' => 'بیماران',
            'appointments' => 'نوبت‌ها',
            'visits' => 'ویزیت‌ها',
            'finance' => 'مالی',
        ];
    }

    public static function label(string $resource): string
    {
        return self::labels()[$resource] ?? $resource;
    }
}
