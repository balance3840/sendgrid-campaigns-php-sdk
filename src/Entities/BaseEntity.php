<?php

namespace SendgridCampaign\Entities;

use Psr\Http\Message\ResponseInterface;
use SendgridCampaign\Clients\SendgridClient;
use SendgridCampaign\DTO\BaseDTO;
use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\DTO\BaseListDto;
use SendgridCampaign\DTO\MetadataDTO;
use SendgridCampaign\Exceptions\MissingApiKeyException;

/**
 * Base Entity Class for SendGrid API Interactions
 * 
 * This abstract class serves as the foundation for all SendGrid entity classes
 * (Contact, ContactList, SingleSend, etc.). It provides common functionality
 * including API client initialization and shared configuration.
 * 
 * All entity classes that interact with the SendGrid API should extend this class
 * to ensure consistent API communication and error handling.
 * 
 * @package SendgridCampaign\Entities
 */
abstract class BaseEntity
{
    public function __construct(
        protected ?string $apiKey = null,
        protected ?string $onBehalfOf = null,
        protected ?SendgridClient $sendgridClient = null
    ) {
        if (!$this->apiKey && !$this->sendgridClient) {
            throw new MissingApiKeyException('API key is required if SendgridClient is not provided.');
        }
        $this->getSendgridClient();
    }

    protected function validateApiKey(): void
    {
        if (!$this->apiKey && !$this->sendgridClient->getApiKey()) {
            throw new MissingApiKeyException('API key is required.');
        }
    }

    protected function getSendgridClient(): void
    {
        if (!$this->sendgridClient) {
            $this->sendgridClient = SendgridClient::create(
                apiKey: $this->apiKey,
                onBehalfOf: $this->onBehalfOf
            );
        }
    }

    /**
     * @template T of BaseDTO
     * @param ResponseInterface $response
     * @param string $dtoClass
     * @return T[]|BaseErrorDTO
     */
    protected function castRawListResponse(ResponseInterface $response, string $dtoClass): array|BaseErrorDTO
    {
        $parsedBody = $this->getResponseBody($response);

        if ($parsedBody instanceof BaseErrorDTO) {
            return $parsedBody;
        }

        return $dtoClass::collect($parsedBody);
    }

    /**
     * @template T of BaseDTO
     * @param ResponseInterface $response
     * @param class-string<T> $dtoClass
     * @return BaseListDto<T>|BaseErrorDTO
     */
    protected function castListResponse(
        ResponseInterface $response,
        string $dtoClass,
        ?string $listClass = BaseListDto::class,
        ?array $extraProperties = null
    ): BaseListDto|BaseErrorDTO {
        $parsedBody = $this->getResponseBody($response);

        if ($parsedBody instanceof BaseErrorDTO) {
            return $parsedBody;
        }

        $result = $dtoClass::collect($parsedBody['result'] ?? $parsedBody['results'] ?? []);
        $metadata = $parsedBody['_metadata'] ?? [];

        $listDto = new $listClass();
        $listDto->result = $result;
        $listDto->_metadata = MetadataDTO::fromArray($metadata);

        if ($extraProperties) {
            foreach ($extraProperties as $key) {
                $listDto->$key = $parsedBody[$key] ?? null;
            }
        }

        return $listDto;
    }

    /**
     * @template T of BaseDTO
     * @param ResponseInterface $response
     * @param class-string<T> $dtoClass
     * @return T|BaseErrorDTO
     */
    protected function castSingleResponse(ResponseInterface $response, string $dtoClass): BaseDTO|BaseErrorDTO
    {
        $parsedBody = $this->getResponseBody($response);

        if ($parsedBody instanceof BaseErrorDTO) {
            return $parsedBody;
        }

        return $dtoClass::fromArray($parsedBody);
    }

    /**
     * @template T of BaseDTO
     * @param ResponseInterface $response
     * @param class-string<T> $dtoClass
     * @return T[]|BaseErrorDTO
     */
    protected function castListNestedResponse(ResponseInterface $response, string $dtoClass): array|BaseErrorDTO
    {
        $parsedBody = $this->getResponseBody($response);

        if ($parsedBody instanceof BaseErrorDTO) {
            return $parsedBody;
        }

        $result = $parsedBody['result'];
        $emails = array_keys($result);

        $responseArray = [];

        foreach ($emails as $email) {
            $responseArray[] = $result[$email]['contact'];
        }

        return $dtoClass::collect($responseArray);
    }

    protected function getResponseBody(ResponseInterface $response): array|BaseErrorDTO
    {
        $body = $response->getBody()->getContents() ?? '{}';
        $parsedBody = json_decode($body, true);

        if (!empty($parsedBody['errors'])) {
            return BaseErrorDTO::fromArray(
                array_merge($parsedBody, ['status_code' => $response->getStatusCode()])
            );
        }

        return $parsedBody;
    }
}