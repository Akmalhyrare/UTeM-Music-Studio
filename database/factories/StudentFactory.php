<?php

namespace Database\Factories;

use Database\Seeders\Support\MalaysianData;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    public function definition(): array
    {
        return array_merge(MalaysianData::studentProfile(), [
            'status' => 'active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
