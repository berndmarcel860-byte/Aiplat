# Novalnet AI — Bulk SMTP Mailer

A self-contained PHP bulk-mail system for outbound lead-generation campaigns.
No Composer or external libraries required — pure PHP 8+, socket-based SMTP.

## Directory layout

```
mailer/
├── SmtpClient.php          Lightweight pure-PHP SMTP socket client
├── BulkMailer.php          Rotating account manager + campaign runner
├── smtp_accounts.php       10 SMTP account credentials (edit before use)
├── email_template.php      Professional HTML email template builder
├── send_campaign.php       CLI / web campaign runner
├── recipients_sample.csv   Sample recipients CSV format
└── README.md               This file
```

## Quick start

### 1 · Configure SMTP accounts

Edit `smtp_accounts.php` and replace every placeholder with real credentials:

```php
[
    'host'       => 'smtp.yourmailprovider.com',
    'port'       => 587,
    'encryption' => 'tls',        // 'tls' (STARTTLS), 'ssl' (smtps), 'none'
    'username'   => 'noreply@yourdomain.com',
    'password'   => 'supersecret',
    'from_email' => 'noreply@yourdomain.com',
    'from_name'  => 'Novalnet AI',
],
```

Good choices for transactional SMTP: **Mailgun**, **SendGrid**, **Brevo (Sendinblue)**,
**Amazon SES**, or any standard ISP/hosting SMTP.

### 2 · Prepare your recipients list

Copy `recipients_sample.csv` → `recipients.csv` and fill in real addresses:

```
email,name
customer@example.com,Max Mustermann
```

One recipient per line. The `name` column is optional.

### 3 · Run the campaign (CLI — recommended)

```bash
php mailer/send_campaign.php \
    --recipients=mailer/recipients.csv \
    --subject="Krypto-Analyse – Unverbindliche Ersteinschätzung" \
    --emails-per-account=3 \
    --pause=60 \
    --reply-to=contact@novalnet-ai.de \
    --cta-url=https://novalnet-ai.de/kontakt.php
```

| Option | Default | Description |
|---|---|---|
| `--recipients` | `recipients.csv` | Path to CSV file |
| `--subject` | *(default DE subject)* | Email subject line |
| `--emails-per-account` | `3` | Emails sent before switching SMTP account |
| `--pause` | `60` | Seconds to sleep between account switches |
| `--reply-to` | *(none)* | Reply-To address |
| `--cta-url` | kontakt.php | Primary call-to-action URL |

### 4 · How rotation works

```
Account 1 → send email 1 · send email 2 · send email 3
  → sleep 60 s
Account 2 → send email 4 · send email 5 · send email 6
  → sleep 60 s
Account 3 → …
  → (cycles back to account 1 after all 10 are used)
```

This keeps each sending address well below typical ISP/ESP hourly rate limits
and distributes reputation load across multiple IPs/domains.

## Deliverability checklist

Before sending, ensure each sending domain has:

- [ ] **SPF** record — authorises your SMTP server IP  
  `v=spf1 include:mailprovider.com ~all`
- [ ] **DKIM** — enabled in your ESP dashboard; public key published in DNS
- [ ] **DMARC** — `p=none` at minimum, preferably `p=quarantine`  
  `v=DMARC1; p=quarantine; rua=mailto:dmarc@yourdomain.com`
- [ ] **Reverse DNS (rDNS)** — sending IP resolves to your domain
- [ ] **Unsubscribe link** — already included in the template (GDPR / CAN-SPAM)
- [ ] **Plain-text alternative** — auto-generated from HTML by SmtpClient

## Security notes

- **Never commit** `smtp_accounts.php` with real passwords to version control.  
  Add it to `.gitignore` once credentials are filled in.
- Web access to `send_campaign.php` is protected by `WEB_ACCESS_TOKEN`.  
  Alternatively, block it via `.htaccess`:
  ```apache
  <Files "send_campaign.php">
      Require all denied
  </Files>
  ```
- The `mailer/` directory should **not** be publicly browsable.  
  Add `Options -Indexes` or an `.htaccess` with `Deny from all`.
- Log file `mailer.log` is written inside the `mailer/` directory;
  ensure the web-server user has write permission.

## Customising the email template

Edit `email_template.php` → `EmailTemplate::build()`.  
The method accepts an options array:

```php
EmailTemplate::build([
    'cta_url'         => 'https://novalnet-ai.de/kontakt.php',
    'unsubscribe_url' => 'https://novalnet-ai.de/unsubscribe.php?email={email}',
    'brand_name'      => 'Novalnet AI',
    'site_url'        => 'https://novalnet-ai.de',
    'contact_email'   => 'contact@novalnet-ai.de',
    'company_address' => 'Novalnet AI GmbH · BaFin-reg. · Deutschland',
]);
```

Personalisation placeholders available in subject and body:

| Placeholder | Value |
|---|---|
| `{first_name}` | First name of recipient |
| `{name}` | Full name |
| `{email}` | Recipient email address |

## Requirements

- PHP 8.0+
- Extensions: `openssl` (for TLS/SSL), `filter` (built-in)
- Outbound TCP to SMTP ports 587 / 465 / 25 from your server

## Licence

Internal use only — Novalnet AI GmbH.
