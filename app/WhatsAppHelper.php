<?php
/**
 * WhatsAppHelper
 *
 * Sends messages via the Meta WhatsApp Business Cloud API.
 * Reads phone_number_id, access_token, is_enabled from the wa_settings table (row id = 1).
 *
 * Database table required (see database/wa_settings.sql):
 *   wa_settings: id, phone_number_id, access_token, is_enabled
 */

class WhatsAppHelper {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Fetch WhatsApp settings from wa_settings table.
     *
     * @return array|null Settings row, or null when not configured / not enabled
     */
    private function getSettings() {
        try {
            $stmt = $this->pdo->query("SELECT phone_number_id, access_token, is_enabled FROM wa_settings WHERE id = 1 LIMIT 1");
            $row  = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                error_log("WhatsAppHelper - getSettings: no configuration row found in wa_settings. Run the migration and save settings in the admin panel.");
                return null;
            }

            if (empty($row['phone_number_id']) || empty($row['access_token'])) {
                error_log("WhatsAppHelper - getSettings: phone_number_id or access_token is empty in wa_settings.");
                return null;
            }

            if (!$row['is_enabled']) {
                return null;
            }

            return [
                'phone_number_id' => $row['phone_number_id'],
                'access_token'    => $row['access_token'],
            ];
        } catch (Exception $e) {
            error_log("WhatsAppHelper - getSettings error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send a plain-text message to a WhatsApp number.
     *
     * @param string $toPhone  Phone number in E.164 format, e.g. "+491701234567"
     * @param string $text     Message text
     * @return bool True on success
     */
    public function sendTextMessage($toPhone, $text) {
        $settings = $this->getSettings();
        if (!$settings) {
            return false;
        }

        // Strip leading '+' — Meta API expects digits only
        $to = ltrim($toPhone, '+');
        if (!$to) {
            error_log("WhatsAppHelper - sendTextMessage: empty recipient phone number.");
            return false;
        }

        $url = 'https://graph.facebook.com/v19.0/' . rawurlencode($settings['phone_number_id']) . '/messages';

        $payload = json_encode([
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => ['body' => $text],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => $payload,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 10,
            CURLOPT_CONNECTTIMEOUT  => 5,
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_SSL_VERIFYHOST  => 2,
            CURLOPT_HTTPHEADER      => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $settings['access_token'],
            ],
        ]);

        $response  = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("WhatsAppHelper - cURL error: $curlError");
            return false;
        }

        $result = json_decode($response, true);
        if (!empty($result['error'])) {
            error_log("WhatsAppHelper - API error: " . ($result['error']['message'] ?? 'unknown'));
            return false;
        }

        return !empty($result['messages']);
    }

    /**
     * Notify a user that an incoming call is waiting.
     *
     * @param string $toPhone   Recipient phone in E.164 format
     * @param string $userName  Recipient's first name
     * @param string $chatUrl   Direct URL to the live chat page
     * @return bool
     */
    public function sendCallNotification($toPhone, $userName, $chatUrl) {
        $name = htmlspecialchars_decode($userName, ENT_QUOTES);
        $text = "📞 Hallo $name,\n\nSie haben einen eingehenden Sprachanruf von unserem Support-Team.\n\nKlicken Sie auf den folgenden Link, um den Anruf entgegenzunehmen:\n$chatUrl\n\nDiese Nachricht wurde automatisch generiert.";
        return $this->sendTextMessage($toPhone, $text);
    }
}
