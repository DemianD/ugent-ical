<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index() 
    {
        return response()->file(storage_path('calendar/cal.ics'));
    }
}
