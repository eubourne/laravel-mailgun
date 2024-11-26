<?php

namespace EuBourne\LaravelMailgun;

use EuBourne\LaravelMailgun\API\MailgunApiClient;
use EuBourne\LaravelMailgun\API\MailgunApiTransport;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class MailgunServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerMailgunClient();
        $this->registerMailer();
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
    }

    /**
     * Register the Mailgun API client.
     *
     * @return void
     */
    protected function registerMailgunClient(): void
    {
        $this->app->singleton(MailgunApiClient::class, function () {
            $config = \Illuminate\Support\Facades\App::make('config');

            return MailgunApiClient::create(
                apiKey: $config->get('services.mailgun-api.secret'),
                endpoint: $config->get('services.mailgun-api.endpoint')
            );
        });
    }

    /**
     * Register the Illuminate mailer instance.
     *
     * @return void
     */
    protected function registerMailer(): void
    {
        $this->app->extend('mail.manager', function (\Illuminate\Mail\MailManager $manager, Application $app) {
            return new MailManager($app);
        });
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
