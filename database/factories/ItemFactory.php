<?php

namespace Database\Factories;

use Database\Seeders\Support\MusicEquipmentData;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    public function definition(): array
    {
        $categoryName = fake()->randomElement(MusicEquipmentData::categories());
        [$itemName, , , $description] = MusicEquipmentData::nextEntry($categoryName);

        $quantity = fake()->numberBetween(2, 15);

        return [
            'item_name'          => $itemName,
            'item_description'   => $description,
            'quantity'           => $quantity,
            'available_quantity' => $quantity,
            'condition_status'   => MusicEquipmentData::condition(),
            'item_status'        => 'available',
            'date_added'         => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ];
    }
}
