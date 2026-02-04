<?php

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


$apiKey = ''; // Your SendGrid API Key

function getSample()
{

    $contactEntity = new Contact(
        apiKey: $GLOBALS['apiKey']
    );

    return $contactEntity->getSample();
}

function getById(string $contactId)
{

    $contactEntity = new Contact(
        apiKey: $GLOBALS['apiKey']
    );

    return $contactEntity->getById($contactId);
}

function getByEmail(array $emails, ?string $phone_number_id, ?string $external_id, ?string $anonymous_id): array
{

    $contactEntity = new Contact(
        apiKey: $GLOBALS['apiKey']
    );
    return $contactEntity->getByEmail(
        emails: $emails,
        phone_number_id: $phone_number_id,
        external_id: $external_id,
        anonymous_id: $anonymous_id
    );
}

/**
 * @param ContactDTO[] $contacts
 * @return SendgridCampaign\Entities\Job\DTO\JobDTO
 */
function createOrUpdate(array $contacts, ?array $listIds = null): JobDTO
{

    $contactEntity = new Contact(
        apiKey: $GLOBALS['apiKey']
    );

    return $contactEntity->createOrUpdate(
        contacts: $contacts,
        listIds: $listIds
    );
}

function deleteContacts(array $contactIds = [], bool $deleteAll = false): JobDTO
{
    $contactEntity = new Contact(
        apiKey: $GLOBALS['apiKey']
    );

    return $contactEntity->delete(
        contactIds: $contactIds,
        deleteAll: $deleteAll
    );
}

/**
 * Import contacts from CSV content
 */
function importFromCsvContent(string $csvContent, ?array $listIds = null, ?array $fieldMappings = null): ContactImportDTO
{
    $contactEntity = new Contact(
        apiKey: $GLOBALS['apiKey']
    );

    return $contactEntity->import(
        fileContent: $csvContent,
        fileType: ContactImportFileType::CSV,
        listIds: $listIds,
        fieldMappings: $fieldMappings
    );
}

/**
 * Import contacts from a local file or URL
 */
function importFromFile(string $filePath, ?array $listIds = null, ?array $fieldMappings = null): ContactImportDTO
{
    $contactEntity = new Contact(
        apiKey: $GLOBALS['apiKey']
    );

    return $contactEntity->importFromFile(
        filePath: $filePath,
        listIds: $listIds,
        fieldMappings: $fieldMappings
    );
}

function getImportStatus(string $jobId): ContactImportStatusDTO
{
    $contactEntity = new Contact(
        apiKey: $GLOBALS['apiKey']
    );

    return $contactEntity->getImportStatus($jobId);
}

function search(string $query): ContactSearchDTO
{
    $contactEntity = new Contact(
        apiKey: $GLOBALS['apiKey']
    );

    return $contactEntity->search($query);
}

function getContactsCount(): ContactCountDTO
{
    $contactEntity = new Contact(
        apiKey: $GLOBALS
        ['apiKey']

    );

    return $contactEntity->getCount();
}

function exportContacts(): JobDTO
{
    $contactEntity = new Contact(
        apiKey: $GLOBALS['apiKey']
    );

    return $contactEntity->export(
        sendEmailNotification: true
    );
}

function exportContactStatus(string $jobId): ContactExportStatusDTO
{
    $contactEntity = new Contact(
        apiKey: $GLOBALS['apiKey']
    );
    return $contactEntity->exportStatus($jobId);
}

function getAllExportsStatus(): array
{
    $contactEntity = new Contact(
        apiKey: $GLOBALS['apiKey']
    );
    return $contactEntity->getAllExportsStatus();
}

function deleteIdentifier(string $contactId, ContactIdentifierType $identifierType, string $identifierValue): JobDTO
{
    $contactEntity = new Contact(
        apiKey: $GLOBALS['apiKey']
    );
    return $contactEntity->deleteIndentifier(
        contactId: $contactId,
        identifierType: $identifierType,
        identifierValue: $identifierValue
    );
}

// echo json_encode(
//     deleteIdentifier(
//         contactId: '89dd1b8a-38e1-4da9-8edc-241a9ff9f923',
//         identifierType: ContactIdentifierType::EXTERNALID,
//         identifierValue: 'very-random-string'
//     ),
//     JSON_PRETTY_PRINT
// );

// echo json_encode(
//     getAllExportsStatus(),
//     JSON_PRETTY_PRINT
// );

// echo json_encode(
//     exportContactStatus(
//         jobId: '6cfb205b-e50c-4600-b7a7-6d24e0706d44'
//     ),
//     JSON_PRETTY_PRINT
// );

// echo json_encode(
//     exportContacts(),
//     JSON_PRETTY_PRINT
// );

// echo json_encode(
//     getContactsCount(),
//     JSON_PRETTY_PRINT
// );

// echo json_encode(
//     search(
//         query: 'email LIKE "%@example.com"'
//     ),
//     JSON_PRETTY_PRINT
// );

// echo json_encode(
//     getImportStatus(
//         jobId: 'JOB_ID'
//     ),
//     JSON_PRETTY_PRINT
// );

// =============================================================================
// IMPORT EXAMPLES
// =============================================================================

// Example 1: Import from CSV string
// ----------------------------------
// $csvContent = <<<CSV
// email,first_name,last_name
// john@example.com,John,Doe
// jane@example.com,Jane,Smith
// bob@example.com,Bob,Johnson
// CSV;

// echo json_encode(
//     importFromCsvContent(
//         csvContent: $csvContent,
//         listIds: ['LIST_ID', 'LIST_ID']
//     ),
//     JSON_PRETTY_PRINT
// );

// Example 2: Import from CSV with custom field mappings
// ------------------------------------------------------
// $csvContent = <<<CSV
// contact_email,fname,lname,company
// john@example.com,John,Doe,Acme Inc
// jane@example.com,Jane,Smith,Tech Corp
// CSV;

// echo json_encode(
//     importFromCsvContent(
//         csvContent: $csvContent,
//         listIds: ['LIST_ID', 'LIST_ID'],
//         fieldMappings: [
//             '_rf2_T',   // column 0 (contact_email) -> email
//             '_rf0_T',   // column 1 (fname) -> first_name  
//             '_rf1_T',   // column 2 (lname) -> last_name
//             null        // column 3 (company) -> skip (or use custom field ID)
//         ]
//     ),
//     JSON_PRETTY_PRINT
// );

// Example 4: Import from local file
// ----------------------------------
// echo json_encode(
//     importFromFile(
//         filePath: 'test.csv',
//         listIds: ['LIST_ID']
//     ),
//     JSON_PRETTY_PRINT
// );

// Example 5: Import from URL
// ---------------------------
// echo json_encode(
//     importFromFile(
//         filePath: 'https://gist.githubusercontent.com/balance3840/240acb3169d33a1c1d19a276f6c99d6c/raw/1bc1687cd06e78bfc9605b23c329efe8b57491a6/test.csv',
//         listIds: ['LIST_ID']
//     ),
//     JSON_PRETTY_PRINT
// );

// =============================================================================
// OTHER EXAMPLES
// =============================================================================

// echo json_encode(
//     deleteContacts(
//         deleteAll: true
//     ), 
//     JSON_PRETTY_PRINT
// );

// echo json_encode(
//     createOrUpdate(
//         contacts: [
//             ContactDTO::fromArray([
//                 'email' => 'EMAIL1',
//             ]),
//                         ContactDTO::fromArray([
//                 'email' => 'EMAIL2',
//             ]),
//                         ContactDTO::fromArray([
//                 'email' => 'EMAIL3',
//             ])
//         ],
//         listIds: ['LIST_ID', 'LIST_ID']
//     ), 
//     JSON_PRETTY_PRINT
// );

// echo json_encode(
//     getByEmail(
//         emails: ['EMAIL1', 'EMAIL2'],
//         phone_number_id: null,
//         external_id: null,
//         anonymous_id: null
//     ), 
//     JSON_PRETTY_PRINT
// );

// echo json_encode(getSample(), JSON_PRETTY_PRINT);