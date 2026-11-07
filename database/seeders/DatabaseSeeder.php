<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
	        // ... các seeder trước
	        IndustrialZoneSeeder::class,
            LightingCabinetSeeder::class,
            // LightingCableSeeder::class,
	        SurroundingLocationSeeder::class,
	    ]);
    }
}
