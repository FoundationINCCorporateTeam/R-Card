<?php
/**
 * Organization API Keys Management
 * 
 * View and regenerate API keys for the organization.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../functions/orgs.php';

if (!isset($_SESSION['org_id'])) {
    $_SESSION['org_id'] = 'org_demo_001';
}

$orgId = $_SESSION['org_id'];
$org = org_load($orgId);

// Handle key regeneration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regenerate'])) {
    $keys = org_generate_api_keys($orgId);
    $org['api_key_public'] = $keys['public'];
    $org['api_key_secret'] = $keys['secret'];
    org_save($org);
    
    $message = 'API keys regenerated successfully. Please update your applications with the new keys.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Keys - R-Card</title>
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
                <a href="/public/orgs/api_keys.php" class="nav-item active">
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
                    <h1 class="text-4xl font-bold mb-2">API Keys</h1>
                    <p class="text-gray-400">Manage your organization's API credentials</p>
                </div>

                <?php if (isset($message)): ?>
                    <div class="alert alert-success mb-6">
                        <span>✓</span>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Warning -->
                <div class="alert alert-info mb-6">
                    <span>ℹ️</span>
                    <span>Keep your secret key secure. Never share it publicly or commit it to version control.</span>
                </div>

                <!-- Public Key -->
                <div class="glass-card p-6 mb-6">
                    <h2 class="text-xl font-bold mb-4">Public API Key</h2>
                    <p class="text-sm text-gray-400 mb-4">Use this key to identify your organization in API requests</p>
                    
                    <div class="bg-white/5 p-4 rounded-lg font-mono text-sm break-all">
                        <?php echo htmlspecialchars($org['api_key_public'] ?? 'Not generated'); ?>
                    </div>
                    
                    <button onclick="copyToClipboard('<?php echo htmlspecialchars($org['api_key_public'] ?? ''); ?>')" 
                            class="btn btn-secondary mt-4">
                        📋 Copy to Clipboard
                    </button>
                </div>

                <!-- Secret Key -->
                <div class="glass-card p-6 mb-6">
                    <h2 class="text-xl font-bold mb-4">Secret API Key</h2>
                    <p class="text-sm text-gray-400 mb-4">Use this key to sign API requests (never share publicly)</p>
                    
                    <div class="bg-white/5 p-4 rounded-lg font-mono text-sm break-all relative">
                        <span id="secret-key" class="blur-sm select-none">
                            <?php echo htmlspecialchars($org['api_key_secret'] ?? 'Not generated'); ?>
                        </span>
                        <button onclick="toggleSecretVisibility()" 
                                class="absolute top-2 right-2 btn btn-secondary btn-sm">
                            👁️ Show
                        </button>
                    </div>
                    
                    <button onclick="copyToClipboard('<?php echo htmlspecialchars($org['api_key_secret'] ?? ''); ?>')" 
                            class="btn btn-secondary mt-4">
                        📋 Copy to Clipboard
                    </button>
                </div>

                <!-- API Documentation -->
                <div class="glass-card p-6 mb-6">
                    <h2 class="text-xl font-bold mb-4">API Usage Example</h2>
                    <p class="text-sm text-gray-400 mb-4">Example request to the Payment API</p>
                    
                    <pre class="bg-black/50 p-4 rounded-lg overflow-x-auto text-sm"><code>{
  "api_key_public": "<?php echo htmlspecialchars($org['api_key_public'] ?? 'your_public_key'); ?>",
  "nonce": "unique-nonce-<?php echo time(); ?>",
  "timestamp": <?php echo time(); ?>,
  "operation": "charge",
  "card_identifier": "user-card-identifier",
  "user_id": 1,
  "amount_credits": 100,
  "description": "Purchase description",
  "signature": "computed-hmac-sha256-signature"
}</code></pre>
                    
                    <p class="text-sm text-gray-400 mt-4">
                        Signature is computed as HMAC-SHA256 of the JSON payload (without the signature field) 
                        using your secret key.
                    </p>
                </div>

                <!-- Regenerate Keys -->
                <div class="glass-card p-6">
                    <h2 class="text-xl font-bold mb-4 text-red-400">Danger Zone</h2>
                    <p class="text-sm text-gray-400 mb-4">
                        Regenerating your API keys will invalidate the current keys. 
                        You'll need to update all applications using these keys.
                    </p>
                    
                    <form method="POST" onsubmit="return confirm('Are you sure? This will invalidate your current API keys.');">
                        <button type="submit" name="regenerate" value="1" class="btn btn-danger">
                            🔄 Regenerate API Keys
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        let secretVisible = false;

        function toggleSecretVisibility() {
            const secretKey = document.getElementById('secret-key');
            secretVisible = !secretVisible;
            
            if (secretVisible) {
                secretKey.classList.remove('blur-sm', 'select-none');
                event.target.textContent = '🙈 Hide';
            } else {
                secretKey.classList.add('blur-sm', 'select-none');
                event.target.textContent = '👁️ Show';
            }
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy:', err);
            });
        }
    </script>
</body>
</html>
