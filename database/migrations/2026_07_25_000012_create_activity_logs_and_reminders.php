<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('status');
        });

        Schema::table('surgery_appointments', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('surgery_appointments', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });

        Schema::dropIfExists('activity_logs');
    }
};
