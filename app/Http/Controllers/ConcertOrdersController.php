<?php

namespace App\Http\Controllers;

use App\Concert;
use App\Billing\PaymentGateway;
use App\Billing\PaymentFailedException;
use App\Exceptions\NotEnoughTicketsException;
use Illuminate\Http\Request;

class ConcertOrdersController extends Controller
{
	private $paymentGateway;

	public function __construct(PaymentGateway $paymentGateway) {
		
		$this->paymentGateway = $paymentGateway;
	}

	public function store(Request $request, $concertId) {

		$concert = Concert::published()->findOrFail($concertId);

		$this->validate($request, [
			'email' => 'required|email',
			'ticket_quantity' => 'required|integer|min:1',
			'payment_token' => 'required'
		]);

		try {

			// Creating the order
			// create order
			$order = $concert->orderTickets($request['email'], $request['ticket_quantity']);

			// Charging the customer
			$this->paymentGateway->charge($request['ticket_quantity'] * $concert->ticket_price, $request['payment_token']);

			return response()->json([], 201);

		} catch (PaymentFailedException $e) {
			// If payment fails need to cancel order;
			$order->cancel();

			return response()->json([], 422);

		} catch (NotEnoughTicketsException $e) {

			return response()->json([], 422);
		}
	}
}
