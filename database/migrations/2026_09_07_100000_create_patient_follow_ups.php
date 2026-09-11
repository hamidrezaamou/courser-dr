<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        \App\Support\PatientFollowUps::ensureTables();
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_follow_ups');
        Schema::dropIfExists('follow_up_template_steps');
        Schema::dropIfExists('follow_up_templates');
        Schema::dropIfExists('follow_up_catalog_items');
    }
};
