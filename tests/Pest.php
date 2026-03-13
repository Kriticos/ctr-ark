<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Cria e retorna um usuário autenticado para os testes.
 */
function createUser(): User
{
    return User::factory()->create();
}

/**
 * Cria e retorna um usuário com as roles especificadas.
 */
function createUserWithRoles(string ...$roleSlugs): User
{
    $user = User::factory()->create();

    foreach ($roleSlugs as $slug) {
        $role = Role::where('slug', $slug)->firstOrFail();
        $user->roles()->attach($role);
    }

    return $user;
}

/**
 * Autentica o usuário fornecido para os testes.
 */
function login(User $user): User
{
    \Pest\Laravel\actingAs($user);

    return $user;
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
|
| Funções auxiliares para facilitar a escrita de testes.
|
*/

require_once __DIR__.'/Support/AuthHelpers.php';
require_once __DIR__.'/Support/AdminRequestHelpers.php';

/*
|--------------------------------------------------------------------------
| Desabilitar o Vite em testes
|--------------------------------------------------------------------------
|
| Desabilita o Vite durante a execução dos testes para evitar problemas relacionados ao carregamento de assets.
|
*/

use Illuminate\Support\Facades\Vite;

beforeEach(function () {
    Vite::useHotFile(null);
    Vite::useBuildDirectory(''); // impede leitura do manifest
});
