<?php
/**
 * Card Management Page
 * 
 * Full card management UI with filtering, sorting, and card grid.
 */

require_once __DIR__ . '/../config.php';

// Check if user is logged in (for demo purposes, we'll skip this check)
// In production, this should redirect to login if not authenticated
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Management - R-Card</title>
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
                <a href="/public/card_management.php" class="nav-item active">
                    <span>💳</span>
                    <span>My Cards</span>
                </a>
                <a href="#" class="nav-item">
                    <span>📊</span>
                    <span>Transactions</span>
                </a>
                <a href="#" class="nav-item">
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
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-4xl font-bold mb-2">My Cards</h1>
                    <p class="text-gray-400">Manage your virtual cards</p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="glass-card p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-400">Total Cards</div>
                                <div class="text-3xl font-bold" id="total-cards">0</div>
                            </div>
                            <div class="text-4xl">💳</div>
                        </div>
                    </div>
                    <div class="glass-card p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-400">Credit Cards</div>
                                <div class="text-3xl font-bold" id="credit-cards">0</div>
                            </div>
                            <div class="text-4xl">💎</div>
                        </div>
                    </div>
                    <div class="glass-card p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-400">Debit Cards</div>
                                <div class="text-3xl font-bold" id="debit-cards">0</div>
                            </div>
                            <div class="text-4xl">💵</div>
                        </div>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="glass-card p-6 mb-8">
                    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                        <!-- Filter Buttons -->
                        <div class="flex gap-2">
                            <button data-filter="all" class="btn btn-secondary active">All Cards</button>
                            <button data-filter="credit" class="btn btn-secondary">Credit</button>
                            <button data-filter="debit" class="btn btn-secondary">Debit</button>
                        </div>

                        <!-- Search and Sort -->
                        <div class="flex gap-4 flex-1 md:flex-initial">
                            <input type="text" id="card-search" class="input-field" 
                                   placeholder="Search cards...">
                            <select id="card-sort" class="select-field">
                                <option value="name">Sort by Name</option>
                                <option value="balance">Sort by Balance</option>
                                <option value="limit">Sort by Limit</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Cards Grid -->
                <div id="cards-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Cards will be dynamically inserted here -->
                    <div class="col-span-full text-center py-12">
                        <div class="spinner mx-auto"></div>
                        <p class="text-gray-400 mt-4">Loading cards...</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="/public/cards.js"></script>
</body>
</html>
