<?php

use App\Mail\OrderConfirmationEmail;
use App\Order;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class OrderConfirmationEmailTest extends TestCase
{
	// helper function so we can render the email to see its 'view' contents
	private function render($mailable)
	{
		$mailable->build();
		return view($mailable->view, $mailable->buildViewData())->render();
	}

    /** @test */
    public function email_contains_a_link_to_the_order_confirmation_page()
    {
        $order = factory(Order::class)->make([
        	'confirmation_number' => 'ORDERCONFIRMATION1234'
        ]);

        $email = new OrderConfirmationEmail($order);
        $rendered = $this->render($email);

        $this->assertContains(url('/orders/ORDERCONFIRMATION1234'), $rendered);

    }

    /** @test */
    public function email_has_a_subject()
    {
        $order = factory(Order::class)->make();

        $email = new OrderConfirmationEmail($order);
        
        $this->assertEquals('Your TicketBeast Order', $email->build()->subject);
    }
}