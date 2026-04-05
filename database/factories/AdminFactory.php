<?php

namespace SmartCms\Kit\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use SmartCms\Kit\Models\Admin;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        $username = $this->faker->unique()->userName();
        $email = $this->faker->unique()->safeEmail();

        return [
            'username' => $username,
            'email' => $email,
            'password' => bcrypt('password'), // Default password for testing
            'telegram_id' => $this->faker->optional()->numerify('##########'),
            'notifications' => [],
            'remember_token' => Str::random(10),
        ];
    }
}
