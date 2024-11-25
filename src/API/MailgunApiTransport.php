<?php

namespace EuBourne\LaravelMailgun\API;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Part\DataPart;

class MailgunApiTransport extends AbstractTransport
{
    /**
     * Persistent fields
     *
     * @var array
     */
    const array PERSISTENT_FIELDS = [
        'subject', 'from', 'to', 'cc', 'bcc'
    ];

    /**
     * Mailgun API client
     *
     * @var MailgunApiClient|null
     */
    protected ?MailgunApiClient $client = null;

    /**
     * Domain
     *
     * @var string|mixed
     */
    protected string $domain;

    /**
     * @param array $config
     */
    public function __construct(protected array $config = [])
    {
        parent::__construct(
            logger: isset($this->config['logger']) ? \Illuminate\Support\Facades\Log::channel($this->config['logger']) : null
        );

        $this->domain = $this->config['domain'];

        try {
            $this->client = \Illuminate\Support\Facades\App::make(MailgunApiClient::class);
        } catch (BindingResolutionException $e) {
            throw new TransportException(message: 'Cannot instantiate Mailgun API client: ' . $e->getMessage(), previous: $e);
        }
    }

    /**
     * Send the message
     *
     * @param SentMessage $message
     * @return void
     */
    protected function doSend(SentMessage $message): void
    {
        try {
            /** @var Message $originalMessage */
            $originalMessage = $message->getOriginalMessage();
            $email = MessageConverter::toEmail($originalMessage);

            $this->client->messages()->send(
                domain: $this->domain,
                params: $this->getPayload($email)
            );
        } catch (ClientExceptionInterface|Exception $e) {
            throw new TransportException(sprintf('Unable to send message with the "%s" transport: ', __CLASS__) . $e->getMessage(), 0, $e);
        }
    }

    public function __toString(): string
    {
        return "mailgun-api";
    }

    /**
     * Get the payload for the email
     *
     * @param Email $email
     * @return array
     */
    protected function getPayload(Email $email): array
    {
        $payload = [
            'from' => $email->getFrom()[0]->toString(),
            'to' => implode(', ', array_map(fn(Address $recipient) => $recipient->toString(), $email->getTo())),
            'subject' => $email->getSubject(),
            'text' => $email->getTextBody(),
            'html' => $email->getHtmlBody(),
        ];

        $this->addHeaders($email, $payload);
        $this->addCcBcc($email, $payload);
        $this->addAttachments($email, $payload);
        $this->addTags($email, $payload);
        $this->addMetadata($email, $payload);

        return $payload;
    }

    /**
     * Add custom headers to the payload
     *
     * @param Email $email
     * @param array $payload
     * @return void
     */
    protected function addHeaders(Email $email, array &$payload): void
    {
        foreach ($email->getHeaders()->all() as $header) {
            $headerName = $header->getName();
            if (!($header instanceof TagHeader) && !($header instanceof MetadataHeader) && !in_array(strtolower($headerName), static::PERSISTENT_FIELDS)) {
                $headerValue = $header->getBodyAsString();
                $payload["h:{$headerName}"] = $headerValue;
            }
        }
    }

    /**
     * Add CC and BCC recipients if present
     *
     * @param Email $email
     * @param array $payload
     * @return void
     */
    protected function addCcBcc(Email $email, array &$payload): void
    {
        if ($cc = $email->getCc()) {
            $payload['cc'] = implode(', ', array_map(fn($ccRecipient) => $ccRecipient->toString(), $cc));
        }
        if ($bcc = $email->getBcc()) {
            $payload['bcc'] = implode(', ', array_map(fn($bccRecipient) => $bccRecipient->toString(), $bcc));
        }
    }

    /**
     * Handle attachments and inline attachments
     *
     * @param Email $email
     * @param array $payload
     * @return void
     */
    protected function addAttachments(Email $email, array &$payload): void
    {
        foreach ($email->getAttachments() as $attachment) {
            if ($attachment instanceof DataPart) {
                $attachmentData = [
                    'fileContent' => $attachment->getBody(),
                    'filename' => $attachment->getFilename(),
                    'contentType' => $attachment->getContentType(),
                ];

                if (strtolower($attachment->getDisposition()) == "inline") {
                    $attachmentData['cid'] = $attachment->getContentId();
                    $payload['inline'][] = $attachmentData;
                } else {
                    $payload['attachment'][] = $attachmentData;
                }
            }
        }
    }

    /**
     * Add tags
     *
     * @param Email $email
     * @param array $payload
     * @return void
     */
    protected function addTags(Email $email, array &$payload): void
    {
        $tags = [];

        foreach ($email->getHeaders()->all() as $header) {
            if ($header instanceof TagHeader) {
                $tags[] = $header->getBodyAsString();
            }
        }

        if (count($tags)) {
            $payload['o:tag'] = $tags;
        }
    }

    /**
     * Add metadata
     *
     * @param Email $email
     * @param array $payload
     * @return void
     */
    protected function addMetadata(Email $email, array &$payload): void
    {
        foreach ($email->getHeaders()->all() as $header) {
            if ($header instanceof MetadataHeader) {
                $headerName = $header->getKey();
                $headerValue = $header->getBodyAsString();
                $payload["v:{$headerName}"] = $headerValue;
            }
        }
    }
}
