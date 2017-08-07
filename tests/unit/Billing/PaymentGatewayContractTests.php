<?php

use App\Billing\PaymentFailedException;

trait PaymentGatewayContractTests
{
	// Any class that uses this trait must have getPaymentGateway() method.
	abstract protected function getPaymentGateway();

	/** @test */
    public function can_fetch_charges_created_during_a_callback()
    {
        $paymentGateway = $this->getPaymentGateway();

        $paymentGateway->charge(2000, $paymentGateway->getValidTestToken());
        $paymentGateway->charge(3000, $paymentGateway->getValidTestToken());

        $newCharges = $paymentGateway->newChargesDuring(function() use ($paymentGateway) {
            $paymentGateway->charge(4000, $paymentGateway->getValidTestToken());
            $paymentGateway->charge(5000, $paymentGateway->getValidTestToken());
        });

        $this->assertCount(2, $newCharges);
        $this->assertEquals([5000, 4000], $newCharges->all());
    }

	/** @test */
    public function charges_with_a_valid_payment_token_are_successful()
    {
        // Create a new StripePaymentGateway
        $paymentGateway = $this->getPaymentGateway();

        $newCharges = $paymentGateway->newChargesDuring(function() use ($paymentGateway) {
            $paymentGateway->charge(2500, $paymentGateway->getValidTestToken());
        });

        // Make sure only one payment was made
        $this->assertCount(1, $newCharges);

        // dd($newCharges);
        // Check the payment amount is correct
        $this->assertEquals(2500, $newCharges->sum());  
    }

    /** @test */
    public function charges_with_invalid_payment_token_fail() {

		$paymentGateway = $this->getPaymentGateway();

        $newCharges = $paymentGateway->newChargesDuring(function() use ($paymentGateway) {

            try {

                $paymentGateway->charge(2500, 'invalid-payment-token');

            } catch (PaymentFailedException $e) {
        
                return;
            }

            $this->fail('Charging with an invalid payment token did not throw a PaymentFailedException.');
        });

		
        $this->assertCount(0, $newCharges);
	
    }
}