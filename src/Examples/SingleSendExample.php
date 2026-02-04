<?php

/**
 * SendGrid Single Send (Campaign) Management Examples
 * 
 * This file demonstrates how to use the SingleSend entity from the SendgridCampaign library.
 * Single Sends are one-time email campaigns sent to a list of contacts.
 * They combine a design template, sender identity, and recipient list.
 * 
 * Run from command line: php SingleSendExample.php [command] [options]
 * 
 * @package SendgridCampaign
 * @example php SingleSendExample.php help
 */

declare(strict_types=1);

use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\DTO\BaseListDto;
use SendgridCampaign\Entities\SingleSend\DTO\EmailConfigDTO;
use SendgridCampaign\Entities\SingleSend\DTO\SendToDTO;
use SendgridCampaign\Entities\SingleSend\DTO\SingleSendDTO;
use SendgridCampaign\Entities\SingleSend\SingleSend;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Single Send Campaign Management Service
 * 
 * Provides a clean interface for managing SendGrid Single Send campaigns.
 * Single Sends are one-time email blasts sent to specified contact lists.
 * 
 * Campaign Lifecycle:
 * 1. Create (draft) → 2. Configure → 3. Schedule/Send → 4. Monitor
 * 
 * Status Types:
 * - draft: Campaign is being configured
 * - scheduled: Campaign is scheduled for future send
 * - triggered: Campaign is currently sending
 * - completed: Campaign has finished sending
 */
final class SingleSendService
{
    private SingleSend $singleSend;

    public function __construct(
        private readonly string $apiKey
    ) {
        $this->singleSend = new SingleSend(apiKey: $this->apiKey);
    }

    /**
     * Retrieve all single sends with pagination
     *
     * @param int $pageSize Number of results per page
     * @return BaseListDto<SingleSendDTO>|BaseErrorDTO
     */
    public function getAll(int $pageSize = 10): BaseListDto|BaseErrorDTO
    {
        return $this->singleSend->getAll($pageSize);
    }

    /**
     * Get a specific single send by ID
     *
     * @param string $singleSendId The unique single send ID (UUID format)
     */
    public function getById(string $singleSendId): SingleSendDTO|BaseErrorDTO
    {
        return $this->singleSend->getById($singleSendId);
    }

    /**
     * Create a new single send campaign
     *
     * @param SingleSendDTO $singleSendDTO The campaign configuration
     * 
     * @note The campaign is created in 'draft' status and must be scheduled to send.
     */
    public function create(SingleSendDTO $singleSendDTO): SingleSendDTO|BaseErrorDTO
    {
        return $this->singleSend->create($singleSendDTO);
    }

    /**
     * Update an existing single send campaign
     *
     * @param string $singleSendId The ID of the campaign to update
     * @param SingleSendDTO $singleSendDTO The updated configuration
     * 
     * @note Only draft campaigns can be updated. Scheduled/sent campaigns cannot be modified.
     */
    public function update(string $singleSendId, SingleSendDTO $singleSendDTO): SingleSendDTO|BaseErrorDTO
    {
        return $this->singleSend->update($singleSendId, $singleSendDTO);
    }

    /**
     * Schedule a single send for delivery
     *
     * @param string $singleSendId The ID of the campaign to schedule
     * @param string $sendAt When to send: 'now' for immediate, or ISO 8601 datetime
     * 
     * @example $service->schedule($id, 'now')
     * @example $service->schedule($id, '2024-12-25T10:00:00Z')
     */
    public function schedule(string $singleSendId, string $sendAt): SingleSendDTO|BaseErrorDTO
    {
        return $this->singleSend->schedule($singleSendId, $sendAt);
    }

    /**
     * Cancel a scheduled single send
     *
     * Returns the campaign to draft status so it can be modified or rescheduled.
     *
     * @param string $singleSendId The ID of the scheduled campaign
     * 
     * @note Cannot cancel campaigns that have already started sending.
     */
    public function cancelSchedule(string $singleSendId): SingleSendDTO|BaseErrorDTO
    {
        return $this->singleSend->deleteSchedule($singleSendId);
    }

    /**
     * Search for single sends by name and filters
     *
     * @param string $name Name to search for (partial match)
     * @param array<string>|null $status Filter by status (draft, scheduled, triggered, completed)
     * @param array<string>|null $categories Filter by categories
     * @param string|null $pageToken Pagination token for next page
     * @return BaseListDto<SingleSendDTO>|BaseErrorDTO
     */
    public function search(
        string $name,
        ?array $status = null,
        ?array $categories = null,
        ?string $pageToken = null
    ): BaseListDto|BaseErrorDTO {
        return $this->singleSend->search(
            name: $name,
            status: $status,
            categories: $categories,
            pageToken: $pageToken
        );
    }

    /**
     * Get all available categories
     *
     * Categories help organize campaigns and can be used for filtering.
     *
     * @return array<string>|BaseErrorDTO
     */
    public function getAllCategories(): array|BaseErrorDTO
    {
        return $this->singleSend->getAllCategories();
    }

    /**
     * Duplicate an existing single send
     *
     * Creates a copy of a campaign, useful for creating similar campaigns
     * or reusing a template.
     *
     * @param string $singleSendId The ID of the campaign to duplicate
     * @param string $newName Name for the new campaign
     */
    public function duplicate(string $singleSendId, string $newName): SingleSendDTO|BaseErrorDTO
    {
        return $this->singleSend->duplicate($singleSendId, $newName);
    }

    /**
     * Delete a single send campaign
     *
     * @param string $singleSendId The ID of the campaign to delete
     * 
     * @warning This permanently removes the campaign and its statistics.
     */
    public function delete(string $singleSendId): void
    {
        $this->singleSend->delete($singleSendId);
    }

    /**
     * Delete multiple single sends at once
     *
     * @param array<string> $singleSendIds Array of campaign IDs to delete
     * 
     * @warning This permanently removes all specified campaigns.
     */
    public function bulkDelete(array $singleSendIds): void
    {
        $this->singleSend->bulkDelete($singleSendIds);
    }

    /**
     * Build a SingleSendDTO for campaign creation
     *
     * Helper method to create a properly configured campaign DTO.
     *
     * @param string $name Campaign name (internal identifier)
     * @param array<string> $listIds Contact list IDs to send to
     * @param string $designId Design/template ID to use
     * @param int $senderId Verified sender ID
     * @param int|null $suppressionGroupId Unsubscribe group ID (recommended)
     * @param array<string>|null $segmentIds Segment IDs to send to (alternative to lists)
     */
    public static function buildCampaign(
        string $name,
        array $listIds,
        string $designId,
        int $senderId,
        ?int $suppressionGroupId = null,
        ?array $segmentIds = null
    ): SingleSendDTO {
        $dto = new SingleSendDTO();
        $dto->name = $name;

        $dto->send_to = new SendToDTO(
            list_ids: $listIds,
            segment_ids: $segmentIds
        );

        $dto->email_config = new EmailConfigDTO(
            design_id: $designId,
            sender_id: $senderId,
            suppression_group_id: $suppressionGroupId
        );

        return $dto;
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
 * Display a formatted table of single sends
 */
function printSingleSendsTable(BaseListDto $singleSends): void
{
    echo "\n" . str_repeat('=', 100) . "\n";
    echo "Single Send Campaigns\n";
    echo str_repeat('=', 100) . "\n";
    echo sprintf(
        "%-36s  %-30s  %-12s  %s\n",
        "ID",
        "NAME",
        "STATUS",
        "SEND AT"
    );
    echo str_repeat('-', 100) . "\n";

    if (empty($singleSends->result)) {
        echo "No single sends found.\n";
    } else {
        foreach ($singleSends->result as $send) {
            $status = $send->status ?? 'unknown';
            $statusIcon = match ($status) {
                'draft' => '📝',
                'scheduled' => '📅',
                'triggered' => '🚀',
                'completed' => '✅',
                default => '❓',
            };

            $sendAt = $send->send_at ?? 'Not scheduled';
            if ($sendAt !== 'Not scheduled') {
                $sendAt = date('Y-m-d H:i', strtotime($sendAt));
            }

            echo sprintf(
                "%-36s  %-30s  %s %-10s  %s\n",
                $send->id ?? 'N/A',
                substr($send->name ?? 'Unnamed', 0, 28),
                $statusIcon,
                $status,
                $sendAt
            );
        }
    }

    echo str_repeat('=', 100) . "\n";
    echo "Total: " . count($singleSends->result ?? []) . " campaign(s)\n";
}

/**
 * Display detailed information about a single send
 */
function printSingleSendDetails(SingleSendDTO $send): void
{
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "Single Send Details\n";
    echo str_repeat('=', 60) . "\n";

    $statusIcon = match ($send->status ?? 'unknown') {
        'draft' => '📝',
        'scheduled' => '📅',
        'triggered' => '🚀',
        'completed' => '✅',
        default => '❓',
    };

    echo "ID:           {$send->id}\n";
    echo "Name:         {$send->name}\n";
    echo "Status:       {$statusIcon} {$send->status?->name}\n";

    if ($send->send_at) {
        echo "Send At:      " . date('Y-m-d H:i:s', strtotime($send->send_at)) . "\n";
    }

    if ($send->send_to) {
        echo "\n── Recipients ──\n";
        if (!empty($send->send_to->list_ids)) {
            echo "List IDs:     " . implode(', ', $send->send_to->list_ids) . "\n";
        }
        if (!empty($send->send_to->segment_ids)) {
            echo "Segment IDs:  " . implode(', ', $send->send_to->segment_ids) . "\n";
        }
        if ($send->send_to->all) {
            echo "Send to:      ALL contacts\n";
        }
    }

    if ($send->email_config) {
        echo "\n── Email Config ──\n";
        if ($send->email_config->design_id) {
            echo "Design ID:    {$send->email_config->design_id}\n";
        }
        if ($send->email_config->sender_id) {
            echo "Sender ID:    {$send->email_config->sender_id}\n";
        }
        if ($send->email_config->suppression_group_id) {
            echo "Suppression:  {$send->email_config->suppression_group_id}\n";
        }
    }

    echo str_repeat('=', 60) . "\n";
}

/**
 * Display available commands
 */
function showHelp(): void
{
    echo <<<HELP

SendGrid Single Send (Campaign) Management Examples
====================================================

Usage: php SingleSendExample.php <command> [options]

Available Commands:
-------------------
  list [page_size]                  List all single sends (default: 10)
  list-json [page_size]             List all single sends (JSON output)
  get <id>                          Get a single send by ID
  search <name> [status]            Search by name and optional status
  categories                        List all available categories
  
  create-demo                       Create a demo campaign (requires IDs)
  duplicate <id> <new_name>         Duplicate a campaign
  rename <id> <new_name>            Rename a campaign
  
  schedule <id> now                 Send immediately
  schedule <id> <datetime>          Schedule for future (ISO 8601)
  cancel <id>                       Cancel a scheduled send
  
  delete <id>                       Delete a single send
  bulk-delete <id1,id2,...>         Delete multiple single sends
  
  help                              Show this help message

Status Values:
--------------
  draft      - Campaign is being configured
  scheduled  - Campaign is scheduled for future send  
  triggered  - Campaign is currently sending
  completed  - Campaign has finished sending

Environment:
------------
  Set SENDGRID_API_KEY environment variable or edit this file.

Examples:
---------
  php SingleSendExample.php list
  php SingleSendExample.php get b55b55b8-066c-11f1-8010-6aae4474d90f
  php SingleSendExample.php search "Newsletter" draft
  php SingleSendExample.php duplicate abc123 "Newsletter v2"
  php SingleSendExample.php schedule abc123 now
  php SingleSendExample.php schedule abc123 "2024-12-25T10:00:00Z"
  php SingleSendExample.php cancel abc123

Creating a Campaign (Code Example):
-----------------------------------
  \$campaign = SingleSendService::buildCampaign(
      name: 'My Campaign',
      listIds: ['list-uuid-here'],
      designId: 'design-uuid-here',
      senderId: 1234567,
      suppressionGroupId: 12345
  );
  \$result = \$service->create(\$campaign);

Required Components:
--------------------
  • Contact List - Who receives the email (list IDs or segment IDs)
  • Design - The email template (design ID)
  • Sender - Verified "from" address (sender ID)
  • Suppression Group - Unsubscribe handling (recommended)

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
        echo "   Example: SENDGRID_API_KEY='your-key' php SingleSendExample.php list\n\n";
        return 1;
    }

    $service = new SingleSendService($apiKey);
    $command = $argv[1] ?? 'help';

    switch ($command) {
        case 'list':
        case 'all':
            $pageSize = isset($argv[2]) ? (int) $argv[2] : 10;
            $result = $service->getAll($pageSize);
            if ($result instanceof BaseErrorDTO) {
                printResult($result, 'Get All Single Sends');
            } else {
                printSingleSendsTable($result);
            }
            break;

        case 'list-json':
            $pageSize = isset($argv[2]) ? (int) $argv[2] : 10;
            printResult($service->getAll($pageSize), 'Get All Single Sends');
            break;

        case 'get':
            $id = $argv[2] ?? null;
            if (!$id) {
                echo "Error: Single send ID required.\n";
                echo "Usage: php SingleSendExample.php get <id>\n";
                return 1;
            }
            $result = $service->getById($id);
            if ($result instanceof BaseErrorDTO) {
                printResult($result, "Get Single Send: {$id}");
            } else {
                printSingleSendDetails($result);
            }
            break;

        case 'search':
            $name = $argv[2] ?? null;
            if (!$name) {
                echo "Error: Search name required.\n";
                echo "Usage: php SingleSendExample.php search <name> [status]\n";
                return 1;
            }
            $status = isset($argv[3]) ? [$argv[3]] : null;
            $result = $service->search($name, $status);
            if ($result instanceof BaseErrorDTO) {
                printResult($result, "Search: {$name}");
            } else {
                printSingleSendsTable($result);
            }
            break;

        case 'categories':
            printResult($service->getAllCategories(), 'Get All Categories');
            break;

        case 'create-demo':
            echo "Creating a demo campaign requires configuration.\n\n";
            echo "Edit the script or use the following code:\n\n";
            echo <<<CODE
\$campaign = SingleSendService::buildCampaign(
    name: 'My Campaign',
    listIds: ['your-list-uuid'],
    designId: 'your-design-uuid',
    senderId: 1234567,              // Your sender ID (integer)
    suppressionGroupId: 12345       // Your suppression group ID
);

\$result = \$service->create(\$campaign);
CODE;
            echo "\n";
            break;

        case 'duplicate':
        case 'copy':
            $id = $argv[2] ?? null;
            $newName = $argv[3] ?? null;
            if (!$id || !$newName) {
                echo "Error: ID and new name required.\n";
                echo "Usage: php SingleSendExample.php duplicate <id> <new_name>\n";
                return 1;
            }
            printResult($service->duplicate($id, $newName), "Duplicate: {$id} -> {$newName}");
            break;

        case 'rename':
        case 'update':
            $id = $argv[2] ?? null;
            $newName = $argv[3] ?? null;
            if (!$id || !$newName) {
                echo "Error: ID and new name required.\n";
                echo "Usage: php SingleSendExample.php rename <id> <new_name>\n";
                return 1;
            }
            $dto = new SingleSendDTO();
            $dto->name = $newName;
            printResult($service->update($id, $dto), "Rename: {$id} -> {$newName}");
            break;

        case 'schedule':
        case 'send':
            $id = $argv[2] ?? null;
            $sendAt = $argv[3] ?? null;
            if (!$id || !$sendAt) {
                echo "Error: ID and send time required.\n";
                echo "Usage: php SingleSendExample.php schedule <id> now\n";
                echo "       php SingleSendExample.php schedule <id> <datetime>\n";
                return 1;
            }

            if ($sendAt === 'now') {
                echo "⚠️  This will send the campaign IMMEDIATELY!\n";
            } else {
                echo "⚠️  This will schedule the campaign for: {$sendAt}\n";
            }
            echo "Type 'yes' to confirm: ";
            $confirmation = trim(fgets(STDIN));

            if ($confirmation !== 'yes') {
                echo "Aborted.\n";
                return 0;
            }

            printResult($service->schedule($id, $sendAt), "Schedule: {$id}");
            break;

        case 'cancel':
        case 'unschedule':
            $id = $argv[2] ?? null;
            if (!$id) {
                echo "Error: Single send ID required.\n";
                echo "Usage: php SingleSendExample.php cancel <id>\n";
                return 1;
            }
            printResult($service->cancelSchedule($id), "Cancel Schedule: {$id}");
            break;

        case 'delete':
            $id = $argv[2] ?? null;
            if (!$id) {
                echo "Error: Single send ID required.\n";
                echo "Usage: php SingleSendExample.php delete <id>\n";
                return 1;
            }

            echo "⚠️  WARNING: This will permanently delete the campaign!\n";
            echo "ID: {$id}\n";
            echo "Type 'yes' to confirm: ";
            $confirmation = trim(fgets(STDIN));

            if ($confirmation !== 'yes') {
                echo "Aborted.\n";
                return 0;
            }

            $service->delete($id);
            echo "✅ Single send deleted successfully.\n";
            break;

        case 'bulk-delete':
            $ids = $argv[2] ?? null;
            if (!$ids) {
                echo "Error: Single send IDs required (comma-separated).\n";
                echo "Usage: php SingleSendExample.php bulk-delete <id1,id2,id3>\n";
                return 1;
            }

            $idArray = array_map('trim', explode(',', $ids));
            $count = count($idArray);

            echo "⚠️  WARNING: This will permanently delete {$count} campaign(s)!\n";
            echo "IDs: " . implode(', ', $idArray) . "\n";
            echo "Type 'yes' to confirm: ";
            $confirmation = trim(fgets(STDIN));

            if ($confirmation !== 'yes') {
                echo "Aborted.\n";
                return 0;
            }

            $service->bulkDelete($idArray);
            echo "✅ {$count} single send(s) deleted successfully.\n";
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
 * Example: Create and send a complete campaign
 * 
 * This demonstrates the full workflow from creation to sending.
 */
function exampleCreateAndSendCampaign(
    SingleSendService $service,
    string $listId,
    string $designId,
    int $senderId,
    int $suppressionGroupId
): void {
    // Step 1: Build the campaign configuration
    $campaign = SingleSendService::buildCampaign(
        name: 'Weekly Newsletter - ' . date('Y-m-d'),
        listIds: [$listId],
        designId: $designId,
        senderId: $senderId,
        suppressionGroupId: $suppressionGroupId
    );

    // Step 2: Create the campaign (draft status)
    echo "Creating campaign...\n";
    $result = $service->create($campaign);

    if ($result instanceof BaseErrorDTO) {
        echo "❌ Failed to create campaign\n";
        return;
    }

    echo "✅ Campaign created: {$result->id}\n";

    // Step 3: Schedule for immediate send
    echo "Scheduling for immediate send...\n";
    $scheduled = $service->schedule($result->id, 'now');

    if ($scheduled instanceof BaseErrorDTO) {
        echo "❌ Failed to schedule campaign\n";
        return;
    }

    echo "✅ Campaign scheduled! Status: {$scheduled->status?->name}\n";
}

/**
 * Example: Create a scheduled campaign for future delivery
 */
function exampleScheduleFutureCampaign(
    SingleSendService $service,
    string $listId,
    string $designId,
    int $senderId,
    \DateTimeInterface $sendAt
): void {
    $campaign = SingleSendService::buildCampaign(
        name: 'Scheduled Campaign - ' . $sendAt->format('Y-m-d H:i'),
        listIds: [$listId],
        designId: $designId,
        senderId: $senderId
    );

    $result = $service->create($campaign);

    if ($result instanceof BaseErrorDTO) {
        echo "❌ Failed to create campaign\n";
        return;
    }

    // Schedule for specific time (ISO 8601 format)
    $scheduled = $service->schedule($result->id, $sendAt->format('c'));

    if ($scheduled instanceof BaseErrorDTO) {
        echo "❌ Failed to schedule campaign\n";
        return;
    }

    echo "✅ Campaign scheduled for: {$sendAt->format('Y-m-d H:i:s')}\n";
}

/**
 * Example: Find and cleanup old draft campaigns
 */
function exampleCleanupDraftCampaigns(SingleSendService $service, int $daysOld = 30): void
{
    $result = $service->search('', ['draft']);

    if ($result instanceof BaseErrorDTO || empty($result->result)) {
        echo "No draft campaigns found.\n";
        return;
    }

    $cutoff = new \DateTime("-{$daysOld} days");
    $toDelete = [];

    foreach ($result->result as $send) {
        if (!isset($send->created_at)) {
            continue;
        }

        $createdAt = new \DateTime($send->created_at);
        if ($createdAt < $cutoff) {
            $toDelete[] = $send;
        }
    }

    if (empty($toDelete)) {
        echo "No draft campaigns older than {$daysOld} days.\n";
        return;
    }

    echo "Found " . count($toDelete) . " draft campaign(s) older than {$daysOld} days:\n";
    foreach ($toDelete as $send) {
        echo "  - {$send->name} ({$send->id})\n";
    }

    // Uncomment to actually delete:
    // $ids = array_map(fn($s) => $s->id, $toDelete);
    // $service->bulkDelete($ids);
    // echo "Deleted " . count($ids) . " campaigns.\n";
}

/**
 * Example: Duplicate a campaign for A/B testing
 */
function exampleCreateABTestVariants(SingleSendService $service, string $originalId): void
{
    $variants = [
        'A/B Test - Subject Line A',
        'A/B Test - Subject Line B',
        'A/B Test - Control',
    ];

    $createdIds = [];

    foreach ($variants as $name) {
        $result = $service->duplicate($originalId, $name);

        if ($result instanceof BaseErrorDTO) {
            echo "❌ Failed to create variant: {$name}\n";
            continue;
        }

        $createdIds[] = $result->id;
        echo "✅ Created: {$name} (ID: {$result->id})\n";
    }

    echo "\nCreated " . count($createdIds) . " A/B test variants.\n";
    echo "Edit each variant's design/subject before scheduling.\n";
}

/**
 * Example: Get campaign statistics summary
 */
function exampleCampaignSummary(SingleSendService $service): void
{
    $result = $service->getAll(100);

    if ($result instanceof BaseErrorDTO) {
        echo "Error fetching campaigns.\n";
        return;
    }

    $stats = [
        'draft' => 0,
        'scheduled' => 0,
        'triggered' => 0,
        'completed' => 0,
    ];

    foreach ($result->result as $send) {
        $status = $send->status ?? 'unknown';
        if (isset($stats[$status])) {
            $stats[$status]++;
        }
    }

    echo "\nCampaign Summary\n";
    echo str_repeat('=', 40) . "\n";
    echo "📝 Draft:      {$stats['draft']}\n";
    echo "📅 Scheduled:  {$stats['scheduled']}\n";
    echo "🚀 Sending:    {$stats['triggered']}\n";
    echo "✅ Completed:  {$stats['completed']}\n";
    echo str_repeat('-', 40) . "\n";
    echo "   Total:      " . count($result->result) . "\n";
}

/**
 * Example: Export campaigns to CSV
 */
function exampleExportCampaigns(SingleSendService $service): void
{
    $result = $service->getAll(100);

    if ($result instanceof BaseErrorDTO) {
        echo "Error fetching campaigns.\n";
        return;
    }

    $csv = "ID,Name,Status,Send At,Created At\n";

    foreach ($result->result as $send) {
        $csv .= sprintf(
            "%s,\"%s\",%s,%s,%s\n",
            $send->id ?? '',
            str_replace('"', '""', $send->name ?? ''),
            $send->status ?? '',
            $send->send_at ?? '',
            $send->created_at ?? ''
        );
    }

    file_put_contents('campaigns_export.csv', $csv);
    echo "Exported " . count($result->result) . " campaigns to campaigns_export.csv\n";
}

// Run CLI if executed directly
if (php_sapi_name() === 'cli' && isset($argv)) {
    exit(main($argv));
}