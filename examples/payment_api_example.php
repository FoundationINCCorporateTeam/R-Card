<?php
/**
 * Example: Using the R-Card Payment API
 * 
 * This script demonstrates how to make a payment request to the R-Card system
 * from an external organization.
 */

// Configuration (replace with your actual values)
$apiKeyPublic = 'pk_47318e8eef5f2452aec410c48fe7e074';  // From org setup
$apiKeySecret = 'sk_1c0eb59bef825231e7fc735d5c886fd5deebeb5dfeb1f6d5bc8685ba776c6253';  // From org setup
$apiEndpoint = 'http://localhost/api/payment.php';

// Payment details
$userId = 1;
$cardIdentifier = 'RCARD-0001-9612';  // From sample user cards
$amountCredits = 100;
$description = 'Game item purchase - Legendary Sword';

// Create payment request
function createPaymentRequest($apiKeyPublic, $apiKeySecret, $userId, $cardIdentifier, $amountCredits, $description) {
    // Generate unique nonce (should never be reused)
    $nonce = 'nonce_' . uniqid() . '_' . bin2hex(random_bytes(8));
    
    // Current timestamp
    $timestamp = time();
    
    // Build payload (without signature)
    $payload = [
        'api_key_public' => $apiKeyPublic,
        'nonce' => $nonce,
        'timestamp' => $timestamp,
        'operation' => 'charge',
        'card_identifier' => $cardIdentifier,
        'user_id' => $userId,
        'amount_credits' => $amountCredits,
        'description' => $description,
    ];
    
    // Sort keys alphabetically for signature
    ksort($payload);
    
    // Create signature
    $payloadJson = json_encode($payload);
    $signature = hash_hmac('sha256', $payloadJson, $apiKeySecret);
    
    // Add signature to payload
    $payload['signature'] = $signature;
    
    return $payload;
}

// Execute payment
echo "R-Card Payment API Example\n";
echo "==========================\n\n";

echo "Creating payment request...\n";
$payload = createPaymentRequest($apiKeyPublic, $apiKeySecret, $userId, $cardIdentifier, $amountCredits, $description);

echo "Request Details:\n";
echo "  User ID: {$userId}\n";
echo "  Card: {$cardIdentifier}\n";
echo "  Amount: {$amountCredits} CR\n";
echo "  Description: {$description}\n";
echo "  Nonce: {$payload['nonce']}\n";
echo "  Timestamp: {$payload['timestamp']}\n";
echo "  Signature: " . substr($payload['signature'], 0, 32) . "...\n\n";

echo "Payload JSON:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n";

echo "\n";
echo "To test this, send the above JSON to: {$apiEndpoint}\n";
