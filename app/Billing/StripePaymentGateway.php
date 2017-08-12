<?php

namespace App\Billing;

use App\Billing\PaymentFailedException;
use Stripe\Error\InvalidRequest;


class StripePaymentGateway implements PaymentGateway
{
	private $apiKey;

    const TEST_CARD_NUMBER = '4242424242424242';


	public function __construct($apiKey)
	{
	    $this->apiKey = $apiKey;
	}

    private function lastCharge()
    {
        return array_first(\Stripe\Charge::all(
            ['limit' => 1],
            ['api_key' => $this->apiKey]
        )['data']);
    }

    private function newChargesSince($charge = null)
    {
        $newCharges = \Stripe\Charge::all(
            [
                'ending_before' => $charge ? $charge->id : null, //Gets only records from after this record's id.
            ],
            ['api_key' => $this->apiKey]
        )['data'];

        return collect($newCharges);
    }

    public function charge($amount, $token)
    {
    	try {
    		
    		$stripeCharge = \Stripe\Charge::create([
			  	"amount" => $amount,
			  	"currency" => "usd",
			  	"source" => $token
			], ['api_key' => $this->apiKey]);

            return new Charge([
                'amount' => $stripeCharge['amount'],
                'card_last_four' => $stripeCharge['source']['last4']
            ]);
    	
    	} catch (InvalidRequest $e) {
    		throw new PaymentFailedException;
    		
    	} 	
    }

    public function getValidTestToken($cardNumber = self::TEST_CARD_NUMBER)
    {
        return \Stripe\Token::create([
            "card" => [
                "number" => $cardNumber,
                "exp_month" => 1,
                "exp_year" => date('Y') + 1,
                "cvc" => "123"
            ]
        ], ['api_key' => $this->apiKey])->id;
    }

    public function newChargesDuring($callback)
    {
        $lastCharge = $this->lastCharge();

        $callback();

        return $this->newChargesSince($lastCharge)->map(function ($stripeCharge) {
            return new Charge([
                'amount' => $stripeCharge['amount'],
                'card_last_four' => $stripeCharge['source']['last4']
            ]);
        });
    }
}
