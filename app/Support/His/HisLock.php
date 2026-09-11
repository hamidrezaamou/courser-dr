<?php

namespace App\Support\His;

use Illuminate\Database\Eloquent\Model;

class HisLock
{
    public const MESSAGE = 'این رکورد از نرم‌افزار مطب (HIS) می‌آید و باید همان‌جا ویرایش شود. تغییر اینجا در همگام‌سازی بعدی از بین می‌رود.';

    /**
     * Stop an edit to a record the HIS owns.
     *
     * Refusing is kinder than allowing it: the next sync would overwrite the
     * change within minutes and nobody would be told the edit was lost.
     */
    public static function guard(?Model $model): void
    {
        if ($model === null) {
            return;
        }

        if (($model->source ?? 'manual') === 'his') {
            abort(403, self::MESSAGE);
        }
    }
}
