<?php
/**
 * Main API Router
 * 
 * Handles authenticated API requests for card and loan management.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions/util.php';
require_once __DIR__ . '/functions/user_cards.php';
require_once __DIR__ . '/functions/loans.php';
require_once __DIR__ . '/functions/cards.php';
require_once __DIR__ . '/functions/currency.php';

// Set JSON header
header('Content-Type: application/json');

// Check session authentication
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'loans_bootstrap':
            handleLoansBootstrap($userId);
            break;
            
        case 'loans_preview':
            handleLoansPreview($userId);
            break;
            
        case 'loans_create':
            handleLoansCreate($userId);
            break;
            
        case 'loans_list':
            handleLoansList($userId);
            break;
            
        case 'loans_repay':
            handleLoansRepay($userId);
            break;
            
        case 'cards_list':
            handleCardsList($userId);
            break;
            
        case 'card_details':
            handleCardDetails($userId);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    r_log('api_error', 'API error: ' . $e->getMessage(), ['user_id' => $userId, 'action' => $action]);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}

/**
 * Handle loans bootstrap request
 */
function handleLoansBootstrap($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['card_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing card_id']);
        return;
    }
    
    $cardId = $input['card_id'];
    $cardRow = r_get_user_card_row($userId, $cardId, null);
    
    if ($cardRow === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Card not found']);
        return;
    }
    
    // Decrypt payload
    $payload = r_decrypt_payload_row($cardRow);
    
    // Build loan policy
    global $cards;
    $policy = r_build_loan_policy($cards, $cardRow, $payload);
    
    if (!$policy['loan_enabled']) {
        echo json_encode([
            'success' => true,
            'loan_enabled' => false,
            'message' => 'Loans not available for this card',
        ]);
        return;
    }
    
    // Calculate year-to-date loans
    $yearTotal = r_sum_loans_year($userId, $cardId);
    $remainingYear = max(0, $policy['loan_max_year'] - $yearTotal);
    
    echo json_encode([
        'success' => true,
        'loan_enabled' => true,
        'policy' => [
            'max_amount' => $policy['loan_max_amount'],
            'max_year' => $policy['loan_max_year'],
            'interest_rate_monthly' => $policy['loan_interest_rate_monthly'],
            'min_wait_days' => $policy['loan_min_wait_days'],
            'max_days' => $policy['loan_max_days'],
            'year_total' => $yearTotal,
            'remaining_year' => $remainingYear,
        ],
    ]);
}

/**
 * Handle loans preview request
 */
function handleLoansPreview($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['card_id']) || !isset($input['amount']) || !isset($input['days'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        return;
    }
    
    $cardId = $input['card_id'];
    $amount = (float) $input['amount'];
    $days = (int) $input['days'];
    
    $cardRow = r_get_user_card_row($userId, $cardId, null);
    
    if ($cardRow === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Card not found']);
        return;
    }
    
    // Decrypt payload
    $payload = r_decrypt_payload_row($cardRow);
    
    // Build loan policy
    global $cards;
    $policy = r_build_loan_policy($cards, $cardRow, $payload);
    
    if (!$policy['loan_enabled']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Loans not enabled for this card']);
        return;
    }
    
    // Validate amount
    if ($amount < LOAN_MIN_AMOUNT) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Amount below minimum']);
        return;
    }
    
    if ($amount > $policy['loan_max_amount']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Amount exceeds card limit']);
        return;
    }
    
    // Check year limit
    $yearTotal = r_sum_loans_year($userId, $cardId);
    if ($yearTotal + $amount > $policy['loan_max_year']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Amount exceeds yearly limit']);
        return;
    }
    
    // Validate days
    if ($days < 1 || $days > $policy['loan_max_days']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid loan duration']);
        return;
    }
    
    // Calculate interest
    $interest = r_calculate_loan_interest(
        $amount,
        $days,
        $policy['loan_interest_rate_monthly'],
        $policy['loan_min_wait_days']
    );
    
    echo json_encode([
        'success' => true,
        'preview' => [
            'amount' => $amount,
            'days' => $days,
            'interest_rate_monthly' => $policy['loan_interest_rate_monthly'],
            'daily_rate' => $interest['daily_rate'],
            'interest_min_wait' => $interest['interest_min_wait'],
            'interest_selected' => $interest['interest_selected'],
            'total_due' => $interest['total_due'],
            'min_wait_days' => $policy['loan_min_wait_days'],
        ],
    ]);
}

/**
 * Handle loans create request
 */
function handleLoansCreate($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['card_id']) || !isset($input['amount']) || !isset($input['days'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        return;
    }
    
    $cardId = $input['card_id'];
    $amount = (float) $input['amount'];
    $days = (int) $input['days'];
    
    $cardRow = r_get_user_card_row($userId, $cardId, null);
    
    if ($cardRow === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Card not found']);
        return;
    }
    
    // Decrypt payload
    $payload = r_decrypt_payload_row($cardRow);
    
    // Build loan policy
    global $cards;
    $policy = r_build_loan_policy($cards, $cardRow, $payload);
    
    if (!$policy['loan_enabled']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Loans not enabled for this card']);
        return;
    }
    
    // Validate amount
    if ($amount < LOAN_MIN_AMOUNT) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Amount below minimum']);
        return;
    }
    
    if ($amount > $policy['loan_max_amount']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Amount exceeds card limit']);
        return;
    }
    
    // Check year limit
    $yearTotal = r_sum_loans_year($userId, $cardId);
    if ($yearTotal + $amount > $policy['loan_max_year']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Amount exceeds yearly limit']);
        return;
    }
    
    // Validate days
    if ($days < 1 || $days > $policy['loan_max_days']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid loan duration']);
        return;
    }
    
    // Create loan
    $loanId = r_create_loan($userId, $cardId, $amount, $days, $policy);
    
    // Add credits to card balance
    $cards = r_load_user_cards($userId);
    foreach ($cards as $index => $card) {
        if ($card['card_id'] === $cardId) {
            $cards[$index]['current_balance'] = ($card['current_balance'] ?? 0) + $amount;
            break;
        }
    }
    r_save_user_cards($userId, $cards);
    
    echo json_encode([
        'success' => true,
        'loan_id' => $loanId,
        'message' => 'Loan created successfully',
    ]);
}

/**
 * Handle loans list request
 */
function handleLoansList($userId) {
    $loans = r_list_loans($userId);
    
    echo json_encode([
        'success' => true,
        'loans' => $loans,
    ]);
}

/**
 * Handle loans repay request
 */
function handleLoansRepay($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['loan_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing loan_id']);
        return;
    }
    
    $loanId = $input['loan_id'];
    $source = $input['source'] ?? 'card_balance';
    
    $success = r_repay_loan($userId, $loanId, $source);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Loan repaid successfully',
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Unable to repay loan',
        ]);
    }
}

/**
 * Handle cards list request
 */
function handleCardsList($userId) {
    $cards = r_load_user_cards($userId);
    
    // Format cards for response
    $formattedCards = array_map(function($card) {
        return [
            'card_id' => $card['card_id'] ?? '',
            'card_identifier' => $card['card_identifier'] ?? '',
            'card_type' => $card['card_type'] ?? '',
            'card_name' => $card['card_name'] ?? '',
            'current_balance' => $card['current_balance'] ?? 0,
            'credit_limit' => $card['credit_limit'] ?? 0,
            'status' => $card['status'] ?? 'active',
            'expiry_date' => $card['expiry_date'] ?? null,
        ];
    }, $cards);
    
    echo json_encode([
        'success' => true,
        'cards' => $formattedCards,
    ]);
}

/**
 * Handle card details request
 */
function handleCardDetails($userId) {
    $cardId = $_GET['card_id'] ?? null;
    $cardIdentifier = $_GET['card_identifier'] ?? null;
    
    if (!$cardId && !$cardIdentifier) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing card identifier']);
        return;
    }
    
    $cardRow = r_get_user_card_row($userId, $cardId, $cardIdentifier);
    
    if ($cardRow === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Card not found']);
        return;
    }
    
    // Don't expose sensitive payload data
    $safeCard = [
        'card_id' => $cardRow['card_id'] ?? '',
        'card_identifier' => $cardRow['card_identifier'] ?? '',
        'card_type' => $cardRow['card_type'] ?? '',
        'card_name' => $cardRow['card_name'] ?? '',
        'current_balance' => $cardRow['current_balance'] ?? 0,
        'credit_limit' => $cardRow['credit_limit'] ?? 0,
        'status' => $cardRow['status'] ?? 'active',
        'expiry_date' => $cardRow['expiry_date'] ?? null,
        'issued_date' => $cardRow['issued_date'] ?? null,
    ];
    
    echo json_encode([
        'success' => true,
        'card' => $safeCard,
    ]);
}
