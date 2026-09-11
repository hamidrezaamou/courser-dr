<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('report_notes')) {
            return;
        }

        if (! Schema::hasColumn('report_notes', 'pinned')) {
            Schema::table('report_notes', function (Blueprint $table) {
                $table->boolean('pinned')->default(false);
            });
        }
        if (! Schema::hasColumn('report_notes', 'source')) {
            Schema::table('report_notes', function (Blueprint $table) {
                $table->string('source', 32)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('report_notes')) {
            return;
        }

        Schema::table('report_notes', function (Blueprint $table) {
            if (Schema::hasColumn('report_notes', 'source')) {
                $table->dropColumn('source');
            }
            if (Schema::hasColumn('report_notes', 'pinned')) {
                $table->dropColumn('pinned');
            }
        });
    }
};
