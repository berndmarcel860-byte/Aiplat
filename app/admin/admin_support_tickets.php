<?php
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2>Support Tickets</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Support Tickets</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Support Tickets</h5>
                <div class="d-flex">
                    <button class="btn btn-info mr-2" id="refreshTickets">
                        <i class="anticon anticon-reload"></i> Refresh
                    </button>
                    <div class="btn-group mr-2">
                        <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown">
                            Filter Status
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item filter-status" data-status="">All Statuses</a>
                            <a class="dropdown-item filter-status" data-status="open">Open</a>
                            <a class="dropdown-item filter-status" data-status="in_progress">In Progress</a>
                            <a class="dropdown-item filter-status" data-status="resolved">Resolved</a>
                            <a class="dropdown-item filter-status" data-status="closed">Closed</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="ticketsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ticket #</th>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Category</th>
                            <th>Last Reply</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
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
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="replyTicketBtn">Reply</button>
                <button type="button" class="btn btn-success" id="resolveTicketBtn">Mark Resolved</button>
                <button type="button" class="btn btn-secondary" id="closeTicketBtn">Close Ticket</button>
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
<input type="hidden" name="csrf_token" value="<?= $_SESSION['admin_csrf_token'] ?>">

                    <input type="hidden" name="ticket_id" id="reply_ticket_id">
                    <div class="form-group">
                        <label>Reply Message</label>
                        <textarea class="form-control" name="message" rows="6" required placeholder="Type your reply..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Update Status</label>
                        <select class="form-control" name="new_status">
                            <option value="">Keep Current Status</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    let currentStatusFilter = '';
    let currentTicketId = null;
    
    const ticketsTable = $('#ticketsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_support_tickets.php',
            type: 'POST',
            data: function(d) {
                d.status_filter = currentStatusFilter;
            }
        },
        order: [[8, 'desc']],
        columns: [
            { data: 'id' },
            { data: 'ticket_number' },
            { data: 'user_name' },
            { 
                data: 'subject',
                render: function(data) {
                    return data.length > 50 ? data.substring(0, 50) + '...' : data;
                }
            },
            { 
                data: 'priority',
                render: function(data) {
                    const priorityClass = {
                        'low': 'info',
                        'medium': 'warning',
                        'high': 'danger',
                        'critical': 'dark'
                    };
                    return `<span class="badge badge-${priorityClass[data] || 'secondary'}">${data.toUpperCase()}</span>`;
                }
            },
            { 
                data: 'status',
                render: function(data) {
                    const statusClass = {
                        'open': 'primary',
                        'in_progress': 'warning',
                        'resolved': 'success',
                        'closed': 'secondary'
                    };
                    return `<span class="badge badge-${statusClass[data] || 'secondary'}">${data.replace('_', ' ').toUpperCase()}</span>`;
                }
            },
            { data: 'category' },
            { 
                data: 'last_reply_at',
                render: function(data) {
                    return data ? new Date(data).toLocaleString() : '-';
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleString();
                }
            },
            {
                data: 'id',
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info view-ticket" 
                                    data-id="${data}" 
                                    title="View Ticket">
                                <i class="anticon anticon-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-primary reply-ticket" 
                                    data-id="${data}" 
                                    title="Reply">
                                <i class="anticon anticon-message"></i>
                            </button>
                            <button class="btn btn-sm btn-success close-ticket" 
                                    data-id="${data}" 
                                    title="Close Ticket">
                                <i class="anticon anticon-check"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });
    
    // Filter status handlers
    $('.filter-status').click(function(e) {
        e.preventDefault();
        currentStatusFilter = $(this).data('status');
        ticketsTable.ajax.reload();
    });
    
    // Refresh button
    $('#refreshTickets').click(function() {
        ticketsTable.ajax.reload();
    });
    
    // View ticket handler
    $(document).on('click', '.view-ticket', function() {
        const ticketId = $(this).data('id');
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
        $.get('admin_ajax/get_ticket_details.php', { id: ticketId })
        .done(function(response) {
            if (response.success) {
                const ticket = response.data.ticket;
                const replies = response.data.replies || [];
                
                let repliesHtml = '';
                replies.forEach(function(reply) {
                    const isAdmin = reply.admin_id ? true : false;
                    repliesHtml += `
                        <div class="card mb-3 ${isAdmin ? 'border-primary' : 'border-secondary'}">
                            <div class="card-header ${isAdmin ? 'bg-primary text-white' : 'bg-light'}">
                                <div class="d-flex justify-content-between">
                                    <strong>${isAdmin ? reply.admin_name : reply.user_name}</strong>
                                    <small>${new Date(reply.created_at).toLocaleString()}</small>
                                </div>
                            </div>
                            <div class="card-body">
                                ${reply.message.replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    `;
                });
                
                $('#ticketDetails').html(`
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Ticket Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Ticket #:</strong></td><td>${ticket.ticket_number}</td></tr>
                                <tr><td><strong>User:</strong></td><td>${ticket.user_name} (${ticket.user_email})</td></tr>
                                <tr><td><strong>Subject:</strong></td><td>${ticket.subject}</td></tr>
                                <tr><td><strong>Priority:</strong></td><td><span class="badge badge-${getPriorityClass(ticket.priority)}">${ticket.priority.toUpperCase()}</span></td></tr>
                                <tr><td><strong>Status:</strong></td><td><span class="badge badge-${getStatusClass(ticket.status)}">${ticket.status.replace('_', ' ').toUpperCase()}</span></td></tr>
                                <tr><td><strong>Category:</strong></td><td>${ticket.category || 'N/A'}</td></tr>
                                <tr><td><strong>Created:</strong></td><td>${new Date(ticket.created_at).toLocaleString()}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Original Message</h6>
                            <div class="card">
                                <div class="card-body">
                                    ${ticket.message.replace(/\n/g, '<br>')}
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h6>Conversation History</h6>
                    <div style="max-height: 400px; overflow-y: auto;">
                        ${repliesHtml || '<p class="text-muted">No replies yet.</p>'}
                    </div>
                `);
            } else {
                $('#ticketDetails').html('<div class="alert alert-danger">Error loading ticket details</div>');
            }
        })
        .fail(function() {
            $('#ticketDetails').html('<div class="alert alert-danger">Failed to load ticket details</div>');
        });
    });
    
    // Reply button handler
    $(document).on('click', '.reply-ticket', function() {
        const ticketId = $(this).data('id');
        $('#reply_ticket_id').val(ticketId);
        $('#replyModal').modal('show');
    });
    
    $('#replyTicketBtn').click(function() {
        if (currentTicketId) {
            $('#reply_ticket_id').val(currentTicketId);
            $('#replyModal').modal('show');
        }
    });
    
    // Reply form submission
    $('#replyForm').submit(function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.post('admin_ajax/reply_ticket.php', formData)
        .done(function(response) {
            if (response.success) {
                $('#replyModal').modal('hide');
                $('#replyForm')[0].reset();
                ticketsTable.ajax.reload();
                toastr.success('Reply sent successfully');
                
                // Reload ticket details if modal is open
                if (currentTicketId && $('#viewTicketModal').hasClass('show')) {
                    $('.view-ticket[data-id="' + currentTicketId + '"]').click();
                }
            } else {
                toastr.error(response.message || 'Failed to send reply');
            }
        })
        .fail(function() {
            toastr.error('Failed to send reply');
        });
    });
    
    // Close ticket handler
    $(document).on('click', '.close-ticket', function() {
        const ticketId = $(this).data('id');
        
        if (confirm('Are you sure you want to close this ticket?')) {
            $.post('admin_ajax/update_ticket_status.php', { 
                ticket_id: ticketId, 
                status: 'closed' 
            })
            .done(function(response) {
                if (response.success) {
                    ticketsTable.ajax.reload();
                    toastr.success('Ticket closed successfully');
                } else {
                    toastr.error(response.message || 'Failed to close ticket');
                }
            })
            .fail(function() {
                toastr.error('Failed to close ticket');
            });
        }
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
});
</script>