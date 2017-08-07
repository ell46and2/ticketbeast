<?php

namespace App;

use App\Exceptions\NotEnoughTicketsException;
use App\Reservation;
use Illuminate\Database\Eloquent\Model;

class Concert extends Model
{
    protected $guarded = [];

    // Will automatically turn that column into a Carbon object.
    // So we can do things like $concert->date->format('d/m/Y')
    protected $dates = ['date'];


    public function orders() {
        
        return $this->belongsToMany(Order::class, 'tickets');
    }

    public function tickets() {
        
        return $this->hasMany(Ticket::class);
    }


    /* 
    	Local scopes allow you to define common sets of constraints that you may easily re-use throughout your application. For example, you may need to frequently retrieve all users that are considered "popular". To define a scope, simply prefix an Eloquent model method with scope.

		Scopes should always return a query builder instance:

		Once the scope has been defined, you may call the scope methods when querying the model. However, you do not need to include the scope prefix when calling the method. e.g. Concert::published()
	*/
    public function scopePublished($query) {
    	
    	return $query->whereNotNull('published_at');
    }

    public function getFormattedDateAttribute() // can retrieve formatted date by using $concert->formatted_date
    {
    	return $this->date->format('F j, Y');
    }

    public function getFormattedStartTimeAttribute() {  // can retrieve formatted start time by using $concert->formatted_start_time
    	
    	return $this->date->format('g:ia');
    }

    public function getTicketPriceInDollarsAttribute() { // can retrieve ticket price in dollars by using $concert->ticket_price_in_dollars
    	
    	return number_format($this->ticket_price / 100, 2);
    }

    public function orderTickets($email, $ticketQuantity) {
    	
    	$tickets = $this->findTickets($ticketQuantity);

        return $this->createOrder($email, $tickets);
    }

    public function findTickets($quantity)
    {
        $tickets = $this->tickets()->available()->take($quantity)->get();

        if ($tickets->count() < $quantity) {
            
            throw new NotEnoughTicketsException;     
        }

        return $tickets;
    }

    public function reserveTickets($quantity, $email)
    {
        $tickets = $this->findTickets($quantity)->each(function($ticket) {
            $ticket->reserve();
        });

        return new Reservation($tickets, $email);
    }

    public function createOrder($email, $tickets)
    {
        return Order::forTickets($tickets, $email, $tickets->sum('price'));
    }

    public function addTickets($quantity) {
        
        foreach (range(1, $quantity) as $i) {
            $this->tickets()->create([]);
        } 

        return $this;   
    }

    public function ticketsRemaining() {
        
        return $this->tickets()->available()->count();
    }

    public function hasOrderFor($customerEmail) {
        
        return $this->orders()->where('email', $customerEmail)->count() > 0;
    }

    public function ordersFor($customerEmail) {
        
        return $this->orders()->where('email', $customerEmail)->get();
    }
}
