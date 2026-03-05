<?php
// ajax/bg_status.php
// Lightweight endpoint used by the dashboard to return current AI status, last scan time and user's balance.
// Save this file as ajax/bg_status.php (path relative to your web root).
// Requires session and your existing config.php (PDO $pdo).

session_start();
header('Content-Type: application/json; charset=utf-8; charset=utf-8');

// ensure user is authenticated
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated'
    ]);
    exit;
}

require_once __DIR__ . '/../config.php';

$userId = (int) $_SESSION['user_id'];

try {
    // Get balance from users table
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $balance = $user ? (float)$user['balance'] : 0.00;

    // Try to get a meaningful "last AI scan" timestamp.
    // Prefer ai_scans table if you have it, otherwise fall back to latest case update for the user,
    // finally fallback to current time.
    $lastScan = null;

    // 1) ai_scans (optional)
    try {
        $scanStmt = $pdo->prepare("SELECT MAX(created_at) AS last_scan FROM ai_scans WHERE user_id = ?");
        $scanStmt->execute([$userId]);
        $r = $scanStmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($r['last_scan'])) {
            $lastScan = $r['last_scan'];
        }
    } catch (Exception $e) {
        // table might not exist — ignore and continue
    }

    // 2) fallback to cases.updated_at
    if (empty($lastScan)) {
        $caseStmt = $pdo->prepare("SELECT MAX(updated_at) AS last_scan FROM cases WHERE user_id = ?");
        $caseStmt->execute([$userId]);
        $r2 = $caseStmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($r2['last_scan'])) {
            $lastScan = $r2['last_scan'];
        }
    }

    // 3) final fallback: now
    if (empty($lastScan)) {
        $lastScan = date('Y-m-d H:i:s');
    }

    $payload = [
        'success' => true,
        'aiStatus' => 'Online', // simple default; replace with real check if you have one
        'lastScan' => date('M d, Y H:i', strtotime($lastScan)),
        // send numeric string to avoid JS float quirks
        'balance' => number_format($balance, 2, '.', '')
    ];

    // No caching for freshness
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo json_encode($payload);
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ]);
    exit;
}