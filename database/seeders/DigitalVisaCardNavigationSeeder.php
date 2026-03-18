<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DigitalVisaCardNavigationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $existing = DB::table('user_navigations')
            ->where('url', 'user/digitalvisacards')
            ->first();

        $payload = [
            'icon' => 'credit-card',
            'url' => 'user/digitalvisacards',
            'type' => 'card',
            'name' => 'Digital VisaCard',
            'position' => 2,
            'translation' => null,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('user_navigations')
                ->where('url', 'user/digitalvisacards')
                ->update($payload);

            return;
        }

        DB::table('user_navigations')->insert($payload + [
            'created_at' => now(),
        ]);
    }
}


