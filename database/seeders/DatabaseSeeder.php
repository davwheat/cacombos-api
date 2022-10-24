<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table('modems')->insert([
            [
                'uuid' => Uuid::uuid4(),
                'modem_name' => 'Shannon 5300',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'uuid' => Uuid::uuid4(),
                'modem_name' => 'Shannon 5123b',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        DB::table('devices')->insert([
            [
                'uuid' => Uuid::uuid4(),
                'device_name' => 'Pixel 6 Pro',
                'model_name' => 'GLU0G',
                'manufacturer' => 'Google',
                'modem_id' => 2,
                'release_date' => '2021-10-28',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Uuid::uuid4(),
                'device_name' => 'Pixel 7 Pro',
                'model_name' => 'GP4BC',
                'manufacturer' => 'Google',
                'modem_id' => 1,
                'release_date' => '2022-10-13',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
