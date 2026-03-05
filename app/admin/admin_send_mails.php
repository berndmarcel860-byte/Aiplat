<?php
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Send Emails</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Send Emails</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <h5>Send Email to Users</h5>
            
            <form id="sendEmailForm">
                <div class="form-group">
                    <label>Recipients</label>
                    <select class="form-control select2" name="recipients" multiple required>
                        <option value="all">All Users</option>
                        <option value="verified">Verified Users Only</option>
                        <option value="unverified">Unverified Users Only</option>
                        <option value="with_cases">Users With Cases</option>
                        <option value="without_cases">Users Without Cases</option>
                        <option value="active">Active Users</option>
                        <option value="suspended">Suspended Users</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Email Template</label>
                    <select class="form-control" name="template_id" id="emailTemplateSelect">
                        <option value="">Custom Email</option>
                        <!-- Templates will be loaded via AJAX -->
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" class="form-control" name="subject" required>
                </div>
                
                <div class="form-group">
                    <label>Message</label>
                    <textarea class="form-control" id="emailMessage" name="message" rows="10" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Available Variables</label>
                    <div id="availableVariables" class="alert alert-info">
                        Select a template to see available variables
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Send Email</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize select2
    $('.select2').select2();
    
    // Load email templates
    $.ajax({
        url: 'admin_ajax/get_email_templates.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">Custom Email</option>';
                response.templates.forEach(template => {
                    options += `<option value="${template.id}">${template.template_key}</option>`;
                });
                $('#emailTemplateSelect').html(options);
            }
        }
    });
    
    // Load template content when selected
    $('#emailTemplateSelect').change(function() {
        const templateId = $(this).val();
        
        if (templateId) {
            $.ajax({
                url: 'admin_ajax/get_email_template.php',
                type: 'GET',
                data: { id: templateId },
                success: function(response) {
                    if (response.success) {
                        const template = response.template;
                        $('input[name="subject"]').val(template.subject);
                        $('#emailMessage').val(template.content);
                        
                        let variablesHtml = 'Available variables: ';
                        if (template.variables) {
                            const vars = template.variables.split(',');
                            vars.forEach(v => {
                                variablesHtml += `{{${v.trim()}}} `;
                            });
                        } else {
                            variablesHtml = 'No variables defined for this template';
                        }
                        
                        $('#availableVariables').html(variablesHtml);
                    }
                }
            });
        } else {
            $('input[name="subject"]').val('');
            $('#emailMessage').val('');
            $('#availableVariables').html('Select a template to see available variables');
        }
    });
    
    // Send email form submission
    $('#sendEmailForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        if (confirm('Are you sure you want to send this email to selected recipients?')) {
            $.ajax({
                url: 'admin_ajax/send_email.php',
                type: 'POST',
                data: formData,
                beforeSend: function() {
                    $('#sendEmailForm button[type="submit"]').prop('disabled', true)
                        .html('<i class="anticon anticon-loading anticon-spin"></i> Sending...');
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#sendEmailForm')[0].reset();
                        $('#emailTemplateSelect').trigger('change');
                    } else {
                        toastr.error(response.message);
                    }
                },
                complete: function() {
                    $('#sendEmailForm button[type="submit"]').prop('disabled', false).html('Send Email');
                }
            });
        }
    });
});
</script>