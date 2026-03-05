<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$package_id = $_POST['package_id'] ?? null;
$payment_method = $_POST['payment_method'] ?? null;

// Validate
if (!$package_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid package']);
    exit;
}

try {
    // === Load selected package ===
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->execute([$package_id]);
    $pkg = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pkg) {
        echo json_encode(['success' => false, 'message' => 'Package not found']);
        exit;
    }

    $price = (float)$pkg['price'];
    $duration_days = (int)($pkg['duration_days'] ?? 30);
    if ($pkg['price'] == 0) {
        // free trial = 48 hours
        $duration_hours = 48;
    }

    // === Handle payment proof upload (only if paid) ===
    $proofPath = null;
    if ($price > 0 && isset($_FILES['proof_of_payment']) && $_FILES['proof_of_payment']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $ext = strtolower(pathinfo($_FILES['proof_of_payment']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type']);
            exit;
        }

        $dir = __DIR__ . '/../uploads/payments/';
        if (!file_exists($dir)) mkdir($dir, 0755, true);
        $filename = $user_id . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['proof_of_payment']['tmp_name'], $dir . $filename);
        $proofPath = 'uploads/payments/' . $filename;
    }

    // === Expire any existing active packages ===
    $pdo->prepare("UPDATE user_packages SET status='expired' WHERE user_id=? AND status='active'")->execute([$user_id]);

    $pdo->beginTransaction();

    // === Create new subscription ===
    $start = date('Y-m-d H:i:s');
    $end = ($pkg['price'] == 0)
        ? date('Y-m-d H:i:s', strtotime('+48 hours'))
        : date('Y-m-d H:i:s', strtotime("+{$duration_days} days"));

    $insert = $pdo->prepare("INSERT INTO user_packages (user_id, package_id, start_date, end_date, status)
                             VALUES (?, ?, ?, ?, 'pending')");
    $insert->execute([$user_id, $package_id, $start, $end]);

    // === If paid, record transaction ===
    if ($price > 0) {
        $reference = 'SUB-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = $pdo->prepare("
            INSERT INTO transactions 
            (user_id, type, amount, payment_method_id, status, reference, proof_path)
            VALUES (?, 'deposit', ?, 
                    (SELECT id FROM payment_methods WHERE method_code = ? LIMIT 1),
                    'pending', ?, ?)
        ");
        $stmt->execute([$user_id, $price, $payment_method, $reference, $proofPath]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $price > 0
            ? 'Subscription recorded successfully! Your payment will be reviewed.'
            : '48H Trial activated successfully!'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Subscription error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error, please try again.']);
}

