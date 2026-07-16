# Aiplat — Professional AI Refund Recovery & Payment Security Platform

[![License: Proprietary](https://img.shields.io/badge/License-Proprietary-red.svg)]()
[![PHP 8.0+](https://img.shields.io/badge/PHP-8.0+-blue.svg)]()
[![Status: Production Ready](https://img.shields.io/badge/Status-Production%20Ready-green.svg)]()

> A secure, compliant platform for recovering lost funds through AI-powered analysis and transparent transaction tracking. Built with payment security guardrails, international AML compliance, and user trust at its core.

---

## 🎯 Features at a Glance

### 💰 **Intelligent Refund Recovery**
- **AI-powered case analysis** — automated detection and recovery of lost funds
- **Multiple recovery channels** — crypto refunds, bank transfers, escrow settlements
- **Real-time tracking** — users see live fund status and recovery progress
- **Transparent fee structure** — percentage-based international partner fees (AML required)

### 🔒 **Bank-Grade Payment Security**
- **Automatic payment address interception** — blocks admin attempts to redirect users to external wallets
- **All-channel protection** — live chat, support tickets, direct emails, bulk emails
- **Superadmin alerts** — German-language notifications of all blocked attempts
- **Security audit log** — full history of intercepted addresses with reversal proof

### ✅ **International AML/Compliance**
- **Satoshi Test verification** — cryptographic proof of wallet ownership before large withdrawals
- **KYC/KYT integration** — know-your-customer and know-your-transaction screening
- **Escrow account system** — deposits held pending verification, configurable release period
- **Transaction fee tracking** — all fees logged and attributable to compliance partners
- **Multi-currency support** — EUR primary, with crypto conversion handling

### 📊 **Advanced Admin Dashboard**
- **Real-time statistics** — pending withdrawals, deposits, KYC verifications, Satoshi tests
- **Payment security alerts** — red-banner warnings with one-click review access
- **User management** — bulk email, individual messaging, account lifecycle
- **Dispute resolution** — refund claims, evidence review, escrow release
- **Audit trails** — every admin action logged with timestamp, user, and change record

### 👥 **User Portal & Communication**
- **Onboarding flow** — 4-step setup (platform selection, location, bank details, Satoshi test)
- **Live support chat** — WebRTC peer-to-peer voice calls + secure messaging
- **Email templates** — segmented campaigns, open tracking, personalisation
- **Payment methods** — multiple crypto addresses, IBAN management with verification
- **Dashboard** — funds recovered, pending status, escrow countdown, fee history

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.2+
- OpenSSL (for TLS/SSL encryption)
- cURL (for API integrations)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/berndmarcel860-byte/Aiplat.git
   cd Aiplat
   ```

2. **Set up the database**
   ```bash
   # Run all idempotent migrations (safe to run multiple times)
   mysql -u <user> -p <database> < database/email_logs_template_key.sql
   mysql -u <user> -p <database> < database/email_tracking.sql
   mysql -u <user> -p <database> < database/payment_security_alerts.sql
   mysql -u <user> -p <database> < database/tg_settings.sql
   mysql -u <user> -p <database> < database/otp_grace.sql
   ```

   Or from **phpMyAdmin**: SQL tab → copy/paste each `.sql` file contents → execute.

3. **Configure environment**
   - Copy `.env.example` to `.env` (if present) or set database credentials in `config/db.php`
   - Set admin email and platform domain in admin settings
   - Configure SMTP for transactional emails (see **Mailer Setup** below)

4. **Initialize admin account**
   - Visit `admin/admin_login.php` 
   - Use default credentials (change immediately after login)
   - Navigate to Settings → change password, update domain/branding

5. **Deploy to production**
   - Use HTTPS only (set in `config/security.php`)
   - Restrict `mailer/`, `database/`, and admin directories to authorized IPs
   - Enable database backups (daily recommended)
   - Configure uptime monitoring on `/health.php` endpoint

---

## 📋 Core Workflows

### User Registration & Onboarding

**Flow:** Signup → Email OTP verification → 4-step onboarding → Satoshi test (if >€50k) → Ready to submit cases

**Step 1: Platform Selection**
- User selects which platform they lost funds on (exchange, wallet, DeFi)
- Amount input in EUR with live conversion preview

**Step 2: Location & Address**
- Country dropdown (tax/AML jurisdiction)
- Full address collected for KYC on large withdrawals

**Step 3: Bank Details**
- IBAN/BIC for EUR withdrawals
- Encrypted and stored for later transfer
- Verified by micro-deposit (optional)

**Step 4: Satoshi Test** (if recovery >€50k)
- User receives unique Bitcoin/Ethereum amount
- User sends test transaction to platform address
- System verifies receipt and amount match
- Unlocks withdrawal capability

**Pages:**
- `onboarding.php` — standard flow
- `onboarding_satoshi.php` — 4-step flow when packages disabled (redesigned in this PR)

### Deposit & Escrow

**User deposits recovered funds via:**
- Bank transfer (manual, admin-approved)
- Crypto payment (auto-verified by blockchain)
- Top-up credits (USD/EUR pre-purchase)

**Upon deposit:**
1. Amount logged to `escrow_accounts` table with `held_at` timestamp
2. **Escrow hold period** (configurable, typically 5-7 business days) begins
3. User receives confirmation; admin sees "Pending Deposits" alert
4. User email: "Your deposit is being verified; funds will be available on [date]"
5. Admin reviews → clicks "Verify & Release" → funds move to user balance

**Pages:**
- `deposit.php` — user deposit request form
- `admin/admin_deposits.php` — admin deposit review/approval
- `payment-methods.php` — user's saved payment addresses

### Withdrawal Request

**User initiates withdrawal:**

1. **Form submission** — amount, bank account or wallet address
2. **Platform verification** — checks against `payment_methods` table (must be pre-registered or Satoshi-verified)
3. **Fee calculation** — percentage fee (international partner requirement, logged for audit)
4. **Fee payment** — user receives invoice, uploads proof (screenshot, PDF)
5. **Admin review** — 2–4 hours typical (dashboard alert shows pending)
6. **Release** — admin approves, funds sent to user's address
7. **Confirmation** — user notified by email + SMS (Telegram optional)

**Security checks:**
- ❌ Unauthorized wallet address → silently replaced with platform address (logged to `payment_security_alerts`)
- ❌ Satoshi test failed for withdrawals >€50k → blocked with reason message
- ❌ AML screening fails → flagged for manual review, no auto-release

**Pages:**
- `withdrawal.php` — user withdrawal form
- `admin/admin_withdrawals.php` — admin approval queue
- `admin/admin_payment_security.php` — review blocked address attempts (NEW in this PR)

---

## 🔐 Payment Security Guardrails (NEW)

### Automatic Address Interception

Every admin message sent to any user is scanned for unauthorized crypto addresses (BTC, ETH, LTC, TRON, XRP) and IBANs **before delivery**.

- ✅ Address in `payment_methods` table? → Message sent as-is
- ❌ Address NOT registered? → Silently replaced with platform address, alert logged

**Channels protected:**
| Channel | File | Status |
|---------|------|--------|
| Support ticket replies | `process_ticket_reply.php` | ✅ Existing |
| Live chat | `admin_ajax/chat_send.php` | ✅ NEW |
| Direct user emails | `admin_ajax/send_user_email.php` | ✅ NEW |
| Universal/custom emails | `admin_ajax/send_universal_email.php` | ✅ NEW |
| Bulk emails to all users | `admin_ajax/send_all_users_email.php` | ✅ NEW |

### Security Alert System

**Interception logging:**
- Persisted to `payment_security_alerts` table
- Superadmin receives German email immediately:
  ```
  Sicherheitswarnung: Admin [name] versuchte, Nutzer [user_id] an externe Adresse zu leiten
  Kanal: Live Chat
  Blockierte Adressen: 0xabc123..., bc1qde456...
  Original-Nachricht: [exact text]
  Status: Ersetzt und zugestellt
  ```

### User-Facing German Notices

**Sticky banner** (every user page via `header.php`):
```
⚠️ Zahlungssicherheit: Wir werden Sie niemals bitten, Geld an externe Adressen zu senden. 
Alle Zahlungen erfolgen nur über die offizielle Einzahlungsseite.
```

**Full security card** (on `deposit.php`, `payment-methods.php`, `support.php`):
```
Sicherheitsleitlinien für Zahlungen
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✗ Wir werden Sie NIEMALS bitten, Geld an externe Adressen zu senden
✗ Zahlungen außerhalb der Plattform werden nicht bearbeitet
✓ Nutzen Sie nur die offizielle Einzahlungsseite
✓ Alle Zahlungsadressen sind auf dieser Seite registriert
```

### Admin Security Alerts Page

**Access:** Admin sidebar → Security Center → Zahlungssicherheit

**Features:**
- Table of all intercepted attempts (date, admin, channel, user, addresses)
- Side-by-side comparison: original message vs. filtered message
- Mark as reviewed individually or in bulk
- Add internal notes ("Admin was testing", "Wallet was unofficial", etc.)
- Unread count badge in sidebar for quick identification

**Pages:**
- `admin/admin_payment_security.php` — view/manage alerts
- `admin/admin_ajax/get_security_alerts.php` — AJAX endpoint for alerts list
- `admin/admin_dashboard.php` — red critical alert + "Jetzt prüfen" button

---

## 💳 Transaction Fees & Compliance

### Why fees are charged

**Deposit fee** (user-initiated, charged at deposit confirmation)
- Covers platform processing, KYC screening, bank transfer fees
- Percentage-based (configurable per currency, typically 2–5%)
- Charged to top-up balance or deducted from recovered funds

**Withdrawal fee** (required by international financial partners)
- **AML/Fraud compliance cost** — mandated by partner banks for large transfers
- **International wire transfer cost** — processing by correspondent banks
- Percentage-based (configurable, typically 3–7% depending on amount/jurisdiction)
- User receives invoice before confirming withdrawal
- Proof of payment (screenshot/PDF) required before admin approval
- **Logged for audit** — every fee charge is attributable to compliance requirements

### Fee Configuration

In **Admin Panel** → Settings → Financial:
```
Deposit fee: 3.5%
Withdrawal fee (deposits): 5%
Withdrawal fee (crypto): 4%
AML threshold: €50,000
Satoshi test threshold: €50,000
Escrow hold period: 7 days
```

All fees visible to users before confirmation.

---

## 📊 Admin Dashboard Sections

### Welcome & Alerts

- **Personal greeting** — "Welcome back, [First Name]"
- **Critical payment security alert** (if unreviewed interceptions exist)
  - Red banner: "[N] unreviewed security alerts — suspicious payment redirects blocked"
  - Quick link: "Jetzt prüfen" → security alerts page
- **Pending items alert** — withdrawals, deposits, KYC, Satoshi tests

### Key Statistics

```
┌─ Fund Recovery Rate: 78.5%
├─ Total Reported: €2,450,000
├─ Total Recovered: €1,923,250
├─ This Month: €315,000 recovered
└─ Average Case Time: 14 days
```

### Pending Items Queue

- **7 pending withdrawals** → admin_withdrawals.php
- **3 pending deposits** → admin_deposits.php
- **2 pending KYC** → admin_kyc.php
- **1 pending Satoshi test** → admin_satoshi_tests.php

### Quick Access Navigation

- Users, Cases, Payments, Email, Support Tickets, Security, Settings

---

## 🗄️ Database Schema Highlights

### Core Tables

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| `users` | User accounts | id, email, password_hash, first_name, kyc_status, otp_verified_at |
| `recovered_funds` | Case records | user_id, amount_eur, platform, status, ai_analysis_score |
| `escrow_accounts` | Deposit holds | user_id, amount, held_at, released_at, verified_by_admin |
| `payment_methods` | Verified addresses | user_id, type (iban/crypto), address, verified_at, is_primary |
| `satoshi_tests` | Wallet verification | user_id, crypto_type, test_amount, txn_hash, verified_at, status |
| `withdrawals` | Withdrawal requests | user_id, amount, fee_amount, fee_paid_proof, status, released_at |
| `payment_security_alerts` | Blocked redirects | admin_id, channel, user_id, original_message, filtered_message, blocked_addresses, reviewed_at |
| `email_logs` | Transactional emails | recipient_id, template_key, status, opened_at, tracking_token |
| `admin_action_logs` | Audit trail | admin_id, action, table_name, record_id, old_value, new_value, created_at |

### Important Indexes

```sql
CREATE INDEX idx_user_status ON recovered_funds(user_id, status);
CREATE INDEX idx_satoshi_pending ON satoshi_tests(status, user_id);
CREATE INDEX idx_escrow_held ON escrow_accounts(status, held_at);
CREATE INDEX idx_security_alerts_unreviewed ON payment_security_alerts(reviewed_at, created_at);
```

---

## 🔧 Configuration Files

### Security (`config/security.php`)
```php
define('ADMIN_SESSION_TIMEOUT', 1800); // 30 minutes
define('LOGIN_ATTEMPT_LIMIT', 5);
define('FORCE_HTTPS', true);
define('SECURE_COOKIES', true);
```

### Payment (`config/payment.php`)
```php
define('DEPOSIT_FEE_PERCENT', 3.5);
define('WITHDRAWAL_FEE_PERCENT', 5.0);
define('AML_THRESHOLD_EUR', 50000);
define('SATOSHI_TEST_THRESHOLD_EUR', 50000);
define('ESCROW_HOLD_DAYS', 7);
```

### Email (`config/email.php`)
```php
define('SMTP_HOST', 'smtp.yourmailprovider.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@aiplat.com');
define('FROM_NAME', 'Aiplat — Fund Recovery');
```

---

## 📧 Bulk Email System (Mailer)

Self-contained bulk-mail system for outbound recovery campaigns. No Composer required — pure PHP 8+, socket-based SMTP.

### Quick Start

1. **Configure SMTP accounts** (`mailer/smtp_accounts.php`)
   ```php
   [
       'host'       => 'smtp.mailgun.org',
       'port'       => 587,
       'encryption' => 'tls',
       'username'   => 'noreply@aiplat.com',
       'password'   => 'your_api_key',
       'from_email' => 'noreply@aiplat.com',
       'from_name'  => 'Aiplat Recovery Team',
   ],
   ```

2. **Prepare recipients** (`mailer/recipients.csv`)
   ```
   email,name
   victim@example.com,John Doe
   another@example.com,Jane Smith
   ```

3. **Run campaign** (CLI)
   ```bash
   php mailer/send_campaign.php \
       --recipients=mailer/recipients.csv \
       --subject="Deine Krypto-Rückforderung — kostenlose Erstanalyse" \
       --emails-per-account=3 \
       --pause=60 \
       --reply-to=support@aiplat.com \
       --cta-url=https://aiplat.com/register
   ```

**Features:**
- ✅ Rotating account management (distributes sending across 10 accounts)
- ✅ Automated pause between account switches (avoids rate limits)
- ✅ Open tracking (pixel-based, opt-out link included)
- ✅ Plain-text + HTML alternatives
- ✅ SPF/DKIM/DMARC compatible
- ✅ GDPR unsubscribe link (auto-included)

See `mailer/README.md` for full documentation.

---

## 🏗️ Architecture

### Frontend Stack
- **Bootstrap 5** — responsive UI components
- **jQuery** — DOM manipulation, AJAX
- **Chart.js** — real-time statistics
- **WebRTC** — peer-to-peer voice calls (live support)
- **Ant Design Icons** — UI icons

### Backend Stack
- **PHP 8.0+** — application logic
- **MySQL/MariaDB** — data persistence
- **PHPMailer / SMTP** — email delivery
- **JWT** — admin session management (optional OAuth2-ready)
- **Telegram Bot API** — optional SMS/call notifications

### Security Layers
- **Password hashing** — bcrypt (PHP's `password_hash()`)
- **SQL parameterization** — all queries use prepared statements
- **XSS protection** — output encoding with `htmlspecialchars()`
- **CSRF tokens** — on all forms
- **Rate limiting** — login attempts, API endpoints
- **HTTPS enforcement** — redirects HTTP to HTTPS
- **Secure cookies** — httpOnly, Secure flags set

---

## 🚨 Deployment Checklist

Before going live, ensure:

- [ ] **Database encrypted at rest** (depends on hosting provider)
- [ ] **Backups automated** (daily, retain 30-day history)
- [ ] **HTTPS certificate** (Let's Encrypt or CA-signed)
- [ ] **Admin directory** restricted to office IPs via `.htaccess` or firewall
- [ ] **Mailer directory** completely hidden (`Options -Indexes` or `Deny from all`)
- [ ] **Database directory** not publicly accessible
- [ ] **Error logging** enabled, errors not shown to users
- [ ] **SMTP credentials** set in environment variables, NOT in code
- [ ] **Uptime monitoring** configured (PagerDuty, Uptime Robot, etc.)
- [ ] **Log retention** set (minimum 90 days for compliance)
- [ ] **Security headers** set (X-Frame-Options, Content-Security-Policy, etc.)
- [ ] **Database backups tested** (restore to staging, verify data integrity)
- [ ] **Admin email** changed from defaults
- [ ] **API keys** (Telegram, payment processors) rotated annually

---

## 📞 Support & Maintenance

### Monitoring
- Dashboard uptime: `/health.php` (returns 200 OK if database connected)
- Email delivery: Check `email_logs` table for bounce/failure records
- Admin actions: Review `admin_action_logs` for unusual activity
- Payment alerts: Check for unreviewed items in `payment_security_alerts`

### Common Issues

**"Payment address blocked" even though it's registered?**
- Verify address is in `payment_methods` table with `verified_at` not null
- Check it's marked `is_primary = 1` or explicitly allow in filter logic

**Satoshi test not unlocking withdrawal?**
- Verify blockchain txn hash is correct in `satoshi_tests.verified_txn_hash`
- Ensure `status = 'verified'` and `verified_at IS NOT NULL`
- Check recovery amount is above threshold (`SATOSHI_TEST_THRESHOLD_EUR`)

**Bulk emails not sending?**
- Verify SMTP credentials in `mailer/smtp_accounts.php`
- Check DNS records (SPF, DKIM, DMARC) for sending domain
- Review `mailer.log` for connection errors

---

## 📄 Legal & Compliance

This platform handles financial data and crypto transactions. Ensure compliance with:

- **GDPR** — data processing consent, user rights, data retention
- **PSD2** — if handling EUR payments/transfers
- **AML/CFT** — international sanctions screening, transaction reporting
- **KYC** — identity verification per local banking regulations
- **Terms of Service** — explicitly state fees, escrow hold periods, dispute process
- **Privacy Policy** — explain data collection, third-party sharing, retention

---

## 📜 License

Proprietary — Internal use only. Aiplat GmbH.

---

## 👥 Support

For bugs, feature requests, or security issues:
- **GitHub Issues:** [Create an issue](https://github.com/berndmarcel860-byte/Aiplat/issues)
- **Email:** support@aiplat.de
- **Security Report:** security@aiplat.de (do NOT open public issues for security findings)

---

## 🎉 Acknowledgments

Built with security and compliance at the core for the global refund recovery community.

**Latest Update:** Payment security guardrails, admin alerts, and user notices (July 2026)
