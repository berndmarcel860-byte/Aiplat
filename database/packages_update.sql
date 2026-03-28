-- Migration: Update all packages with correct durations, descriptions and feature lists.
-- Trial 48h: id=5 (free, 2 days, up to 100k)
-- 1 Year:    id=1 (365 days)
-- 3 Years:   id=2 (1095 days)
-- 5 Years:   id=3 (1825 days)
-- Unlimited: id=4 (36500 days / lifetime)

-- ─── Trial 48h ────────────────────────────────────────────────────────────────
UPDATE `packages` SET
    `name`            = 'Trial 48h',
    `description`     = 'Kostenloser 48-Stunden-Testzugang – erkunden Sie unsere Plattform ohne Risiko. Rückforderungsfälle bis 100.000 € können erfasst werden. Der Zugang läuft automatisch nach 48 Stunden ab.',
    `price`           = 0.00,
    `duration_days`   = 2,
    `features`        = '["Zugang für 48 Stunden (2 Tage)","Fälle bis 100.000 € erfassbar","Eingeschränktes Dashboard","KI-Algorithmus-Vorschau","E-Mail-Support (nur Test)","Keine Zahlungspflicht"]',
    `recovery_speed`  = 'Sofortzugang',
    `support_level`   = 'E-Mail (Testphase)',
    `updated_at`      = NOW()
WHERE `id` = 5;

-- ─── 1 Year ───────────────────────────────────────────────────────────────────
UPDATE `packages` SET
    `name`            = '1 Jahr',
    `description`     = 'Einsteigerpaket mit 12 Monaten Laufzeit. Vollständiger Zugang zur Rückforderungsplattform, KI-gestützte Fallbearbeitung und persönlicher Fallbegleitung – ideal für Fälle bis 250.000 €.',
    `price`           = 399.00,
    `duration_days`   = 365,
    `features`        = '["12 Monate Laufzeit","Fälle bis 250.000 € erfassbar","Voller KI-Algorithmus-Zugang","Persönliche Fallbegleitung","Vollständige Dokumentenprüfung","E-Mail-Support (Antwort innerhalb 48 h)","Alle Fälle & Ergebnisse einsehbar","Monatliche Fortschrittsberichte"]',
    `recovery_speed`  = '4–6 Wochen',
    `support_level`   = 'E-Mail (48 h Antwortzeit)',
    `updated_at`      = NOW()
WHERE `id` = 1;

-- ─── 3 Years ──────────────────────────────────────────────────────────────────
UPDATE `packages` SET
    `name`            = '3 Jahre',
    `description`     = 'Komfortpaket mit 3 Jahren Laufzeit – mehr Zeit, mehr Unterstützung, mehr Erfolg. Erweiterte KI-Analysen und dedizierter Fallmanager für Rückforderungen bis 750.000 €.',
    `price`           = 779.00,
    `duration_days`   = 1095,
    `features`        = '["36 Monate Laufzeit","Fälle bis 750.000 € erfassbar","Erweiterter KI-Algorithmus-Zugang","Dedizierter Fallmanager","Priorisierte Fallbearbeitung","Telefon- & E-Mail-Support (Geschäftszeiten)","Rechtsdokumentenerstattung (Standard)","Vierteljährliche Strategiegespräche","Alle Auszahlungen freigeschaltet","Detaillierte Transaktionsnachverfolgung"]',
    `recovery_speed`  = '2–4 Wochen',
    `support_level`   = 'Telefon & E-Mail (Geschäftszeiten)',
    `updated_at`      = NOW()
WHERE `id` = 2;

-- ─── 5 Years ──────────────────────────────────────────────────────────────────
UPDATE `packages` SET
    `name`            = '5 Jahre',
    `description`     = 'Premium-Paket mit 5 Jahren Laufzeit – maximale Laufzeit, direkter Anwaltszugang und beschleunigte Bearbeitung. Für komplexe Fälle bis 2.000.000 € mit juristischer Unterstützung.',
    `price`           = 1880.00,
    `duration_days`   = 1825,
    `features`        = '["60 Monate Laufzeit","Fälle bis 2.000.000 € erfassbar","Vollständiger Premium-KI-Zugang","Direkter Anwaltszugang","Beschleunigte Fallbearbeitung (Expresskanal)","24/7 Prioritätssupport","Rechtsdokumentenvorbereitung (Erweitert)","Monatliche Strategiegespräche mit Experten","Blockchain-Transaktionsanalyse","Vollständige Auszahlungshistorie & Berichte","Dedizierter Account Manager","Internationale Rückforderungsunterstützung"]',
    `recovery_speed`  = '1–2 Wochen',
    `support_level`   = '24/7 Prioritätssupport',
    `updated_at`      = NOW()
WHERE `id` = 3;

-- ─── Unlimited (Lifetime) ─────────────────────────────────────────────────────
UPDATE `packages` SET
    `name`            = 'Unbegrenzt',
    `description`     = 'Unbegrenztes Lifetime-Paket – einmalige Investition, lebenslanger Zugang. Kein Ablaufdatum, kein Limit. Für VIP-Kunden mit höchsten Ansprüchen, persönlichem Senior-Spezialisten und juristischem Vollteam.',
    `price`           = 2730.00,
    `duration_days`   = 36500,
    `features`        = '["Unbegrenzte Laufzeit (Lifetime)","Keine Fallbetrags-Begrenzung","VIP-KI-Algorithmus (höchste Priorität)","Senior Recovery-Spezialist (persönlich)","Juristisches Vollteam engagiert","Sofortige Fallzuweisung (< 2 Stunden)","Persönlicher Account Manager (24/7)","Unbegrenzte Rechtsdokumentenvorbereitung","Tägliche Statusupdates","Blockchain-Forensik & Tiefenanalyse","Internationale Behördenkontakte","Vollständiger Zugang zu allen zukünftigen Features","VIP-Onboarding & Strategie-Workshop","Rückerstattungsgarantie (Bedingungen gelten)"]',
    `recovery_speed`  = '3–7 Tage',
    `support_level`   = 'Persönlicher Account Manager (24/7)',
    `updated_at`      = NOW()
WHERE `id` = 4;
