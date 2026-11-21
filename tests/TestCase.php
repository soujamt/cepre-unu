<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Los tests no dependen de los assets compilados: sin esto, una página nueva
     * falla si el manifest de Vite no está al día.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
