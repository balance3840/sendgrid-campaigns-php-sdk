<?php

/**
 * SendGrid Custom Field Management Examples
 * 
 * This file demonstrates how to use the CustomField entity from the SendgridCampaign library.
 * Custom fields allow you to store additional data on contacts beyond the standard fields
 * (email, first_name, last_name, etc.), enabling personalized campaigns and segmentation.
 * 
 * Run from command line: php CustomFieldExample.php [command] [options]
 * 
 * @package SendgridCampaign
 * @example php CustomFieldExample.php help
 */

declare(strict_types=1);

use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\Entities\CustomField\CustomField;
use SendgridCampaign\Entities\CustomField\DTO\CustomFieldDTO;
use SendgridCampaign\Entities\CustomField\DTO\CustomFieldListDTO;
use SendgridCampaign\Entities\CustomField\Enums\CustomFieldType;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Custom Field Management Service
 * 
 * Provides a clean interface for managing SendGrid custom fields.
 * Custom fields extend contact profiles with additional data points
 * that can be used for personalization and segmentation.
 * 
 * Available field types:
 * - TEXT: Free-form text (max 1000 characters)
 * - NUMBER: Numeric values (integers or decimals)
 * - DATE: Date values (YYYY-MM-DD format)
 */
final class CustomFieldService
{
    private CustomField $customField;

    public function __construct(
        private readonly string $apiKey
    ) {
        $this->customField = new CustomField(apiKey: $this->apiKey);
    }

    /**
     * Retrieve all custom fields defined in your account
     * 
     * Returns both reserved fields (SendGrid's standard fields) and
     * custom fields you've created.
     */
    public function getAll(): CustomFieldListDTO|BaseErrorDTO
    {
        return $this->customField->getAll();
    }

    /**
     * Create a new custom field
     *
     * @param string $name The field name (alphanumeric and underscores only, max 100 chars)
     * @param CustomFieldType $fieldType The data type for this field
     * 
     * @note Field names cannot be changed after creation, only the display name can be updated.
     * @note Once created, field types cannot be changed.
     */
    public function create(string $name, CustomFieldType $fieldType): void
    {
        $this->customField->create(
            name: $name,
            fieldType: $fieldType
        );
    }

    /**
     * Update an existing custom field
     *
     * @param string $id The custom field ID (e.g., "e1_T" format)
     * @param string $name The new display name for the field
     * @param CustomFieldType|null $fieldType New field type (usually null to keep existing)
     * 
     * @note Changing field type may cause data loss if existing values are incompatible.
     */
    public function update(string $id, string $name, ?CustomFieldType $fieldType = null): CustomFieldDTO|BaseErrorDTO
    {
        return $this->customField->update(
            id: $id,
            name: $name,
            fieldType: $fieldType
        );
    }

    /**
     * Delete a custom field
     *
     * @param string $id The custom field ID to delete
     * 
     * @warning This permanently removes the field and all associated data from all contacts.
     *          This action cannot be undone.
     */
    public function delete(string $id): void
    {
        $this->customField->delete($id);
    }

    /**
     * Get the CustomFieldType enum value from a string
     *
     * @param string $type The type string (text, number, date)
     * @return CustomFieldType|null Returns null if invalid type
     */
    public static function parseFieldType(string $type): ?CustomFieldType
    {
        return match (strtolower(trim($type))) {
            'text', 'string' => CustomFieldType::TEXT,
            'number', 'numeric', 'int', 'integer', 'float' => CustomFieldType::NUMBER,
            'date', 'datetime' => CustomFieldType::DATE,
            default => null,
        };
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
 * Display a formatted table of custom fields
 */
function printFieldsTable(CustomFieldListDTO $fields): void
{
    echo "\n" . str_repeat('=', 75) . "\n";
    echo sprintf("%-20s %-15s %-15s %s\n", "NAME", "ID", "TYPE", "READ-ONLY");
    echo str_repeat('-', 75) . "\n";

    // Show reserved fields first
    if (!empty($fields->reserved_fields)) {
        echo "── Reserved Fields (SendGrid Standard) ──\n";
        foreach ($fields->reserved_fields as $field) {
            echo sprintf(
                "%-20s %-15s %-15s %s\n",
                substr($field->name, 0, 18),
                $field->id ?? 'N/A',
                $field->fieldType ?? 'N/A'
            );
        }
        echo "\n";
    }

    // Then custom fields
    if (!empty($fields->custom_fields)) {
        echo "── Custom Fields ──\n";
        foreach ($fields->custom_fields as $field) {
            echo sprintf(
                "%-20s %-15s %-15s %s\n",
                substr($field->name, 0, 18),
                $field->id,
                $field->fieldType ?? 'N/A'
            );
        }
    } else {
        echo "No custom fields defined yet.\n";
    }

    echo str_repeat('=', 75) . "\n";
}

/**
 * Display available commands
 */
function showHelp(): void
{
    echo <<<HELP

SendGrid Custom Field Management Examples
=========================================

Usage: php CustomFieldExample.php <command> [options]

Available Commands:
-------------------
  list                          List all custom fields (formatted table)
  list-json                     List all custom fields (JSON output)
  create <name> <type>          Create a new custom field
  update <id> <new_name>        Update a custom field's name
  delete <id>                   Delete a custom field
  types                         Show available field types
  help                          Show this help message

Field Types:
------------
  text    - Free-form text (max 1000 characters)
  number  - Numeric values (integers or decimals)
  date    - Date values (YYYY-MM-DD format)

Environment:
------------
  Set SENDGRID_API_KEY environment variable or edit this file.

Examples:
---------
  php CustomFieldExample.php list
  php CustomFieldExample.php create preferred_language text
  php CustomFieldExample.php create loyalty_points number
  php CustomFieldExample.php create signup_date date
  php CustomFieldExample.php update e1_T "Preferred Language"
  php CustomFieldExample.php delete e1_T

Field Naming Rules:
-------------------
  - Use alphanumeric characters and underscores only
  - Maximum 100 characters
  - Names are case-insensitive
  - Cannot use reserved field names (email, first_name, etc.)

Important Notes:
----------------
  - Field types CANNOT be changed after creation
  - Deleting a field removes ALL data for that field from ALL contacts
  - Custom field IDs have format like "e1_T", "e2_N", "e3_D"
    (suffix indicates type: _T=text, _N=number, _D=date)

HELP;
}

/**
 * Show available field types with descriptions
 */
function showFieldTypes(): void
{
    echo <<<TYPES

Available Custom Field Types
============================

┌──────────┬─────────────────────────────────────────────────────────────┐
│ Type     │ Description                                                 │
├──────────┼─────────────────────────────────────────────────────────────┤
│ text     │ Free-form text, up to 1000 characters.                      │
│          │ Use for: names, preferences, notes, categories              │
│          │ Example values: "English", "Premium", "Referred by John"    │
├──────────┼─────────────────────────────────────────────────────────────┤
│ number   │ Numeric values (integers or decimals).                      │
│          │ Use for: scores, counts, amounts, IDs                       │
│          │ Example values: 100, 3.14, -50, 0                           │
├──────────┼─────────────────────────────────────────────────────────────┤
│ date     │ Date values in YYYY-MM-DD format.                           │
│          │ Use for: birthdays, signup dates, renewal dates             │
│          │ Example values: "2024-01-15", "1990-06-30"                  │
└──────────┴─────────────────────────────────────────────────────────────┘

Usage in Contact Import/Update:
-------------------------------
When setting custom field values on contacts, use the field ID:

  ContactDTO::fromArray([
      'email' => 'user@example.com',
      'e1_T' => 'English',        // text field
      'e2_N' => 500,              // number field  
      'e3_D' => '2024-01-15',     // date field
  ]);

TYPES;
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
        echo "   Example: SENDGRID_API_KEY='your-key' php CustomFieldExample.php list\n\n";
        return 1;
    }

    $service = new CustomFieldService($apiKey);
    $command = $argv[1] ?? 'help';

    switch ($command) {
        case 'list':
        case 'all':
            $result = $service->getAll();
            if ($result instanceof BaseErrorDTO) {
                printResult($result, 'Get All Custom Fields');
            } else {
                printFieldsTable($result);
            }
            break;

        case 'list-json':
            printResult($service->getAll(), 'Get All Custom Fields');
            break;

        case 'create':
            $name = $argv[2] ?? null;
            $typeStr = $argv[3] ?? null;

            if (!$name || !$typeStr) {
                echo "Error: Name and type required.\n";
                echo "Usage: php CustomFieldExample.php create <name> <type>\n";
                echo "Types: text, number, date\n";
                echo "Example: php CustomFieldExample.php create loyalty_points number\n";
                return 1;
            }

            $fieldType = CustomFieldService::parseFieldType($typeStr);
            if ($fieldType === null) {
                echo "Error: Invalid field type '{$typeStr}'.\n";
                echo "Valid types: text, number, date\n";
                return 1;
            }

            echo "Creating custom field '{$name}' of type '{$typeStr}'...\n";
            $service->create($name, $fieldType);
            echo "✅ Custom field created successfully.\n";
            echo "\nTip: Run 'php CustomFieldExample.php list' to see the new field and its ID.\n";
            break;

        case 'update':
        case 'rename':
            $id = $argv[2] ?? null;
            $newName = $argv[3] ?? null;

            if (!$id || !$newName) {
                echo "Error: Field ID and new name required.\n";
                echo "Usage: php CustomFieldExample.php update <id> <new_name>\n";
                echo "Example: php CustomFieldExample.php update e1_T \"Preferred Language\"\n";
                return 1;
            }

            printResult(
                $service->update($id, $newName),
                "Update Custom Field: {$id} -> {$newName}"
            );
            break;

        case 'delete':
            $id = $argv[2] ?? null;

            if (!$id) {
                echo "Error: Field ID required.\n";
                echo "Usage: php CustomFieldExample.php delete <id>\n";
                return 1;
            }

            echo "⚠️  WARNING: This will permanently delete the custom field and ALL its data!\n";
            echo "Field ID: {$id}\n";
            echo "This action cannot be undone.\n";
            echo "Type 'yes' to confirm: ";
            $confirmation = trim(fgets(STDIN));

            if ($confirmation !== 'yes') {
                echo "Aborted.\n";
                return 0;
            }

            $service->delete($id);
            echo "✅ Custom field deleted successfully.\n";
            break;

        case 'types':
            showFieldTypes();
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
 * Example: Set up custom fields for an e-commerce application
 * 
 * Creates a standard set of fields useful for online stores.
 */
function exampleEcommerceFields(CustomFieldService $service): void
{
    $fields = [
        ['customer_tier', CustomFieldType::TEXT],        // Gold, Silver, Bronze
        ['lifetime_value', CustomFieldType::NUMBER],     // Total spend
        ['last_purchase_date', CustomFieldType::DATE],   // Most recent order
        ['favorite_category', CustomFieldType::TEXT],    // Product preference
        ['loyalty_points', CustomFieldType::NUMBER],     // Rewards balance
        ['referral_code', CustomFieldType::TEXT],        // Their unique code
    ];

    echo "Creating e-commerce custom fields...\n\n";

    foreach ($fields as [$name, $type]) {
        try {
            $service->create($name, $type);
            echo "✅ Created: {$name} ({$type->value})\n";
        } catch (\Exception $e) {
            echo "❌ Failed to create {$name}: {$e->getMessage()}\n";
        }
    }

    echo "\nDone! Run 'list' command to see all fields with their IDs.\n";
}

/**
 * Example: Set up custom fields for a SaaS application
 */
function exampleSaasFields(CustomFieldService $service): void
{
    $fields = [
        ['plan_name', CustomFieldType::TEXT],            // Free, Pro, Enterprise
        ['trial_end_date', CustomFieldType::DATE],       // When trial expires
        ['mrr', CustomFieldType::NUMBER],                // Monthly recurring revenue
        ['seats', CustomFieldType::NUMBER],              // Number of user seats
        ['account_manager', CustomFieldType::TEXT],      // Assigned AM name
        ['onboarding_completed', CustomFieldType::DATE], // Onboarding completion
        ['nps_score', CustomFieldType::NUMBER],          // Net Promoter Score
    ];

    echo "Creating SaaS custom fields...\n\n";

    foreach ($fields as [$name, $type]) {
        try {
            $service->create($name, $type);
            echo "✅ Created: {$name} ({$type->value})\n";
        } catch (\Exception $e) {
            echo "❌ Failed to create {$name}: {$e->getMessage()}\n";
        }
    }
}

/**
 * Example: Audit custom fields usage
 * 
 * Lists all custom fields with tips on their usage in SGQL queries.
 */
function exampleFieldUsageGuide(CustomFieldService $service): void
{
    $result = $service->getAll();

    if ($result instanceof BaseErrorDTO) {
        echo "Error fetching fields: " . json_encode($result) . "\n";
        return;
    }

    echo "\nCustom Field Usage Guide\n";
    echo str_repeat('=', 60) . "\n\n";

    if (empty($result->customFields)) {
        echo "No custom fields defined. Create some first!\n";
        return;
    }

    foreach ($result->custom_fields as $field) {
        echo "Field: {$field->name}\n";
        echo "  ID: {$field->id}\n";
        echo "  Type: {$field->field_type?->value}\n";
        echo "  \n";
        echo "  Example SGQL queries:\n";

        switch ($field->field_type) {
            case 'Text':
                echo "    {$field->id} = \"value\"\n";
                echo "    {$field->id} LIKE \"%partial%\"\n";
                echo "    {$field->id} IS NOT NULL\n";
                break;
            case 'Number':
                echo "    {$field->id} > 100\n";
                echo "    {$field->id} BETWEEN 50 AND 200\n";
                echo "    {$field->id} IS NOT NULL\n";
                break;
            case 'Date':
                echo "    {$field->id} > \"2024-01-01\"\n";
                echo "    {$field->id} BETWEEN \"2024-01-01\" AND \"2024-12-31\"\n";
                echo "    {$field->id} IS NOT NULL\n";
                break;
        }

        echo "\n  In contact import:\n";
        echo "    ContactDTO::fromArray(['{$field->id}' => \$value])\n";
        echo "\n" . str_repeat('-', 60) . "\n\n";
    }
}

/**
 * Example: Export field definitions for documentation
 */
function exampleExportFieldDefinitions(CustomFieldService $service): array
{
    $result = $service->getAll();

    if ($result instanceof BaseErrorDTO) {
        return ['error' => 'Failed to fetch fields'];
    }

    $export = [
        'exported_at' => date('Y-m-d H:i:s'),
        'reserved_fields' => [],
        'custom_fields' => [],
    ];

    foreach ($result->reserved_fields ?? [] as $field) {
        $export['reserved_fields'][] = [
            'name' => $field->name,
            'id' => $field->id,
            'type' => $field->field_type
        ];
    }

    foreach ($result->custom_fields ?? [] as $field) {
        $export['custom_fields'][] = [
            'name' => $field->name,
            'id' => $field->id,
            'type' => $field->field_type,
        ];
    }

    return $export;
}

// Run CLI if executed directly
if (php_sapi_name() === 'cli' && isset($argv)) {
    exit(main($argv));
}