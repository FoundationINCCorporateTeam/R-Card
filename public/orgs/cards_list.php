<?php
/**
 * Organization Cards List
 * 
 * List all card types defined by the organization.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../functions/org_cards.php';

if (!isset($_SESSION['org_id'])) {
    $_SESSION['org_id'] = 'org_demo_001';
}

$orgId = $_SESSION['org_id'];
$cards = org_cards_list($orgId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Catalog - R-Card</title>
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
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-4xl font-bold mb-2">Card Catalog</h1>
                        <p class="text-gray-400">Manage your organization's card types</p>
                    </div>
                    <a href="/public/orgs/card_new.php" class="btn btn-primary">
                        + New Card Type
                    </a>
                </div>

                <!-- Cards List -->
                <?php if (empty($cards)): ?>
                    <div class="glass-card p-12 text-center">
                        <div class="text-6xl mb-4">💳</div>
                        <h2 class="text-2xl font-bold mb-2">No Card Types Yet</h2>
                        <p class="text-gray-400 mb-6">Create your first card type to get started</p>
                        <a href="/public/orgs/card_new.php" class="btn btn-primary">
                            + Create Card Type
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($cards as $card): ?>
                            <div class="glass-card p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-xl font-bold"><?php echo htmlspecialchars($card['card_name'] ?? 'Unnamed Card'); ?></h3>
                                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($card['card_type'] ?? 'N/A'); ?></p>
                                    </div>
                                    <span class="badge badge-success">Active</span>
                                </div>

                                <div class="space-y-3 mb-4">
                                    <div>
                                        <div class="text-sm text-gray-400">Public Identifier</div>
                                        <div class="font-mono text-sm"><?php echo htmlspecialchars($card['public_identifier'] ?? 'N/A'); ?></div>
                                    </div>

                                    <?php if (isset($card['loan_policy'])): ?>
                                        <div>
                                            <div class="text-sm text-gray-400">Loan Settings</div>
                                            <div class="text-sm">
                                                <?php if ($card['loan_policy']['loan_enabled']): ?>
                                                    Max: <?php echo number_format($card['loan_policy']['loan_max_amount']); ?> CR
                                                <?php else: ?>
                                                    Disabled
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <div class="text-sm text-gray-400">Created</div>
                                        <div class="text-sm"><?php echo date('M j, Y', strtotime($card['created_at'])); ?></div>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <a href="/public/orgs/card_edit.php?card_id=<?php echo urlencode($card['card_id']); ?>" 
                                       class="btn btn-secondary flex-1">
                                        Edit
                                    </a>
                                    <button onclick="deleteCard('<?php echo htmlspecialchars($card['card_id']); ?>')" 
                                            class="btn btn-danger">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function deleteCard(cardId) {
            if (confirm('Are you sure you want to delete this card type?')) {
                // In production, this would make an API call
                alert('Delete functionality not yet implemented');
            }
        }
    </script>
</body>
</html>
