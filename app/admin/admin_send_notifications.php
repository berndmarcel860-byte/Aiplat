<?php
/**
 * Send Notifications Page
 * Comprehensive user filtering and bulk email notification system
 */
require_once 'admin_header.php';
require_once 'email_template_helper.php';

// Initialize email helper
$emailHelper = new EmailTemplateHelper($pdo);

// Get notification templates from the dedicated email_notifications table (grouped by category)
$notificationTemplates = [];
try {
    $notifStmt = $pdo->query("
        SELECT notification_key, name, subject, description, category, variables
        FROM email_notifications
        WHERE is_active = 1
        ORDER BY category ASC, name ASC
    ");
    $notifRows = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($notifRows as $row) {
        $notificationTemplates[$row['category']][] = $row;
    }
} catch (PDOException $e) {
    // Table may not yet exist – fall back gracefully
    $notificationTemplates = [];
}

// Get all generic email templates as fallback
$allTemplatesStmt = $pdo->query("
    SELECT template_key, subject 
    FROM email_templates 
    ORDER BY template_key ASC
");
$allTemplates = $allTemplatesStmt->fetchAll(PDO::FETCH_ASSOC);

$categoryLabels = [
    'engagement'  => '💬 Engagement & Inaktivität',
    'onboarding'  => '🚀 Onboarding',
    'kyc'         => '🔒 KYC & Verifizierung',
    'financial'   => '💰 Finanzen & Transaktionen',
    'case'        => '📁 Fallmanagement',
    'security'    => '🛡️ Sicherheit',
    'account'     => '👤 Konto',
    'reporting'   => '📊 Berichte',
    'general'     => '📧 Allgemein',
];
?>

<div class="main-content">
    <div class="page-header">
        <h2><i class="anticon anticon-send"></i> Benachrichtigungen senden</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <a href="#" class="breadcrumb-item">Kommunikation</a>
                <span class="breadcrumb-item active">Benachrichtigungen senden</span>
            </nav>
        </div>
    </div>

    <!-- Filter Panel -->
    <div class="card">
        <div class="card-header">
            <h4 class="card-title"><i class="anticon anticon-filter"></i> Benutzerfilter</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><strong>KYC-Status</strong></label>
                        <select class="form-control" id="filterKyc">
                            <option value="">Alle Benutzer</option>
                            <option value="no_kyc">Kein KYC</option>
                            <option value="pending_kyc">KYC ausstehend</option>
                            <option value="rejected_kyc">KYC abgelehnt</option>
                            <option value="approved_kyc">KYC genehmigt</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><strong>Login-Aktivität</strong></label>
                        <select class="form-control" id="filterLogin">
                            <option value="">Alle Benutzer</option>
                            <option value="never_logged_in">Nie angemeldet</option>
                            <option value="inactive_7">7+ Tage inaktiv</option>
                            <option value="inactive_14">14+ Tage inaktiv</option>
                            <option value="inactive_30">30+ Tage inaktiv</option>
                            <option value="inactive_60">60+ Tage inaktiv</option>
                            <option value="inactive_90">90+ Tage inaktiv</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><strong>Guthaben</strong></label>
                        <select class="form-control" id="filterBalance">
                            <option value="">Alle Benutzer</option>
                            <option value="has_balance">Hat Guthaben (> 0€)</option>
                            <option value="high_balance">Hohes Guthaben (> 100€)</option>
                            <option value="very_high_balance">Sehr hohes Guthaben (> 500€)</option>
                            <option value="no_balance">Kein Guthaben</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><strong>Onboarding-Status</strong></label>
                        <select class="form-control" id="filterOnboarding">
                            <option value="">Alle Benutzer</option>
                            <option value="incomplete_onboarding">Onboarding unvollständig</option>
                            <option value="complete_onboarding">Onboarding vollständig</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><strong>Benutzerstatus</strong></label>
                        <select class="form-control" id="filterStatus">
                            <option value="">Alle Benutzer</option>
                            <option value="active">Aktiv</option>
                            <option value="suspended">Gesperrt</option>
                            <option value="pending">Ausstehend</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><strong>E-Mail-Verifizierung</strong></label>
                        <select class="form-control" id="filterEmailVerified">
                            <option value="">Alle Benutzer</option>
                            <option value="verified">Verifiziert</option>
                            <option value="not_verified">Nicht verifiziert</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <button class="btn btn-primary" id="applyFilters">
                        <i class="anticon anticon-search"></i> Filter anwenden
                    </button>
                    <button class="btn btn-default" id="clearFilters">
                        <i class="anticon anticon-close"></i> Filter zurücksetzen
                    </button>
                    <span class="ml-3" id="filterResultsCount"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Users List -->
    <div class="card">
        <div class="card-header">
            <h4 class="card-title"><i class="anticon anticon-team"></i> Benutzerliste</h4>
            <div class="card-controls">
                <button class="btn btn-sm btn-success" id="sendToSelectedBtn" disabled>
                    <i class="anticon anticon-send"></i> An ausgewählte senden (<span id="selectedCount">0</span>)
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="usersTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>E-Mail</th>
                            <th>KYC</th>
                            <th>Letzter Login</th>
                            <th>Guthaben</th>
                            <th>Status</th>
                            <th>Onboarding</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Send Email Modal -->
<div class="modal fade" id="sendEmailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="anticon anticon-send"></i> E-Mail-Benachrichtigung senden
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="sendEmailForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="anticon anticon-info-circle"></i>
                        Sie senden eine E-Mail an <strong><span id="recipientCount">0</span> Benutzer</strong>.
                    </div>
                    
                    <div class="form-group">
                        <label><strong>E-Mail-Vorlage auswählen</strong></label>
                        <select class="form-control" name="template_key" id="emailTemplate" required>
                            <option value="">-- Vorlage auswählen --</option>
                            <?php if (!empty($notificationTemplates)): ?>
                                <?php foreach ($notificationTemplates as $cat => $catTemplates): ?>
                                    <optgroup label="<?= htmlspecialchars($categoryLabels[$cat] ?? ucfirst($cat)) ?>">
                                        <?php foreach ($catTemplates as $notif): ?>
                                            <option value="notif:<?= htmlspecialchars($notif['notification_key']) ?>"
                                                    data-description="<?= htmlspecialchars($notif['description'] ?? '') ?>"
                                                    data-vars="<?= htmlspecialchars($notif['variables']) ?>">
                                                <?= htmlspecialchars($notif['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if (!empty($allTemplates)): ?>
                                <optgroup label="📋 E-Mail-Vorlagensystem">
                                    <?php foreach ($allTemplates as $template): ?>
                                        <option value="tpl:<?= htmlspecialchars($template['template_key']) ?>">
                                            <?= htmlspecialchars($template['subject']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                        <div id="templateDescription" class="mt-2" style="display:none;">
                            <small class="text-muted" id="templateDescriptionText"></small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="button" class="btn btn-sm btn-info" id="previewEmailBtn">
                            <i class="anticon anticon-eye"></i> Vorschau anzeigen
                        </button>
                    </div>
                    
                    <div id="emailPreview" style="display: none;">
                        <h6>Vorschau:</h6>
                        <div class="card bg-light">
                            <div class="card-body" id="previewContent">
                                <!-- Preview will be loaded here -->
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="selected_users" id="selectedUsersInput">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="anticon anticon-send"></i> E-Mails senden
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Progress Modal -->
<div class="modal fade" id="progressModal" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">E-Mails werden gesendet...</h5>
            </div>
            <div class="modal-body">
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         id="sendProgress" 
                         style="width: 0%">0%</div>
                </div>
                <p class="mt-3" id="progressText">Wird vorbereitet...</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    let selectedUsers = [];
    let currentFilters = {};
    
    // Initialize DataTable
    const usersTable = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_filtered_users.php',
            type: 'POST',
            data: function(d) {
                d.filters = currentFilters;
            }
        },
        columns: [
            { 
                data: 'id',
                orderable: false,
                render: function(data, type, row) {
                    return `<input type="checkbox" class="user-checkbox" value="${data}" data-email="${row.email}" data-name="${row.first_name} ${row.last_name}">`;
                }
            },
            { data: 'id' },
            { 
                data: null,
                render: function(data) {
                    return `${data.first_name} ${data.last_name}`;
                }
            },
            { data: 'email' },
            { 
                data: 'kyc_status',
                render: function(data) {
                    const badges = {
                        'none': '<span class="badge badge-secondary">Keins</span>',
                        'pending': '<span class="badge badge-warning">Ausstehend</span>',
                        'approved': '<span class="badge badge-success">Genehmigt</span>',
                        'rejected': '<span class="badge badge-danger">Abgelehnt</span>'
                    };
                    return badges[data] || '<span class="badge badge-secondary">Keins</span>';
                }
            },
            { 
                data: 'last_login',
                render: function(data) {
                    if (!data) return '<span class="badge badge-danger">Nie</span>';
                    const date = new Date(data);
                    const days = Math.floor((new Date() - date) / (1000 * 60 * 60 * 24));
                    if (days === 0) return '<span class="badge badge-success">Heute</span>';
                    if (days === 1) return '<span class="badge badge-success">Gestern</span>';
                    if (days < 7) return `<span class="badge badge-info">${days}d</span>`;
                    if (days < 30) return `<span class="badge badge-warning">${days}d</span>`;
                    return `<span class="badge badge-danger">${days}d</span>`;
                }
            },
            { 
                data: 'balance',
                render: function(data) {
                    const balance = parseFloat(data);
                    let badgeClass = 'secondary';
                    if (balance > 500) badgeClass = 'success';
                    else if (balance > 100) badgeClass = 'info';
                    else if (balance > 0) badgeClass = 'warning';
                    return `<span class="badge badge-${badgeClass}">${balance.toFixed(2)}€</span>`;
                }
            },
            { 
                data: 'status',
                render: function(data) {
                    const badges = {
                        'active': '<span class="badge badge-success">Aktiv</span>',
                        'suspended': '<span class="badge badge-danger">Gesperrt</span>',
                        'pending': '<span class="badge badge-warning">Ausstehend</span>'
                    };
                    return badges[data] || '<span class="badge badge-secondary">Unbekannt</span>';
                }
            },
            { 
                data: 'onboarding_complete',
                render: function(data) {
                    return data == 1 
                        ? '<span class="badge badge-success">Vollständig</span>' 
                        : '<span class="badge badge-warning">Unvollständig</span>';
                }
            }
        ],
        order: [[1, 'desc']],
        pageLength: 25
    });
    
    // Apply filters
    $('#applyFilters').click(function() {
        currentFilters = {
            kyc: $('#filterKyc').val(),
            login: $('#filterLogin').val(),
            balance: $('#filterBalance').val(),
            onboarding: $('#filterOnboarding').val(),
            status: $('#filterStatus').val(),
            email_verified: $('#filterEmailVerified').val()
        };
        usersTable.ajax.reload();
        selectedUsers = [];
        updateSelectedCount();
    });
    
    // Clear filters
    $('#clearFilters').click(function() {
        $('#filterKyc, #filterLogin, #filterBalance, #filterOnboarding, #filterStatus, #filterEmailVerified').val('');
        currentFilters = {};
        usersTable.ajax.reload();
    });
    
    // Select all
    $('#selectAll').change(function() {
        const isChecked = $(this).is(':checked');
        $('.user-checkbox').prop('checked', isChecked);
        updateSelectedUsers();
    });
    
    // Individual checkbox
    $('#usersTable').on('change', '.user-checkbox', function() {
        updateSelectedUsers();
    });
    
    function updateSelectedUsers() {
        selectedUsers = [];
        $('.user-checkbox:checked').each(function() {
            selectedUsers.push({
                id: $(this).val(),
                email: $(this).data('email'),
                name: $(this).data('name')
            });
        });
        updateSelectedCount();
    }
    
    function updateSelectedCount() {
        $('#selectedCount').text(selectedUsers.length);
        $('#sendToSelectedBtn').prop('disabled', selectedUsers.length === 0);
        $('#selectAll').prop('checked', selectedUsers.length > 0 && selectedUsers.length === $('.user-checkbox').length);
    }
    
    // Show template description when a notification template is selected
    $('#emailTemplate').on('change', function() {
        const selected = $(this).find(':selected');
        const desc = selected.data('description');
        if (desc) {
            $('#templateDescriptionText').text(desc);
            $('#templateDescription').show();
        } else {
            $('#templateDescription').hide();
        }
    });

    // Send to selected
    $('#sendToSelectedBtn').click(function() {
        if (selectedUsers.length === 0) {
            toastr.error('Bitte wählen Sie mindestens einen Benutzer aus');
            return;
        }
        $('#recipientCount').text(selectedUsers.length);
        $('#selectedUsersInput').val(JSON.stringify(selectedUsers));
        $('#sendEmailModal').modal('show');
    });
    
    // Preview email
    $('#previewEmailBtn').click(function() {
        const templateKey = $('#emailTemplate').val();
        if (!templateKey) {
            toastr.error('Bitte wählen Sie eine Vorlage aus');
            return;
        }
        
        $.ajax({
            url: 'admin_ajax/preview_notification.php',
            type: 'POST',
            dataType: 'json',
            data: { template_key: templateKey },
            success: function(response) {
                if (response.success) {
                    $('#previewContent').html(response.preview);
                    $('#emailPreview').slideDown();
                } else {
                    toastr.error(response.message || 'Fehler beim Laden der Vorschau');
                }
            },
            error: function() {
                toastr.error('Fehler beim Laden der Vorschau');
            }
        });
    });
    
    // Send emails
    $('#sendEmailForm').submit(function(e) {
        e.preventDefault();
        
        if (!confirm(`Möchten Sie wirklich E-Mails an ${selectedUsers.length} Benutzer senden?`)) {
            return;
        }
        
        const templateKey = $('#emailTemplate').val();
        
        $('#sendEmailModal').modal('hide');
        $('#progressModal').modal('show');
        
        $.ajax({
            url: 'admin_ajax/send_bulk_notifications.php',
            type: 'POST',
            dataType: 'json',
            timeout: 300000,
            data: {
                template_key: templateKey,
                users: JSON.stringify(selectedUsers)
            },
            success: function(response) {
                $('#progressModal').modal('hide');
                if (response.success) {
                    toastr.success(`Erfolgreich ${response.sent} E-Mails gesendet!`);
                    if (response.failed > 0) {
                        toastr.warning(`${response.failed} E-Mails konnten nicht gesendet werden`);
                    }
                    // Clear selection
                    selectedUsers = [];
                    $('.user-checkbox').prop('checked', false);
                    updateSelectedCount();
                } else {
                    toastr.error(response.message || 'Fehler beim Senden der E-Mails');
                }
            },
            error: function(xhr, status) {
                $('#progressModal').modal('hide');
                if (status === 'timeout') {
                    toastr.error('Zeitüberschreitung beim Senden — bitte weniger Benutzer auswählen');
                } else {
                    toastr.error('Fehler beim Senden der E-Mails');
                }
            }
        });
    });
});
</script>