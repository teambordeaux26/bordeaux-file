<?php

namespace Database\Seeders;

use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Office Administrator',
            'email' => 'admin@oas-dms.local',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'department' => 'Administration',
            'position' => 'Office Administrator',
            'status' => 'active',
        ]);

        User::factory()->create([
            'name' => 'Head Staff',
            'email' => 'staff@oas-dms.local',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'department' => 'Operations',
            'position' => 'Head Staff',
            'status' => 'active',
        ]);

        DocumentCategory::query()->insert([
            ['name' => 'Memoranda', 'description' => 'Internal memoranda and advisories.'],
            ['name' => 'Resolutions', 'description' => 'Official resolutions and approvals.'],
            ['name' => 'Reports', 'description' => 'Reports and summaries from departments.'],
            ['name' => 'Administrative Records', 'description' => 'Administrative records and files.'],
        ]);
    }
}
