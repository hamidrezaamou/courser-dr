<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_up_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date');
            $table->date('remind_at');
            $table->string('status')->default('pending'); // pending|sent|cancelled
            $table->text('message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'remind_at']);
            $table->index(['patient_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_up_reminders');
    }
};
