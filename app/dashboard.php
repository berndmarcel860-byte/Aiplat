<?php
/**
 * Dashboard - User Main Page (Refactored)
 * 
 * UPDATED: 2026-03-02
 * Branch: copilot/sub-pr-1
 * 
 * Features:
 * - Modular architecture with separate includes
 * - Clean separation of concerns
 * - Easier maintenance and testing
 * 
 * Security: PDO prepared statements, CSRF protection, session validation
 */

// Initialize dashboard (config, session, basic setup)
require_once __DIR__ . '/includes/dashboard-init.php';

// Fetch all dashboard data
require_once __DIR__ . '/includes/dashboard-data.php';
?>

<?php

// Include all modals
require_once __DIR__ . '/includes/dashboard/modals.php';

// Include main dashboard content
require_once __DIR__ . '/includes/dashboard/content.php';

// Include scripts

if (file_exists(__DIR__ . '/footer.php')) {
    require_once __DIR__ . '/footer.php';
}

require_once __DIR__ . '/includes/dashboard/scripts.php';
// Include styles  
require_once __DIR__ . '/includes/dashboard/styles.php';

// Include footer

?>