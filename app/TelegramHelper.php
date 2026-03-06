<?php
/**
 * TelegramHelper
 *
 * Sends messages to a Telegram chat/channel via the Bot API.
 * Reads the bot token and chat ID from the tg_settings table (row id = 1).
 *
 * Database table required (see database/tg_settings.sql):
 *   tg_settings: id, bot_token, chat_id, is_enabled
 *
 * Usage:
 *   $tg = new TelegramHelper($pdo);
 *   $tg->sendTicketNotification($ticketNumber, $subject, $category, $priority);
 */

class TelegramHelper {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Fetch Telegram settings from tg_settings table.
     *
     * @return array|null Settings row, or null when not configured / not enabled
     */
    private function getSettings() {
        try {
            $stmt = $this->pdo->query("SELECT bot_token, chat_id, is_enabled FROM tg_settings WHERE id = 1 LIMIT 1");
            $row  = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                error_log("TelegramHelper - getSettings: no configuration row found in tg_settings (id=1). Run the migration in database/tg_settings.sql and save settings in the admin panel.");
                return null;
            }

            if (empty($row['bot_token']) || empty($row['chat_id'])) {
                error_log("TelegramHelper - getSettings: bot_token or chat_id is empty in tg_settings. Save the Telegram settings in the admin panel.");
                return null;
            }

            if (!$row['is_enabled']) {
                error_log("TelegramHelper - getSettings: Telegram notifications are disabled (is_enabled=0). Enable the toggle and save in the admin panel.");
                return null;
            }

            // Return only what is needed; never log the raw token
            return [
                'bot_token'  => $row['bot_token'],
                'chat_id'    => $row['chat_id'],
                'is_enabled' => $row['is_enabled'],
            ];
        } catch (Exception $e) {
            error_log("TelegramHelper - getSettings error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send a plain-text or HTML-formatted message to the configured chat.
     *
     * @param string $text       Message text (HTML parse_mode supported)
     * @param string $parseMode  'HTML' or 'Markdown'
     * @return bool True on success
     */
    public function sendMessage($text, $parseMode = 'HTML') {
        $settings = $this->getSettings();
        if (!$settings) {
            return false;
        }

        $url     = 'https://api.telegram.org/bot' . $settings['bot_token'] . '/sendMessage';
        $payload = http_build_query([
            'chat_id'    => $settings['chat_id'],
            'text'       => $text,
            'parse_mode' => $parseMode,
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
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("TelegramHelper - cURL error sending message: $curlError");
            return false;
        }

        $result = json_decode($response, true);
        if (!($result['ok'] ?? false)) {
            error_log("TelegramHelper - API error: " . ($result['description'] ?? 'unknown'));
            return false;
        }

        return true;
    }

    /**
     * Send an admin notification about a newly created support ticket.
     *
     * @param string $ticketNumber   e.g. 'TICKET-ABC123'
     * @param string $ticketSubject  Ticket subject / title
     * @param string $ticketCategory Ticket category
     * @param string $ticketPriority Human-readable priority label
     * @return bool True on success
     */
    public function sendTicketNotification($ticketNumber, $ticketSubject, $ticketCategory, $ticketPriority) {
        $number   = htmlspecialchars($ticketNumber,   ENT_QUOTES, 'UTF-8');
        $subject  = htmlspecialchars($ticketSubject,  ENT_QUOTES, 'UTF-8');
        $category = htmlspecialchars($ticketCategory, ENT_QUOTES, 'UTF-8');
        $priority = htmlspecialchars($ticketPriority, ENT_QUOTES, 'UTF-8');

        $text = "🎫 <b>Neues Support-Ticket</b>\n\n"
              . "Nr.: <code>$number</code>\n"
              . "Betreff: $subject\n"
              . "Kategorie: $category\n"
              . "Priorität: $priority";

        return $this->sendMessage($text);
    }
}
