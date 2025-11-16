<?php
/**
 * File Storage Functions
 * 
 * Handles reading and writing JSON files with optional encryption.
 */

if (!defined('R_CARD_INIT')) {
    require_once __DIR__ . '/../config.php';
}

require_once __DIR__ . '/encryption.php';

/**
 * Read JSON data from file
 * 
 * @param string $path Absolute or relative path to JSON file
 * @return array Decoded data or empty array on failure
 */
function r_json_read(string $path): array {
    // Convert relative path to absolute if needed
    if (!str_starts_with($path, '/')) {
        $path = R_JSON_ROOT . '/' . $path;
    }
    
    if (!file_exists($path)) {
        return [];
    }
    
    $contents = file_get_contents($path);
    if ($contents === false) {
        return [];
    }
    
    $data = json_decode($contents, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [];
    }
    
    // Check if data is encrypted (has 'iv' and 'blob' keys)
    if (isset($data['iv']) && isset($data['blob']) && count($data) === 2) {
        return r_decrypt($data);
    }
    
    return is_array($data) ? $data : [];
}

/**
 * Write JSON data to file
 * 
 * @param string $path Absolute or relative path to JSON file
 * @param array $data Data to write
 * @param bool $encrypt Whether to encrypt the data (default: true)
 * @return bool Success status
 */
function r_json_write(string $path, array $data, bool $encrypt = true): bool {
    // Convert relative path to absolute if needed
    if (!str_starts_with($path, '/')) {
        $path = R_JSON_ROOT . '/' . $path;
    }
    
    // Ensure directory exists
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Encrypt if requested
    if ($encrypt) {
        $data = r_encrypt($data);
    }
    
    $json = json_encode($data, JSON_PRETTY_PRINT);
    
    return file_put_contents($path, $json) !== false;
}
