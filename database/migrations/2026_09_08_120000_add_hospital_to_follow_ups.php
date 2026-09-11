<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('follow_up_templates') && ! Schema::hasColumn('follow_up_templates', 'hospital_id')) {
            Schema::table('follow_up_templates', function (Blueprint $table) {
                $table->unsignedBigInteger('hospital_id')->nullable()->after('applies_to');
                $table->index(['hospital_id', 'applies_to', 'is_active'], 'follow_up_tpl_hospital_idx');
            });
        }

        if (Schema::hasTable('patient_follow_ups') && ! Schema::hasColumn('patient_follow_ups', 'hospital_id')) {
            Schema::table('patient_follow_ups', function (Blueprint $table) {
                $table->unsignedBigInteger('hospital_id')->nullable()->after('patient_id');
                $table->index(['hospital_id', 'status', 'due_at'], 'patient_follow_ups_hospital_idx');
            });
        }

        if (Schema::hasTable('patient_follow_ups') && Schema::hasTable('surgery_appointments')) {
            DB::table('patient_follow_ups')
                ->whereNull('hospital_id')
                ->whereNotNull('surgery_appointment_id')
                ->orderBy('id')
                ->chunkById(200, function ($followUps) {
                    $hospitalBySurgery = DB::table('surgery_appointments')
                        ->whereIn('id', $followUps->pluck('surgery_appointment_id')->filter()->unique())
                        ->pluck('hospital_id', 'id');

                    foreach ($followUps->groupBy(
                        fn ($followUp) => (int) ($hospitalBySurgery[$followUp->surgery_appointment_id] ?? 0)
                    ) as $hospitalId => $rows) {
                        if ($hospitalId < 1) {
                            continue;
                        }
                        DB::table('patient_follow_ups')
                            ->whereIn('id', $rows->pluck('id'))
                            ->update(['hospital_id' => $hospitalId]);
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('patient_follow_ups') && Schema::hasColumn('patient_follow_ups', 'hospital_id')) {
            Schema::table('patient_follow_ups', function (Blueprint $table) {
                $table->dropIndex('patient_follow_ups_hospital_idx');
                $table->dropColumn('hospital_id');
            });
        }

        if (Schema::hasTable('follow_up_templates') && Schema::hasColumn('follow_up_templates', 'hospital_id')) {
            Schema::table('follow_up_templates', function (Blueprint $table) {
                $table->dropIndex('follow_up_tpl_hospital_idx');
                $table->dropColumn('hospital_id');
            });
        }
    }
};
