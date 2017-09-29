<?php namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\SetCookie;

class UGentCas {
    
    private $client;
    
    private $loginUrl = 'https://login.ugent.be/login?service=https://minerva.ugent.be/plugin/cas/logincas.php';
    
    private $cookieName = 'mnrv_sid';
    
    public function __construct(Client $client)
    {
        $this->client = $client;
    }
    
    public function getLoginToken()
    {
        $this->client->request('POST', $this->loginUrl, [
            'form_params' => [
                'username' => config('custom.ugent_cas.username'),
                'password' => config('custom.ugent_cas.password'),
            ]
        ]);
        
        $cookies = $this->client->getConfig('cookies');
        
        $cookie = collect($cookies)->first(function ($cookie) {
            return $cookie->getName() === $this->cookieName;
        });
        
        return $cookie->getValue();
    }
    
    public function getLoginCookie($loginToken)
    {
        $cookie = new SetCookie;
        $cookie->setName($this->cookieName);
        $cookie->setValue($loginToken);
        $cookie->setDomain('minerva.ugent.be');
        $cookie->setPath('/');
    
        return $cookie;
    }
}