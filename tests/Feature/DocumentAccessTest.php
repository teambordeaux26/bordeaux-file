<?php

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function documentStaff(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'employee',
        'status' => 'active',
    ], $overrides));
}

function leafCategory(): DocumentCategory
{
    return DocumentCategory::query()->create([
        'name' => 'Memoranda',
        'description' => 'Office memoranda',
        'sort_order' => 1,
    ]);
}

it('lets an uploader restrict access by role and assign a current handler', function () {
    $this->withoutVite();

    $owner = documentStaff(['name' => 'Owner Staff']);
    $colleague = documentStaff(['name' => 'Same Role']);
    $admin = documentStaff(['name' => 'Office Admin', 'role' => 'admin']);
    $category = leafCategory();

    $this->actingAs($owner)
        ->post('/documents', [
            'title' => 'Employee Memo',
            'category_id' => $category->id,
            'priority' => 'Standard',
            'retention_days' => 7,
            'access_roles' => ['employee'],
            'handled_by' => $colleague->id,
        ])
        ->assertRedirect(route('documents.index'));

    $document = Document::query()->where('title', 'Employee Memo')->first();

    expect($document)->not->toBeNull()
        ->and($document->handled_by)->toBe($colleague->id)
        ->and($document->access_roles)->toBe(['employee']);

    $this->actingAs($colleague)->get(route('documents.show', $document))->assertOk();
    $this->actingAs($admin)->get(route('documents.show', $document))->assertOk();

    $this->actingAs($owner)
        ->put(route('documents.sharing', $document), [
            'access_roles' => ['admin'],
            'handled_by' => null,
        ])
        ->assertRedirect();

    expect($document->fresh()->access_roles)->toBe(['admin']);

    $this->actingAs($owner)->get(route('documents.show', $document))->assertOk();
    $this->actingAs($admin)->get(route('documents.show', $document))->assertOk();
    $this->actingAs($colleague)->get(route('documents.show', $document))->assertForbidden();
    $this->actingAs($colleague)->get(route('documents.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Documents/Index')
            ->where('documents.data', fn ($rows) => collect($rows)->pluck('title')->doesntContain('Employee Memo'))
        );
});

it('keeps unrestricted documents visible to all staff', function () {
    $this->withoutVite();

    $owner = documentStaff();
    $colleague = documentStaff();
    $category = leafCategory();

    $this->actingAs($owner)
        ->post('/documents', [
            'title' => 'Open Memo',
            'category_id' => $category->id,
            'priority' => 'Standard',
            'retention_days' => 7,
        ])
        ->assertRedirect();

    $document = Document::query()->where('title', 'Open Memo')->first();

    $this->actingAs($colleague)->get(route('documents.show', $document))->assertOk();
});

it('records the current handler when a reviewer starts work', function () {
    $owner = documentStaff();
    $reviewer = documentStaff(['name' => 'Reviewer One']);
    $category = leafCategory();

    $document = Document::query()->create([
        'tracking_number' => 'DMS-2026-0099',
        'reference_no' => 'REF-202609-0099',
        'title' => 'For Review',
        'category_id' => $category->id,
        'priority' => 'Standard',
        'status' => 'pending',
        'retention_days' => 7,
        'submitted_by' => $owner->id,
        'submitted_at' => now(),
    ]);

    $this->actingAs($reviewer)
        ->put(route('workflow.advance', $document), [
            'action' => 'start_review',
        ])
        ->assertRedirect();

    expect($document->fresh()->handled_by)->toBe($reviewer->id)
        ->and($document->fresh()->reviewed_by)->toBe($reviewer->id);
});
