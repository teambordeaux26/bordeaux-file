<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('ends the session when a user logs out', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'remember_token' => 'remember-me-token',
    ]);

    actingAs($user)
        ->post('/logout')
        ->assertRedirect(route('login'));

    assertGuest();
    expect($user->fresh()->remember_token)->toBeNull();

    get('/dashboard')->assertRedirect(route('login'));
});
