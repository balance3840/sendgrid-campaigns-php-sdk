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

/**
 * SendGrid Campaign Statistics Entity
 * 
 * This class provides access to email campaign performance metrics and
 * statistics. Use it to analyze campaign effectiveness and track engagement.
 * 
 * Available metrics:
 * - Delivery: requests, delivered, bounces, bounce_drops
 * - Engagement: opens, unique_opens, clicks, unique_clicks
 * - Issues: spam_reports, unsubscribes, invalid_emails
 * 
 * Aggregation options:
 * - By day, week, or month
 * - By A/B test phase
 * - By individual send ID
 * 
 * Link tracking:
 * - Click counts per link
 * - Unique clicks per link
 * 
 * @package SendgridCampaign\Entities\Stats
 * @see https://docs.sendgrid.com/api-reference/single-sends/get-all-single-send-stats
 * 
 * @example
 * $stats = new Stats('your-api-key');
 * 
 * // Get stats for a specific campaign
 * $campaignStats = $stats->getBySingleSendId('campaign-uuid');
 * 
 * // Get all campaign stats aggregated by month
 * $monthlyStats = $stats->getAll(aggregatedBy: AggregatedByType::MONTH);
 */
class Stats extends BaseEntity
{
    const BASE_ENDPOINT = 'marketing/stats/singlesends';

    /**
     * Retrieves statistics for a specific single send campaign.
     * 
     * Returns detailed metrics for the specified campaign, with options to
     * aggregate and group results. Useful for analyzing individual campaign
     * performance and engagement trends.
     * @param string $singleSendId The ID of the single send campaign to retrieve stats for.
     * @param AggregatedByType|null $aggregatedBy How to aggregate results:
     *        - DAY: Daily breakdown
     *        - WEEK: Weekly breakdown
     *        - MONTH: Monthly breakdown
     *        Default: Total (no time breakdown)
     * @param string|null $startDate Start date for stats in YYYY-MM-DD format. Defaults to 30 days ago.
     * @param string|null $endDate End date for stats in YYYY-MM-DD format. Defaults to today.
     * @param string|null $timezone Timezone for date-based aggregation (e.g., 'UTC', 'America/New_York'). Defaults to UTC.
     * @param int|null $pageSize Number of results per page for paginated responses. Default: 100.
     * @param string|null $pageToken Token for pagination. Use the token from the previous response to get the next page.
     * @param GroupByType[]|null $groupBy How to group results:
     *        - SINGLESEND: Group by individual campaign
     *        Default: All data combined
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
     * Retrieves statistics for all single send campaigns.
     * 
     * Returns aggregated metrics for all your campaigns within the specified
     * time range. Results can be grouped and aggregated in various ways.
     * 
     * @param string|null $startDate Start date for stats in YYYY-MM-DD format.
     *                               Defaults to 30 days ago.
     * @param string|null $endDate End date for stats in YYYY-MM-DD format.
     *                             Defaults to today.
     * @param AggregatedByType|null $aggregatedBy How to aggregate results:
     *        - DAY: Daily breakdown
     *        - WEEK: Weekly breakdown
     *        - MONTH: Monthly breakdown
     *        Default: Total (no time breakdown)
     * @param GroupByType|null $groupBy How to group results:
     *        - SINGLESEND: Group by individual campaign
     *        Default: All campaigns combined
     * 
     * @return StatsDTO|BaseErrorDTO Returns statistics data containing metrics
     *         like opens, clicks, bounces, etc., or BaseErrorDTO on failure.
     * 
     * @see https://docs.sendgrid.com/api-reference/single-sends/get-all-single-send-stats
     * 
     * @example
     * // Get all stats for the last 30 days
     * $stats = $entity->getAll();
     * 
     * // Get monthly stats for a specific period
     * $stats = $entity->getAll(
     *     startDate: '2024-01-01',
     *     endDate: '2024-03-31',
     *     aggregatedBy: AggregatedByType::MONTH
     * );
     * 
     * if (!$stats instanceof BaseErrorDTO) {
     *     foreach ($stats->results as $result) {
     *         echo "Opens: {$result->stats->opens}\n";
     *         echo "Clicks: {$result->stats->clicks}\n";
     *     }
     * }
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
     * Retrieves link click statistics for a specific campaign.
     * 
     * Returns click counts for each tracked link in the campaign. Useful for
     * understanding which content/CTAs drive the most engagement.
     * 
     * Metrics per link:
     * - Total clicks: All clicks on the link
     * - Unique clicks: Number of unique recipients who clicked
     * - Link URL: The actual URL being tracked
     * 
     * @param string $singleSendId The UUID of the campaign.
     * @param int $pageSize Number of links per page. Default: 10.
     * 
     * @return LinkStatsListDTO|BaseErrorDTO Returns link statistics or error.
     * 
     * @see https://docs.sendgrid.com/api-reference/single-sends/get-single-send-link-stats
     * 
     * @example
     * $linkStats = $entity->getLinkStats('campaign-uuid');
     * 
     * if (!$linkStats instanceof BaseErrorDTO) {
     *     foreach ($linkStats->results as $link) {
     *         echo "URL: {$link->url}\n";
     *         echo "Total clicks: {$link->clicks}\n";
     *         echo "Unique clicks: {$link->unique_clicks}\n\n";
     *     }
     * }
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
     * Exports campaign statistics data in CSV format.
     * @param string[] $singleSendIds Optional array of campaign IDs to include in the export. If null, exports all campaigns.
     * @param string|null $timezone Optional timezone for date-based data in the export (e.g., 'UTC', 'America/New_York'). Defaults to UTC.
     * @return string|BaseErrorDTO Returns CSV data as a string on success, or BaseErrorDTO on failure.
      * 
      * @see https://docs.sendgrid.com/api-reference/single-sends/export-single-send-stats
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