<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ReportNote extends Model
{
    protected $fillable = [
        'subject_type',
        'subject_id',
        'patient_id',
        'created_by',
        'body',
        'include_in_print',
        'pinned',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'include_in_print' => 'boolean',
            'pinned' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public static function ensureIncludeInPrintColumn(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        if (Schema::hasColumn((new static)->getTable(), 'include_in_print')) {
            return;
        }

        try {
            Schema::table('report_notes', function (Blueprint $table) {
                $table->boolean('include_in_print')->default(false)->after('body');
            });
        } catch (\Throwable $e) {
            $ensured = false;
        }
    }

    public static function ensurePinColumns(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        $table = (new static)->getTable();

        try {
            if (! Schema::hasColumn($table, 'pinned')) {
                Schema::table('report_notes', function (Blueprint $table) {
                    $table->boolean('pinned')->default(false);
                });
            }
            if (! Schema::hasColumn($table, 'source')) {
                Schema::table('report_notes', function (Blueprint $table) {
                    $table->string('source', 32)->nullable();
                });
            }
        } catch (\Throwable $e) {
            $ensured = false;
        }
    }

    public static function relaxUniqueConstraint(): void
    {
        foreach (['report_notes_subject_type_subject_id_unique', ['subject_type', 'subject_id']] as $index) {
            try {
                Schema::table('report_notes', function (Blueprint $table) use ($index) {
                    $table->dropUnique($index);
                });
                break;
            } catch (\Throwable $e) {
                // already dropped or different index name
            }
        }

        try {
            Schema::table('report_notes', function (Blueprint $table) {
                $table->index(['subject_type', 'subject_id']);
            });
        } catch (\Throwable $e) {
            // index may already exist
        }
    }
}
