<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfilePhoto
{
    public const MAX_EDGE = 900;

    public const QUALITY = 70;

    public static function store(Model $model, UploadedFile $file, string $directory): string
    {
        $old = (string) ($model->getAttribute('photo_path') ?? '');
        $path = ImageCompressor::storeUploaded($file, $directory, 'public', self::MAX_EDGE, self::QUALITY);
        $model->update(['photo_path' => $path]);
        if ($old !== '' && $old !== $path) {
            Storage::disk('public')->delete($old);
        }

        return $path;
    }

    public static function destroy(Model $model): void
    {
        $old = (string) ($model->getAttribute('photo_path') ?? '');
        $model->update(['photo_path' => null]);
        if ($old !== '') {
            Storage::disk('public')->delete($old);
        }
    }
}
