<?php

namespace ParvizJ\SibSms;

use Illuminate\Support\ServiceProvider;
use ParvizJ\SibSms\Clients\SibSmsClient;

class SibSmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/sib-sms.php', 'sib-sms');

        $this->app->singleton(SibSmsClient::class, function () {
            return new SibSmsClient(config('sib-sms'));
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/sib-sms.php' => config_path('sib-sms.php'),
        ], 'sib-sms-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'sib-sms-migrations');
    }
}
