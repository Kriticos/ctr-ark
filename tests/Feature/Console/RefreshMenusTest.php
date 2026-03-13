<?php

use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('refreshes menus when force option is provided', function () {
    $exitCode = Artisan::call('menus:refresh', [
        '--force' => true,
        '--no-interaction' => true,
    ]);

    expect($exitCode)->toBe(0);
    expect(Menu::count())->toBeGreaterThan(0);
});

it('does not refresh menus without force option', function () {
    Menu::factory()->count(3)->create();

    $exitCode = Artisan::call('menus:refresh', [
        '--no-interaction' => true,
    ]);

    expect($exitCode)->toBe(0);
    expect(Menu::count())->toBe(3);
});

it('outputs success message', function () {
    Artisan::call('menus:refresh', [
        '--force' => true,
        '--no-interaction' => true,
    ]);

    expect(Artisan::output())->toContain('Menus recriados com sucesso');
});
