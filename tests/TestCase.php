<?php

namespace Tests;

abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    use CreatesApplication;
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    protected function setUp()
    {
        parent::setUp();

        // throws an error if we mock a method that doesn't actually exist
        //Mockery::getConfiguration()->allowMockingNonExistentMethods(false);
    }

}
