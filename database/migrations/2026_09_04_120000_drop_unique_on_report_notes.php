<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_notes', function (Blueprint $table) {
            $table->dropUnique(['subject_type', 'subject_id']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::table('report_notes', function (Blueprint $table) {
            $table->dropIndex(['subject_type', 'subject_id']);
            $table->unique(['subject_type', 'subject_id']);
        });
    }
};
