<?php

namespace Asifulmamun\DianasmsEsms;

use Illuminate\Support\ServiceProvider;

class EsmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/esms.php', 'esms');

        $this->app->singleton(EsmsClient::class, function () {
            return new EsmsClient(
                baseUrl:       config('esms.base_url'),
                apiToken:      config('esms.api_token'),
                defaultSender: config('esms.sender_id'),
                defaultType:   config('esms.type'),
                timeout:       (int) config('esms.timeout', 10)
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/esms.php' => config_path('esms.php'),
        ], 'config');
    }
}
