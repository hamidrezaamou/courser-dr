<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plumbing for importing data from the clinic's Bina HIS.
 *
 * Nothing existing is rewritten: each domain table only gains a nullable
 * external id plus a `source` marker, so rows entered by hand keep behaving
 * exactly as before.
 */
return new class extends Migration
{
    /** table => external id column */
    private const EXTERNAL_IDS = [
        'patients' => 'his_patient_id',
        'appointments' => 'his_appointment_id',
        'surgery_appointments' => 'his_surgery_id',
        'visits' => 'his_admission_id',
        'financial_transactions' => 'his_transaction_id',
    ];

    public function up(): void
    {
        foreach (self::EXTERNAL_IDS as $table => $column) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $column) {
                if (! Schema::hasColumn($table, $column)) {
                    $blueprint->string($column, 64)->nullable()->unique();
                }
                if (! Schema::hasColumn($table, 'source')) {
                    $blueprint->string('source', 20)->default('manual')->index();
                }
                if (! Schema::hasColumn($table, 'his_synced_at')) {
                    $blueprint->timestamp('his_synced_at')->nullable();
                }
            });
        }

        if (! Schema::hasTable('his_sync_states')) {
            Schema::create('his_sync_states', function (Blueprint $table) {
                $table->id();
                $table->string('resource', 40)->unique();

                // Two watermarks, because not every HIS table has a change column.
                $table->string('last_id', 64)->nullable();
                $table->dateTime('last_changed_at')->nullable();

                $table->dateTime('last_run_at')->nullable();
                $table->dateTime('last_success_at')->nullable();
                $table->string('last_status', 20)->default('idle');
                $table->text('last_message')->nullable();
                $table->unsignedBigInteger('rows_imported')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('his_sync_logs')) {
            Schema::create('his_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->string('resource', 40)->index();
                $table->string('batch_id', 64)->index();
                $table->unsignedInteger('received')->default(0);
                $table->unsignedInteger('created')->default(0);
                $table->unsignedInteger('updated')->default(0);
                $table->unsignedInteger('skipped')->default(0);
                $table->unsignedInteger('failed')->default(0);
                $table->unsignedInteger('duration_ms')->default(0);
                $table->json('errors')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('his_sync_logs');
        Schema::dropIfExists('his_sync_states');

        foreach (self::EXTERNAL_IDS as $table => $column) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $column) {
                foreach ([$column, 'source', 'his_synced_at'] as $name) {
                    if (Schema::hasColumn($table, $name)) {
                        $blueprint->dropColumn($name);
                    }
                }
            });
        }
    }
};
