<?php
/**
 * Cards Functions
 * 
 * Card catalog and policy management.
 */

if (!defined('R_CARD_INIT')) {
    require_once __DIR__ . '/../config.php';
}

require_once __DIR__ . '/file_storage.php';

// Global cards catalog (credit and debit entries)
$cards = [
    'credit' => [
        'Standard Credit',
        'Premium Credit',
        'Elite Credit',
    ],
    'debit' => [
        'Basic Debit',
        'Premium Debit',
    ],
];

/**
 * Lookup base card policy from catalog
 * 
 * @param string $card_type Type of card ('credit' or 'debit')
 * @param string $nameOrTier Card name or tier
 * @return array Standardized policy array
 */
function r_lookup_base_card_policy(string $card_type, string $nameOrTier): array {
    $baseCards = r_json_read('cards/base_cards.json');
    
    if (!isset($baseCards[$card_type][$nameOrTier])) {
        return [];
    }
    
    return $baseCards[$card_type][$nameOrTier];
}

/**
 * Load organization card specification
 * 
 * @param string $org_id Organization ID
 * @param string $card_id Card ID
 * @return array Card specification or empty array
 */
function r_load_org_card(string $org_id, string $card_id): array {
    $path = "org_cards/{$org_id}/{$card_id}.json";
    return r_json_read($path);
}

/**
 * Find organization card by public identifier
 * 
 * @param string $org_id Organization ID
 * @param string $public_identifier Public card identifier
 * @return array Card specification or empty array
 */
function r_find_org_card_by_public_id(string $org_id, string $public_identifier): array {
    $orgCardsDir = R_JSON_ROOT . "/org_cards/{$org_id}";
    
    if (!is_dir($orgCardsDir)) {
        return [];
    }
    
    $files = glob($orgCardsDir . '/*.json');
    
    foreach ($files as $file) {
        $card = r_json_read($file);
        
        if (isset($card['public_identifier']) && $card['public_identifier'] === $public_identifier) {
            return $card;
        }
    }
    
    return [];
}
