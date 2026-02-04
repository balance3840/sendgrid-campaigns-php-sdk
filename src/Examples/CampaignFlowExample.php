<?php

/**
 * SendGrid Campaign Flow Example
 * 
 * This example demonstrates a complete end-to-end workflow for creating
 * and sending a marketing campaign using the SendgridCampaign library:
 * 
 *   1. Create a contact list
 *   2. Create custom fields (if needed)
 *   3. Add contacts to the list
 *   4. Create an email design/template
 *   5. Create a Single Send campaign
 *   6. Schedule and send the campaign
 * 
 * Usage:
 *   1. Copy config.example.php to config.php and fill in your values
 *   2. Run: php CampaignFlowExample.php
 * 
 * @package SendgridCampaign
 */

declare(strict_types=1);

use SendgridCampaign\Clients\SendgridClient;
use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\Entities\Contact\Contact;
use SendgridCampaign\Entities\Contact\DTO\ContactDTO;
use SendgridCampaign\Entities\ContactList\ContactList;
use SendgridCampaign\Entities\ContactList\DTO\ContactListDTO;
use SendgridCampaign\Entities\CustomField\CustomField;
use SendgridCampaign\Entities\CustomField\DTO\CustomFieldDTO;
use SendgridCampaign\Entities\CustomField\Enums\CustomFieldType;
use SendgridCampaign\Entities\Design\Design;
use SendgridCampaign\Entities\Design\DTO\DesignDTO;
use SendgridCampaign\Entities\Design\Enums\EditorType;
use SendgridCampaign\Entities\Job\DTO\JobDTO;
use SendgridCampaign\Entities\SingleSend\DTO\EmailConfigDTO;
use SendgridCampaign\Entities\SingleSend\DTO\SendToDTO;
use SendgridCampaign\Entities\SingleSend\DTO\SingleSendDTO;
use SendgridCampaign\Entities\SingleSend\SingleSend;

require_once __DIR__ . '/../../vendor/autoload.php';

// =============================================================================
// CONFIGURATION
// =============================================================================

/**
 * Load configuration from config.php if it exists, otherwise use defaults.
 * 
 * Setup:
 *   cp config.example.php config.php
 *   # Edit config.php with your values
 */
$config = file_exists(__DIR__ . '/config.php')
    ? require __DIR__ . '/config.php'
    : [
        // SendGrid API credentials
        'api_key' => getenv('SENDGRID_API_KEY') ?: '',
        'on_behalf_of' => getenv('SENDGRID_SUBUSER') ?: null,
        
        // Sender configuration (required - get from SendGrid dashboard)
        'sender_id' => (int) (getenv('SENDGRID_SENDER_ID') ?: 0),
        'suppression_group_id' => (int) (getenv('SENDGRID_SUPPRESSION_GROUP_ID') ?: 0),
        
        // Campaign details
        'campaign' => [
            'list_name' => 'SDK Demo Campaign - ' . date('Y-m-d H:i'),
            'design_name' => 'SDK Demo Design - ' . date('Y-m-d H:i'),
            'single_send_name' => 'SDK Demo Single Send - ' . date('Y-m-d H:i'),
            'subject' => 'Hello from SendGrid SDK!',
        ],
        
        // Contacts to add (customize as needed)
        'contacts' => [
            [
                'email' => 'test1@example.com',
                'first_name' => 'Test',
                'last_name' => 'User One',
                'custom_fields' => ['corporation_id' => 100],
            ],
            [
                'email' => 'test2@example.com',
                'first_name' => 'Test',
                'last_name' => 'User Two',
                'custom_fields' => ['corporation_id' => 100],
            ],
        ],
        
        // Timing configuration
        'contact_sync_wait_seconds' => 180,
        'single_send_wait_seconds' => 30,
        
        // Set to false for a dry run (creates everything but doesn't send)
        'actually_send' => false,
    ];

// =============================================================================
// WORKFLOW RUNNER
// =============================================================================

/**
 * Orchestrates the complete campaign creation workflow
 */
final class CampaignWorkflow
{
    private SendgridClient $client;
    private ContactList $contactList;
    private Contact $contact;
    private CustomField $customField;
    private Design $design;
    private SingleSend $singleSend;
    
    // Track created resources for summary/cleanup
    private ?ContactListDTO $createdList = null;
    private ?CustomFieldDTO $createdCustomField = null;
    private ?JobDTO $contactJob = null;
    private ?DesignDTO $createdDesign = null;
    private ?SingleSendDTO $createdSingleSend = null;

    public function __construct(
        private readonly array $config
    ) {
        $this->validateConfig();
        $this->initializeClient();
    }

    /**
     * Execute the complete workflow
     */
    public function run(): bool
    {
        $this->printHeader('SendGrid Campaign Workflow');
        $this->printConfig();

        try {
            // Step 1: Create contact list
            $this->createdList = $this->createContactList();
            
            // Step 2: Ensure custom field exists
            $this->createdCustomField = $this->ensureCustomFieldExists('corporation_id', CustomFieldType::NUMBER);
            
            // Step 3: Add contacts
            $this->contactJob = $this->addContacts();
            $this->waitForContactSync();
            
            // Step 4: Create design
            $this->createdDesign = $this->createDesign();
            
            // Step 5: Create single send
            $this->createdSingleSend = $this->createSingleSend();
            
            // Step 6: Schedule (if enabled)
            if ($this->config['actually_send']) {
                $this->scheduleSingleSend();
            } else {
                $this->printWarning('Dry run mode - campaign was NOT scheduled');
                $this->printInfo('Set "actually_send" to true in config to send');
            }

            $this->printSummary();
            return true;

        } catch (WorkflowException $e) {
            $this->printError("Workflow failed at step: {$e->getStep()}");
            $this->printError($e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // WORKFLOW STEPS
    // =========================================================================

    private function createContactList(): ContactListDTO
    {
        $this->printStep(1, 'Creating contact list');

        $result = $this->contactList->create(
            name: $this->config['campaign']['list_name']
        );

        $this->assertSuccess($result, 'Create contact list');
        $this->printSuccess("Created list: {$result->name} (ID: {$result->id})");

        return $result;
    }

    private function ensureCustomFieldExists(string $name, CustomFieldType $type): ?CustomFieldDTO
    {
        $this->printStep(2, "Ensuring custom field '{$name}' exists");

        // Check if field already exists
        $existingFields = $this->customField->getAll();
        
        if (!$existingFields instanceof BaseErrorDTO) {
            foreach ($existingFields->result ?? [] as $field) {
                if ($field->name === $name) {
                    $this->printInfo("Custom field '{$name}' already exists (ID: {$field->id})");
                    return $field;
                }
            }
        }

        // Create new field
        $result = $this->customField->create(
            name: $name,
            fieldType: $type
        );

        if ($result instanceof BaseErrorDTO) {
            // Field might already exist (race condition or previous run)
            if ($this->isAlreadyExistsError($result)) {
                $this->printInfo("Custom field '{$name}' already exists");
                return null;
            }
            throw new WorkflowException('Create custom field', $this->formatError($result));
        }

        $this->printSuccess("Created custom field: {$name} (ID: {$result->id})");
        return $result;
    }

    private function addContacts(): JobDTO
    {
        $this->printStep(3, 'Adding contacts to list');

        $contacts = array_map(
            fn(array $data) => new ContactDTO(
                email: $data['email'],
                first_name: $data['first_name'] ?? null,
                last_name: $data['last_name'] ?? null,
                custom_fields: $data['custom_fields'] ?? null
            ),
            $this->config['contacts']
        );

        $this->printInfo('Contacts to add: ' . count($contacts));
        foreach ($contacts as $contact) {
            $this->printInfo("  - {$contact->email}");
        }

        $result = $this->contact->createOrUpdate(
            contacts: $contacts,
            listIds: [$this->createdList->id]
        );

        $this->assertSuccess($result, 'Add contacts');
        $this->printSuccess("Contact job started (Job ID: {$result->job_id})");

        return $result;
    }

    private function waitForContactSync(): void
    {
        $waitTime = $this->config['contact_sync_wait_seconds'];
        
        $this->printInfo("Waiting {$waitTime}s for contacts to sync to SendGrid...");
        $this->printInfo('(SendGrid requires time to process contacts before they can receive emails)');
        
        $this->progressWait($waitTime);
    }

    private function createDesign(): DesignDTO
    {
        $this->printStep(4, 'Creating email design');

        $htmlContent = $this->buildEmailHtml();

        $result = $this->design->create(
            name: $this->config['campaign']['design_name'],
            subject: $this->config['campaign']['subject'],
            htmlContent: $htmlContent,
            editor: EditorType::CODE
        );

        $this->assertSuccess($result, 'Create design');
        $this->printSuccess("Created design: {$result->name} (ID: {$result->id})");

        return $result;
    }

    private function createSingleSend(): SingleSendDTO
    {
        $this->printStep(5, 'Creating Single Send campaign');

        $result = $this->singleSend->create(
            new SingleSendDTO(
                name: $this->config['campaign']['single_send_name'],
                send_to: new SendToDTO(
                    list_ids: [$this->createdList->id]
                ),
                email_config: new EmailConfigDTO(
                    sender_id: $this->config['sender_id'],
                    design_id: $this->createdDesign->id,
                    suppression_group_id: $this->config['suppression_group_id']
                )
            )
        );

        $this->assertSuccess($result, 'Create Single Send');
        $this->printSuccess("Created Single Send: {$result->name} (ID: {$result->id})");

        // Brief wait for Single Send to be ready
        $waitTime = $this->config['single_send_wait_seconds'];
        $this->printInfo("Waiting {$waitTime}s for Single Send to be ready...");
        $this->progressWait($waitTime);

        return $result;
    }

    private function scheduleSingleSend(): void
    {
        $this->printStep(6, 'Scheduling Single Send');

        $result = $this->singleSend->schedule(
            singleSendId: $this->createdSingleSend->id,
            sendAt: 'now'
        );

        $this->assertSuccess($result, 'Schedule Single Send');
        $this->printSuccess('Campaign scheduled for immediate delivery!');
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function validateConfig(): void
    {
        $required = ['api_key', 'sender_id', 'suppression_group_id'];
        $missing = [];

        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                "Missing required configuration: " . implode(', ', $missing) . "\n" .
                "Set these via environment variables or in the config array."
            );
        }
    }

    private function initializeClient(): void
    {
        $this->client = SendgridClient::create(
            apiKey: $this->config['api_key'],
            onBehalfOf: $this->config['on_behalf_of']
        );

        $this->contactList = new ContactList(sendgridClient: $this->client);
        $this->contact = new Contact(sendgridClient: $this->client);
        $this->customField = new CustomField(sendgridClient: $this->client);
        $this->design = new Design(sendgridClient: $this->client);
        $this->singleSend = new SingleSend(sendgridClient: $this->client);
    }

    private function buildEmailHtml(): string
    {
        return <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>{$this->config['campaign']['subject']}</title>
            </head>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
                <h1 style="color: #0066cc;">Hello {{first_name}} {{last_name}}!</h1>
                
                <p>This is a test email sent via the SendGrid Campaign SDK.</p>
                
                <p>Your corporation ID is: <strong>{{corporation_id}}</strong></p>
                
                <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666;">
                    This email was sent as part of an SDK demonstration.
                </p>
            </body>
            </html>
            HTML;
    }

    private function assertSuccess(mixed $result, string $operation): void
    {
        if ($result instanceof BaseErrorDTO) {
            throw new WorkflowException($operation, $this->formatError($result));
        }
    }

    private function formatError(BaseErrorDTO $error): string
    {
        $messages = array_map(
            fn($e) => $e->message ?? 'Unknown error',
            $error->errors ?? []
        );
        return implode('; ', $messages) ?: 'Unknown error occurred';
    }

    private function isAlreadyExistsError(BaseErrorDTO $error): bool
    {
        foreach ($error->errors ?? [] as $e) {
            if (str_contains(strtolower($e->message ?? ''), 'already exists')) {
                return true;
            }
        }
        return false;
    }

    private function progressWait(int $seconds): void
    {
        $barWidth = 40;
        
        for ($i = 0; $i <= $seconds; $i++) {
            $progress = $i / $seconds;
            $filled = (int) ($progress * $barWidth);
            $empty = $barWidth - $filled;
            
            $bar = str_repeat('█', $filled) . str_repeat('░', $empty);
            $percent = (int) ($progress * 100);
            $remaining = $seconds - $i;
            
            echo "\r    [{$bar}] {$percent}% ({$remaining}s remaining)";
            
            if ($i < $seconds) {
                sleep(1);
            }
        }
        echo "\n";
    }

    // =========================================================================
    // OUTPUT FORMATTING
    // =========================================================================

    private function printHeader(string $title): void
    {
        $line = str_repeat('=', 60);
        echo "\n{$line}\n";
        echo "  {$title}\n";
        echo "{$line}\n\n";
    }

    private function printConfig(): void
    {
        $configSource = file_exists(__DIR__ . '/config.php') ? 'config.php' : 'environment/defaults';
        
        echo "Configuration (from {$configSource}):\n";
        echo "  • Subuser: " . ($this->config['on_behalf_of'] ?? 'None') . "\n";
        echo "  • Sender ID: {$this->config['sender_id']}\n";
        echo "  • Suppression Group: {$this->config['suppression_group_id']}\n";
        echo "  • Contacts: " . count($this->config['contacts']) . "\n";
        echo "  • Mode: " . ($this->config['actually_send'] ? '🔴 LIVE SEND' : '🟢 Dry Run') . "\n";
        echo "\n";
    }

    private function printStep(int $step, string $description): void
    {
        echo "\n[Step {$step}/6] {$description}\n";
        echo str_repeat('-', 50) . "\n";
    }

    private function printSuccess(string $message): void
    {
        echo "  ✅ {$message}\n";
    }

    private function printInfo(string $message): void
    {
        echo "  ℹ️  {$message}\n";
    }

    private function printWarning(string $message): void
    {
        echo "  ⚠️  {$message}\n";
    }

    private function printError(string $message): void
    {
        echo "  ❌ {$message}\n";
    }

    private function printSummary(): void
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "  WORKFLOW COMPLETE\n";
        echo str_repeat('=', 60) . "\n\n";

        echo "Created Resources:\n";
        
        if ($this->createdList) {
            echo "  • Contact List: {$this->createdList->id}\n";
        }
        if ($this->createdCustomField) {
            echo "  • Custom Field: {$this->createdCustomField->id}\n";
        }
        if ($this->contactJob) {
            echo "  • Contact Job: {$this->contactJob->job_id}\n";
        }
        if ($this->createdDesign) {
            echo "  • Design: {$this->createdDesign->id}\n";
        }
        if ($this->createdSingleSend) {
            echo "  • Single Send: {$this->createdSingleSend->id}\n";
        }

        echo "\nView your campaign at:\n";
        echo "  https://mc.sendgrid.com/single-sends/{$this->createdSingleSend?->id}/overview\n\n";
    }
}

/**
 * Custom exception for workflow failures
 */
class WorkflowException extends \Exception
{
    public function __construct(
        private readonly string $step,
        string $message
    ) {
        parent::__construct($message);
    }

    public function getStep(): string
    {
        return $this->step;
    }
}

// =============================================================================
// MAIN EXECUTION
// =============================================================================

if (php_sapi_name() === 'cli') {
    try {
        $workflow = new CampaignWorkflow($config);
        $success = $workflow->run();
        exit($success ? 0 : 1);
    } catch (\InvalidArgumentException $e) {
        echo "\n❌ Configuration Error:\n";
        echo "   {$e->getMessage()}\n\n";
        echo "Required environment variables:\n";
        echo "   SENDGRID_API_KEY          Your SendGrid API key\n";
        echo "   SENDGRID_SENDER_ID        Verified sender ID (from SendGrid dashboard)\n";
        echo "   SENDGRID_SUPPRESSION_GROUP_ID  Unsubscribe group ID\n\n";
        echo "Optional:\n";
        echo "   SENDGRID_SUBUSER          Subuser to act on behalf of\n\n";
        exit(1);
    } catch (\Throwable $e) {
        echo "\n❌ Unexpected Error: {$e->getMessage()}\n";
        echo "   File: {$e->getFile()}:{$e->getLine()}\n\n";
        exit(1);
    }
}