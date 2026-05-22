<?php
/**
 * Automated Report Generation Script
 * 
 * This script should be run via cron job:
 * - Weekly: Every Sunday at 11:59 PM
 * - Monthly: Last day of month at 11:59 PM
 * - Yearly: December 31 at 11:59 PM
 * 
 * Example cron entries:
 * 59 23 * * 0 /usr/bin/php /path/to/generate_reports.php weekly
 * 59 23 28-31 * * /usr/bin/php /path/to/generate_reports.php monthly
 * 59 23 31 12 * /usr/bin/php /path/to/generate_reports.php yearly
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/ReportGenerator.php';

// Get report type from command line argument
$reportType = $argv[1] ?? null;

if (!in_array($reportType, ['weekly', 'monthly', 'yearly'])) {
    echo "Usage: php generate_reports.php [weekly|monthly|yearly]\n";
    exit(1);
}

try {
    $reportGenerator = new ReportGenerator();
    
    // Calculate period dates
    $endDate = date('Y-m-d');
    
    switch ($reportType) {
        case 'weekly':
            // Last 7 days
            $startDate = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'monthly':
            // First day of current month to last day
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
            break;
        case 'yearly':
            // January 1 to December 31 of current year
            $startDate = date('Y-01-01');
            $endDate = date('Y-12-31');
            break;
    }
    
    echo "Generating {$reportType} report for period: {$startDate} to {$endDate}\n";
    
    $result = $reportGenerator->generateReport($reportType, $startDate, $endDate, null);
    
    echo "Report generated successfully!\n";
    echo "File: {$result['file_name']}\n";
    echo "Path: {$result['file_path']}\n";
    
} catch (Exception $e) {
    echo "Error generating report: " . $e->getMessage() . "\n";
    exit(1);
}

