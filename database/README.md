# Database Migrations

All SQL files in this directory are **idempotent** — safe to run more than once.

---

## email_logs columns — `template_key` & `user_id`

Two columns and two indexes are needed before using `EmailTemplateHelper`:

| Column | Type | Purpose |
|--------|------|---------|
| `template_key` | `VARCHAR(255)` | Key of the email template used |
| `user_id` | `INT UNSIGNED` | Recipient user ID |

### Which file to use?

| Scenario | File to run |
|----------|-------------|
| **phpMyAdmin** (SQL tab) | `email_logs_template_key_phpmyadmin.sql` |
| **MySQL CLI** (`mysql` command) | `email_logs_template_key.sql` |
| **MariaDB** (any client) | Either file works |

### Running from phpMyAdmin

1. Open **phpMyAdmin** and select your database.
2. Click the **SQL** tab.
3. Open `database/email_logs_template_key_phpmyadmin.sql`, copy the entire contents.
4. Paste into the SQL tab and click **Go**.

Each step prints a message — either "OK" (column/index added) or "… already exists — skipped".

### Running from the MySQL command line

```bash
mysql -u <user> -p <database_name> < database/email_logs_template_key.sql
```

---

## email_tracking table

Creates the `email_tracking` table and ensures `email_logs` has the `tracking_token`
and `opened_at` columns needed for open-tracking.

```bash
mysql -u <user> -p <database_name> < database/email_tracking.sql
```

---

## Other migrations

| File | Purpose |
|------|---------|
| `tg_settings.sql` | Telegram bot settings table |
| `login_otp_template.sql` | OTP email template seed |
| `otp_grace.sql` | OTP grace-period column |
