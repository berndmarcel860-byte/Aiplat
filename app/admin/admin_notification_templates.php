<?php
require_once 'admin_header.php';

// Fetch categories for filter
$categories = [];
try {
    $catStmt = $pdo->query("SELECT DISTINCT category FROM email_notifications ORDER BY category ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $categories = [];
}

// Default HTML content for new templates
$defaultContent = '<p>Sehr geehrte/r <strong>{first_name} {last_name}</strong>,</p>

<p>
  [Hier den Hauptinhalt der E-Mail einfügen.]
</p>

<p>
  Bei Fragen stehen wir Ihnen unter <a href="mailto:{contact_email}">{contact_email}</a> gerne zur Verfügung.
</p>

<p>
  <a href="{dashboard_url}" style="display:inline-block;background:#2950a8;color:#fff;padding:10px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">
    Zum Kundenportal
  </a>
</p>

<p>Mit freundlichen Grüßen,<br>Ihr {brand_name}-Team</p>';
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title"><i class="anticon anticon-mail mr-2"></i>Email Notificasion TMP</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Email Notification Templates</span>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="anticon anticon-file-text mr-1"></i> Notification Templates</h5>
            <button class="btn btn-primary btn-sm" id="addTemplateBtn">
                <i class="anticon anticon-plus"></i> New Template
            </button>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <select id="categoryFilter" class="form-control form-control-sm">
                        <option value="">— All Categories —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars(ucfirst($cat)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table id="notifTemplatesTable" class="table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Key</th>
                            <th>Name</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Active</th>
                            <th>Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     ADD / EDIT MODAL
     ============================================================ -->
<div class="modal fade" id="templateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="templateModalTitle">
                    <i class="anticon anticon-file-text mr-1"></i> Add Notification Template
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="templateForm">
                <input type="hidden" name="id" id="templateId" value="0">
                <div class="modal-body">
                    <div class="row">
                        <!-- Left column -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Notification Key <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="notification_key" id="notificationKey"
                                       pattern="[a-zA-Z0-9_]+" required
                                       placeholder="e.g. withdrawal_approved">
                                <small class="text-muted">Unique key (letters, digits, underscores only)</small>
                            </div>
                            <div class="form-group">
                                <label>Template Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="templateName"
                                       required placeholder="e.g. Withdrawal Approved">
                            </div>
                            <div class="form-group">
                                <label>Email Subject <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="subject" id="templateSubject"
                                       required placeholder="e.g. Ihre Auszahlung wurde genehmigt – {reference}">
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <select class="form-control" name="category" id="templateCategory">
                                    <option value="general">General</option>
                                    <option value="engagement">Engagement</option>
                                    <option value="financial">Financial</option>
                                    <option value="kyc">KYC</option>
                                    <option value="onboarding">Onboarding</option>
                                    <option value="recovery">Recovery</option>
                                    <option value="security">Security</option>
                                    <option value="support">Support</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea class="form-control" name="description" id="templateDescription"
                                          rows="2" placeholder="Brief description of when this template is used"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Variables (JSON array)</label>
                                <input type="text" class="form-control" name="variables" id="templateVariables"
                                       placeholder='["first_name","last_name","amount"]'>
                                <small class="text-muted">
                                    Common: first_name, last_name, email, amount, reference, brand_name,
                                    contact_email, dashboard_url, current_date
                                </small>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="isActiveSwitch"
                                           name="is_active" value="1" checked>
                                    <label class="custom-control-label" for="isActiveSwitch">Active</label>
                                </div>
                            </div>
                        </div>

                        <!-- Right column: content editor -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="mb-0">HTML Content <span class="text-danger">*</span></label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="previewBtn">
                                        <i class="anticon anticon-eye"></i> Preview
                                    </button>
                                </div>
                                <textarea class="form-control font-monospace" name="content" id="templateContent"
                                          rows="16" required
                                          style="font-family:monospace;font-size:12px;"></textarea>
                            </div>

                            <!-- AI Generation Panel -->
                            <div class="card border-info">
                                <div class="card-header bg-info text-white py-2">
                                    <span><i class="anticon anticon-robot mr-1"></i> AI Content Generator</span>
                                </div>
                                <div class="card-body py-2">
                                    <div class="form-group mb-2">
                                        <label class="small">Describe what this email should say:</label>
                                        <textarea class="form-control form-control-sm" id="aiPrompt" rows="3"
                                                  placeholder="e.g. A professional German email notifying the user that their withdrawal request has been approved and will arrive in 2-3 business days. Include amount and reference fields."></textarea>
                                    </div>
                                    <button type="button" class="btn btn-info btn-sm" id="aiGenerateBtn">
                                        <i class="anticon anticon-thunderbolt mr-1"></i> Generate with AI
                                    </button>
                                    <small class="text-muted ml-2">Requires OpenAI API key in System Settings</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveTemplateBtn">
                        <i class="anticon anticon-save mr-1"></i> Save Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Email Preview</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body p-0">
                <iframe id="previewFrame" style="width:100%;height:580px;border:none;"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Delete Template</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete <strong id="deleteTemplateName"></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(function () {
    var defaultContent = <?= json_encode($defaultContent) ?>;
    var currentDeleteId = null;

    // ── DataTable ─────────────────────────────────────────────
    var table = $('#notifTemplatesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_notification_templates.php',
            type: 'POST',
            data: function (d) {
                d.category = $('#categoryFilter').val();
            }
        },
        columns: [
            { data: 'id', width: '50px' },
            { data: 'notification_key', render: function (d) { return '<code>' + escapeHtml(d) + '</code>'; } },
            { data: 'name', render: function (d) { return escapeHtml(d); } },
            { data: 'subject', render: function (d) { return escapeHtml(d).substring(0, 60) + (d.length > 60 ? '…' : ''); } },
            { data: 'category', render: function (d) { return '<span class="badge badge-light">' + escapeHtml(d) + '</span>'; } },
            {
                data: 'is_active',
                render: function (d) {
                    return d == 1
                        ? '<span class="badge badge-success">Yes</span>'
                        : '<span class="badge badge-secondary">No</span>';
                }
            },
            { data: 'updated_at', render: function (d) { return d ? new Date(d).toLocaleString() : '-'; } },
            {
                data: null,
                orderable: false,
                render: function (data, type, row) {
                    return '<div class="btn-group btn-group-sm">' +
                        '<button class="btn btn-sm btn-primary edit-btn" data-id="' + row.id + '" title="Edit"><i class="anticon anticon-edit"></i></button>' +
                        '<button class="btn btn-sm btn-danger delete-btn" data-id="' + row.id + '" data-name="' + escapeHtml(row.name) + '" title="Delete"><i class="anticon anticon-delete"></i></button>' +
                        '</div>';
                }
            }
        ],
        order: [[4, 'asc'], [2, 'asc']],
        pageLength: 25,
        language: { processing: '<i class="anticon anticon-loading anticon-spin"></i> Loading…' }
    });

    $('#categoryFilter').on('change', function () { table.ajax.reload(); });

    // ── Open Add Modal ─────────────────────────────────────────
    $('#addTemplateBtn').on('click', function () {
        resetForm();
        $('#templateModalTitle').html('<i class="anticon anticon-plus mr-1"></i> Add Notification Template');
        $('#templateContent').val(defaultContent);
        $('#templateModal').modal('show');
    });

    // ── Open Edit Modal ────────────────────────────────────────
    $('#notifTemplatesTable').on('click', '.edit-btn', function () {
        var id = $(this).data('id');
        $.getJSON('admin_ajax/get_notification_template.php', { id: id }, function (resp) {
            if (!resp.success) { toastr.error(resp.message); return; }
            var t = resp.template;
            resetForm();
            $('#templateModalTitle').html('<i class="anticon anticon-edit mr-1"></i> Edit Notification Template');
            $('#templateId').val(t.id);
            $('#notificationKey').val(t.notification_key);
            $('#templateName').val(t.name);
            $('#templateSubject').val(t.subject);
            $('#templateContent').val(t.content);
            $('#templateDescription').val(t.description || '');
            $('#templateCategory').val(t.category);
            $('#templateVariables').val(t.variables || '[]');
            $('#isActiveSwitch').prop('checked', t.is_active == 1);
            $('#templateModal').modal('show');
        });
    });

    // ── Save Template ──────────────────────────────────────────
    $('#templateForm').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#saveTemplateBtn');
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Saving…');

        // Serialise is_active correctly (unchecked checkbox won't appear in serialize)
        var formData = $(this).serialize();
        if (!$('#isActiveSwitch').is(':checked')) {
            formData += '&is_active=0';
        }

        $.ajax({
            url: 'admin_ajax/save_notification_template.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (resp) {
                if (resp.success) {
                    toastr.success(resp.message);
                    $('#templateModal').modal('hide');
                    table.ajax.reload();
                } else {
                    toastr.error(resp.message);
                }
            },
            error: function () { toastr.error('Server error while saving'); },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="anticon anticon-save mr-1"></i> Save Template');
            }
        });
    });

    // ── Delete ─────────────────────────────────────────────────
    $('#notifTemplatesTable').on('click', '.delete-btn', function () {
        currentDeleteId = $(this).data('id');
        $('#deleteTemplateName').text($(this).data('name'));
        $('#deleteModal').modal('show');
    });

    $('#confirmDeleteBtn').on('click', function () {
        if (!currentDeleteId) return;
        var $btn = $(this);
        $btn.prop('disabled', true).text('Deleting…');
        $.post('admin_ajax/delete_notification_template.php', { id: currentDeleteId }, function (resp) {
            if (resp.success) {
                toastr.success(resp.message);
                $('#deleteModal').modal('hide');
                table.ajax.reload();
            } else {
                toastr.error(resp.message);
            }
            $btn.prop('disabled', false).text('Delete');
            currentDeleteId = null;
        }, 'json').fail(function () { toastr.error('Delete failed'); $btn.prop('disabled', false).text('Delete'); });
    });

    // ── Preview ────────────────────────────────────────────────
    $('#previewBtn').on('click', function () {
        var content = $('#templateContent').val();
        var subject = $('#templateSubject').val() || 'Preview';
        var previewHtml = '<html><head><meta charset="UTF-8"><title>' + escapeHtml(subject) + '</title>'
            + '<style>body{font-family:Arial,sans-serif;padding:20px;max-width:700px;margin:auto;background:#f4f4f4;}'
            + '.email-wrap{background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);}'
            + '.highlight-box{background:#f0f4ff;border-left:4px solid #2950a8;padding:15px;margin:15px 0;border-radius:4px;}'
            + '</style></head><body><div class="email-wrap">'
            + '<h4 style="color:#2950a8;border-bottom:1px solid #eee;padding-bottom:8px;">' + escapeHtml(subject) + '</h4>'
            + content
            + '</div></body></html>';

        var blob = new Blob([previewHtml], { type: 'text/html' });
        var url = URL.createObjectURL(blob);
        document.getElementById('previewFrame').src = url;
        $('#previewModal').modal('show');
    });

    // ── AI Generate ────────────────────────────────────────────
    $('#aiGenerateBtn').on('click', function () {
        var prompt = $.trim($('#aiPrompt').val());
        if (!prompt) { toastr.warning('Please describe what the email should say.'); return; }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Generating…');

        $.ajax({
            url: 'admin_ajax/ai_generate_email_content.php',
            type: 'POST',
            data: { prompt: prompt },
            dataType: 'json',
            success: function (resp) {
                if (resp.success) {
                    $('#templateContent').val(resp.content);
                    toastr.success('AI content generated successfully!');
                } else {
                    toastr.error(resp.message);
                }
            },
            error: function () { toastr.error('AI generation failed. Check server logs.'); },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="anticon anticon-thunderbolt mr-1"></i> Generate with AI');
            }
        });
    });

    // ── Helpers ────────────────────────────────────────────────
    function resetForm() {
        $('#templateForm')[0].reset();
        $('#templateId').val(0);
        $('#templateContent').val('');
        $('#isActiveSwitch').prop('checked', true);
        $('#aiPrompt').val('');
    }

    function escapeHtml(str) {
        str = String(str || '');
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
});
</script>
