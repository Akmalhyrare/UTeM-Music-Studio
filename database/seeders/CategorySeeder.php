<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['category_name' => 'Guitars',               'type' => 'equipment', 'description' => 'Acoustic, electric and bass guitars'],
            ['category_name' => 'Keyboards & Pianos',     'type' => 'equipment', 'description' => 'Digital pianos, synthesizers and MIDI controllers'],
            ['category_name' => 'Microphones',            'type' => 'equipment', 'description' => 'Vocal, instrument and recording microphones'],
            ['category_name' => 'Speakers & Amplifiers',  'type' => 'equipment', 'description' => 'PA speakers, monitors and amplifiers'],
            ['category_name' => 'Drums & Percussion',     'type' => 'equipment', 'description' => 'Drum kits, cajons and hand percussion'],
            ['category_name' => 'Cables & Accessories',   'type' => 'equipment', 'description' => 'Cables, stands, picks and other accessories'],
            ['category_name' => 'Traditional Instruments','type' => 'equipment', 'description' => 'Traditional Malaysian musical instruments'],
            ['category_name' => 'Traditional Costumes',   'type' => 'attire',    'description' => 'Costumes for traditional performances'],
            ['category_name' => 'Performance Uniforms',   'type' => 'attire',    'description' => 'Uniforms for band and choir performances'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['category_name' => $category['category_name']], $category);
        }
    }
}
