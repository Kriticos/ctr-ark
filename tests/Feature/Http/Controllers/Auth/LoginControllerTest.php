<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// ========================================
// SHOW FORM
// ========================================

test('login form is accessible', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->get(route('login'))
        ->assertOk()
        ->assertViewIs('auth.login');
});

// ========================================
// LOGIN
// ========================================

test('login succeeds with valid credentials and sets last login/activity', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('Password123!'),
        'last_login_at' => null,
        'last_activity_at' => null,
    ]);

    $response = $this->post(route('login'), [
        'email' => 'user@example.com',
        'password' => 'Password123!',
        'remember' => true,
    ]);

    $response->assertRedirect(route('admin.dashboard'));

    $user->refresh();
    expect($user->last_login_at)->not()->toBeNull();
    expect($user->last_activity_at)->not()->toBeNull();
});

test('login fails with invalid credentials and returns validation error', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('Password123!'),
    ]);

    $response = $this->from(route('login'))
        ->post(route('login'), [
            'email' => 'user@example.com',
            'password' => 'WrongPassword!',
        ]);

    $response->assertSessionHasErrors('email');
});

// ========================================
// LOGOUT
// ========================================

test('logout redirects to login and invalidates session', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $user = User::factory()->create([
        'password' => Hash::make('Password123!'),
        'last_activity_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    // After logout, the user should be considered offline (activity set in the past)
    $user->refresh();
    expect($user->last_activity_at)->not()->toBeNull();
    expect($user->last_activity_at->lt(now()->subMinutes(5)))->toBeTrue();
});
