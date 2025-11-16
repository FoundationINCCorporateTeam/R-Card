<?php
/**
 * Create New Organization Card Type
 * 
 * Form for creating new card specifications.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../functions/org_cards.php';

if (!isset($_SESSION['org_id'])) {
    $_SESSION['org_id'] = 'org_demo_001';
}

$orgId = $_SESSION['org_id'];
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardData = [
        'card_name' => $_POST['card_name'] ?? '',
        'card_type' => $_POST['card_type'] ?? 'debit',
        'public_identifier' => $_POST['public_identifier'] ?? '',
        'description' => $_POST['description'] ?? '',
        'loan_policy' => [
            'loan_enabled' => isset($_POST['loan_enabled']),
            'loan_max_amount' => (float) ($_POST['loan_max_amount'] ?? 0),
            'loan_max_year' => (float) ($_POST['loan_max_year'] ?? 0),
            'loan_interest_rate_monthly' => (float) ($_POST['loan_interest_rate_monthly'] ?? 0),
            'loan_min_wait_days' => (int) ($_POST['loan_min_wait_days'] ?? 7),
            'loan_max_days' => (int) ($_POST['loan_max_days'] ?? 90),
        ],
    ];

    if ($_POST['card_type'] === 'credit') {
        $cardData['credit_limit'] = (float) ($_POST['credit_limit'] ?? 0);
        $cardData['interest_rate_monthly'] = (float) ($_POST['interest_rate_monthly'] ?? 0);
    }

    $cardId = org_cards_create($orgId, $cardData);
    
    header('Location: /public/orgs/cards_list.php?created=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Card Type - R-Card</title>
    <link rel="stylesheet" href="/public/css/app.css">
</head>
<body>
    <div class="flex min-h-screen">
        <!-- Sidebar Navigation -->
        <aside class="sidebar w-64">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-gradient">R-Card</h1>
                <p class="text-sm text-gray-400 mt-1">Organization Portal</p>
            </div>
            <nav class="mt-6">
                <a href="/public/orgs/dashboard.php" class="nav-item">
                    <span>📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="/public/orgs/cards_list.php" class="nav-item active">
                    <span>💳</span>
                    <span>Card Catalog</span>
                </a>
                <a href="/public/orgs/api_keys.php" class="nav-item">
                    <span>🔑</span>
                    <span>API Keys</span>
                </a>
                <a href="/public/orgs/transactions.php" class="nav-item">
                    <span>📜</span>
                    <span>Transactions</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <div class="max-w-4xl mx-auto">
                <!-- Header -->
                <div class="mb-8">
                    <a href="/public/orgs/cards_list.php" class="btn btn-secondary mb-4">
                        ← Back to Cards
                    </a>
                    <h1 class="text-4xl font-bold mb-2">Create New Card Type</h1>
                    <p class="text-gray-400">Define a new card specification for your organization</p>
                </div>

                <!-- Form -->
                <form method="POST" class="space-y-6">
                    <!-- Basic Information -->
                    <div class="glass-card p-6">
                        <h2 class="text-2xl font-bold mb-4">Basic Information</h2>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Card Name *</label>
                                <input type="text" name="card_name" class="input-field" required
                                       placeholder="e.g., Gold Premium Card">
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Card Type *</label>
                                <select name="card_type" id="card_type" class="select-field" required>
                                    <option value="debit">Debit</option>
                                    <option value="credit">Credit</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Public Identifier *</label>
                                <input type="text" name="public_identifier" class="input-field" required
                                       placeholder="e.g., gold-premium">
                                <p class="text-sm text-gray-400 mt-1">Used in API calls to identify this card type</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Description</label>
                                <textarea name="description" class="input-field" rows="3"
                                          placeholder="Card description and features"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Credit Card Settings (shown only for credit cards) -->
                    <div id="credit-settings" class="glass-card p-6 hidden">
                        <h2 class="text-2xl font-bold mb-4">Credit Card Settings</h2>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Credit Limit (CR)</label>
                                <input type="number" name="credit_limit" class="input-field" 
                                       min="0" step="100" value="5000">
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Monthly Interest Rate (%)</label>
                                <input type="number" name="interest_rate_monthly" class="input-field" 
                                       min="0" max="100" step="0.1" value="1.5">
                            </div>
                        </div>
                    </div>

                    <!-- Loan Policy -->
                    <div class="glass-card p-6">
                        <h2 class="text-2xl font-bold mb-4">Loan Policy</h2>
                        
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" name="loan_enabled" id="loan_enabled" 
                                       class="w-5 h-5">
                                <label for="loan_enabled" class="font-medium">Enable Loans</label>
                            </div>

                            <div id="loan-settings" class="space-y-4 hidden">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium mb-2">Max Loan Amount (CR)</label>
                                        <input type="number" name="loan_max_amount" class="input-field" 
                                               min="0" step="100" value="2000">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-2">Yearly Loan Limit (CR)</label>
                                        <input type="number" name="loan_max_year" class="input-field" 
                                               min="0" step="100" value="5000">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-2">Monthly Interest Rate (%)</label>
                                        <input type="number" name="loan_interest_rate_monthly" class="input-field" 
                                               min="0" max="100" step="0.1" value="2.0">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-2">Min Wait Days</label>
                                        <input type="number" name="loan_min_wait_days" class="input-field" 
                                               min="1" max="365" value="7">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-2">Max Loan Duration (Days)</label>
                                        <input type="number" name="loan_max_days" class="input-field" 
                                               min="1" max="180" value="90">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex gap-4">
                        <button type="submit" class="btn btn-primary flex-1">
                            Create Card Type
                        </button>
                        <a href="/public/orgs/cards_list.php" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Show/hide credit settings based on card type
        document.getElementById('card_type').addEventListener('change', function() {
            const creditSettings = document.getElementById('credit-settings');
            if (this.value === 'credit') {
                creditSettings.classList.remove('hidden');
            } else {
                creditSettings.classList.add('hidden');
            }
        });

        // Show/hide loan settings based on checkbox
        document.getElementById('loan_enabled').addEventListener('change', function() {
            const loanSettings = document.getElementById('loan-settings');
            if (this.checked) {
                loanSettings.classList.remove('hidden');
            } else {
                loanSettings.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
