<?php

namespace Tests\Feature;

use App\Billing\FakePaymentGateway;
use App\Billing\PaymentGateway;
use App\Concert;
use App\Facades\OrderConfirmationNumber;
use App\Facades\TicketCode;
use App\Mail\OrderConfirmationEmail;
use App\OrderConfirmationNumberGenerator;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PurchaseTicketsTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp() {

        parent::setUp();

        $this->paymentGateway = new FakePaymentGateway;

        /*
            You may also bind an existing object instance into the container using the instance method. The given instance will always be returned on subsequent calls into the container:
        */
        // Calls to the PaymentGateway class (for testing purposes) will return the $paymentgateway (FakePaymentGateway)
        $this->app->instance(PaymentGateway::class, $this->paymentGateway);

        // Use Laravels Mail Fake
        Mail::fake();
    }


    // helper function to reduce duplication in tests.
    private function orderTickets($concert, $params) {
        
        $savedRequest = $this->app['request'];

        $this->response = $this->json('POST', "/concerts/{$concert->id}/orders", $params);

        $this->app['request'] = $savedRequest;
    }

    // helper function to reduce duplication in tests.
    private function assertValidationError($field) {
        // failed validation will return status code 422
        $this->response->assertStatus(422);
        // the failed field should be in the decodeResponseJson array.
        $this->assertArrayHasKey($field, $this->response->decodeResponseJson());
    }

    /** @test */
    function customer_can_purchase_tickets_to_a_published_concert()
    {
        // Create a mock orderConfirmationNumberGenerator so we can determine the confirmation number for our test
        // $orderConfirmationNumberGenerator = Mockery::mock(OrderConfirmationNumberGenerator::class, [
        //     'generate' => 'ORDERCONFIRMATION123'
        // ]);
        // // Use the mock instead of the real thing for our test.
        // $this->app->instance(OrderConfirmationNumberGenerator::class, $orderConfirmationNumberGenerator);

        // Preprogram the facade to return 'ORDERCONFIRMATION123' for the 'generate' method.
        OrderConfirmationNumber::shouldReceive('generate')->andReturn('ORDERCONFIRMATION123');

        TicketCode::shouldReceive('generateFor')->andReturn('TICKETCODE1', 'TICKETCODE2', 'TICKETCODE3');

        // Create a concert
        $concert = factory(Concert::class)->states('published')->create(['ticket_price' => 3250])->addTickets(3);

        // Purchase concert tickets
        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        // Assert route
        $this->response->assertStatus(201);

        // Assert that the response Json contains the following:
        $this->response->assertJson([
            'confirmation_number' => 'ORDERCONFIRMATION123',
            'email' => 'john@example.com',
            'amount' => 9750,
            'tickets'=> [
                ['code' => 'TICKETCODE1'],
                ['code' => 'TICKETCODE2'],
                ['code' => 'TICKETCODE3']
            ]
        ]);

        // Make sure customer was charged the correct amount
        $this->assertEquals(9750, $this->paymentGateway->totalCharges());

        // Make sure that an order exists for this customer
        // $order = $concert->orders()->where('email', 'john@example.com')->first();
        // $this->assertNotNull($order);

        $order = $concert->ordersFor('john@example.com')->first();

        $this->assertTrue($concert->hasOrderFor('john@example.com'));
        $this->assertEquals(3, $order->ticketQuantity());

        // Check email was sent
        Mail::assertSent(OrderConfirmationEmail::class, function($mail) use ($order) {

            return $mail->hasTo('john@example.com')
                && $mail->order->id == $order->id;
        });
    }

    /** @test */
    public function cannot_purchase_tickets_to_unpublished_concert() {
        
        $concert = factory(Concert::class)->states('unpublished')->create()->addTickets(3);

        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        $this->response->assertStatus(404);
        $this->assertFalse($concert->hasOrderFor('john@example.com'));
        // Make sure customer was not charged 
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
    }

    /** @test */
    public function cannot_purchase_more_tickets_than_remain() {
        
        $concert = factory(Concert::class)->states('published')->create();

        // Add 50 tickets for sale - tickets are added to tickets table but not assigned to a customer yet.
        $concert->addTickets(50);

        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 51,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        $this->response->assertStatus(422);
        $this->assertFalse($concert->hasOrderFor('john@example.com'));
        // Make sure customer was not charged 
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
        $this->assertEquals(50, $concert->ticketsRemaining());
    }

    /** @test */
    public function cannot_purchase_tickets_another_customer_is_already_trying_to_purchase()
    {
        $concert = factory(Concert::class)->states('published')->create(['ticket_price' => 1200])->addTickets(3);

        $this->paymentGateway->beforeFirstCharge(function($paymentGateway) use ($concert) {

            $this->orderTickets($concert, [
                'email' => 'personB@example.com',
                'ticket_quantity' => 1,
                'payment_token' => $this->paymentGateway->getValidTestToken()
            ]);


            $this->response->assertStatus(422);
            $this->assertFalse($concert->hasOrderFor('personBexample.com'));
            // Make sure customer was not charged 
            $this->assertEquals(0, $this->paymentGateway->totalCharges());
        });

        $this->orderTickets($concert, [
            'email' => 'personA@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        $this->assertEquals(3600, $this->paymentGateway->totalCharges());
        $this->assertTrue($concert->hasOrderFor('personA@example.com'));
        $this->assertEquals(3, $concert->ordersFor('personA@example.com')->first()->ticketQuantity());
        
    }

    /** @test */
    public function email_is_required_to_purchase_tickets() {
        
        $concert = factory(Concert::class)->states('published')->create();

        $this->orderTickets($concert, [
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        $this->assertValidationError('email');
    }

    /** @test */
    public function email_must_be_valid_to_purchase_tickets() {
        
        $concert = factory(Concert::class)->states('published')->create();

        $this->orderTickets($concert, [
            'email' => 'not-an-email-address',
            'ticket_quantity' => 1,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        $this->assertValidationError('email');
    }

    /** @test */
    public function ticket_quantity_is_required_to_purchase_tickets() {
        
        $concert = factory(Concert::class)->states('published')->create();

        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        $this->assertValidationError('ticket_quantity');
    }

    /** @test */
    public function ticket_quantity_must_be_at_least_1_to_purchase_tickets() {
        
        $concert = factory(Concert::class)->states('published')->create();

        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 0,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        $this->assertValidationError('ticket_quantity');
    }

    /** @test */
    public function payment_token_is_required() {
        
        $concert = factory(Concert::class)->states('published')->create();

        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 1
        ]);

        $this->assertValidationError('payment_token');
    }

    /** @test */
    public function an_order_is_not_created_if_payment_fails() {
        
        $concert = factory(Concert::class)->states('published')->create(['ticket_price' => 3250])->addTickets(3);

        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => 'invalid-payment-token'
        ]);

        $this->response->assertStatus(422);
        $this->assertFalse($concert->hasOrderFor('john@example.com'));
        $this->assertEquals(3, $concert->ticketsRemaining());
    }
}
