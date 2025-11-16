/**
 * Loans Management JavaScript
 * 
 * Handles loan bootstrap, preview, creation, listing, and repayment.
 */

class LoansManager {
    constructor(cardId) {
        this.cardId = cardId;
        this.policy = null;
        this.init();
    }

    async init() {
        await this.bootstrap();
        this.setupEventListeners();
        await this.loadLoans();
    }

    async bootstrap() {
        try {
            const response = await fetch('/api.php?action=loans_bootstrap', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    card_id: this.cardId
                })
            });

            const data = await response.json();

            if (data.success && data.loan_enabled) {
                this.policy = data.policy;
                this.renderLoanInterface();
            } else {
                this.renderNoLoansAvailable();
            }
        } catch (error) {
            console.error('Bootstrap error:', error);
            this.showError('Failed to load loan information');
        }
    }

    renderLoanInterface() {
        const container = document.getElementById('loans-section');
        if (!container) return;

        container.innerHTML = `
            <div class="glass-card p-6 mb-6">
                <h2 class="text-2xl font-bold mb-4">Loan Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white/5 p-4 rounded-lg">
                        <div class="text-sm text-gray-400">Max Amount</div>
                        <div class="text-xl font-bold">${this.formatCredits(this.policy.max_amount)}</div>
                    </div>
                    <div class="bg-white/5 p-4 rounded-lg">
                        <div class="text-sm text-gray-400">Yearly Limit</div>
                        <div class="text-xl font-bold">${this.formatCredits(this.policy.max_year)}</div>
                    </div>
                    <div class="bg-white/5 p-4 rounded-lg">
                        <div class="text-sm text-gray-400">Remaining This Year</div>
                        <div class="text-xl font-bold text-green-400">${this.formatCredits(this.policy.remaining_year)}</div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white/5 p-4 rounded-lg">
                        <div class="text-sm text-gray-400">Interest Rate</div>
                        <div class="text-xl font-bold">${this.policy.interest_rate_monthly}% / month</div>
                    </div>
                    <div class="bg-white/5 p-4 rounded-lg">
                        <div class="text-sm text-gray-400">Min Wait Days</div>
                        <div class="text-xl font-bold">${this.policy.min_wait_days} days</div>
                    </div>
                    <div class="bg-white/5 p-4 rounded-lg">
                        <div class="text-sm text-gray-400">Max Duration</div>
                        <div class="text-xl font-bold">${this.policy.max_days} days</div>
                    </div>
                </div>
            </div>

            <div class="glass-card p-6 mb-6">
                <h2 class="text-2xl font-bold mb-4">Request New Loan</h2>
                <div id="loan-form-messages"></div>
                <form id="loan-form" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Loan Amount (CR)</label>
                        <input type="number" id="loan-amount" class="input-field" 
                               min="100" max="${this.policy.max_amount}" step="100" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Duration (Days)</label>
                        <input type="range" id="loan-days" class="w-full" 
                               min="1" max="${this.policy.max_days}" value="${Math.floor(this.policy.max_days / 2)}">
                        <div class="text-center mt-2">
                            <span id="loan-days-display" class="text-lg font-bold">${Math.floor(this.policy.max_days / 2)}</span> days
                        </div>
                    </div>
                    <div id="loan-preview" class="bg-white/5 p-4 rounded-lg hidden">
                        <h3 class="font-bold mb-2">Loan Preview</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>Principal:</span>
                                <span id="preview-amount"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Interest:</span>
                                <span id="preview-interest"></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Min. Interest (${this.policy.min_wait_days} days):</span>
                                <span id="preview-min-interest"></span>
                            </div>
                            <div class="divider"></div>
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total Due:</span>
                                <span id="preview-total"></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <button type="button" id="preview-loan-btn" class="btn btn-secondary flex-1">
                            Preview Loan
                        </button>
                        <button type="submit" id="create-loan-btn" class="btn btn-primary flex-1" disabled>
                            Create Loan
                        </button>
                    </div>
                </form>
            </div>

            <div class="glass-card p-6">
                <h2 class="text-2xl font-bold mb-4">Active Loans</h2>
                <div id="loans-list"></div>
            </div>
        `;
    }

    renderNoLoansAvailable() {
        const container = document.getElementById('loans-section');
        if (!container) return;

        container.innerHTML = `
            <div class="glass-card p-6 text-center">
                <div class="text-6xl mb-4">🔒</div>
                <h2 class="text-2xl font-bold mb-2">Loans Not Available</h2>
                <p class="text-gray-400">This card does not support loan features.</p>
            </div>
        `;
    }

    setupEventListeners() {
        // Days slider
        const daysSlider = document.getElementById('loan-days');
        const daysDisplay = document.getElementById('loan-days-display');
        
        if (daysSlider && daysDisplay) {
            daysSlider.addEventListener('input', (e) => {
                daysDisplay.textContent = e.target.value;
            });
        }

        // Preview button
        const previewBtn = document.getElementById('preview-loan-btn');
        if (previewBtn) {
            previewBtn.addEventListener('click', () => this.previewLoan());
        }

        // Form submission
        const form = document.getElementById('loan-form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.createLoan();
            });
        }
    }

    async previewLoan() {
        const amount = parseFloat(document.getElementById('loan-amount').value);
        const days = parseInt(document.getElementById('loan-days').value);

        if (!amount || amount < 100) {
            this.showError('Please enter a valid loan amount');
            return;
        }

        try {
            const response = await fetch('/api.php?action=loans_preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    card_id: this.cardId,
                    amount: amount,
                    days: days
                })
            });

            const data = await response.json();

            if (data.success) {
                this.renderPreview(data.preview);
                document.getElementById('create-loan-btn').disabled = false;
            } else {
                this.showError(data.error || 'Failed to preview loan');
            }
        } catch (error) {
            console.error('Preview error:', error);
            this.showError('Failed to preview loan');
        }
    }

    renderPreview(preview) {
        const previewContainer = document.getElementById('loan-preview');
        if (!previewContainer) return;

        document.getElementById('preview-amount').textContent = this.formatCredits(preview.amount);
        document.getElementById('preview-interest').textContent = this.formatCredits(preview.interest_selected);
        document.getElementById('preview-min-interest').textContent = this.formatCredits(preview.interest_min_wait);
        document.getElementById('preview-total').textContent = this.formatCredits(preview.total_due);

        previewContainer.classList.remove('hidden');
    }

    async createLoan() {
        const amount = parseFloat(document.getElementById('loan-amount').value);
        const days = parseInt(document.getElementById('loan-days').value);

        try {
            const response = await fetch('/api.php?action=loans_create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    card_id: this.cardId,
                    amount: amount,
                    days: days
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Loan created successfully!');
                document.getElementById('loan-form').reset();
                document.getElementById('loan-preview').classList.add('hidden');
                document.getElementById('create-loan-btn').disabled = true;
                await this.loadLoans();
                await this.bootstrap(); // Refresh policy info
            } else {
                this.showError(data.error || 'Failed to create loan');
            }
        } catch (error) {
            console.error('Create error:', error);
            this.showError('Failed to create loan');
        }
    }

    async loadLoans() {
        try {
            const response = await fetch('/api.php?action=loans_list', {
                method: 'GET',
                credentials: 'include'
            });

            const data = await response.json();

            if (data.success) {
                this.renderLoans(data.loans);
            }
        } catch (error) {
            console.error('Load loans error:', error);
        }
    }

    renderLoans(loans) {
        const container = document.getElementById('loans-list');
        if (!container) return;

        // Filter loans for this card
        const cardLoans = loans.filter(loan => loan.card_id === this.cardId);

        if (cardLoans.length === 0) {
            container.innerHTML = '<p class="text-gray-400 text-center py-4">No loans yet</p>';
            return;
        }

        container.innerHTML = cardLoans.map(loan => {
            const isActive = loan.status === 'active';
            const statusClass = isActive ? 'loan-active' : 'loan-paid';
            const statusBadge = isActive ? 
                '<span class="badge badge-warning">Active</span>' : 
                '<span class="badge badge-success">Paid</span>';

            return `
                <div class="loan-card ${statusClass}">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <div class="text-sm text-gray-400">Loan ID</div>
                            <div class="font-mono text-sm">${loan.loan_id}</div>
                        </div>
                        ${statusBadge}
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <div class="text-sm text-gray-400">Amount</div>
                            <div class="font-bold">${this.formatCredits(loan.amount)}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-400">Interest</div>
                            <div class="font-bold">${this.formatCredits(loan.interest_amount)}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-400">Total Due</div>
                            <div class="font-bold">${this.formatCredits(loan.total_due)}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-400">Duration</div>
                            <div class="font-bold">${loan.days} days</div>
                        </div>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <div>
                            <span class="text-gray-400">Created:</span> ${this.formatDate(loan.created_at)}
                        </div>
                        ${isActive ? `
                            <button onclick="loansManager.repayLoan('${loan.loan_id}')" 
                                    class="btn btn-success btn-sm">
                                Repay Loan
                            </button>
                        ` : `
                            <div class="text-gray-400">Paid: ${this.formatDate(loan.paid_at)}</div>
                        `}
                    </div>
                </div>
            `;
        }).join('');
    }

    async repayLoan(loanId) {
        if (!confirm('Are you sure you want to repay this loan? The amount will be deducted from your card balance.')) {
            return;
        }

        try {
            const response = await fetch('/api.php?action=loans_repay', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    loan_id: loanId,
                    source: 'card_balance'
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Loan repaid successfully!');
                await this.loadLoans();
                await this.bootstrap(); // Refresh policy info
            } else {
                this.showError(data.error || 'Failed to repay loan');
            }
        } catch (error) {
            console.error('Repay error:', error);
            this.showError('Failed to repay loan');
        }
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
        const container = document.getElementById('loan-form-messages');
        if (!container) return;

        container.innerHTML = `
            <div class="alert alert-error">
                <span>⚠️</span>
                <span>${message}</span>
            </div>
        `;

        setTimeout(() => {
            container.innerHTML = '';
        }, 5000);
    }

    showSuccess(message) {
        const container = document.getElementById('loan-form-messages');
        if (!container) return;

        container.innerHTML = `
            <div class="alert alert-success">
                <span>✓</span>
                <span>${message}</span>
            </div>
        `;

        setTimeout(() => {
            container.innerHTML = '';
        }, 5000);
    }
}

// Global reference for inline event handlers
let loansManager;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (window.currentCardId) {
        loansManager = new LoansManager(window.currentCardId);
    }
});
