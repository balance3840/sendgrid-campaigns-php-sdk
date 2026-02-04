<?php

/**
 * SendGrid Campaign Statistics Examples
 * 
 * This file demonstrates how to use the Stats entity from the SendgridCampaign library.
 * Stats provide insights into campaign performance including opens, clicks, bounces, and more.
 * Use these metrics to measure engagement and optimize future campaigns.
 * 
 * Run from command line: php StatsExample.php [command] [options]
 * 
 * @package SendgridCampaign
 * @example php StatsExample.php help
 */

declare(strict_types=1);

use SendgridCampaign\DTO\BaseDTO;
use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\Entities\Stats\DTO\LinkStatsListDTO;
use SendgridCampaign\Entities\Stats\Enums\AbPhaseType;
use SendgridCampaign\Entities\Stats\Stats;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Campaign Statistics Service
 * 
 * Provides a clean interface for retrieving SendGrid campaign statistics.
 * Statistics are available after a campaign has been sent and include
 * detailed metrics about delivery, engagement, and link performance.
 * 
 * Key Metrics:
 * - Requests: Total emails attempted
 * - Delivered: Successfully delivered emails
 * - Opens: Unique and total open counts
 * - Clicks: Unique and total click counts
 * - Bounces: Hard and soft bounce counts
 * - Unsubscribes: Recipients who unsubscribed
 * - Spam Reports: Recipients who marked as spam
 */
final class StatsService
{
    private Stats $stats;

    public function __construct(
        private readonly string $apiKey
    ) {
        $this->stats = new Stats(apiKey: $this->apiKey);
    }

    /**
     * Get statistics for a specific single send campaign
     *
     * @param string $singleSendId The campaign ID to get stats for
     * @param string|null $startDate Filter stats from this date (YYYY-MM-DD)
     * @param string|null $endDate Filter stats until this date (YYYY-MM-DD)
     * @param int|null $pageSize Number of results per page
     * @param string|null $pageToken Pagination token for next page
     * @param array<string>|null $groupBy Group results by dimensions (e.g., 'day')
     */
    public function getById(
        string $singleSendId,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $pageSize = null,
        ?string $pageToken = null,
        ?array $groupBy = null
    ): BaseDTO|BaseErrorDTO {
        return $this->stats->getById(
            singleSendId: $singleSendId,
            startDate: $startDate,
            endDate: $endDate,
            pageSize: $pageSize,
            pageToken: $pageToken,
            groupBy: $groupBy
        );
    }

    /**
     * Get statistics for multiple campaigns
     *
     * @param array<string>|null $singleSendIds Campaign IDs to get stats for (empty for all)
     * @param int|null $pageSize Number of results per page
     * @param string|null $pageToken Pagination token for next page
     */
    public function getAll(
        ?array $singleSendIds = [],
        ?int $pageSize = null,
        ?string $pageToken = null
    ): BaseDTO|BaseErrorDTO {
        return $this->stats->getAll(
            singleSendIds: $singleSendIds,
            pageSize: $pageSize,
            pageToken: $pageToken
        );
    }

    /**
     * Get link click statistics for a campaign
     *
     * Returns detailed click data for each link in the email,
     * useful for understanding which content drives engagement.
     *
     * @param string $singleSendId The campaign ID
     * @param int|null $pageSize Number of results per page
     * @param string|null $pageToken Pagination token for next page
     * @param array<string>|null $groupBy Group results by dimensions
     * @param string|null $abVariationId Filter by A/B test variation
     * @param AbPhaseType|null $abPhaseId Filter by A/B test phase
     */
    public function getLinkStats(
        string $singleSendId,
        ?int $pageSize = null,
        ?string $pageToken = null,
        ?array $groupBy = null,
        ?string $abVariationId = null,
        ?AbPhaseType $abPhaseId = null
    ): LinkStatsListDTO|BaseErrorDTO {
        return $this->stats->getLinkStats(
            singleSendId: $singleSendId,
            page_size: $pageSize,
            page_token: $pageToken,
            group_by: $groupBy,
            ab_variation_id: $abVariationId,
            ab_phase_id: $abPhaseId
        );
    }

    /**
     * Export statistics as CSV
     *
     * Generates a CSV export of campaign statistics for analysis
     * in spreadsheet applications or data pipelines.
     *
     * @param array<string> $singleSendIds Campaign IDs to export
     * @param string|null $timezone Timezone for date formatting (e.g., 'UTC', 'America/New_York')
     * @return string|BaseErrorDTO CSV content or error
     */
    public function export(
        array $singleSendIds,
        ?string $timezone = null
    ): string|BaseErrorDTO {
        return $this->stats->export(
            singleSendIds: $singleSendIds,
            timezone: $timezone
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
    } elseif ($result === null) {
        echo "✅ SUCCESS (no response body)\n";
    } elseif (is_string($result)) {
        // CSV export returns a string
        echo "✅ SUCCESS (CSV data):\n";
        echo $result;
    } else {
        echo "✅ SUCCESS:\n";
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    echo "\n";
}

/**
 * Display formatted campaign statistics
 */
function printStatsOverview(BaseDTO $stats): void
{
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "Campaign Statistics Overview\n";
    echo str_repeat('=', 60) . "\n";

    // Extract stats from the DTO structure
    $data = json_decode(json_encode($stats), true);

    if (isset($data['results']) && is_array($data['results'])) {
        foreach ($data['results'] as $result) {
            printSingleCampaignStats($result);
        }
    } else {
        printSingleCampaignStats($data);
    }
}

/**
 * Print stats for a single campaign
 */
function printSingleCampaignStats(array $data): void
{
    $id = $data['id'] ?? $data['single_send_id'] ?? 'Unknown';
    echo "\n📊 Campaign: {$id}\n";
    echo str_repeat('-', 50) . "\n";

    // Delivery metrics
    $requests = $data['stats']['requests'] ?? $data['requests'] ?? 0;
    $delivered = $data['stats']['delivered'] ?? $data['delivered'] ?? 0;
    $deliveryRate = $requests > 0 ? round(($delivered / $requests) * 100, 1) : 0;

    echo "\n📬 Delivery\n";
    echo "   Requests:      " . number_format($requests) . "\n";
    echo "   Delivered:     " . number_format($delivered) . " ({$deliveryRate}%)\n";

    // Bounces
    $bounces = $data['stats']['bounces'] ?? $data['bounces'] ?? 0;
    $bounceDrops = $data['stats']['bounce_drops'] ?? $data['bounce_drops'] ?? 0;
    echo "   Bounces:       " . number_format($bounces) . "\n";
    echo "   Bounce Drops:  " . number_format($bounceDrops) . "\n";

    // Engagement metrics
    $opens = $data['stats']['opens'] ?? $data['opens'] ?? 0;
    $uniqueOpens = $data['stats']['unique_opens'] ?? $data['unique_opens'] ?? 0;
    $openRate = $delivered > 0 ? round(($uniqueOpens / $delivered) * 100, 1) : 0;

    $clicks = $data['stats']['clicks'] ?? $data['clicks'] ?? 0;
    $uniqueClicks = $data['stats']['unique_clicks'] ?? $data['unique_clicks'] ?? 0;
    $clickRate = $delivered > 0 ? round(($uniqueClicks / $delivered) * 100, 1) : 0;

    echo "\n👁️  Engagement\n";
    echo "   Opens:         " . number_format($opens) . " total, " . number_format($uniqueOpens) . " unique ({$openRate}%)\n";
    echo "   Clicks:        " . number_format($clicks) . " total, " . number_format($uniqueClicks) . " unique ({$clickRate}%)\n";

    // Click-to-open rate (CTOR)
    $ctor = $uniqueOpens > 0 ? round(($uniqueClicks / $uniqueOpens) * 100, 1) : 0;
    echo "   Click-to-Open: {$ctor}%\n";

    // Negative metrics
    $unsubscribes = $data['stats']['unsubscribes'] ?? $data['unsubscribes'] ?? 0;
    $spamReports = $data['stats']['spam_reports'] ?? $data['spam_reports'] ?? 0;

    echo "\n⚠️  Negative Signals\n";
    echo "   Unsubscribes:  " . number_format($unsubscribes) . "\n";
    echo "   Spam Reports:  " . number_format($spamReports) . "\n";

    echo "\n";
}

/**
 * Display link statistics in a table format
 */
function printLinkStatsTable(LinkStatsListDTO $linkStats): void
{
    echo "\n" . str_repeat('=', 90) . "\n";
    echo "Link Click Statistics\n";
    echo str_repeat('=', 90) . "\n";
    echo sprintf("%-50s  %-15s  %s\n", "URL", "TOTAL CLICKS", "UNIQUE CLICKS");
    echo str_repeat('-', 90) . "\n";

    $results = $linkStats->results ?? [];

    if (empty($results)) {
        echo "No link statistics available.\n";
    } else {
        foreach ($results as $link) {
            $url = $link->url ?? 'Unknown URL';
            $totalClicks = $link->clicks ?? $link->total_clicks ?? 0;
            $uniqueClicks = $link->unique_clicks ?? 0;

            // Truncate long URLs
            $displayUrl = strlen($url) > 48 ? substr($url, 0, 45) . '...' : $url;

            echo sprintf(
                "%-50s  %-15s  %s\n",
                $displayUrl,
                number_format($totalClicks),
                number_format($uniqueClicks)
            );
        }
    }

    echo str_repeat('=', 90) . "\n";
}

/**
 * Display available commands
 */
function showHelp(): void
{
    echo <<<HELP

SendGrid Campaign Statistics Examples
=====================================

Usage: php StatsExample.php <command> [options]

Available Commands:
-------------------
  get <campaign_id>                 Get stats for a single campaign
  get-json <campaign_id>            Get stats as JSON output
  all [page_size]                   Get stats for all campaigns
  links <campaign_id>               Get link click statistics
  export <campaign_id> [timezone]   Export stats as CSV
  metrics                           Show available metrics reference
  help                              Show this help message

Environment:
------------
  Set SENDGRID_API_KEY environment variable or edit this file.

Examples:
---------
  php StatsExample.php get 01e03f70-003d-11f1-a913-da8805c925c4
  php StatsExample.php all 10
  php StatsExample.php links 64bd5f1a-37eb-11ec-afea-ea40ce6b6799
  php StatsExample.php export 035f6099-2b67-11ec-803f-6e7b5d805d01 America/New_York

Date Filtering (for 'get' command):
-----------------------------------
  Add date range by setting environment variables:
  
  STATS_START_DATE=2024-01-01 STATS_END_DATE=2024-01-31 \\
      php StatsExample.php get <campaign_id>

Understanding Your Stats:
-------------------------
  Good benchmarks vary by industry, but general guidelines:

  📬 Delivery Rate:  >95% is good, <90% needs investigation
  👁️  Open Rate:      15-25% is typical, >30% is excellent
  🖱️  Click Rate:     2-5% is typical, >5% is excellent
  📊 Click-to-Open:  10-15% is typical, >20% is excellent
  ⚠️  Unsubscribe:    <0.5% is normal, >1% needs attention
  🚫 Spam Rate:      <0.1% is ideal, >0.1% is concerning

HELP;
}

/**
 * Show metrics reference
 */
function showMetricsReference(): void
{
    echo <<<METRICS

Available Statistics Metrics
============================

┌─────────────────────┬────────────────────────────────────────────────────────┐
│ Metric              │ Description                                            │
├─────────────────────┼────────────────────────────────────────────────────────┤
│ requests            │ Total emails SendGrid attempted to send                │
│ delivered           │ Emails successfully delivered to recipient servers     │
│ opens               │ Total times emails were opened (includes repeats)      │
│ unique_opens        │ Number of unique recipients who opened                 │
│ clicks              │ Total link clicks (includes repeats)                   │
│ unique_clicks       │ Number of unique recipients who clicked                │
│ bounces             │ Emails that couldn't be delivered                      │
│ bounce_drops        │ Emails not sent due to previous bounces                │
│ unsubscribes        │ Recipients who unsubscribed                            │
│ spam_reports        │ Recipients who marked email as spam                    │
│ spam_report_drops   │ Emails not sent due to previous spam reports           │
│ invalid_emails      │ Emails with invalid addresses                          │
│ blocks              │ Emails blocked by recipient servers                    │
│ deferred            │ Emails temporarily delayed                             │
└─────────────────────┴────────────────────────────────────────────────────────┘

Calculated Metrics:
-------------------
  Delivery Rate    = delivered / requests × 100
  Open Rate        = unique_opens / delivered × 100
  Click Rate       = unique_clicks / delivered × 100
  Click-to-Open    = unique_clicks / unique_opens × 100 (CTOR)
  Bounce Rate      = bounces / requests × 100
  Unsubscribe Rate = unsubscribes / delivered × 100

Grouping Options:
-----------------
  Stats can be grouped by time periods using the 'groupBy' parameter:
  - 'day': Daily breakdown
  - 'week': Weekly breakdown
  - 'month': Monthly breakdown

METRICS;
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
        echo "   Example: SENDGRID_API_KEY='your-key' php StatsExample.php get <id>\n\n";
        return 1;
    }

    $service = new StatsService($apiKey);
    $command = $argv[1] ?? 'help';

    switch ($command) {
        case 'get':
            $campaignId = $argv[2] ?? null;
            if (!$campaignId) {
                echo "Error: Campaign ID required.\n";
                echo "Usage: php StatsExample.php get <campaign_id>\n";
                return 1;
            }

            // Optional date filters from environment
            $startDate = getenv('STATS_START_DATE') ?: null;
            $endDate = getenv('STATS_END_DATE') ?: null;

            $result = $service->getById(
                singleSendId: $campaignId,
                startDate: $startDate,
                endDate: $endDate
            );

            if ($result instanceof BaseErrorDTO) {
                printResult($result, "Get Stats: {$campaignId}");
            } else {
                printStatsOverview($result);
            }
            break;

        case 'get-json':
            $campaignId = $argv[2] ?? null;
            if (!$campaignId) {
                echo "Error: Campaign ID required.\n";
                echo "Usage: php StatsExample.php get-json <campaign_id>\n";
                return 1;
            }
            printResult($service->getById($campaignId), "Get Stats: {$campaignId}");
            break;

        case 'all':
        case 'list':
            $pageSize = isset($argv[2]) ? (int) $argv[2] : 10;
            $result = $service->getAll(pageSize: $pageSize);
            if ($result instanceof BaseErrorDTO) {
                printResult($result, 'Get All Stats');
            } else {
                printStatsOverview($result);
            }
            break;

        case 'all-json':
            $pageSize = isset($argv[2]) ? (int) $argv[2] : 10;
            printResult($service->getAll(pageSize: $pageSize), 'Get All Stats');
            break;

        case 'links':
            $campaignId = $argv[2] ?? null;
            if (!$campaignId) {
                echo "Error: Campaign ID required.\n";
                echo "Usage: php StatsExample.php links <campaign_id>\n";
                return 1;
            }

            $result = $service->getLinkStats($campaignId);
            if ($result instanceof BaseErrorDTO) {
                printResult($result, "Link Stats: {$campaignId}");
            } else {
                printLinkStatsTable($result);
            }
            break;

        case 'links-json':
            $campaignId = $argv[2] ?? null;
            if (!$campaignId) {
                echo "Error: Campaign ID required.\n";
                return 1;
            }
            printResult($service->getLinkStats($campaignId), "Link Stats: {$campaignId}");
            break;

        case 'export':
            $campaignId = $argv[2] ?? null;
            $timezone = $argv[3] ?? 'UTC';

            if (!$campaignId) {
                echo "Error: Campaign ID required.\n";
                echo "Usage: php StatsExample.php export <campaign_id> [timezone]\n";
                return 1;
            }

            $result = $service->export([$campaignId], $timezone);

            if ($result instanceof BaseErrorDTO) {
                printResult($result, "Export Stats: {$campaignId}");
            } else {
                // Save to file
                $filename = "stats_export_{$campaignId}.csv";
                file_put_contents($filename, $result);
                echo "✅ Stats exported to: {$filename}\n";
            }
            break;

        case 'metrics':
        case 'reference':
            showMetricsReference();
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
 * Example: Generate a performance report for multiple campaigns
 */
function examplePerformanceReport(StatsService $service, array $campaignIds): void
{
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "Campaign Performance Report\n";
    echo "Generated: " . date('Y-m-d H:i:s') . "\n";
    echo str_repeat('=', 80) . "\n";

    $totals = [
        'requests' => 0,
        'delivered' => 0,
        'opens' => 0,
        'unique_opens' => 0,
        'clicks' => 0,
        'unique_clicks' => 0,
        'bounces' => 0,
        'unsubscribes' => 0,
    ];

    foreach ($campaignIds as $id) {
        $result = $service->getById($id);

        if ($result instanceof BaseErrorDTO) {
            echo "⚠️  Could not fetch stats for: {$id}\n";
            continue;
        }

        $data = json_decode(json_encode($result), true);
        $stats = $data['stats'] ?? $data;

        // Accumulate totals
        foreach ($totals as $key => &$value) {
            $value += $stats[$key] ?? 0;
        }
    }

    // Print summary
    echo "\n📊 Aggregate Summary (" . count($campaignIds) . " campaigns)\n";
    echo str_repeat('-', 50) . "\n";

    $deliveryRate = $totals['requests'] > 0
        ? round(($totals['delivered'] / $totals['requests']) * 100, 1)
        : 0;
    $openRate = $totals['delivered'] > 0
        ? round(($totals['unique_opens'] / $totals['delivered']) * 100, 1)
        : 0;
    $clickRate = $totals['delivered'] > 0
        ? round(($totals['unique_clicks'] / $totals['delivered']) * 100, 1)
        : 0;

    echo "Total Sent:       " . number_format($totals['requests']) . "\n";
    echo "Total Delivered:  " . number_format($totals['delivered']) . " ({$deliveryRate}%)\n";
    echo "Total Opens:      " . number_format($totals['unique_opens']) . " ({$openRate}%)\n";
    echo "Total Clicks:     " . number_format($totals['unique_clicks']) . " ({$clickRate}%)\n";
    echo "Total Bounces:    " . number_format($totals['bounces']) . "\n";
    echo "Total Unsubs:     " . number_format($totals['unsubscribes']) . "\n";
}

/**
 * Example: Compare two campaigns side by side
 */
function exampleCompareCampaigns(StatsService $service, string $campaignA, string $campaignB): void
{
    $statsA = $service->getById($campaignA);
    $statsB = $service->getById($campaignB);

    if ($statsA instanceof BaseErrorDTO || $statsB instanceof BaseErrorDTO) {
        echo "Error fetching stats for comparison.\n";
        return;
    }

    $dataA = json_decode(json_encode($statsA), true);
    $dataB = json_decode(json_encode($statsB), true);

    echo "\n" . str_repeat('=', 70) . "\n";
    echo "Campaign Comparison\n";
    echo str_repeat('=', 70) . "\n";
    echo sprintf("%-20s  %-20s  %-20s\n", "METRIC", "CAMPAIGN A", "CAMPAIGN B");
    echo str_repeat('-', 70) . "\n";

    $metrics = ['requests', 'delivered', 'unique_opens', 'unique_clicks', 'bounces', 'unsubscribes'];

    foreach ($metrics as $metric) {
        $valueA = $dataA['stats'][$metric] ?? $dataA[$metric] ?? 0;
        $valueB = $dataB['stats'][$metric] ?? $dataB[$metric] ?? 0;

        $diff = $valueA - $valueB;
        $diffStr = $diff > 0 ? "+{$diff}" : (string) $diff;

        echo sprintf(
            "%-20s  %-20s  %-20s  (%s)\n",
            ucfirst(str_replace('_', ' ', $metric)),
            number_format($valueA),
            number_format($valueB),
            $diffStr
        );
    }

    echo str_repeat('=', 70) . "\n";
}

/**
 * Example: Analyze link performance
 */
function exampleAnalyzeLinkPerformance(StatsService $service, string $campaignId): void
{
    $result = $service->getLinkStats($campaignId);

    if ($result instanceof BaseErrorDTO) {
        echo "Error fetching link stats.\n";
        return;
    }

    $links = $result->results ?? [];

    if (empty($links)) {
        echo "No link data available.\n";
        return;
    }

    // Sort by clicks descending
    usort($links, fn($a, $b) => ($b->clicks ?? 0) - ($a->clicks ?? 0));

    echo "\n" . str_repeat('=', 60) . "\n";
    echo "Link Performance Analysis\n";
    echo str_repeat('=', 60) . "\n";

    $totalClicks = array_sum(array_map(fn($l) => $l->clicks ?? 0, $links));

    echo "Total Links: " . count($links) . "\n";
    echo "Total Clicks: " . number_format($totalClicks) . "\n\n";

    echo "🏆 Top Performing Links:\n";
    echo str_repeat('-', 50) . "\n";

    $topLinks = array_slice($links, 0, 5);
    foreach ($topLinks as $index => $link) {
        $rank = $index + 1;
        $url = $link->url ?? 'Unknown';
        $clicks = $link->clicks ?? 0;
        $percentage = $totalClicks > 0 ? round(($clicks / $totalClicks) * 100, 1) : 0;

        echo "#{$rank}: {$clicks} clicks ({$percentage}%)\n";
        echo "    URL: " . substr($url, 0, 60) . (strlen($url) > 60 ? '...' : '') . "\n\n";
    }
}

/**
 * Example: Export stats to JSON file
 */
function exampleExportToJson(StatsService $service, string $campaignId): void
{
    $stats = $service->getById($campaignId);
    $linkStats = $service->getLinkStats($campaignId);

    $export = [
        'campaign_id' => $campaignId,
        'exported_at' => date('c'),
        'stats' => $stats instanceof BaseErrorDTO ? null : $stats,
        'link_stats' => $linkStats instanceof BaseErrorDTO ? null : $linkStats,
    ];

    $filename = "stats_{$campaignId}_" . date('Ymd_His') . ".json";
    file_put_contents($filename, json_encode($export, JSON_PRETTY_PRINT));

    echo "✅ Stats exported to: {$filename}\n";
}

/**
 * Example: Check campaign health and alert on issues
 */
function exampleHealthCheck(StatsService $service, string $campaignId): void
{
    $result = $service->getById($campaignId);

    if ($result instanceof BaseErrorDTO) {
        echo "❌ Could not fetch stats.\n";
        return;
    }

    $data = json_decode(json_encode($result), true);
    $stats = $data['stats'] ?? $data;

    $requests = $stats['requests'] ?? 0;
    $delivered = $stats['delivered'] ?? 0;
    $bounces = $stats['bounces'] ?? 0;
    $spamReports = $stats['spam_reports'] ?? 0;
    $unsubscribes = $stats['unsubscribes'] ?? 0;

    echo "\n🏥 Campaign Health Check\n";
    echo str_repeat('=', 50) . "\n";

    $issues = [];

    // Check delivery rate
    $deliveryRate = $requests > 0 ? ($delivered / $requests) * 100 : 0;
    if ($deliveryRate < 90) {
        $issues[] = "⚠️  Low delivery rate: {$deliveryRate}% (target: >95%)";
    }

    // Check bounce rate
    $bounceRate = $requests > 0 ? ($bounces / $requests) * 100 : 0;
    if ($bounceRate > 5) {
        $issues[] = "⚠️  High bounce rate: {$bounceRate}% (target: <5%)";
    }

    // Check spam rate
    $spamRate = $delivered > 0 ? ($spamReports / $delivered) * 100 : 0;
    if ($spamRate > 0.1) {
        $issues[] = "🚨 High spam rate: {$spamRate}% (target: <0.1%)";
    }

    // Check unsubscribe rate
    $unsubRate = $delivered > 0 ? ($unsubscribes / $delivered) * 100 : 0;
    if ($unsubRate > 1) {
        $issues[] = "⚠️  High unsubscribe rate: {$unsubRate}% (target: <1%)";
    }

    if (empty($issues)) {
        echo "✅ All metrics within healthy ranges!\n";
    } else {
        echo "Issues found:\n\n";
        foreach ($issues as $issue) {
            echo "  {$issue}\n";
        }
    }

    echo "\n";
}

// Run CLI if executed directly
if (php_sapi_name() === 'cli' && isset($argv)) {
    exit(main($argv));
}