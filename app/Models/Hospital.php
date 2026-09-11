<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hospital extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'print_note',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(ClinicSchedule::class);
    }

    public function surgeryAppointments(): HasMany
    {
        return $this->hasMany(SurgeryAppointment::class);
    }
}
