<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surgery_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('patient_name');
            $table->string('national_code');
            $table->string('mobile');
            $table->string('age')->nullable();
            $table->string('surgery_type');
            $table->string('eye_side')->nullable();
            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();
            $table->string('surgeon_name')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surgery_appointments');
    }
};
