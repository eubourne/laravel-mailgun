<?php

namespace EuBourne\LaravelMailgun;

use EuBourne\LaravelMailgun\API\MailgunApiClient;
use EuBourne\LaravelMailgun\API\MailgunApiTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class MailgunServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MailgunApiClient::class, function () {
            $config = \Illuminate\Support\Facades\App::make('config');

            return MailgunApiClient::create(
                apiKey: $config->get('services.mailgun-api.secret'),
                endpoint: $config->get('services.mailgun-api.endpoint')
            );
        });

        $this->registerCommands();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('mailgun-api', function (array $config = []) {
            return new MailgunApiTransport($config);
        });

//        Event::listen(MessageSent::class, function (MessageSent $e) {
//            dump(['sent' => $e]);
//        });
    }

    /**
     * Register the queue commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        $this->commands([
            Console\MailSendCommand::class,
        ]);
    }
}
