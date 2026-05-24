<?php

namespace Database\Seeders;

use App\Models\EaConfiguration;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'username' => 'testuser',
                'password' => 'password',
                'is_admin' => false,
                'role' => 'user',
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Manager EA',
                'username' => 'manager',
                'password' => 'password',
                'is_admin' => false,
                'role' => 'manager',
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin EA',
                'username' => 'admin',
                'password' => 'password',
                'is_admin' => true,
                'role' => 'admin',
            ]
        );

        try {
            EaConfiguration::query()->firstOrCreate(
                ['account_id' => '12345678'],
                [
                    'user_id' => $user->id,
                    'max_layers' => 10,
                    'max_accumulative_lot' => 5.0,
                    'base_lot' => 0.01,
                    'target_tp_percentage' => 60.0,
                    'mart_type' => 0,
                    'mart_addition' => 0.01,
                    'mart_multiplier' => 2.0,
                    'grid_mode' => 1,
                    'fix_grid_distance' => 50,
                    'atr_multiplier' => 1.0,
                    'min_grid_distance' => 30,
                    'guard_status' => 'READY',
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            $this->command->warn('Skipped ea_configurations seed: ' . $e->getMessage());
        }
    }
}
