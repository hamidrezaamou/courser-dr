<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_DOCTOR = 'doctor';

    public const ROLE_ASSISTANT = 'assistant';

    public const ROLE_PATIENT = 'patient';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'national_code',
        'mobile',
        'role',
        'password',
        'dashboard_layout',
        'toolbox_prefs',
        'photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dashboard_layout' => 'array',
            'toolbox_prefs' => 'array',
        ];
    }

    /**
     * Get the internal notes written by the user.
     */
    public function internalNotes(): HasMany
    {
        return $this->hasMany(InternalNote::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isDoctor(): bool
    {
        return $this->role === self::ROLE_DOCTOR;
    }

    public function isAssistant(): bool
    {
        return $this->role === self::ROLE_ASSISTANT;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_DOCTOR, self::ROLE_ASSISTANT], true);
    }

    /** تایم‌ها و بیمارستان‌ها */
    public function canManageSchedule(): bool
    {
        return $this->isAdmin() || $this->isDoctor();
    }

    /** تایم‌ها، بیمارستان، دارو و انواع عمل */
    public function canAccessClinicSettings(): bool
    {
        return $this->isStaff();
    }

    /** مدیریت کل سایت: کاربران، ارتباطات، ممیزی، … */
    public function canManageSettings(): bool
    {
        return $this->isAdmin() || $this->isDoctor();
    }

    /** ماژول‌های پیشرفته (منشی ندارد) */
    public function canAccessModules(): bool
    {
        return $this->isAdmin() || $this->isDoctor();
    }

    /** معاینه، ویس، نقاشی، یادداشت داخلی */
    public function canManageClinical(): bool
    {
        return $this->isAdmin() || $this->isDoctor();
    }

    /** نوبت ویزیت/عمل و تخته روزانه */
    public function canManageAppointments(): bool
    {
        return $this->isStaff();
    }

    /** ویرایش مشخصات پرونده بیمار (ادمین، پزشک، منشی) */
    public function canEditPatient(): bool
    {
        return $this->isStaff();
    }

    /** گزارش و بایگانی */
    public function canViewReports(): bool
    {
        return $this->isStaff();
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
