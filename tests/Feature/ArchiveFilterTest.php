<?php

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function archivedDocument(User $owner, DocumentCategory $category, array $overrides = []): Document
{
    return Document::query()->create(array_merge([
        'tracking_number' => 'DMS-'.fake()->unique()->numerify('####'),
        'title' => 'Archived memo',
        'category_id' => $category->id,
        'status' => 'archived',
        'priority' => 'Standard',
        'retention_days' => 7,
        'submitted_by' => $owner->id,
        'archived_at' => '2026-10-01 08:00:00',
        'submitted_at' => '2026-09-01 08:00:00',
    ], $overrides));
}

it('filters archived documents by search, category, retention, and year', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'name' => 'Office Administrator',
    ]);
    $executive = DocumentCategory::query()->where('name', 'Executive and Administrative Documents')->firstOrFail();
    $legislative = DocumentCategory::query()->where('name', 'Legislative Documents')->firstOrFail();

    $match = archivedDocument($admin, $executive, [
        'tracking_number' => 'DMS-2026-0005',
        'title' => 'safvsdv',
        'retention_days' => 7,
        'archived_at' => '2026-10-01 08:00:00',
    ]);
    archivedDocument($admin, $legislative, [
        'tracking_number' => 'DMS-2025-0001',
        'title' => 'Old ordinance',
        'retention_days' => 365,
        'archived_at' => '2025-03-01 08:00:00',
    ]);
    archivedDocument($admin, $executive, [
        'tracking_number' => 'DMS-2026-0009',
        'title' => 'Still active',
        'status' => 'approved',
        'archived_at' => null,
    ]);

    actingAs($admin)->get('/archive')->assertOk()->assertInertia(fn ($page) => $page
        ->component('Archive/Index')
        ->has('archives.data', 2)
        ->where('totalArchives', 2));

    actingAs($admin)->get('/archive?q=safvsdv')->assertOk()->assertInertia(fn ($page) => $page
        ->has('archives.data', 1)
        ->where('archives.data.0.tracking', $match->tracking_number));

    actingAs($admin)->get('/archive?q=DMS-2025')->assertOk()->assertInertia(fn ($page) => $page
        ->has('archives.data', 1)
        ->where('archives.data.0.title', 'Old ordinance'));

    actingAs($admin)->get('/archive?category_id='.$legislative->id)->assertOk()->assertInertia(fn ($page) => $page
        ->has('archives.data', 1)
        ->where('archives.data.0.tracking', 'DMS-2025-0001'));

    actingAs($admin)->get('/archive?retention_days=7')->assertOk()->assertInertia(fn ($page) => $page
        ->has('archives.data', 1)
        ->where('archives.data.0.tracking', 'DMS-2026-0005'));

    actingAs($admin)->get('/archive?year=2026')->assertOk()->assertInertia(fn ($page) => $page
        ->has('archives.data', 1)
        ->where('archives.data.0.tracking', 'DMS-2026-0005'));
});
