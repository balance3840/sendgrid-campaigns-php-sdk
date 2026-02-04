<?php

namespace SendgridCampaign\Entities\SingleSend;

use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\DTO\BaseListDto;
use SendgridCampaign\Entities\BaseEntity;
use SendgridCampaign\Entities\SingleSend\DTO\SingleSendDTO;
use SendgridCampaign\Entities\SingleSend\Enums\StatusType;

class SingleSend extends BaseEntity
{
    public const BASE_ENDPOINT = 'marketing/singlesends';

    /**
     * @param int $pageSize
     * @param mixed $pageToken
     * @return BaseListDto<SingleSendDTO>|BaseErrorDTO
     */
    public function getAll(
        int $pageSize = 100,
        ?string $pageToken = null
    ): BaseListDto|BaseErrorDTO {
        $this->validateApiKey();

        $queryParams = [
            'page_size' => $pageSize,
            'page_token' => $pageToken
        ];

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT,
            queryParams: $queryParams
        );

        return $this->castListResponse($response, SingleSendDTO::class);
    }

    public function getById(string $singleSendId): SingleSendDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/' . $singleSendId
        );

        return $this->castSingleResponse($response, SingleSendDTO::class);
    }

    public function create(SingleSendDTO $singleSendDTO): SingleSendDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT,
            body: $singleSendDTO->toArray(excludeNullValues: true)
        );

        return $this->castSingleResponse($response, SingleSendDTO::class);
    }

    public function schedule(string $singleSendId, string $sendAt): SingleSendDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->put(
            endpoint: self::BASE_ENDPOINT . '/' . $singleSendId . '/schedule',
            body: ['send_at' => $sendAt]
        );

        return $this->castSingleResponse($response, SingleSendDTO::class);
    }

    /**
     * @param string $name
     * @param StatusType[]|null $status
     * @param string[] $categories
     * @param int $pageSize
     * @param string|null $pageToken
     * @return BaseListDto<SingleSendDTO>|BaseErrorDTO
     */
    public function search(
        string $name,
        ?array $status = null,
        ?array $categories = null,
        int $pageSize = 100,
        ?string $pageToken = null
    ): BaseListDto|BaseErrorDTO {
        $this->validateApiKey();

        $queryParams = [
            'page_size' => $pageSize,
            'page_token' => $pageToken
        ];

        $body = [];

        if ($name) {
            $body['name'] = $name;
        }

        if ($status) {
            $body['status'] = array_map(fn($s) => $s->value, $status);
        }

        if ($categories) {
            $body['categories'] = $categories;
        }

        $response = $this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT . '/search',
            queryParams: $queryParams,
            body: $body
        );

        return $this->castListResponse($response, SingleSendDTO::class);
    }

    /**
     * @return string[]|BaseErrorDTO
     */
    public function getAllCategories(): array|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/categories'
        );

        $responseBody = $this->getResponseBody($response);

        return !empty($responseBody['categories'])
            ? $responseBody['categories']
            : $responseBody;
    }

    public function update(string $singleSendId, SingleSendDTO $singleSendDTO): SingleSendDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->patch(
            endpoint: self::BASE_ENDPOINT . '/' . $singleSendId,
            body: $singleSendDTO->toArray(excludeNullValues: true)
        );

        return $this->castSingleResponse($response, SingleSendDTO::class);
    }

    public function delete(string $singleSendId): void
    {
        $this->validateApiKey();

        $this->sendgridClient->delete(
            endpoint: self::BASE_ENDPOINT . '/' . $singleSendId
        );
    }

    /**
     * @param string[] $singleSendIds
     * @return void
     */
    public function bulkDelete(array $singleSendIds): void
    {
        $this->validateApiKey();

        if (empty($singleSendIds)) {
            throw new \InvalidArgumentException('At least one Single Send ID must be provided for bulk deletion.');
        }

        if (count($singleSendIds) > 50) {
            throw new \InvalidArgumentException('A maximum of 50 Single Send IDs can be deleted in a single request.');
        }

        $queryParams = [
            'ids' => implode(',', $singleSendIds)
        ];

        $this->sendgridClient->delete(
            endpoint: self::BASE_ENDPOINT,
            queryParams: $queryParams
        );
    }

    public function duplicate(string $singleSendId, ?string $name): SingleSendDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $body = [];

        if ($name) {
            if (strlen($name) > 100) {
                throw new \InvalidArgumentException('Name must be 100 characters or less.');
            }
            $body['name'] = $name;
        }

        $response = $this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT . '/' . $singleSendId,
            body: $body
        );

        return $this->castSingleResponse($response, SingleSendDTO::class);
    }

    /**
     * @param string $singleSendId
     * @return SingleSendDTO|BaseErrorDTO
     */
    public function deleteSchedule(string $singleSendId): SingleSendDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->delete(
            endpoint: self::BASE_ENDPOINT . '/' . $singleSendId . '/schedule'
        );

        return $this->castSingleResponse($response, SingleSendDTO::class);
    }
}
