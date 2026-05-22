<?php
/**
 * Clear PHP OpCache
 * Run this after updating PHP files to ensure changes take effect
 */

echo "<h1>Clear PHP Cache</h1>";
echo "<hr>";

// Clear opcache if enabled
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "<p style='color: green; font-weight: bold;'>✅ OpCache cleared successfully!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Failed to clear OpCache</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ OpCache is not enabled</p>";
}

// Clear realpath cache
clearstatcache(true);
echo "<p style='color: green;'>✅ Realpath cache cleared</p>";

// Show opcache status
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    echo "<h2>OpCache Status</h2>";
    echo "<ul>";
    echo "<li><strong>Enabled:</strong> " . ($status['opcache_enabled'] ? 'Yes' : 'No') . "</li>";
    echo "<li><strong>Cache Full:</strong> " . ($status['cache_full'] ? 'Yes' : 'No') . "</li>";
    echo "<li><strong>Cached Scripts:</strong> " . $status['opcache_statistics']['num_cached_scripts'] . "</li>";
    echo "<li><strong>Hits:</strong> " . number_format($status['opcache_statistics']['hits']) . "</li>";
    echo "<li><strong>Misses:</strong> " . number_format($status['opcache_statistics']['misses']) . "</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Cache has been cleared</li>";
echo "<li>Go back to PPMP page and save your PPMP again</li>";
echo "<li>Check if items are now synced to existing LIB</li>";
echo "</ol>";

echo "<p><a href='pages/ppmp.php' style='display: inline-block; padding: 10px 20px; background: #800000; color: white; text-decoration: none; border-radius: 5px;'>← Back to PPMP</a></p>";
echo "<p><a href='pages/lib.php' style='display: inline-block; padding: 10px 20px; background: #800000; color: white; text-decoration: none; border-radius: 5px;'>← Back to LIB</a></p>";
?>
