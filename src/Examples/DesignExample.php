<?php

/**
 * SendGrid Design Management Examples
 * 
 * This file demonstrates how to use the Design entity from the SendgridCampaign library.
 * Designs are reusable email templates that can be used across multiple campaigns.
 * They support both visual (drag-and-drop) and code-based editing.
 * 
 * Run from command line: php DesignExample.php [command] [options]
 * 
 * @package SendgridCampaign
 * @example php DesignExample.php help
 */

declare(strict_types=1);

use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\DTO\BaseListDto;
use SendgridCampaign\Entities\Design\Design;
use SendgridCampaign\Entities\Design\DTO\DesignDTO;
use SendgridCampaign\Entities\Design\Enums\EditorType;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Design Management Service
 * 
 * Provides a clean interface for managing SendGrid email designs (templates).
 * Designs can be created with HTML content and reused across campaigns.
 * 
 * Editor Types:
 * - CODE: For hand-coded HTML templates (full control)
 * - DESIGN: For templates created with the visual drag-and-drop editor
 */
final class DesignService
{
    private Design $design;

    public function __construct(
        private readonly string $apiKey
    ) {
        $this->design = new Design(apiKey: $this->apiKey);
    }

    /**
     * Retrieve all designs with pagination
     *
     * @param int $pageSize Number of designs per page (default: 10, max: 100)
     * @return BaseListDto<DesignDTO>|BaseErrorDTO
     */
    public function getAll(int $pageSize = 10): BaseListDto|BaseErrorDTO
    {
        return $this->design->getAll(pageSize: $pageSize);
    }

    /**
     * Get a specific design by its ID
     *
     * @param string $designId The unique design ID (UUID format)
     */
    public function getById(string $designId): DesignDTO|BaseErrorDTO
    {
        return $this->design->getById($designId);
    }

    /**
     * Create a new design
     *
     * @param string $htmlContent The HTML content of the email template
     * @param string|null $name Display name for the design (auto-generated if null)
     * @param EditorType|null $editor Editor type: CODE for raw HTML, DESIGN for visual editor
     * @param string|null $plainContent Plain text version of the email (for non-HTML clients)
     * 
     * @note If plainContent is not provided, consider using generatePlainContent
     *       when sending to auto-generate from HTML.
     */
    public function create(
        string $htmlContent,
        ?string $name = null,
        ?EditorType $editor = null,
        ?string $plainContent = null
    ): DesignDTO|BaseErrorDTO {
        return $this->design->create(
            htmlContent: $htmlContent,
            name: $name,
            editor: $editor,
            plainContent: $plainContent
        );
    }

    /**
     * Duplicate an existing design
     *
     * Creates a copy of an existing design, optionally with a new name.
     * Useful for creating variations of a template.
     *
     * @param string $designId The ID of the design to duplicate
     * @param string|null $name Name for the new design (defaults to "Copy of [original]")
     * @param EditorType|null $editor Editor type for the duplicate
     */
    public function duplicate(
        string $designId,
        ?string $name = null,
        ?EditorType $editor = null
    ): DesignDTO|BaseErrorDTO {
        return $this->design->duplicate(
            designId: $designId,
            name: $name,
            editor: $editor
        );
    }

    /**
     * Update an existing design
     *
     * @param string $designId The ID of the design to update
     * @param string|null $name New name for the design
     * @param string|null $plainContent New plain text content
     * @param bool|null $generatePlainContent If true, auto-generates plain content from HTML
     * 
     * @note You cannot update the HTML content of an existing design.
     *       To change HTML, create a new design or duplicate and modify.
     */
    public function update(
        string $designId,
        ?string $name = null,
        ?string $plainContent = null,
        ?bool $generatePlainContent = null
    ): DesignDTO|BaseErrorDTO {
        return $this->design->update(
            designId: $designId,
            name: $name,
            plainContent: $plainContent,
            generatePlainContent: $generatePlainContent
        );
    }

    /**
     * Delete a design
     *
     * @param string $designId The ID of the design to delete
     * 
     * @warning This permanently removes the design. This action cannot be undone.
     * @note Designs used by active campaigns should not be deleted.
     */
    public function delete(string $designId): void
    {
        $this->design->delete($designId);
    }

    /**
     * Parse editor type from string input
     *
     * @param string $type The editor type string
     * @return EditorType|null Returns null if invalid
     */
    public static function parseEditorType(string $type): ?EditorType
    {
        return match (strtolower(trim($type))) {
            'code', 'html', 'raw' => EditorType::CODE,
            'design', 'visual', 'drag-drop', 'dragdrop' => EditorType::DESIGN,
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
 * Display a formatted table of designs
 * @param BaseListDTO<DesignDTO> $designs The list of designs to display
 */
function printDesignsTable(BaseListDTO $designs): void
{
    echo "\n" . str_repeat('=', 90) . "\n";
    echo sprintf("%-36s  %-30s  %-10s  %s\n", "ID", "NAME", "EDITOR", "UPDATED");
    echo str_repeat('-', 90) . "\n";

    if (empty($designs->result)) {
        echo "No designs found.\n";
    } else {
        foreach ($designs->result as $design) {
            $updatedAt = $design->updatedAt ?? $design->createdAt ?? 'N/A';
            if ($updatedAt !== 'N/A') {
                $updatedAt = date('Y-m-d H:i', strtotime($updatedAt));
            }

            echo sprintf(
                "%-36s  %-30s  %-10s  %s\n",
                $design->id,
                substr($design->name ?? 'Unnamed', 0, 28),
                $design->editor ?? 'N/A',
                $updatedAt
            );
        }
    }

    echo str_repeat('=', 90) . "\n";
    echo "Total: " . count($designs->result ?? []) . " design(s)\n";
}

/**
 * Display available commands
 */
function showHelp(): void
{
    echo <<<HELP

SendGrid Design Management Examples
===================================

Usage: php DesignExample.php <command> [options]

Available Commands:
-------------------
  list [page_size]                      List all designs (default: 10)
  list-json [page_size]                 List all designs (JSON output)
  get <design_id>                       Get a design by ID
  create <html_file> [name] [editor]    Create a design from HTML file
  create-inline <html> [name] [editor]  Create a design from inline HTML
  duplicate <design_id> [name]          Duplicate an existing design
  rename <design_id> <new_name>         Rename a design
  delete <design_id>                    Delete a design
  editors                               Show available editor types
  help                                  Show this help message

Editor Types:
-------------
  code    - For hand-coded HTML templates (full control over markup)
  design  - For visual drag-and-drop editor templates

Environment:
------------
  Set SENDGRID_API_KEY environment variable or edit this file.

Examples:
---------
  php DesignExample.php list
  php DesignExample.php list 25
  php DesignExample.php get 7e908cbb-21e5-431a-a500-e0210569ace2
  php DesignExample.php create ./template.html "Welcome Email" code
  php DesignExample.php create-inline "<html><body><h1>Hello</h1></body></html>" "Simple Design"
  php DesignExample.php duplicate abc123 "Welcome Email v2"
  php DesignExample.php rename abc123 "Updated Name"
  php DesignExample.php delete abc123

Template Variables:
-------------------
  Use SendGrid substitution tags in your HTML for personalization:
  
    {{first_name}}     - Contact's first name
    {{last_name}}      - Contact's last name
    {{email}}          - Contact's email address
    {{{unsubscribe}}}  - Unsubscribe link (required for marketing emails)
    
  Custom fields use their field ID: {{e1_T}}, {{e2_N}}, etc.

HELP;
}

/**
 * Show editor type information
 */
function showEditorTypes(): void
{
    echo <<<EDITORS

Available Editor Types
======================

┌──────────┬────────────────────────────────────────────────────────────────┐
│ Type     │ Description                                                    │
├──────────┼────────────────────────────────────────────────────────────────┤
│ code     │ For hand-coded HTML templates.                                 │
│          │ - Full control over HTML markup                                │
│          │ - Best for developers and custom designs                       │
│          │ - Supports any valid HTML/CSS                                  │
│          │ - Cannot be edited in the visual editor after creation         │
├──────────┼────────────────────────────────────────────────────────────────┤
│ design   │ For visual drag-and-drop editor templates.                     │
│          │ - Created/edited in SendGrid's Design Editor                   │
│          │ - Uses a structured module format                              │
│          │ - Easier for non-technical users                               │
│          │ - Some HTML limitations apply                                  │
└──────────┴────────────────────────────────────────────────────────────────┘

When to use each:
-----------------
  CODE:   You have custom HTML, need pixel-perfect control, or have a 
          developer maintaining templates.
          
  DESIGN: Marketing team manages templates, need easy updates, or
          starting from SendGrid's template library.

EDITORS;
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
        echo "   Example: SENDGRID_API_KEY='your-key' php DesignExample.php list\n\n";
        return 1;
    }

    $service = new DesignService($apiKey);
    $command = $argv[1] ?? 'help';

    switch ($command) {
        case 'list':
        case 'all':
            $pageSize = isset($argv[2]) ? (int) $argv[2] : 10;
            $result = $service->getAll($pageSize);
            if ($result instanceof BaseErrorDTO) {
                printResult($result, 'Get All Designs');
            } else {
                printDesignsTable($result);
            }
            break;

        case 'list-json':
            $pageSize = isset($argv[2]) ? (int) $argv[2] : 10;
            printResult($service->getAll($pageSize), 'Get All Designs');
            break;

        case 'get':
            $designId = $argv[2] ?? null;
            if (!$designId) {
                echo "Error: Design ID required.\n";
                echo "Usage: php DesignExample.php get <design_id>\n";
                return 1;
            }
            printResult($service->getById($designId), "Get Design: {$designId}");
            break;

        case 'create':
            $htmlFile = $argv[2] ?? null;
            $name = $argv[3] ?? null;
            $editorStr = $argv[4] ?? 'code';

            if (!$htmlFile) {
                echo "Error: HTML file path required.\n";
                echo "Usage: php DesignExample.php create <html_file> [name] [editor]\n";
                return 1;
            }

            if (!file_exists($htmlFile)) {
                echo "Error: File not found: {$htmlFile}\n";
                return 1;
            }

            $htmlContent = file_get_contents($htmlFile);
            if ($htmlContent === false) {
                echo "Error: Could not read file: {$htmlFile}\n";
                return 1;
            }

            $editor = DesignService::parseEditorType($editorStr);
            if ($editor === null) {
                echo "Error: Invalid editor type '{$editorStr}'. Use 'code' or 'design'.\n";
                return 1;
            }

            $name = $name ?? pathinfo($htmlFile, PATHINFO_FILENAME);

            printResult(
                $service->create($htmlContent, $name, $editor),
                "Create Design: {$name}"
            );
            break;

        case 'create-inline':
            $htmlContent = $argv[2] ?? null;
            $name = $argv[3] ?? null;
            $editorStr = $argv[4] ?? 'code';

            if (!$htmlContent) {
                echo "Error: HTML content required.\n";
                echo "Usage: php DesignExample.php create-inline \"<html>...</html>\" [name] [editor]\n";
                return 1;
            }

            $editor = DesignService::parseEditorType($editorStr);
            if ($editor === null) {
                echo "Error: Invalid editor type '{$editorStr}'. Use 'code' or 'design'.\n";
                return 1;
            }

            printResult(
                $service->create($htmlContent, $name, $editor),
                "Create Design from Inline HTML"
            );
            break;

        case 'duplicate':
        case 'copy':
            $designId = $argv[2] ?? null;
            $name = $argv[3] ?? null;

            if (!$designId) {
                echo "Error: Design ID required.\n";
                echo "Usage: php DesignExample.php duplicate <design_id> [name]\n";
                return 1;
            }

            printResult(
                $service->duplicate($designId, $name),
                "Duplicate Design: {$designId}"
            );
            break;

        case 'rename':
        case 'update':
            $designId = $argv[2] ?? null;
            $newName = $argv[3] ?? null;

            if (!$designId || !$newName) {
                echo "Error: Design ID and new name required.\n";
                echo "Usage: php DesignExample.php rename <design_id> <new_name>\n";
                return 1;
            }

            printResult(
                $service->update($designId, $newName),
                "Rename Design: {$designId} -> {$newName}"
            );
            break;

        case 'delete':
            $designId = $argv[2] ?? null;

            if (!$designId) {
                echo "Error: Design ID required.\n";
                echo "Usage: php DesignExample.php delete <design_id>\n";
                return 1;
            }

            echo "⚠️  WARNING: This will permanently delete the design!\n";
            echo "Design ID: {$designId}\n";
            echo "Type 'yes' to confirm: ";
            $confirmation = trim(fgets(STDIN));

            if ($confirmation !== 'yes') {
                echo "Aborted.\n";
                return 0;
            }

            $service->delete($designId);
            echo "✅ Design deleted successfully.\n";
            break;

        case 'editors':
        case 'types':
            showEditorTypes();
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
 * Example: Create a complete transactional email template
 */
function exampleCreateTransactionalTemplate(DesignService $service): void
{
    $htmlContent = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Order Confirmation</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                .button { display: inline-block; padding: 12px 24px; background: #4CAF50; 
                          color: white; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Order Confirmed! 🎉</h1>
                </div>
                <div class="content">
                    <p>Hi {{first_name}},</p>
                    <p>Thank you for your order! We've received your purchase and are 
                       getting it ready for shipment.</p>
                    <p><strong>Order Number:</strong> {{order_number}}</p>
                    <p><strong>Order Total:</strong> \${{order_total}}</p>
                    <p style="text-align: center; margin: 30px 0;">
                        <a href="{{tracking_url}}" class="button">Track Your Order</a>
                    </p>
                    <p>If you have any questions, reply to this email or contact our 
                       support team.</p>
                    <p>Thanks,<br>The Team</p>
                </div>
                <div class="footer">
                    <p>© 2024 Your Company. All rights reserved.</p>
                    <p><a href="{{{unsubscribe}}}">Unsubscribe</a></p>
                </div>
            </div>
        </body>
        </html>
        HTML;

    $plainContent = <<<PLAIN
        Order Confirmed!
        
        Hi {{first_name}},
        
        Thank you for your order! We've received your purchase and are getting it ready.
        
        Order Number: {{order_number}}
        Order Total: \${{order_total}}
        
        Track your order: {{tracking_url}}
        
        Thanks,
        The Team
        
        ---
        Unsubscribe: {{{unsubscribe}}}
        PLAIN;

    $result = $service->create(
        htmlContent: $htmlContent,
        name: 'Order Confirmation Template',
        editor: EditorType::CODE,
        plainContent: $plainContent
    );

    printResult($result, 'Create Transactional Template');
}

/**
 * Example: Create a marketing newsletter template
 */
function exampleCreateNewsletterTemplate(DesignService $service): void
{
    $htmlContent = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #f4f4f4; }
                .wrapper { max-width: 600px; margin: 0 auto; background: white; }
                .hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                        padding: 40px; text-align: center; color: white; }
                .hero h1 { margin: 0; font-size: 28px; }
                .section { padding: 30px; }
                .article { margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid #eee; }
                .article:last-child { border-bottom: none; }
                .article h2 { color: #333; margin: 0 0 10px; }
                .article p { color: #666; }
                .cta { display: inline-block; padding: 10px 20px; background: #667eea; 
                       color: white; text-decoration: none; border-radius: 4px; }
                .footer { background: #333; color: #999; padding: 20px; text-align: center; }
                .footer a { color: #667eea; }
            </style>
        </head>
        <body>
            <div class="wrapper">
                <div class="hero">
                    <h1>Weekly Newsletter</h1>
                    <p>Your weekly dose of insights and updates</p>
                </div>
                <div class="section">
                    <div class="article">
                        <h2>{{article_1_title}}</h2>
                        <p>{{article_1_summary}}</p>
                        <a href="{{article_1_url}}" class="cta">Read More</a>
                    </div>
                    <div class="article">
                        <h2>{{article_2_title}}</h2>
                        <p>{{article_2_summary}}</p>
                        <a href="{{article_2_url}}" class="cta">Read More</a>
                    </div>
                </div>
                <div class="footer">
                    <p>You're receiving this because you subscribed to our newsletter.</p>
                    <p><a href="{{{unsubscribe}}}">Unsubscribe</a> | 
                       <a href="{{preferences_url}}">Update Preferences</a></p>
                </div>
            </div>
        </body>
        </html>
        HTML;

    $result = $service->create(
        htmlContent: $htmlContent,
        name: 'Weekly Newsletter Template',
        editor: EditorType::CODE
    );

    printResult($result, 'Create Newsletter Template');
}

/**
 * Example: Batch duplicate designs for A/B testing
 */
function exampleCreateABTestVariants(DesignService $service, string $originalDesignId): void
{
    $variants = ['Variant A - Short CTA', 'Variant B - Long CTA', 'Variant C - No CTA'];

    echo "Creating A/B test variants...\n\n";

    foreach ($variants as $variantName) {
        $result = $service->duplicate($originalDesignId, $variantName);

        if ($result instanceof BaseErrorDTO) {
            echo "❌ Failed to create '{$variantName}'\n";
        } else {
            echo "✅ Created: {$variantName} (ID: {$result->id})\n";
        }
    }
}

/**
 * Example: List designs and export to CSV
 */
function exampleExportDesignsList(DesignService $service): void
{
    $result = $service->getAll(pageSize: 100);

    if ($result instanceof BaseErrorDTO) {
        echo "Error fetching designs\n";
        return;
    }

    $csv = "ID,Name,Editor,Created,Updated\n";

    foreach ($result->result as $design) {
        $csv .= sprintf(
            "%s,\"%s\",%s,%s,%s\n",
            $design->id,
            str_replace('"', '""', $design->name ?? ''),
            $design->editor ?? '',
            $design->createdAt ?? '',
            $design->updatedAt ?? ''
        );
    }

    file_put_contents('designs_export.csv', $csv);
    echo "Exported " . count($result->result) . " designs to designs_export.csv\n";
}

/**
 * Example: Validate HTML template has required elements
 */
function exampleValidateTemplate(string $htmlContent): array
{
    $issues = [];

    // Check for unsubscribe link (required for marketing emails)
    if (strpos($htmlContent, '{{{unsubscribe}}}') === false) {
        $issues[] = 'Missing unsubscribe link: {{{unsubscribe}}}';
    }

    // Check for basic HTML structure
    if (stripos($htmlContent, '<!DOCTYPE') === false) {
        $issues[] = 'Missing DOCTYPE declaration';
    }

    if (stripos($htmlContent, '<html') === false) {
        $issues[] = 'Missing <html> tag';
    }

    // Check for meta viewport (mobile responsiveness)
    if (stripos($htmlContent, 'viewport') === false) {
        $issues[] = 'Missing viewport meta tag (mobile responsiveness)';
    }

    // Check for common personalization
    if (strpos($htmlContent, '{{') === false) {
        $issues[] = 'No personalization tags found (e.g., {{first_name}})';
    }

    return $issues;
}

// Run CLI if executed directly
if (php_sapi_name() === 'cli' && isset($argv)) {
    exit(main($argv));
}