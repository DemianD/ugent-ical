<?php namespace App\Service;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Cache;

class UGentCalendar {
    
    private $UGentCas;
    
    private $calendarUrl = 'http://minerva.ugent.be///main/curriculum/centauro_user.php';
    
    private $loginToken;
    
    public function __construct(Client $client, UGentCas $UGentCas)
    {
        $this->client = $client;
        $this->UGentCas = $UGentCas;
    }
    
    public function getEvents($year, $month)
    {
        $this->loginToken = Cache::remember('ugent_cas_token', 60, function () {
            return $this->UGentCas->getLoginToken();
        });
        
        $events = $this->fetchEvents($year, $month);
        
        return $this->mapEvents($events);
    }
    
    private function fetchEvents($year, $month)
    {
        $response = $this->client->request('GET', $this->calendarUrl, [
                'cookies' => $this->getCookieJar(),
                'query'   => [
                    'year'  => $year,
                    'month' => $month,
                ]
            ]
        );
        
        return json_decode($response->getBody());
    }
    
    private function getCookieJar()
    {
        $jar = new CookieJar(true);
        $jar->setCookie($this->UGentCas->getLoginCookie($this->loginToken));
        
        return $jar;
    }
    
    private function mapEvents($events)
    {
        return collect($events->activiteiten)
            ->map(function ($event) {
                $event->datum = Carbon::createFromFormat('Y-m-d', $event->datum);
                $event->beginuur = Carbon::createFromFormat('H:i:s.000', $event->beginuur);
                $event->einduur = Carbon::createFromFormat('H:i:s.000', $event->einduur);
                
                $groups = data_get($event, 'groep');
                
                if ($groups !== null)
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
            ->all();
    }
}