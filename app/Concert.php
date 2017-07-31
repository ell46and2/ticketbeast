<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Concert extends Model
{
    protected $guarded = [];

    // Will automatically turn that column into a Carbon object.
    // So we can do things like $concert->date->format('d/m/Y')
    protected $dates = ['date'];

    public function getFormattedDateAttribute() // can retrieve formatted date by using $concert->formatted_date
    {
    	return $this->date->format('F j, Y');
    }
}
