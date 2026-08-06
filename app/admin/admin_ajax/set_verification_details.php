<?php
/**
 * Set Verification Details - Admin Endpoint
 * Admin sets the test amount and platform wallet address for verification
 * Sends email notification to user with verification instructions
 */

require_once '../../config.php';
require_once '../admin_session.php';
require_once '../../EmailHelper.php';

header('Content-Type: application/json');

try {
    $admin_id = $_SESSION['admin_id'];
    
    // Validate input
    if (!isset($_POST['wallet_id']) || !is_numeric($_POST['wallet_id'])) {
        throw new Exception('Invalid wallet ID');
    }
    
    if (!isset($_POST['verification_amount']) || empty(trim($_POST['verification_amount']))) {
        throw new Exception('Verification amount is required');
    }
    
    if (!isset($_POST['verification_address']) || empty(trim($_POST['verification_address']))) {
        throw new Exception('Verification address is required');
    }
    
    $wallet_id = intval($_POST['wallet_id']);
    $verification_amount = trim($_POST['verification_amount']);
    $verification_address = trim($_POST['verification_address']);
    
    // Validate amount format (decimal)
    if (!is_numeric($verification_amount) || floatval($verification_amount) <= 0) {
        throw new Exception('Invalid verification amount. Must be a positive number.');
    }
    
    // Get wallet details with user information
    $stmt = $pdo->prepare("SELECT upm.*, u.first_name, u.last_name, u.email 
                           FROM user_payment_methods upm
                           JOIN users u ON upm.user_id = u.id
                           WHERE upm.id = ? AND upm.type = 'crypto'");
    $stmt->execute([$wallet_id]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$wallet) {
        throw new Exception('Wallet not found');
    }
    
    // Update verification details
    $update_stmt = $pdo->prepare("UPDATE user_payment_methods 
                                   SET verification_amount = ?,
                                       verification_address = ?,
                                       updated_at = CURRENT_TIMESTAMP
                                   WHERE id = ?");
    
    if ($update_stmt->execute([$verification_amount, $verification_address, $wallet_id])) {
        // Log admin action
        $action = "set_verification_details";
        $log_stmt = $pdo->prepare("INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, ip_address) 
                                   VALUES (?, ?, 'payment_method', ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'];
        $log_stmt->execute([$admin_id, $action, $wallet_id, $ip]);
        
        // Send email notification to user
        try {
            $emailHelper = new EmailHelper($pdo);
            
            // Prepare custom variables for email
            $customVars = [
                'verification_amount' => $verification_amount,
                'verification_address' => $verification_address,
                'wallet_cryptocurrency' => htmlspecialchars($wallet['cryptocurrency']),
                'wallet_network' => htmlspecialchars($wallet['network']),
                'wallet_address' => htmlspecialchars($wallet['wallet_address'])
            ];
            
            // Email subject in German
            $subject = "Wallet-Verifizierung bereit – Satoshi-Test kann durchgeführt werden";
            
            // Email body in German - Professional HTML
            $htmlBody = '
            <div style="background-color: #f8f9fa; padding: 40px 0;">
                <div style="max-width: 600px; margin: 0 auto; background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <div style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); color: white; padding: 30px; text-align: center;">
                        <h1 style="margin: 0; font-size: 24px; font-weight: 600;">
                            🔐 Wallet-Verifizierung bereit
                        </h1>
                        <p style="margin: 10px 0 0 0; font-size: 14px; opacity: 0.9;">
                            Ihr Satoshi-Test kann jetzt durchgeführt werden
                        </p>
                    </div>
                    
                    <!-- Content -->
                    <div style="padding: 30px;">
                        <p style="margin: 0 0 20px 0; font-size: 16px; color: #2d3748;">
                            Hallo {first_name} {last_name},
                        </p>
                        
                        <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #4a5568;">
                            Gute Nachrichten! Ihr Administrator hat die Verifizierungsdetails für Ihr <strong>{wallet_cryptocurrency}</strong>-Wallet eingerichtet. Sie können jetzt den Satoshi-Test durchführen, um Ihr Wallet zu verifizieren.
                        </p>
                        
                        <!-- Wallet Info Box -->
                        <div style="background-color: #edf2f7; border-left: 4px solid #4e73df; padding: 15px; margin: 20px 0; border-radius: 4px;">
                            <h3 style="margin: 0 0 10px 0; font-size: 16px; color: #2d3748;">
                                📱 Ihr Wallet
                            </h3>
                            <table style="width: 100%; font-size: 14px;">
                                <tr>
                                    <td style="padding: 5px 0; color: #718096;"><strong>Kryptowährung:</strong></td>
                                    <td style="padding: 5px 0; color: #2d3748;">{wallet_cryptocurrency}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px 0; color: #718096;"><strong>Netzwerk:</strong></td>
                                    <td style="padding: 5px 0; color: #2d3748;">{wallet_network}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px 0; color: #718096;"><strong>Ihre Wallet-Adresse:</strong></td>
                                    <td style="padding: 5px 0; color: #2d3748; word-break: break-all; font-family: monospace; font-size: 12px;">{wallet_address}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <h3 style="margin: 25px 0 15px 0; font-size: 18px; color: #2d3748;">
                            🎯 Verifizierungsdetails
                        </h3>
                        
                        <p style="margin: 0 0 15px 0; font-size: 14px; line-height: 1.6; color: #4a5568;">
                            Um Ihr Wallet zu verifizieren, senden Sie bitte <strong>exakt</strong> den folgenden Betrag an die angegebene Adresse:
                        </p>
                        
                        <!-- Verification Amount Box -->
                        <div style="background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; padding: 20px; margin: 15px 0; border-radius: 8px; text-align: center;">
                            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">
                                Zu sendender Betrag
                            </div>
                            <div style="font-size: 28px; font-weight: 700; font-family: monospace; letter-spacing: 1px;">
                                {verification_amount} {wallet_cryptocurrency}
                            </div>
                        </div>
                        
                        <!-- Verification Address Box -->
                        <div style="background-color: #fff5f5; border: 2px solid #fc8181; padding: 15px; margin: 15px 0; border-radius: 8px;">
                            <div style="font-size: 13px; color: #e53e3e; font-weight: 600; margin-bottom: 8px;">
                                ⚠️ Verifizierungs-Adresse (Platform)
                            </div>
                            <div style="background-color: white; padding: 12px; border-radius: 4px; word-break: break-all; font-family: monospace; font-size: 12px; color: #2d3748; border: 1px solid #feb2b2;">
                                {verification_address}
                            </div>
                            <div style="font-size: 12px; color: #c53030; margin-top: 8px;">
                                ⚠️ <strong>Wichtig:</strong> Senden Sie den Betrag NUR an diese Adresse!
                            </div>
                        </div>
                        
                        <h3 style="margin: 25px 0 15px 0; font-size: 18px; color: #2d3748;">
                            📋 Schritt-für-Schritt-Anleitung
                        </h3>
                        
                        <ol style="padding-left: 20px; margin: 0 0 20px 0;">
                            <li style="margin-bottom: 12px; font-size: 14px; line-height: 1.6; color: #4a5568;">
                                Öffnen Sie Ihre {wallet_cryptocurrency}-Wallet-Anwendung
                            </li>
                            <li style="margin-bottom: 12px; font-size: 14px; line-height: 1.6; color: #4a5568;">
                                Senden Sie <strong>exakt {verification_amount} {wallet_cryptocurrency}</strong> an die oben angegebene Verifizierungs-Adresse
                            </li>
                            <li style="margin-bottom: 12px; font-size: 14px; line-height: 1.6; color: #4a5568;">
                                Warten Sie auf die Blockchain-Bestätigung (ca. 5-10 Minuten)
                            </li>
                            <li style="margin-bottom: 12px; font-size: 14px; line-height: 1.6; color: #4a5568;">
                                Kopieren Sie die <strong>Transaktions-ID (TxHash)</strong> aus Ihrer Wallet
                            </li>
                            <li style="margin-bottom: 12px; font-size: 14px; line-height: 1.6; color: #4a5568;">
                                Melden Sie sich in Ihrem Dashboard an und navigieren Sie zu <strong>Zahlungsmethoden</strong>
                            </li>
                            <li style="margin-bottom: 12px; font-size: 14px; line-height: 1.6; color: #4a5568;">
                                Klicken Sie auf <strong>"Verifizieren"</strong> bei Ihrem Wallet
                            </li>
                            <li style="margin-bottom: 12px; font-size: 14px; line-height: 1.6; color: #4a5568;">
                                Geben Sie die Transaktions-ID ein und reichen Sie die Verifizierung ein
                            </li>
                        </ol>
                        
                        <!-- Important Notes -->
                        <div style="background-color: #fffaf0; border-left: 4px solid #f6ad55; padding: 15px; margin: 20px 0; border-radius: 4px;">
                            <h4 style="margin: 0 0 10px 0; font-size: 15px; color: #c05621;">
                                ⚠️ Wichtige Hinweise
                            </h4>
                            <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #744210; line-height: 1.6;">
                                <li style="margin-bottom: 8px;">
                                    Der Betrag muss <strong>exakt</strong> sein – nicht mehr und nicht weniger
                                </li>
                                <li style="margin-bottom: 8px;">
                                    Senden Sie NUR von Ihrem registrierten Wallet ({wallet_address})
                                </li>
                                <li style="margin-bottom: 8px;">
                                    Überprüfen Sie die Verifizierungs-Adresse sorgfältig vor dem Senden
                                </li>
                                <li style="margin-bottom: 8px;">
                                    Der gesendete Betrag wird NICHT erstattet (Verifizierungsgebühr)
                                </li>
                                <li style="margin-bottom: 8px;">
                                    Die Verifizierung kann 1-2 Werktage dauern
                                </li>
                            </ul>
                        </div>
                        
                        <h3 style="margin: 25px 0 15px 0; font-size: 18px; color: #2d3748;">
                            ❓ Warum ist die Wallet-Verifizierung erforderlich?
                        </h3>
                        
                        <p style="margin: 0 0 15px 0; font-size: 14px; line-height: 1.6; color: #4a5568;">
                            Die Wallet-Verifizierung durch den Satoshi-Test ist eine Sicherheitsmaßnahme, die:
                        </p>
                        
                        <ul style="margin: 0 0 20px 0; padding-left: 20px; font-size: 14px; color: #4a5568; line-height: 1.8;">
                            <li>Bestätigt, dass Sie der tatsächliche Eigentümer des Wallets sind</li>
                            <li>Verhindert Betrug und unbefugte Auszahlungen</li>
                            <li>Erfüllt gesetzliche KYC/AML-Anforderungen</li>
                            <li>Schützt Ihre Gelder vor Missbrauch</li>
                        </ul>
                        
                        <!-- Action Button -->
                        <div style="text-align: center; margin: 30px 0;">
                            <a href="{dashboard_url}/payment-methods.php" style="display: inline-block; background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); color: white; text-decoration: none; padding: 15px 40px; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(78, 115, 223, 0.4);">
                                Zur Verifizierung →
                            </a>
                        </div>
                        
                        <p style="margin: 20px 0 0 0; font-size: 13px; color: #718096; text-align: center;">
                            Bei Fragen zur Verifizierung kontaktieren Sie uns unter<br>
                            <a href="mailto:{contact_email}" style="color: #4e73df; text-decoration: none;">{contact_email}</a>
                        </p>
                    </div>
                    
                    <!-- Footer -->
                    <div style="background-color: #f7fafc; padding: 20px; text-align: center; border-top: 1px solid #e2e8f0;">
                        <p style="margin: 0 0 10px 0; font-size: 13px; color: #718096;">
                            Mit freundlichen Grüßen<br>
                            <strong style="color: #2d3748;">{brand_name} Team</strong>
                        </p>
                        <p style="margin: 0; font-size: 12px; color: #a0aec0;">
                            {company_address}
                        </p>
                        <p style="margin: 10px 0 0 0; font-size: 11px; color: #cbd5e0;">
                            © {current_year} {brand_name}. Alle Rechte vorbehalten.
                        </p>
                    </div>
                    
                </div>
            </div>';
            
            // Send email using EmailHelper
            $emailSent = $emailHelper->sendDirectEmail($wallet['user_id'], $subject, $htmlBody, $customVars);
            
            if (!$emailSent) {
                error_log("Failed to send wallet verification email to user " . $wallet['user_id']);
            }
            
        } catch (Exception $emailError) {
            // Log email error but don't fail the main operation
            error_log("Email notification error: " . $emailError->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Verification details set successfully and user notified',
            'wallet_id' => $wallet_id,
            'verification_amount' => $verification_amount,
            'verification_address' => $verification_address,
            'email_sent' => isset($emailSent) ? $emailSent : false
        ]);
    } else {
        throw new Exception('Failed to update verification details');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}