<?php

namespace SendgridCampaign\Entities\ContactList;

use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\DTO\BaseListDto;
use SendgridCampaign\Entities\BaseEntity;
use SendgridCampaign\Entities\ContactList\DTO\ContactListCountDTO;
use SendgridCampaign\Entities\ContactList\Dto\ContactListDTO;
use SendgridCampaign\Entities\Job\DTO\JobDTO;
use SendgridCampaign\Exceptions\EmptyContactsException;
use SendgridCampaign\Exceptions\MissingApiKeyException;

/**
 * SendGrid Contact List Management Entity
 * 
 * This class provides functionality to manage contact lists in SendGrid
 * Marketing Campaigns. Lists are collections of contacts that you can use
 * to target specific audiences for email campaigns.
 * 
 * Key features:
 * - Create, read, update, and delete contact lists
 * - Manage list membership (add/remove contacts)
 * - Get contact counts for lists
 * 
 * Lists vs Segments:
 * - Lists are static collections where you manually add/remove contacts
 * - Segments are dynamic and automatically include contacts matching criteria
 * 
 * @package SendgridCampaign\Entities\ContactList
 * @see https://docs.sendgrid.com/api-reference/lists
 * 
 * @example
 * $listEntity = new ContactList('your-api-key');
 * 
 * // Create a new list
 * $list = $listEntity->create('Newsletter Subscribers');
 * 
 * // Get all lists
 * $allLists = $listEntity->getAll();
 */
class ContactList extends BaseEntity
{
    public const BASE_ENDPOINT = 'marketing/lists';

    /**
     * Retrieves all contact lists from your SendGrid account.
     * 
     * Returns all lists including their IDs, names, and contact counts.
     * Use this to display available lists for targeting campaigns.
     * 
     * @param int $pageSize Number of lists to return per page. Default: 100. Max: 1000.
     * 
     * @return BaseListDto<ContactListDTO>|BaseErrorDTO Returns a list of ContactListDTO
     *         objects on success, or BaseErrorDTO on failure.
     * 
     * @see https://docs.sendgrid.com/api-reference/lists/get-all-lists
     * 
     * @example
     * $lists = $entity->getAll();
     * if (!$lists instanceof BaseErrorDTO) {
     *     foreach ($lists->result as $list) {
     *         echo "{$list->name}: {$list->contact_count} contacts\n";
     *     }
     * }
     */
    public function getAll(): BaseListDto|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT
        );

        return $this->castListResponse($response, ContactListDTO::class);
    }

    /**
     * Retrieves a specific contact list by its ID.
     * 
     * Fetches detailed information about a single list including its name,
     * description, and contact count.
     * 
     * @param string $listId The unique identifier of the list.
     * @param bool $withContactCount If true, includes the contact count in the response.
     *                               Default: false (faster response without count).
     * 
     * @return ContactListDTO|BaseErrorDTO Returns the list data on success,
     *         or BaseErrorDTO if the list doesn't exist or other errors occur.
     * 
     * @see https://docs.sendgrid.com/api-reference/lists/get-a-list-by-id
     * 
     * @example
     * $list = $entity->getById('list-uuid', withContactCount: true);
     * if (!$list instanceof BaseErrorDTO) {
     *     echo "List: {$list->name}\n";
     *     echo "Contacts: {$list->contact_count}\n";
     * }
     */
    public function getById(string $listId, bool $includeContactSample = false): ContactListDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $queryParams = [];

        if ($includeContactSample) {
            $queryParams['contact_sample'] = 'true';
        }

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/' . $listId,
            queryParams: $queryParams
        );

        return $this->castSingleResponse($response, ContactListDTO::class);
    }

    /**
     * Creates a new contact list.
     * 
     * Lists start empty - use Contact::createOrUpdate() with list IDs to add
     * contacts, or use addContact() to add existing contacts.
     * 
     * @param string $name The name of the list. Must be unique within your account.
     *                     Maximum 100 characters.
     * 
     * @return ContactListDTO|BaseErrorDTO Returns the created list with its new ID
     *         on success, or BaseErrorDTO on failure (e.g., duplicate name).
     * 
     * @see https://docs.sendgrid.com/api-reference/lists/create-list
     * 
     * @example
     * $list = $entity->create('VIP Customers');
     * if (!$list instanceof BaseErrorDTO) {
     *     echo "Created list with ID: {$list->id}\n";
     * }
     */
    public function create(string $name): ContactListDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT,
            body: [
                'name' => $name
            ]
        );

        return $this->castSingleResponse($response, ContactListDTO::class);
    }

    /**
     * Deletes a contact list.
     * 
     * This removes the list but does NOT delete the contacts in it.
     * Contacts remain in your account and in any other lists they belong to.
     * 
     * ⚠️ This action cannot be undone. The list and its membership data
     * will be permanently removed.
     * 
     * @param string $listId The ID of the list to delete.
     * @param bool $deleteContacts If true, also deletes all contacts that are
     *                             ONLY in this list. Contacts in other lists
     *                             are not deleted. Default: false.
     * 
     * @return JobDTO|BaseErrorDTO Returns a JobDTO for the deletion job on success,
     *         or BaseErrorDTO on failure.
     * 
     * @see https://docs.sendgrid.com/api-reference/lists/delete-a-list
     * 
     * @example
     * // Delete list only, keep contacts
     * $result = $entity->delete('list-uuid');
     * 
     * // Delete list AND contacts only in this list
     * $result = $entity->delete('list-uuid', deleteContacts: true);
     */
    public function delete(
        string $listId,
        bool $deleteContactsFromList = false
    ): void {
        $this->validateApiKey();

        $queryParams = [];

        if ($deleteContactsFromList) {
            $queryParams['delete_contacts'] = 'true';
        }

        $this->sendgridClient->delete(
            endpoint: self::BASE_ENDPOINT . '/' . $listId,
            queryParams: $queryParams
        );
    }

    /**
     * Gets the number of contacts in a specific list.
     * 
     * Returns both the total contact count and the billable count.
     * 
     * @param string $listId The ID of the list to count.
     * 
     * @return ContactListCountDTO|BaseErrorDTO Returns the count data on success,
     *         or BaseErrorDTO on failure.
     * 
     * @see https://docs.sendgrid.com/api-reference/lists/get-list-contact-count
     * 
     * @example
     * $count = $entity->getContactCount('list-uuid');
     * if (!$count instanceof BaseErrorDTO) {
     *     echo "List has {$count->contact_count} contacts\n";
     * }
     */
    public function getContactsCount(string $listId): ContactListCountDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/' . $listId . '/contacts/count'
        );

        return $this->castSingleResponse($response, ContactListCountDTO::class);
    }

    /**
     * Updates an existing contact list.
     * 
     * Currently only the list name can be updated. Changing the name does not
     * affect the contacts in the list.
     * 
     * @param string $listId The ID of the list to update.
     * @param string $name The new name for the list.
     * 
     * @return ContactListDTO|BaseErrorDTO Returns the updated list on success,
     *         or BaseErrorDTO on failure.
     * 
     * @see https://docs.sendgrid.com/api-reference/lists/update-list
     * 
     * @example
     * $updated = $entity->update('list-uuid', 'Premium VIP Customers');
     */
    public function update(string $listId, string $newName): ContactListDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->patch(
            endpoint: self::BASE_ENDPOINT . '/' . $listId,
            body: [
                'name' => $newName
            ]
        );

        return $this->castSingleResponse($response, ContactListDTO::class);
    }

    /**
     * Removes one or more contacts from a list.
     * 
     * This removes list membership only - the contacts remain in your SendGrid
     * account and in any other lists they belong to.
     * 
     * @param string $listId The ID of the list to remove contacts from.
     * @param array<string> $contactIds Array of contact IDs to remove.
     *                                  Maximum 100 IDs per request.
     * 
     * @return JobDTO|BaseErrorDTO Returns a JobDTO for the removal job on success,
     *         or BaseErrorDTO on failure.
     * 
     * @see https://docs.sendgrid.com/api-reference/lists/remove-contacts-from-a-list
     * 
     * @example
     * $result = $entity->removeContacts(
     *     listId: 'list-uuid',
     *     contactIds: ['contact-1', 'contact-2', 'contact-3']
     * );
     */
    public function removeContacts(string $listId, array $contactIds): JobDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        if (empty($contactIds)) {
            throw new EmptyContactsException(
                'The contact IDs array cannot be empty.'
            );
        }

        $queryParams = [
            'contact_ids' => implode(',', $contactIds)
        ];

        $response = $this->sendgridClient->delete(
            endpoint: self::BASE_ENDPOINT . '/' . $listId . '/contacts',
            queryParams: $queryParams
        );

        return $this->castSingleResponse($response, JobDTO::class);
    }
}
