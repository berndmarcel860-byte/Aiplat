<?php
/**
 * mailer_db.php — Provides $mailerPdo for admin AJAX mailer endpoints.
 *
 * Include this file after admin_session.php in every mailer AJAX handler.
 * It connects to the external mailer database (if configured) while session
 * authentication continues to use the main application database ($pdo).
 *
 * Configuration is driven by environment variables — see mailer_config.php.
 */

require_once __DIR__ . '/../../mailer/mailer_config.php';
