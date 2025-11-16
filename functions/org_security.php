<?php
/**
 * Organization Security Functions
 * 
 * Security validation for organization API requests.
 */

if (!defined('R_CARD_INIT')) {
    require_once __DIR__ . '/../config.php';
}

require_once __DIR__ . '/file_storage.php';

// Maximum time drift allowed for timestamps (in seconds)
define('ORG_MAX_TIME_DRIFT', 15);

/**
 * Verify HMAC-SHA256 signature
 * 
 * @param string $secret Secret key
 * @param string $payload Payload to verify
 * @param string $givenSig Signature to verify against
 * @return bool True if signature is valid
 */
function org_verify_signature(string $secret, string $payload, string $givenSig): bool {
    $computedSig = hash_hmac('sha256', $payload, $secret);
    return hash_equals($computedSig, $givenSig);
}

/**
 * Validate timestamp is within acceptable drift
 * 
 * @param int|string $timestamp Unix timestamp
 * @return bool True if timestamp is valid
 */
function org_validate_timestamp($timestamp): bool {
    $timestamp = (int) $timestamp;
    $now = time();
    $diff = abs($now - $timestamp);
    
    return $diff <= ORG_MAX_TIME_DRIFT;
}

/**
 * Check if nonce has been used
 * 
 * @param string $nonce Nonce to check
 * @return bool True if nonce has been used
 */
function org_nonce_used(string $nonce): bool {
    $noncesFile = R_JSON_ROOT . '/org_nonces/nonces.json';
    
    if (!file_exists($noncesFile)) {
        return false;
    }
    
    $data = json_decode(file_get_contents($noncesFile), true) ?? [];
    $nonces = $data['nonces'] ?? [];
    
    // Clean up expired nonces (older than ORG_NONCE_EXPIRY)
    $expiryTime = time() - ORG_NONCE_EXPIRY;
    $nonces = array_filter($nonces, function($item) use ($expiryTime) {
        return $item['timestamp'] > $expiryTime;
    });
    
    // Check if nonce exists
    foreach ($nonces as $item) {
        if ($item['nonce'] === $nonce) {
            return true;
        }
    }
    
    return false;
}

/**
 * Mark nonce as used
 * 
 * @param string $nonce Nonce to mark as used
 * @return void
 */
function org_mark_nonce_used(string $nonce): void {
    $noncesFile = R_JSON_ROOT . '/org_nonces/nonces.json';
    
    $data = [];
    if (file_exists($noncesFile)) {
        $data = json_decode(file_get_contents($noncesFile), true) ?? [];
    }
    
    $nonces = $data['nonces'] ?? [];
    
    // Clean up expired nonces
    $expiryTime = time() - ORG_NONCE_EXPIRY;
    $nonces = array_filter($nonces, function($item) use ($expiryTime) {
        return $item['timestamp'] > $expiryTime;
    });
    
    // Add new nonce
    $nonces[] = [
        'nonce' => $nonce,
        'timestamp' => time(),
    ];
    
    $data['nonces'] = array_values($nonces);
    
    file_put_contents($noncesFile, json_encode($data, JSON_PRETTY_PRINT));
}
