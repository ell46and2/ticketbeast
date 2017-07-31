<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Concert extends Model
{
    protected $guarded = [];

    // Will automatically turn that column into a Carbon object.
    // So we can do things like $concert->date->format('d/m/Y')
    protected $dates = ['date'];


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
}