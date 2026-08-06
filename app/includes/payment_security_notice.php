<?php
/**
 * Wiederverwendbares Zahlungssicherheits-Banner (Deutsch)
 *
 * Einbinden mit:
 *   require_once __DIR__ . '/includes/payment_security_notice.php';
 *   renderPaymentSecurityNotice();
 *
 * Optionaler $variant-Parameter:
 *   'full'    – vollständiges Banner mit aufgeklappten Hinweistexten (Standard)
 *   'compact' – einzeilige kompakte Version für die Kopfzeile
 */

function renderPaymentSecurityNotice(string $variant = 'full'): void
{
    if ($variant === 'compact') {
?>
<div class="payment-security-compact-notice" style="background:linear-gradient(90deg,#1a5c2a 0%,#0f4c81 100%);color:#fff;padding:8px 16px;font-size:12px;display:flex;align-items:center;gap:10px;border-bottom:2px solid rgba(255,255,255,0.15);">
    <i class="anticon anticon-lock" style="font-size:15px;flex-shrink:0;"></i>
    <span>
        <strong>Sicherheitshinweis:</strong>
        Überweisen Sie Zahlungen <strong>ausschließlich</strong> über die
        <a href="deposit.php" style="color:#7fc9ff;font-weight:bold;text-decoration:underline;">offizielle Einzahlungsseite</a>.
        Wir werden Sie niemals bitten, Geld an externe Adressen zu senden. Zahlungen außerhalb der Plattform werden <strong>nicht bearbeitet</strong>.
    </span>
    <a href="#" class="ml-auto text-white" style="opacity:.7;font-size:16px;line-height:1;text-decoration:none;" onclick="this.closest('.payment-security-compact-notice').style.display='none';return false;" title="Schließen" aria-label="Schließen">&times;</a>
</div>
<?php
        return;
    }
    // Full variant
?>
<div class="payment-security-notice card mb-4 border-0 shadow-sm" style="border-left:5px solid #1a5c2a !important;">
    <div class="card-body p-0">
        <!-- Header bar -->
        <div style="background:linear-gradient(90deg,#1a5c2a 0%,#0f4c81 100%);padding:14px 20px;border-radius:4px 4px 0 0;display:flex;align-items:center;gap:12px;">
            <div style="background:rgba(255,255,255,0.2);border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="anticon anticon-lock" style="font-size:18px;color:#fff;"></i>
            </div>
            <div style="color:#fff;">
                <div style="font-weight:700;font-size:14px;">Sicherheitshinweis: Zahlungen nur über die offizielle Einzahlungsseite</div>
                <div style="font-size:12px;opacity:.9;">Schützen Sie sich vor unbefugten Zahlungsanforderungen</div>
            </div>
            <div class="ml-auto d-none d-md-flex" style="gap:8px;">
                <span style="background:rgba(255,255,255,0.2);color:#fff;font-size:11px;padding:4px 10px;border-radius:20px;white-space:nowrap;">
                    🔒 SSL-gesichert
                </span>
                <span style="background:rgba(255,255,255,0.2);color:#fff;font-size:11px;padding:4px 10px;border-radius:20px;white-space:nowrap;">
                    ✅ Plattformschutz aktiv
                </span>
            </div>
        </div>
        <!-- Body -->
        <div style="padding:18px 20px;background:#f0f9f1;">
            <div class="row">
                <div class="col-md-7">
                    <h6 style="color:#1a5c2a;font-weight:700;margin-bottom:10px;">
                        <i class="anticon anticon-safety-certificate mr-1"></i>
                        Ihre Zahlungssicherheit
                    </h6>
                    <ul style="margin:0;padding-left:20px;font-size:13px;color:#333;line-height:1.8;">
                        <li>Alle Zahlungen müssen <strong>ausschließlich</strong> über die
                            <a href="deposit.php" style="color:#0f4c81;font-weight:bold;">offizielle Einzahlungsseite</a>
                            getätigt werden.</li>
                        <li>Unser Support-Team und unsere Mitarbeiter werden Sie <strong>niemals</strong> per Chat, E-Mail oder Telefon bitten, Geld an eine externe Adresse oder ein Bankkonto zu überweisen.</li>
                        <li>Zahlungen außerhalb der offiziellen Einzahlungsseite werden von uns <strong>nicht bearbeitet und nicht anerkannt</strong>.</li>
                        <li>Sollte jemand Sie auffordern, außerhalb der Plattform zu zahlen, melden Sie dies sofort über das <a href="support.php" style="color:#0f4c81;font-weight:bold;">Support-System</a>.</li>
                    </ul>
                </div>
                <div class="col-md-5 mt-3 mt-md-0">
                    <div style="background:#fff;border:1px solid #c8e6c9;border-radius:8px;padding:14px;">
                        <div style="font-size:12px;font-weight:700;color:#c0392b;margin-bottom:8px;">
                            <i class="anticon anticon-warning mr-1"></i> Niemals Zahlungen senden an:
                        </div>
                        <ul style="margin:0;padding-left:16px;font-size:12px;color:#555;line-height:1.8;">
                            <li>Externe Kryptowallet-Adressen im Chat</li>
                            <li>Persönliche Bankkonten per E-Mail</li>
                            <li>Adressen außerhalb unserer Zahlungsseite</li>
                            <li>Konten, die per Telefon mitgeteilt werden</li>
                        </ul>
                        <a href="deposit.php" class="btn btn-success btn-sm mt-3 d-block" style="font-size:12px;border-radius:6px;">
                            <i class="anticon anticon-arrow-right mr-1"></i> Zur sicheren Einzahlungsseite
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
}
