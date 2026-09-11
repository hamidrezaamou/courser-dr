<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20); // visit | surgery
            $table->string('date_key', 20); // 1405/05/15
            $table->string('display_text')->nullable();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('day');
            $table->string('weekday', 20)->nullable();
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['kind', 'date_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_schedules');
    }
};
