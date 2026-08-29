<?php

namespace Database\Seeders;

use App\Models\DocumentCategory;
use App\Models\RequestType;
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

        DocumentCategory::syncCatalog();
        RequestType::syncDefaults();
    }
}
