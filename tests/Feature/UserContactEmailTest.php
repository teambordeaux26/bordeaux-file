<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function contactEmailAdmin(): User
{
    return User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);
}

it('stores a contact email when an admin creates a user', function () {
    actingAs(contactEmailAdmin())
        ->post('/users', [
            'name' => 'Maria Santos',
            'email_local' => 'maria.santos',
            'contact_email' => 'maria.santos@gmail.com',
            'role' => 'employee',
            'status' => 'active',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertRedirect();

    $user = User::query()->where('email', 'maria.santos@oas-dms.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->contact_email)->toBe('maria.santos@gmail.com');
});

it('requires a contact email when creating a user', function () {
    actingAs(contactEmailAdmin())
        ->post('/users', [
            'name' => 'Maria Santos',
            'email_local' => 'maria.santos',
            'role' => 'employee',
            'status' => 'active',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertSessionHasErrors('contact_email');

    expect(User::query()->where('email', 'maria.santos@oas-dms.com')->exists())->toBeFalse();
});
