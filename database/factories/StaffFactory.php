<?php

namespace Database\Factories;

use Database\Seeders\Support\MalaysianData;
use Illuminate\Database\Eloquent\Factories\Factory;

class StaffFactory extends Factory
{
    public function definition(): array
    {
        $position = fake()->randomElement(['Studio Technician', 'Equipment Manager', 'Front Desk Officer', 'Maintenance Officer']);

        return array_merge(MalaysianData::staffProfile($position), [
            'status'   => 'active',
            'is_admin' => false,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'position'  => 'Administrator',
            'is_admin'  => true,
        ]);
    }
}
