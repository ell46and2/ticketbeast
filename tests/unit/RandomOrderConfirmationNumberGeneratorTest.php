<?php

namespace Tests\Unit;

use App\RandomOrderConfirmationNumberGenerator;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class RandomOrderConfirmationNumberGeneratorTest extends TestCase
{
    // can only contain uppercase letters and number

    // Cannot contain ambiguous characters (O and 0, I and 1)

    // Must be 24 characters long

    // All order confirmation numbers must be unique

    /** @test */
    public function must_be_24_characters_long()
    {
        $generator = new RandomOrderConfirmationNumberGenerator;

        $confirmationNumber = $generator->generate();

        $this->assertEquals(24, strlen($confirmationNumber));
    }

    /** @test */
    public function can_only_contain_uppercase_letters_and_numbers()
    {
        $generator = new RandomOrderConfirmationNumberGenerator;

        $confirmationNumber = $generator->generate();

        $this->assertRegExp('/^[A-Z0-9]+$/', $confirmationNumber);
    }

    /** @test */
    public function cannot_contain_ambiguous_characters()
    {
        $generator = new RandomOrderConfirmationNumberGenerator;

        $confirmationNumber = $generator->generate();

        $this->assertFalse(strpos($confirmationNumber, '1'));
        $this->assertFalse(strpos($confirmationNumber, 'I'));
        $this->assertFalse(strpos($confirmationNumber, '0'));
        $this->assertFalse(strpos($confirmationNumber, 'O'));
    }

    /** @test */
    public function confirmation_numbers_must_be_unique()
    {
        $generator = new RandomOrderConfirmationNumberGenerator;

        // Create an array of 100 confirmation numbers
        $confirmationNumbers = array_map(function($i) use ($generator) {
        	return $generator->generate();
        }, range(1, 100));

        // array_unique($array) - removes duplicate values from an array.
        // Check that array size is 100 (no duplicates).
        $this->assertCount(100, array_unique($confirmationNumbers));
    }
}