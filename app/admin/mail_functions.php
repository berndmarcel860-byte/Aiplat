<?php
require_once __DIR__ . '/../../mailer/SmtpClient.php';
require_once __DIR__ . '/../config.php';

class Mailer {
    private $pdo;
    private $systemSettings;
    private $smtpSettings;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadSettings();
    }

    private function loadSettings() {
        $stmt = $this->pdo->query("SELECT * FROM smtp_settings LIMIT 1");
        $this->smtpSettings = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$this->smtpSettings) {
            throw new \RuntimeException('SMTP settings not configured');
        }

        $stmt = $this->pdo->query("SELECT * FROM system_settings LIMIT 1");
        $this->systemSettings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function replaceVariables(string $content, array $variables): string {
        $defaults = [
            'surl'   => $this->systemSettings['site_url']      ?? '',
            'sbrand' => $this->systemSettings['brand_name']     ?? '',
            'sphone' => $this->systemSettings['contact_phone']  ?? '',
            'semail' => $this->systemSettings['contact_email']  ?? ''
        ];

        $allVars = array_merge($defaults, $variables);

        foreach ($allVars as $key => $value) {
            $content = str_replace(
                ["{{$key}}", "{{$key}}", "{$key}"],
                $value,
                $content
            );
        }

        return $content;
    }

    public function sendTemplateEmail(string $templateKey, string $recipientEmail, array $variables = []): bool {
        try {
            if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException("Invalid email address: $recipientEmail");
            }

            $stmt = $this->pdo->prepare("SELECT subject, content FROM email_templates WHERE template_key = ?");
            $stmt->execute([$templateKey]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$template) {
                throw new \RuntimeException("Template '$templateKey' not found");
            }

            $template['subject'] = $this->replaceVariables($template['subject'], $variables);
            $template['content'] = $this->replaceVariables($template['content'], $variables);

            $s = $this->smtpSettings;
            $client = new SmtpClient([
                'host'       => $s['host'],
                'port'       => (int)($s['port'] ?? 587),
                'username'   => $s['username'],
                'password'   => $s['password'],
                'from_email' => $s['from_email'] ?? $s['username'],
                'from_name'  => $s['from_name']  ?? ($this->systemSettings['brand_name'] ?? ''),
                'encryption' => $s['encryption'] ?? 'tls',
            ]);

            $client->connect();
            $ok = $client->send($recipientEmail, '', $template['subject'], $template['content']);
            $client->quit();

            error_log("Sending email to $recipientEmail using template $templateKey");
            return $ok;

        } catch (\Throwable $e) {
            error_log("Email Error: " . $e->getMessage());
            return false;
        }
    }
}
