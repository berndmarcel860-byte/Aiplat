<?php 
/**
 * Zahlungsmethoden Verwaltungsseite
 * Ermöglicht Benutzern die Verwaltung ihrer Fiat- und Krypto-Zahlungsmethoden
 * Aktualisiert: 2026-03-01 - Modernes professionelles Design mit Tabellen
 */
include 'header.php'; 
?>

<style>
/* cases.php-inspired Professional Styling */
.main-content {
    padding: 20px;
}

/* Page Header (matches cases.php) */
.page-header {
    margin-bottom: 24px;
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    color: #2d3748;
    margin: 0;
}

.m-b-10 {
    margin-bottom: 10px;
    color: #718096;
}

/* Card Styling (matches cases.php) */
.card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-bottom: 24px;
}

.card-header {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: 16px 20px;
}

.card-title {
    font-size: 16px;
    font-weight: 600;
    color: #2d3748;
}

.card-body {
    padding: 20px;
}

/* Refresh Button */
.refresh-btn {
    padding: 6px 12px;
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.refresh-btn:hover {
    background: #f7fafc;
    border-color: #cbd5e0;
}

/* Table Styling (matches cases.php) */
.payment-table {
    width: 100%;
}

.payment-table.table-borderless thead th {
    border-bottom: 2px solid #e2e8f0;
    padding: 12px 16px;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    color: #4a5568;
    letter-spacing: 0.5px;
}

.payment-table tbody tr {
    border-bottom: 1px solid #e2e8f0;
}

.payment-table tbody tr:hover {
    background: #f7fafc;
}

.payment-table tbody td {
    padding: 12px 16px;
    vertical-align: middle;
}

.align-middle {
    vertical-align: middle !important;
}

.nowrap {
    white-space: nowrap;
}

/* Status Badges */
.badge {
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 4px;
}

.badge.bg-success {
    background-color: #10b981 !important;
}

.badge.bg-warning {
    background-color: #f59e0b !important;
}

.badge.bg-danger {
    background-color: #ef4444 !important;
}

.badge.bg-secondary {
    background-color: #6b7280 !important;
}

.badge.bg-info {
    background-color: #3b82f6 !important;
}

/* Button Groups */
.btn-group {
    display: inline-flex;
    gap: 4px;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
    border-radius: 4px;
}

.btn-info {
    background-color: #17a2b8;
    border-color: #17a2b8;
    color: white;
}

.btn-primary {
    background-color: #4e73df;
    border-color: #4e73df;
    color: white;
}

.btn-success {
    background-color: #10b981;
    border-color: #10b981;
    color: white;
}

.btn-warning {
    background-color: #f59e0b;
    border-color: #f59e0b;
    color: white;
}

.btn-danger {
    background-color: #ef4444;
    border-color: #ef4444;
    color: white;
}

.btn-light {
    background-color: #f7fafc;
    border-color: #e2e8f0;
    color: #4a5568;
}

.btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

/* Modal Styling (matches cases.php) */
.modal-header.bg-light {
    background-color: #f7fafc !important;
    border-bottom: 1px solid #e2e8f0;
}

.modal-title {
    font-size: 16px;
    font-weight: 600;
    color: #2d3748;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    border-top: 1px solid #e2e8f0;
    padding: 16px 20px;
}

/* Loading State */
.loading-state {
    text-align: center;
    padding: 40px 20px;
    color: #718096;
}

.loading-state i {
    font-size: 32px;
    margin-bottom: 12px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #718096;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    color: #cbd5e0;
}

/* Form Controls */
.form-control {
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 8px 12px;
}

.form-control:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1);
}

/* Utilities */
.text-end {
    text-align: right !important;
}

.me-1 {
    margin-right: 4px !important;
}

.me-2 {
    margin-right: 8px !important;
}

.mb-4 {
    margin-bottom: 24px !important;
}

.d-flex {
    display: flex !important;
}

.align-items-center {
    align-items: center !important;
}

.justify-content-between {
    justify-content: space-between !important;
}

.m-0 {
    margin: 0 !important;
}

/* DataTables Custom Styling */
.dataTables_wrapper {
    padding: 0;
}

.dataTables_length select,
.dataTables_filter input {
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 14px;
}

.dataTables_paginate .paginate_button {
    padding: 6px 12px;
    margin: 0 2px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
}

.dataTables_paginate .paginate_button.current {
    background: #4e73df;
    color: white !important;
    border-color: #4e73df;
}
</style>

<div class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="page-title">Zahlungsmethoden</h2>
                    <p class="m-b-10">Verwalten Sie Ihre Fiat- und Kryptowährungs-Zahlungsmethoden sicher</p>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-primary btn-lg me-2" onclick="showAddFiatModal()">
                        <i class="anticon anticon-plus"></i> Bankkonto hinzufügen
                    </button>
                    <button class="btn btn-success btn-lg" onclick="showAddCryptoModal()">
                        <i class="anticon anticon-plus"></i> Krypto-Wallet hinzufügen
                    </button>
                </div>
            </div>
        </div>

        <!-- Info Alert -->
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="anticon anticon-info-circle me-2"></i>
            <strong>Über die Kryptowährungs-Wallet-Verifizierung:</strong> 
            Kryptowährungs-Wallets erfordern eine Verifizierung durch einen Satoshi-Test, bevor sie für Auszahlungen verwendet werden können.
            <a href="satoshi-test-guide.php" class="alert-link ms-2">Mehr erfahren</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <!-- Payment Methods Tables -->
        <div class="row">
            <!-- Fiat Payment Methods Card -->
            <div class="col-lg-12 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title d-flex align-items-center m-0">
                            <i class="anticon anticon-bank me-2"></i>
                            Bankkonten
                        </h4>
                        <button class="btn btn-light refresh-btn" onclick="loadPaymentMethods()" title="Tabelle aktualisieren">
                            <i class="anticon anticon-reload"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <div id="fiatMethodsContainer">
                                <div class="loading-state">
                                    <i class="fas fa-spinner"></i>
                                    <p>Lade Bankkonten...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Crypto Wallets Card -->
            <div class="col-lg-12 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title d-flex align-items-center m-0">
                            <i class="anticon anticon-bitcoin me-2"></i>
                            Krypto-Wallets
                        </h4>
                        <button class="btn btn-light refresh-btn" onclick="loadPaymentMethods()" title="Tabelle aktualisieren">
                            <i class="anticon anticon-reload"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <div id="cryptoMethodsContainer">
                                <div class="loading-state">
                                    <i class="fas fa-spinner"></i>
                                    <p>Lade Krypto-Wallets...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Content Wrapper END -->

<!-- Fiat-Zahlungsmethode hinzufügen Modal -->
<div class="modal fade" id="addFiatModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="anticon anticon-bank me-2"></i>
                    Bankkonto hinzufügen
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addFiatForm">
                <div class="modal-body">
                    <input type="hidden" name="type" value="fiat">
                    
                    <div class="form-group">
                        <label>Zahlungsmethode <span class="text-danger">*</span></label>
                        <select class="form-control" name="payment_method" required>
                            <option value="">Bitte wählen...</option>
                            <option value="Bank Transfer">Banküberweisung (SEPA)</option>
                            <option value="Wire Transfer">Auslandsüberweisung</option>
                            <option value="Credit Card">Kredit-/Debitkarte</option>
                            <option value="PayPal">PayPal</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Kontoinhaber <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="account_holder" 
                               placeholder="Max Mustermann" required>
                    </div>

                    <div class="form-group">
                        <label>Bankname <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="bank_name" 
                               placeholder="Sparkasse München" required>
                    </div>

                    <div class="form-group">
                        <label>IBAN <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="iban" 
                               placeholder="DE89 3704 0044 0532 0130 00" required>
                    </div>

                    <div class="form-group">
                        <label>BIC/SWIFT</label>
                        <input type="text" class="form-control" name="bic" 
                               placeholder="COBADEFFXXX">
                    </div>

                    <div class="form-group">
                        <label>Land <span class="text-danger">*</span></label>
                        <select class="form-control" name="country" required>
                            <option value="">Bitte wählen...</option>
                            <option value="DE">Deutschland</option>
                            <option value="AT">Österreich</option>
                            <option value="CH">Schweiz</option>
                            <option value="FR">Frankreich</option>
                            <option value="IT">Italien</option>
                            <option value="ES">Spanien</option>
                            <option value="NL">Niederlande</option>
                            <option value="BE">Belgien</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Beschreibung (optional)</label>
                        <input type="text" class="form-control" name="label" 
                               placeholder="z.B. Mein Hauptkonto">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Abbrechen
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Bankkonto hinzufügen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Krypto-Wallet hinzufügen Modal -->
<div class="modal fade" id="addCryptoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="anticon anticon-bitcoin me-2"></i>
                    Krypto-Wallet hinzufügen
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCryptoForm">
                <div class="modal-body">
                    <input type="hidden" name="type" value="crypto">
                    <input type="hidden" name="payment_method" id="cryptoPaymentMethodHidden">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Wichtig:</strong> Nach dem Hinzufügen müssen Sie Ihre Wallet durch einen Satoshi-Test verifizieren, bevor Sie Auszahlungen vornehmen können.
                    </div>

                    <div class="form-group">
                        <label>Kryptowährung <span class="text-danger">*</span></label>
                        <select class="form-control" name="cryptocurrency" required>
                            <option value="">Bitte wählen...</option>
                            <option value="Bitcoin">Bitcoin (BTC)</option>
                            <option value="Ethereum">Ethereum (ETH)</option>
                            <option value="Litecoin">Litecoin (LTC)</option>
                            <option value="Ripple">Ripple (XRP)</option>
                            <option value="Bitcoin Cash">Bitcoin Cash (BCH)</option>
                            <option value="Tether">Tether (USDT)</option>
                            <option value="USDC">USD Coin (USDC)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Netzwerk <span class="text-danger">*</span></label>
                        <select class="form-control" name="network" required>
                            <option value="">Bitte wählen...</option>
                            <option value="Mainnet">Mainnet</option>
                            <option value="ERC20">ERC20 (Ethereum)</option>
                            <option value="TRC20">TRC20 (Tron)</option>
                            <option value="BEP20">BEP20 (BSC)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Wallet-Adresse <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="wallet_address" 
                               placeholder="1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa" required>
                        <small class="form-text text-muted">
                            Bitte überprüfen Sie die Adresse sorgfältig. Falsche Adressen können zu Geldverlust führen.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Beschreibung (optional)</label>
                        <input type="text" class="form-control" name="label" 
                               placeholder="z.B. Meine BTC Cold Wallet">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Abbrechen
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Wallet hinzufügen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Payment Method Details Modal -->
<div class="modal fade" id="viewDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="anticon anticon-eye me-2"></i>
                    <span id="viewModalTitle">Zahlungsmethode Details</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="viewDetailsContent">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Schließen
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Fiat Payment Method Modal -->
<div class="modal fade" id="editFiatModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="anticon anticon-edit me-2"></i>
                    Bankkonto bearbeiten
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editFiatForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_fiat_id" name="id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_payment_method">Zahlungsmethode *</label>
                            <select class="form-control" id="edit_payment_method" name="payment_method" required>
                                <option value="">Bitte wählen...</option>
                                <option value="Banküberweisung (SEPA)">Banküberweisung (SEPA)</option>
                                <option value="Auslandsüberweisung">Auslandsüberweisung</option>
                                <option value="Kredit-/Debitkarte">Kredit-/Debitkarte</option>
                                <option value="PayPal">PayPal</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_bank_name">Bankname *</label>
                            <input type="text" class="form-control" id="edit_bank_name" name="bank_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_account_holder">Kontoinhaber *</label>
                            <input type="text" class="form-control" id="edit_account_holder" name="account_holder" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_iban">IBAN *</label>
                            <input type="text" class="form-control" id="edit_iban" name="iban" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_bic">BIC/SWIFT</label>
                            <input type="text" class="form-control" id="edit_bic" name="bic">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_country">Land *</label>
                            <select class="form-control" id="edit_country" name="country" required>
                                <option value="">Bitte wählen...</option>
                                <option value="Deutschland">Deutschland</option>
                                <option value="Österreich">Österreich</option>
                                <option value="Schweiz">Schweiz</option>
                                <option value="Frankreich">Frankreich</option>
                                <option value="Italien">Italien</option>
                                <option value="Spanien">Spanien</option>
                                <option value="Niederlande">Niederlande</option>
                                <option value="Belgien">Belgien</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="edit_label">Beschreibung (optional)</label>
                            <input type="text" class="form-control" id="edit_label" name="label" placeholder="z.B. Mein Hauptkonto">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Abbrechen
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Änderungen speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Crypto Payment Method Modal -->
<div class="modal fade" id="editCryptoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="anticon anticon-edit me-2"></i>
                    Krypto-Wallet bearbeiten
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCryptoForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_crypto_id" name="id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_cryptocurrency">Kryptowährung *</label>
                            <select class="form-control" id="edit_cryptocurrency" name="cryptocurrency" required>
                                <option value="">Bitte wählen...</option>
                                <option value="Bitcoin">Bitcoin (BTC)</option>
                                <option value="Ethereum">Ethereum (ETH)</option>
                                <option value="Litecoin">Litecoin (LTC)</option>
                                <option value="Ripple">Ripple (XRP)</option>
                                <option value="Bitcoin Cash">Bitcoin Cash (BCH)</option>
                                <option value="Tether">Tether (USDT)</option>
                                <option value="USD Coin">USD Coin (USDC)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_network">Netzwerk *</label>
                            <select class="form-control" id="edit_network" name="network" required>
                                <option value="">Bitte wählen...</option>
                                <option value="Mainnet">Mainnet</option>
                                <option value="ERC20">ERC20 (Ethereum)</option>
                                <option value="TRC20">TRC20 (Tron)</option>
                                <option value="BEP20">BEP20 (BSC)</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="edit_wallet_address">Wallet-Adresse *</label>
                            <input type="text" class="form-control" id="edit_wallet_address" name="wallet_address" required>
                            <small class="form-text text-muted">
                                <i class="fas fa-exclamation-triangle"></i> Bitte überprüfen Sie die Adresse sorgfältig. Falsche Adressen können zu Geldverlust führen.
                            </small>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="edit_crypto_label">Beschreibung (optional)</label>
                            <input type="text" class="form-control" id="edit_crypto_label" name="label" placeholder="z.B. Meine BTC Cold Wallet">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Abbrechen
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Änderungen speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Verify Wallet Modal -->
<div class="modal fade" id="verifyWalletModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="anticon anticon-safety me-2"></i>
                    Wallet Verifizierung - Satoshi Test
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="verifyWalletForm">
                <div class="modal-body">
                    <input type="hidden" id="verify_wallet_id" name="wallet_id">
                    
                    <div class="alert alert-danger">
                        <h5 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Warum ist die Wallet-Verifizierung zwingend erforderlich?</h5>
                        <p class="mb-2"><strong>Die Verifizierung Ihres Crypto-Wallets durch den Satoshi-Test ist aus folgenden kritischen Gründen erforderlich:</strong></p>
                        
                        <h6 class="mt-3"><i class="fas fa-lock"></i> Sicherheit & Betrugsschutz:</h6>
                        <ul>
                            <li><strong>Eigentumsnachweis:</strong> Nur der tatsächliche Wallet-Besitzer kann eine Transaktion von dieser Adresse initiieren</li>
                            <li><strong>Schutz vor Identitätsdiebstahl:</strong> Verhindert, dass Dritte fremde Wallet-Adressen missbrauchen</li>
                            <li><strong>Betrugsprävention:</strong> Reduziert das Risiko von Geldwäsche und betrügerischen Transaktionen</li>
                        </ul>
                        
                        <h6 class="mt-3"><i class="fas fa-university"></i> Compliance & Regulierung:</h6>
                        <ul>
                            <li><strong>Gesetzliche Anforderungen:</strong> Erfüllt KYC/AML-Vorschriften (Know Your Customer / Anti-Money Laundering)</li>
                            <li><strong>Finanzaufsicht:</strong> Entspricht den Richtlinien der Finanzregulierungsbehörden</li>
                            <li><strong>Plattform-Schutz:</strong> Schützt unsere Plattform vor illegalen Aktivitäten</li>
                        </ul>
                        
                        <h6 class="mt-3"><i class="fas fa-coins"></i> Transaktionssicherheit:</h6>
                        <ul>
                            <li><strong>Einzahlungen:</strong> Ohne Verifizierung können wir eingehende Zahlungen nicht sicher Ihrem Konto zuordnen</li>
                            <li><strong>Auszahlungen:</strong> Verhindert versehentliche Überweisungen an falsche oder nicht kontrollierte Adressen</li>
                            <li><strong>Rückabwicklung:</strong> Bei Problemen können verifizierte Transaktionen besser nachvollzogen werden</li>
                        </ul>
                        
                        <h6 class="mt-3"><i class="fas fa-shield-alt"></i> Ihre Sicherheit:</h6>
                        <ul>
                            <li><strong>Schutz vor Verlusten:</strong> Verhindert, dass Gelder an falsche Adressen gesendet werden</li>
                            <li><strong>Kontosicherheit:</strong> Zusätzliche Sicherheitsebene für Ihr Konto</li>
                            <li><strong>Verantwortung:</strong> Sie behalten die volle Kontrolle über Ihre Krypto-Assets</li>
                        </ul>
                        
                        <div class="alert alert-warning mt-3 mb-0">
                            <strong><i class="fas fa-ban"></i> Wichtig:</strong> Ohne verifiziertes Wallet können <u>keine Einzahlungen empfangen</u> und <u>keine Auszahlungen durchgeführt</u> werden. Die Verifizierung ist eine einmalige Maßnahme zum Schutz aller Beteiligten.
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Was ist der Satoshi Test?</h6>
                        <p class="mb-0">Sie senden einen sehr kleinen Test-Betrag (meist 1000 Satoshi = 0.00001 BTC oder gleichwertig) von Ihrer Wallet an unsere Verifizierungs-Adresse. Dies beweist unwiderlegbar, dass Sie der Besitzer des Wallets sind und Zugriff auf die Private Keys haben.</p>
                    </div>
                    
                    <div id="verifyWalletDetails">
                        <!-- Wallet details will be populated here -->
                    </div>
                    
                    <div class="card mt-3 bg-light">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-list-ol"></i> Verifizierungsschritte:</h6>
                            <ol class="mb-0">
                                <li>Senden Sie den angegebenen Betrag an die Verifizierungs-Adresse</li>
                                <li>Warten Sie auf die Blockchain-Bestätigung (5-10 Minuten)</li>
                                <li>Geben Sie die Transaktions-ID unten ein</li>
                                <li>Unser System wird die Transaktion überprüfen</li>
                            </ol>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12 mb-3">
                            <label for="verify_transaction_id">Transaktions-ID (TxID) *</label>
                            <input type="text" class="form-control" id="verify_transaction_id" name="transaction_id" required placeholder="Geben Sie die Transaktions-ID ein">
                            <small class="form-text text-muted">
                                Die Transaktions-ID finden Sie in Ihrer Wallet nach dem Senden
                            </small>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="verify_notes">Anmerkungen (optional)</label>
                            <textarea class="form-control" id="verify_notes" name="notes" rows="2" placeholder="Zusätzliche Informationen..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Abbrechen
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Verifizierung einreichen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
<script>
// Zahlungsmethoden laden
function loadPaymentMethods() {
    $.ajax({
        url: 'ajax/get_payment_methods.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // API returns response.methods.fiat and response.methods.crypto
                const fiatMethods = (response.methods && response.methods.fiat) || response.fiat || [];
                const cryptoMethods = (response.methods && response.methods.crypto) || response.crypto || [];
                displayFiatMethods(fiatMethods);
                displayCryptoMethods(cryptoMethods);
            } else {
                showError(response.message || 'Fehler beim Laden der Zahlungsmethoden');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading payment methods:', error);
            showError('Serverfehler beim Laden der Zahlungsmethoden');
        }
    });
}

// Fiat-Methoden anzeigen
function displayFiatMethods(methods) {
    const container = $('#fiatMethodsContainer');
    $('#fiatCount').text(methods.length);
    
    if (methods.length === 0) {
        container.html(`
            <div class="empty-state">
                <i class="fas fa-university"></i>
                <h5>Keine Bankkonten</h5>
                <p>Fügen Sie Ihr erstes Bankkonto hinzu</p>
            </div>
        `);
        return;
    }

    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#fiatMethodsTable')) {
        $('#fiatMethodsTable').DataTable().destroy();
    }

    let tableHtml = `
        <table id="fiatMethodsTable" class="payment-table table table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>Methode</th>
                    <th>Details</th>
                    <th>Status</th>
                    <th>Hinzugefügt</th>
                    <th style="width: 200px;">Aktionen</th>
                </tr>
            </thead>
            <tbody>
    `;

    methods.forEach(method => {
        const isDefault = method.is_default == 1;
        const statusClass = getStatusClass(method.verification_status);
        const statusText = getStatusTextDE(method.verification_status);
        
        tableHtml += `
            <tr>
                <td>
                    <i class="fas fa-university method-icon"></i>
                    <strong>${escapeHtml(method.bank_name || method.payment_method)}</strong>
                    ${isDefault ? '<br><span class="default-badge">Standard</span>' : ''}
                </td>
                <td>
                    <div><strong>${escapeHtml(method.account_holder)}</strong></div>
                    <div class="text-muted" style="font-size: 12px;">${maskIBAN(method.iban)}</div>
                </td>
                <td>
                    <span class="status-badge ${statusClass}">${statusText}</span>
                </td>
                <td data-order="${method.created_at}">
                    <span class="date-text">${formatDate(method.created_at)}</span>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-info" onclick='viewMethodDetails(${JSON.stringify(method)}, "fiat")' title="Details anzeigen">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="editMethod(${method.id}, 'fiat')" title="Bearbeiten">
                            <i class="fas fa-edit"></i>
                        </button>
                        ${!isDefault ? `
                        <button class="btn btn-sm btn-success" onclick="setDefaultMethod(${method.id})" title="Als Standard setzen">
                            <i class="fas fa-star"></i>
                        </button>
                        ` : ''}
                        <button class="btn btn-sm btn-danger" onclick="deleteMethod(${method.id})" title="Löschen">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tableHtml += `
            </tbody>
        </table>
    `;

    container.html(tableHtml);
    
    // Initialize DataTable with German localization
    $('#fiatMethodsTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.3/i18n/de_de.json"
        },
        "order": [[3, "desc"]], // Sort by date, newest first
        "pageLength": 10,
        "responsive": true,
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        "columnDefs": [
            { "orderable": false, "targets": 4 } // Disable sorting on actions column
        ]
    });
}

// Krypto-Methoden anzeigen
function displayCryptoMethods(methods) {
    const container = $('#cryptoMethodsContainer');
    $('#cryptoCount').text(methods.length);
    
    if (methods.length === 0) {
        container.html(`
            <div class="empty-state">
                <i class="fab fa-bitcoin"></i>
                <h5>Keine Krypto-Wallets</h5>
                <p>Fügen Sie Ihr erstes Krypto-Wallet hinzu</p>
            </div>
        `);
        return;
    }

    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#cryptoMethodsTable')) {
        $('#cryptoMethodsTable').DataTable().destroy();
    }

    let tableHtml = `
        <table id="cryptoMethodsTable" class="payment-table table table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>Kryptowährung</th>
                    <th>Wallet-Adresse</th>
                    <th>Status</th>
                    <th>Hinzugefügt</th>
                    <th style="width: 220px;">Aktionen</th>
                </tr>
            </thead>
            <tbody>
    `;

    methods.forEach(method => {
        const isDefault = method.is_default == 1;
        const statusClass = getStatusClass(method.verification_status);
        const statusText = getStatusTextDE(method.verification_status);
        const needsVerification = method.verification_status !== 'verified';
        
        tableHtml += `
            <tr>
                <td>
                    <i class="fab fa-${getCryptoIcon(method.cryptocurrency)} method-icon"></i>
                    <strong>${escapeHtml(method.cryptocurrency)}</strong>
                    ${isDefault ? '<br><span class="default-badge">Standard</span>' : ''}
                </td>
                <td>
                    <code style="font-size: 12px;">${maskWalletAddress(method.wallet_address)}</code>
                    <br>
                    <span class="text-muted" style="font-size: 11px;">Netzwerk: ${escapeHtml(method.network)}</span>
                </td>
                <td>
                    <span class="status-badge ${statusClass}">${statusText}</span>
                </td>
                <td data-order="${method.created_at}">
                    <span class="date-text">${formatDate(method.created_at)}</span>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-info" onclick='viewMethodDetails(${JSON.stringify(method)}, "crypto")' title="Details anzeigen">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${needsVerification ? `
                        <button class="btn btn-sm btn-warning" onclick="verifyWallet(${method.id})" title="Verifizieren">
                            <i class="fas fa-shield-alt"></i>
                        </button>
                        ` : ''}
                        <button class="btn btn-sm btn-primary" onclick="editMethod(${method.id}, 'crypto')" title="Bearbeiten">
                            <i class="fas fa-edit"></i>
                        </button>
                        ${!isDefault ? `
                        <button class="btn btn-sm btn-success" onclick="setDefaultMethod(${method.id})" title="Als Standard setzen">
                            <i class="fas fa-star"></i>
                        </button>
                        ` : ''}
                        <button class="btn btn-sm btn-danger" onclick="deleteMethod(${method.id})" title="Löschen">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tableHtml += `
            </tbody>
        </table>
    `;

    container.html(tableHtml);
    
    // Initialize DataTable with German localization
    $('#cryptoMethodsTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.3/i18n/de_de.json"
        },
        "order": [[3, "desc"]], // Sort by date, newest first
        "pageLength": 10,
        "responsive": true,
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        "columnDefs": [
            { "orderable": false, "targets": 4 } // Disable sorting on actions column
        ]
    });
}

// Hilfsfunktionen
function getStatusClass(status) {
    const statusMap = {
        'verified': 'verified',
        'pending': 'pending',
        'failed': 'failed',
        'not_verified': 'not-verified'
    };
    return statusMap[status] || 'not-verified';
}

function getStatusTextDE(status) {
    const textMap = {
        'verified': 'Verifiziert',
        'pending': 'Ausstehend',
        'failed': 'Fehlgeschlagen',
        'not_verified': 'Nicht verifiziert'
    };
    return textMap[status] || 'Unbekannt';
}

function getCryptoIcon(crypto) {
    const iconMap = {
        'Bitcoin': 'bitcoin',
        'Ethereum': 'ethereum',
        'Litecoin': 'litecoin',
        'Ripple': 'ripple',
        'Bitcoin Cash': 'bitcoin',
        'Tether': 'dollar-sign',
        'USDC': 'dollar-sign'
    };
    return iconMap[crypto] || 'coins';
}

function maskIBAN(iban) {
    if (!iban) return '';
    const cleaned = iban.replace(/\s/g, '');
    if (cleaned.length > 8) {
        return cleaned.substring(0, 4) + ' **** **** ' + cleaned.substring(cleaned.length - 4);
    }
    return iban;
}

function maskWalletAddress(address) {
    if (!address) return '';
    if (address.length > 16) {
        return address.substring(0, 8) + '...' + address.substring(address.length - 8);
    }
    return address;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('de-DE');
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Modal-Funktionen
function showAddFiatModal() {
    $('#addFiatModal').modal('show');
}

function showAddCryptoModal() {
    $('#addCryptoModal').modal('show');
}

// Formular-Handler
$('#addFiatForm').submit(function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'ajax/add_payment_method.php',
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#addFiatModal').modal('hide');
                showSuccess('Bankkonto erfolgreich hinzugefügt');
                loadPaymentMethods();
                $('#addFiatForm')[0].reset();
            } else {
                showError(response.message || 'Fehler beim Hinzufügen des Bankkontos');
            }
        },
        error: function() {
            showError('Serverfehler beim Hinzufügen des Bankkontos');
        }
    });
});

// Sync hidden payment_method field whenever cryptocurrency selection changes
$('#addCryptoForm [name="cryptocurrency"]').on('change', function() {
    $('#cryptoPaymentMethodHidden').val($(this).val());
});

$('#addCryptoForm').submit(function(e) {
    e.preventDefault();

    const cryptocurrency = $(this).find('[name="cryptocurrency"]').val();
    if (!cryptocurrency) {
        showError('Bitte wählen Sie eine Kryptowährung');
        return;
    }
    // Keep hidden payment_method in sync before serializing (handles edge cases where change event did not fire)
    $('#cryptoPaymentMethodHidden').val(cryptocurrency);

    $.ajax({
        url: 'ajax/add_payment_method.php',
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#addCryptoModal').modal('hide');
                showSuccess('Krypto-Wallet erfolgreich hinzugefügt. Bitte verifizieren Sie es über den Satoshi-Test.');
                loadPaymentMethods();
                $('#addCryptoForm')[0].reset();
            } else {
                showError(response.message || 'Fehler beim Hinzufügen des Krypto-Wallets');
            }
        },
        error: function() {
            showError('Serverfehler beim Hinzufügen des Krypto-Wallets');
        }
    });
});

// Aktions-Funktionen
function deleteMethod(id) {
    if (!confirm('Möchten Sie diese Zahlungsmethode wirklich löschen?')) {
        return;
    }
    
    $.ajax({
        url: 'ajax/delete_payment_method.php',
        method: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess('Zahlungsmethode erfolgreich gelöscht');
                loadPaymentMethods();
            } else {
                showError(response.message || 'Fehler beim Löschen');
            }
        },
        error: function() {
            showError('Serverfehler beim Löschen');
        }
    });
}

function setDefaultMethod(id) {
    $.ajax({
        url: 'ajax/set_default_payment_method.php',
        method: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess('Standard-Zahlungsmethode aktualisiert');
                loadPaymentMethods();
            } else {
                showError(response.message || 'Fehler beim Setzen der Standard-Methode');
            }
        },
        error: function() {
            showError('Serverfehler');
        }
    });
}

function verifyWallet(id) {
    // Load wallet details for verification
    $.ajax({
        url: 'ajax/get_payment_methods.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const methods = response.methods ? 
                    [...(response.methods.crypto || []), ...(response.crypto || [])] :
                    (response.crypto || []);
                const wallet = methods.find(m => m.id == id);
                
                if (wallet) {
                    showVerifyWalletModal(wallet);
                } else {
                    showError('Wallet nicht gefunden');
                }
            }
        }
    });
}

function showVerifyWalletModal(wallet) {
    $('#verify_wallet_id').val(wallet.id);
    
    // Check if admin has set verification details
    const hasVerificationDetails = wallet.verification_amount && wallet.verification_address;
    
    let detailsHtml = `
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="text-muted mb-1"><small>Ihr Wallet</small></label>
                <div class="form-control-plaintext border rounded p-2 bg-light">
                    <i class="fab fa-${getCryptoIcon(wallet.cryptocurrency)}"></i>
                    <strong>${escapeHtml(wallet.cryptocurrency)}</strong>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="text-muted mb-1"><small>Netzwerk</small></label>
                <div class="form-control-plaintext border rounded p-2 bg-light">
                    ${escapeHtml(wallet.network)}
                </div>
            </div>
            <div class="col-md-12 mb-3">
                <label class="text-muted mb-1"><small>Ihre Wallet-Adresse</small></label>
                <div class="form-control-plaintext border rounded p-2 bg-light">
                    <code style="font-size: 11px; word-break: break-all;">${escapeHtml(wallet.wallet_address)}</code>
                </div>
            </div>
            <div class="col-md-12 mb-3">
                <label class="text-muted mb-1"><small>Verifizierungsstatus</small></label>
                <div class="form-control-plaintext border rounded p-2 bg-light">
                    <span class="status-badge ${getStatusClass(wallet.verification_status)}">
                        ${getStatusTextDE(wallet.verification_status)}
                    </span>
                </div>
            </div>
    `;
    
    if (!hasVerificationDetails) {
        // Admin hasn't set verification details yet
        detailsHtml += `
            <div class="col-md-12 mt-3">
                <div class="alert alert-warning">
                    <h6 class="alert-heading"><i class="fas fa-clock"></i> Warten auf Administrator-Einrichtung</h6>
                    <p class="mb-2">Die Verifizierungsdetails für dieses Wallet wurden noch nicht vom Administrator eingerichtet.</p>
                    <p class="mb-0">
                        <strong>Was bedeutet das?</strong><br>
                        Unser Administrator muss zunächst die Verifizierungs-Adresse und den Test-Betrag für Ihr ${escapeHtml(wallet.cryptocurrency)}-Wallet konfigurieren. 
                        Dies ist ein einmaliger Prozess zur Sicherstellung der korrekten Netzwerk-Konfiguration.
                    </p>
                </div>
                <div class="alert alert-info">
                    <p class="mb-0">
                        <i class="fas fa-info-circle"></i> <strong>Nächste Schritte:</strong><br>
                        Sie werden per E-Mail benachrichtigt, sobald die Verifizierungsdetails bereitstehen. 
                        Danach können Sie den Verifizierungsprozess abschließen und Ihr Wallet für Ein- und Auszahlungen freischalten.
                    </p>
                </div>
            </div>
        `;
        
        // Hide the transaction form when details are not ready
        $('#verifyWalletForm').find('.row.mt-4').hide();
        $('#verifyWalletForm').find('button[type="submit"]').hide();
    } else {
        // Admin has set verification details - show them
        const verificationAmount = wallet.verification_amount;
        const verificationAddress = wallet.verification_address;
        
        detailsHtml += `
            <div class="col-md-12 mt-3">
                <div class="alert alert-success">
                    <h6 class="alert-heading"><i class="fas fa-check-circle"></i> Verifizierung bereit</h6>
                    <p class="mb-0">Die Verifizierungsdetails sind eingerichtet. Sie können jetzt den Test-Betrag senden.</p>
                </div>
            </div>
            <div class="col-md-12 mt-3">
                <div class="alert alert-warning">
                    <h6 class="alert-heading"><i class="fas fa-arrow-right"></i> Senden Sie den Test-Betrag</h6>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong><i class="fas fa-coins"></i> Exakter Betrag:</strong><br>
                            <code class="text-dark" style="font-size: 14px;">${verificationAmount} ${escapeHtml(wallet.cryptocurrency)}</code>
                            <button class="btn btn-sm btn-outline-primary ml-2" onclick="copyToClipboard('${verificationAmount}')" title="Betrag kopieren">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong><i class="fas fa-wallet"></i> An Verifizierungs-Adresse:</strong><br>
                            <code class="text-dark" style="font-size: 10px; word-break: break-all;">${escapeHtml(verificationAddress)}</code>
                            <button class="btn btn-sm btn-outline-primary ml-2" onclick="copyToClipboard('${verificationAddress}')" title="Adresse kopieren">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Wichtig:</strong> Senden Sie genau den angegebenen Betrag. Abweichungen können die Verifizierung verzögern.
                        </small>
                    </div>
                </div>
            </div>
        `;
        
        // Show the transaction form when details are ready
        $('#verifyWalletForm').find('.row.mt-4').show();
        $('#verifyWalletForm').find('button[type="submit"]').show();
    }
    
    detailsHtml += `</div>`;
    
    $('#verifyWalletDetails').html(detailsHtml);
    $('#verifyWalletModal').modal('show');
}

// Handle verify wallet form submission
$('#verifyWalletForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        wallet_id: $('#verify_wallet_id').val(),
        transaction_id: $('#verify_transaction_id').val(),
        notes: $('#verify_notes').val()
    };
    
    $.ajax({
        url: 'ajax/submit_wallet_verification.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#verifyWalletModal').modal('hide');
                showSuccess('Verifizierung eingereicht. Wir werden Ihre Transaktion prüfen.');
                loadPaymentMethods();
                // Clear form
                $('#verifyWalletForm')[0].reset();
            } else {
                showError(response.message || 'Fehler beim Einreichen der Verifizierung');
            }
        },
        error: function() {
            showError('Serverfehler beim Einreichen der Verifizierung');
        }
    });
});

function editMethod(id, type) {
    // Load method details and show edit modal
    $.ajax({
        url: 'ajax/get_payment_methods.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let methods = [];
                if (type === 'fiat') {
                    methods = response.methods ? response.methods.fiat || [] : response.fiat || [];
                } else {
                    methods = response.methods ? response.methods.crypto || [] : response.crypto || [];
                }
                
                const method = methods.find(m => m.id == id);
                
                if (method) {
                    showEditModal(method, type);
                } else {
                    showError('Zahlungsmethode nicht gefunden');
                }
            }
        }
    });
}

function showEditModal(method, type) {
    if (type === 'fiat') {
        // Populate fiat edit form
        $('#edit_fiat_id').val(method.id);
        $('#edit_payment_method').val(method.payment_method);
        $('#edit_bank_name').val(method.bank_name);
        $('#edit_account_holder').val(method.account_holder);
        $('#edit_iban').val(method.iban);
        $('#edit_bic').val(method.bic);
        $('#edit_country').val(method.country);
        $('#edit_label').val(method.label);
        
        $('#editFiatModal').modal('show');
    } else {
        // Populate crypto edit form
        $('#edit_crypto_id').val(method.id);
        $('#edit_cryptocurrency').val(method.cryptocurrency);
        $('#edit_network').val(method.network);
        $('#edit_wallet_address').val(method.wallet_address);
        $('#edit_crypto_label').val(method.label);
        
        $('#editCryptoModal').modal('show');
    }
}

// Handle edit fiat form submission
$('#editFiatForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'ajax/update_payment_method.php',
        method: 'POST',
        data: $(this).serialize() + '&type=fiat',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#editFiatModal').modal('hide');
                showSuccess('Bankkonto erfolgreich aktualisiert');
                loadPaymentMethods();
            } else {
                showError(response.message || 'Fehler beim Aktualisieren');
            }
        },
        error: function() {
            showError('Serverfehler beim Aktualisieren');
        }
    });
});

// Handle edit crypto form submission
$('#editCryptoForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'ajax/update_payment_method.php',
        method: 'POST',
        data: $(this).serialize() + '&type=crypto',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#editCryptoModal').modal('hide');
                showSuccess('Krypto-Wallet erfolgreich aktualisiert');
                loadPaymentMethods();
            } else {
                showError(response.message || 'Fehler beim Aktualisieren');
            }
        },
        error: function() {
            showError('Serverfehler beim Aktualisieren');
        }
    });
});

// View method details in modal
function viewMethodDetails(method, type) {
    const modalTitle = type === 'fiat' ? 'Bankkonto Details' : 'Krypto-Wallet Details';
    $('#viewModalTitle').html('<i class="fas fa-' + (type === 'fiat' ? 'university' : 'bitcoin') + '"></i> ' + modalTitle);
    
    let detailsHtml = '';
    
    if (type === 'fiat') {
        detailsHtml = `
            <div class="row">
                <div class="col-md-12">
                    <h6 class="mb-3" style="color: #4e73df; font-weight: 600;">
                        <i class="fas fa-info-circle"></i> Allgemeine Informationen
                    </h6>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="text-muted mb-1"><small>Zahlungsmethode</small></label>
                    <div class="form-control-plaintext border rounded p-2 bg-light">
                        ${escapeHtml(method.payment_method || 'N/A')}
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="text-muted mb-1"><small>Bankname</small></label>
                    <div class="form-control-plaintext border rounded p-2 bg-light">
                        ${escapeHtml(method.bank_name || 'N/A')}
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="text-muted mb-1"><small>Kontoinhaber</small></label>
                    <div class="form-control-plaintext border rounded p-2 bg-light">
                        ${escapeHtml(method.account_holder || 'N/A')}
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="text-muted mb-1"><small>IBAN</small></label>
                    <div class="form-control-plaintext border rounded p-2 bg-light">
                        <code style="font-size: 13px;">${escapeHtml(method.iban || 'N/A')}</code>
                        <button class="btn btn-sm btn-outline-primary ml-2" onclick="copyToClipboard('${escapeHtml(method.iban)}')" title="Kopieren">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="text-muted mb-1"><small>BIC/SWIFT</small></label>
                    <div class="form-control-plaintext border rounded p-2 bg-light">
                        ${escapeHtml(method.bic || 'N/A')}
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="text-muted mb-1"><small>Land</small></label>
                    <div class="form-control-plaintext border rounded p-2 bg-light">
                        ${escapeHtml(method.country || 'N/A')}
                    </div>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="text-muted mb-1"><small>Beschreibung</small></label>
                    <div class="form-control-plaintext border rounded p-2 bg-light">
                        ${escapeHtml(method.label || 'Keine Beschreibung')}
                    </div>
                </div>
                <div class="col-md-12 mt-3">
                    <h6 class="mb-3" style="color: #4e73df; font-weight: 600;">
                        <i class="fas fa-check-circle"></i> Status & Daten
                    </h6>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted mb-1"><small>Verifizierungsstatus</small></label>
                    <div class="form-control-plaintext border rounded p-2 bg-light">
                        <span class="status-badge ${getStatusClass(method.verification_status)}">
                            ${getStatusTextDE(method.verification_status)}
                        </span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted mb-1"><small>Standard-Methode</small></label>
                    <div class="form-control-plaintext border rounded p-2 bg-light">
                        ${method.is_default == 1 ? '<span class="badge badge-primary">Ja</span>' : '<span class="badge badge-secondary">Nein</span>'}
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted mb-1"><small>Hinzugefügt am</small></label>
                    <div class="form-control-plaintext border rounded p-2 bg-light">
                        ${formatDate(method.created_at)}
                    </div>
                </div>
            </div>
        `;
    } else {
        // Crypto method
        detailsHtml = `
            <div class="row">
                <div class="col-md-12">
                    <h6 class="mb-3" style="color: #4e73df; font-weight: 600;">
                        <i class="fas fa-info-circle"></i> Wallet Informationen
                    </h6>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="text-muted mb-1"><small>Kryptowährung</small></label>
                    <div class="form-control-plaintext border rounded p-2 bg-light">
                        <i class="fab fa-${getCryptoIcon(method.cryptocurrency)}"></i>
                        ${escapeHtml(method.cryptocurrency || 'N/A')}
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="text-muted mb-1"><small>Netzwerk</small></label>
                    <div class="form-control-plaintext border rounded p-2 bg-light">
                        ${escapeHtml(method.network || 'N/A')}
                    </div>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="text-muted mb-1"><small>Wallet-Adresse</small></label>
                    <div class="form-control-plaintext border rounded p-2 bg-light">
                        <code style="font-size: 12px; word-break: break-all;">${escapeHtml(method.wallet_address || 'N/A')}</code>
                        <button class="btn btn-sm btn-outline-primary ml-2" onclick="copyToClipboard('${escapeHtml(method.wallet_address)}')" title="Kopieren">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="text-muted mb-1"><small>Beschreibung</small></label>
                    <div class="form-control-plaintext border rounded p-2 bg-light">
                        ${escapeHtml(method.label || 'Keine Beschreibung')}
                    </div>
                </div>
                <div class="col-md-12 mt-3">
                    <h6 class="mb-3" style="color: #4e73df; font-weight: 600;">
                        <i class="fas fa-shield-alt"></i> Verifizierung & Status
                    </h6>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted mb-1"><small>Verifizierungsstatus</small></label>
                    <div class="form-control-plaintext border rounded p-2 bg-light">
                        <span class="status-badge ${getStatusClass(method.verification_status)}">
                            ${getStatusTextDE(method.verification_status)}
                        </span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted mb-1"><small>Standard-Methode</small></label>
                    <div class="form-control-plaintext border rounded p-2 bg-light">
                        ${method.is_default == 1 ? '<span class="badge badge-primary">Ja</span>' : '<span class="badge badge-secondary">Nein</span>'}
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="text-muted mb-1"><small>Hinzugefügt am</small></label>
                    <div class="form-control-plaintext border rounded p-2 bg-light">
                        ${formatDate(method.created_at)}
                    </div>
                </div>
                ${method.verification_status !== 'verified' ? `
                <div class="col-md-12 mt-2">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Verifizierung erforderlich:</strong> Diese Wallet muss durch den Satoshi-Test verifiziert werden, bevor sie für Auszahlungen verwendet werden kann.
                    </div>
                </div>
                ` : ''}
            </div>
        `;
    }
    
    $('#viewDetailsContent').html(detailsHtml);
    $('#viewDetailsModal').modal('show');
}

// Copy to clipboard function
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            showSuccess('In Zwischenablage kopiert!');
        }, function() {
            fallbackCopyToClipboard(text);
        });
    } else {
        fallbackCopyToClipboard(text);
    }
}

function fallbackCopyToClipboard(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.top = "-9999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand('copy');
        showSuccess('In Zwischenablage kopiert!');
    } catch (err) {
        showError('Kopieren fehlgeschlagen');
    }
    document.body.removeChild(textArea);
}

// Benachrichtigungen
function showSuccess(message) {
    // Implementieren Sie hier Ihre Toast/Notification-Logik
    alert(message);
}

function showError(message) {
    // Implementieren Sie hier Ihre Toast/Notification-Logik
    alert(message);
}

// Beim Laden der Seite
$(document).ready(function() {
    loadPaymentMethods();
});
</script>

