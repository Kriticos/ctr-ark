<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

// ========================================
// SHOW FORM
// ========================================

test('forgot password form is accessible', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->get(route('password.request'))
        ->assertOk()
        ->assertViewIs('auth.forgot-password');
});

// ========================================
// SEND RESET LINK
// ========================================

test('forgot password sends reset link for valid email', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $user = User::factory()->create(['email' => 'user@example.com']);

    Password::shouldReceive('sendResetLink')
        ->once()
        ->with(['email' => 'user@example.com'])
        ->andReturn(Password::RESET_LINK_SENT);

    $this->post(route('password.email'), ['email' => 'user@example.com'])
        ->assertSessionHas('success', 'Link de recuperação enviado para seu e-mail!');
});

test('forgot password fails for unknown email and returns validation error', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    Password::shouldReceive('sendResetLink')
        ->once()
        ->with(['email' => 'missing@example.com'])
        ->andReturn(Password::INVALID_USER);

    $response = $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'missing@example.com']);

    $response->assertSessionHasErrors('email');
});
