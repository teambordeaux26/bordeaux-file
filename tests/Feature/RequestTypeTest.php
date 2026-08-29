<?php

use App\Models\DocumentRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Support\OasBarangays;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function requestTypesAdmin(): User
{
    return User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);
}

it('saves a new request type to the database', function () {
    $this->withoutVite();
    RequestType::syncDefaults();

    $this->actingAs(requestTypesAdmin())
        ->post('/settings/request-types', [
            'name' => 'Barangay Endorsement',
            'purpose' => 'Endorsement from the barangay captain',
            'issues_certificate' => false,
            'is_active' => true,
        ])
        ->assertRedirect();

    $created = RequestType::query()->where('name', 'Barangay Endorsement')->first();

    expect($created)->not->toBeNull()
        ->and($created->purpose)->toBe('Endorsement from the barangay captain')
        ->and($created->issues_certificate)->toBeFalse()
        ->and($created->is_active)->toBeTrue();

    $this->get('/requests/new')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Requests/Create')
            ->where('requestTypes', fn ($types) => collect($types)->contains(
                fn ($type) => $type['name'] === 'Barangay Endorsement'
                    && $type['purpose'] === 'Endorsement from the barangay captain'
            ))
        );
});

it('stores the connected purpose when a guest submits a request', function () {
    RequestType::syncDefaults();

    $type = RequestType::query()->where('name', 'Certificate of Appearance')->first();
    $address = OasBarangays::labels()[0];

    $this->post('/requests', [
        'requester_name' => 'Juan Dela Cruz',
        'requester_email' => 'juan@example.com',
        'requester_address' => $address,
        'request_type_id' => $type->id,
        'details' => 'Need a certificate for GSIS.',
    ])->assertRedirect();

    $request = DocumentRequest::query()->first();

    expect($request)->not->toBeNull()
        ->and($request->request_type)->toBe('Certificate of Appearance')
        ->and($request->purpose)->toBe($type->purpose)
        ->and($request->issuesCertificate())->toBeTrue();
});

it('shows active request types on the public form', function () {
    $this->withoutVite();
    RequestType::syncDefaults();

    RequestType::query()->create([
        'name' => 'Hidden Type',
        'purpose' => 'Should not appear',
        'issues_certificate' => false,
        'is_active' => false,
        'sort_order' => 99,
    ]);

    $this->get('/requests/new')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Requests/Create')
            ->has('requestTypes')
            ->where('requestTypes', fn ($types) => collect($types)->contains(
                fn ($type) => $type['name'] === 'Certificate of Appearance'
                    && filled($type['purpose'])
            ) && collect($types)->every(fn ($type) => $type['name'] !== 'Hidden Type'))
        );
});

it('removes a request type from the database and the public form', function () {
    $this->withoutVite();
    RequestType::syncDefaults();

    $meeting = RequestType::query()->where('name', 'Meeting Request')->first();

    $this->actingAs(requestTypesAdmin())
        ->delete('/settings/request-types/'.$meeting->id)
        ->assertRedirect();

    expect(RequestType::query()->where('name', 'Meeting Request')->exists())->toBeFalse();

    $this->get('/requests/new')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Requests/Create')
            ->where('requestTypes', fn ($types) => collect($types)->every(
                fn ($type) => $type['name'] !== 'Meeting Request'
            ))
        );
});

it('hides a request type from the public form when it is not shown', function () {
    $this->withoutVite();
    RequestType::syncDefaults();

    $meeting = RequestType::query()->where('name', 'Meeting Request')->first();

    $this->actingAs(requestTypesAdmin())
        ->put('/settings/request-types/'.$meeting->id, [
            'name' => $meeting->name,
            'purpose' => $meeting->purpose,
            'issues_certificate' => $meeting->issues_certificate,
            'is_active' => false,
        ])
        ->assertRedirect();

    expect($meeting->fresh()->is_active)->toBeFalse();

    $this->get('/requests/new')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Requests/Create')
            ->where('requestTypes', fn ($types) => collect($types)->every(
                fn ($type) => $type['name'] !== 'Meeting Request'
            ) && collect($types)->contains(
                fn ($type) => $type['name'] === 'Certificate of Appearance'
            ))
        );
});

it('does not recreate removed default request types when seeding again', function () {
    RequestType::syncDefaults();

    RequestType::query()->where('name', 'Meeting Request')->delete();

    RequestType::syncDefaults();

    expect(RequestType::query()->where('name', 'Meeting Request')->exists())->toBeFalse();
});
