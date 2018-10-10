<?php

namespace App\Console\Commands;

use App\Service\UGentCalendar;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Alarm;
use Eluceo\iCal\Component\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FetchUGentCalendar extends Command
{
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:fetch';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches the calendar from Minerva';
    
    /**
     * @var \App\Service\UGentCalendar
     */
    private $UGentCalendar;
    
    /**
     * Create a new command instance.
     *
     * @param \App\Service\UGentCalendar $UGentCalendar
     */
    public function __construct(UGentCalendar $UGentCalendar)
    {
        parent::__construct();
        
        $this->UGentCalendar = $UGentCalendar;
    }
    
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $calendar = (new Calendar('UGent'))
            ->setCalendarScale(Calendar::CALSCALE_GREGORIAN)
            ->setMethod(Calendar::METHOD_PUBLISH);
        
        $this->UGentCalendar->getEventsForAcademicYear(2018)
            ->filter(function ($event) {
                if ($event->naam === 'Fysica: mechanica, optica en moderne fysica' && $event->datum->dayOfWeek === 1) {
                    return false;
                }

                if ($event->naam === 'Fysica: mechanica, optica en moderne fysica' && $event->datum->dayOfWeek === 3 && $event->beginuur->hour === 13) {
                    return false;
                }

                if ($event->naam === 'Fysica: mechanica, optica en moderne fysica' && $event->datum->dayOfWeek === 5) {
                    return false;
                }

                if ($event->naam === 'Gevorderde algoritmen') {
                    return false;
                }
                
                return true;
                // return !data_get($event, 'groep') || in_array(config('custom.ugent.group'), $event->groep);
            })
            ->each(function ($event) use ($calendar) {
                $this->addEventToCalendar($calendar, $event);
            });
        
        File::put(storage_path('calendar/cal.ics'), $calendar->render());
        
        $this->info('Successfully fetched');
    }
    
    private function addEventToCalendar(Calendar $calendar, $event)
    {
        $vAlarm = (new Alarm())
            ->setAction(Alarm::ACTION_DISPLAY)
            ->setDescription('Herinnering')
            ->setTrigger('-PT15M');

        $groups = data_get($event, 'groep');

        $groupsFormatted = is_null($groups)
            ? ''
            : '(' .  implode(', ', $groups) . ')';

        $vEvent = (new Event())
            ->setDtStart($event->beginuur)
            ->setDtEnd($event->einduur)
            ->setLocation(data_get($event, 'locatie.lokaal'))
            ->setSummary($event->naam . '  ' . $groupsFormatted)
            ->setUseTimezone(true);

        $vEvent->addComponent($vAlarm);
        $calendar->addComponent($vEvent);
    }
}
