<?php

namespace EuBourne\LaravelMailgun\API;

use Mailgun\HttpClient\HttpClientConfigurator;
use Mailgun\Mailgun;

class MailgunApiClient extends Mailgun
{
    /**
     * Create a new Mailgun API client.
     *
     * @param string $apiKey
     * @param string $endpoint
     * @param string|null $subAccountId
     * @return self
     */
    public static function create(string $apiKey, string $endpoint = 'https://api.mailgun.net', ?string $subAccountId = null): self
    {
        $httpClientConfigurator = (new HttpClientConfigurator())
            ->setApiKey($apiKey)
            ->setEndpoint(static::getEndpoint($endpoint))
            ->setSubAccountId($subAccountId);

        return new self($httpClientConfigurator);
    }

    /**
     * Get the endpoint from the given URL.
     *
     * @param string $endpoint
     * @return string
     */
    protected static function getEndpoint(string $endpoint): string
    {
        $host = parse_url($endpoint, PHP_URL_HOST) ?? parse_url($endpoint, PHP_URL_PATH);

        return "https://" . $host;
    }
}
