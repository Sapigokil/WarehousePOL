<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::firstOrCreate(
            ['key' => 'allow_double_login'],
            [
                'value' => '0', 
                'description' => '0 = Tolak Double Login (Single Session), 1 = Izinkan Double Login'
            ]
        );
    }
}