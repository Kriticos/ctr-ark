<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

test('user can be created with mass assignment', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    expect($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com')
        ->and($user->password)->not->toBe('password'); // Deve estar hasheado
});

test('user password is hashed automatically', function () {
    $user = User::factory()->create(['password' => 'plaintext']);

    expect($user->password)->not->toBe('plaintext')
        ->and(\Illuminate\Support\Facades\Hash::check('plaintext', $user->password))->toBeTrue();
});

test('user has hidden attributes', function () {
    $user = User::factory()->create();
    $array = $user->toArray();

    expect($array)->not->toHaveKey('password')
        ->and($array)->not->toHaveKey('remember_token');
});

test('user has correct casts', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'last_activity_at' => now(),
        'last_login_at' => now(),
        'is_online' => true,
    ]);

    expect($user->email_verified_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($user->last_activity_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($user->last_login_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($user->is_online)->toBeBool();
});

test('isOnline returns true when user had activity in last 5 minutes', function () {
    $user = User::factory()->create([
        'last_activity_at' => now()->subMinutes(3),
    ]);

    expect($user->isOnline())->toBeTrue();
});

test('isOnline returns false when user had no recent activity', function () {
    $user = User::factory()->create([
        'last_activity_at' => now()->subMinutes(10),
    ]);

    expect($user->isOnline())->toBeFalse();
});

test('isOnline returns false when user never had activity', function () {
    $user = User::factory()->create([
        'last_activity_at' => null,
    ]);

    expect($user->isOnline())->toBeFalse();
});

test('onlineUsers returns only users with recent activity', function () {
    User::factory()->create(['last_activity_at' => now()->subMinutes(3)]);
    User::factory()->create(['last_activity_at' => now()->subMinutes(4)]);
    User::factory()->create(['last_activity_at' => now()->subMinutes(10)]);
    User::factory()->create(['last_activity_at' => null]);

    $onlineUsers = User::onlineUsers();

    expect($onlineUsers)->toHaveCount(2);
});

test('getStatusText returns Online when user is online', function () {
    $user = User::factory()->create([
        'last_activity_at' => now()->subMinutes(2),
    ]);

    expect($user->getStatusText())->toBe('Online');
});

test('getStatusText returns Nunca ativo when user never logged in', function () {
    $user = User::factory()->create([
        'last_login_at' => null,
        'last_activity_at' => null,
    ]);

    expect($user->getStatusText())->toBe('Nunca ativo');
});

test('getStatusText returns Offline when user inactive for more than 7 days', function () {
    $user = User::factory()->create([
        'last_activity_at' => now()->subDays(10),
        'last_login_at' => now()->subDays(10),
    ]);

    expect($user->getStatusText())->toBe('Offline');
});

test('getStatusText returns relative time when user inactive less than 7 days', function () {
    $user = User::factory()->create([
        'last_activity_at' => now()->subHours(2),
        'last_login_at' => now()->subDays(1),
    ]);

    expect($user->getStatusText())->toContain('Visto');
});

test('getStatusText returns Offline when user has no activity', function () {
    $user = User::factory()->create([
        'last_activity_at' => null,
        'last_login_at' => now()->subDays(1),
    ]);

    expect($user->getStatusText())->toBe('Offline');
});

test('getStatusColorClass returns green for online users', function () {
    $user = User::factory()->create([
        'last_activity_at' => now()->subMinutes(2),
    ]);

    expect($user->getStatusColorClass())->toBe('text-green-600 dark:text-green-400');
});

test('getStatusColorClass returns gray for users who never logged in', function () {
    $user = User::factory()->create([
        'last_login_at' => null,
    ]);

    expect($user->getStatusColorClass())->toBe('text-gray-400 dark:text-gray-600');
});

test('getStatusColorClass returns gray for offline users', function () {
    $user = User::factory()->create([
        'last_activity_at' => now()->subMinutes(10),
        'last_login_at' => now()->subDays(1),
    ]);

    expect($user->getStatusColorClass())->toBe('text-gray-500 dark:text-gray-400');
});

test('user has roles relationship', function () {
    $user = User::factory()->create();
    $role = Role::first();

    $user->roles()->attach($role);

    expect($user->roles)->toHaveCount(1)
        ->and($user->roles->first()->id)->toBe($role->id);
});

test('hasPermissionTo returns true for admin role', function () {
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();

    $user->roles()->attach($adminRole);

    expect($user->hasPermissionTo('any.permission'))->toBeTrue();
});

test('hasPermissionTo returns true when user has permission through role', function () {
    $user = User::factory()->create();
    $role = Role::where('slug', 'editor')->first();

    $user->roles()->attach($role);

    expect($user->hasPermissionTo('admin.dashboard'))->toBeTrue();
});

test('hasPermissionTo returns false when user does not have permission', function () {
    $user = User::factory()->create();
    $role = Role::where('slug', 'viewer')->first();

    $user->roles()->attach($role);

    expect($user->hasPermissionTo('admin.users.create'))->toBeFalse();
});

test('hasRole returns true when user has the role', function () {
    $user = User::factory()->create();
    $role = Role::where('slug', 'admin')->first();

    $user->roles()->attach($role);

    expect($user->hasRole('admin'))->toBeTrue();
});

test('hasRole returns false when user does not have the role', function () {
    $user = User::factory()->create();

    expect($user->hasRole('admin'))->toBeFalse();
});

test('isAdmin returns true for admin users', function () {
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();

    $user->roles()->attach($adminRole);

    expect($user->isAdmin())->toBeTrue();
});

test('isAdmin returns false for non-admin users', function () {
    $user = User::factory()->create();

    expect($user->isAdmin())->toBeFalse();
});

test('user can have multiple roles', function () {
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'admin')->first();
    $editorRole = Role::where('slug', 'editor')->first();

    $user->roles()->attach([$adminRole->id, $editorRole->id]);

    expect($user->roles)->toHaveCount(2);
});
