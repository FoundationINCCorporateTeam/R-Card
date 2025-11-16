# R-Card - Virtual Card System

A production-ready virtual card system for Roblox-like ecosystems with PHP 8 backend and vanilla ES6 JavaScript frontend.

## Features

- **Card Management**: Support for credit, debit, and organization cards
- **Loan System**: Complete loan creation, management, and repayment with interest calculations
- **Organization Portal**: API key management, card type creation, and transaction monitoring
- **Secure Payment API**: HMAC-SHA256 signed requests with nonce and timestamp validation
- **Encrypted Storage**: AES-256-CBC encryption for sensitive data
- **Modern UI**: Glassmorphism design with TailwindCSS and smooth transitions

## System Requirements

- PHP 8.0 or higher
- OpenSSL extension enabled
- JSON extension enabled
- Web server (Apache/Nginx)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/FoundationINCCorporateTeam/R-Card.git
cd R-Card
```

2. Configure your web server to serve from the repository root

3. Update encryption keys in `config.php`:
```php
define('R_JSON_KEY', 'your-32-character-secret-key!!'); // Must be 32 chars
define('ENCRYPTION_KEY', 'your-32-character-payload-key'); // Must be 32 chars
```

4. Run the setup script to initialize sample data:
```bash
php setup.php
```

5. Access the application:
- User Portal: `/public/card_management.php`
- Organization Portal: `/public/orgs/dashboard.php`

## Directory Structure

```
R-Card/
├── api.php                    # Main API router for authenticated requests
├── api/
│   └── payment.php           # External payment API for organizations
├── config.php                # System configuration
├── functions/                # Core business logic
│   ├── cards.php            # Card catalog and policies
│   ├── currency.php         # Currency formatting utilities
│   ├── encryption.php       # AES-256-CBC encryption
│   ├── file_storage.php     # JSON file read/write
│   ├── loans.php            # Loan management
│   ├── org_cards.php        # Organization card specs
│   ├── org_security.php     # API security validation
│   ├── orgs.php             # Organization management
│   ├── user_cards.php       # User card management
│   └── util.php             # Logging and utilities
├── public/                   # Frontend pages
│   ├── card_management.php  # Card listing UI
│   ├── card_view.php        # Card detail view
│   ├── cards.js             # Card management JavaScript
│   ├── loans.js             # Loan management JavaScript
│   ├── css/
│   │   └── app.css          # Tailwind + custom styles
│   └── orgs/                # Organization portal
│       ├── api_keys.php     # API key management
│       ├── card_edit.php    # Edit card type
│       ├── card_new.php     # Create card type
│       ├── cards_list.php   # List card types
│       ├── dashboard.php    # Org dashboard
│       └── transactions.php # Transaction history
├── jsondata/                 # JSON data storage (auto-created)
│   ├── benefits/            # Card benefits HTML
│   ├── cards/               # Base card catalog
│   ├── loans/               # User loans
│   ├── logs/                # System logs
│   ├── org_cards/           # Org card specifications
│   ├── org_nonces/          # API nonces
│   ├── org_transactions/    # Org transactions
│   ├── orgs/                # Organization data
│   ├── settings/            # System settings
│   └── user_cards/          # User card data
└── setup.php                # Initial setup script

```

## API Documentation

### User APIs (api.php)

All user API endpoints require session authentication:
- `loans_bootstrap` - Get loan policy for a card
- `loans_preview` - Preview loan terms before creation
- `loans_create` - Create a new loan
- `loans_list` - List all user loans
- `loans_repay` - Repay a loan
- `cards_list` - List user cards
- `card_details` - Get card details

### Payment API (api/payment.php)

External organizations use this API to process payments:

**Endpoint**: `POST /api/payment.php`

**Required Fields**:
```json
{
  "api_key_public": "pk_xxx",
  "nonce": "unique-nonce",
  "timestamp": 1234567890,
  "signature": "hmac-sha256-signature",
  "operation": "charge",
  "card_identifier": "user-card-id",
  "user_id": 1,
  "amount_credits": 100,
  "description": "Purchase description"
}
```

**Signature Calculation**:
1. Remove `signature` field from payload
2. Sort keys alphabetically
3. JSON encode the payload
4. Compute HMAC-SHA256 with secret key
5. Use the hash as signature

**Response (Success)**:
```json
{
  "status": "approved",
  "transaction_id": "txn_xxx",
  "amount_charged": 100,
  "new_balance": 900
}
```

**Response (Declined)**:
```json
{
  "status": "declined",
  "reason": "Insufficient funds",
  "available_balance": 50
}
```

## Security Features

1. **Encrypted Storage**: All sensitive data encrypted with AES-256-CBC
2. **HMAC Signatures**: API requests signed with HMAC-SHA256
3. **Nonce Protection**: Single-use nonces prevent replay attacks
4. **Timestamp Validation**: 15-second time drift limit
5. **Session-Based Auth**: User endpoints require active session
6. **No Path Disclosure**: Errors never expose filesystem paths

## Loan System

### Loan Calculation

Interest is calculated using daily rates:
```
daily_rate = (monthly_rate / 100) / 30
interest = principal × daily_rate × days
total_due = principal + interest
```

### Minimum Wait Period

Early repayment requires minimum wait days (default: 7). Interest charged is the greater of:
- Actual days elapsed
- Minimum wait days

### Annual Limits

Each card has yearly loan limits. The system tracks:
- Per-transaction maximum
- Annual aggregate maximum

## Configuration Options

Edit `config.php` to customize:

```php
// Encryption keys (MUST be 32 characters)
define('R_JSON_KEY', 'your-32-char-key-here-change-me!!');
define('ENCRYPTION_KEY', 'card-payload-key-32-chars-here!!');

// Session timeout (seconds)
define('SESSION_TIMEOUT', 3600);

// Loan settings
define('LOAN_MIN_AMOUNT', 100);
define('LOAN_MAX_DAYS', 180);
define('LOAN_MIN_WAIT_DAYS', 7);

// Org API settings
define('ORG_MAX_TIME_DRIFT', 15);
define('ORG_NONCE_EXPIRY', 300);

// Currency
define('CREDITS_TO_USD_RATE', 0.01); // 1 CR = $0.01
```

## Card Types

### Credit Cards
- Standard Credit: 5,000 CR limit, loans up to 2,000 CR
- Premium Credit: 15,000 CR limit, loans up to 5,000 CR
- Elite Credit: 50,000 CR limit, loans up to 15,000 CR

### Debit Cards
- Basic Debit: No loans
- Premium Debit: Loans up to 1,000 CR

### Organization Cards
Custom cards with configurable:
- Credit limits
- Interest rates
- Loan policies
- Benefits and perks

## Development

### Running Tests

Currently no automated tests. Manual testing checklist:
1. Create sample data: `php setup.php`
2. Test user card listing
3. Test loan creation and repayment
4. Test payment API with HMAC signatures
5. Test organization portal

### Adding New Card Types

1. Log into organization portal
2. Navigate to Card Catalog
3. Click "New Card Type"
4. Configure limits and policies
5. Save and issue to users

## Troubleshooting

**Issue**: Encryption errors
**Solution**: Ensure R_JSON_KEY and ENCRYPTION_KEY are exactly 32 characters

**Issue**: API signature validation fails
**Solution**: Verify payload is sorted alphabetically before signing

**Issue**: Session not persisting
**Solution**: Check PHP session configuration and cookie settings

**Issue**: Files not saving
**Solution**: Verify write permissions on `/jsondata/` directory

## License

MIT License - See LICENSE file for details

## Support

For issues and questions, please open an issue on GitHub.
