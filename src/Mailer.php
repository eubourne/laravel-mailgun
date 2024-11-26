<?php

namespace EuBourne\LaravelMailgun;

use BackedEnum;
use Closure;
use DateInterval;
use DateTimeInterface;
use EuBourne\LaravelMailgun\Traits\InteractsWithMailable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use Symfony\Component\Mailer\Transport\TransportInterface;

class Mailer extends \Illuminate\Mail\Mailer
{
    // Helper methods to interact with mailables.
    use InteractsWithMailable;

    /**
     * Mailer manager instance.
     *
     * @var \Illuminate\Contracts\Mail\Factory $mailManager
     */
    protected \Illuminate\Contracts\Mail\Factory $mailManager;

    /**
     * Create a new Mailer instance.
     *
     * @param string $name
     * @param Factory $views
     * @param TransportInterface $transport
     * @param Dispatcher|null $events
     */
    public function __construct(string $name, Factory $views, TransportInterface $transport, ?Dispatcher $events = null)
    {
        parent::__construct($name, $views, $transport, $events);

        $this->mailManager = App::make('mail.manager');
    }

    /**
     * Send the given mailable.
     *
     * @param MailableContract $mailable
     * @return SentMessage|null
     */
    protected function sendMailable(MailableContract $mailable): ?SentMessage
    {
        $mailable = $this->setMailerIfEmpty($mailable, $this->name);

        if ($mailable instanceof ShouldQueue) {
            $mailable->queue($this->queue);
            return null;
        }

        return $this->sendOnMailer($mailable);
    }

    /**
     * Send a new message synchronously using a view.
     *
     * @param MailableContract|string|array $mailable
     * @param array $data
     * @param Closure|string|null $callback
     * @return SentMessage|null
     */
    public function sendNow($mailable, array $data = [], $callback = null): ?SentMessage
    {
        return $mailable instanceof MailableContract
            ? $this->sendOnMailer(
                $this->setMailerIfEmpty($mailable, $this->name)
            )
            : $this->send($mailable, $data, $callback);
    }

    /**
     * Queue a new mail message for sending.
     *
     * @param MailableContract|string|array $view
     * @param BackedEnum|string|null $queue
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    public function queue($view, $queue = null): mixed
    {
        if (!$view instanceof MailableContract) {
            throw new InvalidArgumentException('Only mailables may be queued.');
        }

        if (is_string($queue)) {
            $view->onQueue($queue);
        }

        return $this->setMailerIfEmpty($view, $this->name)->queue($this->queue);
    }

    /**
     * Queue a new mail message for sending after (n) seconds.
     *
     * @param DateTimeInterface|DateInterval|int $delay
     * @param MailableContract $view
     * @param string|null $queue
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    public function later($delay, $view, $queue = null): mixed
    {
        if (!$view instanceof MailableContract) {
            throw new InvalidArgumentException('Only mailables may be queued.');
        }

        return $this->setMailerIfEmpty($view, $this->name)->later(
            $delay, is_null($queue) ? $this->queue : $queue
        );
    }
}
