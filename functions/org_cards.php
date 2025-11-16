<?php
/**
 * Organization Cards Functions
 * 
 * Helpers for managing organization card specifications.
 */

if (!defined('R_CARD_INIT')) {
    require_once __DIR__ . '/../config.php';
}

require_once __DIR__ . '/file_storage.php';

/**
 * List all cards for an organization
 * 
 * @param string $org_id Organization ID
 * @return array Array of card specifications
 */
function org_cards_list(string $org_id): array {
    $orgCardsDir = R_JSON_ROOT . "/org_cards/{$org_id}";
    
    if (!is_dir($orgCardsDir)) {
        return [];
    }
    
    $files = glob($orgCardsDir . '/*.json');
    $cards = [];
    
    foreach ($files as $file) {
        $card = r_json_read($file);
        if (!empty($card)) {
            $cards[] = $card;
        }
    }
    
    return $cards;
}

/**
 * Create a new organization card
 * 
 * @param string $org_id Organization ID
 * @param array $cardData Card specification data
 * @return string Card ID
 */
function org_cards_create(string $org_id, array $cardData): string {
    $cardId = 'card_' . uniqid() . '_' . time();
    $cardData['card_id'] = $cardId;
    $cardData['org_id'] = $org_id;
    $cardData['created_at'] = date('Y-m-d H:i:s');
    
    $path = "org_cards/{$org_id}/{$cardId}.json";
    r_json_write($path, $cardData, true);
    
    return $cardId;
}

/**
 * Update an organization card
 * 
 * @param string $org_id Organization ID
 * @param string $card_id Card ID
 * @param array $cardData Updated card data
 * @return bool Success status
 */
function org_cards_update(string $org_id, string $card_id, array $cardData): bool {
    $cardData['card_id'] = $card_id;
    $cardData['org_id'] = $org_id;
    $cardData['updated_at'] = date('Y-m-d H:i:s');
    
    $path = "org_cards/{$org_id}/{$card_id}.json";
    return r_json_write($path, $cardData, true);
}

/**
 * Delete an organization card
 * 
 * @param string $org_id Organization ID
 * @param string $card_id Card ID
 * @return bool Success status
 */
function org_cards_delete(string $org_id, string $card_id): bool {
    $path = R_JSON_ROOT . "/org_cards/{$org_id}/{$card_id}.json";
    
    if (file_exists($path)) {
        return unlink($path);
    }
    
    return false;
}
