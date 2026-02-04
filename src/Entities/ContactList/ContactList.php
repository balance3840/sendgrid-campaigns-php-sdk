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

class ContactList extends BaseEntity
{
    public const BASE_ENDPOINT = 'marketing/lists';

    /**
     * @return BaseListDto<ContactListDTO>|BaseErrorDTO
     * @throws MissingApiKeyException
     */
    public function getAll(): BaseListDto|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT
        );

        return $this->castListResponse($response, ContactListDTO::class);
    }

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

    public function getContactsCount(string $listId): ContactListCountDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/' . $listId . '/contacts/count'
        );

        return $this->castSingleResponse($response, ContactListCountDTO::class);
    }
        
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
