<?php

/**
 * SendGrid Sender Management Examples
 * 
 * This file demonstrates how to use the Sender entity from the SendgridCampaign library.
 * Senders represent verified "from" addresses that can be used to send emails.
 * Before sending campaigns, you must have at least one verified sender.
 * 
 * Run from command line: php SenderExample.php [command] [options]
 * 
 * @package SendgridCampaign
 * @example php SenderExample.php help
 */

declare(strict_types=1);

use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\Entities\Sender\DTO\SenderDTO;
use SendgridCampaign\Entities\Sender\Sender;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Sender Management Service
 * 
 * Provides a clean interface for managing SendGrid sender identities.
 * Senders must be verified before they can be used to send emails.
 * 
 * Sender Types:
 * - Single Sender: Individual email addresses verified via confirmation email
 * - Domain Authentication: All addresses on a domain verified via DNS records
 * 
 * @note At least one verified sender is required to send any email through SendGrid.
 */
final class SenderService
{
    private Sender $sender;

    public function __construct(
        private readonly string $apiKey
    ) {
        $this->sender = new Sender(apiKey: $this->apiKey);
    }

    /**
     * Retrieve all senders (both verified and pending)
     *
     * Returns all sender identities configured in your account,
     * regardless of verification status.
     *
     * @return array<SenderDTO>|BaseErrorDTO
     */
    public function getAll(): array|BaseErrorDTO
    {
        return $this->sender->getAll();
    }

    /**
     * Retrieve only verified senders
     *
     * Returns senders that have completed verification and are
     * ready to use for sending emails.
     *
     * @return array<SenderDTO>|BaseErrorDTO
     */
    public function getAllVerified(): array|BaseErrorDTO
    {
        return $this->sender->getAllVerified();
    }

    /**
     * Get a specific sender by ID
     *
     * @param int $senderId The numeric sender ID
     */
    public function getById(int $senderId): SenderDTO|BaseErrorDTO
    {
        return $this->sender->getById($senderId);
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
 * Display a formatted table of senders
 *
 * @param array<SenderDTO> $senders
 */
function printSendersTable(array $senders, bool $showVerifiedOnly = false): void
{
    $title = $showVerifiedOnly ? 'Verified Senders' : 'All Senders';
    
    echo "\n" . str_repeat('=', 95) . "\n";
    echo $title . "\n";
    echo str_repeat('=', 95) . "\n";
    echo sprintf(
        "%-10s  %-25s  %-30s  %-10s  %s\n",
        "ID",
        "NAME",
        "EMAIL",
        "VERIFIED",
        "REPLY-TO"
    );
    echo str_repeat('-', 95) . "\n";

    if (empty($senders)) {
        echo "No senders found.\n";
    } else {
        foreach ($senders as $sender) {
            $verified = ($sender->verified ?? false) ? '✅ Yes' : '⏳ Pending';
            $replyTo = $sender->replyTo ?? $sender->from ?? 'N/A';
            
            echo sprintf(
                "%-10s  %-25s  %-30s  %-10s  %s\n",
                $sender->id ?? 'N/A',
                substr($sender->nickname ?? $sender->fromName ?? 'Unnamed', 0, 23),
                substr($sender->from ?? 'N/A', 0, 28),
                $verified,
                substr($replyTo, 0, 20)
            );
        }
    }

    echo str_repeat('=', 95) . "\n";
    echo "Total: " . count($senders) . " sender(s)\n";
}

/**
 * Display detailed information about a single sender
 */
function printSenderDetails(SenderDTO $sender): void
{
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "Sender Details\n";
    echo str_repeat('=', 60) . "\n";

    $fields = [
        'ID' => $sender->id ?? 'N/A',
        'Nickname' => $sender->nickname ?? 'N/A',
        'From Name' => $sender->fromName ?? 'N/A',
        'From Email' => $sender->from ?? 'N/A',
        'Reply-To' => $sender->replyTo ?? 'N/A',
        'Verified' => ($sender->verified ?? false) ? '✅ Yes' : '⏳ Pending verification',
        'Locked' => ($sender->locked ?? false) ? 'Yes' : 'No',
    ];

    // Add address fields if present
    if (!empty($sender->address)) {
        $fields['Address'] = $sender->address;
    }
    if (!empty($sender->city)) {
        $fields['City'] = $sender->city;
    }
    if (!empty($sender->state)) {
        $fields['State'] = $sender->state;
    }
    if (!empty($sender->zip)) {
        $fields['ZIP'] = $sender->zip;
    }
    if (!empty($sender->country)) {
        $fields['Country'] = $sender->country;
    }

    foreach ($fields as $label => $value) {
        echo sprintf("%-15s: %s\n", $label, $value);
    }

    echo str_repeat('=', 60) . "\n";
}

/**
 * Display available commands
 */
function showHelp(): void
{
    echo <<<HELP

SendGrid Sender Management Examples
===================================

Usage: php SenderExample.php <command> [options]

Available Commands:
-------------------
  list                  List all senders (verified and pending)
  list-json             List all senders (JSON output)
  verified              List only verified senders
  verified-json         List only verified senders (JSON output)
  get <sender_id>       Get a specific sender by ID
  help                  Show this help message

Environment:
------------
  Set SENDGRID_API_KEY environment variable or edit this file.

Examples:
---------
  php SenderExample.php list
  php SenderExample.php verified
  php SenderExample.php get 5912036

Understanding Senders:
----------------------
  Senders are "from" addresses used when sending emails. SendGrid requires
  verification to prevent spam and protect deliverability.

  Verification Methods:
  1. Single Sender Verification - Confirm via email link
  2. Domain Authentication - Verify entire domain via DNS records

  Benefits of Domain Authentication:
  - Send from any address @yourdomain.com
  - Better deliverability (SPF, DKIM, DMARC)
  - No per-address verification needed

Sender Fields:
--------------
  id          - Unique numeric identifier
  nickname    - Internal label for the sender
  from        - The "from" email address
  fromName    - Display name shown to recipients
  replyTo     - Where replies are directed
  verified    - Whether verification is complete
  locked      - Whether sender can be modified

Tips:
-----
  • You need at least one verified sender to send emails
  • Use domain authentication for production environments
  • Keep reply-to addresses monitored for customer responses
  • Sender address affects deliverability - use professional domains

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
        echo "   Example: SENDGRID_API_KEY='your-key' php SenderExample.php list\n\n";
        return 1;
    }

    $service = new SenderService($apiKey);
    $command = $argv[1] ?? 'help';

    switch ($command) {
        case 'list':
        case 'all':
            $result = $service->getAll();
            if ($result instanceof BaseErrorDTO) {
                printResult($result, 'Get All Senders');
            } else {
                printSendersTable($result, showVerifiedOnly: false);
            }
            break;

        case 'list-json':
            printResult($service->getAll(), 'Get All Senders');
            break;

        case 'verified':
            $result = $service->getAllVerified();
            if ($result instanceof BaseErrorDTO) {
                printResult($result, 'Get Verified Senders');
            } else {
                printSendersTable($result, showVerifiedOnly: true);
            }
            break;

        case 'verified-json':
            printResult($service->getAllVerified(), 'Get Verified Senders');
            break;

        case 'get':
            $senderId = $argv[2] ?? null;
            if (!$senderId) {
                echo "Error: Sender ID required.\n";
                echo "Usage: php SenderExample.php get <sender_id>\n";
                return 1;
            }

            if (!is_numeric($senderId)) {
                echo "Error: Sender ID must be a number.\n";
                return 1;
            }

            $result = $service->getById((int) $senderId);
            if ($result instanceof BaseErrorDTO) {
                printResult($result, "Get Sender: {$senderId}");
            } else {
                printSenderDetails($result);
            }
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
 * Example: Find a verified sender for a campaign
 * 
 * Useful when you need to programmatically select a sender
 * for sending a campaign.
 */
function exampleFindSenderForCampaign(SenderService $service, ?string $preferredDomain = null): ?SenderDTO
{
    $senders = $service->getAllVerified();

    if ($senders instanceof BaseErrorDTO || empty($senders)) {
        echo "No verified senders available.\n";
        return null;
    }

    // If preferred domain specified, try to find matching sender
    if ($preferredDomain !== null) {
        foreach ($senders as $sender) {
            $email = $sender->from ?? '';
            if (str_ends_with($email, '@' . $preferredDomain)) {
                echo "Found sender for domain {$preferredDomain}: {$email}\n";
                return $sender;
            }
        }
        echo "No sender found for domain {$preferredDomain}, using first available.\n";
    }

    // Return first verified sender
    return $senders[0];
}

/**
 * Example: List senders grouped by domain
 */
function exampleGroupSendersByDomain(SenderService $service): void
{
    $senders = $service->getAll();

    if ($senders instanceof BaseErrorDTO) {
        echo "Error fetching senders.\n";
        return;
    }

    $byDomain = [];

    foreach ($senders as $sender) {
        $email = $sender->from ?? '';
        $domain = substr($email, strpos($email, '@') + 1) ?: 'unknown';
        
        if (!isset($byDomain[$domain])) {
            $byDomain[$domain] = [];
        }
        $byDomain[$domain][] = $sender;
    }

    echo "\nSenders by Domain\n";
    echo str_repeat('=', 60) . "\n";

    foreach ($byDomain as $domain => $domainSenders) {
        $verifiedCount = count(array_filter($domainSenders, fn($s) => $s->verified ?? false));
        echo "\n📧 {$domain} ({$verifiedCount}/" . count($domainSenders) . " verified)\n";
        
        foreach ($domainSenders as $sender) {
            $status = ($sender->verified ?? false) ? '✅' : '⏳';
            echo "   {$status} {$sender->from} ({$sender->nickname})\n";
        }
    }
}

/**
 * Example: Check if account is ready to send
 * 
 * Validates that at least one verified sender exists.
 */
function exampleCheckSendReadiness(SenderService $service): bool
{
    $verified = $service->getAllVerified();

    if ($verified instanceof BaseErrorDTO) {
        echo "❌ Error checking senders: Could not fetch sender list.\n";
        return false;
    }

    if (empty($verified)) {
        echo "❌ Not ready to send: No verified senders found.\n";
        echo "\nTo fix this:\n";
        echo "1. Go to SendGrid Dashboard → Settings → Sender Authentication\n";
        echo "2. Add and verify a sender identity\n";
        echo "3. Complete email verification or domain authentication\n";
        return false;
    }

    echo "✅ Ready to send! Found " . count($verified) . " verified sender(s):\n\n";
    
    foreach ($verified as $sender) {
        echo "   • {$sender->from?->name}";
        if ($sender->from_name) {
            echo " ({$sender->from_name})";
        }
        echo "\n";
    }

    return true;
}

/**
 * Example: Export senders for backup/documentation
 */
function exampleExportSenders(SenderService $service): array
{
    $senders = $service->getAll();

    if ($senders instanceof BaseErrorDTO) {
        return ['error' => 'Failed to fetch senders'];
    }

    $export = [
        'exported_at' => date('Y-m-d H:i:s'),
        'total_count' => count($senders),
        'verified_count' => count(array_filter($senders, fn($s) => $s->verified ?? false)),
        'senders' => [],
    ];

    foreach ($senders as $sender) {
        $export['senders'][] = [
            'id' => $sender->id,
            'nickname' => $sender->nickname,
            'from_email' => $sender->from,
            'from_name' => $sender->from_name,
            'reply_to' => $sender->reply_to,
            'verified' => $sender->verified ?? false,
            'locked' => $sender->locked ?? false,
        ];
    }

    return $export;
}

/**
 * Example: Display sender selection menu
 * 
 * Interactive selection for scripts that need user input.
 */
function exampleInteractiveSenderSelection(SenderService $service): ?SenderDTO
{
    $verified = $service->getAllVerified();

    if ($verified instanceof BaseErrorDTO || empty($verified)) {
        echo "No verified senders available.\n";
        return null;
    }

    echo "\nSelect a sender:\n";
    echo str_repeat('-', 40) . "\n";

    foreach ($verified as $index => $sender) {
        $num = $index + 1;
        echo "[{$num}] {$sender->from?->name}";
        if ($sender->from_name) {
            echo " ({$sender->from_name})";
        }
        echo "\n";
    }

    echo "\nEnter number (1-" . count($verified) . "): ";
    $choice = trim(fgets(STDIN));

    if (!is_numeric($choice) || $choice < 1 || $choice > count($verified)) {
        echo "Invalid selection.\n";
        return null;
    }

    $selected = $verified[(int) $choice - 1];
    echo "Selected: {$selected->from?->name}\n";

    return $selected;
}

// Run CLI if executed directly
if (php_sapi_name() === 'cli' && isset($argv)) {
    exit(main($argv));
}