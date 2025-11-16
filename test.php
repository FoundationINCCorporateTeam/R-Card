<?php
/**
 * Test Script for Core Functionality
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions/encryption.php';
require_once __DIR__ . '/functions/loans.php';
require_once __DIR__ . '/functions/currency.php';

echo "R-Card Core Functionality Tests\n";
echo "================================\n\n";

// Test 1: Encryption/Decryption
echo "Test 1: Encryption/Decryption\n";
$testData = [
    'card_number' => '1234567890123456',
    'cvv' => '123',
    'pin' => '9876'
];

$encrypted = r_encrypt($testData);
echo "  Encrypted (IV): " . substr($encrypted['iv'], 0, 20) . "...\n";
echo "  Encrypted (Blob): " . substr($encrypted['blob'], 0, 20) . "...\n";

$decrypted = r_decrypt($encrypted);
if ($decrypted === $testData) {
    echo "  ✓ Encryption/Decryption PASSED\n";
} else {
    echo "  ✗ Encryption/Decryption FAILED\n";
}

echo "\n";

// Test 2: Loan Interest Calculation
echo "Test 2: Loan Interest Calculation\n";
$amount = 1000;
$days = 30;
$monthlyRate = 2.0; // 2% per month
$minWaitDays = 7;

$interest = r_calculate_loan_interest($amount, $days, $monthlyRate, $minWaitDays);
echo "  Principal: {$amount} CR\n";
echo "  Duration: {$days} days\n";
echo "  Monthly Rate: {$monthlyRate}%\n";
echo "  Daily Rate: " . number_format($interest['daily_rate'] * 100, 4) . "%\n";
echo "  Interest (30 days): {$interest['interest_selected']} CR\n";
echo "  Interest (min 7 days): {$interest['interest_min_wait']} CR\n";
echo "  Total Due: {$interest['total_due']} CR\n";

// Verify calculation
$expectedDailyRate = ($monthlyRate / 100) / 30;
$expectedInterest = $amount * $expectedDailyRate * $days;
$expectedTotal = $amount + $expectedInterest;

if (abs($interest['interest_selected'] - round($expectedInterest, 2)) < 0.01 &&
    abs($interest['total_due'] - round($expectedTotal, 2)) < 0.01) {
    echo "  ✓ Interest Calculation PASSED\n";
} else {
    echo "  ✗ Interest Calculation FAILED\n";
    echo "    Expected Interest: " . round($expectedInterest, 2) . "\n";
    echo "    Expected Total: " . round($expectedTotal, 2) . "\n";
}

echo "\n";

// Test 3: Currency Formatting
echo "Test 3: Currency Formatting\n";
$credits = 12345.67;
$formatted = r_format_credits($credits);
echo "  Credits: {$credits}\n";
echo "  Formatted: {$formatted}\n";
echo "  Expected: 12,346 CR\n";

if ($formatted === '12,346 CR') {
    echo "  ✓ Currency Formatting PASSED\n";
} else {
    echo "  ✗ Currency Formatting FAILED\n";
}

echo "\n";

// Test 4: Money Conversion
echo "Test 4: Money Conversion\n";
$credits = 1000;
$money = r_credits_to_money($credits);
$backToCredits = r_money_to_credits($money);

echo "  1000 CR = \$" . number_format($money, 2) . "\n";
echo "  \$10.00 = {$backToCredits} CR\n";

if ($money === 10.0 && $backToCredits === 1000.0) {
    echo "  ✓ Money Conversion PASSED\n";
} else {
    echo "  ✗ Money Conversion FAILED\n";
}

echo "\n";

// Test 5: Percentage to Float
echo "Test 5: Percentage Conversion\n";
$percent1 = r_percent_to_float("2.5%");
$percent2 = r_percent_to_float(3.0);

echo "  '2.5%' -> {$percent1}\n";
echo "  3.0 -> {$percent2}\n";

if ($percent1 === 2.5 && $percent2 === 3.0) {
    echo "  ✓ Percentage Conversion PASSED\n";
} else {
    echo "  ✗ Percentage Conversion FAILED\n";
}

echo "\n";
echo "================================\n";
echo "All tests completed!\n";
