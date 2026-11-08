<?php

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the in-app access error instead of a raw permission page', function () {
    $this->withoutVite();

    $settings = SystemSetting::current();
    $settings->update(['employee_pages' => ['dashboard', 'workflow']]);

    $employee = User::factory()->create([
        'role' => 'employee',
        'status' => 'active',
    ]);

    $this->actingAs($employee)
        ->get(route('documents.returned'))
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page
            ->component('Errors/Show')
            ->where('status', 403)
            ->where('message', 'You do not have permission to access this page.')
        );
});

it('keeps an in-app visit on the previous page and reports the denial as a flash error', function () {
    $this->withoutVite();

    $settings = SystemSetting::current();
    $settings->update(['employee_pages' => ['dashboard', 'workflow']]);

    $employee = User::factory()->create([
        'role' => 'employee',
        'status' => 'active',
    ]);

    $version = file_exists(public_path('build/manifest.json'))
        ? hash_file('xxh128', public_path('build/manifest.json'))
        : '';

    $this->actingAs($employee)
        ->from(route('dashboard'))
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
            'X-Requested-With' => 'XMLHttpRequest',
        ])
        ->get(route('documents.returned'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error', 'You do not have permission to access this page.');
});

it('treats the older tracking permission as workflow access', function () {
    $this->withoutVite();

    $settings = SystemSetting::current();
    $settings->update(['employee_pages' => ['dashboard', 'tracking']]);

    $employee = User::factory()->create([
        'role' => 'employee',
        'status' => 'active',
    ]);

    $this->actingAs($employee)
        ->get(route('workflow.index'))
        ->assertOk();
});
