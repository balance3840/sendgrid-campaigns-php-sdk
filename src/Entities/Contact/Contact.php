<?php

namespace SendgridCampaign\Entities\Contact;

use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\DTO\BaseListDto;
use SendgridCampaign\Entities\Contact\DTO\ContactDTO;
use SendgridCampaign\Entities\BaseEntity;
use SendgridCampaign\Entities\Contact\DTO\ContactAllExportsStatusDTO;
use SendgridCampaign\Entities\Contact\DTO\ContactCountDTO;
use SendgridCampaign\Entities\Contact\DTO\ContactExportStatusDTO;
use SendgridCampaign\Entities\Contact\DTO\ContactImportDTO;
use SendgridCampaign\Entities\Contact\DTO\ContactImportStatusDTO;
use SendgridCampaign\Entities\Contact\DTO\ContactSearchDTO;
use SendgridCampaign\Entities\Contact\Enums\ContactIdentifierType;
use SendgridCampaign\Entities\Contact\Enums\ContactImportFileType;
use SendgridCampaign\Entities\Job\DTO\JobDTO;
use SendgridCampaign\Enums\RequestType;

class Contact extends BaseEntity
{

    public const BASE_ENDPOINT = 'marketing/contacts';

    /**
     * @return BaseListDto<ContactDTO>|BaseErrorDTO
     */
    public function getSample(): BaseListDto|BaseErrorDTO
    {
        $this->validateApiKey();

        $response =$this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT
        );

        return $this->castListResponse($response, ContactDTO::class);
    }

    public function getById(string $contactId): ContactDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response =$this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/' . $contactId
        );

        return $this->castSingleResponse($response, ContactDTO::class);
    }

    /**
     * @param string[] $emails
     * @param ?string $phone_number_id
     * @param ?string $external_id
     * @param ?string $anonymous_id
     * @return ContactDTO[]|BaseErrorDTO
     */
    public function getByEmail(
        array $emails,
        ?string $phone_number_id,
        ?string $external_id,
        ?string $anonymous_id
    ): array|BaseErrorDTO {
        $this->validateApiKey();

        $response =$this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT . '/search/emails',
            body: [
                'emails' => $emails,
                'phone_number_id' => $phone_number_id,
                'external_id' => $external_id,
                'anonymous_id' => $anonymous_id
            ]
        );

        return $this->castListNestedResponse($response, ContactDTO::class);
    }

    /**
     * @param ContactDTO[] $contacts
     * @param string[]|null $listIds
     */
    public function createOrUpdate(array $contacts, ?array $listIds = null): JobDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $body = [
            'contacts' => array_map(fn(ContactDTO $dto) => $dto->toArray(
                excludeNullValues: true
            ), $contacts)
        ];

        if (!empty($listIds)) {
            $body['list_ids'] = $listIds;
        }

        $response =$this->sendgridClient->put(
            endpoint: self::BASE_ENDPOINT,
            body: $body
        );

        return $this->castSingleResponse($response, JobDTO::class);
    }

    /**
     * @param string[]|null $contactIds
     * @param bool $deleteAll
     * @throws \InvalidArgumentException
     * @return JobDTO|BaseErrorDTO
     */
    public function delete(?array $contactIds = null, bool $deleteAll = false): JobDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        if (empty($contactIds) && !$deleteAll) {
            throw new \InvalidArgumentException('Either contact IDs must be provided or deleteAll must be true.');
        }

        $queryParams = [];

        if ($deleteAll) {
            $queryParams['delete_all_contacts'] = 'true';
        } else {
            $queryParams['ids'] = implode(',', $contactIds);
        }

        $response =$this->sendgridClient->delete(
            endpoint: self::BASE_ENDPOINT,
            queryParams: $queryParams
        );

        return $this->castSingleResponse($response, JobDTO::class);
    }

    /**
     * Import contacts from a CSV file
     *
     * @param string $fileContent The content of the file (CSV format)
     * @param ContactImportFileType $fileType The type of file: 'csv'
     * @param string[]|null $listIds Array of list IDs to add contacts to
     * @param string[]|null $fieldMappings Custom field mappings (for CSV files)
     * @return ContactImportDTO|BaseErrorDTO
     * @throws \InvalidArgumentException
     */
    public function import(
        string $fileContent,
        ContactImportFileType $fileType = ContactImportFileType::CSV,
        ?array $listIds = null,
        ?array $fieldMappings = null
    ): ContactImportDTO|BaseErrorDTO {
        $this->validateApiKey();

        if ($fileType->value !== 'csv') {
            throw new \InvalidArgumentException('File type must be "csv".');
        }

        if (empty($fileContent)) {
            throw new \InvalidArgumentException('File content cannot be empty.');
        }

        $body = [
            'file_type' => $fileType,
        ];

        if (!empty($listIds)) {
            $body['list_ids'] = $listIds;
        }

        if (!empty($fieldMappings)) {
            $body['field_mappings'] = $fieldMappings;
        }

        $uploadResponse =$this->sendgridClient->put(
            endpoint: self::BASE_ENDPOINT . '/imports',
            body: $body
        );

        $contactImport = $this->castSingleResponse($uploadResponse, ContactImportDTO::class);

        if (!$contactImport->upload_uri) {
            throw new \RuntimeException('Failed to get upload URL from SendGrid.');
        }

        $this->uploadFileContent(
            $contactImport->upload_uri,
            $fileContent,
            $contactImport->upload_headers
        );

        return $contactImport;
    }

    /**
     * Upload file content to SendGrid's upload URL
     *
     * @param string $uploadUrl The URL provided by SendGrid
     * @param string $fileContent The file content to upload
     * @param array{header: string, value: string}[] $uploadHeaders Headers from SendGrid response
     */
    protected function uploadFileContent(
        string $uploadUrl,
        string $fileContent,
        array $uploadHeaders
    ): void {
        $headers = [];

        foreach ($uploadHeaders as $uploadHeader) {
            $headers[$uploadHeader['header']] = $uploadHeader['value'];
        }

        $client = new \GuzzleHttp\Client();
        $client->request(RequestType::PUT->value, $uploadUrl, [
            'headers' => $headers,
            'body' => $fileContent,
        ]);
    }

    /**
     * Import contacts from a file path or URL
     *
     * @param string $filePath Path to the CSV file, or a URL
     * @param string[]|null $listIds Array of list IDs to add contacts to
     * @param string[]|null $fieldMappings Custom field mappings (for CSV files)
     * @param ContactImportFileType|null $fileType Explicitly specify 'csv' (required for URLs without clear extensions)
     * @return ContactImportDTO
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function importFromFile(
        string $filePath,
        ?array $listIds = null,
        ?array $fieldMappings = null,
        ?ContactImportFileType $fileType = null
    ): ContactImportDTO|BaseErrorDTO {
        $isUrl = filter_var($filePath, FILTER_VALIDATE_URL) !== false;

        if (!$isUrl && !file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        if ($fileType !== null) {
            $extension = strtolower($fileType->value);
        } else {
            $path = $isUrl ? parse_url($filePath, PHP_URL_PATH) : $filePath;
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        }

        if (!in_array($extension, ['csv'], true)) {
            throw new \InvalidArgumentException(
                'File must be a CSV file. ' .
                ($isUrl ? 'For URLs, you may need to specify the $fileType parameter explicitly.' : '')
            );
        }

        $fileContent = file_get_contents($filePath);

        if ($fileContent === false) {
            throw new \RuntimeException(
                $isUrl
                ? "Failed to fetch content from URL: {$filePath}"
                : "Failed to read file: {$filePath}"
            );
        }

        return $this->import(
            fileContent: $fileContent,
            fileType: $fileType ?? ContactImportFileType::from($extension),
            listIds: $listIds,
            fieldMappings: $fieldMappings
        );
    }

    public function getImportStatus(string $jobId): ContactImportStatusDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response =$this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/imports/' . $jobId
        );

        return $this->castSingleResponse($response, ContactImportStatusDTO::class);
    }

    public function search(string $query): ContactSearchDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response =$this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT . '/search',
            body: ['query' => $query]
        );

        return $this->castSingleResponse($response, ContactSearchDTO::class);
    }

    public function getCount(): ContactCountDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response =$this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/count'
        );

        return $this->castSingleResponse($response, ContactCountDTO::class);
    }

    public function export(
        ?array $listIds = null,
        ?array $segmentIds = null,
        ?bool $sendEmailNotification = false,
        ?ContactImportFileType $fileType = ContactImportFileType::CSV,
        ?int $maxFileSizeInMb = 5000
    ): JobDTO|BaseErrorDTO {
        $this->validateApiKey();

        $body = [
            'file_type' => $fileType->value,
            'notifications' => [
                'email' => $sendEmailNotification
            ],
            'max_file_size' => $maxFileSizeInMb
        ];

        if (!empty($listIds)) {
            $body['list_ids'] = $listIds;
        }

        if (!empty($segmentIds)) {
            $body['segment_ids'] = $segmentIds;
        }

        $response =$this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT . '/exports',
            body: $body
        );

        return $this->castSingleResponse($response, JobDTO::class);
    }

    public function exportStatus(string $jobId): ContactExportStatusDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response =$this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/exports/' . $jobId
        );

        return $this->castSingleResponse($response, ContactExportStatusDTO::class);
    }

    /**
     * @return  BaseListDto<ContactAllExportsStatusDTO>
     */
    public function getAllExportsStatus(): BaseListDto|BaseErrorDTO
    {
        $this->validateApiKey();

        $response =$this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/exports'
        );

        return $this->castListResponse($response, ContactAllExportsStatusDTO::class);
    }

    public function deleteIndentifier(string $contactId, ContactIdentifierType $identifierType, string $identifierValue): JobDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response =$this->sendgridClient->delete(
            endpoint: self::BASE_ENDPOINT . '/' . $contactId . '/identifiers',
            body: [
                'identifier_type' => $identifierType->value,
                'identifier_value' => $identifierValue
            ]
        );

        return $this->castSingleResponse($response, JobDTO::class);
    }
}
