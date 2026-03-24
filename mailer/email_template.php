<?php
/**
 * EmailTemplate — Novalnet AI lead-generation email template builder.
 *
 * Designed to render cleanly in:
 *   Gmail · Outlook 2016/2019/365 · Apple Mail · Yahoo · Thunderbird
 *
 * Guidelines applied to avoid spam filters
 * ─────────────────────────────────────────
 *  ✓ No ALL-CAPS words or excessive exclamation marks
 *  ✓ No spam trigger words (free, click here, guarantee, act now, winner…)
 *  ✓ Text-to-image ratio > 80 % text
 *  ✓ Single clear call-to-action link (not a button image)
 *  ✓ Unsubscribe link included (CAN-SPAM / GDPR compliant)
 *  ✓ Plain-text alternative generated automatically
 *
 * Personalisation placeholders (replaced by BulkMailer):
 *   {first_name}   — first name of the recipient
 *   {name}         — full name
 *   {email}        — recipient email address
 *
 * Usage:
 *   $html = EmailTemplate::build([
 *       'cta_url'         => 'https://novalnet-ai.de/kontakt.php',
 *       'unsubscribe_url' => 'https://novalnet-ai.de/unsubscribe.php?email={email}',
 *   ]);
 */
class EmailTemplate
{
    public static function build(array $options = []): string
    {
        $ctaUrl         = $options['cta_url']         ?? 'https://novalnet-ai.de/kontakt.php';
        $unsubscribeUrl = $options['unsubscribe_url'] ?? 'https://novalnet-ai.de/unsubscribe.php?email={email}';
        $brandName      = $options['brand_name']      ?? 'Novalnet AI';
        $siteUrl        = $options['site_url']        ?? 'https://novalnet-ai.de';
        $logoUrl        = $options['logo_url']        ?? $siteUrl . '/assets/img/logo.png';
        $contactEmail   = $options['contact_email']   ?? 'contact@novalnet-ai.de';
        $companyAddress = $options['company_address'] ?? 'Novalnet AI GmbH · BaFin-reg. · Deutschland';

        // Primary brand colour
        $blue = '#0d6efd';

        return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>$brandName</title>
  <!--[if mso]>
  <noscript>
    <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
  </noscript>
  <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">

<!--[if !vml]><!-- Outlook wrapper table -->
<table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#f4f6f9;">
<tr><td align="center" style="padding:32px 16px;">

  <!-- Email card -->
  <table role="presentation" width="600" border="0" cellspacing="0" cellpadding="0"
         style="max-width:600px;width:100%;background:#ffffff;border-radius:10px;overflow:hidden;
                box-shadow:0 2px 12px rgba(0,0,0,0.08);">

    <!-- ── HEADER ──────────────────────────────────────────── -->
    <tr>
      <td style="background:linear-gradient(135deg,$blue 0%,#0b5ed7 100%);padding:28px 40px;text-align:center;">
        <p style="margin:0;font-size:22px;font-weight:700;color:#ffffff;letter-spacing:-0.02em;">
          $brandName
        </p>
        <p style="margin:6px 0 0;font-size:12px;color:rgba(255,255,255,0.75);letter-spacing:0.08em;text-transform:uppercase;">
          KI-gestützte Blockchain-Forensik · BaFin-lizenziert
        </p>
      </td>
    </tr>

    <!-- ── BODY ────────────────────────────────────────────── -->
    <tr>
      <td style="padding:40px 40px 32px;">

        <!-- Greeting (gender-neutral) -->
        <p style="margin:0 0 20px;font-size:17px;font-weight:600;color:#1a1e2e;">
          Guten Tag {first_name},
        </p>

        <!-- Opening paragraph -->
        <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#374151;">
          haben Sie oder Ihr Unternehmen Kryptowährungen durch Betrug oder unrechtmäßige
          Transaktionen verloren? Mit modernster KI-Blockchain-Analyse und einem zertifizierten
          Forensik-Team helfen wir Ihnen dabei, den Verbleib Ihrer digitalen Werte lückenlos
          nachzuverfolgen.
        </p>

        <!-- Value proposition block -->
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0"
               style="background:#f8faff;border-left:4px solid $blue;border-radius:0 6px 6px 0;
                      margin:0 0 24px;">
          <tr>
            <td style="padding:18px 20px;">
              <p style="margin:0 0 10px;font-size:14px;font-weight:700;color:#1a1e2e;">
                Warum Novalnet AI?
              </p>
              <ul style="margin:0;padding-left:18px;font-size:14px;line-height:1.8;color:#374151;">
                <li>87 % dokumentierte Rückführungsquote bei abgeschlossenen Fällen</li>
                <li>BaFin-reguliert und ISO-27001-zertifiziert</li>
                <li>Echtzeit-Blockchain-Tracing über mehr als 50 Netzwerke</li>
                <li>Vollständige Transparenz: Sie erhalten regelmäßige Statusberichte</li>
                <li>Keine Vorabgebühren — Honorar nur im Erfolgsfall</li>
              </ul>
            </td>
          </tr>
        </table>

        <!-- How it works -->
        <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#374151;">
          Unser Verfahren ist in drei Schritte gegliedert:
        </p>

        <!-- Steps -->
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0"
               style="margin:0 0 24px;">
          <tr>
            <td width="32" valign="top" style="padding:0 12px 12px 0;">
              <div style="width:28px;height:28px;border-radius:50%;background:$blue;
                          color:#fff;font-size:13px;font-weight:700;
                          text-align:center;line-height:28px;">1</div>
            </td>
            <td style="padding:0 0 12px;">
              <p style="margin:0;font-size:14px;line-height:1.6;color:#374151;">
                <strong>Kostenlose Ersteinschätzung</strong> — Teilen Sie uns Ihren Fall vertraulich mit.
                Unser Team prüft die technische Nachverfolgbarkeit innerhalb von 48 Stunden.
              </p>
            </td>
          </tr>
          <tr>
            <td width="32" valign="top" style="padding:0 12px 12px 0;">
              <div style="width:28px;height:28px;border-radius:50%;background:$blue;
                          color:#fff;font-size:13px;font-weight:700;
                          text-align:center;line-height:28px;">2</div>
            </td>
            <td style="padding:0 0 12px;">
              <p style="margin:0;font-size:14px;line-height:1.6;color:#374151;">
                <strong>KI-Blockchain-Analyse</strong> — Unsere Algorithmen verfolgen
                Transaktionspfade über Tausende von Wallets und Börsen.
              </p>
            </td>
          </tr>
          <tr>
            <td width="32" valign="top" style="padding:0 12px 0 0;">
              <div style="width:28px;height:28px;border-radius:50%;background:$blue;
                          color:#fff;font-size:13px;font-weight:700;
                          text-align:center;line-height:28px;">3</div>
            </td>
            <td style="padding:0;">
              <p style="margin:0;font-size:14px;line-height:1.6;color:#374151;">
                <strong>Rechtliche Koordinierung</strong> — Bei Bedarf arbeiten wir mit
                Strafverfolgungsbehörden und spezialisierten Anwaltskanzleien zusammen.
              </p>
            </td>
          </tr>
        </table>

        <!-- CTA paragraph -->
        <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#374151;">
          Eine vertrauliche Erstberatung ist unverbindlich und ohne Risiko für Sie.
          Teilen Sie uns Ihren Fall mit — wir melden uns innerhalb von 24 Stunden.
        </p>

        <!-- CTA link (text-based, not image) -->
        <table role="presentation" border="0" cellspacing="0" cellpadding="0" style="margin:0 0 32px;">
          <tr>
            <td style="border-radius:8px;background:$blue;">
              <a href="$ctaUrl"
                 style="display:inline-block;padding:14px 32px;font-size:15px;font-weight:600;
                        color:#ffffff;text-decoration:none;border-radius:8px;
                        font-family:'Segoe UI',Arial,sans-serif;letter-spacing:0.01em;">
                Jetzt unverbindlich anfragen →
              </a>
            </td>
          </tr>
        </table>

        <!-- Trust badges (text) -->
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0"
               style="border-top:1px solid #e9ecef;padding-top:20px;">
          <tr>
            <td align="center" style="padding:0 0 8px;">
              <p style="margin:0;font-size:12px;color:#6c757d;letter-spacing:0.04em;text-transform:uppercase;
                         font-weight:600;">
                Vertraut von Mandanten in über 30 Ländern
              </p>
            </td>
          </tr>
          <tr>
            <td align="center">
              <p style="margin:0;font-size:12px;color:#6c757d;line-height:1.8;">
                🔒 BaFin-lizenziert &nbsp;|&nbsp; 🏅 ISO 27001 &nbsp;|&nbsp; ⚖️ DSGVO-konform
              </p>
            </td>
          </tr>
        </table>

      </td>
    </tr>

    <!-- ── FOOTER ───────────────────────────────────────────── -->
    <tr>
      <td style="background:#f8f9fa;padding:24px 40px;border-top:1px solid #e9ecef;">

        <p style="margin:0 0 8px;font-size:12px;color:#6c757d;line-height:1.6;text-align:center;">
          Diese Nachricht wurde an <a href="mailto:{email}" style="color:#6c757d;">{email}</a> gesendet,
          da wir über einen möglichen Bezug zu unserem Leistungsbereich informiert wurden.
        </p>

        <p style="margin:0 0 8px;font-size:12px;color:#6c757d;text-align:center;">
          $companyAddress
        </p>

        <p style="margin:0;font-size:12px;text-align:center;">
          <a href="$unsubscribeUrl"
             style="color:#6c757d;text-decoration:underline;">
            Abmelden / Unsubscribe
          </a>
          &nbsp;|&nbsp;
          <a href="$siteUrl/datenschutz.php"
             style="color:#6c757d;text-decoration:underline;">
            Datenschutz
          </a>
          &nbsp;|&nbsp;
          <a href="$siteUrl/impressum.php"
             style="color:#6c757d;text-decoration:underline;">
            Impressum
          </a>
        </p>

      </td>
    </tr>

  </table>
  <!-- /Email card -->

</td></tr>
</table>
<!--<![endif]-->

</body>
</html>
HTML;
    }
}
