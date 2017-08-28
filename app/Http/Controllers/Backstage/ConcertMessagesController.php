<?php

namespace App\Http\Controllers\Backstage;

use App\AttendeeMessage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConcertMessagesController extends Controller
{
    public function create($id)
    {
        $concert = Auth::user()->concerts()->findOrFail($id);

        return view('backstage.concert-messages.new', ['concert' => $concert]);
    }

    public function store($id)
    {
    	$concert = Auth::user()->concerts()->findOrFail($id);

    	$message = $concert->attendeeMessages()->create([
    		'subject' => request('subject'),
    		'message' => request('message'),
    	]);

    	return redirect()->route('backstage.concert-messages.new', $concert)
    		->with('flash', 'Your message has been sent');
    }
}