<?php
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2>Email Templates</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Email Templates</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Email Templates</h5>
                <div class="d-flex">
                    <button class="btn btn-info mr-2" id="refreshTemplates">
                        <i class="anticon anticon-reload"></i> Refresh
                    </button>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addTemplateModal">
                        <i class="anticon anticon-plus"></i> Add Template
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="templatesTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Template Key</th>
                            <th>Subject</th>
                            <th>Variables</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Template Modal -->
<div class="modal fade" id="addTemplateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Email Template</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="templateForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="template_id">
                    <div class="form-group">
                        <label>Template Key</label>
                        <input type="text" class="form-control" name="template_key" required>
                        <small class="form-text text-muted">Unique identifier for this template</small>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" class="form-control" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <textarea class="form-control" name="content" rows="10" required></textarea>
                        <small class="form-text text-muted">You can use variables like {user_name}, {case_number}, etc.</small>
                    </div>
                    <div class="form-group">
                        <label>Available Variables (JSON format)</label>
                        <textarea class="form-control" name="variables" rows="3" placeholder='["user_name", "case_number", "amount"]'></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    const templatesTable = $('#templatesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_email_templates.php',
            type: 'POST'
        },
        order: [[4, 'desc']],
        columns: [
            { data: 'id' },
            { data: 'template_key' },
            { data: 'subject' },
            { 
                data: 'variables',
                render: function(data) {
                    if (!data) return '-';
                    try {
                        const vars = JSON.parse(data);
                        return vars.join(', ');
                    } catch(e) {
                        return data;
                    }
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleDateString();
                }
            },
            { 
                data: 'updated_at',
                render: function(data) {
                    return new Date(data).toLocaleDateString();
                }
            },
            {
                data: 'id',
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button class="btn btn-sm btn-primary edit-template" 
                                    data-id="${data}" 
                                    title="Edit Template">
                                <i class="anticon anticon-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-template" 
                                    data-id="${data}" 
                                    title="Delete Template">
                                <i class="anticon anticon-delete"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });
    
    $('#refreshTemplates').click(function() {
        templatesTable.ajax.reload();
    });
    
    $('#templateForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const url = $('#template_id').val() ? 'admin_ajax/update_email_template.php' : 'admin_ajax/add_email_template.php';
        
        $.post(url, formData)
        .done(function(response) {
            if (response.success) {
                $('#addTemplateModal').modal('hide');
                templatesTable.ajax.reload();
                toastr.success(response.message);
                $('#templateForm')[0].reset();
            } else {
                toastr.error(response.message);
            }
        })
        .fail(function() {
            toastr.error('Failed to save template');
        });
    });
});
</script>