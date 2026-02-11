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
use SendgridCampaign\Exceptions\EmptyContactsException;

/**
 * SendGrid Contact Management Entity
 * 
 * This class provides a complete interface for managing contacts in SendGrid
 * Marketing Campaigns. Contacts are the foundation of email marketing - they
 * represent the recipients of your email campaigns.
 * 
 * Key capabilities:
 * - CRUD operations for contacts (create, read, update, delete)
 * - Bulk import from CSV/JSON files or strings
 * - Search contacts using SendGrid Query Language (SGQL)
 * - Export contacts for backup or external processing
 * - Manage contact identifiers (email, phone, external_id, etc.)
 * 
 * Most operations are asynchronous and return a job ID that can be used
 * to track progress via the status methods.
 * 
 * @package SendgridCampaign\Entities\Contact
 * @see https://docs.sendgrid.com/api-reference/contacts
 * 
 * @example
 * $contact = new Contact('your-api-key');
 * 
 * // Get a sample of contacts
 * $sample = $contact->getSample();
 * 
 * // Search for contacts
 * $results = $contact->search('email LIKE "%@gmail.com"');
 */
class Contact extends BaseEntity
{

    public const BASE_ENDPOINT = 'marketing/contacts';

    /**
     * @return BaseListDto<ContactDTO>|BaseErrorDTO
     */

    /**
     * Retrieves a sample of contacts from your SendGrid account.
     * 
     * This method fetches a representative sample of contacts to help you
     * understand your data structure without loading all contacts. Useful for:
     * - Testing your integration
     * - Previewing contact data format
     * - Debugging field mappings
     * 
     * The sample size is determined by SendGrid (typically 50 contacts).
     * 
     * @return BaseListDto<ContactDTO>|BaseErrorDTO Returns a BaseListDto containing
     *         an array of ContactDTO objects on success, or a BaseErrorDTO with
     *         error details if the request fails.
     * 
     * @see https://docs.sendgrid.com/api-reference/contacts/get-sample-contacts
     * 
     * @example
     * $sample = $contact->getSample();
     * if (!$sample instanceof BaseErrorDTO) {
     *     foreach ($sample->result as $contactDto) {
     *         echo "Email: {$contactDto->email}\n";
     *     }
     * }
     */
    public function getSample(): BaseListDto|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT
        );

        return $this->castListResponse($response, ContactDTO::class);
    }

    /**
     * Retrieves a single contact by their unique SendGrid identifier.
     * 
     * Fetches complete contact information including all standard fields
     * (email, first_name, last_name, address, etc.) and any custom fields
     * you've defined in your account.
     * 
     * @param string $contactId The unique identifier (UUID) assigned by SendGrid
     *                          when the contact was created. This is NOT the email
     *                          address - use getByEmail() to search by email.
     * 
     * @return ContactDTO|BaseErrorDTO Returns a ContactDTO with all contact data
     *         on success, or a BaseErrorDTO if the contact doesn't exist or
     *         another error occurs.
     * 
     * @see https://docs.sendgrid.com/api-reference/contacts/get-a-contact-by-id
     * 
     * @example
     * $contact = $entity->getById('abc123-def456-ghi789');
     * if (!$contact instanceof BaseErrorDTO) {
     *     echo "Found: {$contact->first_name} {$contact->last_name}";
     *     echo "Email: {$contact->email}";
     * }
     */
    public function getById(string $contactId): ContactDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/' . $contactId
        );

        return $this->castSingleResponse($response, ContactDTO::class);
    }

    /**
     * Looks up contacts by email addresses and optional alternative identifiers.
     * 
     * This is the primary method for finding existing contacts when you know
     * their email address but not their SendGrid contact ID. You can search
     * for multiple emails at once (up to 50 per request).
     * 
     * Alternative identifiers allow you to search by:
     * - Phone number ID: For contacts with phone numbers
     * - External ID: Your system's internal identifier
     * - Anonymous ID: For tracking anonymous website visitors
     * 
     * @param array<string> $emails Array of email addresses to search for.
     *                              Maximum 50 emails per request.
     * @param string|null $phone_number_id Optional phone number identifier to search.
     * @param string|null $external_id Optional external system ID to search.
     *                                  Useful when syncing with CRM or other systems.
     * @param string|null $anonymous_id Optional anonymous tracking ID.
     * 
     * @return array<ContactDTO>|BaseErrorDTO Returns an array of ContactDTO objects
     *         for each found contact. Contacts not found are simply not included
     *         in the results. Returns BaseErrorDTO on API errors.
     * 
     * @see https://docs.sendgrid.com/api-reference/contacts/get-contacts-by-emails
     * 
     * @example
     * // Search by email only
     * $contacts = $entity->getByEmail(['john@example.com', 'jane@example.com']);
     * 
     * // Search with external ID for CRM sync
     * $contacts = $entity->getByEmail(
     *     emails: ['john@example.com'],
     *     external_id: 'CRM-12345'
     * );
     */
    public function getByEmail(
        array $emails,
        ?string $phone_number_id,
        ?string $external_id,
        ?string $anonymous_id
    ): array|BaseErrorDTO {
        $this->validateApiKey();

        $response = $this->sendgridClient->post(
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
     * Creates new contacts or updates existing ones (upsert operation).
     * 
     * This is the primary method for adding contacts to SendGrid. It performs
     * an "upsert" operation: if a contact with the given email already exists,
     * it will be updated with the new data; otherwise, a new contact is created.
     * 
     * Key features:
     * - Bulk processing: Add up to 30,000 contacts per request
     * - Automatic deduplication: Contacts are matched by email address
     * - List assignment: Optionally add contacts to one or more lists
     * - Asynchronous: Returns immediately with a job ID
     * 
     * IMPORTANT: This operation is asynchronous. Use getImportStatus() with
     * the returned job ID to check when the import is complete.
     * 
     * @param array<ContactDTO> $contacts Array of ContactDTO objects to create/update.
     *                                    Each must have at least an email address.
     *                                    Maximum 30,000 contacts per request.
     * @param array<string>|null $listIds Optional array of list IDs to add contacts to.
     *                                    Contacts will be added to all specified lists.
     * 
     * @return JobDTO|BaseErrorDTO Returns a JobDTO containing the job_id for tracking
     *         the import status, or BaseErrorDTO on validation/API errors.
     * 
     * @throws EmptyContactsException If the contacts array is empty.
     * 
     * @see https://docs.sendgrid.com/api-reference/contacts/add-or-update-a-contact
     * @see getImportStatus() To check the status of the import job
     * 
     * @example
     * // Create contacts with basic info
     * $contacts = [
     *     ContactDTO::fromArray([
     *         'email' => 'john@example.com',
     *         'first_name' => 'John',
     *         'last_name' => 'Doe'
     *     ]),
     *     ContactDTO::fromArray([
     *         'email' => 'jane@example.com',
     *         'first_name' => 'Jane'
     *     ])
     * ];
     * 
     * $job = $entity->createOrUpdate($contacts, ['newsletter-list-id']);
     * if (!$job instanceof BaseErrorDTO) {
     *     echo "Import started. Job ID: {$job->job_id}";
     *     // Check status later with getImportStatus($job->job_id)
     * }
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

        $response = $this->sendgridClient->put(
            endpoint: self::BASE_ENDPOINT,
            body: $body
        );

        return $this->castSingleResponse($response, JobDTO::class);
    }

    /**
     * Deletes contacts from your SendGrid account.
     * 
     * You can delete specific contacts by ID, or delete ALL contacts from
     * your account. Deleted contacts cannot be recovered.
     * 
     * This operation is asynchronous - use the returned job ID to track
     * completion if needed.
     * 
     * ⚠️ WARNING: Using deleteAll: true will PERMANENTLY DELETE all contacts
     * from your account. This action cannot be undone. All contacts will be
     * removed from all lists and segments.
     * 
     * @param array<string> $contactIds Array of contact IDs (UUIDs) to delete.
     *                                  Maximum 100 IDs per request.
     *                                  Required when deleteAll is false.
     * @param bool $deleteAll When true, deletes ALL contacts in your account.
     *                        The contactIds parameter is ignored when this is true.
     *                        Default: false.
     * 
     * @return JobDTO|BaseErrorDTO Returns a JobDTO with the job_id for the
     *         deletion job, or BaseErrorDTO on errors.
     * 
     * @see https://docs.sendgrid.com/api-reference/contacts/delete-contacts
     * 
     * @example
     * // Delete specific contacts
     * $job = $entity->delete(['contact-id-1', 'contact-id-2']);
     * 
     * // Delete ALL contacts (use with extreme caution!)
     * $job = $entity->delete(deleteAll: true);
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

        $response = $this->sendgridClient->delete(
            endpoint: self::BASE_ENDPOINT,
            queryParams: $queryParams
        );

        return $this->castSingleResponse($response, JobDTO::class);
    }

    /**
     * Imports contacts from file content (CSV or JSON format).
     * 
     * This method allows you to import contacts by providing the file content
     * as a string. This is useful when you have dynamically generated data
     * or when you want to process file content before importing.
     * 
     * For CSV files:
     * - First row must be headers
     * - Email column is required
     * - Use field mappings for non-standard column names
     * 
     * For JSON files:
     * - Must be an array of contact objects
     * - Each object must have an "email" property
     * 
     * Field Mapping Reference (for non-standard CSV columns):
     * - '_rf0_T' = first_name
     * - '_rf1_T' = last_name
     * - '_rf2_T' = email
     * - '_rf3_T' = alternate_emails
     * - '_rf4_T' = address_line_1
     * - '_rf5_T' = address_line_2
     * - '_rf6_T' = city
     * - '_rf7_T' = state_province_region
     * - '_rf8_T' = postal_code
     * - '_rf9_T' = country
     * - '_rf10_T' = phone_number
     * - '_rf11_T' = whatsapp
     * - '_rf12_T' = line
     * - '_rf13_T' = facebook
     * - '_rf14_T' = unique_name
     * - '_rf15_T' = external_id
     * - '_rf16_T' = anonymous_id
     * - null = skip column
     * - Custom field IDs for custom fields
     * 
     * @param string $fileContent The raw file content as a string.
     * @param ContactImportFileType $fileType The format of the file content
     *                                        (CSV or JSON).
     * @param array<string>|null $listIds Optional list IDs to add imported
     *                                    contacts to.
     * @param array<string|null>|null $fieldMappings Array mapping column indices
     *                                               to field IDs. Use null for
     *                                               columns to skip.
     * 
     * @return ContactImportDTO|BaseErrorDTO Returns ContactImportDTO with job_id
     *         on success, or BaseErrorDTO on failure.
     * 
     * @see https://docs.sendgrid.com/api-reference/contacts/import-contacts
     * 
     * @example
     * // Import from CSV string
     * $csv = "email,first_name,last_name\njohn@test.com,John,Doe";
     * $result = $entity->import(
     *     fileContent: $csv,
     *     fileType: ContactImportFileType::CSV
     * );
     * 
     * // Import with custom field mappings
     * $csv = "user_email,fname,lname\njohn@test.com,John,Doe";
     * $result = $entity->import(
     *     fileContent: $csv,
     *     fileType: ContactImportFileType::CSV,
     *     fieldMappings: ['_rf2_T', '_rf0_T', '_rf1_T']  // email, first_name, last_name
     * );
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

        $uploadResponse = $this->sendgridClient->put(
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
     * Imports contacts from a local file path or remote URL.
     * 
     * A convenience method that handles reading file content and detecting
     * file type automatically based on the file extension. Supports both
     * local filesystem paths and publicly accessible URLs.
     * 
     * Supported file types:
     * - .csv - Comma-separated values
     * - .json - JSON array of contact objects
     * 
     * For URLs, the file must be publicly accessible (no authentication).
     * The method will download the file content before uploading to SendGrid.
     * 
     * @param string $filePath Path to a local file OR a publicly accessible URL.
     *                         File extension is used to detect format.
     * @param array<string>|null $listIds Optional list IDs to add contacts to.
     * @param array<string|null>|null $fieldMappings Column-to-field mappings.
     *                                               See import() for mapping reference.
     * 
     * @return ContactImportDTO|BaseErrorDTO Returns ContactImportDTO with job info
     *         on success, or BaseErrorDTO on failure.
     * 
     * @see import() For field mapping details and format specifications
     * 
     * @example
     * // Import from local file
     * $result = $entity->importFromFile('/path/to/contacts.csv');
     * 
     * // Import from URL with list assignment
     * $result = $entity->importFromFile(
     *     filePath: 'https://example.com/data/contacts.csv',
     *     listIds: ['marketing-list-id']
     * );
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


    /**
     * Checks the status of a contact import job.
     * 
     * Since imports are processed asynchronously, use this method to track
     * the progress of an import initiated by createOrUpdate(), import(),
     * or importFromFile().
     * 
     * Status progression:
     * 1. 'pending' - Job is queued, not yet started
     * 2. 'processing' - Job is currently being processed
     * 3. 'completed' - Job finished successfully
     * 4. 'failed' - Job encountered an error
     * 
     * When completed, the response includes detailed results about how many
     * contacts were created, updated, or had errors.
     * 
     * @param string $jobId The job ID returned from an import operation.
     * 
     * @return ContactImportStatusDTO|BaseErrorDTO Returns status information including:
     *         - status: Current job status
     *         - results: Counts (created, updated, errors)
     *         - started_at/finished_at: Timestamps
     *         Or BaseErrorDTO if the job ID is invalid or other errors occur.
     * 
     * @see https://docs.sendgrid.com/api-reference/contacts/import-contacts-status
     * 
     * @example
     * $status = $entity->getImportStatus('job-id-from-import');
     * 
     * if (!$status instanceof BaseErrorDTO) {
     *     echo "Status: {$status->status}\n";
     *     
     *     if ($status->results) {
     *         echo "Created: {$status->results->created_count}\n";
     *         echo "Updated: {$status->results->updated_count}\n";
     *         echo "Errors: {$status->results->errored_count}\n";
     *     }
     * }
     */
    public function getImportStatus(string $jobId): ContactImportStatusDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/imports/' . $jobId
        );

        return $this->castSingleResponse($response, ContactImportStatusDTO::class);
    }

    /**
     * Searches contacts using SendGrid Query Language (SGQL).
     * 
     * SGQL is a powerful query language that allows you to search contacts
     * based on any field (standard or custom) using various operators.
     * 
     * Supported operators:
     * - = : Exact match (email = 'test@example.com')
     * - != : Not equal
     * - LIKE : Pattern matching with % wildcard (email LIKE '%@gmail.com')
     * - IS NULL, IS NOT NULL : Check for empty/non-empty values
     * - > < >= <= : Comparison for numbers and dates
     * - AND, OR, NOT : Combine conditions
     * - IN : Match any value in a list
     * 
     * @param string $query SGQL query string. Maximum 4096 characters.
     *                      Field names must match SendGrid's field names exactly.
     * 
     * @return ContactSearchDTO|BaseErrorDTO Returns ContactSearchDTO containing:
     *         - result: Array of matching ContactDTO objects
     *         - contact_count: Total number of matches
     *         Or BaseErrorDTO on query syntax errors or API failures.
     * 
     * @see https://docs.sendgrid.com/api-reference/contacts/search-contacts
     * @see https://docs.sendgrid.com/for-developers/sending-email/segmentation-query-language
     * 
     * @example
     * // Find Gmail users
     * $results = $entity->search('email LIKE "%@gmail.com"');
     * 
     * // Find contacts by name created after a date
     * $results = $entity->search(
     *     'first_name = "John" AND created_at > "2024-01-01"'
     * );
     * 
     * // Search by custom field
     * $results = $entity->search('customer_type = "premium"');
     */
    public function search(string $query): ContactSearchDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT . '/search',
            body: ['query' => $query]
        );

        return $this->castSingleResponse($response, ContactSearchDTO::class);
    }

    /**
     * Gets the total count of contacts in your SendGrid account.
     * 
     * Returns both the total contact count and the billable contact count.
     * These may differ because:
     * - Billable count only includes unique email addresses
     * - Duplicate emails across different contacts count as one billable contact
     * 
     * Use this to monitor your contact usage against your plan limits.
     * 
     * @return ContactCountDTO|BaseErrorDTO Returns ContactCountDTO containing:
     *         - contact_count: Total contacts in your account
     *         - billable_count: Number counting toward plan limits
     *         Or BaseErrorDTO on failures.
     * 
     * @see https://docs.sendgrid.com/api-reference/contacts/get-total-contact-count
     * 
     * @example
     * $count = $entity->getCount();
     * if (!$count instanceof BaseErrorDTO) {
     *     echo "Total contacts: {$count->contact_count}\n";
     *     echo "Billable: {$count->billable_count}\n";
     * }
     */
    public function getCount(): ContactCountDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/count'
        );

        return $this->castSingleResponse($response, ContactCountDTO::class);
    }

    /**
     * Initiates an export of all contacts from your SendGrid account.
     * 
     * This creates an asynchronous job to export your contacts to downloadable
     * files. Once complete, use exportStatus() to get the download URLs.
     * 
     * Export features:
     * - Exports all contacts with all fields
     * - Files are compressed (gzip)
     * - Large exports may be split into multiple files
     * - Download links expire after ~24 hours
     * 
     * @param bool $sendEmailNotification If true, SendGrid sends an email to
     *                                    the account owner when export is ready.
     *                                    Default: true.
     * 
     * @return JobDTO|BaseErrorDTO Returns JobDTO with job_id to track the export,
     *         or BaseErrorDTO on failures.
     * 
     * @see exportStatus() To check status and get download URLs
     * @see https://docs.sendgrid.com/api-reference/contacts/export-contacts
     * 
     * @example
     * $job = $entity->export(sendEmailNotification: true);
     * if (!$job instanceof BaseErrorDTO) {
     *     // Save job ID to check status later
     *     $exportJobId = $job->job_id;
     *     
     *     // Later, check: $entity->exportStatus($exportJobId)
     * }
     */
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

        $response = $this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT . '/exports',
            body: $body
        );

        return $this->castSingleResponse($response, JobDTO::class);
    }

    /**
     * Checks the status of a contact export job.
     * 
     * Use this to track an export initiated by export() and retrieve the
     * download URLs when the export is complete.
     * 
     * Status values:
     * - 'pending': Export is queued, not started
     * - 'ready': Export complete, download URLs available
     * - 'failure': Export failed (check error details)
     * 
     * When status is 'ready', the urls array contains download links.
     * These links are time-limited (typically 24 hours).
     * 
     * @param string $jobId The job ID returned from export().
     * 
     * @return ContactExportStatusDTO|BaseErrorDTO Returns status including:
     *         - status: Current export status
     *         - urls: Array of download URLs (when ready)
     *         - contact_count: Number of exported contacts
     *         - expires_at: When download links expire
     *         Or BaseErrorDTO if job not found or other errors.
     * 
     * @see https://docs.sendgrid.com/api-reference/contacts/export-contacts-status
     * 
     * @example
     * $status = $entity->exportStatus($exportJobId);
     * 
     * if (!$status instanceof BaseErrorDTO) {
     *     if ($status->status->value === 'ready') {
     *         foreach ($status->urls as $url) {
     *             echo "Download: {$url}\n";
     *             // Use file_get_contents() or curl to download
     *         }
     *     } else {
     *         echo "Export still processing...";
     *     }
     * }
     */
    public function exportStatus(string $jobId): ContactExportStatusDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/exports/' . $jobId
        );

        return $this->castSingleResponse($response, ContactExportStatusDTO::class);
    }

    /**
     * Retrieves the status of all contact export jobs.
     * 
     * Lists all export jobs in your account, including completed, pending,
     * and failed exports. Useful for:
     * - Finding a specific export job
     * - Monitoring multiple exports
     * - Reviewing export history
     * 
     * @return BaseListDto<ContactExportStatusDTO>|BaseErrorDTO Returns a list of
     *         all export jobs with their statuses, or BaseErrorDTO on failure.
     * 
     * @see https://docs.sendgrid.com/api-reference/contacts/get-all-existing-exports
     * 
     * @example
     * $exports = $entity->getAllExportsStatus();
     * 
     * if (!$exports instanceof BaseErrorDTO) {
     *     foreach ($exports->result as $export) {
     *         echo "Job {$export->id}: {$export->status->value}\n";
     *     }
     * }
     */
    public function getAllExportsStatus(): BaseListDto|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/exports'
        );

        return $this->castListResponse($response, ContactAllExportsStatusDTO::class);
    }

    /**
     * Removes a specific identifier from a contact.
     * 
     * This allows you to remove an identifier (email, phone, external_id, etc.)
     * from a contact WITHOUT deleting the entire contact record. Use cases:
     * - Removing an outdated external_id when switching systems
     * - Removing a secondary email address
     * - Clearing an anonymous_id after user identification
     * 
     * Note: You cannot remove a contact's primary/only email address.
     * The contact must have at least one identifier remaining.
     * 
     * @param string $contactId The contact's unique ID (UUID).
     * @param ContactIdentifierType $identifierType The type of identifier to remove:
     *        - EMAIL: An email address
     *        - PHONENUMBERID: Phone number identifier
     *        - EXTERNALID: Your custom external ID
     *        - ANONYMOUSID: Anonymous tracking ID
     * @param string $identifierValue The actual value of the identifier to remove.
     * 
     * @return JobDTO|BaseErrorDTO Returns JobDTO on success (deletion is async),
     *         or BaseErrorDTO on failures.
     * 
     * @see https://docs.sendgrid.com/api-reference/contacts/delete-a-contact-identifier
     * 
     * @example
     * // Remove an old external ID
     * $result = $entity->deleteIndentifier(
     *     contactId: 'contact-uuid',
     *     identifierType: ContactIdentifierType::EXTERNALID,
     *     identifierValue: 'old-crm-id-12345'
     * );
     * 
     * // Remove an alternate email
     * $result = $entity->deleteIndentifier(
     *     contactId: 'contact-uuid',
     *     identifierType: ContactIdentifierType::EMAIL,
     *     identifierValue: 'old-email@example.com'
     * );
     */
    public function deleteIndentifier(string $contactId, ContactIdentifierType $identifierType, string $identifierValue): JobDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->delete(
            endpoint: self::BASE_ENDPOINT . '/' . $contactId . '/identifiers',
            body: [
                'identifier_type' => $identifierType->value,
                'identifier_value' => $identifierValue
            ]
        );

        return $this->castSingleResponse($response, JobDTO::class);
    }
}
