<?php

namespace Tests\Unit;

use App\Concert;
use App\Facades\TicketCode;
use App\Order;
use App\Ticket;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class TicketTest extends TestCase
{
	use DatabaseMigrations;

	/** @test */
	public function a_ticket_can_be_reserved()
	{
		$ticket = factory(Ticket::class)->create();
		$this->assertNull($ticket->reserved_at);

		$ticket->reserve();

		$this->assertNotNull($ticket->fresh()->reserved_at);

	}

	/** @test */
	public function a_ticket_can_be_released() {
		
		$ticket = factory(Ticket::class)->states('reserved')->create();
		$this->assertNotNull($ticket->reserved_at);

		$ticket->release();

		$this->assertNull($ticket->fresh()->reserved_at);
	}

	/** @test */
	public function a_ticket_can_be_claimed_for_an_order()
	{
	    $order = factory(Order::class)->create();
	    $ticket = factory(Ticket::class)->create(['code' => null]);
	    TicketCode::shouldReceive('generateFor')->with($ticket)->andReturn('TICKETCODE1');

	    $ticket->claimFor($order);

	    // Assert that the ticket is saved to the order - by checking the ticket_id is in the collection of tickets belonging to the order.
	    $this->assertContains($ticket->id, $order->tickets->pluck('id'));


	    // Assert that the ticket had the expected ticket code generated
	    $this->assertEquals('TICKETCODE1', $ticket->code);
	}
}