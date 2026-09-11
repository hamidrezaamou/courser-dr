<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('surgery_appointments')) {
            Schema::table('surgery_appointments', function (Blueprint $table) {
                if (! Schema::hasColumn('surgery_appointments', 'surgery_type_id')) {
                    $table->foreignId('surgery_type_id')
                        ->nullable()
                        ->after('surgery_type')
                        ->constrained('surgery_types')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('surgery_appointments', 'surgery_subtype_id')) {
                    $table->foreignId('surgery_subtype_id')
                        ->nullable()
                        ->after('surgery_type_id')
                        ->constrained('surgery_subtypes')
                        ->nullOnDelete();
                }
            });
        }

        if (! Schema::hasTable('surgery_checklist_templates')) {
            Schema::create('surgery_checklist_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('surgery_type_id')->constrained('surgery_types')->cascadeOnDelete();
                $table->foreignId('surgery_subtype_id')->nullable()->constrained('surgery_subtypes')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['surgery_type_id', 'surgery_subtype_id'], 'surgery_checklist_tpl_unique');
            });
        }

        if (! Schema::hasTable('surgery_checklist_template_items')) {
            Schema::create('surgery_checklist_template_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')->constrained('surgery_checklist_templates')->cascadeOnDelete();
                $table->string('label');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('patient_surgery_checklists')) {
            Schema::create('patient_surgery_checklists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->foreignId('surgery_appointment_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('surgery_type_id')->nullable()->constrained('surgery_types')->nullOnDelete();
                $table->foreignId('surgery_subtype_id')->nullable()->constrained('surgery_subtypes')->nullOnDelete();
                $table->string('title')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('saved_at')->nullable();
                $table->timestamps();

                $table->unique('surgery_appointment_id');
            });
        }

        if (! Schema::hasTable('patient_surgery_checklist_items')) {
            Schema::create('patient_surgery_checklist_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('checklist_id')->constrained('patient_surgery_checklists')->cascadeOnDelete();
                $table->string('label');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamp('checked_at')->nullable();
                $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_custom')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_surgery_checklist_items');
        Schema::dropIfExists('patient_surgery_checklists');
        Schema::dropIfExists('surgery_checklist_template_items');
        Schema::dropIfExists('surgery_checklist_templates');

        if (Schema::hasTable('surgery_appointments')) {
            Schema::table('surgery_appointments', function (Blueprint $table) {
                if (Schema::hasColumn('surgery_appointments', 'surgery_subtype_id')) {
                    $table->dropConstrainedForeignId('surgery_subtype_id');
                }
                if (Schema::hasColumn('surgery_appointments', 'surgery_type_id')) {
                    $table->dropConstrainedForeignId('surgery_type_id');
                }
            });
        }
    }
};
