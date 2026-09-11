<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('patients', 'mobile_secondary')) {
            return;
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->string('mobile_secondary', 20)->nullable()->after('mobile');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('patients', 'mobile_secondary')) {
            return;
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('mobile_secondary');
        });
    }
};
