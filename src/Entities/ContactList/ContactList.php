<?php

namespace SendgridCampaign\Entities\ContactList;

use SendgridCampaign\Entities\BaseEntity;
use SendgridCampaign\Entities\ContactList\DTO\ContactListCountDTO;
use SendgridCampaign\Entities\ContactList\Dto\ContactListDTO;
use SendgridCampaign\Entities\Job\DTO\JobDTO;
use SendgridCampaign\Enums\RequestType;
use SendgridCampaign\Exceptions\EmptyContactsException;
use SendgridCampaign\Exceptions\MissingApiKeyException;

class ContactList extends BaseEntity
{
    public const BASE_ENDPOINT = 'marketing/lists';

    /**
     * @return ContactListDTO[]
     * @throws MissingApiKeyException
     */
    public function getAll(): array
    {
        $this->validateApiKey();

        $response = $this->getSendgridClient()->sendRequest(
            method: RequestType::GET->value,
            endpoint: self::BASE_ENDPOINT
        );

        return $this->castListResponse($response, ContactListDTO::class);
    }

    public function getById(string $listId, bool $includeContactSample = false): ContactListDTO
    {
        $this->validateApiKey();

        if ($includeContactSample) {
            $listId .= '?contact_sample=true';
        }

        $response = $this->getSendgridClient()->sendRequest(
            method: RequestType::GET->value,
            endpoint: self::BASE_ENDPOINT . '/' . $listId
        );

        return $this->castSingleResponse($response, ContactListDTO::class);
    }

    public function create(string $name): ContactListDTO
    {
        $this->validateApiKey();

        $response = $this->getSendgridClient()->sendRequest(
            method: RequestType::POST->value,
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
    ): bool {
        $this->validateApiKey();

        if ($deleteContactsFromList) {
            $listId .= '?delete_contacts=true';
        }

        $this->getSendgridClient()->sendRequest(
            method: RequestType::DELETE->value,
            endpoint: self::BASE_ENDPOINT . '/' . $listId
        );

        return true;
    }

    public function getContactsCount(string $listId): ContactListCountDTO
    {
        $this->validateApiKey();

        $response = $this->getSendgridClient()->sendRequest(
            method: RequestType::GET->value,
            endpoint: self::BASE_ENDPOINT . '/' . $listId . '/contacts/count'
        );

        return $this->castSingleResponse($response, ContactListCountDTO::class);
    }

    public function update(string $listId, string $newName): ContactListDTO
    {
        $this->validateApiKey();

        $response = $this->getSendgridClient()->sendRequest(
            method: RequestType::PATCH->value,
            endpoint: self::BASE_ENDPOINT . '/' . $listId,
            body: [
                'name' => $newName
            ]
        );

        return $this->castSingleResponse($response, ContactListDTO::class);
    }

    public function removeContacts(string $listId, array $contactIds): JobDTO
    {
        $this->validateApiKey();

        if (empty($contactIds)) {
            throw new EmptyContactsException(
                'The contact IDs array cannot be empty.'
            );
        }

        $response = $this->getSendgridClient()->sendRequest(
            method: RequestType::DELETE->value,
            endpoint: self::BASE_ENDPOINT . '/' .
            $listId . '/contacts' . '?contact_ids=' . implode(',', $contactIds),
        );

        return $this->castSingleResponse($response, JobDTO::class);
    }
}
