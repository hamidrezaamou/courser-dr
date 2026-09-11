<?php

namespace App\Support;

use App\Models\FollowUpCatalogItem;
use App\Models\FollowUpTemplate;
use App\Models\PatientFollowUp;
use App\Models\SurgeryAppointment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PatientFollowUps
{
    public static function isAvailable(): bool
    {
        return FeatureFlags::enabled('features.followup_reminders')
            && Schema::hasTable('patient_follow_ups')
            && Schema::hasTable('follow_up_templates');
    }

    public static function ensureTables(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        try {
            if (! Schema::hasTable('follow_up_catalog_items')) {
                Schema::create('follow_up_catalog_items', function (Blueprint $table) {
                    $table->id();
                    $table->string('group', 32);
                    $table->string('slug', 64);
                    $table->string('label', 120);
                    $table->unsignedSmallInteger('sort_order')->default(0);
                    $table->boolean('is_active')->default(true);
                    $table->timestamps();
                    $table->unique(['group', 'slug'], 'follow_up_catalog_group_slug_unique');
                });
            }

            if (! Schema::hasTable('follow_up_templates')) {
                Schema::create('follow_up_templates', function (Blueprint $table) {
                    $table->id();
                    $table->string('name', 160);
                    $table->text('description')->nullable();
                    $table->string('applies_to', 20)->default('surgery');
                    $table->unsignedBigInteger('hospital_id')->nullable();
                    $table->unsignedBigInteger('surgery_type_id')->nullable();
                    $table->unsignedBigInteger('surgery_subtype_id')->nullable();
                    $table->boolean('is_active')->default(true);
                    $table->unsignedBigInteger('created_by')->nullable();
                    $table->timestamps();
                    $table->index(['applies_to', 'is_active', 'surgery_type_id', 'surgery_subtype_id'], 'follow_up_tpl_lookup_idx');
                    $table->index(['hospital_id', 'applies_to', 'is_active'], 'follow_up_tpl_hospital_idx');
                });
            } elseif (! Schema::hasColumn('follow_up_templates', 'hospital_id')) {
                Schema::table('follow_up_templates', function (Blueprint $table) {
                    $table->unsignedBigInteger('hospital_id')->nullable()->after('applies_to');
                    $table->index(['hospital_id', 'applies_to', 'is_active'], 'follow_up_tpl_hospital_idx');
                });
            }

            if (! Schema::hasTable('follow_up_template_steps')) {
                Schema::create('follow_up_template_steps', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('template_id');
                    $table->string('title', 160);
                    $table->text('description')->nullable();
                    $table->string('kind', 64)->default('call');
                    $table->string('method', 64)->default('call');
                    $table->unsignedInteger('offset_amount')->default(0);
                    $table->string('offset_unit', 16)->default('day');
                    $table->string('offset_direction', 16)->default('after');
                    $table->string('reference_event', 32)->default('surgery_date');
                    $table->unsignedBigInteger('assigned_user_id')->nullable();
                    $table->unsignedSmallInteger('sort_order')->default(0);
                    $table->json('meta')->nullable();
                    $table->timestamps();
                    $table->index(['template_id', 'sort_order'], 'follow_up_step_tpl_sort_idx');
                });
            }

            if (! Schema::hasTable('patient_follow_ups')) {
                Schema::create('patient_follow_ups', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('patient_id');
                    $table->unsignedBigInteger('hospital_id')->nullable();
                    $table->nullableMorphs('subject');
                    $table->unsignedBigInteger('surgery_appointment_id')->nullable();
                    $table->unsignedBigInteger('appointment_id')->nullable();
                    $table->unsignedBigInteger('visit_id')->nullable();
                    $table->unsignedBigInteger('surgery_type_id')->nullable();
                    $table->unsignedBigInteger('surgery_subtype_id')->nullable();
                    $table->unsignedBigInteger('template_id')->nullable();
                    $table->unsignedBigInteger('template_step_id')->nullable();
                    $table->string('generation_key', 191)->nullable();
                    $table->unsignedBigInteger('parent_id')->nullable();
                    $table->string('title', 190);
                    $table->text('description')->nullable();
                    $table->text('notes')->nullable();
                    $table->string('kind', 64)->default('custom');
                    $table->string('method', 64)->default('call');
                    $table->string('reference_event', 32)->default('custom');
                    $table->dateTime('reference_at')->nullable();
                    $table->dateTime('due_at');
                    $table->string('status', 32)->default(FollowUpStatus::PENDING);
                    $table->string('outcome', 64)->nullable();
                    $table->text('outcome_notes')->nullable();
                    $table->unsignedBigInteger('assigned_to')->nullable();
                    $table->unsignedBigInteger('created_by')->nullable();
                    $table->unsignedBigInteger('completed_by')->nullable();
                    $table->timestamp('completed_at')->nullable();
                    $table->timestamp('cancelled_at')->nullable();
                    $table->string('cancel_reason', 190)->nullable();
                    $table->string('source', 20)->default(PatientFollowUp::SOURCE_MANUAL);
                    $table->json('meta')->nullable();
                    $table->timestamps();

                    $table->unique('generation_key', 'patient_follow_ups_generation_key_unique');
                    $table->index(['status', 'due_at']);
                    $table->index(['patient_id', 'due_at']);
                    $table->index(['hospital_id', 'status', 'due_at'], 'patient_follow_ups_hospital_idx');
                    $table->index(['assigned_to', 'status']);
                    $table->index(['surgery_appointment_id']);
                    $table->index(['appointment_id']);
                    $table->index(['parent_id']);
                });
            } elseif (! Schema::hasColumn('patient_follow_ups', 'hospital_id')) {
                Schema::table('patient_follow_ups', function (Blueprint $table) {
                    $table->unsignedBigInteger('hospital_id')->nullable()->after('patient_id');
                    $table->index(['hospital_id', 'status', 'due_at'], 'patient_follow_ups_hospital_idx');
                });
            }

            self::seedCatalogIfEmpty();
        } catch (\Throwable $e) {
            $ensured = false;
            report($e);
        }
    }

    public static function seedCatalogIfEmpty(): void
    {
        if (! Schema::hasTable('follow_up_catalog_items')) {
            return;
        }
        if (FollowUpCatalogItem::query()->exists()) {
            return;
        }

        $rows = [
            ['group' => FollowUpCatalogItem::GROUP_KIND, 'slug' => 'call', 'label' => 'تماس تلفنی', 'sort_order' => 10],
            ['group' => FollowUpCatalogItem::GROUP_KIND, 'slug' => 'sms', 'label' => 'ارسال پیامک', 'sort_order' => 20],
            ['group' => FollowUpCatalogItem::GROUP_KIND, 'slug' => 'reminder', 'label' => 'یادآوری', 'sort_order' => 30],
            ['group' => FollowUpCatalogItem::GROUP_KIND, 'slug' => 'documents', 'label' => 'بررسی مدارک', 'sort_order' => 40],
            ['group' => FollowUpCatalogItem::GROUP_KIND, 'slug' => 'status', 'label' => 'پیگیری وضعیت بیمار', 'sort_order' => 50],
            ['group' => FollowUpCatalogItem::GROUP_KIND, 'slug' => 'preop', 'label' => 'پیگیری قبل از عمل', 'sort_order' => 60],
            ['group' => FollowUpCatalogItem::GROUP_KIND, 'slug' => 'postop', 'label' => 'پیگیری بعد از عمل', 'sort_order' => 70],
            ['group' => FollowUpCatalogItem::GROUP_KIND, 'slug' => 'visit', 'label' => 'ویزیت', 'sort_order' => 80],
            ['group' => FollowUpCatalogItem::GROUP_KIND, 'slug' => 'in_person', 'label' => 'مراجعه', 'sort_order' => 90],
            ['group' => FollowUpCatalogItem::GROUP_KIND, 'slug' => 'custom', 'label' => 'پیگیری سفارشی', 'sort_order' => 100],

            ['group' => FollowUpCatalogItem::GROUP_METHOD, 'slug' => 'call', 'label' => 'تماس', 'sort_order' => 10],
            ['group' => FollowUpCatalogItem::GROUP_METHOD, 'slug' => 'sms', 'label' => 'پیامک', 'sort_order' => 20],
            ['group' => FollowUpCatalogItem::GROUP_METHOD, 'slug' => 'in_person', 'label' => 'مراجعه', 'sort_order' => 30],
            ['group' => FollowUpCatalogItem::GROUP_METHOD, 'slug' => 'visit', 'label' => 'ویزیت', 'sort_order' => 40],
            ['group' => FollowUpCatalogItem::GROUP_METHOD, 'slug' => 'in_app', 'label' => 'اعلان داخل سیستم', 'sort_order' => 50],
            ['group' => FollowUpCatalogItem::GROUP_METHOD, 'slug' => 'other', 'label' => 'سایر', 'sort_order' => 60],

            ['group' => FollowUpCatalogItem::GROUP_OUTCOME, 'slug' => 'answered', 'label' => 'بیمار پاسخ داد', 'sort_order' => 10],
            ['group' => FollowUpCatalogItem::GROUP_OUTCOME, 'slug' => 'no_answer', 'label' => 'بیمار پاسخ نداد', 'sort_order' => 20],
            ['group' => FollowUpCatalogItem::GROUP_OUTCOME, 'slug' => 'confirmed', 'label' => 'بیمار تأیید کرد', 'sort_order' => 30],
            ['group' => FollowUpCatalogItem::GROUP_OUTCOME, 'slug' => 'cancelled', 'label' => 'بیمار لغو کرد', 'sort_order' => 40],
            ['group' => FollowUpCatalogItem::GROUP_OUTCOME, 'slug' => 'reschedule', 'label' => 'بیمار درخواست تغییر زمان داد', 'sort_order' => 50],
            ['group' => FollowUpCatalogItem::GROUP_OUTCOME, 'slug' => 'retry', 'label' => 'نیاز به پیگیری مجدد', 'sort_order' => 60],
            ['group' => FollowUpCatalogItem::GROUP_OUTCOME, 'slug' => 'attended', 'label' => 'مراجعه انجام شد', 'sort_order' => 70],
            ['group' => FollowUpCatalogItem::GROUP_OUTCOME, 'slug' => 'visited', 'label' => 'ویزیت انجام شد', 'sort_order' => 80],
            ['group' => FollowUpCatalogItem::GROUP_OUTCOME, 'slug' => 'other', 'label' => 'سایر', 'sort_order' => 90],
        ];

        $now = now();
        foreach ($rows as $row) {
            FollowUpCatalogItem::query()->firstOrCreate(
                ['group' => $row['group'], 'slug' => $row['slug']],
                [
                    'label' => $row['label'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public static function findTemplateForSurgery(SurgeryAppointment $surgery): ?FollowUpTemplate
    {
        self::ensureTables();
        if (! Schema::hasTable('follow_up_templates')) {
            return null;
        }

        $typeId = $surgery->surgery_type_id ? (int) $surgery->surgery_type_id : null;
        $subtypeId = $surgery->surgery_subtype_id ? (int) $surgery->surgery_subtype_id : null;

        if (! $typeId && $surgery->surgery_type) {
            $type = \App\Models\SurgeryType::query()
                ->where('name', $surgery->surgery_type)
                ->first();
            $typeId = $type?->id;
        }

        if (! $typeId) {
            return null;
        }

        $base = FollowUpTemplate::query()
            ->with('steps')
            ->where('is_active', true)
            ->where('applies_to', FollowUpTemplate::APPLIES_SURGERY);

        $hospitalId = $surgery->hospital_id ? (int) $surgery->hospital_id : null;
        $hospitalScopes = $hospitalId ? [$hospitalId, null] : [null];
        $subtypeScopes = $subtypeId ? [$subtypeId, null] : [null];

        foreach ($hospitalScopes as $candidateHospitalId) {
            foreach ($subtypeScopes as $candidateSubtypeId) {
                $candidate = (clone $base)
                    ->where('surgery_type_id', $typeId)
                    ->when(
                        $candidateSubtypeId,
                        fn ($query) => $query->where('surgery_subtype_id', $candidateSubtypeId),
                        fn ($query) => $query->whereNull('surgery_subtype_id')
                    )
                    ->when(
                        $candidateHospitalId,
                        fn ($query) => $query->where('hospital_id', $candidateHospitalId),
                        fn ($query) => $query->whereNull('hospital_id')
                    )
                    ->orderBy('id')
                    ->first();

                if ($candidate && $candidate->steps->isNotEmpty()) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    public static function findTemplateForVisit(): ?FollowUpTemplate
    {
        self::ensureTables();
        if (! Schema::hasTable('follow_up_templates')) {
            return null;
        }

        return FollowUpTemplate::query()
            ->with('steps')
            ->where('is_active', true)
            ->where('applies_to', FollowUpTemplate::APPLIES_VISIT)
            ->whereNull('hospital_id')
            ->whereNull('surgery_type_id')
            ->whereNull('surgery_subtype_id')
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array<string, string>
     */
    public static function catalogMap(string $group): array
    {
        self::ensureTables();
        if (! Schema::hasTable('follow_up_catalog_items')) {
            return [];
        }

        return FollowUpCatalogItem::query()
            ->forGroup($group)
            ->active()
            ->ordered()
            ->pluck('label', 'slug')
            ->all();
    }

    public static function catalogLabel(string $group, ?string $slug): string
    {
        if (! $slug) {
            return '—';
        }
        $map = self::catalogMap($group);

        return $map[$slug] ?? $slug;
    }

    public static function assignedOpenCount(?int $userId): int
    {
        if (! $userId || ! self::isAvailable()) {
            return 0;
        }

        $today = now()->toDateString();

        return PatientFollowUp::query()
            ->open()
            ->where('assigned_to', $userId)
            ->whereDate('due_at', '<=', $today)
            ->count();
    }

    /**
     * @return array{today: int, upcoming: int, overdue: int, due: int}
     */
    public static function openCounts(?int $hospitalId = null): array
    {
        static $cached = [];
        $cacheKey = $hospitalId ?: 0;
        if (isset($cached[$cacheKey])) {
            return $cached[$cacheKey];
        }

        $empty = [
            'today' => 0,
            'upcoming' => 0,
            'overdue' => 0,
            'due' => 0,
        ];

        if (! self::isAvailable()) {
            return $cached[$cacheKey] = $empty;
        }

        $today = now()->toDateString();
        $base = PatientFollowUp::query()
            ->when($hospitalId, fn ($query) => $query->where('hospital_id', $hospitalId));
        $todayCount = (clone $base)->open()->whereDate('due_at', $today)->count();
        $upcomingCount = (clone $base)->open()->whereDate('due_at', '>', $today)->count();
        $overdueCount = (clone $base)->open()->whereDate('due_at', '<', $today)->count();

        return $cached[$cacheKey] = [
            'today' => $todayCount,
            'upcoming' => $upcomingCount,
            'overdue' => $overdueCount,
            'due' => $todayCount + $overdueCount,
        ];
    }

    public static function openDueCount(?int $hospitalId = null): int
    {
        return self::openCounts($hospitalId)['due'];
    }

    public static function openOverdueCount(?int $hospitalId = null): int
    {
        return self::openCounts($hospitalId)['overdue'];
    }

    public static function openTodayCount(?int $hospitalId = null): int
    {
        return self::openCounts($hospitalId)['today'];
    }

    public static function openUpcomingCount(?int $hospitalId = null): int
    {
        return self::openCounts($hospitalId)['upcoming'];
    }
}
