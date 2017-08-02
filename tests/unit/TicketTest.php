<?php

use App\Concert;
use App\Order;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TicketTest extends TestCase
{
	use DatabaseMigrations;

	/** @test */
	public function a_ticket_can_be_released() {
		
		$concert = factory(Concert::class)->states('published')->create();

		$concert->addTickets(1);

		$order = $concert->orderTickets('jane@example.com', 1);

		$ticket = $order->tickets()->first();

		$this->assertEquals($order->id, $ticket->order_id);

		$ticket->release();

		// fresh() - Reloads a fresh model instance from the database.
		// So we can be sure that order_id is null in the db, and not just on the $ticket model.
		$this->assertNull($ticket->fresh()->order_id); 
	}
}