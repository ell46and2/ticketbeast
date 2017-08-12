<?php

namespace App\Facades;

use App\OrderConfirmationNumberGenerator;
use Illuminate\Support\Facades\Facade;

class OrderConfirmationNumber extends Facade
{
	public static function getFacadeAccessor()
	{
	    return OrderConfirmationNumberGenerator::class;
	}
}