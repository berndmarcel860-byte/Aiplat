<?php 
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Email Management</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Email Templates</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5>Email Templates</h5>
                <button class="btn btn-primary" data-toggle="modal" data-target="#addEmailTemplateModal">
                    <i class="anticon anticon-plus"></i> Add Template
                </button>
            </div>
            
            <div class="m-t-25">
                <table id="emailTemplatesTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Template Key</th>
                            <th>Subject</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Email Template Modal -->
<div class="modal fade" id="addEmailTemplateModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Email Template</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="addEmailTemplateForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Template Key</label>
                        <input type="text" class="form-control" name="template_key" required>
                        <small class="text-muted">Unique identifier for this template (e.g., welcome_email)</small>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" class="form-control" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <textarea class="form-control" name="content" rows="10" required></textarea>
                        <small class="text-muted">Use {{variable}} for dynamic content</small>
                    </div>
                    <div class="form-group">
                        <label>Available Variables</label>
                        <textarea class="form-control" name="variables" rows="3" placeholder="Enter variables as comma-separated list (e.g., name,email,amount)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="previewAddTemplate">
                        <i class="anticon anticon-eye"></i> Preview
                    </button>
                    <button type="submit" class="btn btn-primary">Add Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Email Template Modal -->
<div class="modal fade" id="editEmailTemplateModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Email Template</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="editEmailTemplateForm">
                <input type="hidden" name="template_id" id="editTemplateId">
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="previewEditTemplate">
                        <i class="anticon anticon-eye"></i> Preview
                    </button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Template Preview Modal -->
<div class="modal fade" id="previewModal">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Email Template Preview</h5>
        <button type="button" class="close" data-dismiss="modal"><i class="anticon anticon-close"></i></button>
      </div>
      <div class="modal-body p-0">
        <iframe id="previewFrame" style="width:100%;height:600px;border:none;"></iframe>
      </div>
    </div>
  </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const emailTemplatesTable = $('#emailTemplatesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: 'admin_ajax/get_email_templates.php', type: 'POST' },
        columns: [
            { data: 'id' },
            { data: 'template_key' },
            { data: 'subject' },
            { data: 'updated_at', render: data => new Date(data).toLocaleString() },
            {
                data: 'id',
                render: data => `
                    <div class="btn-group">
                        <button class="btn btn-sm btn-primary edit-template" data-id="${data}">
                            <i class="anticon anticon-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-template" data-id="${data}">
                            <i class="anticon anticon-delete"></i>
                        </button>
                    </div>
                `
            }
        ]
    });

    // Add Email Template
    $('#addEmailTemplateForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.ajax({
            url: 'admin_ajax/add_email_template.php',
            type: 'POST',
            data: formData,
            beforeSend: () => $('#addEmailTemplateForm button[type="submit"]').prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Adding...'),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#addEmailTemplateModal').modal('hide');
                    emailTemplatesTable.ajax.reload();
                    $('#addEmailTemplateForm')[0].reset();
                } else toastr.error(response.message);
            },
            complete: () => $('#addEmailTemplateForm button[type="submit"]').prop('disabled', false).html('Add Template')
        });
    });

    // Edit Email Template - Load Data
    $('#emailTemplatesTable').on('click', '.edit-template', function() {
        const templateId = $(this).data('id');
        $.get('admin_ajax/get_email_template.php', { id: templateId }, function(response) {
            if (response.success) {
                const t = response.template;
                const formHtml = `
                    <div class="form-group">
                        <label>Template Key</label>
                        <input type="text" class="form-control" name="template_key" value="${t.template_key}" required>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" class="form-control" name="subject" value="${t.subject}" required>
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <textarea class="form-control" name="content" rows="10" required>${t.content}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Available Variables</label>
                        <textarea class="form-control" name="variables" rows="3">${t.variables || ''}</textarea>
                    </div>`;
                $('#editTemplateId').val(t.id);
                $('#editEmailTemplateForm .modal-body').html(formHtml);
                $('#editEmailTemplateModal').modal('show');
            } else toastr.error(response.message);
        });
    });

    // Edit Email Template Form Submit
    $('#editEmailTemplateForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        $.ajax({
            url: 'admin_ajax/update_email_template.php',
            type: 'POST',
            data: formData,
            beforeSend: () => $('#editEmailTemplateForm button[type="submit"]').prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Saving...'),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#editEmailTemplateModal').modal('hide');
                    emailTemplatesTable.ajax.reload();
                } else toastr.error(response.message);
            },
            complete: () => $('#editEmailTemplateForm button[type="submit"]').prop('disabled', false).html('Save Changes')
        });
    });

    // Delete Template
    $('#emailTemplatesTable').on('click', '.delete-template', function() {
        const id = $(this).data('id');
        if (confirm('Are you sure you want to delete this email template?')) {
            $.post('admin_ajax/delete_email_template.php', { id }, function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    emailTemplatesTable.ajax.reload();
                } else toastr.error(response.message);
            });
        }
    });

    // ==============================
    // 👁️ PREVIEW FUNCTIONS
    // ==============================

    function showPreview(subject, content, variables) {
        $.post('admin_ajax/preview_email_template.php', { subject, content, variables })
        .done(function(html) {
            const iframe = document.getElementById('previewFrame');
            iframe.contentWindow.document.open();
            iframe.contentWindow.document.write(html);
            iframe.contentWindow.document.close();
            $('#previewModal').modal('show');
        })
        .fail(() => toastr.error('Failed to generate preview.'));
    }

    // Add modal preview
    $('#previewAddTemplate').on('click', function() {
        const s = $('#addEmailTemplateForm [name="subject"]').val();
        const c = $('#addEmailTemplateForm [name="content"]').val();
        const v = $('#addEmailTemplateForm [name="variables"]').val();
        if (!c.trim()) return toastr.warning('Please enter content before preview.');
        showPreview(s, c, v);
    });

    // Edit modal preview
    $('#previewEditTemplate').on('click', function() {
        const modal = $('#editEmailTemplateForm');
        const s = modal.find('[name="subject"]').val();
        const c = modal.find('[name="content"]').val();
        const v = modal.find('[name="variables"]').val();
        if (!c.trim()) return toastr.warning('Please enter content before preview.');
        showPreview(s, c, v);
    });
});
</script>

