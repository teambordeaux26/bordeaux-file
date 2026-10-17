<?php

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\DocumentRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function retentionAdmin(): User
{
    return User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'name' => 'Office Administrator',
    ]);
}

function retentionSettingsPayload(array $overrides = []): array
{
    $settings = SystemSetting::current();

    return array_merge([
        'hotline' => $settings->hotline,
        'office_hours' => $settings->office_hours,
        'public_search_fields' => $settings->public_search_fields,
        'public_search_categories' => $settings->public_search_categories ?? [],
        'employee_pages' => $settings->employee_pages,
        'retention_periods' => $settings->retention_periods ?: DocumentRetentionService::defaultPeriods(),
        'official_signer_user_id' => $settings->official_signer_user_id,
    ], $overrides);
}

it('lets an administrator set archive retention periods in days or months', function () {
    $this->withoutVite();
    $admin = retentionAdmin();

    actingAs($admin)->get('/settings')->assertOk()->assertInertia(fn ($page) => $page
        ->component('Settings/Index')
        ->where('settings.retention_periods.0.amount', 7)
        ->where('settings.retention_periods.0.unit', 'days')
        ->where('settings.retention_periods.2.amount', 3)
        ->where('settings.retention_periods.2.unit', 'months'));

    actingAs($admin)->put('/settings', retentionSettingsPayload([
        'retention_periods' => [
            ['amount' => 14, 'unit' => 'days'],
            ['amount' => 3, 'unit' => 'months'],
        ],
    ]))->assertRedirect(route('settings.index'));

    $settings = SystemSetting::current()->fresh();

    expect($settings->retention_periods)->toEqual([
        ['amount' => 14, 'unit' => 'days'],
        ['amount' => 3, 'unit' => 'months'],
    ]);

    actingAs($admin)->get('/archive')->assertOk()->assertInertia(fn ($page) => $page
        ->component('Archive/Index')
        ->where('retentionOptions', [
            ['value' => 14, 'label' => '14 Days'],
            ['value' => 90, 'label' => '3 Months'],
        ]));

    actingAs($admin)->get('/documents/upload')->assertOk()->assertInertia(fn ($page) => $page
        ->component('Documents/Upload')
        ->where('retentionOptions.0.value', 14)
        ->where('retentionOptions.0.label', '14 Days')
        ->where('retentionOptions.1.value', 90)
        ->where('retentionOptions.1.label', '3 Months'));

    $category = DocumentCategory::query()->create([
        'name' => 'Memoranda',
        'description' => 'Office memoranda',
        'sort_order' => 1,
    ]);

    actingAs($admin)->post('/documents', [
        'title' => 'Custom retention memo',
        'category_id' => $category->id,
        'priority' => 'Standard',
        'retention_days' => 90,
    ])->assertRedirect(route('documents.index'));

    expect(Document::query()->where('title', 'Custom retention memo')->value('retention_days'))->toBe(90);

    actingAs($admin)->post('/documents', [
        'title' => 'Retired period',
        'category_id' => $category->id,
        'priority' => 'Standard',
        'retention_days' => 7,
    ])->assertSessionHasErrors('retention_days');
});

it('lets an administrator remove a retention period immediately', function () {
    $this->withoutVite();
    $admin = retentionAdmin();

    actingAs($admin)->put('/settings', retentionSettingsPayload([
        'retention_periods' => [
            ['amount' => 7, 'unit' => 'days'],
            ['amount' => 30, 'unit' => 'days'],
            ['amount' => 6, 'unit' => 'months'],
        ],
    ]))->assertRedirect();

    actingAs($admin)->put('/settings/retention', [
        'retention_periods' => [
            ['amount' => 7, 'unit' => 'days'],
            ['amount' => 6, 'unit' => 'months'],
        ],
    ])->assertRedirect();

    expect(SystemSetting::current()->fresh()->retention_periods)->toEqual([
        ['amount' => 7, 'unit' => 'days'],
        ['amount' => 6, 'unit' => 'months'],
    ]);

    actingAs($admin)->get('/settings')->assertOk()->assertInertia(fn ($page) => $page
        ->component('Settings/Index')
        ->has('settings.retention_periods', 2)
        ->where('settings.retention_periods.0.amount', 7)
        ->where('settings.retention_periods.1.amount', 6)
        ->where('settings.retention_periods.1.unit', 'months'));

    actingAs($admin)->get('/archive')->assertOk()->assertInertia(fn ($page) => $page
        ->where('retentionOptions', [
            ['value' => 7, 'label' => '7 Days'],
            ['value' => 180, 'label' => '6 Months'],
        ]));
});

it('keeps year-long month periods aligned with existing retention days', function () {
    expect(DocumentRetentionService::toDays(6, 'months'))->toBe(180)
        ->and(DocumentRetentionService::toDays(12, 'months'))->toBe(365)
        ->and(DocumentRetentionService::toDays(24, 'months'))->toBe(730)
        ->and(DocumentRetentionService::toDays(3, 'months'))->toBe(90);
});
