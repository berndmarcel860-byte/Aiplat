$(document).ready(function() {
    // Initialize withdrawals table
    const withdrawalsTable = $('#withdrawalsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "ajax/withdrawal.php",
            type: "POST",
            data: function(d) {
                d.csrf_token = $('meta[name="csrf-token"]').attr('content');
                return d;
            },
            dataSrc: function(json) {
                if (!json || !json.data) {
                    console.error('Invalid data format:', json);
                    $('#withdrawalError').text('Invalid data received from server').removeClass('d-none');
                    return [];
                }
                return json.data;
            },
            error: function(xhr) {
                let errorMsg = xhr.status === 401 ? 
                    'Session expired. Please login again.' : 
                    'Failed to load withdrawals. Please try again.';
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error) errorMsg = response.error;
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
                
                $('#withdrawalError').text(errorMsg).removeClass('d-none');
            }
        },
        columns: [
            { 
                data: "reference",
                render: function(data) {
                    return data ? `<small class="text-muted">${data}</small>` : 'N/A';
                }
            },
            { 
                data: "amount",
                render: function(data) {
                    return data ? '$' + parseFloat(data).toFixed(2) : '$0.00';
                }
            },
                { 
                data: "method_code", // Changed from "method_code" to "method" to match server response
                render: function(data) {
                    if (!data) return 'N/A';
                    
                    const methodNames = {
                        'bank_transfer': 'Bank Transfer',
                        'paypal': 'PayPal',
                        'bitcoin': 'Bitcoin',
                        'ethereum': 'Ethereum',
                        'credit_card': 'Credit Card',
                        'BANK_TRANSFER': 'Bank Transfer' // Added uppercase version
                    };
                    
                    // Convert to lowercase for case-insensitive matching
                    const methodKey = data.toLowerCase();
                    return methodNames[methodKey] || data;
                }
            },
            { 
                data: "status",
                render: function(data) {
                    if (!data) return '';
                    const statusClass = getStatusClass(data);
                    return `<span class="badge badge-${statusClass}">${data}</span>`;
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
                    if (row.status && row.status.toLowerCase() === 'pending') {
                        buttons += `<button class="btn btn-sm btn-danger cancel-withdrawal mr-1" data-id="${data}">
                                  <i class="anticon anticon-close"></i> Cancel
                                  </button>`;
                    }
                    buttons += `<button class="btn btn-sm btn-primary view-withdrawal" data-id="${data}">
                              <i class="anticon anticon-eye"></i> View
                              </button>`;
                    return `<div class="btn-group">${buttons}</div>`;
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
            infoFiltered: "(filtered from _MAX_ total entries)",
            lengthMenu: "Show _MENU_ entries",
            loadingRecords: "Loading...",
            zeroRecords: "No matching records found"
        }
    });

    // Refresh button with loading state
    $('#refreshWithdrawals').click(function() {
        const $btn = $(this);
        const originalHtml = $btn.html();
        
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Refreshing...');
        $('#withdrawalError').addClass('d-none');
        
        withdrawalsTable.ajax.reload(function() {
            $btn.prop('disabled', false).html(originalHtml);
            toastr.success('Withdrawals refreshed successfully');
        }, false);
        
        // Update balance
        $.get('ajax/get-balance.php', { 
            csrf_token: $('meta[name="csrf-token"]').attr('content') 
        }, function(response) {
            if (response.success) {
                $('#currentBalance, #currentBalanceDisplay').text('$' + parseFloat(response.balance).toFixed(2));
            }
        }).fail(function() {
            toastr.error('Failed to update balance');
        });
    });

    // View withdrawal details
    $('#withdrawalsTable').on('click', '.view-withdrawal', function() {
        const withdrawalId = $(this).data('id');
        showWithdrawalDetailsModal(withdrawalId);
    });

    // Cancel withdrawal from table
    $('#withdrawalsTable').on('click', '.cancel-withdrawal', function() {
        const withdrawalId = $(this).data('id');
        cancelWithdrawal(withdrawalId, $(this));
    });

    // Cancel withdrawal from modal
    $(document).on('click', '.cancel-withdrawal-modal', function() {
        const withdrawalId = $(this).data('id');
        cancelWithdrawal(withdrawalId, $(this));
    });

    // Withdrawal form submission
    $('#withdrawalForm').submit(function(e) {
        e.preventDefault();
        submitWithdrawalForm($(this));
    });

    // Toggle payment details fields based on method
    $('#method').change(function() {
        $('.payment-details-group').addClass('d-none');
        const method = $(this).val();
        
        if (method === 'BANK_TRANSFER') {
            $('#bankTransferFields').removeClass('d-none');
        } else if (method === 'BITCOIN' || method === 'ETHEREUM') {
            $('#cryptoFields').removeClass('d-none');
        }
    });

    // Helper functions
    function getMethodName(methodCode) {
        const methodNames = {
            'BANK_TRANSFER': 'Bank Transfer',
            'PAYPAL': 'PayPal',
            'BITCOIN': 'Bitcoin',
            'ETHEREUM': 'Ethereum',
            'CREDIT_CARD': 'Credit Card'
        };
        return methodNames[methodCode];
    }

    function getPaymentMethodDisplay(row) {
        // First try to get from payment_method object
        if (row.payment_method && row.payment_method.method_name) {
            return row.payment_method.method_name;
        }
        
        // Then try direct method_code
        const methodCode = row.method_code;
        if (methodCode) {
            return getMethodName(methodCode) || methodCode;
        }
        
        // Fallback
        return 'N/A';
    }

    function getStatusClass(status) {
        const statusMap = {
            'pending': 'warning',
            'completed': 'success',
            'failed': 'danger',
            'cancelled': 'secondary',
            'processing': 'info'
        };
        return statusMap[status.toLowerCase()] || 'info';
    }

    function formatPaymentDetails(details) {
        if (!details) return 'No payment details available';
        
        try {
            if (typeof details === 'string' && (details.startsWith('{') || details.startsWith('['))) {
                const parsed = JSON.parse(details);
                if (typeof parsed === 'object') {
                    return Object.entries(parsed)
                        .filter(([key, value]) => value)
                        .map(([key, value]) => `<strong>${key.replace('_', ' ')}:</strong> ${value}`)
                        .join('<br>');
                }
            }
            return details;
        } catch (e) {
            console.error('Error parsing payment details:', e);
            return details;
        }
    }

    function showWithdrawalDetailsModal(withdrawalId) {
        const $modal = $('#withdrawalDetailsModal');
        const $modalContent = $('#withdrawalDetailsContent');
        
        // Show loading state
        $modalContent.html(`
            <div class="modal-header">
                <h5 class="modal-title">Loading Withdrawal Details...</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body text-center p-4">
                <i class="anticon anticon-loading anticon-spin" style="font-size:24px"></i>
                <p>Please wait while we load the details...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        `);
        
        $modal.modal('show');
        
        $.ajax({
            url: 'ajax/get-withdrawal.php',
            type: 'GET',
            data: { 
                id: withdrawalId,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const withdrawal = response.data;
                    const methodName = getPaymentMethodDisplay(withdrawal);
                    const status = withdrawal.status || 'unknown';
                    const statusClass = getStatusClass(status);
                    const paymentDetails = formatPaymentDetails(withdrawal.payment_details);
                    
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
                                    <p><strong>Amount:</strong> $${withdrawal.amount ? parseFloat(withdrawal.amount).toFixed(2) : '0.00'}</p>
                                    <p><strong>Method:</strong> ${methodName}</p>
                                    <p><strong>Status:</strong> <span class="badge badge-${statusClass}">${status}</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Reference:</strong> ${withdrawal.reference || 'N/A'}</p>
                                    <p><strong>Request Date:</strong> ${withdrawal.created_at ? new Date(withdrawal.created_at).toLocaleString() : 'N/A'}</p>
                                    ${withdrawal.updated_at ? `<p><strong>Last Update:</strong> ${new Date(withdrawal.updated_at).toLocaleString()}</p>` : ''}
                                </div>
                            </div>
                            <div class="card mb-3">
                                <div class="card-header">
                                    <strong>Payment Details</strong>
                                </div>
                                <div class="card-body">
                                    ${paymentDetails}
                                </div>
                            </div>
                            ${withdrawal.admin_notes ? `
                            <div class="card">
                                <div class="card-header">
                                    <strong>Admin Notes</strong>
                                </div>
                                <div class="card-body">
                                    ${withdrawal.admin_notes}
                                </div>
                            </div>
                            ` : ''}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            ${status.toLowerCase() === 'pending' ? `
                            <button type="button" class="btn btn-danger cancel-withdrawal-modal" data-id="${withdrawal.id}">
                                <i class="anticon anticon-close"></i> Cancel Withdrawal
                            </button>
                            ` : ''}
                        </div>
                    `);
                } else {
                    showModalError(response.error || 'Failed to load withdrawal details');
                }
            },
            error: function(xhr) {
                showModalError(xhr.status === 401 ? 
                    'Session expired. Please login again.' : 
                    'Failed to load withdrawal details. Please try again.');
            }
        });
    }

    function cancelWithdrawal(withdrawalId, $button) {
        if (confirm('Are you sure you want to cancel this withdrawal request?')) {
            const originalHtml = $button.html();
            $button.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Processing...');
            
            $.ajax({
                url: 'ajax/cancel-withdrawal.php',
                type: 'POST',
                data: { 
                    id: withdrawalId,
                    csrf_token: $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || 'Withdrawal cancelled successfully');
                        withdrawalsTable.ajax.reload(null, false);
                        
                        if (response.new_balance) {
                            $('#currentBalance, #currentBalanceDisplay').text('$' + parseFloat(response.new_balance).toFixed(2));
                        }
                        
                        // Close modal if open
                        $('#withdrawalDetailsModal').modal('hide');
                    } else {
                        toastr.error(response.message || 'Failed to cancel withdrawal');
                        $button.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.status === 401 ? 
                        'Session expired. Please login again.' : 
                        'Failed to cancel withdrawal.');
                    $button.prop('disabled', false).html(originalHtml);
                }
            });
        }
    }

    function submitWithdrawalForm($form) {
        const $submitBtn = $form.find('button[type="submit"]');
        const originalBtnText = $submitBtn.html();
        
        // Validate amount
        const amount = parseFloat($('#amount').val());
        if (isNaN(amount) || amount <= 0) {
            toastr.error('Please enter a valid amount');
            return;
        }
        
        // Validate payment method
        const method = $('#method').val();
        if (!method) {
            toastr.error('Please select a payment method');
            return;
        }
        
        $submitBtn.prop('disabled', true)
            .html('<i class="anticon anticon-loading anticon-spin"></i> Processing...');
        
        // Get payment details
        let paymentDetails = {};
        if (method === 'BANK_TRANSFER') {
            paymentDetails = {
                account_number: $('#accountNumber').val(),
                account_name: $('#accountName').val(),
                bank_name: $('#bankName').val(),
                routing_number: $('#routingNumber').val()
            };
        } else if (method === 'BITCOIN' || method === 'ETHEREUM') {
            paymentDetails = {
                wallet_address: $('#walletAddress').val(),
                network: $('#cryptoNetwork').val()
            };
        }
        
        // Submit withdrawal
        $.ajax({
            url: 'ajax/process-withdrawal.php',
            type: 'POST',
            data: {
                amount: amount,
                method: method,
                payment_details: JSON.stringify(paymentDetails),
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Withdrawal request submitted successfully');
                    $('#newWithdrawalModal').modal('hide');
                    withdrawalsTable.ajax.reload(null, false);
                    $form[0].reset();
                    
                    if (response.new_balance) {
                        $('#currentBalance, #currentBalanceDisplay').text('$' + parseFloat(response.new_balance).toFixed(2));
                    }
                } else {
                    toastr.error(response.message || 'Error processing withdrawal');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Failed to process withdrawal.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) errorMsg = response.message;
                    if (xhr.status === 401) {
                        window.location.reload();
                        return;
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
                toastr.error(errorMsg);
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalBtnText);
            }
        });
    }

    function showModalError(message) {
        const $modalContent = $('#withdrawalDetailsContent');
        $modalContent.html(`
            <div class="modal-header">
                <h5 class="modal-title">Error</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    ${message}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        `);
    }
});