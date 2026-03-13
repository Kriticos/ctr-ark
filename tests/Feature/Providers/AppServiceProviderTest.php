<?php

use App\Models\Role;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

it('boots access-route gate and allows admin', function () {
    $provider = new AppServiceProvider(app());
    $provider->boot();

    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'avatars/john.png',
    ]);
    $adminRole = Role::factory()->create(['name' => 'Admin', 'slug' => 'admin']);
    $user->roles()->attach($adminRole);

    // Admin has access to all routes
    expect(Gate::forUser($user)->allows('access-route', 'some.route'))
        ->toBeTrue();
});

it('boots access-route gate denies non-admin without permission', function () {
    $provider = new AppServiceProvider(app());
    $provider->boot();

    $user = User::factory()->create();
    $editorRole = Role::factory()->create(['name' => 'Editor', 'slug' => 'editor']);
    $user->roles()->attach($editorRole);

    expect(Gate::forUser($user)->allows('access-route', 'some.route'))
        ->toBeFalse();
});

it('boots viewPulse gate for admin users', function () {
    $provider = new AppServiceProvider(app());
    $provider->boot();

    $user = User::factory()->create();
    $adminRole = Role::factory()->create(['name' => 'Admin', 'slug' => 'admin']);
    $user->roles()->attach($adminRole);

    expect(Gate::forUser($user)->allows('viewPulse'))
        ->toBeTrue();
});

it('boots viewPulse gate denies non-admin', function () {
    $provider = new AppServiceProvider(app());
    $provider->boot();

    $user = User::factory()->create();
    $editorRole = Role::factory()->create(['name' => 'Editor', 'slug' => 'editor']);
    $user->roles()->attach($editorRole);

    expect(Gate::forUser($user)->allows('viewPulse'))
        ->toBeFalse();
});
