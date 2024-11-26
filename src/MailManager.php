<?php

namespace EuBourne\LaravelMailgun;

class MailManager extends \Illuminate\Mail\MailManager
{
    /**
     * Build a new mailer instance.
     *
     * @param array $config
     * @return Mailer
     */
    public function build($config): Mailer
    {
        $mailer = new Mailer(
            $config['name'] ?? 'ondemand',
            $this->app['view'],
            $this->createSymfonyTransport($config),
            $this->app['events']
        );

        if ($this->app->bound('queue')) {
            $mailer->setQueue($this->app['queue']);
        }

        return $mailer;
    }
}
