<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

// ========================================
// SHOW FORM
// ========================================

test('reset password form is accessible and shows token/email', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $response = $this->get(route('password.reset', ['token' => 'abc123', 'email' => 'user@example.com']));
    $response->assertOk()
        ->assertViewIs('auth.reset-password')
        ->assertViewHasAll(['token', 'email']);
});

// ========================================
// RESET
// ========================================

test('reset password succeeds with valid token and updates password', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $user = User::factory()->create(['email' => 'user@example.com', 'password' => Hash::make('OldPass123!')]);

    Password::shouldReceive('reset')
        ->once()
        ->with(
            [
                'email' => 'user@example.com',
                'password' => 'NewPass123!',
                'password_confirmation' => 'NewPass123!',
                'token' => 'valid-token',
            ],
            \Mockery::on(function ($closure) use ($user) {
                // Simula execução do closure de atualização de senha
                $request = new \Illuminate\Http\Request([
                    'password' => 'NewPass123!',
                ]);
                $closure($user);

                return true;
            })
        )
        ->andReturn(Password::PASSWORD_RESET);

    $response = $this->post(route('password.update'), [
        'email' => 'user@example.com',
        'password' => 'NewPass123!',
        'password_confirmation' => 'NewPass123!',
        'token' => 'valid-token',
    ]);

    $response->assertRedirect(route('login'))
        ->assertSessionHas('success', 'Senha redefinida com sucesso!');
});

test('reset password fails with invalid token and returns validation error', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    Password::shouldReceive('reset')
        ->once()
        ->andReturn(Password::INVALID_TOKEN);

    $response = $this->from(route('password.reset', ['token' => 'invalid', 'email' => 'user@example.com']))
        ->post(route('password.update'), [
            'email' => 'user@example.com',
            'password' => 'SomePass123!',
            'password_confirmation' => 'SomePass123!',
            'token' => 'invalid',
        ]);

    $response->assertSessionHasErrors('email');
});
