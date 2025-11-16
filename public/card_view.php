<?php
/**
 * Card Detail View Page
 * 
 * Displays detailed information about a specific card, including
 * transactions, perks, and loan management.
 */

require_once __DIR__ . '/../config.php';

// Get card identifier from URL
$cardId = $_GET['card_id'] ?? null;
$cardIdentifier = $_GET['card_identifier'] ?? null;

if (!$cardId && !$cardIdentifier) {
    header('Location: /public/card_management.php');
    exit;
}

// For demo purposes, set a session (in production, check actual auth)
if (!isset($_SESSION['logged_in'])) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Details - R-Card</title>
    <link rel="stylesheet" href="/public/css/app.css">
</head>
<body>
    <div class="flex min-h-screen">
        <!-- Sidebar Navigation -->
        <aside class="sidebar w-64">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-gradient">R-Card</h1>
                <p class="text-sm text-gray-400 mt-1">Virtual Card System</p>
            </div>
            <nav class="mt-6">
                <a href="/public/card_management.php" class="nav-item">
                    <span>💳</span>
                    <span>My Cards</span>
                </a>
                <a href="#" class="nav-item">
                    <span>📊</span>
                    <span>Transactions</span>
                </a>
                <a href="#" class="nav-item active">
                    <span>💰</span>
                    <span>Loans</span>
                </a>
                <a href="#" class="nav-item">
                    <span>⚙️</span>
                    <span>Settings</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <div class="max-w-7xl mx-auto">
                <!-- Back Button -->
                <div class="mb-6">
                    <a href="/public/card_management.php" class="btn btn-secondary">
                        ← Back to Cards
                    </a>
                </div>

                <!-- Card Hero -->
                <div id="card-hero" class="glass-card p-8 mb-8">
                    <div class="spinner mx-auto"></div>
                    <p class="text-center text-gray-400 mt-4">Loading card details...</p>
                </div>

                <!-- Card Summary -->
                <div id="card-summary" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Summary will be loaded dynamically -->
                </div>

                <!-- Tabs -->
                <div class="glass-card p-6 mb-8">
                    <div class="flex gap-4 border-b border-white/10 pb-4 mb-6">
                        <button class="tab-btn active" data-tab="loans">
                            Loans
                        </button>
                        <button class="tab-btn" data-tab="transactions">
                            Transactions
                        </button>
                        <button class="tab-btn" data-tab="benefits">
                            Benefits & Perks
                        </button>
                    </div>

                    <!-- Loans Tab -->
                    <div id="tab-loans" class="tab-content">
                        <div id="loans-section">
                            <!-- Loans will be loaded by loans.js -->
                        </div>
                    </div>

                    <!-- Transactions Tab -->
                    <div id="tab-transactions" class="tab-content hidden">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="text-center text-gray-400 py-8">
                                            No transactions yet
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Benefits Tab -->
                    <div id="tab-benefits" class="tab-content hidden">
                        <div id="benefits-content">
                            <p class="text-gray-400">Loading benefits...</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Set current card identifiers for loans.js
        window.currentCardId = <?php echo json_encode($cardId); ?>;
        window.currentCardIdentifier = <?php echo json_encode($cardIdentifier); ?>;

        // Load card details
        async function loadCardDetails() {
            try {
                const params = new URLSearchParams();
                if (window.currentCardId) {
                    params.set('card_id', window.currentCardId);
                }
                if (window.currentCardIdentifier) {
                    params.set('card_identifier', window.currentCardIdentifier);
                }

                const response = await fetch('/api.php?action=card_details&' + params.toString(), {
                    credentials: 'include'
                });

                const data = await response.json();

                if (data.success) {
                    renderCardHero(data.card);
                    renderCardSummary(data.card);
                    loadBenefits(data.card);
                    // Set card ID for loans manager
                    window.currentCardId = data.card.card_id;
                } else {
                    showError('Failed to load card details');
                }
            } catch (error) {
                console.error('Load error:', error);
                showError('Failed to load card details');
            }
        }

        function renderCardHero(card) {
            const cardClass = card.card_type === 'credit' ? 'card-credit' : 'card-debit';
            const hero = document.getElementById('card-hero');
            
            hero.innerHTML = `
                <div class="card-item ${cardClass} max-w-md mx-auto">
                    <div class="mb-4">
                        <div class="text-sm opacity-80">${card.card_type.toUpperCase()}</div>
                        <h2 class="text-2xl font-bold">${card.card_name}</h2>
                    </div>
                    <div class="font-mono text-xl mb-4">
                        •••• •••• •••• ${card.card_identifier.slice(-4)}
                    </div>
                    <div class="flex justify-between items-end">
                        <div>
                            <div class="text-sm opacity-80">Balance</div>
                            <div class="text-2xl font-bold">${formatCredits(card.current_balance)}</div>
                        </div>
                        ${card.expiry_date ? `
                            <div class="text-right">
                                <div class="text-sm opacity-80">Expires</div>
                                <div>${formatDate(card.expiry_date)}</div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        function renderCardSummary(card) {
            const summary = document.getElementById('card-summary');
            
            summary.innerHTML = `
                <div class="glass-card p-6">
                    <div class="text-sm text-gray-400 mb-2">Current Balance</div>
                    <div class="text-2xl font-bold">${formatCredits(card.current_balance)}</div>
                </div>
                ${card.card_type === 'credit' ? `
                    <div class="glass-card p-6">
                        <div class="text-sm text-gray-400 mb-2">Credit Limit</div>
                        <div class="text-2xl font-bold">${formatCredits(card.credit_limit)}</div>
                    </div>
                    <div class="glass-card p-6">
                        <div class="text-sm text-gray-400 mb-2">Available Credit</div>
                        <div class="text-2xl font-bold text-green-400">
                            ${formatCredits(card.credit_limit - Math.abs(card.current_balance))}
                        </div>
                    </div>
                ` : `
                    <div class="glass-card p-6">
                        <div class="text-sm text-gray-400 mb-2">Card Status</div>
                        <div class="text-2xl font-bold">${card.status}</div>
                    </div>
                    <div class="glass-card p-6">
                        <div class="text-sm text-gray-400 mb-2">Issued Date</div>
                        <div class="text-lg">${formatDate(card.issued_date)}</div>
                    </div>
                `}
            `;
        }

        async function loadBenefits(card) {
            try {
                const cardSlug = card.card_name.toLowerCase().replace(/\s+/g, '-');
                const response = await fetch('/jsondata/benefits/all_cards.html');
                const html = await response.text();
                
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const benefitsSection = doc.getElementById(`benefits-${cardSlug}`);
                
                const container = document.getElementById('benefits-content');
                if (benefitsSection) {
                    container.innerHTML = benefitsSection.innerHTML;
                } else {
                    container.innerHTML = '<p class="text-gray-400">No benefits information available</p>';
                }
            } catch (error) {
                console.error('Benefits load error:', error);
            }
        }

        function formatCredits(amount) {
            return new Intl.NumberFormat('en-US').format(amount) + ' CR';
        }

        function formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }

        function showError(message) {
            console.error(message);
        }

        // Tab switching
        document.addEventListener('DOMContentLoaded', () => {
            loadCardDetails();

            // Setup tab switching
            const tabBtns = document.querySelectorAll('.tab-btn');
            tabBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const tab = btn.dataset.tab;
                    
                    // Update active button
                    tabBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    
                    // Show/hide content
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.add('hidden');
                    });
                    document.getElementById(`tab-${tab}`).classList.remove('hidden');
                });
            });
        });
    </script>
    <script src="/public/loans.js"></script>

    <style>
        .tab-btn {
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: transparent;
            color: var(--text-secondary);
            border: none;
            cursor: pointer;
        }

        .tab-btn.active {
            background: rgba(99, 102, 241, 0.2);
            color: var(--primary-color);
        }

        .tab-btn:hover {
            background: rgba(99, 102, 241, 0.1);
        }
    </style>
</body>
</html>
