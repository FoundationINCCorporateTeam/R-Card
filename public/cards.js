/**
 * Cards Management JavaScript
 * 
 * Handles card listing, filtering, and sorting.
 */

class CardsManager {
    constructor() {
        this.cards = [];
        this.filteredCards = [];
        this.currentFilter = 'all';
        this.currentSort = 'name';
        this.init();
    }

    async init() {
        await this.loadCards();
        this.setupEventListeners();
    }

    async loadCards() {
        try {
            const response = await fetch('/api.php?action=cards_list', {
                method: 'GET',
                credentials: 'include'
            });

            const data = await response.json();

            if (data.success) {
                this.cards = data.cards;
                this.filteredCards = [...this.cards];
                this.renderCards();
                this.updateStats();
            } else {
                this.showError('Failed to load cards');
            }
        } catch (error) {
            console.error('Load cards error:', error);
            this.showError('Failed to load cards');
        }
    }

    setupEventListeners() {
        // Filter buttons
        const filterButtons = document.querySelectorAll('[data-filter]');
        filterButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const filter = e.target.dataset.filter;
                this.applyFilter(filter);
            });
        });

        // Sort dropdown
        const sortSelect = document.getElementById('card-sort');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                this.applySort(e.target.value);
            });
        }

        // Search input
        const searchInput = document.getElementById('card-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.applySearch(e.target.value);
            });
        }
    }

    applyFilter(filter) {
        this.currentFilter = filter;

        // Update active button
        document.querySelectorAll('[data-filter]').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-filter="${filter}"]`)?.classList.add('active');

        // Filter cards
        if (filter === 'all') {
            this.filteredCards = [...this.cards];
        } else {
            this.filteredCards = this.cards.filter(card => card.card_type === filter);
        }

        this.applySort(this.currentSort);
        this.renderCards();
        this.updateStats();
    }

    applySort(sortBy) {
        this.currentSort = sortBy;

        this.filteredCards.sort((a, b) => {
            switch (sortBy) {
                case 'name':
                    return (a.card_name || '').localeCompare(b.card_name || '');
                case 'balance':
                    return (b.current_balance || 0) - (a.current_balance || 0);
                case 'limit':
                    return (b.credit_limit || 0) - (a.credit_limit || 0);
                default:
                    return 0;
            }
        });

        this.renderCards();
    }

    applySearch(query) {
        const searchTerm = query.toLowerCase();

        if (!searchTerm) {
            this.applyFilter(this.currentFilter);
            return;
        }

        this.filteredCards = this.cards.filter(card => {
            const name = (card.card_name || '').toLowerCase();
            const identifier = (card.card_identifier || '').toLowerCase();
            const type = (card.card_type || '').toLowerCase();

            return name.includes(searchTerm) || 
                   identifier.includes(searchTerm) || 
                   type.includes(searchTerm);
        });

        this.renderCards();
    }

    renderCards() {
        const container = document.getElementById('cards-grid');
        if (!container) return;

        if (this.filteredCards.length === 0) {
            container.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <div class="text-6xl mb-4">💳</div>
                    <h3 class="text-xl font-bold mb-2">No cards found</h3>
                    <p class="text-gray-400">Try adjusting your filters</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.filteredCards.map(card => {
            const cardClass = this.getCardClass(card.card_type);
            const statusBadge = this.getStatusBadge(card.status);
            const balanceColor = this.getBalanceColor(card.current_balance);

            return `
                <div class="glass-card card-item ${cardClass} fade-in" 
                     onclick="window.location.href='/public/card_view.php?card_id=${card.card_id}'">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <div class="text-sm opacity-80">${card.card_type.toUpperCase()}</div>
                            <h3 class="text-xl font-bold">${card.card_name}</h3>
                        </div>
                        ${statusBadge}
                    </div>
                    
                    <div class="mb-4">
                        <div class="text-sm opacity-80">Card Number</div>
                        <div class="font-mono text-lg">•••• ${card.card_identifier.slice(-4)}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <div class="text-sm opacity-80">Balance</div>
                            <div class="text-lg font-bold ${balanceColor}">
                                ${this.formatCredits(card.current_balance)}
                            </div>
                        </div>
                        ${card.card_type === 'credit' ? `
                            <div>
                                <div class="text-sm opacity-80">Limit</div>
                                <div class="text-lg font-bold">
                                    ${this.formatCredits(card.credit_limit)}
                                </div>
                            </div>
                        ` : ''}
                    </div>

                    ${card.expiry_date ? `
                        <div class="text-sm opacity-70">
                            Expires: ${this.formatDate(card.expiry_date)}
                        </div>
                    ` : ''}
                </div>
            `;
        }).join('');
    }

    updateStats() {
        const totalCards = this.cards.length;
        const creditCards = this.cards.filter(c => c.card_type === 'credit').length;
        const debitCards = this.cards.filter(c => c.card_type === 'debit').length;

        document.getElementById('total-cards')?.textContent = totalCards;
        document.getElementById('credit-cards')?.textContent = creditCards;
        document.getElementById('debit-cards')?.textContent = debitCards;
    }

    getCardClass(cardType) {
        switch (cardType) {
            case 'credit':
                return 'card-credit';
            case 'debit':
                return 'card-debit';
            case 'org':
                return 'card-org';
            default:
                return '';
        }
    }

    getStatusBadge(status) {
        switch (status) {
            case 'active':
                return '<span class="badge badge-success">Active</span>';
            case 'blocked':
                return '<span class="badge badge-danger">Blocked</span>';
            case 'stolen':
                return '<span class="badge badge-danger">Stolen</span>';
            default:
                return '<span class="badge badge-info">Unknown</span>';
        }
    }

    getBalanceColor(balance) {
        if (balance > 0) {
            return 'text-green-400';
        } else if (balance < 0) {
            return 'text-red-400';
        }
        return '';
    }

    formatCredits(amount) {
        return new Intl.NumberFormat('en-US').format(amount) + ' CR';
    }

    formatDate(dateStr) {
        if (!dateStr) return 'N/A';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    }

    showError(message) {
        console.error(message);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('cards-grid')) {
        new CardsManager();
    }
});
