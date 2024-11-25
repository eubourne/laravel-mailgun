<?php

namespace EuBourne\LaravelMailgun\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TestMail extends Mailable
{
    use Queueable;

    public function __construct(protected array $attributes = [])
    {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->attributes['from']['address'], $this->attributes['from']['name'] ?: null),
            cc: array_map(fn(string $cc) => new Address($cc), $this->attributes['cc']),
            bcc: array_map(fn(string $bcc) => new Address($bcc), $this->attributes['bcc']),
            subject: $this->attributes['subject'],
            tags: $this->attributes['tag'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->attributes['body'],
        );
    }
}
