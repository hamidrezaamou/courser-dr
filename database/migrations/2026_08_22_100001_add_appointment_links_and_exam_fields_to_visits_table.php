<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            if (! Schema::hasColumn('visits', 'appointment_id')) {
                $table->foreignId('appointment_id')
                    ->nullable()
                    ->after('patient_id')
                    ->constrained('appointments')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('visits', 'surgery_appointment_id')) {
                $table->foreignId('surgery_appointment_id')
                    ->nullable()
                    ->after('appointment_id')
                    ->constrained('surgery_appointments')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('visits', 'eye_side')) {
                $table->string('eye_side', 10)->nullable()->after('treatment');
            }
            if (! Schema::hasColumn('visits', 'va_right')) {
                $table->string('va_right', 50)->nullable()->after('eye_side');
            }
            if (! Schema::hasColumn('visits', 'va_left')) {
                $table->string('va_left', 50)->nullable()->after('va_right');
            }
            if (! Schema::hasColumn('visits', 'iop_right')) {
                $table->string('iop_right', 50)->nullable()->after('va_left');
            }
            if (! Schema::hasColumn('visits', 'iop_left')) {
                $table->string('iop_left', 50)->nullable()->after('iop_right');
            }
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            if (Schema::hasColumn('visits', 'appointment_id')) {
                $table->dropConstrainedForeignId('appointment_id');
            }
            if (Schema::hasColumn('visits', 'surgery_appointment_id')) {
                $table->dropConstrainedForeignId('surgery_appointment_id');
            }

            $drop = [];
            foreach (['eye_side', 'va_right', 'va_left', 'iop_right', 'iop_left'] as $col) {
                if (Schema::hasColumn('visits', $col)) {
                    $drop[] = $col;
                }
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
