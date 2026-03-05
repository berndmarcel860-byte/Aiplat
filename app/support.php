<?php
require_once 'config.php';
require_once 'header.php';

// Handle ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $category = trim($_POST['category']);
    $priority = $_POST['priority'] ?? 'medium';
    
    // Generate ticket number
    $ticket_number = 'TICKET-' . strtoupper(uniqid());
    
    try {
        $stmt = $pdo->prepare("INSERT INTO support_tickets 
                              (user_id, ticket_number, subject, message, category, priority) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $ticket_number, $subject, $message, $category, $priority]);
        
        $_SESSION['success'] = "Ticket submitted successfully! Your ticket number is: $ticket_number";
        header("Location: support.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error submitting ticket: " . $e->getMessage();
    }
}

// Get user's tickets
$tickets = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching tickets: " . $e->getMessage();
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
                            <i class="anticon anticon-customer-service mr-2"></i>Support Center
                        </h2>
                        <p class="mb-0" style="color: rgba(255,255,255,0.9); font-size: 15px;">
                            Get help from our dedicated support team
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                            <i class="anticon anticon-info-circle mr-2" style="color: var(--brand);"></i>Support Information
                        </h5>
                        <ul class="list-unstyled" style="line-height: 2.2;">
                            <li class="d-flex align-items-center mb-2">
                                <div class="mr-3" style="width: 40px; height: 40px; background: rgba(41, 80, 168, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="anticon anticon-mail" style="color: var(--brand); font-size: 18px;"></i>
                                </div>
                                <div>
                                    <div class="text-muted" style="font-size: 12px;">Email</div>
                                    <strong>support@kryptox.co.uk</strong>
                                </div>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <div class="mr-3" style="width: 40px; height: 40px; background: rgba(41, 80, 168, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="anticon anticon-clock-circle" style="color: var(--brand); font-size: 18px;"></i>
                                </div>
                                <div>
                                    <div class="text-muted" style="font-size: 12px;">Hours</div>
                                    <strong>Mon-Fri 24/7</strong>
                                </div>
                            </li>
                        </ul>
                        <div class="alert alert-info border-0 mt-4" style="border-radius: 10px;">
                            <i class="anticon anticon-exclamation-circle mr-2"></i>
                            For urgent matters, please create a ticket with <strong>"Critical"</strong> priority.
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="mb-3" style="color: #2c3e50; font-weight: 600;">
                            <i class="anticon anticon-plus-circle mr-2" style="color: var(--brand);"></i>Create New Ticket
                        </h5>
                        <form method="POST" id="ticketForm">
                            <div class="form-group">
                                <label class="font-weight-500">Subject</label>
                                <input type="text" class="form-control" name="subject" required placeholder="Brief description of your issue" style="border-radius: 8px;">
                            </div>
                            <div class="form-group">
                                <label class="font-weight-500">Category</label>
                                <select class="form-control" name="category" required style="border-radius: 8px;">
                                    <option value="">Select category</option>
                                    <option value="Case Inquiry">📁 Case Inquiry</option>
                                    <option value="Document Submission">📄 Document Submission</option>
                                    <option value="Payment Issue">💳 Payment Issue</option>
                                    <option value="Technical Problem">⚙️ Technical Problem</option>
                                    <option value="Other">💬 Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-500">Priority</label>
                                <select class="form-control" name="priority" style="border-radius: 8px;">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-500">Message</label>
                                <textarea class="form-control" rows="5" name="message" required placeholder="Describe your issue in detail..." style="border-radius: 8px;"></textarea>
                            </div>
                            <button type="submit" name="submit_ticket" class="btn btn-primary btn-block" style="border-radius: 8px;">
                                <i class="anticon anticon-plus-circle mr-1"></i> Submit Ticket
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tickets Section -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0" style="color: #2c3e50; font-weight: 600;">
                                <i class="anticon anticon-file-text mr-2" style="color: var(--brand);"></i>Your Support Tickets
                            </h5>
                            <button class="btn btn-outline-primary btn-sm" id="refreshTickets">
                                <i class="anticon anticon-reload mr-1"></i> Refresh
                            </button>
                        </div>
                        
                        <?php if (empty($tickets)): ?>
                            <div class="alert alert-info border-0 d-flex align-items-center" style="border-radius: 10px;">
                                <i class="anticon anticon-info-circle mr-2" style="font-size: 20px;"></i>
                                <span>No support tickets found. Create your first ticket above!</span>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="ticketsTable">
                                    <thead>
                                        <tr>
                                            <th>Ticket #</th>
                                            <th>Subject</th>
                                            <th>Category</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Last Reply</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($ticket['ticket_number']) ?></strong></td>
                                            <td><?= htmlspecialchars($ticket['subject']) ?></td>
                                            <td><?= htmlspecialchars($ticket['category']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= 
                                                    $ticket['priority'] == 'low' ? 'info' : 
                                                    ($ticket['priority'] == 'medium' ? 'warning' : 
                                                    ($ticket['priority'] == 'high' ? 'danger' : 'dark')) 
                                                ?>" style="font-size: 11px;">
                                                    <?= ucfirst($ticket['priority']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= 
                                                    $ticket['status'] == 'open' ? 'primary' : 
                                                    ($ticket['status'] == 'in_progress' ? 'warning' :
                                                    ($ticket['status'] == 'resolved' ? 'success' : 'secondary'))
                                                ?>" style="font-size: 11px;">
                                                    <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                                                </span>
                                            </td>
                                            <td><?= $ticket['last_reply_at'] ? date('M d, Y H:i', strtotime($ticket['last_reply_at'])) : '-' ?></td>
                                            <td><?= date('M d, Y H:i', strtotime($ticket['created_at'])) ?></td>
                                            <td>
                                                <div class="d-flex" style="gap: 5px;">
                                                    <button class="btn btn-sm btn-outline-info view-ticket" 
                                                            data-id="<?= $ticket['id'] ?>" 
                                                            data-ticket="<?= htmlspecialchars($ticket['ticket_number']) ?>"
                                                            title="View ticket">
                                                        <i class="anticon anticon-eye"></i>
                                                    </button>
                                                    <?php if ($ticket['status'] != 'closed'): ?>
                                                    <button class="btn btn-sm btn-outline-primary reply-ticket" 
                                                            data-id="<?= $ticket['id'] ?>"
                                                            data-ticket="<?= htmlspecialchars($ticket['ticket_number']) ?>"
                                                            title="Reply to ticket">
                                                        <i class="anticon anticon-message"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Ticket Modal -->
<div class="modal fade" id="viewTicketModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ticket Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="ticketDetails">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p>Loading ticket details...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="replyFromView" style="display: none;">
                    <i class="anticon anticon-message"></i> Reply
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reply Modal -->
<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reply to Ticket</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="replyForm">
                <div class="modal-body">
                    <input type="hidden" name="ticket_id" id="reply_ticket_id">
                    <div class="alert alert-info">
                        <strong>Ticket #:</strong> <span id="reply_ticket_number"></span>
                    </div>
                    <div class="form-group">
                        <label>Your Reply</label>
                        <textarea class="form-control" name="message" rows="6" required placeholder="Type your reply message here..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Attach Files (Optional)</label>
                        <input type="file" class="form-control-file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <small class="form-text text-muted">You can attach multiple files. Max 10MB per file.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="anticon anticon-send"></i> Send Reply
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

<script>
$(document).ready(function() {
    let currentTicketId = null;
    
    // View ticket handler
    $(document).on('click', '.view-ticket', function() {
        const ticketId = $(this).data('id');
        const ticketNumber = $(this).data('ticket');
        currentTicketId = ticketId;
        
        // Show loading
        $('#ticketDetails').html(`
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p>Loading ticket details...</p>
            </div>
        `);
        
        $('#viewTicketModal').modal('show');
        
        // Load ticket details
        $.get('ajax/get_ticket_details.php', { id: ticketId })
        .done(function(response) {
            if (response.success) {
                const ticket = response.data.ticket;
                const replies = response.data.replies || [];
                
                let repliesHtml = '';
                replies.forEach(function(reply) {
                    const isAdmin = reply.admin_id ? true : false;
                    const senderName = isAdmin ? reply.admin_name : 'You';
                    const cardClass = isAdmin ? 'border-primary' : 'border-info';
                    const headerClass = isAdmin ? 'bg-primary text-white' : 'bg-info text-white';
                    
                    repliesHtml += `
                        <div class="card mb-3 ${cardClass}">
                            <div class="card-header ${headerClass}">
                                <div class="d-flex justify-content-between">
                                    <strong>${senderName}</strong>
                                    <small>${new Date(reply.created_at).toLocaleString()}</small>
                                </div>
                            </div>
                            <div class="card-body">
                                ${reply.message.replace(/\n/g, '<br>')}
                                ${reply.attachments ? getAttachmentsHtml(reply.attachments) : ''}
                            </div>
                        </div>
                    `;
                });
                
                $('#ticketDetails').html(`
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Ticket Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Ticket #:</strong></td><td>${ticket.ticket_number}</td></tr>
                                <tr><td><strong>Subject:</strong></td><td>${ticket.subject}</td></tr>
                                <tr><td><strong>Category:</strong></td><td>${ticket.category}</td></tr>
                                <tr><td><strong>Priority:</strong></td><td><span class="badge badge-${getPriorityClass(ticket.priority)}">${ticket.priority.toUpperCase()}</span></td></tr>
                                <tr><td><strong>Status:</strong></td><td><span class="badge badge-${getStatusClass(ticket.status)}">${ticket.status.replace('_', ' ').toUpperCase()}</span></td></tr>
                                <tr><td><strong>Created:</strong></td><td>${new Date(ticket.created_at).toLocaleString()}</td></tr>
                                ${ticket.last_reply_at ? `<tr><td><strong>Last Reply:</strong></td><td>${new Date(ticket.last_reply_at).toLocaleString()}</td></tr>` : ''}
                            </table>
                        </div>
                        <div class="col-md-8">
                            <h6>Original Message</h6>
                            <div class="card mb-3">
                                <div class="card-body">
                                    ${ticket.message.replace(/\n/g, '<br>')}
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h6>Conversation History</h6>
                    <div style="max-height: 500px; overflow-y: auto;">
                        ${repliesHtml || '<p class="text-muted">No replies yet.</p>'}
                    </div>
                `);
                
                // Show reply button if ticket is not closed
                if (ticket.status !== 'closed') {
                    $('#replyFromView').show();
                } else {
                    $('#replyFromView').hide();
                }
            } else {
                $('#ticketDetails').html('<div class="alert alert-danger">Error loading ticket details</div>');
            }
        })
        .fail(function() {
            $('#ticketDetails').html('<div class="alert alert-danger">Failed to load ticket details</div>');
        });
    });
    
    // Reply button handlers
    $(document).on('click', '.reply-ticket', function() {
        const ticketId = $(this).data('id');
        const ticketNumber = $(this).data('ticket');
        openReplyModal(ticketId, ticketNumber);
    });
    
    $('#replyFromView').click(function() {
        if (currentTicketId) {
            const ticketNumber = $('#ticketDetails').find('td').first().next().text();
            openReplyModal(currentTicketId, ticketNumber);
        }
    });
    
    function openReplyModal(ticketId, ticketNumber) {
        $('#reply_ticket_id').val(ticketId);
        $('#reply_ticket_number').text(ticketNumber);
        $('#replyModal').modal('show');
    }
    
    // Reply form submission
    $('#replyForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: 'ajax/reply_ticket.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#replyModal').modal('hide');
                    $('#replyForm')[0].reset();
                    
                    // Show success message
                    toastr.success('Reply sent successfully');
                    
                    // Refresh the tickets table
                    location.reload();
                } else {
                    toastr.error(response.message || 'Failed to send reply');
                }
            },
            error: function() {
                toastr.error('Failed to send reply');
            }
        });
    });
    
    // Refresh tickets
    $('#refreshTickets').click(function() {
        location.reload();
    });
    
    // Helper functions
    function getPriorityClass(priority) {
        const classes = {
            'low': 'info',
            'medium': 'warning',
            'high': 'danger',
            'critical': 'dark'
        };
        return classes[priority] || 'secondary';
    }
    
    function getStatusClass(status) {
        const classes = {
            'open': 'primary',
            'in_progress': 'warning',
            'resolved': 'success',
            'closed': 'secondary'
        };
        return classes[status] || 'secondary';
    }
    
    function getAttachmentsHtml(attachments) {
        if (!attachments) return '';
        
        try {
            const files = JSON.parse(attachments);
            if (!files.length) return '';
            
            let html = '<div class="mt-2"><small class="text-muted">Attachments:</small><br>';
            files.forEach(function(file) {
                html += `<a href="uploads/tickets/${file}" target="_blank" class="btn btn-sm btn-outline-info mr-1 mt-1">
                    <i class="anticon anticon-paper-clip"></i> ${file}
                </a>`;
            });
            html += '</div>';
            return html;
        } catch (e) {
            return '';
        }
    }
});
</script>