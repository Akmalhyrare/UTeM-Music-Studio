<?php

namespace Database\Seeders;

use Database\Seeders\Support\MusicEquipmentData;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Item;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        foreach (MusicEquipmentData::categories() as $categoryName) {
            $category = Category::where('category_name', $categoryName)->first();

            if (!$category) {
                continue;
            }

            foreach (MusicEquipmentData::itemsForCategory($categoryName) as [$itemName, , , $description]) {
                $quantity = fake()->numberBetween(3, 12);

                Item::firstOrCreate(
                    ['item_name' => $itemName],
                    [
                        'category_id'        => $category->category_id,
                        'item_description'   => $description,
                        'quantity'           => $quantity,
                        'available_quantity' => $quantity,
                        'condition_status'   => MusicEquipmentData::condition(),
                        'item_status'        => 'available',
                        'date_added'         => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                    ]
                );
            }
        }
    }
}
