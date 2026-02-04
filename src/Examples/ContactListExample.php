<?php

/**
 * SendGrid Contact List Management Examples
 * 
 * This file demonstrates how to use the ContactList entity from the SendgridCampaign library.
 * Contact Lists allow you to organize your contacts into groups for targeted campaigns.
 * 
 * Run from command line: php ContactListExample.php [command] [options]
 * 
 * @package SendgridCampaign
 * @example php ContactListExample.php list-commands
 */

declare(strict_types=1);

use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\DTO\BaseListDto;
use SendgridCampaign\Entities\ContactList\ContactList;
use SendgridCampaign\Entities\ContactList\DTO\ContactListCountDTO;
use SendgridCampaign\Entities\ContactList\Dto\ContactListDTO;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Contact List Management Service
 * 
 * Provides a clean interface for managing SendGrid contact lists.
 * Lists are used to organize contacts for targeted email campaigns and segmentation.
 */
final class ContactListService
{
    private ContactList $contactList;

    public function __construct(
        private readonly string $apiKey
    ) {
        $this->contactList = new ContactList(apiKey: $this->apiKey);
    }

    /**
     * Retrieve all contact lists in your account
     *
     * @return BaseListDto<ContactListDTO>|BaseErrorDTO
     */
    public function getAll(): BaseListDto|BaseErrorDTO
    {
        return $this->contactList->getAll();
    }

    /**
     * Create a new contact list
     *
     * @param string $name The display name for the new list (must be unique)
     */
    public function create(string $name): ContactListDTO|BaseErrorDTO
    {
        return $this->contactList->create($name);
    }

    /**
     * Delete a contact list
     *
     * @param string $listId The unique ID of the list to delete
     * @param bool $deleteContacts If true, also deletes all contacts in the list.
     *                             If false (default), contacts are preserved but removed from this list.
     */
    public function delete(string $listId, bool $deleteContacts = false): void
    {
        $this->contactList->delete($listId, $deleteContacts);
    }

    /**
     * Get a contact list by its ID
     *
     * @param string $listId The unique ID of the list
     * @param bool $includeContactSample If true, includes a sample of contacts in the response
     */
    public function getById(string $listId, bool $includeContactSample = false): ContactListDTO|BaseErrorDTO
    {
        return $this->contactList->getById($listId, $includeContactSample);
    }

    /**
     * Get the number of contacts in a specific list
     *
     * @param string $listId The unique ID of the list
     */
    public function getContactsCount(string $listId): ContactListCountDTO|BaseErrorDTO
    {
        return $this->contactList->getContactsCount($listId);
    }

    /**
     * Update a contact list's name
     *
     * @param string $listId The unique ID of the list to update
     * @param string $newName The new display name for the list
     */
    public function update(string $listId, string $newName): ContactListDTO|BaseErrorDTO
    {
        return $this->contactList->update($listId, $newName);
    }

    /**
     * Remove specific contacts from a list
     *
     * This removes the association between contacts and the list,
     * but does not delete the contacts themselves.
     *
     * @param string $listId The unique ID of the list
     * @param array<string> $contactIds Array of contact IDs to remove from the list
     */
    public function removeContacts(string $listId, array $contactIds): ContactListDTO|BaseErrorDTO
    {
        return $this->contactList->removeContacts($listId, $contactIds);
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
    } elseif ($result === null) {
        echo "✅ SUCCESS (no response body)\n";
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

SendGrid Contact List Management Examples
=========================================

Usage: php ContactListExample.php <command> [options]

Available Commands:
-------------------
  list                              List all contact lists
  create <name>                     Create a new contact list
  get <list_id>                     Get a list by ID
  get-with-sample <list_id>         Get a list by ID with contact sample
  count <list_id>                   Get contact count in a list
  rename <list_id> <new_name>       Rename a contact list
  delete <list_id>                  Delete a list (keep contacts)
  delete-with-contacts <list_id>    Delete a list AND its contacts
  remove-contacts <list_id> <ids>   Remove contacts from a list
  help                              Show this help message

Environment:
------------
  Set SENDGRID_API_KEY environment variable or edit this file.

Examples:
---------
  php ContactListExample.php list
  php ContactListExample.php create "Newsletter Subscribers"
  php ContactListExample.php get abc123-def456-ghi789
  php ContactListExample.php count abc123-def456-ghi789
  php ContactListExample.php rename abc123 "New List Name"
  php ContactListExample.php remove-contacts abc123 contact1,contact2,contact3

Notes:
------
  - List names must be unique within your account
  - Deleting a list with 'delete' preserves the contacts
  - Use 'delete-with-contacts' to remove both the list and contacts
  - Contact IDs for 'remove-contacts' should be comma-separated

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
        echo "   Example: SENDGRID_API_KEY='your-key' php ContactListExample.php list\n\n";
        return 1;
    }

    $service = new ContactListService($apiKey);
    $command = $argv[1] ?? 'help';

    switch ($command) {
        case 'list':
        case 'all':
            printResult($service->getAll(), 'Get All Contact Lists');
            break;

        case 'create':
            $name = $argv[2] ?? null;
            if (!$name) {
                echo "Error: List name required.\n";
                echo "Usage: php ContactListExample.php create <name>\n";
                echo "Example: php ContactListExample.php create \"My Newsletter\"\n";
                return 1;
            }
            printResult($service->create($name), "Create List: {$name}");
            break;

        case 'get':
            $listId = $argv[2] ?? null;
            if (!$listId) {
                echo "Error: List ID required.\n";
                echo "Usage: php ContactListExample.php get <list_id>\n";
                return 1;
            }
            printResult($service->getById($listId), "Get List: {$listId}");
            break;

        case 'get-with-sample':
            $listId = $argv[2] ?? null;
            if (!$listId) {
                echo "Error: List ID required.\n";
                echo "Usage: php ContactListExample.php get-with-sample <list_id>\n";
                return 1;
            }
            printResult(
                $service->getById($listId, includeContactSample: true),
                "Get List with Sample: {$listId}"
            );
            break;

        case 'count':
            $listId = $argv[2] ?? null;
            if (!$listId) {
                echo "Error: List ID required.\n";
                echo "Usage: php ContactListExample.php count <list_id>\n";
                return 1;
            }
            printResult($service->getContactsCount($listId), "Contact Count for List: {$listId}");
            break;

        case 'rename':
        case 'update':
            $listId = $argv[2] ?? null;
            $newName = $argv[3] ?? null;
            if (!$listId || !$newName) {
                echo "Error: List ID and new name required.\n";
                echo "Usage: php ContactListExample.php rename <list_id> <new_name>\n";
                echo "Example: php ContactListExample.php rename abc123 \"Updated Name\"\n";
                return 1;
            }
            printResult($service->update($listId, $newName), "Rename List: {$listId} -> {$newName}");
            break;

        case 'delete':
            $listId = $argv[2] ?? null;
            if (!$listId) {
                echo "Error: List ID required.\n";
                echo "Usage: php ContactListExample.php delete <list_id>\n";
                return 1;
            }
            echo "Deleting list {$listId} (contacts will be preserved)...\n";
            $service->delete($listId, deleteContacts: false);
            echo "✅ List deleted successfully.\n";
            break;

        case 'delete-with-contacts':
            $listId = $argv[2] ?? null;
            if (!$listId) {
                echo "Error: List ID required.\n";
                echo "Usage: php ContactListExample.php delete-with-contacts <list_id>\n";
                return 1;
            }
            echo "⚠️  WARNING: This will delete the list AND all contacts in it!\n";
            echo "List ID: {$listId}\n";
            echo "Type 'yes' to confirm: ";
            $confirmation = trim(fgets(STDIN));
            if ($confirmation !== 'yes') {
                echo "Aborted.\n";
                return 0;
            }
            $service->delete($listId, deleteContacts: true);
            echo "✅ List and contacts deleted successfully.\n";
            break;

        case 'remove-contacts':
            $listId = $argv[2] ?? null;
            $contactIds = $argv[3] ?? null;
            if (!$listId || !$contactIds) {
                echo "Error: List ID and contact IDs required.\n";
                echo "Usage: php ContactListExample.php remove-contacts <list_id> <contact_id1,contact_id2>\n";
                return 1;
            }
            $contactIdArray = array_map('trim', explode(',', $contactIds));
            $count = count($contactIdArray);
            printResult(
                $service->removeContacts($listId, $contactIdArray),
                "Remove {$count} Contact(s) from List: {$listId}"
            );
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
 * Example: Create multiple lists for a campaign workflow
 * 
 * This demonstrates how you might set up lists for a typical
 * marketing campaign with different audience segments.
 */
function exampleCreateCampaignLists(ContactListService $service): void
{
    $listNames = [
        'Campaign 2024 - All Subscribers',
        'Campaign 2024 - Engaged Users',
        'Campaign 2024 - Inactive Users',
        'Campaign 2024 - VIP Customers',
    ];

    $createdLists = [];

    foreach ($listNames as $name) {
        $result = $service->create($name);

        if ($result instanceof BaseErrorDTO) {
            echo "Failed to create list '{$name}': ";
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
            continue;
        }

        $createdLists[] = $result;
        echo "✅ Created list: {$name}\n";
    }

    echo "\nCreated " . count($createdLists) . " lists successfully.\n";
}

/**
 * Example: Get summary of all lists with contact counts
 * 
 * Useful for auditing your lists or building a dashboard.
 */
function exampleListSummary(ContactListService $service): void
{
    $lists = $service->getAll();

    if ($lists instanceof BaseErrorDTO) {
        echo "Error fetching lists: " . json_encode($lists) . "\n";
        return;
    }

    echo "\n" . str_repeat('=', 70) . "\n";
    echo sprintf("%-40s %-15s %s\n", "LIST NAME", "ID", "CONTACTS");
    echo str_repeat('-', 70) . "\n";

    foreach ($lists->result as $list) {
        $countResult = $service->getContactsCount($list->id);

        $count = $countResult instanceof ContactListCountDTO
            ? $countResult->contact_count
            : 'Error';

        echo sprintf(
            "%-40s %-15s %s\n",
            substr($list->name, 0, 38),
            substr($list->id, 0, 13) . '...',
            $count
        );
    }

    echo str_repeat('=', 70) . "\n";
}

/**
 * Example: Clean up empty lists
 * 
 * Finds and optionally deletes lists with zero contacts.
 */
function exampleCleanupEmptyLists(ContactListService $service, bool $dryRun = true): void
{
    $lists = $service->getAll();

    if ($lists instanceof BaseErrorDTO) {
        echo "Error fetching lists: " . json_encode($lists) . "\n";
        return;
    }

    $emptyLists = [];

    foreach ($lists->result as $list) {
        $countResult = $service->getContactsCount($list->id);

        if ($countResult instanceof ContactListCountDTO && $countResult->contact_count === 0) {
            $emptyLists[] = $list;
        }
    }

    if (empty($emptyLists)) {
        echo "No empty lists found.\n";
        return;
    }

    echo "Found " . count($emptyLists) . " empty list(s):\n";

    foreach ($emptyLists as $list) {
        echo "  - {$list->name} ({$list->id})\n";

        if (!$dryRun) {
            $service->delete($list->id);
            echo "    → Deleted\n";
        }
    }

    if ($dryRun) {
        echo "\nThis was a dry run. Set \$dryRun = false to actually delete.\n";
    }
}

/**
 * Example: Bulk remove contacts from multiple lists
 */
function exampleBulkRemoveContacts(
    ContactListService $service,
    array $listIds,
    array $contactIds
): void {
    foreach ($listIds as $listId) {
        $result = $service->removeContacts($listId, $contactIds);

        if ($result instanceof BaseErrorDTO) {
            echo "❌ Failed to remove contacts from list {$listId}\n";
            continue;
        }

        echo "✅ Removed " . count($contactIds) . " contact(s) from list: {$listId}\n";
    }
}

// Run CLI if executed directly
if (php_sapi_name() === 'cli' && isset($argv)) {
    exit(main($argv));
}