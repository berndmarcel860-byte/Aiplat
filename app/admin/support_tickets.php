<?php
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Support Tickets</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Support Tickets</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5>All Support Tickets</h5>
                <div class="btn-group">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#filterTicketsModal">
                        <i class="anticon anticon-filter"></i> Filter
                    </button>
                </div>
            </div>
            
            <div class="m-t-25">
                <table id="ticketsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Created</th>
                            <th>Last Reply</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- AJAX will populate this -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterTicketsModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filter Tickets</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="filterTicketsForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status">
                            <option value="">All Statuses</option>
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <select class="form-control" name="priority">
                            <option value="">All Priorities</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select class="form-control" name="category">
                            <option value="">All Categories</option>
                            <option value="Case Inquiry">Case Inquiry</option>
                            <option value="Technical Problem">Technical Problem</option>
                            <option value="Payment Issue">Payment Issue</option>
                            <option value="Account Help">Account Help</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date Range</label>
                        <div class="input-daterange input-group" data-provide="datepicker">
                            <input type="text" class="form-control" name="start_date">
                            <span class="input-group-addon">to</span>
                            <input type="text" class="form-control" name="end_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Ticket Details Modal -->
<div class="modal fade" id="ticketDetailsModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ticket Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body" id="ticketDetailsContent">
                <!-- AJAX will populate this -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="replyToTicketBtn">Reply</button>
                <button type="button" class="btn btn-success" id="resolveTicketBtn">Resolve</button>
                <button type="button" class="btn btn-danger" id="closeTicketBtn">Close</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const ticketsTable = $('#ticketsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_tickets.php',
            type: 'POST'
        },
        columns: [
            { data: 'ticket_number' },
            { 
                data: null,
                render: function(data) {
                    return data.user_first_name + ' ' + data.user_last_name;
                }
            },
            { data: 'subject' },
            { 
                data: 'status',
                render: function(data) {
                    const statusClass = {
                        'open': 'primary',
                        'in_progress': 'info',
                        'resolved': 'success',
                        'closed': 'secondary'
                    }[data] || 'light';
                    return `<span class="badge badge-${statusClass}">${data.replace('_', ' ')}</span>`;
                }
            },
            { 
                data: 'priority',
                render: function(data) {
                    const priorityClass = {
                        'low': 'info',
                        'medium': 'primary',
                        'high': 'warning',
                        'critical': 'danger'
                    }[data] || 'light';
                    return `<span class="badge badge-${priorityClass}">${data}</span>`;
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleDateString();
                }
            },
            { 
                data: 'last_reply_at',
                render: function(data) {
                    return data ? new Date(data).toLocaleDateString() : 'No replies';
                }
            },
            {
                data: 'id',
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button class="btn btn-sm btn-primary view-ticket" data-id="${data}" title="View">
                                <i class="anticon anticon-eye"></i>
                            </button>
                            <a href="ticket_reply.php?id=${data}" class="btn btn-sm btn-info" title="Reply">
                                <i class="anticon anticon-message"></i>
                            </a>
                            ${row.status !== 'closed' ? `
                                <button class="btn btn-sm btn-success resolve-ticket" data-id="${data}" title="Resolve">
                                    <i class="anticon anticon-check"></i>
                                </button>
                                <button class="btn btn-sm btn-danger close-ticket" data-id="${data}" title="Close">
                                    <i class="anticon anticon-close"></i>
                                </button>
                            ` : ''}
                        </div>
                    `;
                }
            }
        ],
        order: [[5, 'desc']] // Sort by created_at descending by default
    });

    // Current ticket ID for actions
    let currentTicketId = null;

    // View Ticket Details
    $('#ticketsTable').on('click', '.view-ticket', function() {
        currentTicketId = $(this).data('id');
        
        $.ajax({
            url: 'admin_ajax/get_ticket.php',
            type: 'GET',
            data: { id: currentTicketId },
            success: function(response) {
                if (response.success) {
                    const ticket = response.ticket;
                    const user = response.user;
                    
                    // Build ticket details HTML
                    let detailsHtml = `
                        <div class="ticket-header mb-4">
                            <h3>${ticket.subject}</h3>
                            <div class="d-flex align-items-center mt-2">
                                <span class="badge badge-${ticket.status === 'open' ? 'primary' : 
                                    ticket.status === 'in_progress' ? 'info' : 
                                    ticket.status === 'resolved' ? 'success' : 'secondary'} mr-2">
                                    ${ticket.status.replace('_', ' ')}
                                </span>
                                <span class="badge badge-${ticket.priority === 'low' ? 'info' : 
                                    ticket.priority === 'medium' ? 'primary' : 
                                    ticket.priority === 'high' ? 'warning' : 'danger'} mr-2">
                                    ${ticket.priority}
                                </span>
                                <span class="text-muted">Created: ${new Date(ticket.created_at).toLocaleString()}</span>
                            </div>
                            <div class="mt-2">
                                <strong>User:</strong> ${user.first_name} ${user.last_name} (${user.email})
                            </div>
                            <div>
                                <strong>Category:</strong> ${ticket.category || 'Not specified'}
                            </div>
                        </div>
                        
                        <div class="conversation-thread mb-4">
                            <h5>Conversation</h5>`;
                    
                    // Add each message to the conversation
                    response.conversation.forEach(msg => {
                        const isAdmin = msg.admin_id !== null;
                        const senderName = isAdmin ? 'Admin' : `${user.first_name} ${user.last_name}`;
                        
                        detailsHtml += `
                            <div class="message ${isAdmin ? 'admin-message' : 'user-message'} mb-4 p-3 border rounded">
                                <div class="message-header d-flex justify-content-between mb-2">
                                    <div>
                                        <strong>${senderName}</strong>
                                        ${isAdmin ? '<span class="badge badge-primary ml-2">Staff</span>' : ''}
                                    </div>
                                    <small class="text-muted">${new Date(msg.created_at).toLocaleString()}</small>
                                </div>
                                <div class="message-body">
                                    <p>${msg.message.replace(/\n/g, '<br>')}</p>`;
                                    
                        if (msg.attachments) {
                            detailsHtml += `<div class="attachments mt-2">
                                <strong>Attachments:</strong><br>`;
                            
                            JSON.parse(msg.attachments).forEach(attachment => {
                                detailsHtml += `<a href="${attachment}" target="_blank" class="d-block">
                                    <i class="anticon anticon-paper-clip"></i> ${attachment.split('/').pop()}
                                </a>`;
                            });
                            
                            detailsHtml += `</div>`;
                        }
                        
                        detailsHtml += `</div></div>`;
                    });
                    
                    detailsHtml += `</div>`;
                    
                    $('#ticketDetailsContent').html(detailsHtml);
                    
                    // Show/hide action buttons based on status
                    if (ticket.status === 'closed') {
                        $('#resolveTicketBtn, #closeTicketBtn').hide();
                    } else {
                        $('#resolveTicketBtn, #closeTicketBtn').show();
                    }
                    
                    $('#ticketDetailsModal').modal('show');
                } else {
                    toastr.error(response.message);
                }
            }
        });
    });

    // Reply to Ticket button
    $('#replyToTicketBtn').click(function() {
        window.location.href = `ticket_reply.php?id=${currentTicketId}`;
    });

    // Resolve Ticket
    $('#resolveTicketBtn').click(function() {
        if (confirm('Are you sure you want to mark this ticket as resolved?')) {
            updateTicketStatus(currentTicketId, 'resolved');
        }
    });

    // Close Ticket
    $('#closeTicketBtn').click(function() {
        if (confirm('Are you sure you want to close this ticket?')) {
            updateTicketStatus(currentTicketId, 'closed');
        }
    });

    // Resolve Ticket from table
    $('#ticketsTable').on('click', '.resolve-ticket', function() {
        const ticketId = $(this).data('id');
        if (confirm('Are you sure you want to mark this ticket as resolved?')) {
            updateTicketStatus(ticketId, 'resolved');
        }
    });

    // Close Ticket from table
    $('#ticketsTable').on('click', '.close-ticket', function() {
        const ticketId = $(this).data('id');
        if (confirm('Are you sure you want to close this ticket?')) {
            updateTicketStatus(ticketId, 'closed');
        }
    });

    // Function to update ticket status
    function updateTicketStatus(ticketId, status) {
        $.ajax({
            url: 'admin_ajax/update_ticket_status.php',
            type: 'POST',
            data: { 
                id: ticketId,
                status: status,
                csrf_token: '<?= $_SESSION['admin_csrf_token'] ?>'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    ticketsTable.ajax.reload();
                    $('#ticketDetailsModal').modal('hide');
                } else {
                    toastr.error(response.message);
                }
            }
        });
    }

    // Apply Filters
    $('#filterTicketsForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        ticketsTable.ajax.url('admin_ajax/get_tickets.php?' + formData).load();
        $('#filterTicketsModal').modal('hide');
    });
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
.attachments a {
    color: #1890ff;
    text-decoration: none;
}
.attachments a:hover {
    text-decoration: underline;
}
</style>