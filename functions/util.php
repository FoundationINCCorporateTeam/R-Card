<?php
/**
 * Utility Functions
 * 
 * Logging and general utility functions.
 */

if (!defined('R_CARD_INIT')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Log a message to the daily log file
 * 
 * @param string $category Log category (e.g., 'auth', 'api', 'security')
 * @param string $message Log message
 * @param array $context Additional context data
 * @return void
 */
function r_log(string $category, string $message, array $context = []): void {
    if (!LOG_ENABLED) {
        return;
    }
    
    $date = date('Y-m-d');
    $logFile = R_JSON_ROOT . '/logs/' . $date . '.json';
    
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'category' => $category,
        'message' => $message,
        'context' => $context,
    ];
    
    // Read existing logs
    $logs = [];
    if (file_exists($logFile)) {
        $contents = file_get_contents($logFile);
        $logs = json_decode($contents, true) ?? [];
    }
    
    // Append new entry
    $logs[] = $entry;
    
    // Write back
    file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
}
