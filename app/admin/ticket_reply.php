<?php
require_once 'admin_header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: support_tickets.php");
    exit();
}

$ticket_id = (int)$_GET['id'];
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Reply to Support Ticket</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <a href="support_tickets.php" class="breadcrumb-item">Support Tickets</a>
                <span class="breadcrumb-item active">Ticket #<?= $ticket_id ?></span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <!-- Loading indicator -->
            <div id="loadingIndicator" class="text-center" style="display: none;">
                <div class="ant-spin ant-spin-lg"></div>
                <p>Loading ticket details...</p>
            </div>

            <!-- Error message -->
            <div id="errorMessage" class="alert alert-danger" style="display: none;"></div>

            <!-- Ticket Details Section -->
            <div id="ticketDetails" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 id="ticketSubject"></h3>
                        <div class="d-flex align-items-center mt-2">
                            <span class="badge badge-pill mr-2" id="ticketStatus"></span>
                            <span class="badge badge-pill mr-2" id="ticketPriority"></span>
                            <span class="text-muted" id="ticketCreated"></span>
                        </div>
                    </div>
                    <a href="support_tickets.php" class="btn btn-default">
                        <i class="anticon anticon-arrow-left"></i> Back to Tickets
                    </a>
                </div>

                <!-- Conversation Thread -->
                <div class="conversation-thread mb-4" id="conversationThread"></div>

                <!-- Reply Form -->
                <div class="reply-form card">
                    <div class="card-header">
                        <h4>Reply to Ticket</h4>
                    </div>
                    <div class="card-body">
                        <form id="replyForm">
                            <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['admin_csrf_token'] ?>">
                            
                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Attachments</label>
                                <div class="dropzone" id="attachmentDropzone">
                                    <div class="dz-message">
                                        <i class="anticon anticon-upload" style="font-size: 32px;"></i>
                                        <p>Drop files here or click to upload</p>
                                        <small class="text-muted">(Max 5MB per file. Allowed: JPG, PNG, PDF, DOC, XLS)</small>
                                    </div>
                                </div>
                                <input type="hidden" name="attachment_ids" id="attachmentIds" value="">
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="changeStatus" name="change_status" checked>
                                    <label class="custom-control-label" for="changeStatus">Update ticket status to "In Progress"</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="priority">Change Priority</label>
                                <select class="form-control" id="priority" name="priority">
                                    <option value="">Keep Current Priority</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="internal_notes">Internal Notes (not visible to user)</label>
                                <textarea class="form-control" id="internal_notes" name="internal_notes" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="anticon anticon-send"></i> Send Reply
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<!-- Include Dropzone CSS & JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize Dropzone for file uploads
    Dropzone.autoDiscover = false;
    const attachmentDropzone = new Dropzone("#attachmentDropzone", {
        url: "admin_ajax/upload_attachment.php",
        paramName: "file",
        maxFilesize: 5, // MB
        acceptedFiles: "image/jpeg,image/png,application/pdf,application/msword,application/vnd.ms-excel",
        addRemoveLinks: true,
        autoProcessQueue: true,
        parallelUploads: 5,
        dictDefaultMessage: "Drop files here to upload",
        dictFileTooBig: "File is too big ({{filesize}}MB). Max filesize: {{maxFilesize}}MB.",
        dictInvalidFileType: "Invalid file type",
        dictRemoveFile: "Remove",
        headers: {
            'X-CSRF-TOKEN': '<?= $_SESSION['admin_csrf_token'] ?>'
        },
        init: function() {
            this.on("success", function(file, response) {
                if (!response.success) {
                    this.removeFile(file);
                    toastr.error(response.message);
                    return;
                }
                
                // Add file ID to hidden input
                const currentIds = $('#attachmentIds').val();
                $('#attachmentIds').val(currentIds ? currentIds + ',' + response.file_id : response.file_id);
                
                // Add file ID to the file element
                file.previewElement.setAttribute('data-file-id', response.file_id);
            });
            
            this.on("removedfile", function(file) {
                const fileId = file.previewElement.getAttribute('data-file-id');
                if (fileId) {
                    // Remove file ID from hidden input
                    const currentIds = $('#attachmentIds').val().split(',');
                    const newIds = currentIds.filter(id => id !== fileId);
                    $('#attachmentIds').val(newIds.join(','));
                    
                    // Delete file from server
                    $.ajax({
                        url: 'admin_ajax/delete_attachment.php',
                        type: 'POST',
                        data: { 
                            file_id: fileId,
                            csrf_token: '<?= $_SESSION['admin_csrf_token'] ?>'
                        }
                    });
                }
            });
        }
    });

    // Load ticket details
    function loadTicketDetails() {
        $('#loadingIndicator').show();
        $('#ticketDetails').hide();
        $('#errorMessage').hide();
        
        $.ajax({
            url: 'admin_ajax/get_ticket.php',
            type: 'GET',
            data: { id: <?= $ticket_id ?> },
            dataType: 'json',
            success: function(response) {
                $('#loadingIndicator').hide();
                
                if (response.success) {
                    const ticket = response.ticket;
                    const user = response.user;
                    
                    // Set ticket details
                    $('#ticketSubject').text(ticket.subject);
                    $('#ticketCreated').text('Created: ' + new Date(ticket.created_at).toLocaleString());
                    
                    // Set status badge
                    const statusClass = {
                        'open': 'badge-primary',
                        'in_progress': 'badge-info',
                        'resolved': 'badge-success',
                        'closed': 'badge-secondary'
                    }[ticket.status] || 'badge-light';
                    $('#ticketStatus').text(ticket.status.replace('_', ' ')).addClass(statusClass);
                    
                    // Set priority badge
                    const priorityClass = {
                        'low': 'badge-info',
                        'medium': 'badge-primary',
                        'high': 'badge-warning',
                        'critical': 'badge-danger'
                    }[ticket.priority] || 'badge-light';
                    $('#ticketPriority').text(ticket.priority).addClass(priorityClass);
                    
                    // Build conversation thread
                    let conversationHtml = '';
                    response.conversation.forEach(msg => {
                        const isAdmin = msg.admin_id !== null;
                        const senderName = isAdmin ? 'Admin' : `${user.first_name} ${user.last_name}`;
                        const badge = isAdmin ? '<span class="badge badge-primary ml-2">Staff</span>' : '';
                        
                        conversationHtml += `
                            <div class="message ${isAdmin ? 'admin-message' : 'user-message'} mb-4 p-3 border rounded">
                                <div class="message-header d-flex justify-content-between mb-2">
                                    <div>
                                        <strong>${senderName}</strong>
                                        ${badge}
                                    </div>
                                    <small class="text-muted">${new Date(msg.created_at).toLocaleString()}</small>
                                </div>
                                <div class="message-body">
                                    <p>${msg.message.replace(/\n/g, '<br>')}</p>`;
                                    
                        if (msg.attachments) {
                            conversationHtml += `<div class="attachments mt-2">
                                <strong>Attachments:</strong><br>`;
                            
                            JSON.parse(msg.attachments).forEach(attachment => {
                                conversationHtml += `<a href="${attachment.url}" target="_blank" class="d-block">
                                    <i class="anticon anticon-paper-clip"></i> ${attachment.name}
                                </a>`;
                            });
                            
                            conversationHtml += `</div>`;
                        }
                        
                        conversationHtml += `</div></div>`;
                    });
                    
                    $('#conversationThread').html(conversationHtml);
                    $('#ticketDetails').show();
                } else {
                    $('#errorMessage').text(response.message).show();
                }
            },
            error: function(xhr, status, error) {
                $('#loadingIndicator').hide();
                $('#errorMessage').text('Error loading ticket details: ' + error).show();
            }
        });
    }
    
    // Handle form submission
    $('#replyForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serializeArray();
        const submitBtn = $('#submitBtn');
        
        submitBtn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Sending...');
        
        $.ajax({
            url: 'admin_ajax/process_ticket_reply.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    
                    // Clear form
                    $('#message').val('');
                    $('#internal_notes').val('');
                    attachmentDropzone.removeAllFiles(true);
                    $('#attachmentIds').val('');
                    
                    // Reload ticket to show new reply
                    loadTicketDetails();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr, status, error) {
                toastr.error('Error sending reply: ' + error);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="anticon anticon-send"></i> Send Reply');
            }
        });
    });
    
    // Initial load
    loadTicketDetails();
});
</script>

<style>
.message {
    background-color: #f8f9fa;
    border-left: 4px solid #dee2e6;
}
.admin-message {
    background-color: #f0f7ff;
    border-left-color: #1890ff;
}
.user-message {
    background-color: #f8f9fa;
    border-left-color: #d1d1d1;
}
.dropzone {
    border: 2px dashed #d9d9d9;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
}
.dropzone .dz-message {
    margin: 0;
}
.dropzone .dz-preview .dz-remove {
    font-size: 14px;
    text-decoration: none;
}
</style>