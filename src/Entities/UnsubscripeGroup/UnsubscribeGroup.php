<?php

namespace SendgridCampaign\Entities\UnsubscripeGroup;

use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\Entities\BaseEntity;
use SendgridCampaign\Entities\UnsubscripeGroup\DTO\UnsubscribeDTO;

/**
 * SendGrid Unsubscribe Group (Suppression Group) Management Entity
 * 
 * This class manages unsubscribe groups (also called suppression groups) in
 * SendGrid. Unsubscribe groups allow recipients to opt out of specific types
 * of emails without unsubscribing from everything.
 * 
 * Benefits of using unsubscribe groups:
 * - Give recipients granular control over email preferences
 * - Reduce spam complaints by allowing targeted opt-outs
 * - Maintain engagement by keeping interested subscribers
 * - Comply with email regulations (CAN-SPAM, GDPR)
 * 
 * Common group examples:
 * - Marketing/Promotional emails
 * - Product updates
 * - Newsletter
 * - Account notifications
 * 
 * When creating campaigns, you must specify an unsubscribe group.
 * Recipients can unsubscribe from just that group rather than all emails.
 * 
 * @package SendgridCampaign\Entities\UnsubscripeGroup
 * @see https://docs.sendgrid.com/api-reference/suppressions-unsubscribe-groups
 * 
 * @example
 * $unsubscribe = new UnsubscribeGroup('your-api-key');
 * 
 * // List all groups
 * $groups = $unsubscribe->getAll();
 * 
 * // Create a new group
 * $group = $unsubscribe->create('Weekly Newsletter', 'Our weekly product updates');
 */
class UnsubscribeGroup extends BaseEntity
{
    public const BASE_ENDPOINT = 'asm/groups';

    /**
     * Retrieves all unsubscribe groups from your account.
     * 
     * Returns all suppression groups you've created. Each campaign must
     * be associated with one of these groups.
     * 
     * @return UnsubscribeDTO[]|BaseErrorDTO Returns a list of
     *         unsubscribe groups on success, or BaseErrorDTO on failure.
     * 
     * @see https://docs.sendgrid.com/api-reference/suppressions-unsubscribe-groups/retrieve-all-suppression-groups
     * 
     * @example
     * $groups = $entity->getAll();
     * if (!$groups instanceof BaseErrorDTO) {
     *     foreach ($groups as $group) {
     *         echo "{$group->id}: {$group->name}\n";
     *         echo "  {$group->description}\n";
     *     }
     * }
     */
    public function getAll(): array|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT
        );

        return $this->castRawListResponse(
            response: $response,
            dtoClass: UnsubscribeDTO::class
        );
    }

    /**
     * Retrieves a specific unsubscribe group by ID.
     * 
     * @param int $groupId The ID of the unsubscribe group.
     * 
     * @return UnsubscribeDTO|BaseErrorDTO Returns the group data on success,
     *         or BaseErrorDTO if not found.
     * 
     * @see https://docs.sendgrid.com/api-reference/suppressions-unsubscribe-groups/get-information-on-a-single-suppression-group
     * 
     * @example
     * $group = $entity->getById(12345);
     * if (!$group instanceof BaseErrorDTO) {
     *     echo "Name: {$group->name}\n";
     *     echo "Unsubscribes: {$group->unsubscribes}\n";
     * }
     */
    public function getById(int $groupId): UnsubscribeDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/' . $groupId
        );

        return $this->castSingleResponse(
            response: $response,
            dtoClass: UnsubscribeDTO::class
        );
    }

    /**
     * Creates a new unsubscribe group.
     * @param string $name The name of the unsubscribe group. Must be unique and descriptive.
     * @param string $description A brief description of the group’s purpose (e.g., "Weekly Newsletter"). Helps you identify the group when managing campaigns.
     * @param bool|null $isDefault Optional flag to set this group as the default for new campaigns. Only one group can be default. If true, this group will be used for campaigns that don't specify a group.
     * @return BaseErrorDTO|UnsubscribeDTO Returns the created group with its ID on success, or BaseErrorDTO on validation errors (e.g., duplicate name).
     * 
     * @see https://docs.sendgrid.com/api-reference/suppressions-unsubscribe-groups/create-a-suppression-group
     * 
     * @example
     * $group = $entity->create('Weekly Newsletter', 'Our weekly product updates');
     * if (!$group instanceof BaseErrorDTO) {
     *     echo "Created group with ID: {$group->id}\n";
     * }
     */
    public function create(
        string $name,
        string $description,
        ?bool $isDefault = null
    ): UnsubscribeDTO|BaseErrorDTO {
        $this->validateApiKey();

        $body = [
            'name' => $name,
            'description' => $description
        ];

        if (!is_null($isDefault)) {
            $body['is_default'] = $isDefault;
        }

        $response = $this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT,
            body: $body
        );

        return $this->castSingleResponse(
            response: $response,
            dtoClass: UnsubscribeDTO::class
        );
    }

    /**
     * Updates an existing unsubscribe group.
     * @param int $groupId The ID of the unsubscribe group to update.
     * @param string|null $name The new name for the group. Must be unique and descriptive.
     * @param string|null $description A new description for the group’s purpose.
     * @param bool|null $isDefault Optional flag to set this group as the default for new campaigns.
     * @throws \InvalidArgumentException If no fields are provided for update.
     * @return BaseErrorDTO|UnsubscribeDTO Returns the updated group on success, or BaseErrorDTO if the group doesn't exist or validation fails (e.g., duplicate name).
     * 
     * @see https://docs.sendgrid.com/api-reference/suppressions-unsubscribe-groups/update-a-suppression-group
     * 
     * @example
     * $updated = $entity->update(12345, 'New Name', 'Updated description', true);
     * if (!$updated instanceof BaseErrorDTO) {
     *     echo "Updated group name: {$updated->name}\n";
     * }
     */
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

        $response = $this->sendgridClient->patch(
            endpoint: self::BASE_ENDPOINT . '/' . $groupId,
            body: $body
        );

        return $this->castSingleResponse(
            response: $response,
            dtoClass: UnsubscribeDTO::class
        );
    }

    /**
     * Deletes an unsubscribe group.
     * @param int $groupId The ID of the unsubscribe group to delete.
     * @return void
     * 
     * @see https://docs.sendgrid.com/api-reference/suppressions-unsubscribe-groups/delete-a-suppression-group
     * 
     * @example
     * $entity->delete(12345);
     */
    public function delete(int $groupId): void
    {
        $this->validateApiKey();

        $this->sendgridClient->delete(
            endpoint: self::BASE_ENDPOINT . '/' . $groupId
        );
    }
}
