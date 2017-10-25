<?php namespace App\Service;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Promise\Promise;
use function GuzzleHttp\Promise\settle;
use Illuminate\Support\Facades\Cache;

class UGentCalendar {
    
    private $UGentCas;
    
    private $calendarUrl = 'http://minerva.ugent.be/main/curriculum/centauro_user.php';
    
    private $loginToken;
    
    public function __construct(Client $client, UGentCas $UGentCas)
    {
        $this->client = $client;
        $this->UGentCas = $UGentCas;
    }
    
    public function getEventsForAcademicYear($year)
    {
        $this->provideToken();
        $nextYear = $year + 1;        
        
        $promises = [
            $this->fetchEvents($year, 9),
            $this->fetchEvents($year, 10),
            $this->fetchEvents($year, 11),
            $this->fetchEvents($year, 12),
            $this->fetchEvents($nextYear, 1),
            $this->fetchEvents($nextYear, 2),
            $this->fetchEvents($nextYear, 3),
            $this->fetchEvents($nextYear, 4),
            $this->fetchEvents($nextYear, 5),
            $this->fetchEvents($nextYear, 6),
            $this->fetchEvents($nextYear, 7),
            $this->fetchEvents($nextYear, 8),
            $this->fetchEvents($nextYear, 9),
        ];
        
        $results = settle($promises)->wait();

        return collect($results)
            ->map(function ($response) {
                $value = $response['value']->getBody()->getContents();
             
                $fixed = str_replace('{"resulttype":"success","activiteiten":', '', $value);
                $fixed = str_replace('[]}', '', $fixed);
                $fixed = str_replace(']}[', ',', $fixed);
                $fixed = rtrim($fixed, '}');
                
                return json_decode($fixed);
            })
            ->reject(function ($events) {
                return is_null($events);
            })
            ->flatMap(function ($events) {
                return $this->mapEvents($events);
            });
    }
    
    private function provideToken()
    {
        $this->loginToken = Cache::remember('ugent_cas_token', 60, function () {
            return $this->UGentCas->getLoginToken();
        });
    }
    
    private function fetchEvents($year, $month)    
    {
        return $this->client->requestAsync('GET', $this->calendarUrl, [
                'cookies' => $this->getCookieJar(),
                'query'   => [
                    'year'  => $year,
                    'month' => $month,
                ]
            ]
        );
    }
    
    private function getCookieJar()
    {
        $jar = new CookieJar(true);
        
        $jar->setCookie($this->UGentCas->getLoginCookie($this->loginToken));
        
        return $jar;
    }
    
    private function mapEvents($events)
    {
        return collect($events)
            // Edge case ...
            ->filter(function($event) {
                return $event !== "";
            })
            ->map(function ($event) {
                $event->beginuur = Carbon::createFromFormat('Y-m-d H:i:s.000', $event->datum . ' ' . $event->beginuur);
                $event->einduur = Carbon::createFromFormat('Y-m-d H:i:s.000', $event->datum . ' ' . $event->einduur);
                $event->datum = Carbon::createFromFormat('Y-m-d', $event->datum);
                
                $groups = data_get($event, 'groep');
                
                if (!is_null($groups))
                {
                    $event->groep = $this->parseGroups($groups);
                }
                
                return $event;
            });
    }
    
    private function parseGroups($groups)
    {
        return collect(explode(',', $groups))
            ->map(function ($group) {
                return trim($group);
            })
            ->map(function ($group) {
                if (!str_contains($group, '-'))
                {
                    return $group;
                }
                
                $groups = explode('-', $group);
                
                for ($i = $groups[0] + 1; $i < $groups[1]; $i++)
                {
                    array_push($groups, $i);
                }
                
                return $groups;
            })
            ->flatten()
            ->map(function ($group) {
                return intval($group);
            })
            ->all();
    }
}