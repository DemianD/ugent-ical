<?php

namespace App\Providers;

use App\Service\UGentCalendar;
use App\Service\UGentCas;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
    
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Client::class, function () {
            return new Client(['cookies' => true]);
        });
        
        $this->app->bind(UGentCas::class, function ($app) {
            return new UGentCas($app->make(Client::class));
        });
    
        $this->app->bind(UGentCalendar::class, function ($app) {
            return new UGentCalendar(
                $app->make(Client::class),
                $app->make(UGentCas::class)
            );
        });
    }
}
