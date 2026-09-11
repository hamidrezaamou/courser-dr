<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surgery_appointments', function (Blueprint $table) {
            $table->boolean('is_exception')->default(false)->after('status');
            $table->boolean('is_emergency')->default(false)->after('is_exception');
            $table->string('mobile_secondary')->nullable()->after('mobile');
        });
    }

    public function down(): void
    {
        Schema::table('surgery_appointments', function (Blueprint $table) {
            $table->dropColumn(['is_exception', 'is_emergency', 'mobile_secondary']);
        });
    }
};
