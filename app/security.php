<?php include 'header.php'; ?>
<?php
// Fetch login history for the current user
$loginHistory = [];
$activeSessionCount = 0;

if (!empty($_SESSION['user_id'])) {
    try {
        $histStmt = $pdo->prepare(
            "SELECT ip_address, user_agent, success, attempted_at, city, country
             FROM login_logs
             WHERE user_id = ?
             ORDER BY attempted_at DESC
             LIMIT 50"
        );
        $histStmt->execute([$_SESSION['user_id']]);
        $loginHistory = $histStmt->fetchAll(PDO::FETCH_ASSOC);

        // Count active sessions (online_users table)
        $sessStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM online_users
             WHERE user_id = ? AND last_activity > DATE_SUB(NOW(), INTERVAL 30 MINUTE)"
        );
        $sessStmt->execute([$_SESSION['user_id']]);
        $activeSessionCount = (int)$sessStmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('security.php: ' . $e->getMessage());
    }
}

// Current session info
$currentIp    = $_SERVER['REMOTE_ADDR'] ?? '';
$currentAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
?>

<div class="main-content">
    <div class="container-fluid">

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="background:linear-gradient(135deg,#1a2a6c 0%,#2950a8 55%,#2da9e3 100%);color:#fff;border-radius:14px;overflow:hidden;">
                    <div class="card-body py-4 px-4">
                        <div class="d-flex align-items-center">
                            <div style="width:52px;height:52px;background:rgba(255,255,255,0.18);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;margin-right:16px;">
                                <i class="anticon anticon-safety"></i>
                            </div>
                            <div>
                                <h2 class="mb-1 text-white" style="font-weight:700;">Sicherheit & Aktivitätslog</h2>
                                <p class="mb-0" style="color:rgba(255,255,255,0.85);font-size:14px;">
                                    Übersicht aller Anmeldevorgänge und aktiver Sitzungen
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Session -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #28a745;">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div style="width:38px;height:38px;background:linear-gradient(135deg,#28a745,#5cd872);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;margin-right:12px;flex-shrink:0;">
                                <i class="anticon anticon-check-circle"></i>
                            </div>
                            <h6 class="mb-0" style="font-weight:700;color:#2c3e50;">Aktuelle Sitzung</h6>
                        </div>
                        <div style="font-size:13px;color:#555;">
                            <div class="mb-1"><span style="font-weight:600;">IP:</span> <?= htmlspecialchars($currentIp, ENT_QUOTES) ?></div>
                            <div class="text-truncate" title="<?= htmlspecialchars($currentAgent, ENT_QUOTES) ?>">
                                <span style="font-weight:600;">Browser:</span>
                                <?php
                                // Simple browser detection
                                $agent = $currentAgent;
                                if (str_contains($agent, 'Firefox')) $browserLabel = 'Mozilla Firefox';
                                elseif (str_contains($agent, 'Chrome') && !str_contains($agent, 'Edg')) $browserLabel = 'Google Chrome';
                                elseif (str_contains($agent, 'Safari') && !str_contains($agent, 'Chrome')) $browserLabel = 'Apple Safari';
                                elseif (str_contains($agent, 'Edg')) $browserLabel = 'Microsoft Edge';
                                else $browserLabel = 'Unbekannt';
                                echo htmlspecialchars($browserLabel, ENT_QUOTES);
                                ?>
                            </div>
                            <div class="mt-1">
                                <span class="badge badge-success px-2 py-1">
                                    <i class="anticon anticon-check mr-1"></i>Aktiv
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #2950a8;">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div style="width:38px;height:38px;background:linear-gradient(135deg,#2950a8,#2da9e3);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;margin-right:12px;flex-shrink:0;">
                                <i class="anticon anticon-laptop"></i>
                            </div>
                            <h6 class="mb-0" style="font-weight:700;color:#2c3e50;">Aktive Sitzungen</h6>
                        </div>
                        <div style="font-size:28px;font-weight:700;color:#2950a8;"><?= $activeSessionCount ?></div>
                        <div style="font-size:12px;color:#888;">in den letzten 30 Minuten</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #ffc107;">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div style="width:38px;height:38px;background:linear-gradient(135deg,#ffc107,#ffdb4d);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;margin-right:12px;flex-shrink:0;">
                                <i class="anticon anticon-clock-circle"></i>
                            </div>
                            <h6 class="mb-0" style="font-weight:700;color:#2c3e50;">Letzter Login</h6>
                        </div>
                        <?php if (!empty($loginHistory)): ?>
                        <div style="font-size:13px;color:#555;">
                            <div><?= htmlspecialchars(date('d.m.Y H:i', strtotime($loginHistory[0]['attempted_at'] ?? 'now')), ENT_QUOTES) ?></div>
                            <div><?= htmlspecialchars($loginHistory[0]['ip_address'] ?? '-', ENT_QUOTES) ?></div>
                        </div>
                        <?php else: ?>
                        <div style="font-size:13px;color:#888;">Keine Daten verfügbar</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Login History Table -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0" style="color:#2c3e50;font-weight:700;">
                                <i class="anticon anticon-history mr-2" style="color:var(--brand);"></i>Anmeldeverlauf
                            </h5>
                            <span class="badge badge-secondary px-3 py-2" style="font-size:12px;">
                                Letzte <?= count($loginHistory) ?> Einträge
                            </span>
                        </div>

                        <?php if (empty($loginHistory)): ?>
                        <div class="text-center py-5">
                            <div style="font-size:48px;color:#dee2e6;margin-bottom:12px;">
                                <i class="anticon anticon-safety"></i>
                            </div>
                            <p class="text-muted">Kein Anmeldeverlauf gefunden.</p>
                            <small class="text-muted">Anmeldedaten werden erst nach dem nächsten Login erfasst.</small>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="loginHistoryTable">
                                <thead style="background:#f8f9fa;">
                                    <tr>
                                        <th style="font-size:12px;color:#6c757d;font-weight:600;text-transform:uppercase;border-top:none;">Datum &amp; Uhrzeit</th>
                                        <th style="font-size:12px;color:#6c757d;font-weight:600;text-transform:uppercase;border-top:none;">IP-Adresse</th>
                                        <th style="font-size:12px;color:#6c757d;font-weight:600;text-transform:uppercase;border-top:none;">Browser / Gerät</th>
                                        <th style="font-size:12px;color:#6c757d;font-weight:600;text-transform:uppercase;border-top:none;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($loginHistory as $log): ?>
                                    <?php
                                    $ua = $log['user_agent'] ?? '';
                                    if (str_contains($ua, 'Firefox')) $br = 'Firefox';
                                    elseif (str_contains($ua, 'Chrome') && !str_contains($ua, 'Edg')) $br = 'Chrome';
                                    elseif (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) $br = 'Safari';
                                    elseif (str_contains($ua, 'Edg')) $br = 'Edge';
                                    else $br = 'Unbekannt';

                                    $isMobile = str_contains($ua, 'Mobile') || str_contains($ua, 'Android') || str_contains($ua, 'iPhone');
                                    $deviceIcon = $isMobile ? 'anticon-mobile' : 'anticon-laptop';
                                    $isCurrentIp = ($log['ip_address'] === $currentIp);
                                    ?>
                                    <tr>
                                        <td style="font-size:13px;vertical-align:middle;">
                                            <?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($log['attempted_at'])), ENT_QUOTES) ?>
                                        </td>
                                        <td style="font-size:13px;vertical-align:middle;">
                                            <span style="font-family:monospace;"><?= htmlspecialchars($log['ip_address'] ?? '-', ENT_QUOTES) ?></span>
                                            <?php if ($isCurrentIp): ?>
                                            <span class="badge badge-success badge-sm ml-1" style="font-size:10px;">Aktiv</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size:13px;vertical-align:middle;">
                                            <i class="anticon <?= $deviceIcon ?> mr-1" style="color:#6c757d;"></i>
                                            <?= htmlspecialchars($br, ENT_QUOTES) ?>
                                        </td>
                                        <td style="vertical-align:middle;">
                                            <?php if ((int)$log['success'] === 1): ?>
                                            <span class="badge badge-success px-2 py-1" style="font-size:11px;">
                                                <i class="anticon anticon-check mr-1"></i>Erfolgreich
                                            </span>
                                            <?php else: ?>
                                            <span class="badge badge-danger px-2 py-1" style="font-size:11px;">
                                                <i class="anticon anticon-close mr-1"></i>Fehlgeschlagen
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
.table-hover tbody tr:hover { background-color: rgba(41,80,168,0.04); }
</style>

<?php include 'footer.php'; ?>
