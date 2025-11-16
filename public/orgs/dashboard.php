<?php
/**
 * Organization Dashboard
 * 
 * Main dashboard for organization management.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../functions/orgs.php';

// For demo purposes, set org session
if (!isset($_SESSION['org_id'])) {
    $_SESSION['org_id'] = 'org_demo_001';
}

$orgId = $_SESSION['org_id'];
$org = org_load($orgId);

// If org doesn't exist, create a demo one
if (empty($org)) {
    $keys = org_generate_api_keys($orgId);
    $org = [
        'org_id' => $orgId,
        'name' => 'Demo Organization',
        'status' => 'active',
        'api_key_public' => $keys['public'],
        'api_key_secret' => $keys['secret'],
        'created_at' => date('Y-m-d H:i:s'),
    ];
    org_save($org);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Dashboard - R-Card</title>
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
                <a href="/public/orgs/dashboard.php" class="nav-item active">
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
                <a href="/public/orgs/transactions.php" class="nav-item">
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
                    <h1 class="text-4xl font-bold mb-2">Dashboard</h1>
                    <p class="text-gray-400">Welcome to <?php echo htmlspecialchars($org['name']); ?></p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="glass-card p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-400">Status</div>
                                <div class="text-2xl font-bold capitalize">
                                    <?php echo htmlspecialchars($org['status']); ?>
                                </div>
                            </div>
                            <div class="text-4xl">✓</div>
                        </div>
                    </div>
                    <div class="glass-card p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-400">Card Types</div>
                                <div class="text-2xl font-bold" id="card-types-count">0</div>
                            </div>
                            <div class="text-4xl">💳</div>
                        </div>
                    </div>
                    <div class="glass-card p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-400">Transactions</div>
                                <div class="text-2xl font-bold" id="transactions-count">0</div>
                            </div>
                            <div class="text-4xl">📊</div>
                        </div>
                    </div>
                    <div class="glass-card p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-400">API Status</div>
                                <div class="text-2xl font-bold text-green-400">Active</div>
                            </div>
                            <div class="text-4xl">🔌</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="glass-card p-6 mb-8">
                    <h2 class="text-2xl font-bold mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="/public/orgs/card_new.php" class="btn btn-primary">
                            + Create New Card Type
                        </a>
                        <a href="/public/orgs/api_keys.php" class="btn btn-secondary">
                            🔑 View API Keys
                        </a>
                        <a href="/public/orgs/transactions.php" class="btn btn-secondary">
                            📜 View Transactions
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="glass-card p-6">
                    <h2 class="text-2xl font-bold mb-4">Recent Activity</h2>
                    <div id="recent-activity">
                        <p class="text-gray-400 text-center py-8">No recent activity</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Load stats
        async function loadStats() {
            try {
                // Load card types count
                const orgId = <?php echo json_encode($orgId); ?>;
                
                // This would normally be an API call, but for now we'll use placeholder values
                document.getElementById('card-types-count').textContent = '0';
                document.getElementById('transactions-count').textContent = '0';
            } catch (error) {
                console.error('Stats load error:', error);
            }
        }

        document.addEventListener('DOMContentLoaded', loadStats);
    </script>
</body>
</html>
