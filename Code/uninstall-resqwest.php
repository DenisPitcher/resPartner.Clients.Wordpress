<?php
/**
 * Manual Uninstall Script for resQwest Plugin
 * 
 * IMPORTANT: This script should be run ONCE to clean up plugin data, then deleted.
 * 
 * Usage:
 * 1. Upload this file to your WordPress root directory (same level as wp-config.php)
 * 2. Access it via browser: https://yoursite.com/uninstall-resqwest.php
 * 3. Delete this file immediately after running
 * 
 * WARNING: This will permanently delete plugin data. Backup your database first!
 */

// Security check - only allow if accessed directly
if (!defined('ABSPATH')) {
    // Load WordPress
    require_once('wp-load.php');
}

// Additional security - require admin login
if (!current_user_can('manage_options')) {
    die('Access denied. You must be logged in as an administrator.');
}

// Check if this is a POST request (confirmation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_uninstall'])) {
    
    global $wpdb;
    $deleted_items = array();
    
    // 1. Clear scheduled cron hook
    $cron_hook = 'resQwest_loadInventory_hook';
    $cron_cleared = wp_clear_scheduled_hook($cron_hook);
    $deleted_items[] = "Cleared cron hook: {$cron_hook}";
    
    // 2. Delete plugin options
    $options_deleted = delete_option('resQwest_options');
    $deleted_items[] = "Deleted plugin options: " . ($options_deleted ? 'Yes' : 'No');
    
    // 3. Delete transients
    $transient_deleted = delete_transient('resQwestAccessToken');
    $deleted_items[] = "Deleted security token transient: " . ($transient_deleted ? 'Yes' : 'No');
    
    // 4. Delete all resQwest post meta (optional - comment out if you want to keep the data)
    $meta_keys_to_delete = array(
        '_resQwest_enabled',
        '_resQwest_route',
        'resQwest-inventoryId',
        'resQwest-categoryId',
        'inventory-name',
        'inventory-shortDescription',
        'inventory-description',
        'inventory-duration',
        'inventory-operates',
        'inventory-checkin',
        'inventory-cost',
        'inventory-cancelPolicy',
        'inventory-restrictions',
        'inventory-bookingNotes',
        'inventory-location',
        '_resQwest_lastUpdate',
        '_resQwest_lastUpdateStatus',
        '_resQwest_lastError',
        '_resQwest_apiResponseCode',
        '_resQwest_apiResponseTime',
    );
    
    $meta_deleted_count = 0;
    foreach ($meta_keys_to_delete as $meta_key) {
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s",
            $meta_key
        ));
        $meta_deleted_count += $deleted;
    }
    $deleted_items[] = "Deleted {$meta_deleted_count} post meta entries";
    
    // Display results
    echo '<div style="max-width: 800px; margin: 50px auto; padding: 20px; background: #fff; border: 1px solid #ccc;">';
    echo '<h1>resQwest Plugin Uninstall Complete</h1>';
    echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 4px;">';
    echo '<h2>Cleanup Results:</h2>';
    echo '<ul>';
    foreach ($deleted_items as $item) {
        echo '<li>' . esc_html($item) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
    echo '<p><strong>Next Steps:</strong></p>';
    echo '<ol>';
    echo '<li>Delete the plugin folder from <code>wp-content/plugins/</code></li>';
    echo '<li><strong style="color: red;">DELETE THIS FILE (uninstall-resqwest.php) immediately!</strong></li>';
    echo '</ol>';
    echo '</div>';
    
} else {
    // Show confirmation page
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>resQwest Plugin Uninstall</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
            .warning { background: #fff3cd; border: 2px solid #ffc107; padding: 20px; margin: 20px 0; border-radius: 4px; }
            .danger { background: #f8d7da; border: 2px solid #dc3545; padding: 20px; margin: 20px 0; border-radius: 4px; }
            button { background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
            button:hover { background: #c82333; }
            .info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 20px 0; border-radius: 4px; }
        </style>
    </head>
    <body>
        <h1>resQwest Plugin Uninstall</h1>
        
        <div class="danger">
            <h2>⚠️ WARNING</h2>
            <p><strong>This will permanently delete all resQwest plugin data from your database!</strong></p>
            <ul>
                <li>Plugin options and settings</li>
                <li>All inventory post meta data</li>
                <li>Scheduled cron jobs</li>
                <li>Security tokens</li>
            </ul>
            <p><strong>This action cannot be undone!</strong></p>
        </div>
        
        <div class="info">
            <h3>What will be deleted:</h3>
            <ul>
                <li>Cron hook: <code>resQwest_loadInventory_hook</code></li>
                <li>Options: <code>resQwest_options</code></li>
                <li>Transients: <code>resQwestAccessToken</code></li>
                <li>All post meta fields starting with <code>_resQwest_</code>, <code>resQwest-</code>, and <code>inventory-</code></li>
            </ul>
        </div>
        
        <div class="warning">
            <p><strong>Before proceeding:</strong></p>
            <ol>
                <li>Backup your database</li>
                <li>Delete the plugin folder from <code>wp-content/plugins/</code> first (or do it after)</li>
                <li>Make sure you really want to delete all this data</li>
            </ol>
        </div>
        
        <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete all resQwest plugin data? This cannot be undone!');">
            <input type="hidden" name="confirm_uninstall" value="1">
            <button type="submit">Yes, Delete All Plugin Data</button>
        </form>
        
        <p style="margin-top: 30px; color: #666;">
            <small>If you don't want to delete the data, just close this page and manually delete the plugin folder.</small>
        </p>
    </body>
    </html>
    <?php
}
