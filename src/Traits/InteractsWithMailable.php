<?php

namespace EuBourne\LaravelMailgun\Traits;

use Illuminate\Contracts\Mail\Factory;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Mail\SentMessage;

/**
 * @property Factory $mailManager
 */
trait InteractsWithMailable
{
    /**
     * Get the mailer name from the mailable. If a given mailable does not have
     * a specific mailer set, return null.
     *
     * @param Mailable $mailable
     * @return string|null
     */
    protected function getMailerName(Mailable $mailable): ?string
    {
        return property_exists($mailable, 'mailer')
            ? $mailable->mailer
            : null;
    }

    /**
     * Set the mailer on the mailable if it is not already set.
     *
     * @param Mailable $mailable
     * @param string $mailer
     * @return Mailable
     */
    protected function setMailerIfEmpty(Mailable $mailable, string $mailer): Mailable
    {
        $mailerName = $this->getMailerName($mailable) ?: $mailer;
        return $mailable->mailer($mailerName);
    }

    /**
     * Send the given mailable using the given mailer. If no mailer is provided,
     * the mailer that is set for the mailable will be used.
     *
     * @param Mailable $mailable
     * @param string|null $mailer
     * @return SentMessage|null
     */
    protected function sendOnMailer(Mailable $mailable, string $mailer = null): ?SentMessage
    {
        $mailerName = $mailer ?: $this->getMailerName($mailable);
        $mailer = $this->mailManager->mailer($mailerName);
        return $mailable->send($mailer);
    }
}
