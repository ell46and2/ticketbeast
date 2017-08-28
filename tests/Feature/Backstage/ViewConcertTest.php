<?php

namespace Tests\Feature\Backstage;

use App\Concert;
use App\User;
use ConcertFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

class ViewConcertTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function guests_cannot_view_a_promoters_concert_list()
    {
        $response = $this->get('/backstage/concerts');

        $response->assertStatus(302);
        $response->assertRedirect('/login');	
    }

    /** @test */
    public function promoters_can_only_view_a_list_of_their_concerts()
    {
        $user = factory(User::class)->create();
        $otherUser = factory(User::class)->create();
        $publishedConcertA = ConcertFactory::createPublished(['user_id' => $user->id]);	
        $publishedConcertB = ConcertFactory::createPublished(['user_id' => $otherUser->id]);
        $publishedConcertC = ConcertFactory::createPublished(['user_id' => $user->id]);

        $unpublishedConcertA = ConcertFactory::createUnpublished(['user_id' => $user->id]);		
        $unpublishedConcertB = ConcertFactory::createUnpublished(['user_id' => $otherUser->id]);
        $unpublishedConcertC = ConcertFactory::createUnpublished(['user_id' => $user->id]);	


        
        $response = $this->actingAs($user)->get('/backstage/concerts');

        $response->assertStatus(200);

        // dd($response->original->getData()['concerts']);
        // $response->data('publishedConcerts')->assertContains($publishedConcertA);
        // $response->data('publishedConcerts')->assertContains($publishedConcertC);
        // $response->data('publishedConcerts')->assertNotContains($publishedConcertB);
        // $response->data('publishedConcerts')->assertNotContains($unpublishedConcertA);
        // $response->data('publishedConcerts')->assertNotContains($unpublishedConcertB);
        // $response->data('publishedConcerts')->assertNotContains($unpublishedConcertC);

        // $response->data('unpublishedConcerts')->assertContains($unpublishedConcertA);
        // $response->data('unpublishedConcerts')->assertContains($unpublishedConcertC);
        // $response->data('unpublishedConcerts')->assertNotContains($unpublishedConcertB);
        // $response->data('unpublishedConcerts')->assertNotContains($publishedConcertA);
        // $response->data('unpublishedConcerts')->assertNotContains($publishedConcertB);
        // $response->data('unpublishedConcerts')->assertNotContains($publishedConcertC);

        $response->data('publishedConcerts')->assertEquals([
        	$publishedConcertA,
        	$publishedConcertC
        ]);

        $response->data('unpublishedConcerts')->assertEquals([
        	$unpublishedConcertA,
        	$unpublishedConcertC
        ]);

    }
}