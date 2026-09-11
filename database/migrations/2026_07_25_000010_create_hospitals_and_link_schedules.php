<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('clinic_schedules', function (Blueprint $table) {
            $table->dropUnique(['kind', 'date_key']);
            $table->foreignId('hospital_id')
                ->nullable()
                ->after('kind')
                ->constrained('hospitals')
                ->cascadeOnDelete();
            $table->unique(['kind', 'date_key', 'hospital_id']);
        });

        Schema::table('surgery_appointments', function (Blueprint $table) {
            $table->foreignId('hospital_id')
                ->nullable()
                ->after('patient_id')
                ->constrained('hospitals')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('surgery_appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hospital_id');
        });

        Schema::table('clinic_schedules', function (Blueprint $table) {
            $table->dropUnique(['kind', 'date_key', 'hospital_id']);
            $table->dropConstrainedForeignId('hospital_id');
            $table->unique(['kind', 'date_key']);
        });

        Schema::dropIfExists('hospitals');
    }
};
