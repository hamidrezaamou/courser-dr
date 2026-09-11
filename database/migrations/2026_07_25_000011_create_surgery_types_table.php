<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surgery_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            'آب مروارید (کاتاراکت)',
            'لیزیک / فمتو',
            'شبکیه',
            'گلوکوم',
            'پلک',
            'سایر',
        ];

        foreach ($defaults as $index => $name) {
            DB::table('surgery_types')->insert([
                'name' => $name,
                'is_active' => true,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('surgery_types');
    }
};
