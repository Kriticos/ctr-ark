<?php

use Illuminate\Testing\TestResponse;

use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

/**
 * Assert padrão: rota de admin exige autenticação.
 */
function assertGuestIsRedirectedToLogin(string $route, array $params = []): void
{
    get(route($route, $params))
        ->assertRedirect(route('login'));
}

/**
 * GET autenticado como admin.
 */
function getAsAdmin(string $route, array $params = []): TestResponse
{
    actingAsAdmin();

    return get(route($route, $params));
}

/**
 * POST autenticado como admin.
 */
function postAsAdmin(string $route, array $data = [], array $params = []): TestResponse
{
    actingAsAdmin();

    return post(route($route, $params), $data);
}

/**
 * PATCH autenticado como admin.
 */
function patchAsAdmin(string $route, array $data = [], array $params = []): TestResponse
{
    actingAsAdmin();

    return patch(route($route, $params), $data);
}

/**
 * PUT autenticado como admin.
 */
function putAsAdmin(string $route, array $data = [], array $params = []): TestResponse
{
    actingAsAdmin();

    return put(route($route, $params), $data);
}

/**
 * DELETE autenticado como admin.
 */
function deleteAsAdmin(string $route, array $params = []): TestResponse
{
    actingAsAdmin();

    return delete(route($route, $params));
}
