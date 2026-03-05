$(document).ready(function() {
    // Initialize withdrawals table
    const withdrawalsTable = $('#withdrawalsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "ajax/withdrawals.php",
            type: "POST",
            data: function(d) {
                d.csrf_token = $('meta[name="csrf-token"]').attr('content');
            },
            error: function(xhr) {
                $('#withdrawalError').text(xhr.status === 401 ? 
                    'Session expired. Please login again.' : 
                    'Failed to load withdrawals. Please try again.')
                .removeClass('d-none');
            }
        },
        columns: [
            { 
                data: "reference",
                render: function(data) {
                    return data || 'N/A';
                }
            },
            { 
                data: "amount",
                render: function(data) {
                    return data ? '$' + parseFloat(data).toFixed(2) : '$0.00';
                }
            },
            { 
                data: "method_code",
                render: function(data) {
                    const methodNames = {
                        'bank_transfer': 'Bank Transfer',
                        'paypal': 'PayPal',
                        'bitcoin': 'Bitcoin',
                        'ethereum': 'Ethereum',
                        'credit_card': 'Credit Card'
                    };
                    return methodNames[data] || data;
                }
            },
            { 
                data: "status",
                render: function(data) {
                    if (!data) return '';
                    const statusClass = 'badge-' + data.toLowerCase();
                    return `<span class="badge ${statusClass} withdrawal-status-badge">${data}</span>`;
                }
            },
            { 
                data: "created_at",
                render: function(data) {
                    return data ? new Date(data).toLocaleString() : '';
                }
            },
            {
                data: "id",
                render: function(data, type, row) {
                    let buttons = '';
                    if (row.status.toLowerCase() === 'pending') {
                        buttons += `<button class="btn btn-sm btn-danger cancel-withdrawal mr-1" data-id="${data}">
                                  <i class="anticon anticon-close"></i> Cancel
                                  </button>`;
                    }
                    buttons += `<button class="btn btn-sm btn-primary view-withdrawal" data-id="${data}">
                              <i class="anticon anticon-eye"></i> View
                              </button>`;
                    return buttons;
                },
                orderable: false
            }
        ],
        responsive: true,
        order: [[4, 'desc']],
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>',
            emptyTable: "No withdrawal requests found",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            lengthMenu: "Show _MENU_ entries"
        }
    });

    // Refresh button
    $('#refreshWithdrawals').click(function() {
        $('#withdrawalError').addClass('d-none');
        withdrawalsTable.ajax.reload();
        
        // Refresh balance
        $.get('ajax/get-balance.php', { 
            csrf_token: $('meta[name="csrf-token"]').attr('content') 
        }, function(response) {
            if (response.success) {
                $('#currentBalance, #currentBalanceDisplay').text('$' + response.balance);
            }
        });
    });

    // Withdrawal form submission
    $('#withdrawalForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const $submitBtn = $(this).find('button[type="submit"]');
        
        $submitBtn.prop('disabled', true)
            .html('<i class="anticon anticon-loading anticon-spin"></i> Processing...');
        
        $.ajax({
            url: 'ajax/process-withdrawal.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#newWithdrawalModal').modal('hide');
                    withdrawalsTable.ajax.reload();
                    $('#withdrawalForm')[0].reset();
                    $('#currentBalance, #currentBalanceDisplay').text('$' + response.data.new_balance);
                } else {
                    toastr.error(response.message);
                }
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html('Submit Request');
            },
            error: function(xhr) {
                let errorMsg = 'Failed to process withdrawal.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMsg = response.message;
                    }
                    if (xhr.status === 401) {
                        window.location.reload();
                        return;
                    }
                } catch (e) {
                    console.error('Error parsing error response:', e);
                }
                toastr.error(errorMsg);
            }
        });
    });

    // Cancel withdrawal
    $('#withdrawalsTable').on('click', '.cancel-withdrawal', function() {
        const withdrawalId = $(this).data('id');
        const $button = $(this);
        
        if (confirm('Are you sure you want to cancel this withdrawal request?')) {
            $button.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Processing...');
            
            $.ajax({
                url: 'ajax/cancel-withdrawal.php',
                type: 'POST',
                data: { 
                    id: withdrawalId,
                    csrf_token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        withdrawalsTable.ajax.reload();
                        $('#currentBalance, #currentBalanceDisplay').text('$' + response.new_balance);
                    } else {
                        toastr.error(response.message);
                        $button.prop('disabled', false).html('<i class="anticon anticon-close"></i> Cancel');
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.status === 401 ? 'Session expired. Please login again.' : 'Failed to cancel withdrawal.');
                    $button.prop('disabled', false).html('<i class="anticon anticon-close"></i> Cancel');
                }
            });
        }
    });

    // View withdrawal details
    $('#withdrawalsTable').on('click', '.view-withdrawal', function() {
        const withdrawalId = $(this).data('id');
        const $modal = $('#withdrawalDetailsModal');
        const $modalContent = $('#withdrawalDetailsContent');
        
        $modalContent.html(`
            <div class="text-center p-4">
                <i class="anticon anticon-loading anticon-spin" style="font-size:24px"></i>
                <p>Loading withdrawal details...</p>
            </div>
        `);
        
        $modal.modal('show');
        
        $.get('ajax/get-withdrawal.php', { 
            id: withdrawalId,
            csrf_token: $('meta[name="csrf-token"]').attr('content')
        }, function(response) {
            if (response.success) {
                const withdrawal = response.withdrawal;
                const methodNames = {
                    'bank_transfer': 'Bank Transfer',
                    'paypal': 'PayPal',
                    'bitcoin': 'Bitcoin',
                    'ethereum': 'Ethereum',
                    'credit_card': 'Credit Card'
                };
                const methodName = methodNames[withdrawal.method_code] || withdrawal.method_code;
                const statusClass = 'badge-' + withdrawal.status.toLowerCase();
                
                $modalContent.html(`
                    <div class="modal-header">
                        <h5 class="modal-title">Withdrawal #${withdrawal.id}</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <i class="anticon anticon-close"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Amount:</strong> $${parseFloat(withdrawal.amount).toFixed(2)}</p>
                                <p><strong>Method:</strong> ${methodName}</p>
                                <p><strong>Status:</strong> <span class="badge ${statusClass} withdrawal-status-badge">${withdrawal.status}</span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Reference:</strong> ${withdrawal.reference}</p>
                                <p><strong>Request Date:</strong> ${new Date(withdrawal.created_at).toLocaleString()}</p>
                                ${withdrawal.updated_at ? `<p><strong>Last Update:</strong> ${new Date(withdrawal.updated_at).toLocaleString()}</p>` : ''}
                            </div>
                        </div>
                        <div class="form-group">
                            <label><strong>Payment Details:</strong></label>
                            <textarea class="form-control" rows="3" readonly>${withdrawal.payment_details}</textarea>
                        </div>
                        ${withdrawal.admin_notes ? `
                        <div class="form-group">
                            <label><strong>Admin Notes:</strong></label>
                            <textarea class="form-control" rows="2" readonly>${withdrawal.admin_notes}</textarea>
                        </div>
                        ` : ''}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        ${withdrawal.status.toLowerCase() === 'pending' ? `
                        <button type="button" class="btn btn-danger cancel-withdrawal-modal" data-id="${withdrawal.id}">
                            <i class="anticon anticon-close"></i> Cancel Withdrawal
                        </button>
                        ` : ''}
                    </div>
                `);
            } else {
                $modalContent.html(`
                    <div class="alert alert-danger">
                        ${response.message || 'Failed to load withdrawal details'}
                    </div>
                `);
            }
        }).fail(function() {
            $modalContent.html(`
                <div class="alert alert-danger">
                    Failed to load withdrawal details. Please try again.
                </div>
            `);
        });
    });

    // Cancel withdrawal from modal
    $(document).on('click', '.cancel-withdrawal-modal', function() {
        const withdrawalId = $(this).data('id');
        const $modal = $('#withdrawalDetailsModal');
        const $button = $(this);
        
        if (confirm('Are you sure you want to cancel this withdrawal request?')) {
            $button.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Processing...');
            
            $.ajax({
                url: 'ajax/cancel-withdrawal.php',
                type: 'POST',
                data: { 
                    id: withdrawalId,
                    csrf_token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $modal.modal('hide');
                        withdrawalsTable.ajax.reload();
                        $('#currentBalance, #currentBalanceDisplay').text('$' + response.new_balance);
                    } else {
                        toastr.error(response.message);
                        $button.prop('disabled', false).html('<i class="anticon anticon-close"></i> Cancel Withdrawal');
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.status === 401 ? 'Session expired. Please login again.' : 'Failed to cancel withdrawal.');
                    $button.prop('disabled', false).html('<i class="anticon anticon-close"></i> Cancel Withdrawal');
                }
            });
        }
    });

    // Update form when modal is shown
    $('#newWithdrawalModal').on('show.bs.modal', function() {
        $('#withdrawalForm')[0].reset();
        $('#currentBalanceDisplay').text($('#currentBalance').text());
    });
});