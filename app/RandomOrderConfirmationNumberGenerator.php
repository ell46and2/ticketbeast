<?php

namespace App;

class RandomOrderConfirmationNumberGenerator implements OrderConfirmationNumberGenerator {
	
	// Generate a unique 24 character code
	public function generate()
	{
		$pool = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

        return substr(str_shuffle(str_repeat($pool, 24)), 0, 24);
	}
}