<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('report_cards')) {
            return;
        }

        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('card_type', 20)->default('metric');
            $table->string('source', 30)->default('payments');
            $table->string('metric', 20)->default('total');
            $table->string('filter_service')->nullable();
            $table->string('filter_type')->nullable();
            $table->string('partner_name')->nullable();
            $table->unsignedTinyInteger('partner_percent')->default(0);
            $table->string('range_mode', 20)->default('this_month');
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->string('color', 9)->default('#4f86be');
            $table->string('text_color', 9)->default('#ffffff');
            $table->boolean('show_count')->default(true);
            $table->boolean('show_avg')->default(false);
            $table->boolean('show_total')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_cards');
    }
};
