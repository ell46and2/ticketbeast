<?php

namespace Tests;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\TestResponse;
use PHPUnit\Framework\Assert;

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

        // Gets the response data that is sent with the view.
        // i.e return view('index', ['concert' => $concert]);
        // We can get that $concert data by passing in 'concert' as the key.
        TestResponse::macro('data', function($key) {
            return $this->original->getData()[$key];
        });

        // Assert that the view rendered is equal to the view name passed in.
        TestResponse::macro('assertViewIs', function($name) {
            Assert::assertEquals($name, $this->original->name);
        });

        // Add a assertContains method to the Eloquent collection
        EloquentCollection::macro('assertContains', function($value) {
            Assert::assertTrue($this->contains($value), 
            "Failed asserting that the collection contained the specified value");
        });

        // Add a assertNotContains method to the Eloquent collection
        EloquentCollection::macro('assertNotContains', function($value) {
            Assert::assertFalse($this->contains($value), 
            "Failed asserting that the collection does not contain the specified value");
        });

        // Check that two collections are the same
        EloquentCollection::macro('assertEquals', function($items) {

            Assert::assertEquals(count($this), count($items));

            $this->zip($items)->each(function($pair) {
                list($a, $b) = $pair;
                Assert::assertTrue($a->is($b));
            });
        });

        // throws an error if we mock a method that doesn't actually exist
        //Mockery::getConfiguration()->allowMockingNonExistentMethods(false);
    }

    protected function from($url)
    {
        // sets the url we are posting from, so that when validation fails and redirects back
        // it goes to the correct url.
        session()->setPreviousUrl(url($url));

        return $this;
    }

}
