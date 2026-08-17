<?php

namespace Database\Seeders;

use Database\Seeders\Support\MalaysianData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            ItemSeeder::class,
            StaffSeeder::class,
            StudentSeeder::class,
            StudioSeeder::class,
            BookingSeeder::class,
            BorrowingSeeder::class,
            BookingHistorySeeder::class,
            BorrowingHistorySeeder::class,
        ]);

        // Write the dev-only plaintext/hash credentials reference (storage/app/private/dev-credentials.md)
        MalaysianData::writeCredentialsLog();
    }
}
