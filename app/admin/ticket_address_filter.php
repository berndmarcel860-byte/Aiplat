<?php
/**
 * Scans an admin ticket-reply message for crypto wallet addresses (or IBANs)
 * that are NOT stored in the payment_methods table and replaces them with the
 * official address for that payment method.
 *
 * This prevents admins or admin members from accidentally (or maliciously)
 * sending unofficial / wrong deposit addresses to users.
 *
 * @param  PDO    $pdo     Active database connection
 * @param  string $message The raw reply message to filter
 * @return string          The message with any unofficial addresses replaced
 */
function filterPaymentAddresses(PDO $pdo, string $message): string
{
    // Load every active payment method that has a wallet_address or account_number
    $stmt = $pdo->query("
        SELECT method_code, method_name, wallet_address, account_number, iban, is_crypto
        FROM payment_methods
        WHERE is_active = 1
    ");
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($methods)) {
        return $message;
    }

    // ── Crypto wallet-address patterns ────────────────────────────────────────
    // Each entry: [ regex, array of method_code / method_name keywords ]
    $cryptoPatterns = [
        // Bitcoin: legacy (1…/3…) and bech32 (bc1…)
        'bitcoin'  => [
            'pattern'  => '/\b(bc1[ac-hj-np-z02-9]{25,87}|[13][a-km-zA-HJ-NP-Z1-9]{25,34})\b/',
            'keywords' => ['bitcoin', 'btc'],
        ],
        // Ethereum / EVM (0x + 40 hex chars)
        'ethereum' => [
            'pattern'  => '/\b(0x[a-fA-F0-9]{40})\b/',
            'keywords' => ['ethereum', 'eth', 'usdt', 'usdc', 'erc20'],
        ],
        // Litecoin: bech32 (ltc1…) and legacy (L…/M…)
        'litecoin' => [
            'pattern'  => '/\b(ltc1[a-z0-9]{25,87}|[LM][a-km-zA-HJ-NP-Z1-9]{25,34})\b/',
            'keywords' => ['litecoin', 'ltc'],
        ],
        // TRON (T + 33 base58 chars)
        'tron'     => [
            'pattern'  => '/\b(T[A-Za-z1-9]{33})\b/',
            'keywords' => ['tron', 'trx', 'trc20'],
        ],
        // Ripple / XRP (r + 24-33 base58 chars)
        'ripple'   => [
            'pattern'  => '/\b(r[0-9a-zA-Z]{24,33})\b/',
            'keywords' => ['ripple', 'xrp'],
        ],
    ];

    // Build a quick lookup of all official wallet addresses (lower-cased for comparison)
    $officialWallets = [];
    foreach ($methods as $m) {
        if (!empty($m['wallet_address'])) {
            $officialWallets[] = strtolower(trim($m['wallet_address']));
        }
    }

    // ── Process each crypto pattern ───────────────────────────────────────────
    foreach ($cryptoPatterns as $type => $cfg) {
        if (!preg_match_all($cfg['pattern'], $message, $matches)) {
            continue;
        }

        $foundAddresses = array_unique($matches[1]);

        foreach ($foundAddresses as $found) {
            // Skip if this is already an official address
            if (in_array(strtolower($found), $officialWallets, true)) {
                continue;
            }

            // Find the replacement: the first active payment method whose
            // method_code or method_name contains one of the type keywords
            $replacement = null;
            foreach ($methods as $m) {
                if (empty($m['wallet_address'])) {
                    continue;
                }
                $code = strtolower($m['method_code']);
                $name = strtolower($m['method_name']);
                foreach ($cfg['keywords'] as $kw) {
                    if (strpos($code, $kw) !== false || strpos($name, $kw) !== false) {
                        $replacement = $m['wallet_address'];
                        break 2;
                    }
                }
            }

            if ($replacement !== null) {
                $message = str_replace($found, $replacement, $message);
            }
        }
    }

    // ── IBAN pattern ──────────────────────────────────────────────────────────
    // Match IBANs: 2 letters + 2 digits + up to 30 alphanumeric chars,
    // optionally spaced in groups of 4.
    $ibanPattern = '/\b([A-Z]{2}[0-9]{2}(?:\s?[A-Z0-9]{4}){1,7}\s?[A-Z0-9]{0,3})\b/';
    if (preg_match_all($ibanPattern, $message, $ibanMatches)) {
        // Collect official IBANs from payment methods
        $officialIbans = [];
        foreach ($methods as $m) {
            if (!empty($m['iban'])) {
                $officialIbans[] = strtoupper(preg_replace('/\s+/', '', $m['iban']));
            }
            if (!empty($m['account_number'])) {
                $officialIbans[] = strtoupper(preg_replace('/\s+/', '', $m['account_number']));
            }
        }

        foreach (array_unique($ibanMatches[1]) as $foundIban) {
            $normalized = strtoupper(preg_replace('/\s+/', '', $foundIban));
            if (in_array($normalized, $officialIbans, true)) {
                continue;
            }

            // Replace with the first active bank payment method's account_number/iban
            foreach ($methods as $m) {
                if (!$m['is_crypto'] && !empty($m['account_number'])) {
                    $message = str_replace($foundIban, $m['account_number'], $message);
                    break;
                }
            }
        }
    }

    return $message;
}
