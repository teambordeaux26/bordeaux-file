<?php

use App\Models\DocumentRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Support\OasBarangays;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

function guestRequestPayload(int $typeId, array $extra = []): array
{
    return array_merge([
        'requester_name' => 'Juan Dela Cruz',
        'requester_email' => 'juan@example.com',
        'requester_address' => OasBarangays::labels()[0],
        'request_type_id' => $typeId,
        'details' => 'Please review the attached proposal.',
    ], $extra);
}

it('stores multiple files with a guest request', function () {
    Storage::fake('local');
    RequestType::syncDefaults();

    $type = RequestType::query()->where('name', 'Other')->first();

    post('/requests', guestRequestPayload($type->id, [
        'attachments' => [
            UploadedFile::fake()->create('proposal.pdf', 120, 'application/pdf'),
            UploadedFile::fake()->create('annex.pdf', 40, 'application/pdf'),
        ],
    ]))->assertRedirect();

    $request = DocumentRequest::query()->with('attachments')->first();

    expect($request)->not->toBeNull()
        ->and($request->attachments)->toHaveCount(2)
        ->and($request->attachments->pluck('name')->all())->toBe(['proposal.pdf', 'annex.pdf']);

    foreach ($request->attachments as $attachment) {
        expect(Storage::disk('local')->exists($attachment->path))->toBeTrue();
    }
});

it('continues the request tracking number after one that is already in use', function () {
    RequestType::syncDefaults();

    $type = RequestType::query()->where('name', 'Other')->first();
    $year = now()->year;

    DocumentRequest::query()->create([
        'requester_name' => 'Earlier Guest',
        'requester_email' => 'earlier@example.com',
        'requester_address' => OasBarangays::labels()[0],
        'request_type' => $type->name,
        'request_type_id' => $type->id,
        'purpose' => $type->purpose,
        'tracking_number' => "REQ-{$year}-0002",
        'status' => 'pending',
    ]);

    post('/requests', guestRequestPayload($type->id))->assertRedirect();

    $created = DocumentRequest::query()->where('requester_name', 'Juan Dela Cruz')->first();

    expect($created)->not->toBeNull()
        ->and($created->tracking_number)->toBe("REQ-{$year}-0003");
});

it('rejects a guest attachment that is not an allowed document', function () {
    Storage::fake('local');
    RequestType::syncDefaults();

    $type = RequestType::query()->where('name', 'Other')->first();
    $file = UploadedFile::fake()->create('proposal.exe', 20, 'application/octet-stream');

    post('/requests', guestRequestPayload($type->id, [
        'attachments' => [$file],
    ]))->assertSessionHasErrors('attachments.0');

    expect(DocumentRequest::query()->count())->toBe(0);
});

it('lets an admin download each guest attachment and keeps them private', function () {
    Storage::fake('local');
    RequestType::syncDefaults();

    $type = RequestType::query()->where('name', 'Other')->first();

    post('/requests', guestRequestPayload($type->id, [
        'attachments' => [
            UploadedFile::fake()->create('proposal.pdf', 80, 'application/pdf'),
        ],
    ]))->assertRedirect();

    $request = DocumentRequest::query()->with('attachments')->first();
    $attachment = $request->attachments->first();

    get(route('requests.attachment', [$request, $attachment]))
        ->assertRedirect('/login');

    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    actingAs($admin)
        ->get(route('requests.attachment', [$request, $attachment]))
        ->assertOk();
});
