# R-Card System Architecture

## System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                         R-Card System                            │
│              Virtual Card Management Platform                    │
└─────────────────────────────────────────────────────────────────┘
                                 │
                    ┌────────────┴────────────┐
                    │                         │
            ┌───────▼─────┐          ┌───────▼────────┐
            │ User Portal │          │  Org Portal    │
            │             │          │                │
            │ • Cards     │          │ • Dashboard    │
            │ • Loans     │          │ • Card Types   │
            │ • Txns      │          │ • API Keys     │
            └──────┬──────┘          └────────┬───────┘
                   │                          │
                   │      ┌──────────┐        │
                   └──────► API Layer ◄───────┘
                          │          │
                          │ api.php  │
                          └────┬─────┘
                               │
              ┌────────────────┼────────────────┐
              │                │                │
      ┌───────▼────┐   ┌──────▼──────┐  ┌─────▼────────┐
      │ Functions  │   │  Security   │  │   Storage    │
      │            │   │             │  │              │
      │ • Cards    │   │ • Encrypt   │  │ • JSON Files │
      │ • Loans    │   │ • HMAC      │  │ • Encrypted  │
      │ • Currency │   │ • Nonce     │  │ • Logs       │
      │ • Orgs     │   │ • Timestamp │  │              │
      └────────────┘   └─────────────┘  └──────────────┘
```

## API Flow

### User API Request Flow
```
User Browser
    │
    ▼
Session Check ──[invalid]──► 401 Unauthorized
    │
    │ [valid]
    ▼
api.php Router
    │
    ├──► loans_bootstrap ──► r_get_user_card_row ──► r_build_loan_policy ──► Response
    │
    ├──► loans_preview ──► Validate ──► r_calculate_loan_interest ──► Response
    │
    ├──► loans_create ──► Validate ──► r_create_loan ──► Update Balance ──► Response
    │
    ├──► loans_repay ──► r_repay_loan ──► Recalculate ──► Update ──► Response
    │
    ├──► cards_list ──► r_load_user_cards ──► Format ──► Response
    │
    └──► card_details ──► r_get_user_card_row ──► Format ──► Response
```

### Payment API Request Flow
```
External Org
    │
    ▼
api/payment.php
    │
    ├──► Validate JSON
    │
    ├──► org_find_by_apikey ──[not found]──► 401 Invalid Key
    │        │
    │        ▼ [found]
    │    Check Status ──[inactive]──► 403 Org Not Active
    │        │
    │        ▼ [active]
    ├──► org_validate_timestamp ──[invalid]──► 400 Invalid Timestamp
    │        │
    │        ▼ [valid]
    ├──► org_nonce_used ──[used]──► 400 Nonce Reused
    │        │
    │        ▼ [not used]
    ├──► org_verify_signature ──[invalid]──► 401 Invalid Signature
    │        │
    │        ▼ [valid]
    ├──► org_mark_nonce_used
    │
    ├──► r_get_user_card_row ──[not found]──► 404 Card Not Found
    │        │
    │        ▼ [found]
    ├──► Check Balance/Limit
    │        │
    │        ├──[insufficient]──► declined
    │        │
    │        ▼ [sufficient]
    ├──► Update Balance
    │
    ├──► Save Transaction
    │
    ├──► Log Event
    │
    └──► Response { approved }
```

## Data Layer

### JSON Storage Structure
```
/jsondata/
├── users/
│   └── {user_id}.json [encrypted]
│
├── user_cards/
│   └── {user_id}.json [encrypted]
│       └── { cards: [ { card_id, card_identifier, ... } ] }
│
├── loans/
│   └── {user_id}.json [encrypted]
│       └── { loans: [ { loan_id, amount, ... } ] }
│
├── orgs/
│   └── {org_id}.json [encrypted]
│       └── { org_id, name, api_keys, ... }
│
├── org_cards/
│   └── {org_id}/
│       └── {card_id}.json [encrypted]
│           └── { card_name, loan_policy, ... }
│
├── org_transactions/
│   └── {org_id}.json [plain JSON]
│       └── { transactions: [ { txn_id, ... } ] }
│
├── org_nonces/
│   └── nonces.json [plain JSON]
│       └── { nonces: [ { nonce, timestamp } ] }
│
├── logs/
│   └── {YYYY-MM-DD}.json [plain JSON]
│       └── [ { timestamp, category, message } ]
│
├── cards/
│   └── base_cards.json [plain JSON]
│       └── { credit: {...}, debit: {...} }
│
└── benefits/
    └── all_cards.html
        └── HTML benefit descriptions
```

## Security Layers

### Layer 1: Transport
- HTTPS (in production)
- Session cookies (HttpOnly, Secure)

### Layer 2: Authentication
- Session-based for user APIs
- API key + signature for payment API

### Layer 3: Authorization
- Card ownership validation
- Organization status check
- User ID matching

### Layer 4: Data Protection
- AES-256-CBC encryption for storage
- Separate keys for different data types
- Random IV per encryption

### Layer 5: Request Validation
- Nonce uniqueness
- Timestamp freshness (±15s)
- HMAC-SHA256 signature
- Input sanitization

### Layer 6: Rate Limiting
- Yearly loan limits
- Per-transaction maximums
- Nonce expiry (5 minutes)

### Layer 7: Audit Trail
- All transactions logged
- Suspicious events logged
- Daily log rotation

## Function Dependencies

```
Encryption Layer
├── r_encrypt() ──► OpenSSL AES-256-CBC
└── r_decrypt() ──► OpenSSL AES-256-CBC

File Storage Layer
├── r_json_read() ──► r_decrypt()
└── r_json_write() ──► r_encrypt()

Cards Layer
├── r_lookup_base_card_policy() ──► r_json_read()
├── r_load_org_card() ──► r_json_read()
└── r_find_org_card_by_public_id() ──► r_json_read()

User Cards Layer
├── r_load_user_cards() ──► r_json_read()
├── r_save_user_cards() ──► r_json_write()
├── r_get_user_card_row() ──► r_load_user_cards()
└── r_decrypt_payload_row() ──► Custom decrypt

Loans Layer
├── r_load_user_loans() ──► r_json_read()
├── r_save_user_loans() ──► r_json_write()
├── r_build_loan_policy() ──► r_lookup_base_card_policy()
├── r_sum_loans_year() ──► r_load_user_loans()
├── r_calculate_loan_interest() ──► Math
├── r_create_loan() ──► r_save_user_loans(), r_log()
└── r_repay_loan() ──► r_load_user_loans(), r_save_user_loans(), r_log()

Organization Layer
├── org_load() ──► r_json_read()
├── org_save() ──► r_json_write()
├── org_find_by_apikey() ──► r_json_read() (multiple)
├── org_generate_api_keys() ──► random_bytes()
├── org_load_transactions() ──► r_json_read()
└── org_save_transactions() ──► r_json_write()

Organization Security Layer
├── org_verify_signature() ──► hash_hmac(), hash_equals()
├── org_validate_timestamp() ──► time()
├── org_nonce_used() ──► file operations
└── org_mark_nonce_used() ──► file operations
```

## Loan Interest Formula

```
Given:
  P = Principal amount (CR)
  M = Monthly interest rate (%)
  D = Loan duration (days)
  W = Minimum wait days

Calculate:
  R_daily = (M / 100) / 30
  
  Interest_min = P × R_daily × W
  Interest_actual = P × R_daily × D
  
  Total_due = P + Interest_actual

On Repayment:
  Days_elapsed = (now - created_at) / 86400
  Days_charged = max(Days_elapsed, W)
  
  Final_interest = P × R_daily × Days_charged
  Amount_to_pay = P + Final_interest
```

## UI Component Hierarchy

```
User Portal
│
├── card_management.php
│   ├── Sidebar Navigation
│   ├── Stats Cards
│   ├── Filter/Sort/Search Bar
│   └── Cards Grid
│       └── Card Item (click → card_view.php)
│
└── card_view.php
    ├── Sidebar Navigation
    ├── Card Hero Display
    ├── Summary Stats
    └── Tab Container
        ├── Loans Tab (loans.js)
        │   ├── Loan Policy Info
        │   ├── Loan Request Form
        │   │   ├── Amount Input
        │   │   ├── Duration Slider
        │   │   ├── Preview Button
        │   │   └── Create Button
        │   └── Active Loans List
        │       └── Loan Item (Repay Button)
        │
        ├── Transactions Tab
        │   └── Transaction Table
        │
        └── Benefits Tab
            └── Benefits HTML

Organization Portal
│
├── dashboard.php
│   ├── Sidebar Navigation
│   ├── Stats Cards
│   ├── Quick Actions
│   └── Recent Activity
│
├── cards_list.php
│   ├── Sidebar Navigation
│   └── Card Types Grid
│       └── Card Type Item (Edit/Delete)
│
├── card_new.php / card_edit.php
│   ├── Sidebar Navigation
│   └── Card Form
│       ├── Basic Information
│       ├── Credit Settings (conditional)
│       └── Loan Policy Settings
│
├── api_keys.php
│   ├── Sidebar Navigation
│   ├── Public Key Display
│   ├── Secret Key Display (blurred)
│   ├── API Usage Example
│   └── Regenerate Button
│
└── transactions.php
    ├── Sidebar Navigation
    ├── Transaction Stats
    └── Transactions Table
```

## Deployment Architecture

```
Production Environment
│
├── Web Server (Nginx/Apache)
│   ├── HTTPS/SSL
│   ├── Rate Limiting
│   └── Static File Serving
│
├── PHP-FPM
│   ├── OPcache Enabled
│   ├── Session Storage
│   └── Application Code
│
├── File System
│   ├── /jsondata/ (700 permissions)
│   │   ├── Encrypted user data
│   │   └── Logs
│   │
│   └── /public/ (755 permissions)
│       ├── CSS/JS assets
│       └── PHP pages
│
└── Monitoring
    ├── Access Logs
    ├── Error Logs
    └── Application Logs (/jsondata/logs/)
```

---

**Version**: 1.0.0
**Last Updated**: 2024-11-16
