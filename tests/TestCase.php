<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Fortify;
use Laravel\Jetstream\Jetstream;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        // Reset static route registration flags before creating the application
        // to prevent test pollution from Filament panel providers that set these to false
        Fortify::$registersRoutes = true;
        Jetstream::$registersRoutes = true;

        parent::setUp();
    }
}
