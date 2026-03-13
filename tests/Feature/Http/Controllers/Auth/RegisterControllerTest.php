<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// ========================================
// SHOW FORM
// ========================================

test('register form is accessible', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->get(route('register'))
        ->assertOk()
        ->assertViewIs('auth.register');
});

// ========================================
// REGISTER
// ========================================

test('register creates user and logs in', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $payload = [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ];

    $response = $this->post('/register', $payload);

    $response->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('success', 'Conta criada com sucesso!');

    $user = User::where('email', 'new@example.com')->first();
    expect($user)->not()->toBeNull();
    expect(Hash::check('Password123!', $user->password))->toBeTrue();
});

test('register validates unique email and password confirmation', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    User::factory()->create(['email' => 'taken@example.com']);

    // Unique email
    $this->post('/register', [
        'name' => 'User',
        'email' => 'taken@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertSessionHasErrors('email');

    // Password confirmation mismatch
    $this->post('/register', [
        'name' => 'User',
        'email' => 'another@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'DifferentPassword!',
    ])->assertSessionHasErrors('password');
});
