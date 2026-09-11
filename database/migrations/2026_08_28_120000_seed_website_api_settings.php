<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $now = now();
        $rows = [
            ['key' => 'services.website_api.key', 'value' => 'dr-website-mramo-2026'],
            ['key' => 'services.website_api.clinic_label', 'value' => 'مطب شخصی (ساختمان سان)'],
        ];

        foreach ($rows as $row) {
            if (DB::table('site_settings')->where('key', $row['key'])->exists()) {
                continue;
            }

            DB::table('site_settings')->insert([
                'key' => $row['key'],
                'value' => $row['value'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Keep website API settings on rollback — removing them would break public booking.
    }
};
