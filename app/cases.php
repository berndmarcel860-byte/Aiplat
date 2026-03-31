<?php include 'header.php'; ?>
<?php
// ── Package check & 100k recovery gate for cases page ────────────────────
$cases_isTrialUser        = true;
$cases_hasActivePaidPkg   = false;
$cases_recoveredTotal     = 0.0;
$cases_recovery100kGate   = false;

if (!empty($_SESSION['user_id'])) {
    try {
        // Package status
        $cpkgStmt = $pdo->prepare(
            "SELECT up.status, p.price
             FROM user_packages up
             JOIN packages p ON up.package_id = p.id
             WHERE up.user_id = ?
             ORDER BY up.end_date DESC LIMIT 1"
        );
        $cpkgStmt->execute([$_SESSION['user_id']]);
        $cpkg = $cpkgStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        $cases_hasActivePaidPkg = $cpkg && $cpkg['status'] === 'active' && (float)$cpkg['price'] > 0;
        $cases_isTrialUser      = !$cases_hasActivePaidPkg;

        // Total recovered
        $crecStmt = $pdo->prepare(
            "SELECT COALESCE(SUM(recovered_amount), 0) FROM cases WHERE user_id = ?"
        );
        $crecStmt->execute([$_SESSION['user_id']]);
        $cases_recoveredTotal   = (float)$crecStmt->fetchColumn();
        $cases_recovery100kGate = $cases_recoveredTotal >= 100000.0;
    } catch (PDOException $e) {
        error_log("cases.php gate check: " . $e->getMessage());
    }
}
?>

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

                        <?php if ($cases_recovery100kGate): ?>
                        <!-- 100k upgrade gate banner -->
                        <div class="alert d-flex align-items-start mb-3" style="background:linear-gradient(135deg,#fff3cd,#ffeeba);border:1.5px solid #ffc107;border-radius:12px;box-shadow:0 2px 10px rgba(255,193,7,.18);">
                            <div style="flex-shrink:0;width:42px;height:42px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;margin-right:14px;">
                                <i class="anticon anticon-lock"></i>
                            </div>
                            <div class="flex-grow-1">
                                <strong style="color:#92400e;font-size:14px;">Upgrade erforderlich – über €100.000 zurückgewonnen</strong>
                                <p class="mb-2 mt-1" style="color:#78350f;font-size:12.5px;line-height:1.5;">
                                    Ihr Konto hat die <strong>€100.000</strong>-Grenze für zurückgewonnene Gelder erreicht.
                                    Mit dem Testzugang sind Auszahlungen und die vollständige Ansicht aller Falldaten eingeschränkt.
                                    Upgraden Sie auf ein kostenpflichtiges Abonnement, um vollen Zugriff zu erhalten.
                                </p>
                                <button type="button" class="btn btn-sm font-weight-700" data-toggle="modal" data-target="#casesTrialUpgradeModal"
                                    style="background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;border:none;border-radius:8px;">
                                    <i class="anticon anticon-info-circle mr-1"></i>Details &amp; Upgrade
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="position-relative">
                        <?php if ($cases_recovery100kGate): ?>
                            <!-- Blur overlay -->
                            <div style="position:absolute;inset:0;backdrop-filter:blur(5px);-webkit-backdrop-filter:blur(5px);background:rgba(255,255,255,0.55);z-index:10;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                                <div class="text-center p-4">
                                    <div style="width:60px;height:60px;background:linear-gradient(135deg,#d97706,#f59e0b);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:28px;color:#fff;margin:0 auto 14px;">
                                        <i class="anticon anticon-lock"></i>
                                    </div>
                                    <h5 style="font-weight:700;color:#92400e;margin-bottom:8px;">Fallansicht eingeschränkt</h5>
                                    <p style="font-size:13px;color:#78350f;margin-bottom:14px;">
                                        Sie haben €100.000 zurückgewonnen.<br>
                                        Upgraden Sie für vollständigen Zugriff auf alle Fälle.
                                    </p>
                                    <button type="button" class="btn font-weight-700 mr-2" data-toggle="modal" data-target="#casesTrialUpgradeModal"
                                        style="background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;border:none;border-radius:8px;font-size:13px;padding:8px 16px;">
                                        <i class="anticon anticon-info-circle mr-1"></i>Mehr erfahren
                                    </button>
                                    <a href="packages.php" class="btn font-weight-700"
                                        style="background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border:none;border-radius:8px;font-size:13px;padding:8px 16px;">
                                        <i class="anticon anticon-rocket mr-1"></i>Jetzt upgraden
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
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
                        </div><!-- /position-relative wrapper -->
                    </div><!-- /card-body -->
            </div>
        </div>
    </div>
</div>
<!-- Content Wrapper END -->

<!-- ═══ Cases Page: Trial Upgrade Modal ══════════════════════════════════════ -->
<div class="modal fade" id="casesTrialUpgradeModal" tabindex="-1" role="dialog" aria-labelledby="casesTrialUpgradeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:540px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header border-0 px-4 py-4" style="background:linear-gradient(135deg,#1a2a6c 0%,#2950a8 60%,#2da9e3 100%);color:#fff;border-radius:16px 16px 0 0;">
                <div class="d-flex align-items-center">
                    <div class="mr-3" style="width:46px;height:46px;background:rgba(255,255,255,0.18);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">
                        <i class="anticon anticon-rocket"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 font-weight-bold" id="casesTrialUpgradeModalLabel" style="font-size:1.05rem;">Upgrade erforderlich</h5>
                        <small style="opacity:0.85;font-size:12px;">Testzugang – Eingeschränkte Fallsicht</small>
                    </div>
                </div>
                <button type="button" class="close text-white ml-auto" data-dismiss="modal" aria-label="Schließen"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body px-4 py-4" style="background:#fff;">
                <div class="d-flex align-items-start p-3 mb-4" style="background:linear-gradient(135deg,#fff8e1,#fff3cd);border:1.5px solid #ffc107;border-radius:12px;">
                    <i class="anticon anticon-lock mr-3 mt-1" style="color:#d97706;font-size:20px;flex-shrink:0;"></i>
                    <div>
                        <strong style="color:#92400e;font-size:13px;">€100.000 Wiederherstellungslimit erreicht</strong>
                        <div style="font-size:12.5px;color:#78350f;margin-top:4px;line-height:1.5;">
                            Mit dem <strong>Testzugang</strong> sind Falldetails und Auszahlungen auf €100.000 zurückgewonnene Gelder begrenzt. Upgraden Sie auf ein kostenpflichtiges Abonnement für:
                        </div>
                    </div>
                </div>
                <div style="display:grid;gap:8px;margin-bottom:20px;">
                    <div style="display:flex;align-items:center;gap:10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 12px;">
                        <i class="anticon anticon-check-circle" style="color:#28a745;font-size:16px;flex-shrink:0;"></i>
                        <span style="font-size:13px;color:#495057;">Vollständige Fallsicht – alle Fälle ohne Limit anzeigen</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 12px;">
                        <i class="anticon anticon-check-circle" style="color:#28a745;font-size:16px;flex-shrink:0;"></i>
                        <span style="font-size:13px;color:#495057;">Auszahlungen freischalten und Gelder abheben</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 12px;">
                        <i class="anticon anticon-check-circle" style="color:#28a745;font-size:16px;flex-shrink:0;"></i>
                        <span style="font-size:13px;color:#495057;">Prioritäts-Support &amp; dedizierter Fallmanager</span>
                    </div>
                </div>
                <div style="background:linear-gradient(135deg,rgba(41,80,168,0.05),rgba(45,169,227,0.05));border:1px solid rgba(41,80,168,0.15);border-radius:10px;padding:12px 14px;">
                    <div style="font-size:12px;color:#495057;line-height:1.6;">
                        <i class="anticon anticon-safety mr-1" style="color:#2950a8;"></i>
                        <strong>Hinweis:</strong> Alle Pakete unterliegen unseren Compliance-Standards. Ihre Daten und Gelder sind vollständig geschützt.
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 py-3" style="background:#f8f9fa;border-radius:0 0 16px 16px;gap:10px;">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius:8px;">Schließen</button>
                <a href="packages.php" class="btn btn-sm font-weight-700" style="background:linear-gradient(135deg,#2950a8,#2da9e3);color:#fff;border:none;border-radius:8px;padding:8px 20px;">
                    <i class="anticon anticon-rocket mr-1"></i>Jetzt upgraden
                </a>
            </div>
        </div>
    </div>
</div>
<!-- /Cases Trial Upgrade Modal -->

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
        ]
    });

    // View Case Details
    $('#casesTable').on('click', '.view-case', function() {
        const caseId = $(this).data('id');
        loadCaseDetails(caseId);
    });

    // Open document upload modal
    $('#casesTable').on('click', '.upload-docs', function() {
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
