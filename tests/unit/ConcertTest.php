<?php

namespace Tests\Unit;

use App\Concert;
use App\Exceptions\NotEnoughTicketsException;
use App\Order;
use App\Ticket;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class ConcertTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function can_get_formatted_date()
    {
        // Create a concert with a known date - (use ->make() as don't need to save to database for this test).
        $concert = factory(Concert::class)->make([
            'date' => Carbon::parse('2017-12-01 8:00pm')
        ]);

        // Verify the date is formatted as expected
        $this->assertEquals('December 1, 2017', $concert->formatted_date);        
    }

    /** @test */
    public function can_get_formatted_start_time() {
        
        $concert = factory(Concert::class)->make([
            'date' => Carbon::parse('2017-12-01 17:00:00')
        ]);

        $this->assertEquals('5:00pm', $concert->formatted_start_time);
    }

    /** @test */
    public function can_get_ticket_price_in_dollars() {
        
        $concert = factory(Concert::class)->make([
            'ticket_price' => 6750
        ]);

        $this->assertEquals('67.50', $concert->ticket_price_in_dollars);
    }

    /** @test */
    public function concerts_with_a_published_at_date_are_published() {
        
        $publishedConcertA = factory(Concert::class)->states('published')->create();
        $publishedConcertB = factory(Concert::class)->states('published')->create();
        $unpublishedConcert = factory(Concert::class)->states('unpublished')->create();

        $publishedConcerts = Concert::published()->get();
        $this->assertTrue($publishedConcerts->contains($publishedConcertA));
        $this->assertTrue($publishedConcerts->contains($publishedConcertB));
        $this->assertFalse($publishedConcerts->contains($unpublishedConcert));
    }


    /** @test */
    public function can_add_tickets() {
        
        $concert = factory(Concert::class)->create();

        $concert->addTickets(50);

        $this->assertEquals(50, $concert->ticketsRemaining());
    }

    /** @test */
    public function tickets_remaining_does_not_include_tickets_associated_with_an_order() {
        
        $concert = factory(Concert::class)->create();

        $concert->tickets()->saveMany(factory(Ticket::class, 30)->create(['order_id' => 1]));
        $concert->tickets()->saveMany(factory(Ticket::class, 20)->create(['order_id' => null]));

        $this->assertEquals(20, $concert->ticketsRemaining());

    }

    /** @test */
    public function trying_to_reserve_more_tickets_than_remain_throws_an_exception() {
        
        $concert = factory(Concert::class)->create()->addTickets(10);

        try {

            $concert->reserveTickets(11, 'jane@example.com');

        } catch (NotEnoughTicketsException $e) {
            // assert that no order was created.
    
            $this->assertFalse($concert->hasOrderFor('jane@example.com'));
            // assert that same amount of tickets still remain
            $this->assertEquals(10, $concert->ticketsRemaining());

            return;
        }
        
        $this->fail("Order succeeded even though there were not enough tickets remaining.");
    }

    /** @test */
    public function can_reserve_available_tickets()
    {
        $concert = factory(Concert::class)->create()->addTickets(3);
        $this->assertEquals(3, $concert->ticketsRemaining());

        $reservation = $concert->reserveTickets(2, 'john@example.com');

        $this->assertCount(2, $reservation->tickets());
        $this->assertEquals('john@example.com', $reservation->email());
        $this->assertEquals(1, $concert->ticketsRemaining()); 
    }

    /** @test */
    public function cannot_reserve_tickets_that_have_already_been_purchased()
    {
        $concert = factory(Concert::class)->create()->addTickets(3);
        $order = factory(Order::class)->create();
        $order->tickets()->saveMany($concert->tickets->take(2));

        try {
            
            $reservedTickets = $concert->reserveTickets(2, 'john@example.com');
        
        } catch (NotEnoughTicketsException $e) {
            
            $this->assertEquals(1, $concert->ticketsRemaining());

            return;
        }     

        $this->fail("Reserved tickets succeeded even though the tickets were already sold");
    }

    /** @test */
    public function cannot_reserve_tickets_that_have_already_been_reserved()
    {
        $concert = factory(Concert::class)->create()->addTickets(3);
        $concert->reserveTickets(2, 'jane@example.com');

        try {
            
            $reservedTickets = $concert->reserveTickets(2, 'john@example.com');
        
        } catch (NotEnoughTicketsException $e) {
            
            $this->assertEquals(1, $concert->ticketsRemaining());

            return;
        }     

        $this->fail("Reserved tickets succeeded even though the tickets were already reserved");
    }
}
