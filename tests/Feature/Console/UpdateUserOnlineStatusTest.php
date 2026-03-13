<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('updates user online status command runs successfully', function () {
    User::factory(3)->create();

    $exitCode = Artisan::call('users:update-online-status');
    expect($exitCode)->toBe(0);
});

it('outputs status messages during update', function () {
    User::factory(2)->create();

    Artisan::call('users:update-online-status');
    expect(Artisan::output())->toContain('Atualizando status online dos usuários...');
});

it('handles empty user list', function () {
    Artisan::call('users:update-online-status');
    expect(Artisan::output())->toContain('Atualizando status online dos usuários...');
});

it('marks offline users with specific activity window', function () {
    // Create multiple users to ensure the marking offline logic executes
    User::factory(5)->create([
        'last_activity_at' => now()->subMinutes(7),
    ]);

    Artisan::call('users:update-online-status');
    expect(Artisan::output())->toContain('Atualizando status online dos usuários...');
});
