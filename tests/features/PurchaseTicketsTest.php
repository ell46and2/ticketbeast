<?php

use App\Concert;
use App\Billing\FakePaymentGateway;
use App\Billing\PaymentGateway;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
    }


    // helper function to reduce duplication in tests.
    private function orderTickets($concert, $params) {
        $this->json('POST', "/concerts/{$concert->id}/orders", $params);
    }

    // helper function to reduce duplication in tests.
    private function assertValidationError($field) {
        // failed validation will return status code 422
        $this->assertResponseStatus(422);
        // the failed field should be in the decodeResponseJson array.
        $this->assertArrayHasKey($field, $this->decodeResponseJson());
    }

    /** @test */
    function customer_can_purchase_tickets_to_a_published_concert()
    {
        // Create a concert
        $concert = factory(Concert::class)->states('published')->create(['ticket_price' => 3250])->addTickets(3);

        // Purchase concert tickets
        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        // Assert route
        $this->assertResponseStatus(201);

        // Make sure customer was charged the correct amount
        $this->assertEquals(9750, $this->paymentGateway->totalCharges());

        // Make sure that an order exists for this customer
        // $order = $concert->orders()->where('email', 'john@example.com')->first();
        // $this->assertNotNull($order);

        $this->assertTrue($concert->hasOrderFor('john@example.com'));
        $this->assertEquals(3, $concert->ordersFor('john@example.com')->first()->tickets()->count());
    }

    /** @test */
    public function cannot_purchase_tickets_to_unpublished_concert() {
        
        $concert = factory(Concert::class)->states('unpublished')->create();

        $concert->addTickets(3);

        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        $this->assertResponseStatus(404);
        $this->assertEquals(0, $concert->orders()->count());
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

        $this->assertResponseStatus(422);
        $this->assertEquals(0, $concert->orders()->count());
        // Make sure customer was not charged 
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
        $this->assertEquals(50, $concert->ticketsRemaining());
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
        
        $concert = factory(Concert::class)->states('published')->create(['ticket_price' => 3250]);

        $concert->addTickets(3);

        $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => 'invalid-payment-token'
        ]);

        $this->assertResponseStatus(422);
        $order = $concert->orders()->where('email', 'john@example.com')->first();
        $this->assertNull($order);
    }
}
