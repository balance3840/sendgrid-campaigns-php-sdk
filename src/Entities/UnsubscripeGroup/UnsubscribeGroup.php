<?php

namespace SendgridCampaign\Entities\UnsubscripeGroup;

use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\Entities\BaseEntity;
use SendgridCampaign\Entities\UnsubscripeGroup\DTO\UnsubscribeDTO;

class UnsubscribeGroup extends BaseEntity
{
    public const BASE_ENDPOINT = 'asm/groups';

    /**
     * @return UnsubscribeDTO[]|BaseErrorDTO
     */
    public function getAll(): array|BaseErrorDTO
    {
        $this->validateApiKey();

        $response =$this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT
        );

        return $this->castRawListResponse(
            response: $response,
            dtoClass: UnsubscribeDTO::class
        );
    }

    public function getById(int $groupId): UnsubscribeDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response =$this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/' . $groupId
        );

        return $this->castSingleResponse(
            response: $response,
            dtoClass: UnsubscribeDTO::class
        );
    }

    public function create(
        string $name,
        string $description,
        ?bool $isDefault = null
        ): UnsubscribeDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $body = [
            'name' => $name,
            'description' => $description
        ];

        if (!is_null($isDefault)) {
            $body['is_default'] = $isDefault;
        }

        $response =$this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT,
            body: $body
        );

        return $this->castSingleResponse(
            response: $response,
            dtoClass: UnsubscribeDTO::class
        );
    }

    public function update(
        int $groupId,
        ?string $name = null,
        ?string $description = null,
        ?bool $isDefault = null
    ): UnsubscribeDTO|BaseErrorDTO {

        if (is_null($name) && is_null($description) && is_null($isDefault)) {
            throw new \InvalidArgumentException('At least one field must be provided for update.');
        }

        $this->validateApiKey();

        $body = [];

        if (!is_null($name)) {
            $body['name'] = $name;
        }

        if (!is_null($description)) {
            $body['description'] = $description;
        }

        if (!is_null($isDefault)) {
            $body['is_default'] = $isDefault;
        }

        $response =$this->sendgridClient->patch(
            endpoint: self::BASE_ENDPOINT . '/' . $groupId,
            body: $body
        );

        return $this->castSingleResponse(
            response: $response,
            dtoClass: UnsubscribeDTO::class
        );
    }

    public function delete(int $groupId): void
    {
        $this->validateApiKey();

       $this->sendgridClient->delete(
            endpoint: self::BASE_ENDPOINT . '/' . $groupId
        );
    }
}
