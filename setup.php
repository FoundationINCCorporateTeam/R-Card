<?php
/**
 * R-Card System Setup & Initialization
 * 
 * Run this script once to initialize the system with sample data.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions/encryption.php';
require_once __DIR__ . '/functions/file_storage.php';
require_once __DIR__ . '/functions/orgs.php';
require_once __DIR__ . '/functions/org_cards.php';
require_once __DIR__ . '/functions/user_cards.php';
require_once __DIR__ . '/functions/loans.php';

echo "R-Card System Initialization\n";
echo "============================\n\n";

// Create sample user
$userId = 1;
echo "Creating sample user (ID: {$userId})...\n";

// Create sample cards for user
$sampleCards = [
    [
        'card_id' => 'card_' . uniqid(),
        'card_identifier' => 'RCARD-0001-' . rand(1000, 9999),
        'card_type' => 'credit',
        'card_name' => 'Standard Credit',
        'current_balance' => -500, // Negative for credit (owed)
        'credit_limit' => 5000,
        'status' => 'active',
        'issued_date' => date('Y-m-d H:i:s', strtotime('-6 months')),
        'expiry_date' => date('Y-m-d H:i:s', strtotime('+2 years')),
        'payload' => [] // Encrypted card details would go here
    ],
    [
        'card_id' => 'card_' . uniqid(),
        'card_identifier' => 'RCARD-0002-' . rand(1000, 9999),
        'card_type' => 'debit',
        'card_name' => 'Premium Debit',
        'current_balance' => 10000,
        'status' => 'active',
        'issued_date' => date('Y-m-d H:i:s', strtotime('-1 year')),
        'expiry_date' => date('Y-m-d H:i:s', strtotime('+3 years')),
        'payload' => []
    ],
];

r_save_user_cards($userId, $sampleCards);
echo "✓ Created " . count($sampleCards) . " sample cards for user\n";

// Display card information
foreach ($sampleCards as $card) {
    echo "  - {$card['card_name']} ({$card['card_identifier']})\n";
    echo "    Balance: " . number_format($card['current_balance']) . " CR\n";
}

echo "\n";

// Create sample organization
$orgId = 'org_demo_001';
echo "Creating sample organization (ID: {$orgId})...\n";

$org = org_load($orgId);
if (empty($org)) {
    $keys = org_generate_api_keys($orgId);
    $org = [
        'org_id' => $orgId,
        'name' => 'Demo Game Studio',
        'status' => 'active',
        'api_key_public' => $keys['public'],
        'api_key_secret' => $keys['secret'],
        'created_at' => date('Y-m-d H:i:s'),
    ];
    org_save($org);
    echo "✓ Organization created\n";
    echo "  Public Key: {$org['api_key_public']}\n";
    echo "  Secret Key: {$org['api_key_secret']}\n";
} else {
    echo "✓ Organization already exists\n";
}

echo "\n";

// Create sample organization card
echo "Creating sample organization card type...\n";

$existingCards = org_cards_list($orgId);
if (empty($existingCards)) {
    $orgCardData = [
        'card_name' => 'Game Pass Premium',
        'card_type' => 'credit',
        'public_identifier' => 'game-pass-premium',
        'description' => 'Premium game pass card with exclusive benefits',
        'credit_limit' => 10000,
        'interest_rate_monthly' => 1.2,
        'loan_policy' => [
            'loan_enabled' => true,
            'loan_max_amount' => 3000,
            'loan_max_year' => 10000,
            'loan_interest_rate_monthly' => 1.8,
            'loan_min_wait_days' => 7,
            'loan_max_days' => 120,
        ],
    ];
    
    $cardId = org_cards_create($orgId, $orgCardData);
    echo "✓ Organization card created (ID: {$cardId})\n";
} else {
    echo "✓ Organization already has card types\n";
}

echo "\n";

// System ready
echo "============================\n";
echo "System initialization complete!\n\n";
echo "Next steps:\n";
echo "1. Access the user portal at: /public/card_management.php\n";
echo "2. Access the org portal at: /public/orgs/dashboard.php\n";
echo "3. View a card detail at: /public/card_view.php?card_id={$sampleCards[0]['card_id']}\n";
echo "\n";
echo "Sample credentials:\n";
echo "- User ID: {$userId}\n";
echo "- Card 1: {$sampleCards[0]['card_identifier']}\n";
echo "- Card 2: {$sampleCards[1]['card_identifier']}\n";
echo "- Org ID: {$orgId}\n";
echo "\n";

// Test encryption
echo "Testing encryption...\n";
$testData = ['test' => 'data', 'value' => 12345];
$encrypted = r_encrypt($testData);
$decrypted = r_decrypt($encrypted);
if ($decrypted === $testData) {
    echo "✓ Encryption/decryption working correctly\n";
} else {
    echo "✗ Encryption/decryption test failed\n";
}

echo "\n";
echo "All systems ready! 🚀\n";
