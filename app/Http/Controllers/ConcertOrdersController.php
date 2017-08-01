<?php

namespace App\Http\Controllers;

use App\Concert;
use App\Billing\PaymentGateway;
use App\Billing\PaymentFailedException;
use Illuminate\Http\Request;

class ConcertOrdersController extends Controller
{
	private $paymentGateway;

	public function __construct(PaymentGateway $paymentGateway) {
		
		$this->paymentGateway = $paymentGateway;
	}

	public function store(Request $request, $concertId) {

		$this->validate($request, [
			'email' => 'required|email',
			'ticket_quantity' => 'required|integer|min:1',
			'payment_token' => 'required'
		]);

		try {
			// Charging the customer
			$concert = Concert::find($concertId);
			
			$this->paymentGateway->charge($request['ticket_quantity'] * $concert->ticket_price, $request['payment_token']);

			// Creating the order
			// create order
			$order = $concert->orderTickets($request['email'], $request['ticket_quantity']);

			return response()->json([], 201);

		} catch (PaymentFailedException $e) {

			return response()->json([], 422);
		}
	}
}
