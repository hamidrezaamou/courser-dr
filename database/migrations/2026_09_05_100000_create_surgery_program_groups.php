<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('surgery_program_groups')) {
            Schema::create('surgery_program_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('surgery_program_group_items')) {
            Schema::create('surgery_program_group_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('surgery_program_group_id')->constrained('surgery_program_groups')->cascadeOnDelete();
                $table->unsignedBigInteger('surgery_type_id');
                $table->unsignedBigInteger('surgery_subtype_id')->nullable();
                $table->timestamps();
                $table->index(['surgery_type_id', 'surgery_subtype_id'], 'spgi_type_subtype_idx');
            });
        }

        if (! Schema::hasTable('surgery_program_group_days')) {
            Schema::create('surgery_program_group_days', function (Blueprint $table) {
                $table->id();
                $table->foreignId('surgery_program_group_id')->constrained('surgery_program_groups')->cascadeOnDelete();
                $table->unsignedBigInteger('hospital_id');
                $table->string('date_key', 20);
                $table->unsignedSmallInteger('total_slots')->default(10);
                $table->timestamps();
                $table->unique(
                    ['surgery_program_group_id', 'hospital_id', 'date_key'],
                    'spgd_group_hospital_date_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('surgery_program_group_days');
        Schema::dropIfExists('surgery_program_group_items');
        Schema::dropIfExists('surgery_program_groups');
    }
};
