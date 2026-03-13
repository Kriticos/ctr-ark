<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** @var User */
    protected $user;

    protected function setup(): void
    {
        parent::setUp();

        // Desativa o vite em testes
        $this->withoutVite();
    }
}
