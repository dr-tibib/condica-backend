<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Backpack\Settings\app\Models\Setting;

class TenantSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::unguard();

        $settings = [
            [
                'key'         => 'shift_start',
                'name'        => 'Shift Start',
                'description' => 'Start time of the shift',
                'value'       => '08:00',
                'field'       => json_encode(['name' => 'value', 'label' => 'Value', 'type' => 'time']),
                'active'      => 1,
            ],
            [
                'key'         => 'shift_end',
                'name'        => 'Shift End',
                'description' => 'End time of the shift',
                'value'       => '17:00',
                'field'       => json_encode(['name' => 'value', 'label' => 'Value', 'type' => 'time']),
                'active'      => 1,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }

        Setting::reguard();
    }
}
