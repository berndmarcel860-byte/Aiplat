<?php
// admin_ajax/mailer_smtp.php — CRUD for SMTP accounts
require_once '../admin_session.php';
header('Content-Type: application/json');
if (!is_admin_logged_in()) { echo json_encode(['ok' => false, 'message' => 'Unauthorized']); exit; }

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {

        case 'list':
            $rows = $pdo->query(
                "SELECT id, label, host, port, encryption, username, from_email, from_name,
                        is_active, emails_sent, last_used_at
                   FROM mailer_smtp_accounts ORDER BY id ASC"
            )->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'accounts' => $rows]);
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT id,label,host,port,encryption,username,from_email,from_name,is_active FROM mailer_smtp_accounts WHERE id=?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) { echo json_encode(['ok' => false, 'message' => 'Not found']); break; }
            echo json_encode(['ok' => true, 'account' => $row]);
            break;

        case 'create':
        case 'update':
            $id         = (int)($_POST['id'] ?? 0);
            $label      = trim($_POST['label']      ?? '');
            $host       = trim($_POST['host']       ?? '');
            $port       = (int)($_POST['port']       ?? 587);
            $enc        = in_array($_POST['encryption'] ?? '', ['tls','ssl','none']) ? $_POST['encryption'] : 'tls';
            $username   = trim($_POST['username']   ?? '');
            $password   = $_POST['password']        ?? '';
            $fromEmail  = trim($_POST['from_email'] ?? '');
            $fromName   = trim($_POST['from_name']  ?? '');
            $isActive   = (int)($_POST['is_active'] ?? 1);

            if (!$host || !$username || !$fromEmail) {
                echo json_encode(['ok' => false, 'message' => 'Host, username and from_email are required.']);
                break;
            }
            if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['ok' => false, 'message' => 'Invalid from_email address.']);
                break;
            }

            if ($action === 'create') {
                if (!$password) { echo json_encode(['ok' => false, 'message' => 'Password is required for new accounts.']); break; }
                $stmt = $pdo->prepare(
                    "INSERT INTO mailer_smtp_accounts (label,host,port,encryption,username,password,from_email,from_name,is_active)
                     VALUES (?,?,?,?,?,?,?,?,?)"
                );
                $stmt->execute([$label,$host,$port,$enc,$username,$password,$fromEmail,$fromName,$isActive]);
                echo json_encode(['ok' => true, 'message' => 'SMTP account added.']);
            } else {
                if ($password) {
                    $stmt = $pdo->prepare(
                        "UPDATE mailer_smtp_accounts SET label=?,host=?,port=?,encryption=?,username=?,password=?,from_email=?,from_name=?,is_active=? WHERE id=?"
                    );
                    $stmt->execute([$label,$host,$port,$enc,$username,$password,$fromEmail,$fromName,$isActive,$id]);
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE mailer_smtp_accounts SET label=?,host=?,port=?,encryption=?,username=?,from_email=?,from_name=?,is_active=? WHERE id=?"
                    );
                    $stmt->execute([$label,$host,$port,$enc,$username,$fromEmail,$fromName,$isActive,$id]);
                }
                echo json_encode(['ok' => true, 'message' => 'SMTP account updated.']);
            }
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("DELETE FROM mailer_smtp_accounts WHERE id=?")->execute([$id]);
            echo json_encode(['ok' => true, 'message' => 'Account deleted.']);
            break;

        default:
            echo json_encode(['ok' => false, 'message' => 'Unknown action.']);
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
