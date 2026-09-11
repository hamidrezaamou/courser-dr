<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff_note_notifications')) {
            return;
        }

        Schema::create('staff_note_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('actor_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('internal_note_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'internal_note_id'], 'staff_note_notif_user_note_unique');
            $table->index(['user_id', 'read_at', 'id'], 'staff_note_notif_inbox_idx');
            $table->index('internal_note_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_note_notifications');
    }
};
