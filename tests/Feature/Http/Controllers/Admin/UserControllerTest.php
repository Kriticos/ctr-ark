<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $this->seed(RolePermissionSeeder::class);
});

// ========================================
// INDEX TESTS
// ========================================

test('user index requires authentication', function () {
    assertGuestIsRedirectedToLogin('admin.users.index');
});

test('user index shows list of users', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $users = User::factory(3)->create();

    getAsAdmin('admin.users.index')
        ->assertOk()
        ->assertViewIs('admin.users.index')
        ->assertViewHas('users', function ($viewUsers) use ($users) {
            foreach ($users as $user) {
                if (! $viewUsers->contains('id', $user->id)) {
                    return false;
                }
            }

            return true;
        });
});

test('user index paginates users', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    User::factory(20)->create();

    getAsAdmin('admin.users.index')
        ->assertOk()
        ->assertViewIs('admin.users.index')
        ->assertViewHas('users', function ($viewUsers) {
            return $viewUsers->count() === 15; // Padrão de paginação
        });
});

test('user index filters by search term', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $john = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

    getAsAdmin('admin.users.index', ['search' => 'John'])
        ->assertOk()
        ->assertViewIs('admin.users.index')
        ->assertViewHas('users', function ($viewUsers) use ($john) {
            return $viewUsers->count() === 1 && $viewUsers->first()->id === $john->id;
        });
});

test('user index search by email', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $user = User::factory()->create(['email' => 'specific@example.com']);

    getAsAdmin('admin.users.index', ['search' => 'specific@example.com'])
        ->assertOk()
        ->assertViewIs('admin.users.index')
        ->assertViewHas('users', function ($viewUsers) use ($user) {
            return $viewUsers->count() === 1 && $viewUsers->first()->id === $user->id;
        });
});

// ========================================
// CREATE TESTS
// ========================================

test('user create requires authentication', function () {
    assertGuestIsRedirectedToLogin('admin.users.create');
});

test('user create shows form with roles', function () {

    getAsAdmin('admin.users.create')
        ->assertOk()
        ->assertViewIs('admin.users.create')
        ->assertViewHas('roles');
});

// ========================================
// STORE TESTS
// ========================================

test('user store requires authentication', function () {
    post(route('admin.users.store'), [])
        ->assertRedirect(route('login'));
});

test('user store creates user with valid data', function () {
    $userData = [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
    ];

    $response = postAsAdmin('admin.users.store', $userData);
    $response->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success', 'Usuário criado com sucesso!');

    assertDatabaseHas('users', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
    ]);
});

test('user store hashes password correctly', function () {
    $userData = [
        'name' => 'Hash Test User',
        'email' => 'hashtest@example.com',
        'password' => 'PlainTextPassword123!',
        'password_confirmation' => 'PlainTextPassword123!',
    ];

    postAsAdmin('admin.users.store', $userData);
    $user = User::where('email', 'hashtest@example.com')->first();
    expect(Hash::check('PlainTextPassword123!', $user->password))->toBeTrue();
});

test('user store validates required fields', function () {
    postAsAdmin('admin.users.store', [])
        ->assertSessionHasErrors(['name', 'email', 'password']);
});

test('user store validates email uniqueness', function () {
    // Criar um usuário para forçar a violação de unicidade
    User::factory()->create(['email' => 'taken@example.com']);

    $userData = [
        'name' => 'New User',
        'email' => 'taken@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
    ];

    postAsAdmin('admin.users.store', $userData)
        ->assertSessionHasErrors('email');
});

test('user store attaches roles when provided', function () {
    $role = Role::first();

    $userData = [
        'name' => 'User With Role',
        'email' => 'userwithrole@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'roles' => [$role->id],
    ];

    postAsAdmin('admin.users.store', $userData);

    $user = User::where('email', 'userwithrole@example.com')->first();
    expect($user->roles->contains('id', $role->id))->toBeTrue();
});

test('user store handles base64 avatar upload', function () {
    Storage::fake('public');

    $base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    $userData = [
        'name' => 'User With Base64 Avatar',
        'email' => 'base64avatar@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'avatar' => $base64Image,
    ];

    postAsAdmin('admin.users.store', $userData);

    $user = User::where('email', 'base64avatar@example.com')->first();
    expect($user->avatar)->not()->toBeNull();

    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
    $disk = Storage::disk('public');
    $disk->assertExists($user->avatar);
});

test('user store without roles creates user successfully', function () {
    $userData = [
        'name' => 'User Without Roles',
        'email' => 'noroles@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
    ];

    postAsAdmin('admin.users.store', $userData);

    $user = User::where('email', 'noroles@example.com')->first();
    expect($user->roles)->toHaveCount(0);
});

// ========================================
// SHOW TESTS
// ========================================

test('user show requires authentication', function () {
    $user = User::factory()->create();
    assertGuestIsRedirectedToLogin('admin.users.show', ['user' => $user]);
});

test('user show displays user with statistics', function () {
    $user = User::factory()->create();

    getAsAdmin('admin.users.show', ['user' => $user])
        ->assertOk()
        ->assertViewIs('admin.users.show')
        ->assertViewHas('user', $user);
});

test('user show includes activity log', function () {
    $user = User::factory()->create();

    getAsAdmin('admin.users.show', ['user' => $user])
        ->assertOk()
        ->assertViewHas('activityLog');
});

test('user show displays complete statistics', function () {
    $module = \App\Models\Module::create([
        'name' => 'Test Module',
        'slug' => 'test-module-'.uniqid(),
        'order' => 1,
    ]);

    $role = Role::factory()->create();
    $permission = \App\Models\Permission::factory()->create(['module_id' => $module->id]);
    $role->permissions()->attach($permission);

    $user = User::factory()->create();
    $user->roles()->attach($role);

    $response = getAsAdmin('admin.users.show', ['user' => $user]);

    $response->assertOk()
        ->assertViewHasAll([
            'user',
            'totalRoles',
            'totalPermissions',
            'totalModules',
            'modules',
            'permissionsByRole',
            'permissionsByModule',
            'activityLog',
        ]);

    expect($response->viewData('totalRoles'))->toBe(1);
    expect($response->viewData('totalPermissions'))->toBe(1);
    expect($response->viewData('totalModules'))->toBe(1);
});

// ========================================
// EDIT TESTS
// ========================================

test('user edit requires authentication', function () {
    $user = User::factory()->create();
    assertGuestIsRedirectedToLogin('admin.users.edit', ['user' => $user]);
});

test('user edit shows form with user data and roles', function () {
    $user = User::factory()->create();

    $response = getAsAdmin('admin.users.edit', ['user' => $user]);
    $response->assertOk()
        ->assertViewIs('admin.users.edit')
        ->assertViewHas('user', $user)
        ->assertViewHas('roles');
});

// ========================================
// UPDATE TESTS
// ========================================

test('user update requires authentication', function () {
    $user = User::factory()->create();
    assertGuestIsRedirectedToLogin('admin.users.update', ['user' => $user]);
});

test('user update modifies user data', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $userData = [
        'name' => 'New Name',
        'email' => $user->email,
    ];

    $response = patchAsAdmin('admin.users.update', $userData, ['user' => $user]);
    $response->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success', 'Usuário atualizado com sucesso!');

    $user->refresh();
    expect($user->name)->toBe('New Name');
});

test('user update requires valid email', function () {
    $user = User::factory()->create();
    $userData = [
        'name' => 'Updated Name',
        'email' => 'invalid-email',
    ];
    patchAsAdmin('admin.users.update', $userData, ['user' => $user])
        ->assertSessionHasErrors('email');
});

test('user update validates email uniqueness', function () {
    $user1 = User::factory()->create(['email' => 'user1@example.com']);
    $user2 = User::factory()->create(['email' => 'user2@example.com']);

    patchAsAdmin('admin.users.update', [
        'name' => 'User Two',
        'email' => 'user1@example.com',
    ], ['user' => $user2])
        ->assertSessionHasErrors('email');
});

test('user update changes password when provided', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $user = User::factory()->create();
    $oldPassword = 'OldPassword123!';
    $newPassword = 'NewPassword123!';
    $userData = [
        'name' => $user->name,
        'email' => $user->email,
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ];

    patchAsAdmin('admin.users.update', $userData, ['user' => $user]);
    $user->refresh();
    expect(Hash::check($newPassword, $user->password))->toBeTrue();
});

test('user update syncs roles', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $user = User::factory()->create();
    $role1 = Role::first();
    $role2 = Role::where('id', '!=', $role1->id)->first();
    $userData = [
        'name' => $user->name,
        'email' => $user->email,
        'roles' => [$role2->id],
    ];

    patchAsAdmin('admin.users.update', $userData, ['user' => $user]);
    $user->refresh();
    expect($user->roles->contains('id', $role2->id))->toBeTrue();
    expect($user->roles->contains('id', $role1->id))->toBeFalse();
});

test('user update without password keeps existing password', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $user = User::factory()->create(['password' => Hash::make('OriginalPassword123!')]);
    $originalHash = $user->password;
    $userData = [
        'name' => 'Updated Name',
        'email' => $user->email,
    ];

    patchAsAdmin('admin.users.update', $userData, ['user' => $user]);
    $user->refresh();
    expect($user->password)->toBe($originalHash);
});

test('user update replaces old avatar with new base64 avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create(['avatar' => 'avatars/old-avatar.jpg']);

    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
    $disk = Storage::disk('public');
    $disk->put('avatars/old-avatar.jpg', 'old content');
    $base64Image = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCwAA8A/9k=';
    $userData = [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => $base64Image,
    ];

    patchAsAdmin('admin.users.update', $userData, ['user' => $user]);

    $user->refresh();
    $disk->assertMissing('avatars/old-avatar.jpg');
    $disk->assertExists($user->avatar);
});

test('user update without avatar keeps existing avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create(['avatar' => 'avatars/keep-avatar.jpg']);
    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
    $disk = Storage::disk('public');
    $disk->put('avatars/keep-avatar.jpg', 'content');

    patchAsAdmin('admin.users.update', [
        'name' => 'Updated Name',
        'email' => $user->email,
    ], ['user' => $user]);

    $user->refresh();
    expect($user->avatar)->toBe('avatars/keep-avatar.jpg');
    $disk->assertExists('avatars/keep-avatar.jpg');
});

test('user update without roles does not clear existing roles', function () {
    $user = User::factory()->create();
    $role = Role::first();
    $user->roles()->attach($role);

    patchAsAdmin('admin.users.update', [
        'name' => 'Updated Name',
        'email' => $user->email,
    ], ['user' => $user]);

    $user->refresh();
    expect($user->roles)->toHaveCount(1);
});

// ========================================
// DESTROY TESTS
// ========================================

test('user destroy requires authentication', function () {
    $user = User::factory()->create();
    delete(route('admin.users.destroy', $user))
        ->assertRedirect(route('login'));
});

test('user destroy deletes user from database', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    $user = User::factory()->create();
    $userId = $user->id;

    $response = deleteAsAdmin('admin.users.destroy', ['user' => $user]);
    $response->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success', 'Usuário excluído com sucesso!');

    assertDatabaseMissing('users', ['id' => $userId]);
});

test('user cannot delete their own account', function () {
    $user = actingAsAdmin();
    $response = delete(route('admin.users.destroy', $user));
    $response->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('error', 'Você não pode excluir seu próprio usuário!');

    assertDatabaseHas('users', ['id' => $user->id]);
});

test('user destroy deletes avatar from storage', function () {
    Storage::fake('public');

    $user = User::factory()->create([
        'avatar' => 'avatars/test-avatar.jpg',
    ]);

    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
    $disk = Storage::disk('public');
    $disk->put('avatars/test-avatar.jpg', 'fake image content');

    deleteAsAdmin('admin.users.destroy', ['user' => $user]);
    $disk->assertMissing('avatars/test-avatar.jpg');
});

// ========================================
// DELETE AVATAR TESTS
// ========================================

test('delete avatar requires authentication', function () {
    $user = User::factory()->create();
    delete(route('admin.users.delete-avatar', $user))
        ->assertRedirect(route('login'));
});

test('delete avatar removes avatar from user', function () {
    Storage::fake('public');

    $user = User::factory()->create(['avatar' => 'avatars/test.jpg']);

    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
    $disk = Storage::disk('public');
    $disk->put('avatars/test.jpg', 'content');

    deleteAsAdmin('admin.users.delete-avatar', ['user' => $user]);
    $user->refresh();
    expect($user->avatar)->toBeNull();
});

test('delete avatar succeeds even if file not found', function () {
    /** @var \Illuminate\Foundation\Testing\TestCase $this */
    Storage::fake('public');

    $user = User::factory()->create(['avatar' => 'avatars/nonexistent.jpg']);

    $response = deleteAsAdmin('admin.users.delete-avatar', ['user' => $user]);
    $response->assertRedirect(route('admin.users.edit', $user))
        ->assertSessionHas('success', 'Foto removida com sucesso!');
});

// ========================================
// ANOMALY TESTS
// ========================================

test('user store with invalid avatar format rejected', function () {
    $userData = [
        'name' => 'User With Invalid Avatar',
        'email' => 'invalid-avatar@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'avatar' => UploadedFile::fake()->create('document.pdf', 2048),
    ];

    $response = postAsAdmin('admin.users.store', $userData);
    $response->assertSessionHasErrors('avatar');
});

test('user store prevents password confirmation mismatch', function () {
    $userData = [
        'name' => 'Password Mismatch User',
        'email' => 'mismatch@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'DifferentPassword123!',
    ];

    postAsAdmin('admin.users.store', $userData)
        ->assertSessionHasErrors('password');
});

test('user store with very long name', function () {
    $userData = [
        'name' => str_repeat('A', 256), // Exceder limite
        'email' => 'longname@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
    ];

    postAsAdmin('admin.users.store', $userData)
        ->assertSessionHasErrors('name');
});

test('user update with nonexistent role handles gracefully', function () {
    $user = User::factory()->create();

    $response = patchAsAdmin('admin.users.update', [
        'name' => 'Updated User',
        'email' => $user->email,
        'roles' => [99999], // ID que não existe
    ], ['user' => $user]);

    // Form request deve falhar validação por role inexistente
    $response->assertSessionHasErrors('roles.0');
});

test('user index with empty search returns all users', function () {
    User::factory(5)->create();

    $response = getAsAdmin('admin.users.index', ['search' => '']);
    $response->assertOk();

    $users = $response->viewData('users');
    expect($users->count())->toBeGreaterThan(0);
});

test('user cannot update to duplicate email in same record', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);

    // Tentar atualizar com o mesmo email deve funcionar
    $response = patchAsAdmin('admin.users.update', [
        'name' => 'Updated Name',
        'email' => 'user@example.com',
    ], ['user' => $user]);

    $response->assertRedirect(); // Deve redirecionar com sucesso
});

test('delete avatar from user without avatar', function () {
    $user = User::factory()->create(['avatar' => null]);
    $response = deleteAsAdmin('admin.users.delete-avatar', ['user' => $user]);
    $response->assertRedirect(route('admin.users.edit', $user))
        ->assertSessionHas('success');
});

test('delete avatar when file exists in storage', function () {
    Storage::fake('public');

    $user = User::factory()->create(['avatar' => 'avatars/existing.jpg']);
    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
    $disk = Storage::disk('public');
    $disk->put('avatars/existing.jpg', 'content');

    deleteAsAdmin('admin.users.delete-avatar', ['user' => $user]);

    $user->refresh();
    expect($user->avatar)->toBeNull();
    $disk->assertMissing('avatars/existing.jpg');
});
