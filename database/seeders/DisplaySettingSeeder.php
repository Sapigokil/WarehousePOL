<?php

namespace Database\Seeders;

use App\Models\DisplaySetting;
use Illuminate\Database\Seeder;

class DisplaySettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaultSettings = [
            'refresh_interval' => '30',   // Detik untuk reload data via AJAX
            'auto_slide'       => '1',    // 1 = Aktif, 0 = Nonaktif
            'slide_interval'    => '10',   // Detik perpindahan halaman/slide
            'items_per_page'   => '8',    // Jumlah kartu per halaman monitor
        ];

        foreach ($defaultSettings as $key => $value) {
            DisplaySetting::setValue($key, $value);
        }
    }
}