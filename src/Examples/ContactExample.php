<?php

/**
 * SendGrid Contact Management Examples
 * 
 * This file demonstrates how to use the Contact entity from the SendgridCampaign library.
 * Run from command line: php ContactExample.php [command] [options]
 * 
 * @package SendgridCampaign
 * @example php ContactExample.php list-commands
 */

declare(strict_types=1);

use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\DTO\BaseListDto;
use SendgridCampaign\Entities\Contact\Contact;
use SendgridCampaign\Entities\Contact\DTO\ContactCountDTO;
use SendgridCampaign\Entities\Contact\DTO\ContactDTO;
use SendgridCampaign\Entities\Contact\DTO\ContactExportStatusDTO;
use SendgridCampaign\Entities\Contact\DTO\ContactImportDTO;
use SendgridCampaign\Entities\Contact\DTO\ContactImportStatusDTO;
use SendgridCampaign\Entities\Contact\DTO\ContactSearchDTO;
use SendgridCampaign\Entities\Contact\Enums\ContactIdentifierType;
use SendgridCampaign\Entities\Contact\Enums\ContactImportFileType;
use SendgridCampaign\Entities\Job\DTO\JobDTO;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Contact Management Service
 * 
 * Wraps the SendGrid Contact entity with convenient helper methods
 * and consistent error handling.
 */
final class ContactService
{
    private Contact $contact;

    public function __construct(
        private readonly string $apiKey
    ) {
        $this->contact = new Contact(apiKey: $this->apiKey);
    }

    /**
     * Get a sample of contacts from your account
     */
    public function getSample(): BaseListDto|BaseErrorDTO
    {
        return $this->contact->getSample();
    }

    /**
     * Retrieve a single contact by their unique ID
     *
     * @param string $contactId The SendGrid contact ID (UUID format)
     */
    public function getById(string $contactId): ContactDTO|BaseErrorDTO
    {
        return $this->contact->getById($contactId);
    }

    /**
     * Look up contacts by email addresses and optional identifiers
     *
     * @param array<string> $emails List of email addresses to search
     * @param string|null $phoneNumberId Optional phone number identifier
     * @param string|null $externalId Optional external system identifier
     * @param string|null $anonymousId Optional anonymous tracking identifier
     * @return array<ContactDTO>|BaseErrorDTO
     */
    public function getByEmail(
        array $emails,
        ?string $phoneNumberId = null,
        ?string $externalId = null,
        ?string $anonymousId = null
    ): array|BaseErrorDTO {
        return $this->contact->getByEmail(
            emails: $emails,
            phone_number_id: $phoneNumberId,
            external_id: $externalId,
            anonymous_id: $anonymousId
        );
    }

    /**
     * Create new contacts or update existing ones (upsert operation)
     *
     * @param array<ContactDTO> $contacts Contacts to create or update
     * @param array<string>|null $listIds Optional list IDs to add contacts to
     */
    public function createOrUpdate(array $contacts, ?array $listIds = null): JobDTO|BaseErrorDTO
    {
        return $this->contact->createOrUpdate(
            contacts: $contacts,
            listIds: $listIds
        );
    }

    /**
     * Delete contacts by ID or delete all contacts
     *
     * @param array<string> $contactIds Specific contact IDs to delete
     * @param bool $deleteAll If true, deletes ALL contacts (use with caution!)
     */
    public function delete(array $contactIds = [], bool $deleteAll = false): JobDTO|BaseErrorDTO
    {
        return $this->contact->delete(
            contactIds: $contactIds,
            deleteAll: $deleteAll
        );
    }

    /**
     * Import contacts from CSV content string
     *
     * @param string $csvContent Raw CSV data as a string
     * @param array<string>|null $listIds Lists to add imported contacts to
     * @param array<string|null>|null $fieldMappings Column-to-field mappings (see examples below)
     */
    public function importFromCsv(
        string $csvContent,
        ?array $listIds = null,
        ?array $fieldMappings = null
    ): ContactImportDTO|BaseErrorDTO {
        return $this->contact->import(
            fileContent: $csvContent,
            fileType: ContactImportFileType::CSV,
            listIds: $listIds,
            fieldMappings: $fieldMappings
        );
    }

    /**
     * Import contacts from a local file path or remote URL
     *
     * Supports CSV and JSON files. For URLs, the file must be publicly accessible.
     *
     * @param string $filePath Local filesystem path or HTTP(S) URL
     * @param array<string>|null $listIds Lists to add imported contacts to
     * @param array<string|null>|null $fieldMappings Column-to-field mappings
     */
    public function importFromFile(
        string $filePath,
        ?array $listIds = null,
        ?array $fieldMappings = null
    ): ContactImportDTO|BaseErrorDTO {
        return $this->contact->importFromFile(
            filePath: $filePath,
            listIds: $listIds,
            fieldMappings: $fieldMappings
        );
    }

    /**
     * Check the status of an import job
     *
     * @param string $jobId The job ID returned from an import operation
     */
    public function getImportStatus(string $jobId): ContactImportStatusDTO|BaseErrorDTO
    {
        return $this->contact->getImportStatus($jobId);
    }

    /**
     * Search contacts using SGQL (SendGrid Query Language)
     *
     * @param string $query SGQL query string (e.g., 'email LIKE "%@example.com"')
     * @see https://docs.sendgrid.com/for-developers/sending-email/segmentation-query-language
     */
    public function search(string $query): ContactSearchDTO|BaseErrorDTO
    {
        return $this->contact->search($query);
    }

    /**
     * Get the total count of contacts in your account
     */
    public function getCount(): ContactCountDTO|BaseErrorDTO
    {
        return $this->contact->getCount();
    }

    /**
     * Start an export job for all contacts
     *
     * @param bool $sendEmailNotification Whether to email when export completes
     */
    public function export(bool $sendEmailNotification = true): JobDTO|BaseErrorDTO
    {
        return $this->contact->export(sendEmailNotification: $sendEmailNotification);
    }

    /**
     * Check the status of an export job
     *
     * @param string $jobId The job ID returned from an export operation
     */
    public function getExportStatus(string $jobId): ContactExportStatusDTO|BaseErrorDTO
    {
        return $this->contact->exportStatus($jobId);
    }

    /**
     * Get status of all export jobs
     */
    public function getAllExportsStatus(): BaseListDto|BaseErrorDTO
    {
        return $this->contact->getAllExportsStatus();
    }

    /**
     * Remove a specific identifier from a contact
     *
     * Use this to remove an email, phone, external_id, or anonymous_id from a contact
     * without deleting the entire contact record.
     *
     * @param string $contactId The contact's UUID
     * @param ContactIdentifierType $identifierType Type of identifier to remove
     * @param string $identifierValue The identifier value to remove
     */
    public function deleteIdentifier(
        string $contactId,
        ContactIdentifierType $identifierType,
        string $identifierValue
    ): JobDTO|BaseErrorDTO {
        return $this->contact->deleteIndentifier(
            contactId: $contactId,
            identifierType: $identifierType,
            identifierValue: $identifierValue
        );
    }
}

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

/**
 * Pretty print a result with error handling
 * 
 * @param mixed $result The API response to display
 * @param string $operation Description of the operation performed
 */
function printResult(mixed $result, string $operation): void
{
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "Operation: {$operation}\n";
    echo str_repeat('=', 60) . "\n";

    if ($result instanceof BaseErrorDTO) {
        echo "❌ ERROR:\n";
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        echo "✅ SUCCESS:\n";
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    echo "\n";
}

/**
 * Display available commands
 */
function showHelp(): void
{
    echo <<<HELP
    
SendGrid Contact Management Examples
====================================

Usage: php ContactExample.php <command> [options]

Available Commands:
-------------------
  sample                      Get a sample of contacts
  count                       Get total contact count
  get <contact_id>            Get a contact by ID
  search <query>              Search contacts using SGQL
  lookup <email1,email2,...>  Look up contacts by email
  create                      Create sample contacts (demo)
  delete <id1,id2,...>        Delete specific contacts
  delete-all                  Delete ALL contacts (dangerous!)
  import-csv                  Import from CSV string (demo)
  import-file <path>          Import from file or URL
  import-status <job_id>      Check import job status
  export                      Start a contact export
  export-status <job_id>      Check export job status
  exports                     List all export jobs
  help                        Show this help message

Environment:
------------
  Set SENDGRID_API_KEY environment variable or edit this file.

Examples:
---------
  php ContactExample.php sample
  php ContactExample.php search 'email LIKE "%@gmail.com"'
  php ContactExample.php lookup user1@example.com,user2@example.com
  php ContactExample.php import-file ./contacts.csv

HELP;
}

// =============================================================================
// CLI RUNNER
// =============================================================================

/**
 * Main entry point for CLI execution
 */
function main(array $argv): int
{
    // Configuration - prefer environment variable
    $apiKey = getenv('SENDGRID_API_KEY') ?: '';

    if (empty($apiKey)) {
        echo "⚠️  Warning: SENDGRID_API_KEY not set. Set it as an environment variable.\n";
        echo "   Example: SENDGRID_API_KEY='your-key' php ContactExample.php sample\n\n";
        return 1;
    }

    $service = new ContactService($apiKey);
    $command = $argv[1] ?? 'help';

    switch ($command) {
        case 'sample':
            printResult($service->getSample(), 'Get Sample Contacts');
            break;

        case 'count':
            printResult($service->getCount(), 'Get Contact Count');
            break;

        case 'get':
            $contactId = $argv[2] ?? null;
            if (!$contactId) {
                echo "Error: Contact ID required. Usage: php ContactExample.php get <contact_id>\n";
                return 1;
            }
            printResult($service->getById($contactId), "Get Contact: {$contactId}");
            break;

        case 'search':
            $query = $argv[2] ?? null;
            if (!$query) {
                echo "Error: Query required. Usage: php ContactExample.php search '<query>'\n";
                echo "Example: php ContactExample.php search 'email LIKE \"%@example.com\"'\n";
                return 1;
            }
            printResult($service->search($query), "Search: {$query}");
            break;

        case 'lookup':
            $emails = $argv[2] ?? null;
            if (!$emails) {
                echo "Error: Emails required. Usage: php ContactExample.php lookup <email1,email2>\n";
                return 1;
            }
            $emailList = explode(',', $emails);
            printResult($service->getByEmail($emailList), "Lookup Emails");
            break;

        case 'create':
            // Demo: Create sample contacts
            $contacts = [
                ContactDTO::fromArray([
                    'email' => 'demo1@example.com',
                    'first_name' => 'Demo',
                    'last_name' => 'User One',
                ]),
                ContactDTO::fromArray([
                    'email' => 'demo2@example.com',
                    'first_name' => 'Demo',
                    'last_name' => 'User Two',
                ]),
            ];
            printResult($service->createOrUpdate($contacts), 'Create/Update Contacts');
            break;

        case 'delete':
            $ids = $argv[2] ?? null;
            if (!$ids) {
                echo "Error: Contact IDs required. Usage: php ContactExample.php delete <id1,id2>\n";
                return 1;
            }
            $idList = explode(',', $ids);
            printResult($service->delete($idList), 'Delete Contacts');
            break;

        case 'delete-all':
            echo "⚠️  WARNING: This will delete ALL contacts!\n";
            echo "Type 'yes' to confirm: ";
            $confirmation = trim(fgets(STDIN));
            if ($confirmation !== 'yes') {
                echo "Aborted.\n";
                return 0;
            }
            printResult($service->delete(deleteAll: true), 'Delete All Contacts');
            break;

        case 'import-csv':
            // Demo: Import from CSV string
            $csvContent = <<<CSV
                email,first_name,last_name
                import1@example.com,Import,User One
                import2@example.com,Import,User Two
                import3@example.com,Import,User Three
                CSV;
            printResult($service->importFromCsv($csvContent), 'Import from CSV String');
            break;

        case 'import-file':
            $filePath = $argv[2] ?? null;
            if (!$filePath) {
                echo "Error: File path required. Usage: php ContactExample.php import-file <path>\n";
                return 1;
            }
            printResult($service->importFromFile($filePath), "Import from: {$filePath}");
            break;

        case 'import-status':
            $jobId = $argv[2] ?? null;
            if (!$jobId) {
                echo "Error: Job ID required. Usage: php ContactExample.php import-status <job_id>\n";
                return 1;
            }
            printResult($service->getImportStatus($jobId), "Import Status: {$jobId}");
            break;

        case 'export':
            printResult($service->export(), 'Export Contacts');
            break;

        case 'export-status':
            $jobId = $argv[2] ?? null;
            if (!$jobId) {
                echo "Error: Job ID required. Usage: php ContactExample.php export-status <job_id>\n";
                return 1;
            }
            printResult($service->getExportStatus($jobId), "Export Status: {$jobId}");
            break;

        case 'exports':
            printResult($service->getAllExportsStatus(), 'All Export Jobs');
            break;

        case 'help':
        case '--help':
        case '-h':
        default:
            showHelp();
            break;
    }

    return 0;
}

// =============================================================================
// ADVANCED EXAMPLES (for reference)
// =============================================================================

/**
 * Example: Import with custom field mappings
 * 
 * When your CSV columns don't match SendGrid's expected field names,
 * use field mappings to specify how columns should be mapped.
 * 
 * Standard field mapping codes:
 *   - _rf0_T = first_name
 *   - _rf1_T = last_name  
 *   - _rf2_T = email
 *   - _rf3_T = alternate_emails
 *   - _rf4_T = address_line_1
 *   - _rf5_T = address_line_2
 *   - _rf6_T = city
 *   - _rf7_T = state_province_region
 *   - _rf8_T = postal_code
 *   - _rf9_T = country
 *   - _rf10_T = phone_number
 *   - _rf11_T = whatsapp
 *   - _rf12_T = line
 *   - _rf13_T = facebook
 *   - _rf14_T = unique_name
 *   - _rf15_T = external_id
 *   - _rf16_T = anonymous_id
 *   - null = skip this column
 *   - For custom fields, use the custom field ID
 */
function exampleImportWithFieldMappings(ContactService $service): void
{
    $csvContent = <<<CSV
        contact_email,fname,lname,company_name
        john@example.com,John,Doe,Acme Inc
        jane@example.com,Jane,Smith,Tech Corp
        CSV;

    $result = $service->importFromCsv(
        csvContent: $csvContent,
        listIds: ['your-list-id-here'],
        fieldMappings: [
            '_rf2_T',  // Column 0 (contact_email) -> email
            '_rf0_T',  // Column 1 (fname) -> first_name
            '_rf1_T',  // Column 2 (lname) -> last_name
            null,      // Column 3 (company_name) -> skip
        ]
    );

    printResult($result, 'Import with Field Mappings');
}

/**
 * Example: Delete a specific identifier from a contact
 * 
 * Useful when you need to remove an external_id or alternate email
 * without deleting the entire contact.
 */
function exampleDeleteIdentifier(ContactService $service): void
{
    $result = $service->deleteIdentifier(
        contactId: '89dd1b8a-38e1-4da9-8edc-241a9ff9f923',
        identifierType: ContactIdentifierType::EXTERNALID,
        identifierValue: 'old-external-id-123'
    );

    printResult($result, 'Delete Identifier');
}

/**
 * Example: Create contacts with all available fields
 */
function exampleCreateFullContact(ContactService $service): void
{
    $contacts = [
        ContactDTO::fromArray([
            'email' => 'complete@example.com',
            'first_name' => 'Complete',
            'last_name' => 'Contact',
            'address_line_1' => '123 Main Street',
            'address_line_2' => 'Suite 100',
            'city' => 'San Francisco',
            'state_province_region' => 'CA',
            'postal_code' => '94102',
            'country' => 'USA',
            'phone_number' => '+1-555-123-4567',
            'external_id' => 'crm-12345',
            // Add custom fields as needed:
            // 'custom_field_id' => 'value',
        ]),
    ];

    $result = $service->createOrUpdate(
        contacts: $contacts,
        listIds: ['marketing-list-id', 'newsletter-list-id']
    );

    printResult($result, 'Create Full Contact');
}

/**
 * Example: Search with complex SGQL queries
 */
function exampleAdvancedSearch(ContactService $service): void
{
    // Find contacts with Gmail addresses added in the last 30 days
    $query = 'email LIKE "%@gmail.com" AND created_at > "2024-01-01"';

    $result = $service->search($query);

    printResult($result, 'Advanced Search');
}

// Run CLI if executed directly
if (php_sapi_name() === 'cli' && isset($argv)) {
    exit(main($argv));
}