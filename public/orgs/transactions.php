<?php
/**
 * Organization Transactions
 * 
 * View transaction history for the organization.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../functions/orgs.php';

if (!isset($_SESSION['org_id'])) {
    $_SESSION['org_id'] = 'org_demo_001';
}

$orgId = $_SESSION['org_id'];
$transactions = org_load_transactions($orgId);

// Sort by timestamp descending (newest first)
usort($transactions, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - R-Card</title>
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
                <a href="/public/orgs/cards_list.php" class="nav-item">
                    <span>💳</span>
                    <span>Card Catalog</span>
                </a>
                <a href="/public/orgs/api_keys.php" class="nav-item">
                    <span>🔑</span>
                    <span>API Keys</span>
                </a>
                <a href="/public/orgs/transactions.php" class="nav-item active">
                    <span>📜</span>
                    <span>Transactions</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-4xl font-bold mb-2">Transactions</h1>
                    <p class="text-gray-400">View all payment transactions processed through your organization</p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="glass-card p-6">
                        <div class="text-sm text-gray-400 mb-2">Total Transactions</div>
                        <div class="text-3xl font-bold"><?php echo count($transactions); ?></div>
                    </div>
                    <div class="glass-card p-6">
                        <div class="text-sm text-gray-400 mb-2">Total Volume</div>
                        <div class="text-3xl font-bold">
                            <?php 
                                $totalVolume = array_sum(array_column($transactions, 'amount_credits'));
                                echo number_format($totalVolume);
                            ?> CR
                        </div>
                    </div>
                    <div class="glass-card p-6">
                        <div class="text-sm text-gray-400 mb-2">Approved Rate</div>
                        <div class="text-3xl font-bold text-green-400">
                            <?php 
                                $approved = array_filter($transactions, fn($t) => $t['status'] === 'approved');
                                $rate = count($transactions) > 0 ? (count($approved) / count($transactions)) * 100 : 0;
                                echo number_format($rate, 1);
                            ?>%
                        </div>
                    </div>
                </div>

                <!-- Transactions Table -->
                <?php if (empty($transactions)): ?>
                    <div class="glass-card p-12 text-center">
                        <div class="text-6xl mb-4">📜</div>
                        <h2 class="text-2xl font-bold mb-2">No Transactions Yet</h2>
                        <p class="text-gray-400">Transactions will appear here once you start processing payments</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Date</th>
                                    <th>User ID</th>
                                    <th>Card Identifier</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $txn): ?>
                                    <tr>
                                        <td>
                                            <span class="font-mono text-sm">
                                                <?php echo htmlspecialchars(substr($txn['transaction_id'], 0, 16)); ?>...
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                                $date = new DateTime($txn['timestamp']);
                                                echo $date->format('M j, Y H:i');
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($txn['user_id']); ?></td>
                                        <td>
                                            <span class="font-mono text-sm">
                                                •••• <?php echo htmlspecialchars(substr($txn['card_identifier'], -4)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($txn['description']); ?>">
                                                <?php echo htmlspecialchars($txn['description']); ?>
                                            </div>
                                        </td>
                                        <td class="font-bold">
                                            <?php echo number_format($txn['amount_credits']); ?> CR
                                        </td>
                                        <td>
                                            <?php if ($txn['status'] === 'approved'): ?>
                                                <span class="badge badge-success">Approved</span>
                                            <?php elseif ($txn['status'] === 'declined'): ?>
                                                <span class="badge badge-danger">Declined</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning"><?php echo htmlspecialchars($txn['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
