<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('financial_transactions')) {
            Schema::create('financial_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->string('type', 20);
                $table->unsignedBigInteger('amount');
                $table->string('method', 40)->nullable();
                $table->string('label');
                $table->nullableMorphs('reference');
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('transaction_date');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('service_tariffs')) {
            Schema::create('service_tariffs', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('kind', 20);
                $table->unsignedBigInteger('amount');
                $table->unsignedTinyInteger('insurance_coverage')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('billing_records')) {
            Schema::create('billing_records', function (Blueprint $table) {
                $table->id();
                $table->morphs('billable');
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->foreignId('service_tariff_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedBigInteger('fee_amount');
                $table->unsignedBigInteger('insurance_share')->default(0);
                $table->unsignedBigInteger('patient_share');
                $table->string('settlement_status', 20)->default('open');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('consent_templates')) {
            Schema::create('consent_templates', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('kind', 20);
                $table->text('body');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('patient_consents')) {
            Schema::create('patient_consents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->foreignId('consent_template_id')->constrained()->cascadeOnDelete();
                $table->nullableMorphs('subject');
                $table->timestamp('signed_at')->nullable();
                $table->string('signed_by_name')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('waiting_list_entries')) {
            Schema::create('waiting_list_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
                $table->string('patient_name');
                $table->string('mobile', 20);
                $table->string('national_code', 20)->nullable();
                $table->string('kind', 20);
                $table->date('preferred_date')->nullable();
                $table->string('status', 20)->default('waiting');
                $table->unsignedSmallInteger('priority')->default(50);
                $table->text('notes')->nullable();
                $table->nullableMorphs('converted_to');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('waiting_list_entries');
        Schema::dropIfExists('patient_consents');
        Schema::dropIfExists('consent_templates');
        Schema::dropIfExists('billing_records');
        Schema::dropIfExists('service_tariffs');
        Schema::dropIfExists('financial_transactions');
    }
};
