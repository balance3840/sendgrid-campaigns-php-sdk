<?php

namespace SendgridCampaign\Entities\Sender;

use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\DTO\BaseListDto;
use SendgridCampaign\Entities\BaseEntity;
use SendgridCampaign\Entities\Sender\DTO\SenderDTO;

class Sender extends BaseEntity
{
    protected const BASE_ENDPOINT = 'marketing/senders';
    protected const VERIFIED_BASE_ENDPOINT = 'verified_senders';

    /**
     * Get all senders
     *
     * @return SenderDTO[]|BaseErrorDTO
     */
    public function getAll(): array|BaseErrorDTO
    {
        $this->validateApiKey();

        $response =$this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT
        );

        return $this->castRawListResponse($response, SenderDTO::class);
    }

    /**
     * @param int|null $limit
     * @param int|null $lastSeenID
     * @param int|null $id
     * @return BaseListDto<SenderDTO>|BaseErrorDTO
     */
    public function getAllVerified(
        ?int $limit = null,
        ?int $lastSeenID = null,
        ?int $id = null
    ): BaseListDto|BaseErrorDTO {
        $this->validateApiKey();

        $queryParams = [
            'limit' => $limit,
            'last_seen_id' => $lastSeenID,
            'id' => $id
        ];

        $response =$this->sendgridClient->get(
            endpoint: self::VERIFIED_BASE_ENDPOINT,
            queryParams: $queryParams
        );

        return $this->castListResponse($response, SenderDTO::class);
    }

    public function getById(int $senderId): SenderDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response =$this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/' . $senderId
        );

        return $this->castSingleResponse($response, SenderDTO::class);
    }
}
