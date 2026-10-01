<?php

use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

function incomingRequest(string $status = 'pending'): DocumentRequest
{
    return DocumentRequest::create([
        'tracking_number' => 'OAS-'.strtoupper($status).'-'.fake()->unique()->numerify('####'),
        'requester_name' => 'Juan Dela Cruz',
        'requester_email' => 'juan@example.com',
        'request_type' => 'Certificate of Appearance',
        'details' => 'Need a certificate.',
        'status' => $status,
    ]);
}

it('shows pending document requests to admins and employees', function () {
    $pending = incomingRequest('pending');
    incomingRequest('completed');

    foreach (['admin', 'employee'] as $role) {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);

        actingAs($user)
            ->getJson('/requests/notifications')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('items.0.tracking', $pending->tracking_number)
            ->assertJsonPath('items.0.name', 'Juan Dela Cruz');

        actingAs($user)
            ->get('/requests?status=pending')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Requests/Admin')
                ->where('requestNotifications.count', 1)
                ->has('requestNotifications.items', 1));
    }
});

it('keeps request notifications behind sign-in', function () {
    incomingRequest();

    get('/requests/notifications')->assertRedirect(route('login'));
    get('/requests')->assertRedirect(route('login'));
});
