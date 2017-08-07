<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Concert::class)->states('published')->create([
    		'title' => 'The Red Chord',
    	    'subtitle' => 'with Animosity and Lethargy',
    	    'date' => Carbon::parse('2017-12-13 8:00pm'),
    	    'ticket_price' => 3250,
    	    'venue' => 'The Mosh Pit',
    	    'venue_address' => '123 Example Lane',
    	    'city' => 'Laraille',
    	    'state' => 'ON',
    	    'zip' => '17916',
    	    'additional_information' => 'The concert is 18+.'
        ])->addTickets(10);
    }
}
