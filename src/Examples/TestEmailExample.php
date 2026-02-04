<?php

/**
 * SendGrid Test Email Examples
 * 
 * This file demonstrates how to use the TestEmail entity from the SendgridCampaign library.
 * Test emails allow you to preview and validate your email templates before sending
 * to your full audience. Always test your campaigns before scheduling!
 * 
 * Run from command line: php TestEmailExample.php [command] [options]
 * 
 * @package SendgridCampaign
 * @example php TestEmailExample.php help
 */

declare(strict_types=1);

use SendgridCampaign\Entities\TestEmail\TestEmail;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Test Email Service
 * 
 * Provides a clean interface for sending test/preview emails via SendGrid.
 * Test emails are essential for QA before launching campaigns.
 * 
 * Use Cases:
 * - Preview how emails render in different clients
 * - Verify personalization/merge tags work correctly
 * - Check links and images load properly
 * - Get stakeholder approval before sending
 * - Test spam filter behavior
 */
final class TestEmailService
{
    private TestEmail $testEmail;

    public function __construct(
        private readonly string $apiKey
    ) {
        $this->testEmail = new TestEmail(apiKey: $this->apiKey);
    }

    /**
     * Send a test email using a dynamic template
     *
     * @param string $templateId The dynamic template ID (d-xxxxxxxx format)
     * @param array<string> $emails Recipient email addresses for the test
     * @param int $senderId The verified sender ID to use
     * @param string|null $versionIdOverride Specific template version to test (null for active)
     * @param string|null $customUnsubscribeUrl Custom unsubscribe URL override
     * @param int|null $suppressionGroupId Unsubscribe group ID
     * @param string|null $fromAddress Override the from email address
     * 
     * @note Test emails don't count against your contact limits but do count against sending limits.
     */
    public function send(
        string $templateId,
        array $emails,
        int $senderId,
        ?string $versionIdOverride = null,
        ?string $customUnsubscribeUrl = null,
        ?int $suppressionGroupId = null,
        ?string $fromAddress = null
    ): void {
        $this->testEmail->send(
            templateId: $templateId,
            emails: $emails,
            version_id_override: $versionIdOverride,
            sender_id: $senderId,
            custom_unsubscribe_url: $customUnsubscribeUrl,
            suppression_group_id: $suppressionGroupId,
            from_address: $fromAddress
        );
    }

    /**
     * Send a test email with minimal configuration
     *
     * Convenience method for quick tests with just the essentials.
     *
     * @param string $templateId The dynamic template ID
     * @param string $email Single recipient email address
     * @param int $senderId The verified sender ID
     */
    public function sendQuick(string $templateId, string $email, int $senderId): void
    {
        $this->send(
            templateId: $templateId,
            emails: [$email],
            senderId: $senderId
        );
    }

    /**
     * Send test emails to multiple recipients
     *
     * Useful for getting approval from multiple stakeholders.
     *
     * @param string $templateId The dynamic template ID
     * @param array<string> $emails List of recipient email addresses
     * @param int $senderId The verified sender ID
     */
    public function sendToTeam(string $templateId, array $emails, int $senderId): void
    {
        $this->send(
            templateId: $templateId,
            emails: $emails,
            senderId: $senderId
        );
    }
}

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

/**
 * Display available commands
 */
function showHelp(): void
{
    echo <<<HELP

SendGrid Test Email Examples
============================

Usage: php TestEmailExample.php <command> [options]

Available Commands:
-------------------
  send <template_id> <sender_id> <email>       Send a test email
  send-multi <template_id> <sender_id> <emails> Send to multiple recipients
  interactive                                   Interactive test email wizard
  help                                          Show this help message

Environment:
------------
  Set SENDGRID_API_KEY environment variable or edit this file.

Examples:
---------
  # Send a single test email
  php TestEmailExample.php send d-abc123xyz 1234567 test@example.com

  # Send to multiple recipients (comma-separated)
  php TestEmailExample.php send-multi d-abc123xyz 1234567 qa@example.com,pm@example.com

  # Use the interactive wizard
  php TestEmailExample.php interactive

Required Parameters:
--------------------
  template_id  - Dynamic template ID (format: d-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx)
  sender_id    - Numeric ID of a verified sender
  email(s)     - One or more recipient email addresses

Finding Your IDs:
-----------------
  Template ID:  SendGrid Dashboard → Email API → Dynamic Templates → Select template
                The ID starts with "d-" followed by 32 hexadecimal characters
                
  Sender ID:    Run 'php sender_example.php list' to see your verified senders
                Or: SendGrid Dashboard → Settings → Sender Authentication

Tips for Effective Testing:
---------------------------
  ✓ Test on multiple email clients (Gmail, Outlook, Apple Mail)
  ✓ Check both desktop and mobile rendering
  ✓ Verify all links work correctly
  ✓ Confirm images load properly
  ✓ Test with different personalization data
  ✓ Check spam score before sending to full list
  ✓ Have multiple team members review the test

HELP;
}

/**
 * Interactive wizard for sending test emails
 */
function interactiveWizard(TestEmailService $service): int
{
    echo "\n";
    echo str_repeat('=', 50) . "\n";
    echo "📧 Test Email Wizard\n";
    echo str_repeat('=', 50) . "\n\n";

    // Get template ID
    echo "Enter template ID (d-xxxxxxxx): ";
    $templateId = trim(fgets(STDIN));

    if (empty($templateId)) {
        echo "❌ Template ID is required.\n";
        return 1;
    }

    if (!str_starts_with($templateId, 'd-')) {
        echo "⚠️  Warning: Template ID usually starts with 'd-'\n";
        echo "Continue anyway? (y/n): ";
        if (strtolower(trim(fgets(STDIN))) !== 'y') {
            echo "Aborted.\n";
            return 0;
        }
    }

    // Get sender ID
    echo "Enter sender ID (numeric): ";
    $senderIdInput = trim(fgets(STDIN));

    if (!is_numeric($senderIdInput)) {
        echo "❌ Sender ID must be a number.\n";
        return 1;
    }
    $senderId = (int) $senderIdInput;

    // Get recipient emails
    echo "Enter recipient email(s) (comma-separated): ";
    $emailsInput = trim(fgets(STDIN));

    if (empty($emailsInput)) {
        echo "❌ At least one email is required.\n";
        return 1;
    }

    $emails = array_map('trim', explode(',', $emailsInput));

    // Validate emails
    foreach ($emails as $email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "❌ Invalid email address: {$email}\n";
            return 1;
        }
    }

    // Confirm
    echo "\n" . str_repeat('-', 50) . "\n";
    echo "Summary:\n";
    echo "  Template:   {$templateId}\n";
    echo "  Sender ID:  {$senderId}\n";
    echo "  Recipients: " . implode(', ', $emails) . "\n";
    echo str_repeat('-', 50) . "\n";
    echo "\nSend test email? (y/n): ";

    if (strtolower(trim(fgets(STDIN))) !== 'y') {
        echo "Aborted.\n";
        return 0;
    }

    // Send
    try {
        $service->send(
            templateId: $templateId,
            emails: $emails,
            senderId: $senderId
        );
        echo "\n✅ Test email sent successfully!\n";
        echo "   Check the inbox(es) of: " . implode(', ', $emails) . "\n\n";
        return 0;
    } catch (\Exception $e) {
        echo "\n❌ Failed to send test email.\n";
        echo "   Error: " . $e->getMessage() . "\n\n";
        return 1;
    }
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
        echo "   Example: SENDGRID_API_KEY='your-key' php TestEmailExample.php send ...\n\n";
        return 1;
    }

    $service = new TestEmailService($apiKey);
    $command = $argv[1] ?? 'help';

    switch ($command) {
        case 'send':
            $templateId = $argv[2] ?? null;
            $senderId = $argv[3] ?? null;
            $email = $argv[4] ?? null;

            if (!$templateId || !$senderId || !$email) {
                echo "Error: All parameters required.\n";
                echo "Usage: php TestEmailExample.php send <template_id> <sender_id> <email>\n";
                echo "Example: php TestEmailExample.php send d-abc123 1234567 test@example.com\n";
                return 1;
            }

            if (!is_numeric($senderId)) {
                echo "Error: Sender ID must be a number.\n";
                return 1;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo "Error: Invalid email address: {$email}\n";
                return 1;
            }

            echo "Sending test email...\n";
            echo "  Template:  {$templateId}\n";
            echo "  Sender:    {$senderId}\n";
            echo "  Recipient: {$email}\n\n";

            try {
                $service->sendQuick($templateId, $email, (int) $senderId);
                echo "✅ Test email sent successfully!\n";
                echo "   Check the inbox of: {$email}\n";
                return 0;
            } catch (\Exception $e) {
                echo "❌ Failed to send test email.\n";
                echo "   Error: " . $e->getMessage() . "\n";
                return 1;
            }

        case 'send-multi':
        case 'send-multiple':
            $templateId = $argv[2] ?? null;
            $senderId = $argv[3] ?? null;
            $emailsStr = $argv[4] ?? null;

            if (!$templateId || !$senderId || !$emailsStr) {
                echo "Error: All parameters required.\n";
                echo "Usage: php TestEmailExample.php send-multi <template_id> <sender_id> <emails>\n";
                echo "Example: php TestEmailExample.php send-multi d-abc123 1234567 a@ex.com,b@ex.com\n";
                return 1;
            }

            if (!is_numeric($senderId)) {
                echo "Error: Sender ID must be a number.\n";
                return 1;
            }

            $emails = array_map('trim', explode(',', $emailsStr));

            // Validate all emails
            foreach ($emails as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo "Error: Invalid email address: {$email}\n";
                    return 1;
                }
            }

            echo "Sending test email to " . count($emails) . " recipient(s)...\n";
            echo "  Template:   {$templateId}\n";
            echo "  Sender:     {$senderId}\n";
            echo "  Recipients: " . implode(', ', $emails) . "\n\n";

            try {
                $service->sendToTeam($templateId, $emails, (int) $senderId);
                echo "✅ Test emails sent successfully!\n";
                echo "   Check the inboxes of: " . implode(', ', $emails) . "\n";
                return 0;
            } catch (\Exception $e) {
                echo "❌ Failed to send test emails.\n";
                echo "   Error: " . $e->getMessage() . "\n";
                return 1;
            }

        case 'interactive':
        case 'wizard':
            return interactiveWizard($service);

        case 'help':
        case '--help':
        case '-h':
        default:
            showHelp();
            return 0;
    }
}

// =============================================================================
// ADVANCED EXAMPLES (for reference)
// =============================================================================

/**
 * Example: Send test email with all options
 */
function exampleFullOptionsTest(TestEmailService $service): void
{
    $service->send(
        templateId: 'd-a626d0e87dbb4eef90e4efe5086e265a',
        emails: ['qa@example.com', 'pm@example.com', 'design@example.com'],
        senderId: 1194082,
        versionIdOverride: 'version-uuid-here',     // Test specific version
        customUnsubscribeUrl: 'https://example.com/unsubscribe',
        suppressionGroupId: 15737,
        fromAddress: 'noreply@example.com'
    );

    echo "✅ Test email sent with all options configured.\n";
}

/**
 * Example: Test multiple template versions
 * 
 * Useful for A/B testing different versions before deciding which to use.
 */
function exampleTestMultipleVersions(
    TestEmailService $service,
    string $templateId,
    array $versionIds,
    string $testEmail,
    int $senderId
): void {
    echo "Testing " . count($versionIds) . " template versions...\n\n";

    foreach ($versionIds as $index => $versionId) {
        $versionNum = $index + 1;
        echo "Sending version {$versionNum} ({$versionId})...\n";

        try {
            $service->send(
                templateId: $templateId,
                emails: [$testEmail],
                senderId: $senderId,
                versionIdOverride: $versionId
            );
            echo "  ✅ Sent successfully\n";
        } catch (\Exception $e) {
            echo "  ❌ Failed: " . $e->getMessage() . "\n";
        }

        // Small delay between sends to make them easier to distinguish
        if ($index < count($versionIds) - 1) {
            sleep(2);
        }
    }

    echo "\n🎉 All versions sent! Check {$testEmail} to compare.\n";
}

/**
 * Example: Pre-launch checklist with automated testing
 */
function examplePreLaunchChecklist(
    TestEmailService $service,
    string $templateId,
    int $senderId,
    array $stakeholderEmails
): array {
    $checklist = [
        'test_sent' => false,
        'recipients' => [],
        'timestamp' => null,
        'notes' => [],
    ];

    echo "\n📋 Pre-Launch Test Checklist\n";
    echo str_repeat('=', 50) . "\n\n";

    // Step 1: Send to QA team
    echo "Step 1: Sending test emails to stakeholders...\n";

    try {
        $service->send(
            templateId: $templateId,
            emails: $stakeholderEmails,
            senderId: $senderId
        );

        $checklist['test_sent'] = true;
        $checklist['recipients'] = $stakeholderEmails;
        $checklist['timestamp'] = date('Y-m-d H:i:s');

        echo "  ✅ Test emails sent to:\n";
        foreach ($stakeholderEmails as $email) {
            echo "     - {$email}\n";
        }
    } catch (\Exception $e) {
        echo "  ❌ Failed to send test emails\n";
        $checklist['notes'][] = 'Send failed: ' . $e->getMessage();
        return $checklist;
    }

    // Step 2: Remind to check
    echo "\nStep 2: Manual verification required\n";
    echo "  □ Check email renders correctly in Gmail\n";
    echo "  □ Check email renders correctly in Outlook\n";
    echo "  □ Check email renders correctly on mobile\n";
    echo "  □ Verify all links work\n";
    echo "  □ Verify images load properly\n";
    echo "  □ Confirm personalization tags render\n";
    echo "  □ Check unsubscribe link works\n";
    echo "  □ Review subject line and preview text\n";

    echo "\nStep 3: Get stakeholder approval\n";
    echo "  □ Marketing approved\n";
    echo "  □ Legal approved (if applicable)\n";
    echo "  □ Final sign-off received\n";

    echo "\n" . str_repeat('=', 50) . "\n";
    echo "Template ID: {$templateId}\n";
    echo "Test sent at: {$checklist['timestamp']}\n";
    echo str_repeat('=', 50) . "\n";

    return $checklist;
}

/**
 * Example: Send test to common email providers
 * 
 * Ensures rendering works across different email clients.
 */
function exampleCrossClientTest(
    TestEmailService $service,
    string $templateId,
    int $senderId,
    string $baseEmail
): void {
    // Extract username and domain
    $atPos = strpos($baseEmail, '@');
    $username = substr($baseEmail, 0, $atPos);

    // You would need actual accounts on these providers
    $testAddresses = [
        "{$username}+gmail@gmail.com",
        "{$username}+outlook@outlook.com",
        "{$username}+yahoo@yahoo.com",
        $baseEmail, // Original address
    ];

    echo "Sending cross-client test emails...\n";
    echo "Using variations of: {$baseEmail}\n\n";

    foreach ($testAddresses as $email) {
        echo "  → {$email}... ";

        try {
            $service->sendQuick($templateId, $email, $senderId);
            echo "✅\n";
        } catch (\Exception $e) {
            echo "❌ " . $e->getMessage() . "\n";
        }

        usleep(500000); // 0.5 second delay
    }

    echo "\n💡 Tip: Check each inbox to verify rendering.\n";
}

// Run CLI if executed directly
if (php_sapi_name() === 'cli' && isset($argv)) {
    exit(main($argv));
}