<?php
// admin_ajax/mailer_leads.php — CRUD + CSV import for leads
require_once '../admin_session.php';
header('Content-Type: application/json');
if (!is_admin_logged_in()) { echo json_encode(['ok' => false, 'message' => 'Unauthorized']); exit; }

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {

        case 'list':
            $rows = $pdo->query(
                "SELECT id, email, name, source, tags, status, added_at
                   FROM mailer_leads ORDER BY id DESC LIMIT 1000"
            )->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'leads' => $rows]);
            break;

        case 'get':
            $id   = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT id,email,name,source,tags,status FROM mailer_leads WHERE id=?");
            $stmt->execute([$id]);
            $row  = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) { echo json_encode(['ok' => false, 'message' => 'Not found']); break; }
            echo json_encode(['ok' => true, 'lead' => $row]);
            break;

        case 'create':
        case 'update':
            $id     = (int)($_POST['id'] ?? 0);
            $email  = strtolower(trim($_POST['email']  ?? ''));
            $name   = trim($_POST['name']   ?? '');
            $source = trim($_POST['source'] ?? 'manual');
            $tags   = trim($_POST['tags']   ?? '');
            $status = in_array($_POST['status'] ?? '', ['active','unsubscribed','bounced','invalid'])
                      ? $_POST['status'] : 'active';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['ok' => false, 'message' => 'Invalid email address.']);
                break;
            }

            if ($action === 'create') {
                // Ignore duplicate
                $stmt = $pdo->prepare(
                    "INSERT IGNORE INTO mailer_leads (email,name,source,tags,status) VALUES (?,?,?,?,?)"
                );
                $stmt->execute([$email,$name,$source,$tags,$status]);
                if ($stmt->rowCount() === 0) {
                    echo json_encode(['ok' => false, 'message' => 'Email already exists in leads.']);
                } else {
                    echo json_encode(['ok' => true, 'message' => 'Lead added.']);
                }
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE mailer_leads SET email=?,name=?,source=?,tags=?,status=? WHERE id=?"
                );
                $stmt->execute([$email,$name,$source,$tags,$status,$id]);
                echo json_encode(['ok' => true, 'message' => 'Lead updated.']);
            }
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("DELETE FROM mailer_leads WHERE id=?")->execute([$id]);
            echo json_encode(['ok' => true, 'message' => 'Lead deleted.']);
            break;

        case 'import':
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['ok' => false, 'message' => 'No file uploaded or upload error.']);
                break;
            }
            $source = trim($_POST['source'] ?? 'csv-import');
            $tags   = trim($_POST['tags']   ?? '');
            $tmpFile = $_FILES['csv_file']['tmp_name'];

            $handle  = fopen($tmpFile, 'r');
            $inserted = 0;
            $skipped  = 0;
            $first    = true;

            $stmt = $pdo->prepare(
                "INSERT IGNORE INTO mailer_leads (email,name,source,tags,status) VALUES (?,?,?,?,'active')"
            );

            while (($cols = fgetcsv($handle)) !== false) {
                // Skip header row
                if ($first) {
                    $first = false;
                    if (strtolower(trim($cols[0] ?? '')) === 'email') continue;
                }
                $email = strtolower(trim($cols[0] ?? ''));
                $name  = trim($cols[1] ?? '');
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $skipped++; continue; }
                $stmt->execute([$email, $name, $source, $tags]);
                if ($stmt->rowCount() > 0) $inserted++;
                else $skipped++;
            }
            fclose($handle);
            echo json_encode(['ok' => true, 'message' => "Imported $inserted leads. Skipped (duplicate/invalid): $skipped."]);
            break;

        default:
            echo json_encode(['ok' => false, 'message' => 'Unknown action.']);
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
