-- ─────────────────────────────────────────────────────────────────────────────
-- Migration: Add email template for fee-proof "under review" notification
-- Sent to the user when they upload their fee payment proof.
-- Safe to re-run (uses ON DUPLICATE KEY UPDATE).
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO `email_templates` (`template_key`, `subject`, `body`, `variables`, `created_at`, `updated_at`)
VALUES (
    'fee_proof_under_review',
    'Zahlungsnachweis eingegangen – Prüfung läuft',
    '<p>Hallo {first_name},</p>
<p>wir haben Ihren Zahlungsnachweis für die Verwaltungsgebühr Ihrer Auszahlung erfolgreich erhalten.</p>
<table style="width:100%;border-collapse:collapse;margin:16px 0;">
    <tr>
        <td style="padding:8px 12px;font-weight:600;color:#495057;width:40%;">Auszahlung Ref.:</td>
        <td style="padding:8px 12px;">{reference}</td>
    </tr>
    <tr style="background:#f8f9fa;">
        <td style="padding:8px 12px;font-weight:600;color:#495057;">Betrag:</td>
        <td style="padding:8px 12px;">{amount} €</td>
    </tr>
    <tr>
        <td style="padding:8px 12px;font-weight:600;color:#495057;">Gebühr:</td>
        <td style="padding:8px 12px;">{fee_amount} €</td>
    </tr>
</table>
<p>Ihr Nachweis wird derzeit von unserem Compliance-Team geprüft. Sobald die Zahlung bestätigt ist, wird Ihre Auszahlung freigegeben.</p>
<p style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:12px 16px;font-size:13px;">
    <strong>Status:</strong> In Prüfung
</p>
<p>Bei Fragen stehen wir Ihnen jederzeit zur Verfügung.</p>',
    'first_name, reference, amount, fee_amount',
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    `subject` = VALUES(`subject`),
    `body`    = VALUES(`body`),
    `updated_at` = NOW();
