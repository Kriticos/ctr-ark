<?php

use App\Models\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;

/**
 * @param  string[]|array{
 *     roles?: string[],
 *     attributes?: array<string, mixed>
 * }  $roles
 */
function actingAsUser(array $roles = []): User
{
    if (array_is_list($roles)) {
        $options = ['roles' => $roles];
    } else {
        $options = $roles;
    }

    $attributes = $options['attributes'] ?? [];
    $roleSlugs = $options['roles'] ?? [];

    /** @var User $user */
    $user = User::factory()->create($attributes);

    if ($roleSlugs !== []) {
        $roleModels = Role::whereIn('slug', $roleSlugs)->get();
        $user->roles()->attach($roleModels);
    }

    actingAs($user);

    return $user;
}

function actingAsAdmin(array $attributes = []): User
{
    return actingAsUser([
        'roles' => ['admin'],
        'attributes' => $attributes,
    ]);
}
