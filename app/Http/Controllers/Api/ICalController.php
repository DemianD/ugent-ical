<?php

namespace App\Http\Controllers\Api;

use App\Service\UGentCalendar;
use App\Service\UGentCas;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class ICalController extends Controller {
    
    private $UGentCalendar;
    
    public function __construct(UGentCalendar $UGentCalendar)
    {
        $this->UGentCalendar = $UGentCalendar;
    }
    
    public function index()
    {
        $events = $this->UGentCalendar->getEvents('2017', '9');
        
        return $events;
    }
}
