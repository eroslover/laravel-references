<?php

namespace Eroslover\References\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\CreatesApplication;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, WithFaker, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }
}
