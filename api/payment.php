<?php
/**
 * Payment API for External Organizations
 * 
 * Secure payment processing endpoint for organization partners.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions/util.php';
require_once __DIR__ . '/../functions/orgs.php';
require_once __DIR__ . '/../functions/org_security.php';
require_once __DIR__ . '/../functions/user_cards.php';
require_once __DIR__ . '/../functions/cards.php';

// Set JSON header
header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'rejected', 'reason' => 'Method not allowed']);
    exit;
}

// Parse JSON input
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['status' => 'rejected', 'reason' => 'Invalid JSON']);
    exit;
}

try {
    // Validate required fields
    $requiredFields = ['api_key_public', 'nonce', 'timestamp', 'signature', 'operation', 'card_identifier', 'user_id', 'amount_credits', 'description'];
    
    foreach ($requiredFields as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode(['status' => 'rejected', 'reason' => "Missing field: {$field}"]);
            exit;
        }
    }
    
    $apiKeyPublic = $input['api_key_public'];
    $nonce = $input['nonce'];
    $timestamp = $input['timestamp'];
    $givenSignature = $input['signature'];
    $operation = $input['operation'];
    $cardIdentifier = $input['card_identifier'];
    $userId = (int) $input['user_id'];
    $amountCredits = (float) $input['amount_credits'];
    $description = $input['description'];
    
    // Find organization by API key
    $org = org_find_by_apikey($apiKeyPublic);
    
    if ($org === null) {
        r_log('payment_api', 'Invalid API key', ['api_key' => $apiKeyPublic]);
        http_response_code(401);
        echo json_encode(['status' => 'rejected', 'reason' => 'Invalid API key']);
        exit;
    }
    
    // Check organization status
    if (isset($org['status']) && $org['status'] !== 'active') {
        r_log('payment_api', 'Inactive organization', ['org_id' => $org['org_id']]);
        http_response_code(403);
        echo json_encode(['status' => 'rejected', 'reason' => 'Organization not active']);
        exit;
    }
    
    $orgId = $org['org_id'];
    $apiKeySecret = $org['api_key_secret'] ?? '';
    
    // Validate timestamp
    if (!org_validate_timestamp($timestamp)) {
        r_log('payment_api', 'Invalid timestamp', ['org_id' => $orgId, 'timestamp' => $timestamp]);
        http_response_code(400);
        echo json_encode(['status' => 'rejected', 'reason' => 'Invalid timestamp']);
        exit;
    }
    
    // Check nonce
    if (org_nonce_used($nonce)) {
        r_log('payment_api', 'Nonce already used', ['org_id' => $orgId, 'nonce' => $nonce]);
        http_response_code(400);
        echo json_encode(['status' => 'rejected', 'reason' => 'Nonce already used']);
        exit;
    }
    
    // Verify signature
    // Create payload without signature for verification
    $inputWithoutSig = $input;
    unset($inputWithoutSig['signature']);
    ksort($inputWithoutSig);
    $payloadToVerify = json_encode($inputWithoutSig);
    
    if (!org_verify_signature($apiKeySecret, $payloadToVerify, $givenSignature)) {
        r_log('payment_api', 'Invalid signature', ['org_id' => $orgId]);
        http_response_code(401);
        echo json_encode(['status' => 'rejected', 'reason' => 'Invalid signature']);
        exit;
    }
    
    // Mark nonce as used
    org_mark_nonce_used($nonce);
    
    // Validate operation
    if ($operation !== 'charge') {
        http_response_code(400);
        echo json_encode(['status' => 'rejected', 'reason' => 'Invalid operation']);
        exit;
    }
    
    // Validate amount
    if ($amountCredits <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'declined', 'reason' => 'Invalid amount']);
        exit;
    }
    
    // Get user card
    $cardRow = r_get_user_card_row($userId, null, $cardIdentifier);
    
    if ($cardRow === null) {
        r_log('payment_api', 'Card not found', ['org_id' => $orgId, 'user_id' => $userId, 'card_identifier' => $cardIdentifier]);
        http_response_code(404);
        echo json_encode(['status' => 'declined', 'reason' => 'Card not found or invalid']);
        exit;
    }
    
    $cardId = $cardRow['card_id'];
    $cardType = $cardRow['card_type'] ?? 'debit';
    $currentBalance = $cardRow['current_balance'] ?? 0;
    $creditLimit = $cardRow['credit_limit'] ?? 0;
    
    // Check if this is a credit or debit card
    if ($cardType === 'credit') {
        // For credit cards, check if charge would exceed credit limit
        $availableCredit = $creditLimit - abs($currentBalance);
        
        if ($amountCredits > $availableCredit) {
            r_log('payment_api', 'Credit limit exceeded', [
                'org_id' => $orgId,
                'user_id' => $userId,
                'card_id' => $cardId,
                'amount' => $amountCredits,
                'available_credit' => $availableCredit,
            ]);
            
            echo json_encode([
                'status' => 'declined',
                'reason' => 'Credit limit exceeded',
                'available_credit' => $availableCredit,
            ]);
            exit;
        }
        
        // Charge credit card (increase negative balance)
        $newBalance = $currentBalance - $amountCredits;
    } else {
        // For debit cards, check if sufficient balance
        if ($amountCredits > $currentBalance) {
            r_log('payment_api', 'Insufficient funds', [
                'org_id' => $orgId,
                'user_id' => $userId,
                'card_id' => $cardId,
                'amount' => $amountCredits,
                'balance' => $currentBalance,
            ]);
            
            echo json_encode([
                'status' => 'declined',
                'reason' => 'Insufficient funds',
                'available_balance' => $currentBalance,
            ]);
            exit;
        }
        
        // Charge debit card (decrease balance)
        $newBalance = $currentBalance - $amountCredits;
    }
    
    // Update card balance
    $cards = r_load_user_cards($userId);
    $updated = false;
    
    foreach ($cards as $index => $card) {
        if ($card['card_id'] === $cardId) {
            $cards[$index]['current_balance'] = $newBalance;
            $updated = true;
            break;
        }
    }
    
    if (!$updated) {
        http_response_code(500);
        echo json_encode(['status' => 'rejected', 'reason' => 'Unable to update balance']);
        exit;
    }
    
    r_save_user_cards($userId, $cards);
    
    // Create transaction record
    $transactionId = 'txn_' . uniqid() . '_' . time();
    
    $transaction = [
        'transaction_id' => $transactionId,
        'org_id' => $orgId,
        'user_id' => $userId,
        'card_id' => $cardId,
        'card_identifier' => $cardIdentifier,
        'operation' => $operation,
        'amount_credits' => $amountCredits,
        'description' => $description,
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'approved',
    ];
    
    // Save to org transactions
    $transactions = org_load_transactions($orgId);
    $transactions[] = $transaction;
    org_save_transactions($orgId, $transactions);
    
    // Log successful transaction
    r_log('payment_api', 'Transaction approved', [
        'org_id' => $orgId,
        'transaction_id' => $transactionId,
        'user_id' => $userId,
        'card_id' => $cardId,
        'amount' => $amountCredits,
    ]);
    
    // Return success
    echo json_encode([
        'status' => 'approved',
        'transaction_id' => $transactionId,
        'amount_charged' => $amountCredits,
        'new_balance' => $newBalance,
    ]);
    
} catch (Exception $e) {
    r_log('payment_api_error', 'Payment API error: ' . $e->getMessage(), [
        'input' => $input ?? null,
    ]);
    
    http_response_code(500);
    echo json_encode(['status' => 'rejected', 'reason' => 'Internal server error']);
}
