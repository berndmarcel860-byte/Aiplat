<?php
/**
 * SmtpClient — lightweight pure-PHP SMTP client (no Composer required).
 *
 * Supports:
 *  - Plain TCP (port 25/587) and STARTTLS upgrade
 *  - SSL/TLS (port 465, smtps)
 *  - AUTH LOGIN and AUTH PLAIN
 *  - Multipart (text/plain + text/html) emails
 *  - Sender name + reply-to header
 *
 * Usage:
 *   $client = new SmtpClient($accountConfig);
 *   $client->connect();
 *   $ok = $client->send($to, $toName, $subject, $htmlBody, $textBody);
 *   $client->quit();
 */
class SmtpClient
{
    private array  $account;
    private        $socket = null;
    private string $lastResponse = '';

    /**
     * @param array $account Keys: host, port, username, password,
     *                        from_email, from_name, encryption (tls|ssl|none)
     */
    public function __construct(array $account)
    {
        $this->account = array_merge([
            'host'       => 'smtp.example.com',
            'port'       => 587,
            'username'   => '',
            'password'   => '',
            'from_email' => '',
            'from_name'  => '',
            'encryption' => 'tls',   // 'tls' = STARTTLS, 'ssl' = smtps, 'none' = plain
            'timeout'    => 30,
        ], $account);
    }

    /**
     * Open connection + EHLO + STARTTLS (if needed) + AUTH.
     *
     * @throws RuntimeException on failure
     */
    public function connect(): void
    {
        $a = $this->account;

        $prefix = ($a['encryption'] === 'ssl') ? 'ssl://' : '';
        $this->socket = @stream_socket_client(
            $prefix . $a['host'] . ':' . $a['port'],
            $errno,
            $errstr,
            $a['timeout'],
            STREAM_CLIENT_CONNECT
        );

        if (!$this->socket) {
            throw new RuntimeException("SMTP connect failed ({$a['host']}:{$a['port']}): $errstr ($errno)");
        }

        stream_set_timeout($this->socket, $a['timeout']);

        $this->read();                                  // 220 greeting

        $domain = $this->localDomain();
        $this->command("EHLO $domain", 250);            // EHLO

        if ($a['encryption'] === 'tls') {
            $this->command('STARTTLS', 220);
            $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            if (!stream_socket_enable_crypto($this->socket, true, $cryptoMethod)) {
                throw new RuntimeException('STARTTLS negotiation failed.');
            }
            $this->command("EHLO $domain", 250);        // re-EHLO after TLS
        }

        // AUTH LOGIN
        $this->command('AUTH LOGIN', 334);
        $this->command(base64_encode($a['username']), 334);
        $this->command(base64_encode($a['password']), 235);
    }

    /**
     * Send a single email, optionally with file attachments.
     *
     * @param string $toEmail
     * @param string $toName
     * @param string $subject
     * @param string $htmlBody
     * @param string $textBody      Plain-text fallback (auto-generated from HTML if empty)
     * @param string $replyTo       Optional reply-to address
     * @param array  $attachments   Optional file attachments.
     *                              Each entry: ['path' => '/abs/path', 'name' => 'file.pdf', 'mime' => 'application/pdf']
     * @return bool
     */
    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody = '',
        string $replyTo = '',
        array  $attachments = []
    ): bool {
        $a = $this->account;

        if ($textBody === '') {
            $textBody = strip_tags(preg_replace('/<br\s*\/?>/', "\n", $htmlBody));
        }

        $this->command("MAIL FROM:<{$a['from_email']}>", 250);
        $this->command("RCPT TO:<$toEmail>", [250, 251]);

        $this->command('DATA', 354);

        $altBoundary  = 'alt_' . bin2hex(random_bytes(12));
        $mixBoundary  = 'mix_' . bin2hex(random_bytes(12));
        $msgId        = '<' . bin2hex(random_bytes(10)) . '@' . $this->localDomain() . '>';
        $date         = date('r');

        $fromEncoded = $this->encodeHeader($a['from_name']) . ' <' . $a['from_email'] . '>';
        $toEncoded   = $this->encodeHeader($toName)        . ' <' . $toEmail . '>';
        $subjectEnc  = $this->encodeHeader($subject);

        $hasAttachments = !empty($attachments);

        $headers  = "From: $fromEncoded\r\n";
        $headers .= "To: $toEncoded\r\n";
        $headers .= "Subject: $subjectEnc\r\n";
        $headers .= "Date: $date\r\n";
        $headers .= "Message-ID: $msgId\r\n";
        $headers .= "MIME-Version: 1.0\r\n";

        if ($hasAttachments) {
            $headers .= "Content-Type: multipart/mixed; boundary=\"$mixBoundary\"\r\n";
        } else {
            $headers .= "Content-Type: multipart/alternative; boundary=\"$altBoundary\"\r\n";
        }
        $headers .= "X-Mailer: PHP/SmtpClient\r\n";

        if ($replyTo) {
            $headers .= "Reply-To: $replyTo\r\n";
        }

        // Build the text+html alternative part
        $altPart  = "--$altBoundary\r\n";
        $altPart .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $altPart .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $altPart .= $this->quotedPrintable($textBody) . "\r\n";
        $altPart .= "--$altBoundary\r\n";
        $altPart .= "Content-Type: text/html; charset=UTF-8\r\n";
        $altPart .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $altPart .= $this->quotedPrintable($htmlBody) . "\r\n";
        $altPart .= "--$altBoundary--";

        if ($hasAttachments) {
            // Wrap the alternative part inside the mixed envelope
            $body  = "--$mixBoundary\r\n";
            $body .= "Content-Type: multipart/alternative; boundary=\"$altBoundary\"\r\n\r\n";
            $body .= $altPart . "\r\n";

            foreach ($attachments as $att) {
                $filePath = $att['path']  ?? '';
                $fileName = $att['name']  ?? basename($filePath);
                $fileMime = $att['mime']  ?? 'application/octet-stream';

                if (!file_exists($filePath)) {
                    continue;
                }

                $fileData = chunk_split(base64_encode(file_get_contents($filePath)));
                $body .= "--$mixBoundary\r\n";
                $body .= "Content-Type: $fileMime; name=\"$fileName\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n";
                $body .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n\r\n";
                $body .= $fileData . "\r\n";
            }

            $body .= "--$mixBoundary--\r\n";
        } else {
            $body = $altPart . "\r\n";
        }

        // Escape leading dots (SMTP transparency)
        $message = str_replace("\r\n.", "\r\n..", $headers . "\r\n" . $body);

        fwrite($this->socket, $message . "\r\n.\r\n");
        $resp = $this->read();

        $code = (int) substr(ltrim($resp), 0, 3);
        return $code === 250 || $code === 251;
    }

    /**
     * Send QUIT and close the socket.
     */
    public function quit(): void
    {
        if ($this->socket) {
            @fwrite($this->socket, "QUIT\r\n");
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    // ------------------------------------------------------------------ helpers

    private function command(string $cmd, $expectedCode): string
    {
        fwrite($this->socket, $cmd . "\r\n");
        $resp = $this->read();
        $codes = is_array($expectedCode) ? $expectedCode : [$expectedCode];
        $respCode = (int) substr(ltrim($resp), 0, 3);
        if (!in_array($respCode, $codes, true)) {
            throw new RuntimeException("SMTP error after '$cmd': $resp");
        }
        return $resp;
    }

    private function read(): string
    {
        $resp = '';
        while ($line = fgets($this->socket, 512)) {
            $resp .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        $this->lastResponse = trim($resp);
        return $this->lastResponse;
    }

    private function localDomain(): string
    {
        // Use server hostname; never trust HTTP_HOST (user-controlled)
        $h = gethostname() ?: 'localhost';
        return strtolower($h);
    }

    private function encodeHeader(string $value): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }

    private function quotedPrintable(string $str): string
    {
        return quoted_printable_encode($str);
    }
}
