<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\delete;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->seed(RolePermissionSeeder::class);
});

// ========================================
// EDIT
// ========================================

test('profile edit requires authentication', function () {
    assertGuestIsRedirectedToLogin('admin.profile.edit');
});

test('profile edit shows current user data', function () {
    $response = getAsAdmin('admin.profile.edit');

    $response->assertOk()
        ->assertViewIs('admin.profile.edit')
        ->assertViewHas('user');
});

// ========================================
// UPDATE
// ========================================

test('profile update requires authentication', function () {
    assertGuestIsRedirectedToLogin('admin.profile.update');
});

test('profile update changes name and email', function () {
    $user = actingAsAdmin();

    put(route('admin.profile.update'), [
        'name' => 'New Name',
        'email' => 'new@example.com',
    ])
        ->assertRedirect(route('admin.profile.edit'))
        ->assertSessionHas('success');

    $user->refresh();
    expect($user->name)->toBe('New Name');
    expect($user->email)->toBe('new@example.com');
});

test('profile update validates unique email', function () {
    $other = User::factory()->create(['email' => 'taken@example.com']);

    putAsAdmin('admin.profile.update', [
        'name' => 'User',
        'email' => 'taken@example.com',
    ])
        ->assertSessionHasErrors('email');
});

test('profile update changes password with current password check', function () {
    $user = actingAsAdmin();
    $user->update(['password' => Hash::make('OldPass123!')]);

    put(route('admin.profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'current_password' => 'OldPass123!',
        'password' => 'NewPass123!',
        'password_confirmation' => 'NewPass123!',
    ])
        ->assertRedirect(route('admin.profile.edit'))
        ->assertSessionHas('success');

    $user->refresh();
    expect(Hash::check('NewPass123!', $user->password))->toBeTrue();
});

test('profile update rejects password change without confirmation', function () {
    $user = actingAsAdmin();
    $user->update(['password' => Hash::make('OldPass123!')]);

    put(route('admin.profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'current_password' => 'OldPass123!',
        'password' => 'NewPass123!',
        // missing confirmation
    ])
        ->assertSessionHasErrors('password');
});

test('profile update handles base64 avatar upload', function () {
    Storage::fake('public');
    $user = actingAsAdmin();

    $base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    put(route('admin.profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => $base64Image,
    ])
        ->assertRedirect(route('admin.profile.edit'))
        ->assertSessionHas('success');

    $user->refresh();
    expect($user->avatar)->not()->toBeNull();

    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
    $disk = Storage::disk('public');
    $disk->assertExists($user->avatar);
});

test('profile update replaces old avatar when uploading new base64', function () {
    Storage::fake('public');

    $user = actingAsAdmin();
    $user->avatar = 'avatars/old.jpg';
    $user->save();

    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
    $disk = Storage::disk('public');
    $disk->put('avatars/old.jpg', 'old');

    $base64Image = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD/...';

    put(route('admin.profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => $base64Image,
    ])
        ->assertRedirect(route('admin.profile.edit'))
        ->assertSessionHas('success');

    $user->refresh();

    $disk->assertMissing('avatars/old.jpg');
    $disk->assertExists($user->avatar);
});

// ========================================
// DELETE AVATAR
// ========================================

test('profile delete avatar requires authentication', function () {
    delete(route('admin.profile.delete-avatar'))
        ->assertRedirect(route('login'));
});

test('profile delete avatar removes file and clears avatar', function () {
    Storage::fake('public');
    $user = actingAsAdmin();
    $user->avatar = 'avatars/pic.jpg';
    $user->save();

    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
    $disk = Storage::disk('public');
    $disk->put('avatars/pic.jpg', 'content');

    delete(route('admin.profile.delete-avatar'))
        ->assertRedirect(route('admin.profile.edit'))
        ->assertSessionHas('success');

    $user->refresh();
    expect($user->avatar)->toBeNull();
    $disk->assertMissing('avatars/pic.jpg');
});

// ========================================
// UPDATE THEME
// ========================================

test('profile update theme requires authentication', function () {
    post(route('admin.profile.update-theme'), ['theme' => 'dark'])
        ->assertRedirect(route('login'));
});

test('profile update theme saves preference and returns JSON', function () {
    $response = postAsAdmin('admin.profile.update-theme', ['theme' => 'dark']);

    $user = actingAsAdmin();
    $response = post(route('admin.profile.update-theme'), ['theme' => 'dark']);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Preferência de tema atualizada com sucesso!',
            'theme' => 'dark',
        ]);

    $user->refresh();
    expect($user->theme_preference)->toBe('dark');
});
