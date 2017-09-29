<?php

namespace App\Http\Controllers\Api;

use App\Service\UGentCalendar;
use App\Service\UGentCas;
use Carbon\Carbon;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
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
        $calendar = new Calendar('UGent');
        
        $this->UGentCalendar->getEventsForAcademicYear(2017)
            ->filter(function ($event) {
                return !data_get($event, 'groep') || in_array(config('custom.ugent.group'), $event->groep);
            })
            ->each(function ($event) use ($calendar) {
                $this->addEventToCalendar($calendar, $event);
            });
        
        return response($calendar->render())
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="cal.ics"');
    }
    
    private function addEventToCalendar(Calendar $calendar, $event)
    {
        $vEvent = (new Event())
            ->setDtStart($event->beginuur)
            ->setDtEnd($event->einduur)
            ->setLocation(data_get($event, 'locatie.lokaal'))
            ->setSummary($event->naam)
            ->setUseTimezone(true);
        
        $calendar->addComponent($vEvent);
    }
}
