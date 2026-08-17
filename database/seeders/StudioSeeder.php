<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Studio;

class StudioSeeder extends Seeder
{
    public function run(): void
    {
        $studios = [
            ['studio_name' => 'Music Studio',      'studio_type' => 'music',      'description' => 'Practice & recording sessions',   'status' => 'available'],
            ['studio_name' => 'Dance Studio',      'studio_type' => 'dance',      'description' => 'Choreography & performances',     'status' => 'available'],
            ['studio_name' => 'Gamelan Studio',     'studio_type' => 'gamelan',    'description' => 'Traditional gamelan practice',    'status' => 'available'],
            ['studio_name' => 'Caklempong Studio', 'studio_type' => 'caklempong', 'description' => 'Traditional caklempong sessions', 'status' => 'available'],
        ];

        foreach ($studios as $studio) {
            Studio::firstOrCreate(['studio_name' => $studio['studio_name']], $studio);
        }
    }
}
