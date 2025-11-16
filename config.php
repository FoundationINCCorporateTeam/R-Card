<?php
/**
 * R-Card Configuration
 * 
 * Core configuration file for the R-Card virtual card system.
 * Defines paths, encryption keys, and system constants.
 */

// Prevent direct access
if (!defined('R_CARD_INIT')) {
    define('R_CARD_INIT', true);
}

// JSON data root directory
define('R_JSON_ROOT', __DIR__ . '/jsondata');

// Encryption key for JSON file storage (AES-256-CBC requires 32 bytes)
define('R_JSON_KEY', 'put-32-char-secret-here-change-me-please!');

// Separate encryption key for per-card payloads (32 bytes)
define('ENCRYPTION_KEY', 'card-payload-encryption-key-32ch');

// Session configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('SESSION_NAME', 'R_CARD_SESSION');

// Currency settings
define('CREDITS_TO_USD_RATE', 0.01); // 1 credit = $0.01
define('DEFAULT_CURRENCY', 'USD');

// Loan settings
define('LOAN_MIN_AMOUNT', 100); // Minimum loan amount in credits
define('LOAN_MAX_DAYS', 180); // Maximum loan duration
define('LOAN_MIN_WAIT_DAYS', 7); // Minimum wait period for early payoff

// Organization API settings
define('ORG_MAX_TIME_DRIFT', 15); // Maximum time drift in seconds
define('ORG_NONCE_EXPIRY', 300); // Nonce expiry in seconds (5 minutes)

// Logging
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// Error handling
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Ensure JSON data directories exist
$jsonDirs = [
    R_JSON_ROOT,
    R_JSON_ROOT . '/users',
    R_JSON_ROOT . '/user_cards',
    R_JSON_ROOT . '/loans',
    R_JSON_ROOT . '/orgs',
    R_JSON_ROOT . '/org_cards',
    R_JSON_ROOT . '/org_transactions',
    R_JSON_ROOT . '/org_nonces',
    R_JSON_ROOT . '/logs',
    R_JSON_ROOT . '/cards',
    R_JSON_ROOT . '/benefits',
    R_JSON_ROOT . '/settings',
];

foreach ($jsonDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Initialize base cards catalog if it doesn't exist
$baseCardsPath = R_JSON_ROOT . '/cards/base_cards.json';
if (!file_exists($baseCardsPath)) {
    $baseCards = [
        'credit' => [
            'Standard Credit' => [
                'type' => 'credit',
                'name' => 'Standard Credit',
                'credit_limit' => 5000,
                'interest_rate_monthly' => 1.5,
                'loan_enabled' => true,
                'loan_max_amount' => 2000,
                'loan_max_year' => 5000,
                'loan_interest_rate_monthly' => 2.0,
                'loan_min_wait_days' => 7,
                'loan_max_days' => 90,
            ],
            'Premium Credit' => [
                'type' => 'credit',
                'name' => 'Premium Credit',
                'credit_limit' => 15000,
                'interest_rate_monthly' => 1.2,
                'loan_enabled' => true,
                'loan_max_amount' => 5000,
                'loan_max_year' => 15000,
                'loan_interest_rate_monthly' => 1.8,
                'loan_min_wait_days' => 7,
                'loan_max_days' => 120,
            ],
            'Elite Credit' => [
                'type' => 'credit',
                'name' => 'Elite Credit',
                'credit_limit' => 50000,
                'interest_rate_monthly' => 0.9,
                'loan_enabled' => true,
                'loan_max_amount' => 15000,
                'loan_max_year' => 40000,
                'loan_interest_rate_monthly' => 1.5,
                'loan_min_wait_days' => 7,
                'loan_max_days' => 180,
            ],
        ],
        'debit' => [
            'Basic Debit' => [
                'type' => 'debit',
                'name' => 'Basic Debit',
                'loan_enabled' => false,
            ],
            'Premium Debit' => [
                'type' => 'debit',
                'name' => 'Premium Debit',
                'loan_enabled' => true,
                'loan_max_amount' => 1000,
                'loan_max_year' => 3000,
                'loan_interest_rate_monthly' => 2.5,
                'loan_min_wait_days' => 7,
                'loan_max_days' => 60,
            ],
        ],
    ];
    file_put_contents($baseCardsPath, json_encode($baseCards, JSON_PRETTY_PRINT));
}

// Initialize benefits HTML if it doesn't exist
$benefitsPath = R_JSON_ROOT . '/benefits/all_cards.html';
if (!file_exists($benefitsPath)) {
    $benefitsHtml = <<<HTML
<!-- Standard Credit Benefits -->
<div id="benefits-standard-credit" class="benefits-section">
    <h3 class="text-xl font-semibold mb-3">Standard Credit Card Benefits</h3>
    <ul class="list-disc list-inside space-y-2">
        <li>5,000 CR credit limit</li>
        <li>1.5% monthly interest rate on balances</li>
        <li>Loan access up to 2,000 CR per transaction</li>
        <li>Annual loan limit of 5,000 CR</li>
        <li>Early payoff option after 7 days</li>
    </ul>
</div>

<!-- Premium Credit Benefits -->
<div id="benefits-premium-credit" class="benefits-section">
    <h3 class="text-xl font-semibold mb-3">Premium Credit Card Benefits</h3>
    <ul class="list-disc list-inside space-y-2">
        <li>15,000 CR credit limit</li>
        <li>1.2% monthly interest rate on balances</li>
        <li>Loan access up to 5,000 CR per transaction</li>
        <li>Annual loan limit of 15,000 CR</li>
        <li>Extended loan terms up to 120 days</li>
        <li>Priority customer support</li>
    </ul>
</div>

<!-- Elite Credit Benefits -->
<div id="benefits-elite-credit" class="benefits-section">
    <h3 class="text-xl font-semibold mb-3">Elite Credit Card Benefits</h3>
    <ul class="list-disc list-inside space-y-2">
        <li>50,000 CR credit limit</li>
        <li>0.9% monthly interest rate on balances</li>
        <li>Loan access up to 15,000 CR per transaction</li>
        <li>Annual loan limit of 40,000 CR</li>
        <li>Extended loan terms up to 180 days</li>
        <li>VIP customer support</li>
        <li>Exclusive rewards and cashback</li>
    </ul>
</div>

<!-- Basic Debit Benefits -->
<div id="benefits-basic-debit" class="benefits-section">
    <h3 class="text-xl font-semibold mb-3">Basic Debit Card Benefits</h3>
    <ul class="list-disc list-inside space-y-2">
        <li>Direct access to your credits</li>
        <li>No interest charges</li>
        <li>No credit checks required</li>
        <li>Instant balance updates</li>
    </ul>
</div>

<!-- Premium Debit Benefits -->
<div id="benefits-premium-debit" class="benefits-section">
    <h3 class="text-xl font-semibold mb-3">Premium Debit Card Benefits</h3>
    <ul class="list-disc list-inside space-y-2">
        <li>Direct access to your credits</li>
        <li>No interest charges on regular transactions</li>
        <li>Loan access up to 1,000 CR</li>
        <li>Annual loan limit of 3,000 CR</li>
        <li>Short-term loan terms up to 60 days</li>
        <li>Enhanced fraud protection</li>
    </ul>
</div>
HTML;
    file_put_contents($benefitsPath, $benefitsHtml);
}
