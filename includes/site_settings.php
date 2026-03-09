<?php
/**
 * Loads company/site settings from the system_settings database table.
 * Provides $siteSettings array to all root-level public pages.
 *
 * Required columns used: brand_name, company_address, contact_email
 */

if (!isset($siteSettings)) {
    $siteSettings = [];

    try {
        $host     = getenv('DB_HOST')     ?: 'localhost';
        $dbname   = getenv('DB_NAME')     ?: 'novalnet-ai';
        $username = getenv('DB_USER')     ?: 'novalnet';
        $password = getenv('DB_PASSWORD') ?: '';

        $pdoSite = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );

        $stmt = $pdoSite->prepare("SELECT brand_name, company_address, contact_email, fca_reference_number, site_url FROM system_settings WHERE id = 1 LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row) {
            $siteSettings = $row;
        }
    } catch (PDOException $e) {
        error_log("site_settings.php: failed to load system_settings – " . $e->getMessage());
    }

    // Fallback defaults so pages still render if DB is unreachable
    $siteSettings += [
        'brand_name'           => 'Novalnet AI',
        'company_address'      => 'Davidson House, Forbury Square, Reading, RG1 3EU, United Kingdom',
        'contact_email'        => 'info@novalnet-ai.de',
        'fca_reference_number' => '122702',
        'site_url'             => 'https://novalnet-ai.de',
    ];
}
