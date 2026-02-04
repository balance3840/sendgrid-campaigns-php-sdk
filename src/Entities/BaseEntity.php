<?php

namespace SendgridCampaign\Entities;

use Psr\Http\Message\ResponseInterface;
use SendgridCampaign\Clients\SendgridClient;
use SendgridCampaign\DTO\BaseDTO;
use SendgridCampaign\Exceptions\MissingApiKeyException;

class BaseEntity
{

    protected SendgridClient $sendgridClient;

    public function __construct(
        protected ?string $apiKey = null,
        protected ?string $onBehalfOf = null
    ) {
    }

    public function apiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function onBehalfOf(string $onBehalfOf): self
    {
        $this->onBehalfOf = $onBehalfOf;
        return $this;
    }

    protected function validateApiKey(): void
    {
        if (!$this->apiKey) {
            throw new MissingApiKeyException('API key is required.');
        }
    }

    protected function getSendgridClient(): SendgridClient
    {
        if (!isset($this->sendgridClient)) {
            $this->sendgridClient = new SendgridClient(
                apiKey: $this->apiKey,
                onBehalfOf: $this->onBehalfOf
            );
        }

        return $this->sendgridClient;
    }

    /**
     * @template T of BaseDTO
     * @param ResponseInterface $response
     * @param class-string<T> $dtoClass
     * @return T[]
     */
    protected function castListResponse(ResponseInterface $response, string $dtoClass): array
    {
        $body = $response->getBody()->getContents();
        $parsedBody = json_decode($body, true);

        return $dtoClass::collect($parsedBody['result']);
    }

    /**
     * @template T of BaseDTO
     * @param ResponseInterface $response
     * @param class-string<T> $dtoClass
     * @return T
     */
    protected function castSingleResponse(ResponseInterface $response, string $dtoClass): BaseDTO
    {
        $body = $response->getBody()->getContents();
        $parsedBody = json_decode($body, true);

        return $dtoClass::fromArray($parsedBody);
    }

    /**
     * @template T of BaseDTO
     * @param ResponseInterface $response
     * @param class-string<T> $dtoClass
     * @return T[]
     */
    protected function castListNestedResponse(ResponseInterface $response, string $dtoClass): array
    {
        $body = $response->getBody()->getContents();
        $parsedBody = json_decode($body, true);

        $result = $parsedBody['result'];
        $emails = array_keys($result);

        $responseArray = [];

        foreach ($emails as $email) {
            $responseArray[] = $result[$email]['contact'];
        }

        return $dtoClass::collect($responseArray);
    }
}