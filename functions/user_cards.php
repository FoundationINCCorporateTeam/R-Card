<?php
/**
 * User Cards Functions
 * 
 * User card management and access control.
 */

if (!defined('R_CARD_INIT')) {
    require_once __DIR__ . '/../config.php';
}

require_once __DIR__ . '/file_storage.php';
require_once __DIR__ . '/encryption.php';

/**
 * Load user cards
 * 
 * @param int $user_id User ID
 * @return array Array of user cards
 */
function r_load_user_cards(int $user_id): array {
    $path = "user_cards/{$user_id}.json";
    $data = r_json_read($path);
    
    return $data['cards'] ?? [];
}

/**
 * Save user cards
 * 
 * @param int $user_id User ID
 * @param array $cards Array of card data
 * @return bool Success status
 */
function r_save_user_cards(int $user_id, array $cards): bool {
    $data = ['cards' => $cards];
    $path = "user_cards/{$user_id}.json";
    
    return r_json_write($path, $data, true);
}

/**
 * Get user card row with validation
 * 
 * @param int $user_id User ID
 * @param string|null $card_id Card ID (optional)
 * @param string|null $card_identifier Card identifier (optional)
 * @return array|null Card row or null if not found/invalid
 */
function r_get_user_card_row(int $user_id, ?string $card_id, ?string $card_identifier): ?array {
    $cards = r_load_user_cards($user_id);
    
    $matchingCard = null;
    
    foreach ($cards as $card) {
        if ($card_id !== null && isset($card['card_id']) && $card['card_id'] === $card_id) {
            $matchingCard = $card;
            break;
        }
        
        if ($card_identifier !== null && isset($card['card_identifier']) && $card['card_identifier'] === $card_identifier) {
            $matchingCard = $card;
            break;
        }
    }
    
    if ($matchingCard === null) {
        return null;
    }
    
    // Check if card is expired
    if (isset($matchingCard['expiry_date'])) {
        $expiryTime = strtotime($matchingCard['expiry_date']);
        if ($expiryTime !== false && time() > $expiryTime) {
            return null; // Card expired
        }
    }
    
    // Check if card is stolen
    if (isset($matchingCard['status']) && $matchingCard['status'] === 'stolen') {
        return null;
    }
    
    // Check if card is blocked
    if (isset($matchingCard['status']) && $matchingCard['status'] === 'blocked') {
        return null;
    }
    
    return $matchingCard;
}

/**
 * Decrypt card payload row using ENCRYPTION_KEY
 * 
 * @param array $row Card row with encrypted payload
 * @return array Decrypted payload data
 */
function r_decrypt_payload_row(array $row): array {
    if (!isset($row['payload']) || !is_array($row['payload'])) {
        return [];
    }
    
    $payload = $row['payload'];
    
    // Check if payload is encrypted
    if (!isset($payload['iv']) || !isset($payload['blob'])) {
        return $payload; // Not encrypted, return as-is
    }
    
    // Use ENCRYPTION_KEY instead of R_JSON_KEY
    $key = ENCRYPTION_KEY;
    
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
        error_log("Payload decryption error: " . $e->getMessage());
        return [];
    }
}
