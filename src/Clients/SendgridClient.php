<?php

namespace SendgridCampaign\Clients;

use GuzzleHttp\Client;

class SendgridClient
{
    protected const BASE_URL = 'https://api.sendgrid.com/v3/';
    protected Client $httpClient;

    public function __construct(
        protected string $apiKey,
        protected ?string $onBehalfOf = null
    ) {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];

        if ($this->onBehalfOf) {
            $headers['on-behalf-of'] = $this->onBehalfOf;
        }

        $this->httpClient = new Client([
            'base_uri' => self::BASE_URL,
            'headers' => array_filter($headers),
        ]);
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getOnBehalfOf(): ?string
    {
        return $this->onBehalfOf;
    }

    public function getHttpClient(): Client
    {
        return $this->httpClient;
    }

    public function sendRequest(
        string $method,
        string $endpoint,
        ?array $body = null,
        array $options = []
    ): \Psr\Http\Message\ResponseInterface {
        if ($body !== null) {
            $options['json'] = $body;
        }
        
        return $this->httpClient->request($method, $endpoint, $options);
    }
}
