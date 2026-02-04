<?php

namespace SendgridCampaign\Entities\Stats;

use SendgridCampaign\DTO\BaseDTO;
use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\DTO\BaseListDto;
use SendgridCampaign\Entities\BaseEntity;
use SendgridCampaign\Entities\Stats\DTO\LinkStatsDTO;
use SendgridCampaign\Entities\Stats\DTO\LinkStatsListDTO;
use SendgridCampaign\Entities\Stats\DTO\StatsDTO;
use SendgridCampaign\Entities\Stats\Enums\AbPhaseType;
use SendgridCampaign\Entities\Stats\Enums\AggregatedByType;
use SendgridCampaign\Entities\Stats\Enums\GroupByType;

class Stats extends BaseEntity
{
    const BASE_ENDPOINT = 'marketing/stats/singlesends';

    /**
     * Summary of getById
     * @param string $singleSendId
     * @param AggregatedByType|null $aggregatedBy
     * @param string|null $startDate
     * @param string|null $endDate
     * @param string|null $timezone
     * @param int|null $pageSize
     * @param string|null $pageToken
     * @param GroupByType[]|null $groupBy
     * @return BaseDTO<StatsDTO>|BaseErrorDTO
     */
    public function getById(
        string $singleSendId,
        ?AggregatedByType $aggregatedBy = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $timezone = null,
        ?int $pageSize = null,
        ?string $pageToken = null,
        ?array $groupBy = null
    ): BaseDTO|BaseErrorDTO {
        $this->validateApiKey();

        $queryParams = [
            'aggregated_by' => $aggregatedBy?->value,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'timezone' => $timezone,
            'page_size' => $pageSize,
            'page_token' => $pageToken,
            'group_by' => $groupBy ? array_map(fn($groupByType) => $groupByType->value, $groupBy) : null

        ];

        $response = $this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT . '/' . $singleSendId,
            queryParams: $queryParams
        );

        return $this->castListResponse($response, StatsDTO::class);
    }

    /**
     * @param string[]|null $singlesendIds
     * @param int|null $pageSize
     * @param string|null $pageToken
     * @return BaseListDto<StatsDTO>|BaseErrorDTO
     */
    public function getAll(
        ?array $singleSendIds = null,
        ?int $pageSize = null,
        ?string $pageToken = null,
    ): BaseListDto|BaseErrorDTO {
        $this->validateApiKey();

        $queryParams = [
            'singlesend_ids' => $singleSendIds ? implode(',', $singleSendIds) : null,
            'page_size' => $pageSize,
            'page_token' => $pageToken
        ];

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT,
            queryParams: $queryParams
        );

        return $this->castListResponse($response, StatsDTO::class);
    }

    /**
     * @param string $singleSendId
     * @param int|null $page_size
     * @param string|null $page_token
     * @param GroupByType[]|null $group_by
     * @param string|null $ab_variation_id
     * @param AbPhaseType|null $ab_phase_id
     * @return LinkStatsListDTO|BaseErrorDTO
     */
    public function getLinkStats(
        string $singleSendId,
        ?int $page_size = null,
        ?string $page_token = null,
        ?array $group_by = null,
        ?string $ab_variation_id = null,
        ?AbPhaseType $ab_phase_id = null
    ): LinkStatsListDTO|BaseErrorDTO {
        $this->validateApiKey();

        if ($ab_phase_id) {
            if ($ab_phase_id === AbPhaseType::ALL) {
                throw new \InvalidArgumentException('ab_phase_id cannot be "all" when fetching link stats.');
            }
            $queryParams['ab_phase_id'] = $ab_phase_id->value;
        }

        $queryParams = [
            'ab_variation_id' => $ab_variation_id,
            'ab_phase_id' => $ab_phase_id?->value,
            'page_size' => $page_size,
            'page_token' => $page_token,
            'group_by' => $group_by ? array_map(fn($groupByType) => $groupByType->value, $group_by) : null
        ];

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/' . $singleSendId . '/links',
            queryParams: $queryParams
        );

        return $this->castListResponse(
            $response,
            dtoClass: LinkStatsDTO::class,
            listClass: LinkStatsListDTO::class,
            extraProperties: ['total_clicks']
        );
    }

    /**
     * @param string[] $singleSendIds
     * @param string|null $timezone
     * @return string|BaseErrorDTO
     */
    public function export(
        ?array $singleSendIds = null,
        ?string $timezone = null
    ): string|BaseErrorDTO {
        $this->validateApiKey();

        $queryParams = [
            'ids' => $singleSendIds ? implode(',', $singleSendIds) : null,
            'timezone' => $timezone
        ];

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/export',
            queryParams: $queryParams
        );

        $requestBody = $response->getBody()->getContents();

        if (!empty($requestBody['errors'])) {
            return $this->castSingleResponse($response, BaseErrorDTO::class);
        }

        return $requestBody;
    }
}