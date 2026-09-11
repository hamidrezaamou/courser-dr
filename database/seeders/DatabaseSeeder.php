<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::query()->firstOrCreate(
            ['national_code' => '1234567890'],
            [
                'name' => 'دکتر فتوحی',
                'mobile' => '09123456789',
                'role' => User::ROLE_DOCTOR,
                'password' => Hash::make('Demo@1234'),
            ]
        );
    }
}
