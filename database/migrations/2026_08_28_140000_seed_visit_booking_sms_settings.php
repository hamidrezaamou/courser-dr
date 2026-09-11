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
            ['key' => 'reminders.sms.visit_on_booking', 'value' => '0'],
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
        //
    }
};
