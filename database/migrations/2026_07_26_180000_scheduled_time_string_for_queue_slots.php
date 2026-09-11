<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE appointments MODIFY scheduled_time VARCHAR(32) NULL');
            DB::statement('ALTER TABLE surgery_appointments MODIFY scheduled_time VARCHAR(32) NULL');

            return;
        }

        // SQLite / others: recreate is heavy; string affinity already flexible for SQLite.
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE appointments MODIFY scheduled_time TIME NULL');
            DB::statement('ALTER TABLE surgery_appointments MODIFY scheduled_time TIME NULL');
        }
    }
};
