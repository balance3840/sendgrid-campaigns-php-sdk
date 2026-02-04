<?php

namespace SendgridCampaign\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\Enums\RequestType;

class SendgridClient
{
    protected const BASE_URL = 'https://api.sendgrid.com/v3/';

    public function __construct(
        protected ClientInterface $httpClient,
        protected ?string $apiKey = null,
        protected ?string $onBehalfOf = null
    ) {
    }

    public static function create(string $apiKey, ?string $onBehalfOf = null): self
    {
        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ];

        if ($onBehalfOf !== null) {
            $headers['on-behalf-of'] = $onBehalfOf;
        }

        $client = new Client([
            'base_uri' => self::BASE_URL,
            'headers' => $headers,
        ]);

        return new self($client, $apiKey, $onBehalfOf);
    }

    public function get(string $endpoint, array $queryParams = []): ResponseInterface|BaseErrorDTO
    {
        return $this->sendRequest(RequestType::GET, $endpoint, queryParams: $queryParams);
    }

    public function post(string $endpoint, array $body = [], array $queryParams = []): ResponseInterface|BaseErrorDTO
    {
        return $this->sendRequest(RequestType::POST, $endpoint, $body, $queryParams);
    }

    public function put(string $endpoint, array $body = [], array $queryParams = []): ResponseInterface|BaseErrorDTO
    {
        return $this->sendRequest(RequestType::PUT, $endpoint, $body, $queryParams);
    }

    public function patch(string $endpoint, array $body = [], array $queryParams = []): ResponseInterface|BaseErrorDTO
    {
        return $this->sendRequest(RequestType::PATCH, $endpoint, $body, $queryParams);
    }

    public function delete(string $endpoint, array $body = [], array $queryParams = []): ResponseInterface|BaseErrorDTO
    {
        return $this->sendRequest(RequestType::DELETE, $endpoint, $body, $queryParams);
    }

    protected function sendRequest(
        RequestType $method,
        string $endpoint,
        array $body = [],
        array $queryParams = []
    ): ResponseInterface|BaseErrorDTO {
        $options = [];

        if (!empty($body)) {
            $options['json'] = $body;
        }

        if (!empty($queryParams)) {
            $options['query'] = $queryParams;
        }

        try {
            $result = $this->httpClient->request($method->value, $endpoint, $options);
        } catch (RequestException | \Exception $e) {
            if ($e instanceof RequestException && $e->hasResponse()) {
                return $e->getResponse();
            }
            throw $e;
        }
        return $result;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function getOnBehalfOf(): ?string
    {
        return $this->onBehalfOf;
    }
}