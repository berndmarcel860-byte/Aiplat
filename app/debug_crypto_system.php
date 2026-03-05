<?php
/**
 * Comprehensive Crypto System Debugger
 * Tests all aspects of cryptocurrency management system
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Crypto System Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table th, table td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        table th { background: #007bff; color: white; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .test-result { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .test-pass { background: #d4edda; border: 1px solid #c3e6cb; }
        .test-fail { background: #f8d7da; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<h1>🔍 Cryptocurrency System Debug Tool</h1>
<p>Testing all components of the cryptocurrency management system...</p>

<?php
// Test 1: Database Connection
echo '<div class="test-section">';
echo '<h2>Test 1: Database Connection</h2>';
try {
    $testQuery = $pdo->query("SELECT VERSION()");
    $version = $testQuery->fetchColumn();
    echo '<div class="test-result test-pass">';
    echo '<span class="success">✓ Database Connected</span><br>';
    echo "MySQL Version: {$version}";
    echo '</div>';
} catch (Exception $e) {
    echo '<div class="test-result test-fail">';
    echo '<span class="error">✗ Database Connection Failed</span><br>';
    echo "Error: " . htmlspecialchars($e->getMessage());
    echo '</div>';
}
echo '</div>';

// Test 2: Check if tables exist
echo '<div class="test-section">';
echo '<h2>Test 2: Check Tables Existence</h2>';

$tables = ['cryptocurrencies', 'crypto_networks', 'user_payment_methods'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
            $count = $countStmt->fetchColumn();
            echo '<div class="test-result test-pass">';
            echo "<span class='success'>✓</span> Table '<strong>{$table}</strong>' EXISTS with <strong>{$count}</strong> rows";
            echo '</div>';
        } else {
            echo '<div class="test-result test-fail">';
            echo "<span class='error'>✗</span> Table '<strong>{$table}</strong>' DOES NOT EXIST";
            echo '</div>';
        }
    } catch (Exception $e) {
        echo '<div class="test-result test-fail">';
        echo "<span class='error'>✗</span> Error checking table '{$table}': " . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}
echo '</div>';

// Test 3: Check cryptocurrencies data
echo '<div class="test-section">';
echo '<h2>Test 3: Cryptocurrencies Data</h2>';
try {
    $stmt = $pdo->query("SELECT * FROM cryptocurrencies ORDER BY sort_order ASC, name ASC LIMIT 15");
    $cryptos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($cryptos) > 0) {
        echo '<div class="test-result test-pass">';
        echo '<span class="success">✓ Found ' . count($cryptos) . ' cryptocurrencies</span>';
        echo '</div>';
        
        echo '<table>';
        echo '<tr><th>ID</th><th>Symbol</th><th>Name</th><th>Active</th><th>Sort</th><th>Icon</th></tr>';
        foreach ($cryptos as $crypto) {
            $active = $crypto['is_active'] ? '<span class="success">Yes</span>' : '<span class="error">No</span>';
            echo "<tr>";
            echo "<td>{$crypto['id']}</td>";
            echo "<td><strong>{$crypto['symbol']}</strong></td>";
            echo "<td>{$crypto['name']}</td>";
            echo "<td>{$active}</td>";
            echo "<td>{$crypto['sort_order']}</td>";
            echo "<td>{$crypto['icon']}</td>";
            echo "</tr>";
        }
        echo '</table>';
    } else {
        echo '<div class="test-result test-fail">';
        echo '<span class="error">✗ NO cryptocurrencies found in database!</span><br>';
        echo '<strong>Action needed:</strong> Run the migration script to seed data.';
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<div class="test-result test-fail">';
    echo '<span class="error">✗ Error fetching cryptocurrencies:</span><br>';
    echo htmlspecialchars($e->getMessage());
    echo '</div>';
}
echo '</div>';

// Test 4: Check networks data
echo '<div class="test-section">';
echo '<h2>Test 4: Crypto Networks Data</h2>';
try {
    $stmt = $pdo->query("SELECT cn.*, c.symbol, c.name as crypto_name 
                         FROM crypto_networks cn 
                         LEFT JOIN cryptocurrencies c ON cn.crypto_id = c.id 
                         ORDER BY c.symbol ASC, cn.sort_order ASC 
                         LIMIT 20");
    $networks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($networks) > 0) {
        echo '<div class="test-result test-pass">';
        echo '<span class="success">✓ Found ' . count($networks) . ' networks</span>';
        echo '</div>';
        
        echo '<table>';
        echo '<tr><th>ID</th><th>Crypto</th><th>Network Name</th><th>Type</th><th>Active</th><th>Chain ID</th></tr>';
        foreach ($networks as $network) {
            $active = $network['is_active'] ? '<span class="success">Yes</span>' : '<span class="error">No</span>';
            echo "<tr>";
            echo "<td>{$network['id']}</td>";
            echo "<td><strong>{$network['symbol']}</strong></td>";
            echo "<td>{$network['network_name']}</td>";
            echo "<td>{$network['network_type']}</td>";
            echo "<td>{$active}</td>";
            echo "<td>{$network['chain_id']}</td>";
            echo "</tr>";
        }
        echo '</table>';
    } else {
        echo '<div class="test-result test-fail">';
        echo '<span class="error">✗ NO networks found in database!</span><br>';
        echo '<strong>Action needed:</strong> Run the migration script to seed network data.';
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<div class="test-result test-fail">';
    echo '<span class="error">✗ Error fetching networks:</span><br>';
    echo htmlspecialchars($e->getMessage());
    echo '</div>';
}
echo '</div>';

// Test 5: Test User AJAX Endpoint
echo '<div class="test-section">';
echo '<h2>Test 5: User AJAX Endpoint</h2>';
echo '<p>Testing: <code>ajax/get_available_cryptocurrencies.php</code></p>';
try {
    // Simulate the query that the AJAX endpoint uses
    $sql = "SELECT 
                c.id, 
                c.symbol, 
                c.name, 
                c.icon,
                c.description
            FROM cryptocurrencies c
            WHERE c.is_active = 1
            ORDER BY c.sort_order ASC, c.name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $cryptos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<div class="test-result test-pass">';
    echo '<span class="success">✓ Query executed successfully</span><br>';
    echo 'Found <strong>' . count($cryptos) . '</strong> active cryptocurrencies for users';
    echo '</div>';
    
    if (count($cryptos) > 0) {
        echo '<p><strong>Sample output for dropdown:</strong></p>';
        echo '<pre>';
        foreach (array_slice($cryptos, 0, 5) as $crypto) {
            echo "  - {$crypto['symbol']}: {$crypto['name']}\n";
        }
        echo '</pre>';
    } else {
        echo '<div class="test-result test-fail">';
        echo '<span class="error">⚠ No ACTIVE cryptocurrencies!</span><br>';
        echo 'All cryptocurrencies might be disabled. Check the is_active column.';
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<div class="test-result test-fail">';
    echo '<span class="error">✗ User AJAX Query Failed:</span><br>';
    echo htmlspecialchars($e->getMessage());
    echo '</div>';
}
echo '</div>';

// Test 6: Test Admin AJAX Endpoint
echo '<div class="test-section">';
echo '<h2>Test 6: Admin AJAX Endpoint</h2>';
echo '<p>Testing: <code>admin/admin_ajax/get_all_cryptocurrencies.php</code></p>';
try {
    $sql = "SELECT * FROM cryptocurrencies ORDER BY sort_order ASC, name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $cryptos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<div class="test-result test-pass">';
    echo '<span class="success">✓ Admin query executed successfully</span><br>';
    echo 'Found <strong>' . count($cryptos) . '</strong> total cryptocurrencies (including inactive)';
    echo '</div>';
    
    if (count($cryptos) > 0) {
        $activeCount = count(array_filter($cryptos, function($c) { return $c['is_active']; }));
        $inactiveCount = count($cryptos) - $activeCount;
        echo "<p>Active: <span class='success'>{$activeCount}</span> | Inactive: <span class='warning'>{$inactiveCount}</span></p>";
    }
} catch (Exception $e) {
    echo '<div class="test-result test-fail">';
    echo '<span class="error">✗ Admin AJAX Query Failed:</span><br>';
    echo htmlspecialchars($e->getMessage());
    echo '</div>';
}
echo '</div>';

// Test 7: Check file paths
echo '<div class="test-section">';
echo '<h2>Test 7: File Path Verification</h2>';
$files = [
    'ajax/get_available_cryptocurrencies.php',
    'admin/admin_ajax/get_all_cryptocurrencies.php',
    'payment-methods.php',
    'admin/admin_crypto_management.php',
    'test_crypto_tables.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo '<div class="test-result test-pass">';
        echo "<span class='success'>✓</span> File exists: <code>{$file}</code>";
        echo '</div>';
    } else {
        echo '<div class="test-result test-fail">';
        echo "<span class='error'>✗</span> File NOT found: <code>{$file}</code>";
        echo '</div>';
    }
}
echo '</div>';

// Test 8: JavaScript Test
echo '<div class="test-section">';
echo '<h2>Test 8: JavaScript AJAX Test</h2>';
echo '<p>Testing live AJAX call to user endpoint...</p>';
echo '<div id="ajax-test-result">Loading...</div>';
echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
echo '<script>
$(document).ready(function() {
    $.ajax({
        url: "ajax/get_available_cryptocurrencies.php",
        method: "GET",
        dataType: "json",
        success: function(response) {
            if (response.success) {
                let html = "<div class=\"test-result test-pass\">";
                html += "<span class=\"success\">✓ AJAX call successful!</span><br>";
                html += "Received " + response.cryptocurrencies.length + " cryptocurrencies<br>";
                html += "<strong>Data sample:</strong><pre>" + JSON.stringify(response.cryptocurrencies.slice(0, 2), null, 2) + "</pre>";
                html += "</div>";
                $("#ajax-test-result").html(html);
            } else {
                $("#ajax-test-result").html("<div class=\"test-result test-fail\"><span class=\"error\">✗ AJAX returned error:</span><br>" + response.message + "</div>");
            }
        },
        error: function(xhr, status, error) {
            $("#ajax-test-result").html("<div class=\"test-result test-fail\"><span class=\"error\">✗ AJAX call failed:</span><br>Status: " + status + "<br>Error: " + error + "<br>Response: " + xhr.responseText + "</div>");
        }
    });
});
</script>';
echo '</div>';

// Summary
echo '<div class="test-section">';
echo '<h2>📋 Summary & Next Steps</h2>';
echo '<ol>';
echo '<li><strong>If tables are missing:</strong> Run <code>admin/migrations/005_create_crypto_and_network_tables.sql</code></li>';
echo '<li><strong>If data is missing:</strong> The migration script includes seed data - make sure it completed</li>';
echo '<li><strong>If all inactive:</strong> Run SQL: <code>UPDATE cryptocurrencies SET is_active = 1</code></li>';
echo '<li><strong>If AJAX fails:</strong> Check browser console (F12) for JavaScript errors</li>';
echo '<li><strong>If session errors:</strong> Make sure you are logged in as user/admin</li>';
echo '</ol>';
echo '<p><strong>Quick Fix SQL Commands:</strong></p>';
echo '<pre>';
echo "-- Activate all cryptocurrencies\n";
echo "UPDATE cryptocurrencies SET is_active = 1;\n\n";
echo "-- Activate all networks\n";
echo "UPDATE crypto_networks SET is_active = 1;\n\n";
echo "-- Check current status\n";
echo "SELECT symbol, name, is_active FROM cryptocurrencies;\n";
echo '</pre>';
echo '</div>';
?>

</body>
</html>
