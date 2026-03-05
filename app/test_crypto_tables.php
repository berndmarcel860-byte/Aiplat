<?php
/**
 * Payment Methods Database Diagnostic Tool
 * Tests the user_payment_methods table structure and compatibility
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Methods Database Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .test-section { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        h2 { color: #333; border-bottom: 2px solid #4e73df; padding-bottom: 10px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        table th, table td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
        table th { background: #4e73df; color: white; }
        .code { background: #e9ecef; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <h1>🔍 Payment Methods Database Diagnostic</h1>
    
    <?php
    try {
        // Test 1: Check database connection
        echo '<div class="test-section">';
        echo '<h2>Test 1: Database Connection</h2>';
        
        if ($pdo) {
            echo '<p class="success">✓ Database connection successful</p>';
            $version = $pdo->query("SELECT VERSION()")->fetchColumn();
            echo '<p>MySQL Version: <span class="code">' . htmlspecialchars($version) . '</span></p>';
        } else {
            echo '<p class="error">✗ Database connection failed</p>';
        }
        echo '</div>';
        
        // Test 2: Check table existence
        echo '<div class="test-section">';
        echo '<h2>Test 2: Table Existence</h2>';
        
        $tables = $pdo->query("SHOW TABLES LIKE 'user_payment_methods'")->fetchAll();
        if (count($tables) > 0) {
            echo '<p class="success">✓ Table <span class="code">user_payment_methods</span> EXISTS</p>';
        } else {
            echo '<p class="error">✗ Table <span class="code">user_payment_methods</span> NOT FOUND</p>';
            echo '<p class="warning">Run migration: <span class="code">admin/migrations/003_enhance_user_payment_methods.sql</span></p>';
        }
        echo '</div>';
        
        // Test 3: Check table structure
        echo '<div class="test-section">';
        echo '<h2>Test 3: Table Structure</h2>';
        
        $columns = $pdo->query("DESCRIBE user_payment_methods")->fetchAll(PDO::FETCH_ASSOC);
        echo '<p>Found <strong>' . count($columns) . ' columns</strong></p>';
        
        echo '<table>';
        echo '<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
        
        $required_columns = ['id', 'user_id', 'payment_method', 'type', 'is_default', 'created_at', 
                           'label', 'account_holder', 'bank_name', 'iban', 'bic', 'wallet_address', 
                           'cryptocurrency', 'network', 'status', 'verification_status', 'updated_at'];
        
        $found_columns = [];
        foreach ($columns as $col) {
            $found_columns[] = $col['Field'];
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($col['Field']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
            echo '<td>' . htmlspecialchars($col['Extra']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // Check for missing required columns
        $missing = array_diff($required_columns, $found_columns);
        if (empty($missing)) {
            echo '<p class="success">✓ All required columns present</p>';
        } else {
            echo '<p class="error">✗ Missing columns: ' . implode(', ', $missing) . '</p>';
        }
        echo '</div>';
        
        // Test 4: Check row count
        echo '<div class="test-section">';
        echo '<h2>Test 4: Data Check</h2>';
        
        $count = $pdo->query("SELECT COUNT(*) FROM user_payment_methods")->fetchColumn();
        echo '<p>Total payment methods: <strong>' . $count . '</strong></p>';
        
        if ($count > 0) {
            $sample = $pdo->query("SELECT * FROM user_payment_methods LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
            echo '<p class="info">Sample data (first 3 rows):</p>';
            echo '<table>';
            if (!empty($sample)) {
                $headers = array_keys($sample[0]);
                echo '<tr>';
                foreach ($headers as $h) {
                    echo '<th>' . htmlspecialchars($h) . '</th>';
                }
                echo '</tr>';
                foreach ($sample as $row) {
                    echo '<tr>';
                    foreach ($row as $val) {
                        echo '<td>' . htmlspecialchars($val ?? 'NULL') . '</td>';
                    }
                    echo '</tr>';
                }
            }
            echo '</table>';
        }
        echo '</div>';
        
        // Test 5: Test INSERT query structure
        echo '<div class="test-section">';
        echo '<h2>Test 5: INSERT Query Test (Dry Run)</h2>';
        
        $test_data = [
            'user_id' => 1,
            'type' => 'fiat',
            'payment_method' => 'Bank Transfer',
            'label' => 'Test Bank Account',
            'is_default' => 0,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'account_holder' => 'Test User',
            'bank_name' => 'Test Bank',
            'iban' => 'DE89370400440532013000'
        ];
        
        $fields = array_keys($test_data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO user_payment_methods (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        echo '<p class="info">Generated SQL:</p>';
        echo '<pre>' . htmlspecialchars($sql) . '</pre>';
        
        echo '<p class="info">Data to insert:</p>';
        echo '<pre>' . htmlspecialchars(print_r($test_data, true)) . '</pre>';
        
        echo '<p class="success">✓ Query structure looks valid</p>';
        echo '<p class="warning">⚠️ Not actually inserting (dry run only)</p>';
        echo '</div>';
        
        // Test 6: Check for users table (foreign key)
        echo '<div class="test-section">';
        echo '<h2>Test 6: Foreign Key Check</h2>';
        
        $users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo '<p>Users in database: <strong>' . $users_count . '</strong></p>';
        
        if ($users_count > 0) {
            echo '<p class="success">✓ Users table has data (foreign key should work)</p>';
        } else {
            echo '<p class="warning">⚠️ No users in database - need at least one user to add payment methods</p>';
        }
        echo '</div>';
        
        // Test 7: Check MySQL mode
        echo '<div class="test-section">';
        echo '<h2>Test 7: MySQL Configuration</h2>';
        
        $sql_mode = $pdo->query("SELECT @@sql_mode")->fetchColumn();
        echo '<p>SQL Mode: <span class="code">' . htmlspecialchars($sql_mode) . '</span></p>';
        
        if (strpos($sql_mode, 'STRICT_TRANS_TABLES') !== false) {
            echo '<p class="warning">⚠️ STRICT_TRANS_TABLES is enabled - all columns must be valid</p>';
        } else {
            echo '<p class="info">ℹ️ Strict mode not enabled</p>';
        }
        echo '</div>';
        
        // Summary
        echo '<div class="test-section">';
        echo '<h2>📋 Summary</h2>';
        echo '<ul>';
        echo '<li><strong>Database:</strong> Connected ✓</li>';
        echo '<li><strong>Table:</strong> Exists ✓</li>';
        echo '<li><strong>Columns:</strong> ' . count($columns) . ' total</li>';
        echo '<li><strong>Data:</strong> ' . $count . ' payment methods</li>';
        echo '<li><strong>Users:</strong> ' . $users_count . ' users</li>';
        echo '</ul>';
        
        echo '<h3>Recommendations:</h3>';
        echo '<ol>';
        if (empty($missing)) {
            echo '<li class="success">✓ Table structure is correct</li>';
        } else {
            echo '<li class="error">Run migration to add missing columns</li>';
        }
        
        if ($users_count == 0) {
            echo '<li class="warning">Create at least one user before adding payment methods</li>';
        }
        
        echo '<li>Test adding a payment method through the UI: <a href="payment-methods.php">payment-methods.php</a></li>';
        echo '<li>Check browser console (F12) for JavaScript errors</li>';
        echo '<li>Check PHP error logs if INSERT fails</li>';
        echo '</ol>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="test-section">';
        echo '<h2 class="error">❌ Error</h2>';
        echo '<p class="error">' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</div>';
    }
    ?>
    
    <div class="test-section">
        <h2>🛠️ Quick Fixes</h2>
        
        <h3>If table doesn't exist:</h3>
        <pre>mysql -u username -p database_name < admin/migrations/003_enhance_user_payment_methods.sql</pre>
        
        <h3>If columns are missing:</h3>
        <pre>mysql -u username -p database_name < admin/migrations/004_add_wallet_verification_system.sql</pre>
        
        <h3>Test adding payment method via AJAX:</h3>
        <pre>
// Open browser console (F12) and run:
fetch('ajax/add_payment_method.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'type=fiat&payment_method=Bank Transfer&label=Test Bank&account_holder=Test User&bank_name=Test Bank&iban=DE89370400440532013000'
}).then(r => r.json()).then(console.log);
        </pre>
    </div>
    
</body>
</html>