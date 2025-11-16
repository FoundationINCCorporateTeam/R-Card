# R-Card Deployment Guide

## Quick Start

### 1. Initial Setup

Run the setup script to initialize the system:
```bash
php setup.php
```

This will:
- Create the JSON data directory structure
- Initialize base card catalog
- Create sample user with 2 cards
- Create demo organization with API keys
- Create sample organization card type
- Test encryption/decryption

### 2. Security Configuration

**IMPORTANT**: Before deploying to production, update the encryption keys in `config.php`:

```php
// Change these to random 32-character strings
define('R_JSON_KEY', 'your-unique-32-char-key-here!!!');
define('ENCRYPTION_KEY', 'another-unique-32-char-key-here');
```

Generate secure keys using:
```bash
php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;"
```

### 3. Web Server Configuration

#### Apache (.htaccess)

Create `.htaccess` in the root directory:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Protect JSON data directory
    RewriteRule ^jsondata/ - [F,L]
    
    # Prevent direct access to function files
    RewriteRule ^functions/ - [F,L]
</IfModule>

# Disable directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "^(config\.php|setup\.php|test\.php)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

#### Nginx

Add to your server block:
```nginx
location /jsondata/ {
    deny all;
    return 404;
}

location /functions/ {
    deny all;
    return 404;
}

location ~ ^/(config|setup|test)\.php$ {
    deny all;
    return 404;
}
```

### 4. File Permissions

```bash
# Make jsondata writable by web server
chmod -R 755 jsondata/
chown -R www-data:www-data jsondata/  # Adjust for your system

# Protect sensitive files
chmod 600 config.php
```

### 5. Session Configuration

Ensure PHP sessions are properly configured in `php.ini`:
```ini
session.cookie_httponly = 1
session.cookie_secure = 1  # If using HTTPS
session.use_strict_mode = 1
```

## Testing the System

### 1. Test Core Functionality
```bash
php test.php
```

Should show all tests passing:
- ✓ Encryption/Decryption
- ✓ Interest Calculation
- ✓ Currency Formatting
- ✓ Money Conversion
- ✓ Percentage Conversion

### 2. Test Payment API Example
```bash
php examples/payment_api_example.php
```

This generates a properly signed payment request.

### 3. Access User Portal

Navigate to: `http://your-domain/public/card_management.php`

**Note**: For demo purposes, the session is auto-created. In production, implement proper authentication.

### 4. Access Organization Portal

Navigate to: `http://your-domain/public/orgs/dashboard.php`

View API keys, create card types, and monitor transactions.

## Production Checklist

- [ ] Update encryption keys in `config.php`
- [ ] Configure web server to protect sensitive directories
- [ ] Set proper file permissions
- [ ] Configure HTTPS with valid SSL certificate
- [ ] Implement proper user authentication (replace demo session)
- [ ] Set up database backups for JSON files
- [ ] Configure PHP error logging
- [ ] Set `display_errors = 0` in production
- [ ] Test Payment API with real integrations
- [ ] Set up monitoring and alerting
- [ ] Review and adjust loan limits and interest rates
- [ ] Customize card benefits in `/jsondata/benefits/all_cards.html`

## Integration Guide

### For Game Developers

1. Get your organization API keys from the portal
2. Use the Payment API to charge users
3. See `examples/payment_api_example.php` for implementation

### Payment API Endpoint

```
POST /api/payment.php
Content-Type: application/json
```

**Request Body**:
```json
{
  "api_key_public": "pk_xxx",
  "nonce": "unique-nonce",
  "timestamp": 1234567890,
  "operation": "charge",
  "card_identifier": "user-card-id",
  "user_id": 1,
  "amount_credits": 100,
  "description": "Purchase description",
  "signature": "hmac-sha256-hash"
}
```

**Signature Calculation**:
```php
// Remove signature from payload
// Sort keys alphabetically
// JSON encode
// HMAC-SHA256 with secret key
$signature = hash_hmac('sha256', $payload_json, $secret_key);
```

### Response Codes

- `200` - Success (approved/declined/rejected in body)
- `400` - Bad request (invalid parameters)
- `401` - Unauthorized (invalid API key or signature)
- `404` - Card not found
- `405` - Method not allowed
- `500` - Internal server error

## Monitoring

### Log Files

Logs are stored in `/jsondata/logs/YYYY-MM-DD.json`

Monitor for:
- `payment_api` - Payment transactions
- `payment_api_error` - Payment errors
- `loans` - Loan activity
- `security` - Security events

### Transaction Monitoring

View organization transactions:
```
/public/orgs/transactions.php
```

## Backup Strategy

### Manual Backup
```bash
tar -czf rcard-backup-$(date +%Y%m%d).tar.gz jsondata/
```

### Automated Backup (Cron)
```bash
0 2 * * * cd /path/to/R-Card && tar -czf /backups/rcard-$(date +\%Y\%m\%d).tar.gz jsondata/
```

## Troubleshooting

### Issue: Cards not loading
**Check**: File permissions on `/jsondata/user_cards/`
**Fix**: `chmod 755 jsondata/user_cards/`

### Issue: Encryption errors
**Check**: Key lengths in `config.php`
**Fix**: Ensure both keys are exactly 32 characters

### Issue: Payment API signature validation fails
**Check**: Payload sorting and JSON encoding
**Fix**: See `examples/payment_api_example.php` for reference

### Issue: Session not persisting
**Check**: PHP session configuration
**Fix**: Verify `session.save_path` is writable

## Performance Optimization

### For High Traffic

1. **Enable OPcache** in `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
```

2. **Use a reverse proxy** (Nginx) in front of Apache

3. **Consider migrating** to a database for production scale:
   - Replace JSON file storage with MySQL/PostgreSQL
   - Keep the same function signatures
   - Update `functions/file_storage.php` only

## Scaling Considerations

The current JSON file-based storage works well for:
- Development and testing
- Small to medium deployments (< 10,000 users)
- Prototype/MVP phase

For large-scale production:
- Migrate to a relational database
- Implement caching (Redis/Memcached)
- Use queue systems for loan processing
- Consider microservices architecture

## Support

For issues and questions:
- Check the README.md for API documentation
- Review log files in `/jsondata/logs/`
- Run test suite with `php test.php`
- Open an issue on GitHub

## Security Considerations

1. **Never expose** the secret API key
2. **Rotate keys** periodically
3. **Monitor logs** for suspicious activity
4. **Use HTTPS** in production
5. **Implement rate limiting** on Payment API
6. **Validate all inputs** (system does this, but verify)
7. **Keep PHP updated** for security patches

---

Last Updated: 2024-11-16
Version: 1.0.0
