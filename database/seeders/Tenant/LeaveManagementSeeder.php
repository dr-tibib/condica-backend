<?php

namespace Database\Seeders\Tenant;

use App\Models\LeaveType;
use App\Models\PublicHoliday;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class LeaveManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Leave Types
        $types = [
            [
                'name' => 'Concediu Odihnă',
                'is_paid' => true,
                'requires_document' => false,
                'affects_annual_quota' => true,
                'medical_code_required' => false,
            ],
            [
                'name' => 'Concediu Medical',
                'is_paid' => true,
                'requires_document' => true,
                'affects_annual_quota' => false,
                'medical_code_required' => true,
            ],
            [
                'name' => 'Concediu Fără Plată',
                'is_paid' => false,
                'requires_document' => false,
                'affects_annual_quota' => false,
                'medical_code_required' => false,
            ],
            [
                'name' => 'Telemuncă',
                'is_paid' => true,
                'requires_document' => false,
                'affects_annual_quota' => false,
                'medical_code_required' => false,
            ],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(['name' => $type['name']], $type);
        }

        // Seed Public Holidays (2025 - 2026)
        $holidays = [
            // 2025
            ['date' => '2025-01-01', 'description' => 'Anul Nou'],
            ['date' => '2025-01-02', 'description' => 'Anul Nou'],
            ['date' => '2025-01-06', 'description' => 'Boboteaza'],
            ['date' => '2025-01-07', 'description' => 'Sfântul Ioan Botezătorul'],
            ['date' => '2025-01-24', 'description' => 'Ziua Unirii Principatelor Române'],
            ['date' => '2025-04-18', 'description' => 'Vinerea Mare'],
            ['date' => '2025-04-20', 'description' => 'Paștele Ortodox'],
            ['date' => '2025-04-21', 'description' => 'Paștele Ortodox'],
            ['date' => '2025-05-01', 'description' => 'Ziua Muncii'],
            ['date' => '2025-06-01', 'description' => 'Ziua Copilului'],
            ['date' => '2025-06-08', 'description' => 'Rusalii'],
            ['date' => '2025-06-09', 'description' => 'Rusalii'],
            ['date' => '2025-08-15', 'description' => 'Adormirea Maicii Domnului'],
            ['date' => '2025-11-30', 'description' => 'Sfântul Andrei'],
            ['date' => '2025-12-01', 'description' => 'Ziua Națională a României'],
            ['date' => '2025-12-25', 'description' => 'Crăciunul'],
            ['date' => '2025-12-26', 'description' => 'Crăciunul'],

            // 2026
            ['date' => '2026-01-01', 'description' => 'Anul Nou'],
            ['date' => '2026-01-02', 'description' => 'Anul Nou'],
            ['date' => '2026-01-06', 'description' => 'Boboteaza'],
            ['date' => '2026-01-07', 'description' => 'Sfântul Ioan Botezătorul'],
            ['date' => '2026-01-24', 'description' => 'Ziua Unirii Principatelor Române'],
            ['date' => '2026-04-10', 'description' => 'Vinerea Mare'],
            ['date' => '2026-04-12', 'description' => 'Paștele Ortodox'],
            ['date' => '2026-04-13', 'description' => 'Paștele Ortodox'],
            ['date' => '2026-05-01', 'description' => 'Ziua Muncii'],
            ['date' => '2026-06-01', 'description' => 'Ziua Copilului / Rusalii'],
            ['date' => '2026-05-31', 'description' => 'Rusalii'],
            ['date' => '2026-08-15', 'description' => 'Adormirea Maicii Domnului'],
            ['date' => '2026-11-30', 'description' => 'Sfântul Andrei'],
            ['date' => '2026-12-01', 'description' => 'Ziua Națională a României'],
            ['date' => '2026-12-25', 'description' => 'Crăciunul'],
            ['date' => '2026-12-26', 'description' => 'Crăciunul'],
        ];

        foreach ($holidays as $holiday) {
            PublicHoliday::firstOrCreate(
                ['date' => Carbon::parse($holiday['date'])->startOfDay()],
                $holiday
            );
        }
    }
}
