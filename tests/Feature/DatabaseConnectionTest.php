<?php

use Illuminate\Support\Facades\DB;

test('application is running in testing environment', function () {
    expect(app()->environment())->toBe('testing');
});

test('can connect to configured testing database', function () {
    $result = DB::select('SELECT DATABASE() as db');
    $expectedDatabase = config('database.connections.mysql.database');
    expect($result[0]->db)->toBe($expectedDatabase);
});
