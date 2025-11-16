<?php
/**
 * Organizations Functions
 * 
 * Organization management and API key handling.
 */

if (!defined('R_CARD_INIT')) {
    require_once __DIR__ . '/../config.php';
}

require_once __DIR__ . '/file_storage.php';

/**
 * Load organization data
 * 
 * @param string $org_id Organization ID
 * @return array Organization data or empty array
 */
function org_load(string $org_id): array {
    $path = "orgs/{$org_id}.json";
    return r_json_read($path);
}

/**
 * Save organization data
 * 
 * @param array $org Organization data
 * @return bool Success status
 */
function org_save(array $org): bool {
    if (!isset($org['org_id'])) {
        return false;
    }
    
    $path = "orgs/{$org['org_id']}.json";
    return r_json_write($path, $org, true);
}

/**
 * Find organization by public API key
 * 
 * @param string $public Public API key
 * @return array|null Organization data or null if not found
 */
function org_find_by_apikey(string $public): ?array {
    $orgsDir = R_JSON_ROOT . '/orgs';
    
    if (!is_dir($orgsDir)) {
        return null;
    }
    
    $files = glob($orgsDir . '/*.json');
    
    foreach ($files as $file) {
        $org = r_json_read($file);
        
        if (isset($org['api_key_public']) && $org['api_key_public'] === $public) {
            return $org;
        }
    }
    
    return null;
}

/**
 * Generate API keys for organization
 * 
 * @param string $org_id Organization ID
 * @return array Array with 'public' and 'secret' keys
 */
function org_generate_api_keys(string $org_id): array {
    $public = 'pk_' . bin2hex(random_bytes(16));
    $secret = 'sk_' . bin2hex(random_bytes(32));
    
    return [
        'public' => $public,
        'secret' => $secret,
    ];
}

/**
 * Load organization transactions
 * 
 * @param string $org_id Organization ID
 * @return array Array of transactions
 */
function org_load_transactions(string $org_id): array {
    $path = "org_transactions/{$org_id}.json";
    $data = r_json_read($path);
    
    return $data['transactions'] ?? [];
}

/**
 * Save organization transactions
 * 
 * @param string $org_id Organization ID
 * @param array $txns Array of transactions
 * @return bool Success status
 */
function org_save_transactions(string $org_id, array $txns): bool {
    $data = ['transactions' => $txns];
    $path = "org_transactions/{$org_id}.json";
    
    return r_json_write($path, $data, false); // Don't encrypt transactions
}
