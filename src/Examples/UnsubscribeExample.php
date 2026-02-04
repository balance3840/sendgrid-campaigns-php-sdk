<?php

/**
 * SendGrid Unsubscribe Group Management Examples
 * 
 * This file demonstrates how to use the UnsubscribeGroup entity from the SendgridCampaign library.
 * Unsubscribe groups (also called suppression groups) allow recipients to opt out of specific
 * types of emails while still receiving others. This is essential for compliance with
 * email regulations like CAN-SPAM, GDPR, and CASL.
 * 
 * Run from command line: php UnsubscribeExample.php [command] [options]
 * 
 * @package SendgridCampaign
 * @example php UnsubscribeExample.php help
 */

declare(strict_types=1);

use SendgridCampaign\Entities\UnsubscripeGroup\UnsubscribeGroup;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Unsubscribe Group Management Service
 * 
 * Provides a clean interface for managing SendGrid unsubscribe/suppression groups.
 * These groups allow contacts to manage their email preferences granularly.
 * 
 * Why Use Unsubscribe Groups:
 * - Legal compliance (CAN-SPAM, GDPR, CASL)
 * - Better user experience (opt out of promotions but keep receipts)
 * - Reduced spam complaints
 * - Higher engagement from interested subscribers
 * 
 * Common Group Examples:
 * - Marketing/Promotional emails
 * - Product updates/announcements
 * - Newsletter/Blog updates
 * - Transactional notifications
 * - Event invitations
 */
final class UnsubscribeGroupService
{
    private UnsubscribeGroup $unsubscribeGroup;

    public function __construct(
        private readonly string $apiKey
    ) {
        $this->unsubscribeGroup = new UnsubscribeGroup(apiKey: $this->apiKey);
    }

    /**
     * Retrieve all unsubscribe groups
     *
     * @return array List of unsubscribe groups
     */
    public function getAll(): array
    {
        return $this->unsubscribeGroup->getAll();
    }

    /**
     * Get a specific unsubscribe group by ID
     *
     * @param int $groupId The numeric group ID
     */
    public function getById(int $groupId): mixed
    {
        return $this->unsubscribeGroup->getById($groupId);
    }

    /**
     * Create a new unsubscribe group
     *
     * @param string $name Display name shown to recipients in preference center
     * @param string $description Explains what emails this group controls
     * @param bool|null $isDefault If true, new contacts are automatically added
     * 
     * @note The name and description are visible to recipients, so make them clear and user-friendly.
     */
    public function create(string $name, string $description, ?bool $isDefault = null): mixed
    {
        return $this->unsubscribeGroup->create(
            name: $name,
            description: $description,
            isDefault: $isDefault
        );
    }

    /**
     * Update an existing unsubscribe group
     *
     * @param int $groupId The group ID to update
     * @param string|null $name New display name (null to keep current)
     * @param string|null $description New description (null to keep current)
     * @param bool|null $isDefault New default status (null to keep current)
     */
    public function update(
        int $groupId,
        ?string $name = null,
        ?string $description = null,
        ?bool $isDefault = null
    ): mixed {
        return $this->unsubscribeGroup->update(
            groupId: $groupId,
            name: $name,
            description: $description,
            isDefault: $isDefault
        );
    }

    /**
     * Delete an unsubscribe group
     *
     * @param int $groupId The group ID to delete
     * 
     * @warning Deleting a group removes all suppression data for that group.
     *          Contacts who unsubscribed from this group will start receiving
     *          emails again if you create a new group for the same purpose.
     */
    public function delete(int $groupId): void
    {
        $this->unsubscribeGroup->delete(groupId: $groupId);
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

    if ($result === null) {
        echo "✅ SUCCESS (no response body)\n";
    } else {
        echo "✅ SUCCESS:\n";
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    echo "\n";
}

/**
 * Display a formatted table of unsubscribe groups
 */
function printGroupsTable(array $groups): void
{
    echo "\n" . str_repeat('=', 85) . "\n";
    echo "Unsubscribe Groups\n";
    echo str_repeat('=', 85) . "\n";
    echo sprintf(
        "%-10s  %-25s  %-35s  %s\n",
        "ID",
        "NAME",
        "DESCRIPTION",
        "DEFAULT"
    );
    echo str_repeat('-', 85) . "\n";

    if (empty($groups)) {
        echo "No unsubscribe groups found.\n";
    } else {
        foreach ($groups as $group) {
            $isDefault = ($group['is_default'] ?? false) ? '✓ Yes' : 'No';
            $description = $group['description'] ?? '';

            echo sprintf(
                "%-10s  %-25s  %-35s  %s\n",
                $group['id'] ?? 'N/A',
                substr($group['name'] ?? 'Unnamed', 0, 23),
                substr($description, 0, 33),
                $isDefault
            );
        }
    }

    echo str_repeat('=', 85) . "\n";
    echo "Total: " . count($groups) . " group(s)\n";
}

/**
 * Display detailed information about a single group
 */
function printGroupDetails(mixed $group): void
{
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "Unsubscribe Group Details\n";
    echo str_repeat('=', 60) . "\n";

    if (is_array($group)) {
        echo "ID:          " . ($group['id'] ?? 'N/A') . "\n";
        echo "Name:        " . ($group['name'] ?? 'N/A') . "\n";
        echo "Description: " . ($group['description'] ?? 'N/A') . "\n";
        echo "Is Default:  " . (($group['is_default'] ?? false) ? 'Yes' : 'No') . "\n";

        if (isset($group['unsubscribes'])) {
            echo "Unsubscribes: " . number_format($group['unsubscribes']) . "\n";
        }
    } else {
        echo json_encode($group, JSON_PRETTY_PRINT);
    }

    echo str_repeat('=', 60) . "\n";
}

/**
 * Display available commands
 */
function showHelp(): void
{
    echo <<<HELP

SendGrid Unsubscribe Group Management Examples
==============================================

Usage: php UnsubscribeExample.php <command> [options]

Available Commands:
-------------------
  list                              List all unsubscribe groups
  list-json                         List all groups (JSON output)
  get <group_id>                    Get a group by ID
  create <n> <description>       Create a new group
  create-default <n> <desc>      Create a new default group
  rename <group_id> <new_name>      Rename a group
  update <group_id> [options]       Update a group (see below)
  delete <group_id>                 Delete a group
  help                              Show this help message

Update Options:
---------------
  --name="New Name"          Update the group name
  --description="New desc"   Update the description
  --default=true|false       Set as default group

Environment:
------------
  Set SENDGRID_API_KEY environment variable or edit this file.

Examples:
---------
  php UnsubscribeExample.php list
  php UnsubscribeExample.php get 15737
  php UnsubscribeExample.php create "Newsletter" "Weekly company newsletter and updates"
  php UnsubscribeExample.php create-default "Marketing" "Promotional emails and offers"
  php UnsubscribeExample.php rename 15737 "Product Updates"
  php UnsubscribeExample.php delete 29762

What Are Unsubscribe Groups?
----------------------------
  Unsubscribe groups (suppression groups) let recipients opt out of specific
  email types while continuing to receive others. For example, a user might
  unsubscribe from marketing emails but still want to receive receipts.

Best Practices:
---------------
  • Create separate groups for different email types
  • Use clear, user-friendly names (recipients see these)
  • Write helpful descriptions explaining what emails the group controls
  • Consider these common groups:
    - Marketing/Promotional
    - Product Updates
    - Newsletter
    - Transactional (usually not suppressible)
    - Events & Webinars
  • Always assign a suppression group to marketing campaigns
  • Avoid deleting groups that have active suppressions

Legal Compliance:
-----------------
  Unsubscribe groups help you comply with:
  • CAN-SPAM Act (US)
  • GDPR (EU)
  • CASL (Canada)
  • PECR (UK)

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
        echo "   Example: SENDGRID_API_KEY='your-key' php UnsubscribeExample.php list\n\n";
        return 1;
    }

    $service = new UnsubscribeGroupService($apiKey);
    $command = $argv[1] ?? 'help';

    switch ($command) {
        case 'list':
        case 'all':
            $result = $service->getAll();
            printGroupsTable($result);
            break;

        case 'list-json':
            printResult($service->getAll(), 'Get All Unsubscribe Groups');
            break;

        case 'get':
            $groupId = $argv[2] ?? null;
            if (!$groupId || !is_numeric($groupId)) {
                echo "Error: Numeric group ID required.\n";
                echo "Usage: php UnsubscribeExample.php get <group_id>\n";
                return 1;
            }
            $result = $service->getById((int) $groupId);
            printGroupDetails($result);
            break;

        case 'create':
            $name = $argv[2] ?? null;
            $description = $argv[3] ?? null;

            if (!$name || !$description) {
                echo "Error: Name and description required.\n";
                echo "Usage: php UnsubscribeExample.php create <n> <description>\n";
                echo "Example: php UnsubscribeExample.php create \"Newsletter\" \"Weekly updates\"\n";
                return 1;
            }

            $result = $service->create($name, $description, false);
            printResult($result, "Create Group: {$name}");
            break;

        case 'create-default':
            $name = $argv[2] ?? null;
            $description = $argv[3] ?? null;

            if (!$name || !$description) {
                echo "Error: Name and description required.\n";
                echo "Usage: php UnsubscribeExample.php create-default <n> <description>\n";
                return 1;
            }

            $result = $service->create($name, $description, true);
            printResult($result, "Create Default Group: {$name}");
            break;

        case 'rename':
            $groupId = $argv[2] ?? null;
            $newName = $argv[3] ?? null;

            if (!$groupId || !is_numeric($groupId) || !$newName) {
                echo "Error: Group ID and new name required.\n";
                echo "Usage: php UnsubscribeExample.php rename <group_id> <new_name>\n";
                return 1;
            }

            $result = $service->update((int) $groupId, name: $newName);
            printResult($result, "Rename Group: {$groupId} -> {$newName}");
            break;

        case 'update':
            $groupId = $argv[2] ?? null;

            if (!$groupId || !is_numeric($groupId)) {
                echo "Error: Numeric group ID required.\n";
                echo "Usage: php UnsubscribeExample.php update <group_id> [--name=...] [--description=...] [--default=true|false]\n";
                return 1;
            }

            // Parse options
            $name = null;
            $description = null;
            $isDefault = null;

            for ($i = 3; $i < count($argv); $i++) {
                $arg = $argv[$i];
                if (str_starts_with($arg, '--name=')) {
                    $name = substr($arg, 7);
                } elseif (str_starts_with($arg, '--description=')) {
                    $description = substr($arg, 14);
                } elseif (str_starts_with($arg, '--default=')) {
                    $isDefault = strtolower(substr($arg, 10)) === 'true';
                }
            }

            if ($name === null && $description === null && $isDefault === null) {
                echo "Error: At least one update option required.\n";
                echo "Options: --name=\"...\" --description=\"...\" --default=true|false\n";
                return 1;
            }

            $result = $service->update((int) $groupId, $name, $description, $isDefault);
            printResult($result, "Update Group: {$groupId}");
            break;

        case 'delete':
            $groupId = $argv[2] ?? null;

            if (!$groupId || !is_numeric($groupId)) {
                echo "Error: Numeric group ID required.\n";
                echo "Usage: php UnsubscribeExample.php delete <group_id>\n";
                return 1;
            }

            echo "⚠️  WARNING: This will permanently delete the unsubscribe group!\n";
            echo "Group ID: {$groupId}\n";
            echo "\n";
            echo "IMPORTANT: Deleting this group will also delete all suppression data.\n";
            echo "Contacts who unsubscribed from this group may start receiving emails again.\n";
            echo "\n";
            echo "Type 'yes' to confirm: ";
            $confirmation = trim(fgets(STDIN));

            if ($confirmation !== 'yes') {
                echo "Aborted.\n";
                return 0;
            }

            $service->delete((int) $groupId);
            echo "✅ Unsubscribe group deleted successfully.\n";
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
 * Example: Set up standard unsubscribe groups for a typical business
 */
function exampleSetupStandardGroups(UnsubscribeGroupService $service): void
{
    $groups = [
        [
            'name' => 'Marketing & Promotions',
            'description' => 'Special offers, discounts, and promotional emails',
            'isDefault' => false,
        ],
        [
            'name' => 'Product Updates',
            'description' => 'New features, improvements, and product announcements',
            'isDefault' => false,
        ],
        [
            'name' => 'Newsletter',
            'description' => 'Our weekly newsletter with industry insights and tips',
            'isDefault' => false,
        ],
        [
            'name' => 'Events & Webinars',
            'description' => 'Invitations to events, webinars, and workshops',
            'isDefault' => false,
        ],
        [
            'name' => 'Account Notifications',
            'description' => 'Important updates about your account and service',
            'isDefault' => true,
        ],
    ];

    echo "Setting up standard unsubscribe groups...\n\n";

    foreach ($groups as $group) {
        try {
            $result = $service->create(
                $group['name'],
                $group['description'],
                $group['isDefault']
            );

            $id = $result['id'] ?? 'unknown';
            $default = $group['isDefault'] ? ' (default)' : '';
            echo "✅ Created: {$group['name']}{$default} (ID: {$id})\n";
        } catch (\Exception $e) {
            echo "❌ Failed to create '{$group['name']}': " . $e->getMessage() . "\n";
        }
    }

    echo "\nDone! Run 'list' command to see all groups.\n";
}

/**
 * Example: Audit unsubscribe groups for best practices
 */
function exampleAuditGroups(UnsubscribeGroupService $service): void
{
    $groups = $service->getAll();

    echo "\n" . str_repeat('=', 60) . "\n";
    echo "Unsubscribe Group Audit\n";
    echo str_repeat('=', 60) . "\n\n";

    $issues = [];

    // Check for minimum groups
    if (count($groups) < 2) {
        $issues[] = "⚠️  Only " . count($groups) . " group(s). Consider creating separate groups for different email types.";
    }

    // Check each group
    foreach ($groups as $group) {
        $name = $group['name'] ?? 'Unknown';
        $description = $group['description'] ?? '';

        // Check description length
        if (strlen($description) < 20) {
            $issues[] = "⚠️  '{$name}' has a short description. Recipients benefit from clear explanations.";
        }

        // Check for generic names
        $genericNames = ['default', 'test', 'group', 'emails'];
        foreach ($genericNames as $generic) {
            if (stripos($name, $generic) !== false && strlen($name) < 15) {
                $issues[] = "⚠️  '{$name}' may be too generic. Use descriptive names like 'Marketing Emails'.";
                break;
            }
        }
    }

    // Check for default group
    $hasDefault = false;
    foreach ($groups as $group) {
        if ($group['is_default'] ?? false) {
            $hasDefault = true;
            break;
        }
    }

    if (!$hasDefault && count($groups) > 0) {
        $issues[] = "💡 No default group set. Consider setting one for new signups.";
    }

    // Report
    if (empty($issues)) {
        echo "✅ All groups look good!\n";
    } else {
        echo "Issues found:\n\n";
        foreach ($issues as $issue) {
            echo "  {$issue}\n";
        }
    }

    echo "\n";
}

/**
 * Example: Export groups for documentation
 */
function exampleExportGroups(UnsubscribeGroupService $service): array
{
    $groups = $service->getAll();

    $export = [
        'exported_at' => date('Y-m-d H:i:s'),
        'total_groups' => count($groups),
        'groups' => [],
    ];

    foreach ($groups as $group) {
        $export['groups'][] = [
            'id' => $group['id'] ?? null,
            'name' => $group['name'] ?? null,
            'description' => $group['description'] ?? null,
            'is_default' => $group['is_default'] ?? false,
        ];
    }

    return $export;
}

/**
 * Example: Find group by name
 */
function exampleFindGroupByName(UnsubscribeGroupService $service, string $searchName): ?array
{
    $groups = $service->getAll();

    foreach ($groups as $group) {
        $name = $group['name'] ?? '';
        if (stripos($name, $searchName) !== false) {
            return $group;
        }
    }

    return null;
}

/**
 * Example: Create groups for e-commerce business
 */
function exampleEcommerceGroups(UnsubscribeGroupService $service): void
{
    $groups = [
        [
            'name' => 'Sales & Promotions',
            'description' => 'Flash sales, discount codes, and special offers',
        ],
        [
            'name' => 'New Arrivals',
            'description' => 'Be the first to know about new products',
        ],
        [
            'name' => 'Back in Stock',
            'description' => 'Alerts when items you viewed are available again',
        ],
        [
            'name' => 'Order Updates',
            'description' => 'Shipping confirmations and delivery updates',
        ],
        [
            'name' => 'Review Requests',
            'description' => 'Invitations to review your purchases',
        ],
        [
            'name' => 'Loyalty Program',
            'description' => 'Points balance, rewards, and member exclusives',
        ],
    ];

    echo "Creating e-commerce unsubscribe groups...\n\n";

    foreach ($groups as $group) {
        try {
            $result = $service->create($group['name'], $group['description']);
            echo "✅ Created: {$group['name']}\n";
        } catch (\Exception $e) {
            echo "❌ Failed: {$group['name']}\n";
        }
    }
}

// Run CLI if executed directly
if (php_sapi_name() === 'cli' && isset($argv)) {
    exit(main($argv));
}