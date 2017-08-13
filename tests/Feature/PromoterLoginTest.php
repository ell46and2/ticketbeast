<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PromoterLoginTest extends TestCase
{
	use DatabaseMigrations;

    /** @test */
    public function logging_in_with_valid_credentials()
    {
        $user = factory(User::class)->create([
        	'email' => 'jane@example.com',
        	'password' => bcrypt('super-secret-password')
        ]);

        $response = $this->post('/login', [
        	'email' => 'jane@example.com',
        	'password' => 'super-secret-password'
        ]);

        // check that the user is redirected to the correct url
        $response->assertRedirect('/backstage/concerts');

        // check that a user is logged in
        $this->assertTrue(Auth::check());

        // check that the logged in user is the correct user
        $this->assertTrue(Auth::user()->is($user));
    }

    /** @test */
    public function logging_in_with_invalid_credentials()
    {
        $user = factory(User::class)->create([
            'email' => 'jane@example.com',
            'password' => bcrypt('super-secret-password')
        ]);

        $response = $this->post('/login', [
            'email' => 'jane@example.com',
            'password' => 'not-the-right-password'
        ]);

        // check that the user is redirected back to the login page
        $response->assertRedirect('/login');

        // check that the session has an error (laravel sets error for email if password and/or email is incorrect).
        $response->assertSessionHasErrors('email');

        // check that no user is logged in
        $this->assertFalse(Auth::check());

  
    }

    /** @test */
    public function logging_in_with_an_account_that_does_not_exist()
    {
        $response = $this->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'not-the-right-password'
        ]);

        // check that the user is redirected back to the login page
        $response->assertRedirect('/login');

        // check that the session has an error (laravel sets error for email if password and/or email is incorrect).
        $response->assertSessionHasErrors('email');

        // check that no user is logged in
        $this->assertFalse(Auth::check());

  
    }
}