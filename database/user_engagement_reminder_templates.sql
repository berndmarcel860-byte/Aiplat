INSERT INTO `email_templates` (`template_key`, `subject`, `content`, `variables`, `is_active`, `created_at`, `updated_at`)
VALUES
(
  'user_never_logged_in_reminder_3_days',
  'Willkommen bei {brand_name} – bitte aktivieren Sie jetzt Ihren Zugang',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>vielen Dank für Ihre Registrierung bei <strong>{brand_name}</strong>. Ihr Konto wurde am <strong>{registration_date}</strong> angelegt, ein erster Login ist jedoch bislang noch nicht erfolgt.</p>
<div class="highlight-box">
  <h3>Warum Ihr erster Login jetzt wichtig ist</h3>
  <p>Erst nach Ihrem ersten Login können Sie Ihre persönlichen Daten prüfen, den Bearbeitungsstand verfolgen und die nächsten Schritte in Ihrem Kundenportal vollständig nutzen.</p>
  <p><strong>Wichtiger Hinweis:</strong> Wenn in den nächsten <strong>{grace_days} Tagen</strong> weiterhin kein Login erfolgt, wird Ihr Zugang am <strong>{deactivation_date}</strong> automatisch vorübergehend eingeschränkt.</p>
</div>
<p>Bitte melden Sie sich jetzt kurz an und prüfen Sie Ihre Angaben. So stellen Sie sicher, dass unsere Systeme Ihren Vorgang ohne Verzögerung weiterverarbeiten können.</p>
<p style="text-align:center;margin:26px 0;">
  <a href="{login_url}" class="btn">Jetzt im Portal anmelden</a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',
  '["first_name","last_name","brand_name","registration_date","grace_days","deactivation_date","login_url","dashboard_url","contact_email"]',
  1,
  NOW(),
  NOW()
),
(
  'user_never_logged_in_suspended',
  'Ihr {brand_name}-Konto wurde vorübergehend eingeschränkt',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>da seit Ihrer Registrierung am <strong>{registration_date}</strong> kein erster Login erfolgt ist, wurde Ihr Konto aus Sicherheits- und Verwaltungsgründen vorübergehend eingeschränkt.</p>
<div class="highlight-box" style="background:#fff7e6;border-left-color:#fa8c16;">
  <h3>Aktueller Kontostatus</h3>
  <p>Ihr Zugang wurde automatisch auf <strong>suspendiert</strong> gesetzt. Ihre Daten bleiben dabei erhalten.</p>
</div>
<p>Wenn Sie Ihr Konto weiterhin nutzen möchten, wenden Sie sich bitte an unseren Support oder antworten Sie über den vorgesehenen Kontaktweg, damit wir die Reaktivierung prüfen können.</p>
<p style="text-align:center;margin:26px 0;">
  <a href="{support_url}" class="btn">Support kontaktieren</a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',
  '["first_name","last_name","brand_name","registration_date","support_url","contact_email"]',
  1,
  NOW(),
  NOW()
),
(
  'user_inactive_case_reminder_5_days',
  'Bitte prüfen Sie Ihr Portal – Ihr Vorgang wartet auf Ihre Aufmerksamkeit',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>Sie haben sich seit <strong>{last_login_date}</strong> nicht mehr in Ihr Kundenportal eingeloggt. Wir empfehlen Ihnen, den aktuellen Stand Ihres Kontos wieder zu prüfen.</p>
<div class="highlight-box">
  <h3>Ihr Status im Überblick</h3>
  <p>Sie sind aktuell seit <strong>{days_inactive} Tagen</strong> inaktiv. Ein kurzer Login genügt, um neue Hinweise, Aufgaben und eventuelle Rückfragen einzusehen.</p>
  {#if has_reported_case_amount}
  <p><strong>Gemeldeter Fallbetrag:</strong> {reported_case_amount}</p>
  {/if}
  {#if case_number}
  <p><strong>Aktuelle Fallreferenz:</strong> {case_number}</p>
  {/if}
</div>
<p>Unsere Empfehlung: Melden Sie sich jetzt an, prüfen Sie Ihre Fallseite und stellen Sie sicher, dass keine Rückmeldung oder Unterlage von Ihnen benötigt wird.</p>
<p style="text-align:center;margin:26px 0;">
  <a href="{cases_url}" class="btn">Fallstatus prüfen</a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',
  '["first_name","last_name","brand_name","last_login_date","days_inactive","reported_case_amount","has_reported_case_amount","case_number","case_status","cases_url","dashboard_url"]',
  1,
  NOW(),
  NOW()
),
(
  'user_kyc_required_withdrawal_notice',
  'KYC erforderlich – Auszahlungen bleiben bis zur Verifizierung gesperrt',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>für Ihr Konto liegt derzeit noch keine abgeschlossene Identitätsprüfung (KYC) vor.</p>
<div class="highlight-box" style="background:#fff1f0;border-left-color:#cf1322;">
  <h3>Wichtiger Hinweis zu Auszahlungen</h3>
  <p>Ohne erfolgreiche KYC-Verifizierung können <strong>keine Auszahlungen</strong> freigegeben oder verarbeitet werden.</p>
  <p>{kyc_status_detail}</p>
</div>
<p>Bitte reichen Sie die benötigten Unterlagen möglichst zeitnah ein. In der Regel dauert die Einreichung nur wenige Minuten.</p>
<p style="text-align:center;margin:26px 0;">
  <a href="{kyc_url}" class="btn">KYC jetzt abschließen</a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Compliance-Team</p>',
  '["first_name","last_name","brand_name","kyc_status_detail","kyc_url","withdrawal_url","support_url","contact_email"]',
  1,
  NOW(),
  NOW()
),
(
  'user_onboarding_package_reminder',
  'Bitte vervollständigen Sie Ihr Onboarding und prüfen Sie Ihren Fortschritt',
  '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>
<p>für Ihr Konto ist bereits ein aktives Paket hinterlegt{#if package_name} (<strong>{package_name}</strong>){/if}. Damit wir Ihren Vorgang optimal weiterverarbeiten können, fehlt jedoch noch ein vollständiger Abschluss Ihres Onboardings.</p>
<div class="highlight-box">
  <h3>Warum Ihr nächster Login wichtig ist</h3>
  <p>{algorithm_status}</p>
  {#if has_reported_case_amount}
  <p><strong>Gemeldeter Betrag aus Ihrem Vorgang:</strong> {reported_case_amount}</p>
  {/if}
</div>
<p>Bitte loggen Sie sich ein, schließen Sie Ihr Onboarding ab und prüfen Sie, ob noch Angaben oder KYC-Unterlagen fehlen.</p>
<p style="text-align:center;margin:26px 0;">
  <a href="{onboarding_url}" class="btn">Onboarding fortsetzen</a>
</p>
<p style="text-align:center;margin:10px 0 0;">
  <a href="{dashboard_url}" style="color:#2950a8;text-decoration:none;font-weight:600;">Zum Dashboard wechseln</a>
</p>
<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>',
  '["first_name","last_name","brand_name","package_name","algorithm_status","reported_case_amount","has_reported_case_amount","onboarding_url","dashboard_url","packages_url","kyc_url"]',
  1,
  NOW(),
  NOW()
)
ON DUPLICATE KEY UPDATE
  `subject` = VALUES(`subject`),
  `content` = VALUES(`content`),
  `variables` = VALUES(`variables`),
  `is_active` = VALUES(`is_active`),
  `updated_at` = VALUES(`updated_at`);
