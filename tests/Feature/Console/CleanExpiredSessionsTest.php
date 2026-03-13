<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('cleans expired sessions successfully', function () {
    Artisan::call('sessions:clean-expired');
    expect(Artisan::output())->toContain('Limpando sessões expiradas...');
});

it('outputs messages during cleanup', function () {
    Artisan::call('sessions:clean-expired');
    expect(Artisan::output())->toContain('Limpando sessões expiradas...');
});

it('completes without errors', function () {
    $exitCode = Artisan::call('sessions:clean-expired');
    expect($exitCode)->toBe(0);
});
