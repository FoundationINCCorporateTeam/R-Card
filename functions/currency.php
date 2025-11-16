<?php
/**
 * Currency Functions
 * 
 * Currency formatting and conversion utilities.
 */

if (!defined('R_CARD_INIT')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Format credits amount
 * 
 * @param float $amount Amount in credits
 * @return string Formatted string like "1,500 CR"
 */
function r_format_credits(float $amount): string {
    return number_format($amount, 0) . ' CR';
}

/**
 * Format money amount
 * 
 * @param float $amount Amount in currency
 * @param string $currency Currency code (default: USD)
 * @return string Formatted string like "$15.00"
 */
function r_format_money(float $amount, string $currency = 'USD'): string {
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
    ];
    
    $symbol = $symbols[$currency] ?? $currency . ' ';
    
    return $symbol . number_format($amount, 2);
}

/**
 * Convert credits to money
 * 
 * @param float $credits Amount in credits
 * @return float Amount in currency (1 credit = $0.01)
 */
function r_credits_to_money(float $credits): float {
    return $credits * CREDITS_TO_USD_RATE;
}

/**
 * Convert money to credits
 * 
 * @param float $money Amount in currency
 * @return float Amount in credits
 */
function r_money_to_credits(float $money): float {
    return $money / CREDITS_TO_USD_RATE;
}
