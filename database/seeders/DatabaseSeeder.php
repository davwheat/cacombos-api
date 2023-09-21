<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\CapabilitySet;
use App\Models\DeviceFirmware;
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
        DB::table('tokens')->insert([
            [
                'token'         => 'admin',
                'comment'       => '',
                'expires_after' => now()->addDays(7),
                'created_at'    => now(),
                'updated_at'    => now(),
                'type'          => 'admin',
            ],
            [
                'token'         => 'admin-expired',
                'comment'       => '',
                'expires_after' => now()->addDays(-1),
                'created_at'    => now(),
                'updated_at'    => now(),
                'type'          => 'admin',
            ],
        ]);

        DB::table('modems')->insert([
            [
                'uuid'       => Uuid::uuid4(),
                'name'       => 'Shannon 5300',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'uuid'       => Uuid::uuid4(),
                'name'       => 'Shannon 5123b',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('devices')->insert([
            [
                'uuid'         => Uuid::uuid4(),
                'device_name'  => 'Pixel 6 Pro',
                'model_name'   => 'GLU0G',
                'manufacturer' => 'Google',
                'modem_id'     => 2,
                'release_date' => '2021-10-28',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'uuid'         => Uuid::uuid4(),
                'device_name'  => 'Pixel 7 Pro',
                'model_name'   => 'GP4BC',
                'manufacturer' => 'Google',
                'modem_id'     => 1,
                'release_date' => '2022-10-13',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);

        $p7pro_a13 = new DeviceFirmware();
        $p7pro_a13->device_id = 2;
        $p7pro_a13->name = 'Android 13';
        $p7pro_a13->save();

        $p7pro_a13_ee = new CapabilitySet();
        $p7pro_a13_ee->description = 'EE';
        $p7pro_a13_ee->plmn = '234-30';
        $p7pro_a13_ee->device_firmware()->associate($p7pro_a13);
        $p7pro_a13_ee->save();
    }
}
