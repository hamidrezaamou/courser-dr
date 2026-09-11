<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'national_code' => fake()->unique()->numerify('##########'),
            'mobile' => '09'.fake()->unique()->numerify('#########'),
            'role' => User::ROLE_DOCTOR,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_ADMIN]);
    }

    public function assistant(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_ASSISTANT]);
    }

    public function patient(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_PATIENT]);
    }
}
