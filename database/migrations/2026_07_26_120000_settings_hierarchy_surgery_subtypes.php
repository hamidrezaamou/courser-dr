<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surgery_subtypes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_type_id')->constrained('surgery_types')->cascadeOnDelete();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['surgery_type_id', 'name']);
        });

        Schema::table('clinic_schedules', function (Blueprint $table) {
            $table->dropUnique(['kind', 'date_key', 'hospital_id']);

            $table->foreignId('surgery_type_id')
                ->nullable()
                ->after('hospital_id')
                ->constrained('surgery_types')
                ->nullOnDelete();

            $table->foreignId('surgery_subtype_id')
                ->nullable()
                ->after('surgery_type_id')
                ->constrained('surgery_subtypes')
                ->nullOnDelete();

            $table->unique(
                ['kind', 'date_key', 'hospital_id', 'surgery_type_id', 'surgery_subtype_id'],
                'clinic_schedules_scope_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('clinic_schedules', function (Blueprint $table) {
            $table->dropUnique('clinic_schedules_scope_unique');
            $table->dropConstrainedForeignId('surgery_subtype_id');
            $table->dropConstrainedForeignId('surgery_type_id');
            $table->unique(['kind', 'date_key', 'hospital_id']);
        });

        Schema::dropIfExists('surgery_subtypes');
    }
};
