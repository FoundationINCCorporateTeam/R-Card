<?php
/**
 * Loans Functions
 * 
 * Loan creation, calculation, and repayment management.
 */

if (!defined('R_CARD_INIT')) {
    require_once __DIR__ . '/../config.php';
}

require_once __DIR__ . '/file_storage.php';
require_once __DIR__ . '/cards.php';
require_once __DIR__ . '/user_cards.php';
require_once __DIR__ . '/util.php';

/**
 * Load user loans
 * 
 * @param int $user_id User ID
 * @return array Array of loans
 */
function r_load_user_loans(int $user_id): array {
    $path = "loans/{$user_id}.json";
    $data = r_json_read($path);
    
    return $data['loans'] ?? [];
}

/**
 * Save user loans
 * 
 * @param int $user_id User ID
 * @param array $loans Array of loan data
 * @return bool Success status
 */
function r_save_user_loans(int $user_id, array $loans): bool {
    $data = ['loans' => $loans];
    $path = "loans/{$user_id}.json";
    
    return r_json_write($path, $data, true);
}

/**
 * Convert percentage string to float
 * 
 * @param mixed $v Value (string or numeric)
 * @return float Percentage as float
 */
function r_percent_to_float($v): float {
    if (is_string($v)) {
        $v = str_replace('%', '', $v);
    }
    return (float) $v;
}

/**
 * Build loan policy for a card
 * 
 * @param array $cardsCatalog Base cards catalog
 * @param array $userCardRow User's card row
 * @param array $payload Decrypted card payload
 * @return array Loan policy
 */
function r_build_loan_policy(array $cardsCatalog, array $userCardRow, array $payload): array {
    $cardType = $userCardRow['card_type'] ?? 'debit';
    $cardName = $userCardRow['card_name'] ?? '';
    
    // Check if this is an org card
    if (isset($userCardRow['org_id']) && !empty($userCardRow['org_id'])) {
        $orgId = $userCardRow['org_id'];
        $orgCardId = $userCardRow['org_card_id'] ?? '';
        
        if ($orgCardId) {
            $orgCard = r_load_org_card($orgId, $orgCardId);
            
            if (!empty($orgCard) && isset($orgCard['loan_policy'])) {
                return $orgCard['loan_policy'];
            }
        }
    }
    
    // Fallback to base card policy
    $basePolicy = r_lookup_base_card_policy($cardType, $cardName);
    
    if (empty($basePolicy)) {
        return [
            'loan_enabled' => false,
            'loan_max_amount' => 0,
            'loan_max_year' => 0,
            'loan_interest_rate_monthly' => 0,
            'loan_min_wait_days' => LOAN_MIN_WAIT_DAYS,
            'loan_max_days' => LOAN_MAX_DAYS,
        ];
    }
    
    return [
        'loan_enabled' => $basePolicy['loan_enabled'] ?? false,
        'loan_max_amount' => $basePolicy['loan_max_amount'] ?? 0,
        'loan_max_year' => $basePolicy['loan_max_year'] ?? 0,
        'loan_interest_rate_monthly' => r_percent_to_float($basePolicy['loan_interest_rate_monthly'] ?? 0),
        'loan_min_wait_days' => $basePolicy['loan_min_wait_days'] ?? LOAN_MIN_WAIT_DAYS,
        'loan_max_days' => $basePolicy['loan_max_days'] ?? LOAN_MAX_DAYS,
    ];
}

/**
 * Sum loans for a user and card for the current year
 * 
 * @param int $user_id User ID
 * @param string $card_id Card ID
 * @return float Total loan amount for the year
 */
function r_sum_loans_year(int $user_id, string $card_id): float {
    $loans = r_load_user_loans($user_id);
    $currentYear = date('Y');
    $total = 0;
    
    foreach ($loans as $loan) {
        if ($loan['card_id'] !== $card_id) {
            continue;
        }
        
        if ($loan['status'] === 'paid') {
            continue;
        }
        
        $loanYear = date('Y', strtotime($loan['created_at']));
        
        if ($loanYear === $currentYear) {
            $total += $loan['amount'];
        }
    }
    
    return $total;
}

/**
 * Calculate loan interest
 * 
 * @param float $amount Loan amount
 * @param int $days Loan duration in days
 * @param float $monthlyRate Monthly interest rate (percentage)
 * @param int $minWaitDays Minimum wait days for early payoff
 * @return array Interest calculation details
 */
function r_calculate_loan_interest(float $amount, int $days, float $monthlyRate, int $minWaitDays): array {
    // Calculate daily rate from monthly rate
    $dailyRate = ($monthlyRate / 100) / 30;
    
    // Interest for minimum wait period
    $interestMinWait = $amount * $dailyRate * $minWaitDays;
    
    // Interest for selected duration
    $interestSelected = $amount * $dailyRate * $days;
    
    // Total amount due
    $totalDue = $amount + $interestSelected;
    
    return [
        'daily_rate' => $dailyRate,
        'interest_min_wait' => round($interestMinWait, 2),
        'interest_selected' => round($interestSelected, 2),
        'total_due' => round($totalDue, 2),
    ];
}

/**
 * Create a new loan
 * 
 * @param int $user_id User ID
 * @param string $card_id Card ID
 * @param float $amount Loan amount
 * @param int $days Loan duration in days
 * @param array $policy Loan policy
 * @return string Loan ID
 */
function r_create_loan(int $user_id, string $card_id, float $amount, int $days, array $policy): string {
    $loanId = 'loan_' . uniqid() . '_' . time();
    
    $interest = r_calculate_loan_interest(
        $amount,
        $days,
        $policy['loan_interest_rate_monthly'],
        $policy['loan_min_wait_days']
    );
    
    $loan = [
        'loan_id' => $loanId,
        'card_id' => $card_id,
        'amount' => $amount,
        'days' => $days,
        'interest_rate_monthly' => $policy['loan_interest_rate_monthly'],
        'interest_amount' => $interest['interest_selected'],
        'total_due' => $interest['total_due'],
        'min_wait_days' => $policy['loan_min_wait_days'],
        'created_at' => date('Y-m-d H:i:s'),
        'due_date' => date('Y-m-d H:i:s', strtotime("+{$days} days")),
        'status' => 'active',
        'paid_at' => null,
    ];
    
    $loans = r_load_user_loans($user_id);
    $loans[] = $loan;
    r_save_user_loans($user_id, $loans);
    
    r_log('loans', 'Loan created', [
        'user_id' => $user_id,
        'loan_id' => $loanId,
        'amount' => $amount,
        'days' => $days,
    ]);
    
    return $loanId;
}

/**
 * List all loans for a user
 * 
 * @param int $user_id User ID
 * @return array Array of loans
 */
function r_list_loans(int $user_id): array {
    return r_load_user_loans($user_id);
}

/**
 * Repay a loan
 * 
 * @param int $user_id User ID
 * @param string $loan_id Loan ID
 * @param string $source Payment source (e.g., 'card_balance')
 * @return bool Success status
 */
function r_repay_loan(int $user_id, string $loan_id, string $source): bool {
    $loans = r_load_user_loans($user_id);
    $loanIndex = -1;
    $loan = null;
    
    foreach ($loans as $index => $l) {
        if ($l['loan_id'] === $loan_id) {
            $loanIndex = $index;
            $loan = $l;
            break;
        }
    }
    
    if ($loan === null || $loan['status'] === 'paid') {
        return false;
    }
    
    // Calculate days elapsed
    $createdTime = strtotime($loan['created_at']);
    $daysElapsed = (int) floor((time() - $createdTime) / 86400);
    
    // Recalculate interest based on actual days elapsed
    $minWaitDays = $loan['min_wait_days'];
    $daysToCharge = max($daysElapsed, $minWaitDays);
    
    $interest = r_calculate_loan_interest(
        $loan['amount'],
        $daysToCharge,
        $loan['interest_rate_monthly'],
        $minWaitDays
    );
    
    $totalDue = $interest['total_due'];
    
    // Load user cards to update balance
    $cards = r_load_user_cards($user_id);
    $cardIndex = -1;
    
    foreach ($cards as $index => $card) {
        if ($card['card_id'] === $loan['card_id']) {
            $cardIndex = $index;
            break;
        }
    }
    
    if ($cardIndex === -1) {
        return false;
    }
    
    // Deduct from card balance
    $cards[$cardIndex]['current_balance'] = ($cards[$cardIndex]['current_balance'] ?? 0) - $totalDue;
    r_save_user_cards($user_id, $cards);
    
    // Mark loan as paid
    $loans[$loanIndex]['status'] = 'paid';
    $loans[$loanIndex]['paid_at'] = date('Y-m-d H:i:s');
    $loans[$loanIndex]['actual_interest'] = $interest['interest_selected'];
    $loans[$loanIndex]['actual_total_paid'] = $totalDue;
    $loans[$loanIndex]['days_elapsed'] = $daysElapsed;
    
    r_save_user_loans($user_id, $loans);
    
    r_log('loans', 'Loan repaid', [
        'user_id' => $user_id,
        'loan_id' => $loan_id,
        'amount_paid' => $totalDue,
        'days_elapsed' => $daysElapsed,
    ]);
    
    return true;
}
