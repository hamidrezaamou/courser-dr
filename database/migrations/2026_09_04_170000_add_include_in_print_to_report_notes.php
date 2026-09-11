<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('report_notes') || Schema::hasColumn('report_notes', 'include_in_print')) {
            return;
        }

        Schema::table('report_notes', function (Blueprint $table) {
            $table->boolean('include_in_print')->default(false)->after('body');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('report_notes', 'include_in_print')) {
            return;
        }

        Schema::table('report_notes', function (Blueprint $table) {
            $table->dropColumn('include_in_print');
        });
    }
};
