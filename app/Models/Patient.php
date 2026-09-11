<?php

namespace App\Models;

use App\Models\Concerns\HasHisSource;
use App\Support\PatientIdentitySync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Patient extends Model
{
    use HasHisSource;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'national_code',
        'mobile',
        'mobile_secondary',
        'age',
        'his_patient_id',
        'source',
        'his_synced_at',
        'photo_path',
    ];

    protected function casts(): array
    {
        return [
            'his_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (Patient $patient): void {
            if ($patient->wasChanged(['name', 'mobile', 'mobile_secondary', 'national_code', 'age'])) {
                PatientIdentitySync::syncFromPatient($patient);
            }
        });
    }

    /**
     * Get the visits for the patient.
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * Get the medical documents for the patient.
     */
    public function medicalDocuments(): HasMany
    {
        return $this->hasMany(MedicalDocument::class);
    }

    /**
     * Get the internal notes for the patient.
     */
    public function internalNotes(): HasMany
    {
        return $this->hasMany(InternalNote::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function surgeryAppointments(): HasMany
    {
        return $this->hasMany(SurgeryAppointment::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function followUpReminders(): HasMany
    {
        return $this->hasMany(FollowUpReminder::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(PatientFollowUp::class);
    }

    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(PatientConsent::class);
    }

    public function isSecureErased(): bool
    {
        return str_starts_with((string) $this->national_code, 'DEL-');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeActive($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('national_code')
                ->orWhere('national_code', 'not like', 'DEL-%');
        });
    }

    public static function ensureMobileSecondaryColumn(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        if (Schema::hasColumn((new static)->getTable(), 'mobile_secondary')) {
            return;
        }

        try {
            Schema::table('patients', function (Blueprint $table) {
                $table->string('mobile_secondary', 20)->nullable()->after('mobile');
            });
        } catch (\Throwable $e) {
            $ensured = false;
        }
    }

    public static function ensurePhotoColumn(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        try {
            $table = (new static)->getTable();
            if (! Schema::hasTable($table)) {
                return;
            }
            if (in_array('photo_path', Schema::getColumnListing($table), true)) {
                return;
            }
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('photo_path', 255)->nullable();
            });
        } catch (\Throwable $e) {
            $ensured = false;
        }
    }

    public function photoUrl(): ?string
    {
        $path = trim((string) ($this->getAttributes()['photo_path'] ?? ''));
        if ($path === '') {
            return null;
        }

        return asset('storage/'.$path);
    }

    public function photoInitial(): string
    {
        $name = trim((string) $this->name);

        return $name !== '' ? mb_substr($name, 0, 1) : '؟';
    }
}
