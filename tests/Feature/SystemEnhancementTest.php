<?php

use App\Mail\VisitorRecordMail;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Models\VisitorLog;
use App\Rules\OasBarangayAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

function enhancementAdmin(): User
{
    return User::factory()->create([
        'name' => 'Office Administrator',
        'email' => 'admin@oas-dms.local',
        'contact_email' => 'admin.contact@gmail.com',
        'role' => 'admin',
        'status' => 'active',
        'password' => 'old-password',
    ]);
}

it('lets an administrator set a new password without rewriting a custom login domain', function () {
    $admin = enhancementAdmin();
    $staff = User::factory()->create([
        'name' => 'Maria Santos',
        'email' => 'maria@oas-dms.local',
        'contact_email' => 'maria.santos@gmail.com',
        'role' => 'employee',
        'status' => 'active',
        'password' => 'old-password',
    ]);

    actingAs($admin)->put("/users/{$staff->id}", [
        'name' => 'Maria Santos',
        'email_local' => 'maria',
        'contact_email' => 'maria.santos@gmail.com',
        'role' => 'employee',
        'status' => 'active',
        'password' => 'new-password-1',
        'password_confirmation' => 'new-password-1',
    ])->assertRedirect();

    $staff->refresh();

    expect($staff->email)->toBe('maria@oas-dms.local')
        ->and(Hash::check('new-password-1', $staff->password))->toBeTrue();

    config(['services.turnstile.secret_key' => null]);

    post('/login', [
        'email' => 'maria@oas-dms.local',
        'password' => 'new-password-1',
    ])->assertRedirect(route('dashboard'));
});

it('deactivates a department and does not expose a delete route', function () {
    $admin = enhancementAdmin();
    $department = Department::query()->create([
        'name' => 'Records',
        'code' => 'REC',
        'status' => 'active',
    ]);

    actingAs($admin)->put("/departments/{$department->id}", [
        'name' => 'Records',
        'code' => 'REC',
        'status' => 'inactive',
    ])->assertRedirect();

    expect($department->fresh()->status)->toBe('inactive');

    actingAs($admin)->delete("/departments/{$department->id}")->assertStatus(405);
});

it('removes a restored document from the archive and records the exact audit sentence', function () {
    $admin = enhancementAdmin();
    $category = DocumentCategory::query()->where('name', 'Executive and Administrative Documents')->firstOrFail();
    $document = Document::query()->create([
        'tracking_number' => 'DMS-2026-0042',
        'title' => 'Budget memo',
        'category_id' => $category->id,
        'status' => 'archived',
        'priority' => 'Standard',
        'retention_days' => 30,
        'submitted_by' => $admin->id,
        'archived_at' => now()->subMonth(),
        'expires_at' => now()->subDay(),
        'submitted_at' => now()->subMonths(2),
    ]);

    actingAs($admin)->put("/archive/{$document->id}/restore")
        ->assertRedirect(route('documents.index'));

    $document->refresh();

    expect($document->status)->toBe('approved')
        ->and($document->archived_at)->toBeNull()
        ->and($document->restored_by)->toBe($admin->id)
        ->and($document->expires_at?->isFuture())->toBeTrue();

    actingAs($admin)->get('/archive')->assertOk()->assertInertia(fn ($page) => $page
        ->where('totalArchives', 0)
        ->has('archives.data', 0));

    actingAs($admin)->get('/documents')->assertOk()->assertInertia(fn ($page) => $page
        ->component('Documents/Index')
        ->where('documents.data', fn ($rows) => collect($rows)->contains(
            fn ($row) => $row['tracking'] === 'DMS-2026-0042'
        )));

    $log = AuditLog::query()->where('action', 'Document Restored')->first();

    expect($log)->not->toBeNull()
        ->and($log->description)->toContain('File DMS-2026-0042 was restored on')
        ->and($log->description)->toContain('by Office Administrator.')
        ->and($log->description)->not->toContain('ago');

    actingAs($admin)->get('/workflow')->assertOk()->assertInertia(fn ($page) => $page
        ->has('restored', 1)
        ->where('restored.0.tracking', 'DMS-2026-0042')
        ->where('restored.0.summary', fn ($summary) => str_contains($summary, 'was restored on')
            && str_contains($summary, 'Office Administrator')));
});

it('emails a visitor record only when an email address is provided', function () {
    Mail::fake();
    $admin = enhancementAdmin();
    $address = 'Brgy. Badbad, Oas, Albay';

    actingAs($admin)->post('/visitors', [
        'visitor_name' => 'Walk In Guest',
        'address' => $address,
        'purpose' => 'Inquiry',
    ])->assertRedirect();

    Mail::assertNothingSent();

    actingAs($admin)->post('/visitors', [
        'visitor_name' => 'Elena Cruz',
        'visitor_email' => 'elena@example.com',
        'address' => $address,
        'purpose' => 'Follow up',
    ])->assertRedirect();

    Mail::assertSent(VisitorRecordMail::class, function (VisitorRecordMail $mail) {
        return $mail->hasTo('elena@example.com') && $mail->mailer === 'smtp';
    });

    actingAs($admin)->get('/visitors')->assertOk()->assertInertia(fn ($page) => $page
        ->has('visitors', 2)
        ->has('previous'));
});

it('accepts a custom non-resident address and rejects the literal Other option', function () {
    $rule = new OasBarangayAddress;

    expect(Validator::make(['address' => 'Brgy. Badbad, Oas, Albay'], ['address' => $rule])->fails())->toBeFalse()
        ->and(Validator::make(['address' => 'Purok 2, Ligao City'], ['address' => $rule])->fails())->toBeFalse()
        ->and(Validator::make(['address' => 'Other'], ['address' => $rule])->fails())->toBeTrue();
});

it('matches visitor addresses that begin with a letter regardless of case', function () {
    $admin = enhancementAdmin();

    VisitorLog::query()->create([
        'visitor_name' => 'Ana Cruz',
        'address' => 'Brgy. Badbad, Oas, Albay',
        'purpose' => 'Visit',
        'time_in' => now(),
        'recorded_by' => $admin->id,
    ]);
    VisitorLog::query()->create([
        'visitor_name' => 'Pedro Lopez',
        'address' => 'Brgy. Iraya, Oas, Albay',
        'purpose' => 'Visit',
        'time_in' => now(),
        'recorded_by' => $admin->id,
    ]);

    foreach (['B', 'b'] as $term) {
        $response = actingAs($admin)->getJson('/search/suggest?q='.$term)->assertOk();
        $titles = collect($response->json('results'))->pluck('title');

        expect($titles)->toContain('Ana Cruz')
            ->and($titles)->not->toContain('Pedro Lopez');
    }
});

it('serves a document request qr code and paginates audit and approval lists', function () {
    Carbon::setTestNow('2026-10-09 12:00:00');
    $admin = enhancementAdmin();
    $category = DocumentCategory::query()->where('name', 'Legislative Documents')->firstOrFail();

    get('/requests/qr')
        ->assertOk()
        ->assertHeader('content-type', 'image/svg+xml');

    $recent = AuditLog::query()->create([
        'user_id' => $admin->id,
        'action' => 'Document Viewed',
        'description' => 'Opened a legislative file.',
        'meta' => ['category_id' => $category->id],
    ]);
    $recent->created_at = Carbon::parse('2026-10-08 09:00:00');
    $recent->save();

    $older = AuditLog::query()->create([
        'user_id' => $admin->id,
        'action' => 'Older Activity',
        'description' => 'An older entry.',
    ]);
    $older->created_at = Carbon::parse('2026-09-01 09:00:00');
    $older->save();

    Document::query()->create([
        'tracking_number' => 'DMS-2026-0100',
        'title' => 'Pending ordinance',
        'category_id' => $category->id,
        'status' => 'pending',
        'priority' => 'Standard',
        'retention_days' => 7,
        'submitted_by' => $admin->id,
        'submitted_at' => now(),
    ]);

    actingAs($admin)->get('/audit?preset=week')->assertOk()->assertInertia(fn ($page) => $page
        ->has('logs.data', 1)
        ->where('logs.data.0.action', 'Document Viewed')
        ->where('logs.data.0.time', fn ($time) => ! str_contains($time, 'ago'))
        ->has('logs.links'));

    actingAs($admin)->get('/audit?category_id='.$category->id)->assertOk()->assertInertia(fn ($page) => $page
        ->has('logs.data', 1));

    actingAs($admin)->get('/approvals')->assertOk()->assertInertia(fn ($page) => $page
        ->has('documents.data', 1)
        ->has('documents.links'));

    actingAs($admin)->get('/dashboard')->assertOk()->assertInertia(fn ($page) => $page
        ->has('stats', 6)
        ->has('calendar')
        ->missing('activity')
        ->has('documentFolders'));
});
