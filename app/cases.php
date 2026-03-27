<?php include 'header.php'; ?>
<?php
// ── Package / Subscription Status (for blur feature) ──────────────────────
$casesSubscriptionEnabled  = false;
$casesNeedsBlur            = false;  // true when user should see blurred rows
$casesIsExpired            = false;
$casesUserPackage          = null;
$CASES_BLUR_LIMIT          = 2;      // show this many rows freely; blur the rest
try {
    $subRow = $pdo->query("SELECT subscription_enabled FROM system_settings WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $casesSubscriptionEnabled = !empty($subRow['subscription_enabled']);
} catch (PDOException $e) { /* column not yet migrated */ }

if ($casesSubscriptionEnabled && !empty($_SESSION['user_id'])) {
    try {
        $pkgStmt = $pdo->prepare(
            "SELECT up.id, up.status, up.end_date, p.name AS package_name, p.price
               FROM user_packages up
               JOIN packages p ON p.id = up.package_id
              WHERE up.user_id = ?
              ORDER BY up.created_at DESC LIMIT 1"
        );
        $pkgStmt->execute([$_SESSION['user_id']]);
        $casesUserPackage = $pkgStmt->fetch(PDO::FETCH_ASSOC);

        // Auto-expire in-memory if DB not yet updated
        if ($casesUserPackage
            && $casesUserPackage['status'] === 'active'
            && !empty($casesUserPackage['end_date'])
            && strtotime($casesUserPackage['end_date']) < time()
        ) {
            $casesUserPackage['status'] = 'expired';
        }

        $casesIsExpired = $casesUserPackage && in_array($casesUserPackage['status'], ['expired', 'cancelled']);
        $hasActivePaidCases = $casesUserPackage
            && $casesUserPackage['status'] === 'active'
            && (float)$casesUserPackage['price'] > 0.0;

        $casesNeedsBlur = !$hasActivePaidCases;
    } catch (PDOException $e) { /* user_packages not yet available */ }
}
?>

<!-- Content Wrapper START -->
<div class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #2950a8 0%, #2da9e3 100%); color: #fff;">
                    <div class="card-body py-4">
                        <h2 class="mb-2 text-white" style="font-weight: 700;">
                            <i class="anticon anticon-folder-open mr-2"></i>Meine Fälle
                        </h2>
                        <p class="mb-0" style="color: rgba(255,255,255,0.9); font-size: 15px;">
                            Alle gemeldeten Fälle anzeigen und verwalten
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cases Table Card -->
        <?php if ($casesNeedsBlur): ?>
        <!-- Subscription restriction banner -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert border-0 d-flex align-items-start" role="alert"
                     style="background:linear-gradient(135deg,rgba(255,193,7,.15),rgba(255,152,0,.08));border-left:4px solid #ffc107 !important;border-radius:10px;gap:14px;">
                    <div style="font-size:24px;flex-shrink:0;">🔒</div>
                    <div class="flex-grow-1">
                        <strong style="color:#856404;">
                            <?php if ($casesIsExpired): ?>Paket abgelaufen – Upgrade erforderlich
                            <?php elseif ($casesUserPackage): ?>Test-Zugang aktiv – Vollzugang upgraden
                            <?php else: ?>Kein aktives Paket – Fälle gesperrt
                            <?php endif; ?>
                        </strong>
                        <p class="mb-0 mt-1" style="font-size:.88rem;color:#856404;">
                            Ihr vollständiger Fallverlauf ist mit unserem leistungsstarken Algorithmus verfügbar.
                            <?php if ($casesIsExpired): ?>Ihr Paket ist abgelaufen – erneuern Sie es, um wieder vollen Zugang zu erhalten.
                            <?php else: ?>Abonnieren Sie ein bezahltes Paket, um alle Fälle zu sehen und Ihre Gelder zurückzufordern.
                            <?php endif; ?>
                        </p>
                    </div>
                    <a href="packages.php" class="btn btn-warning btn-sm font-weight-600 flex-shrink-0" style="border-radius:8px;white-space:nowrap;">
                        <i class="anticon anticon-rise mr-1"></i>Jetzt upgraden
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0" style="color: #2c3e50; font-weight: 600;">
                                <i class="anticon anticon-file-text mr-2" style="color: var(--brand);"></i>Meine gemeldeten Fälle
                            </h5>
                            <button class="btn btn-outline-primary btn-sm" id="refreshCases" title="Tabelle aktualisieren">
                                <i class="anticon anticon-reload mr-1"></i>Aktualisieren
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table id="casesTable" class="table table-hover mb-0" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Fall-ID</th>
                                        <th>Plattform</th>
                                        <th>Gemeldeter Betrag</th>
                                        <th>Wiederbeschaffter Betrag</th>
                                        <th>Status</th>
                                        <th>Schwierigkeit</th>
                                        <th>Erstellt am</th>
                                        <th>Zuletzt aktualisiert</th>
                                        <th class="text-center">Aktionen</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Content Wrapper END -->

<!-- Case Details Modal -->
<div class="modal fade" id="caseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #2950a8 0%, #2da9e3 100%); color: #fff; border-radius: 12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold">
                    <i class="anticon anticon-file-text mr-2"></i>Fall #<span id="caseNumber"></span> &#x2013; Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Schließen">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4" id="caseModalContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Wird geladen …</span>
                    </div>
                    <p class="mt-3 text-muted">Falldetails werden geladen …</p>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 12px 12px;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="anticon anticon-close mr-1"></i>Schließen
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Timeline Styles for Cases Modal */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e0e0e0;
}

.timeline-item-active .timeline-marker {
    width: 14px;
    height: 14px;
    box-shadow: 0 0 0 3px rgba(41, 80, 168, 0.2);
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -24px;
    top: 17px;
    bottom: -5px;
    width: 2px;
    background: #e0e0e0;
}

.timeline-content {
    background: rgba(41, 80, 168, 0.03);
    padding: 12px;
    border-radius: 8px;
    border-left: 3px solid rgba(41, 80, 168, 0.2);
}

.timeline-item-active .timeline-content {
    background: rgba(41, 80, 168, 0.08);
    border-left-color: #2950a8;
}

/* Blurred rows for restricted package users */
.cases-row-blurred td {
    -webkit-filter: blur(5px);
    filter: blur(5px);
    pointer-events: none;
    user-select: none;
    opacity: 0.7;
}
</style>



<!-- Document Upload Modal -->
<div class="modal fade" id="documentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="anticon anticon-upload me-2"></i>
                    Dokumente für Fall #<span id="documentCaseNumber"></span> hochladen
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form id="documentUploadForm" enctype="multipart/form-data">
                <input type="hidden" id="documentCaseId" name="case_id">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="anticon anticon-info-circle"></i>
                        Bitte laden Sie alle erforderlichen Dokumente hoch, um Ihren Fall weiterzubearbeiten.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Dokumenttyp</label>
                        <select class="form-control" name="document_type" required>
                            <option value="">Dokumenttyp auswählen</option>
                            <option value="proof_of_payment">Zahlungsnachweis</option>
                            <option value="identity_verification">Identitätsnachweis</option>
                            <option value="communication_records">Kommunikationsnachweise</option>
                            <option value="bank_statement">Kontoauszug</option>
                            <option value="other">Sonstiges</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Dokument-Datei</label>
                        <input type="file" class="form-control" id="documentFile" name="document_file" required>
                        <small class="form-text text-muted">Max. Dateigröße: 5 MB (PDF, JPG, PNG)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Anmerkungen (optional)</label>
                        <textarea class="form-control" name="document_notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="anticon anticon-upload me-1"></i> Dokument hochladen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<!-- DataTables already loaded in footer.php -->

<script>
$(document).ready(function() {
    // ── Package blur settings (from PHP) ──────────────────────────────────────
    const CASES_NEEDS_BLUR = <?php echo json_encode($casesNeedsBlur); ?>;
    const CASES_BLUR_LIMIT = <?php echo (int)$CASES_BLUR_LIMIT; ?>;

    // Zentrale Statuszuordnung (einmalig definiert, überall verwendet)
    const STATUS_MAP = {
        open:               { cls: 'secondary', label: 'Offen' },
        documents_required: { cls: 'warning',   label: 'Dokumente erforderlich' },
        under_review:       { cls: 'info',       label: 'In Prüfung' },
        refund_approved:    { cls: 'success',    label: 'Rückerstattung genehmigt' },
        refund_rejected:    { cls: 'danger',     label: 'Rückerstattung abgelehnt' },
        closed:             { cls: 'dark',       label: 'Geschlossen' }
    };

    function getStatus(key) {
        return STATUS_MAP[key] || { cls: 'secondary', label: (key || '').replace(/_/g, ' ') };
    }

    const casesTable = $('#casesTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 10,
        order: [[6, 'desc']],
        ajax: {
            url: 'ajax/cases.php',
            type: 'POST'
        },
        columns: [
            { data: 'case_number' },
            { data: 'platform_name', render: d => d || 'N/A' },
            { data: 'reported_amount', render: d => '€' + parseFloat(d).toFixed(2) },
            { 
                data: null, 
                render: function(data, type, row) {
                    const recovered = parseFloat(row.recovered_amount || 0);
                    const reported = parseFloat(row.reported_amount || 0);
                    const progress = reported > 0 ? ((recovered / reported) * 100).toFixed(1) : 0;
                    
                    return `
                        <div style="min-width:180px;">
                            <div>
                                <strong style="font-size:14px;color:#2c3e50;">€${recovered.toFixed(2)}</strong>
                            </div>
                            <div class="mt-1">
                                <div class="progress" style="height:6px;border-radius:3px;">
                                    <div class="progress-bar" 
                                         style="width:${progress}%;background:linear-gradient(90deg,#2950a8,#2da9e3);"
                                         role="progressbar" 
                                         aria-valuenow="${progress}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-1">
                                <small class="text-muted" style="font-size:11px;">${progress}% von €${reported.toFixed(2)}</small>
                            </div>
                        </div>
                    `;
                }
            },
            {
                data: 'status',
                render: function(data) {
                    const s = getStatus(data);
                    return `<span class="badge bg-${s.cls}">${s.label}</span>`;
                }
            },
            {
                data: 'refund_difficulty',
                render: function(data) {
                    const cfg = {
                        easy:   { cls: 'success', label: 'Einfach' },
                        medium: { cls: 'warning', label: 'Mittel' },
                        hard:   { cls: 'danger',  label: 'Schwierig' }
                    };
                    const d = cfg[data] || cfg['medium'];
                    return `<span class="badge bg-${d.cls}">${d.label}</span>`;
                }
            },
            { data: 'created_at', render: d => new Date(d).toLocaleDateString('de-DE') },
            { data: 'updated_at', render: d => new Date(d).toLocaleDateString('de-DE') },
            {
                data: null,
                render: function(data, type, row) {
                    let buttons = `
                        <button class="btn btn-sm btn-info view-case" data-id="${row.id}" title="Details anzeigen">
                            <i class="anticon anticon-eye"></i>
                        </button>`;
                    if (row.status === 'documents_required') {
                        buttons += `
                            <button class="btn btn-sm btn-warning upload-docs" 
                                    data-id="${row.id}" data-case-number="${row.case_number}" 
                                    title="Dokumente hochladen">
                                <i class="anticon anticon-upload"></i>
                            </button>`;
                    }
                    return `<div class="btn-group">${buttons}</div>`;
                }
            }
        ],
        // After every draw: apply CSS blur to rows beyond the free limit (safe — never modifies DOM structure)
        drawCallback: function() {
            if (!CASES_NEEDS_BLUR) return;

            const $tbody = $('#casesTable tbody');
            $tbody.find('tr').each(function(idx) {
                if (idx >= CASES_BLUR_LIMIT) {
                    $(this).addClass('cases-row-blurred');
                }
            });

            // Show upgrade CTA once below the table (remove stale one first on redraw)
            $('#casesBlurCta').remove();
            if ($tbody.find('tr.cases-row-blurred').length > 0) {
                $('#casesTable').closest('.table-responsive').after(
                    '<div id="casesBlurCta" class="text-center p-3 mt-2" style="background:linear-gradient(135deg,rgba(41,80,168,.07),rgba(45,169,227,.04));border-radius:10px;">' +
                    '<i class="anticon anticon-lock mr-2 text-warning" style="font-size:18px;"></i>' +
                    '<span style="color:#2c3e50;font-size:.9rem;">Weitere Fälle sind verfügbar – </span>' +
                    '<a href="packages.php" class="btn btn-sm btn-warning ml-2" style="border-radius:8px;font-weight:700;">' +
                    '<i class="anticon anticon-rise mr-1"></i>Jetzt upgraden</a>' +
                    '</div>'
                );
            }
        }
    });

    // View Case Details
    $('#casesTable').on('click', '.view-case', function() {
        const caseId = $(this).data('id');
        loadCaseDetails(caseId);
    });

    // Refresh button
    $('#refreshCases').on('click', function() {
        casesTable.ajax.reload();
    });
        $('#documentCaseId').val($(this).data('id'));
        $('#documentCaseNumber').text($(this).data('case-number'));
        new bootstrap.Modal(document.getElementById('documentModal')).show();
    });

    // File input label update
    $('#documentFile').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        $(this).next('label').html(fileName || 'Datei auswählen');
    });

    // Document upload form
    $('#documentUploadForm').submit(function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        $.ajax({
            url: 'user_ajax/upload_document.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#documentUploadForm button[type="submit"]').prop('disabled', true)
                    .html('<i class="anticon anticon-loading anticon-spin"></i> Wird hochgeladen …');
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    bootstrap.Modal.getInstance(document.getElementById('documentModal')).hide();
                    $('#documentUploadForm')[0].reset();
                    $('#documentFile').next('label').html('Datei auswählen');

                    if ($('#caseModal').is(':visible')) {
                        loadCaseDetails($('#documentCaseId').val());
                    }
                    casesTable.ajax.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('Fehler beim Hochladen des Dokuments.');
            },
            complete: function() {
                $('#documentUploadForm button[type="submit"]').prop('disabled', false)
                    .html('<i class="anticon anticon-upload me-1"></i> Dokument hochladen');
            }
        });
    });

    function loadCaseDetails(caseId) {
        // Show modal with loading state
        $('#caseModal').modal('show');
        $('#caseModalContent').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Wird geladen …</span>
                </div>
                <p class="mt-3 text-muted">Falldetails werden geladen …</p>
            </div>
        `);
        
        // Fetch case details via AJAX
        $.ajax({
            url: 'ajax/get-case.php',
            method: 'GET',
            data: { id: caseId },
            success: function(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success && data.case) {
                        const c = data.case;
                        const progress = c.reported_amount > 0 ? Math.round((c.recovered_amount / c.reported_amount) * 100) : 0;
                        
                        const s = getStatus(c.status);
                        
                        const html = `
                            <div class="case-details-content">
                                <!-- Kopfzeile -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card border-0" style="background: rgba(41, 80, 168, 0.05);">
                                            <div class="card-body">
                                                <h6 class="text-muted mb-2" style="font-size: 12px; text-transform: uppercase;">Fallnummer</h6>
                                                <h4 class="mb-0 font-weight-bold" style="color: #2950a8;">${c.case_number || 'N/A'}</h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-0" style="background: rgba(41, 80, 168, 0.05);">
                                            <div class="card-body">
                                                <h6 class="text-muted mb-2" style="font-size: 12px; text-transform: uppercase;">Status</h6>
                                                <span class="badge badge-${s.cls} px-3 py-2" style="font-size: 14px;">
                                                    <i class="anticon anticon-flag mr-1"></i>${s.label}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Finanzübersicht -->
                                <div class="card border-0 mb-4" style="background: linear-gradient(135deg, rgba(41, 80, 168, 0.05), rgba(45, 169, 227, 0.05));">
                                    <div class="card-body">
                                        <h5 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-euro mr-2" style="color: #2950a8;"></i>Finanzübersicht
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="text-muted mb-1" style="font-size: 13px;">Gemeldeter Betrag</div>
                                                <h4 class="mb-0 font-weight-bold text-danger">€${parseFloat(c.reported_amount || 0).toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</h4>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="text-muted mb-1" style="font-size: 13px;">Wiederbeschaffter Betrag</div>
                                                <h3 class="mb-2 font-weight-bold" style="color: #2c3e50;">€${parseFloat(c.recovered_amount || 0).toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</h3>
                                                <div class="progress mb-2" style="height: 8px; border-radius: 10px; background: #e9ecef;">
                                                    <div class="progress-bar" style="width: ${progress}%; background: linear-gradient(90deg, #2950a8 0%, #2da9e3 100%);"></div>
                                                </div>
                                                <small class="text-muted">${progress}% von €${parseFloat(c.reported_amount || 0).toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Plattform & Zeitplan -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card border-0 h-100">
                                            <div class="card-body">
                                                <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                                    <i class="anticon anticon-global mr-2" style="color: #2950a8;"></i>Plattform-Informationen
                                                </h6>
                                                <p class="mb-2"><strong>Plattform:</strong> ${c.platform_name || 'N/A'}</p>
                                                <p class="mb-0"><strong>Erstellt am:</strong> ${c.created_at ? new Date(c.created_at).toLocaleDateString('de-DE', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'}) : 'N/A'}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-0 h-100">
                                            <div class="card-body">
                                                <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                                    <i class="anticon anticon-clock-circle mr-2" style="color: #2950a8;"></i>Zeitplan
                                                </h6>
                                                <p class="mb-2"><strong>Zuletzt aktualisiert:</strong> ${c.updated_at ? new Date(c.updated_at).toLocaleDateString('de-DE', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'}) : 'N/A'}</p>
                                                <p class="mb-0"><strong>Aktive Tage:</strong> ${c.created_at ? Math.floor((new Date() - new Date(c.created_at)) / (1000 * 60 * 60 * 24)) : 0} Tage</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Fallbeschreibung -->
                                ${c.description ? `
                                <div class="card border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-file-text mr-2" style="color: #2950a8;"></i>Fallbeschreibung
                                        </h6>
                                        <p class="mb-0" style="line-height: 1.6;">${c.description}</p>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <!-- Rückbuchungstransaktionen -->
                                ${data.recoveries && data.recoveries.length > 0 ? `
                                <div class="card border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-transaction mr-2" style="color: #2950a8;"></i>Rückbuchungstransaktionen
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead style="background: rgba(41, 80, 168, 0.05);">
                                                    <tr>
                                                        <th>Datum</th>
                                                        <th>Betrag</th>
                                                        <th>Methode</th>
                                                        <th>Referenz</th>
                                                        <th>Bearbeitet von</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${data.recoveries.map(r => `
                                                        <tr>
                                                            <td>${r.transaction_date ? new Date(r.transaction_date).toLocaleDateString('de-DE', {year: 'numeric', month: 'short', day: 'numeric'}) : 'N/A'}</td>
                                                            <td><strong class="text-success">€${parseFloat(r.amount || 0).toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>
                                                            <td>${r.method || 'N/A'}</td>
                                                            <td><small class="text-muted">${r.transaction_reference || 'N/A'}</small></td>
                                                            <td>${r.admin_first_name && r.admin_last_name ? `${r.admin_first_name} ${r.admin_last_name}` : 'System'}</td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <!-- Falldokumente -->
                                ${data.documents && data.documents.length > 0 ? `
                                <div class="card border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-paper-clip mr-2" style="color: #2950a8;"></i>Falldokumente
                                        </h6>
                                        <div class="list-group">
                                            ${data.documents.map(d => `
                                                <div class="list-group-item border-0 px-0">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="anticon anticon-file mr-2" style="color: #2950a8;"></i>
                                                            <strong>${d.document_type || 'Dokument'}</strong>
                                                            ${d.verified ? '<span class="badge badge-success badge-sm ml-2"><i class="anticon anticon-check"></i> Verifiziert</span>' : ''}
                                                        </div>
                                                        <small class="text-muted">${d.uploaded_at ? new Date(d.uploaded_at).toLocaleDateString('de-DE', {year: 'numeric', month: 'short', day: 'numeric'}) : ''}</small>
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <!-- Statusverlauf -->
                                ${data.history && data.history.length > 0 ? `
                                <div class="card border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                                            <i class="anticon anticon-history mr-2" style="color: #2950a8;"></i>Statusverlauf
                                        </h6>
                                        <div class="timeline">
                                            ${data.history.map((h, idx) => {
                                                const label = h.new_status ? getStatus(h.new_status).label : 'Statusänderung';
                                                return `
                                                <div class="timeline-item ${idx === 0 ? 'timeline-item-active' : ''}">
                                                    <div class="timeline-marker ${idx === 0 ? 'bg-primary' : 'bg-secondary'}"></div>
                                                    <div class="timeline-content">
                                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                                            <strong>${label}</strong>
                                                            <small class="text-muted">${h.created_at ? new Date(h.created_at).toLocaleDateString('de-DE', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'}) : ''}</small>
                                                        </div>
                                                        ${h.comments ? `<p class="mb-1 text-muted small">${h.comments}</p>` : ''}
                                                        ${h.first_name && h.last_name ? `<small class="text-muted">Von: ${h.first_name} ${h.last_name}</small>` : ''}
                                                    </div>
                                                </div>`;
                                            }).join('')}
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        `;
                        
                        $('#caseModalContent').html(html);
                        $('#caseNumber').text(c.case_number || 'N/A');
                    } else {
                        $('#caseModalContent').html(`
                            <div class="alert alert-danger">
                                <i class="anticon anticon-close-circle mr-2"></i>${data.message || 'Falldetails konnten nicht geladen werden.'}
                            </div>
                        `);
                    }
                } catch (e) {
                    $('#caseModalContent').html(`
                        <div class="alert alert-danger">
                            <i class="anticon anticon-close-circle mr-2"></i>Fehler beim Verarbeiten der Falldaten.
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                $('#caseModalContent').html(`
                    <div class="alert alert-danger">
                        <i class="anticon anticon-close-circle mr-2"></i>Fehler beim Laden der Falldetails: ${error}
                    </div>
                `);
            }
        });
    }

    function getStatusClass(status) {
        return {
            open: 'secondary',
            documents_required: 'warning',
            under_review: 'info',
            refund_approved: 'success',
            refund_rejected: 'danger',
            closed: 'dark'
        }[status] || 'secondary';
    }

    // Refresh button
    $('#refreshCases, .refresh-btn').click(function() {
        casesTable.ajax.reload();
        toastr.success('Fälle wurden aktualisiert.');
    });
});
</script>
