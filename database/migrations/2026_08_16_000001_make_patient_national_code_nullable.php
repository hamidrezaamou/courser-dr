<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE patients MODIFY national_code VARCHAR(20) NULL');
            DB::statement('ALTER TABLE appointments MODIFY national_code VARCHAR(255) NULL');
            DB::statement('ALTER TABLE surgery_appointments MODIFY national_code VARCHAR(255) NULL');

            return;
        }

        if ($driver === 'sqlite') {
            // SQLite ignores strict nullability in many setups; no-op is acceptable for local tests.
            return;
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("UPDATE patients SET national_code = '' WHERE national_code IS NULL");
            DB::statement("UPDATE appointments SET national_code = '' WHERE national_code IS NULL");
            DB::statement("UPDATE surgery_appointments SET national_code = '' WHERE national_code IS NULL");
            DB::statement('ALTER TABLE patients MODIFY national_code VARCHAR(20) NOT NULL');
            DB::statement('ALTER TABLE appointments MODIFY national_code VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE surgery_appointments MODIFY national_code VARCHAR(255) NOT NULL');
        }
    }
};
