<?php

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('keeps the previous file as an old version and stays on returned documents', function () {
    $this->withoutVite();
    Storage::fake('public');

    $owner = User::factory()->create([
        'role' => 'employee',
        'status' => 'active',
        'name' => 'Head Staff',
    ]);
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'name' => 'Office Administrator',
    ]);
    $category = DocumentCategory::query()->create([
        'name' => 'Revision Memoranda',
        'sort_order' => 1,
    ]);

    Storage::disk('public')->put('documents/original-memo.pdf', 'original contents');

    $document = Document::query()->create([
        'tracking_number' => 'DMS-2026-0012',
        'reference_no' => 'REF-202611-0001',
        'title' => 'memo',
        'description' => 'memo',
        'category_id' => $category->id,
        'status' => 'returned',
        'priority' => 'Standard',
        'file_path' => 'documents/original-memo.pdf',
        'retention_days' => 7,
        'submitted_by' => $owner->id,
        'returned_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(route('documents.edit', $document))
        ->assertRedirect(route('documents.returned', ['edit' => $document->id]));

    $this->actingAs($owner)
        ->get(route('documents.returned', ['edit' => $document->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Documents/Returned')
            ->where('editingId', $document->id)
        );

    $this->actingAs($owner)
        ->post(route('documents.update', $document), [
            '_method' => 'put',
            'title' => 'memo',
            'category_id' => $category->id,
            'priority' => 'Standard',
            'description' => 'revised memo',
            'owner_remarks' => 'Corrected the attachment. Please review again.',
            'retention_days' => 7,
            'resubmit' => 1,
            'file' => UploadedFile::fake()->create('revised-memo.pdf', 40, 'application/pdf'),
        ])
        ->assertRedirect(route('documents.returned'));

    $document->refresh();

    expect($document->status)->toBe('pending')
        ->and($document->owner_remarks)->toBe('Corrected the attachment. Please review again.')
        ->and($document->file_path)->not->toBe('documents/original-memo.pdf')
        ->and(Storage::disk('public')->exists('documents/original-memo.pdf'))->toBeTrue()
        ->and(Storage::disk('public')->exists($document->file_path))->toBeTrue();

    $versions = $document->versions()->reorder()->orderBy('version_number')->get();

    expect($versions)->toHaveCount(2)
        ->and($versions[0]->label)->toBe('Old version')
        ->and($versions[0]->file_path)->toBe('documents/original-memo.pdf')
        ->and($versions[1]->label)->toBe('Revised version')
        ->and($versions[1]->original_name)->toBe('revised-memo.pdf')
        ->and($versions[1]->file_path)->toBe($document->file_path);

    $this->actingAs($admin)
        ->get(route('documents.show', $document))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Documents/Show')
            ->where('document.versions.0.label', 'Revised version')
            ->where('document.versions.1.label', 'Old version')
            ->where('document.owner_remarks', 'Corrected the attachment. Please review again.')
        );

    $this->actingAs($admin)
        ->get(route('workflow.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Workflow/Index')
            ->where('documents.pending.0.versions.0.label', 'Revised version')
            ->where('documents.pending.0.versions.1.label', 'Old version')
            ->where('documents.pending.0.owner_remarks', 'Corrected the attachment. Please review again.')
        );

    $this->actingAs($admin)
        ->get(route('documents.versions.file', [$document, $versions[0]]))
        ->assertOk();
});
