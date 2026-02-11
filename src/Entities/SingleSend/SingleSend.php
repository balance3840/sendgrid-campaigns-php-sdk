<?php

namespace SendgridCampaign\Entities\SingleSend;

use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\DTO\BaseListDto;
use SendgridCampaign\Entities\BaseEntity;
use SendgridCampaign\Entities\SingleSend\DTO\SingleSendDTO;
use SendgridCampaign\Entities\SingleSend\Enums\StatusType;

/**
 * SendGrid Single Send (Email Campaign) Management Entity
 * 
 * This class provides functionality to create and manage email campaigns
 * in SendGrid Marketing Campaigns. A "Single Send" is SendGrid's term for
 * a one-time email campaign sent to a specific audience.
 * 
 * Key capabilities:
 * - Create email campaigns with customizable content
 * - Schedule campaigns for future delivery
 * - Send campaigns immediately
 * - Manage campaign drafts
 * - View campaign status and details
 * - Support for A/B testing
 * 
 * Campaign Workflow:
 * 1. Create a draft campaign with create()
 * 2. Configure email content (subject, body, sender)
 * 3. Select audience (lists or segments)
 * 4. Schedule with schedule() or send immediately with sendNow()
 * 
 * @package SendgridCampaign\Entities\SingleSend
 * @see https://docs.sendgrid.com/api-reference/single-sends
 * 
 * @example
 * $singleSend = new SingleSend('your-api-key');
 * 
 * // Get all campaigns
 * $campaigns = $singleSend->getAll();
 * 
 * // Create and send a campaign
 * $campaign = $singleSend->create($campaignDTO);
 * $singleSend->sendNow($campaign->id);
 */
class SingleSend extends BaseEntity
{
    public const BASE_ENDPOINT = 'marketing/singlesends';

    /**
     * Retrieves all single send campaigns from your account.
     * 
     * Returns campaigns in all statuses (draft, scheduled, triggered, etc.).
     * Use this to display a campaign management dashboard.
     * 
     * @param int $pageSize Number of campaigns per page. Default: 100. Max: 100.
     * 
     * @return BaseListDto<SingleSendDTO>|BaseErrorDTO Returns a list of campaign
     *         DTOs on success, or BaseErrorDTO on failure.
     * 
     * @see https://docs.sendgrid.com/api-reference/single-sends/get-all-single-sends
     * 
     * @example
     * $campaigns = $entity->getAll(pageSize: 100);
     * if (!$campaigns instanceof BaseErrorDTO) {
     *     foreach ($campaigns->result as $campaign) {
     *         echo "{$campaign->name} - Status: {$campaign->status->value}\n";
     *     }
     * }
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

    /**
     * Retrieves a specific single send campaign by ID.
     * 
     * Returns complete campaign details including content, audience settings,
     * A/B test configuration, and scheduling information.
     * 
     * @param string $singleSendId The unique identifier of the campaign.
     * 
     * @return SingleSendDTO|BaseErrorDTO Returns the complete campaign data
     *         on success, or BaseErrorDTO if not found.
     * 
     * @see https://docs.sendgrid.com/api-reference/single-sends/get-single-send-by-id
     * 
     * @example
     * $campaign = $entity->getById('campaign-uuid');
     * if (!$campaign instanceof BaseErrorDTO) {
     *     echo "Campaign: {$campaign->name}\n";
     *     echo "Subject: {$campaign->email_config->subject}\n";
     * }
     */
    public function getById(string $singleSendId): SingleSendDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/' . $singleSendId
        );

        return $this->castSingleResponse($response, SingleSendDTO::class);
    }


    /**
     * Creates a new single send campaign.
     * 
     * Creates a campaign in draft status. You must provide at minimum:
     * - name: The campaign name (for internal use)
     * - send_to: The audience (list IDs or segment IDs)
     * - email_config: Email settings (sender, subject, content)
     * 
     * After creation, use schedule() or sendNow() to deliver the campaign.
     * 
     * @param SingleSendDTO $singleSend DTO containing campaign configuration:
     *        - name: Campaign name (required)
     *        - send_to: Audience targeting (required)
     *        - email_config: Email content and sender (required)
     *        - ab_test: Optional A/B testing configuration
     * 
     * @return SingleSendDTO|BaseErrorDTO Returns the created campaign with its
     *         new ID on success, or BaseErrorDTO on validation errors.
     * 
     * @see https://docs.sendgrid.com/api-reference/single-sends/create-single-send
     * 
     * @example
     * $campaign = SingleSendDTO::fromArray([
     *     'name' => 'March Newsletter',
     *     'email_config' => [
     *         'sender_id' => 123456,
     *         'subject' => 'March Updates',
     *         'html_content' => '<html>...</html>',
     *         'suppression_group_id' => 789
     *     ],
     *     'send_to' => [
     *         'list_ids' => ['newsletter-list-uuid']
     *     ]
     * ]);
     * 
     * $result = $entity->create($campaign);
     */
    public function create(SingleSendDTO $singleSendDTO): SingleSendDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT,
            body: $singleSendDTO->toArray(excludeNullValues: true)
        );

        return $this->castSingleResponse($response, SingleSendDTO::class);
    }

    /**
     * Schedules a campaign to be sent at a specific time.
     * 
     * The campaign must be in draft status and have all required fields
     * configured (content, sender, audience).
     * 
     * Note: The scheduled time must be at least 15 minutes in the future
     * to allow for processing.
     * 
     * @param string $singleSendId The ID of the campaign to schedule.
     * @param string $sendAt ISO 8601 formatted datetime string for when to send.
     *                       Must be in the future. Example: '2024-03-15T10:00:00Z'
     * 
     * @return SingleSendDTO|BaseErrorDTO Returns the updated campaign with
     *         'scheduled' status on success, or BaseErrorDTO on failure.
     * 
     * @see https://docs.sendgrid.com/api-reference/single-sends/schedule-single-send
     * 
     * @example
     * // Schedule for tomorrow at 10 AM UTC
     * $sendTime = (new DateTime('tomorrow 10:00', new DateTimeZone('UTC')))
     *     ->format('Y-m-d\TH:i:s\Z');
     * 
     * $result = $entity->schedule('campaign-uuid', $sendTime);
     */
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
     * Searches for campaigns matching specific criteria.
     * 
     * Allows filtering campaigns by name and/or status. Use this to find
     * specific campaigns in accounts with many campaigns.
     * 
     * @param string $name Filter by campaign name (partial match).
     * @param StatusType[]|null $status Filter by status ('draft', 'scheduled', 'triggered').
     * @param string[]|null $categories Filter by campaign categories (exact match).
     * @param int|null $pageSize Number of results per page. Default: 10.
     * @param string|null $pageToken Token for pagination. Use the token from the previous response to get the next page.
     * 
     * @return BaseListDto<SingleSendDTO>|BaseErrorDTO Returns matching campaigns
     *         on success, or BaseErrorDTO on failure.
     * 
     * @example
     * // Find all draft campaigns
     * $drafts = $entity->search(status: ['draft']);
     * 
     * // Find campaigns by name
     * $newsletters = $entity->search(name: 'newsletter');
     */
    public function search(
        string $name,
        ?array $status = null,
        ?array $categories = null,
        ?int $pageSize = 100,
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
     * Retrieves all categories used in single send campaigns.
     * 
     * @return string[]|BaseErrorDTO Returns an array of categories on success,
     *         or BaseErrorDTO on failure.
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

    /**
     * Updates an existing single send campaign.
     * 
     * You can only update campaigns that are in draft status. Scheduled or
     * sent campaigns cannot be modified.
     * 
     * All fields in the DTO will be applied to the campaign. Include the
     * campaign ID in the DTO.
     * 
     * @param SingleSendDTO $singleSend DTO with updated campaign data.
     *                                  Must include the 'id' field.
     * 
     * @return SingleSendDTO|BaseErrorDTO Returns the updated campaign on success,
     *         or BaseErrorDTO if campaign not found or already sent.
     * 
     * @see https://docs.sendgrid.com/api-reference/single-sends/update-single-send
     * 
     * @example
     * // First get the existing campaign
     * $campaign = $entity->getById('campaign-uuid');
     * 
     * // Modify and update
     * $campaign->name = 'Updated Campaign Name';
     * $result = $entity->update($campaign);
     */
    public function update(string $singleSendId, SingleSendDTO $singleSendDTO): SingleSendDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->patch(
            endpoint: self::BASE_ENDPOINT . '/' . $singleSendId,
            body: $singleSendDTO->toArray(excludeNullValues: true)
        );

        return $this->castSingleResponse($response, SingleSendDTO::class);
    }

    /**
     * Deletes a single send campaign.
     * 
     * Only draft campaigns can be deleted. Scheduled, sending, or sent campaigns
     * must first be cancelled before deletion.
     * 
     * ⚠️ This action is permanent and cannot be undone.
     * 
     * @param string $singleSendId The ID of the campaign to delete.
     * 
     * @return bool|BaseErrorDTO Returns true on successful deletion,
     *         or BaseErrorDTO on failure.
     * 
     * @see https://docs.sendgrid.com/api-reference/single-sends/delete-single-send
     * 
     * @example
     * $result = $entity->delete('campaign-uuid');
     * if ($result === true) {
     *     echo "Campaign deleted successfully";
     * }
     */
    public function delete(string $singleSendId): void
    {
        $this->validateApiKey();

        $this->sendgridClient->delete(
            endpoint: self::BASE_ENDPOINT . '/' . $singleSendId
        );
    }

    /**
     * Deletes multiple single send campaigns in bulk.
     * @param string[] $singleSendIds An array of campaign IDs to delete. Maximum 50 IDs per request.
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

    /**
     * Duplicates an existing campaign.
     * 
     * Creates a copy of a campaign including all its settings, content, and
     * audience configuration. The copy is created in draft status regardless
     * of the original campaign's status.
     * 
     * Useful for creating similar campaigns without starting from scratch.
     * 
     * @param string $singleSendId The ID of the campaign to duplicate.
     * @param string $name The name for the new duplicated campaign.
     * 
     * @return SingleSendDTO|BaseErrorDTO Returns the new campaign on success,
     *         or BaseErrorDTO on failure.
     * 
     * @example
     * $copy = $entity->duplicate(
     *     singleSendId: 'original-uuid',
     *     name: 'April Newsletter (Copy)'
     * );
     */
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
     * Cancels a scheduled campaign.
     * @param string $singleSendId The ID of the campaign to cancel.
     * @return SingleSendDTO|BaseErrorDTO Returns the updated campaign with 'draft' status on success, or BaseErrorDTO on failure.
     */
    public function deleteSchedule(string $singleSendId): SingleSendDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->delete(
            endpoint: self::BASE_ENDPOINT . '/' . $singleSendId . '/schedule'
        );

        return $this->castSingleResponse($response, SingleSendDTO::class);
    }

    /**
     * Sends a campaign immediately.
     * 
     * The campaign must be in draft status and fully configured with content,
     * sender, and audience. Once triggered, the campaign enters the sending
     * queue and cannot be stopped.
     * 
     * ⚠️ This action is irreversible. Verify your campaign content and
     * audience before calling this method.
     * 
     * @param string $singleSendId The ID of the campaign to send.
     * 
     * @return SingleSendDTO|BaseErrorDTO Returns the campaign with 'triggered'
     *         status on success, or BaseErrorDTO if validation fails.
     * 
     * @see https://docs.sendgrid.com/api-reference/single-sends/schedule-single-send
     * 
     * @example
     * // First verify the campaign
     * $campaign = $entity->getById('campaign-uuid');
     * 
     * // Then send it
     * $result = $entity->sendNow($campaign->id);
     * if (!$result instanceof BaseErrorDTO) {
     *     echo "Campaign is now sending!";
     * }
     */
    public function sendNow(string $singleSendId): SingleSendDTO|BaseErrorDTO
    {
        return $this->schedule($singleSendId, 'now');
    }
}
