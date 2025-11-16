<?php
/**
 * Encryption Functions
 * 
 * Provides AES-256-CBC encryption and decryption for sensitive data.
 */

if (!defined('R_CARD_INIT')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Encrypt data using AES-256-CBC
 * 
 * @param array $data Data to encrypt
 * @return array Array with 'iv' and 'blob' keys
 */
function r_encrypt(array $data): array {
    $key = R_JSON_KEY;
    
    // Generate random IV
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = openssl_random_pseudo_bytes($ivLength);
    
    // Serialize and encrypt data
    $json = json_encode($data);
    $encrypted = openssl_encrypt($json, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    
    return [
        'iv' => base64_encode($iv),
        'blob' => base64_encode($encrypted)
    ];
}

/**
 * Decrypt data using AES-256-CBC
 * 
 * @param array $payload Array with 'iv' and 'blob' keys
 * @return array Decrypted data or empty array on failure
 */
function r_decrypt(array $payload): array {
    if (!isset($payload['iv']) || !isset($payload['blob'])) {
        return [];
    }
    
    $key = R_JSON_KEY;
    
    try {
        $iv = base64_decode($payload['iv']);
        $encrypted = base64_decode($payload['blob']);
        
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        
        if ($decrypted === false) {
            return [];
        }
        
        $data = json_decode($decrypted, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        
        return is_array($data) ? $data : [];
    } catch (Exception $e) {
        error_log("Decryption error: " . $e->getMessage());
        return [];
    }
}
