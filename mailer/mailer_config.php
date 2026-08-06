<?php
/**
 * mailer_config.php — External database and URL configuration for the mailer subsystem.
 *
 * The mailer bulk-sender and the admin mailer backend can operate against a
 * dedicated database that is completely separate from the main application
 * database.  Set the MAILER_DB_* environment variables to point to that
 * external database; leave them unset to fall back to the main app credentials.
 *
 * Environment variables
 * ─────────────────────
 * MAILER_DB_HOST     — External DB host          (default: DB_HOST → 'localhost')
 * MAILER_DB_PORT     — External DB port          (default: DB_PORT → 3306)
 * MAILER_DB_NAME     — External DB name          (default: DB_NAME → 'novalnet-ai')
 * MAILER_DB_USER     — External DB user          (default: DB_USER → 'novalnet')
 * MAILER_DB_PASSWORD — External DB password      (default: DB_PASSWORD → '')
 * MAILER_BASE_URL    — Base URL for email links  (default: APP_URL → 'https://your-domain.com')
 * MAILER_TIMEZONE    — DB/PHP timezone           (default: APP_TIMEZONE → 'Europe/Berlin')
 */

$mailerHost     = getenv('MAILER_DB_HOST')     ?: (getenv('DB_HOST')     ?: 'localhost');
$mailerPort     = (int)(getenv('MAILER_DB_PORT') ?: (getenv('DB_PORT') ?: 3306));
$mailerDbName   = getenv('MAILER_DB_NAME')     ?: (getenv('DB_NAME')     ?: 'novalnet-ai');
$mailerUser     = getenv('MAILER_DB_USER')     ?: (getenv('DB_USER')     ?: 'novalnet');
$mailerPassword = getenv('MAILER_DB_PASSWORD') ?: (getenv('DB_PASSWORD') ?: '');
$mailerTimezone = getenv('MAILER_TIMEZONE')    ?: (getenv('APP_TIMEZONE') ?: 'Europe/Berlin');

try {
    $mailerPdo = new PDO(
        "mysql:host={$mailerHost};port={$mailerPort};dbname={$mailerDbName};charset=utf8mb4",
        $mailerUser,
        $mailerPassword
    );
    $mailerPdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $mailerPdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $mailerPdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false);

    // Sync MySQL session timezone
    $tzObj  = new DateTimeZone($mailerTimezone);
    $offset = $tzObj->getOffset(new DateTime('now', new DateTimeZone('UTC')));
    $hours  = intdiv($offset, 3600);
    $mins   = abs($offset % 3600) / 60;
    $mailerPdo->exec("SET time_zone = " . $mailerPdo->quote(sprintf('%+03d:%02d', $hours, $mins)));
} catch (PDOException $e) {
    error_log("Mailer database connection failed: " . $e->getMessage());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Mailer DB connection error: " . $e->getMessage() . "\n");
        exit(1);
    }
    die("Mailer database connection error. Please try again later.");
}

// Base URL used in mailer email links (unsubscribe, footer, CTA, etc.)
// MAILER_BASE_URL env var is mandatory when using an external URL.
// Falls back to APP_URL (main app env var) when running against the same server.
if (!defined('MAILER_BASE_URL')) {
    $mailerBaseUrl = getenv('MAILER_BASE_URL') ?: getenv('APP_URL') ?: '';
    define('MAILER_BASE_URL', rtrim($mailerBaseUrl, '/'));
}
