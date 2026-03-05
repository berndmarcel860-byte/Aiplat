$(document).ready(function() {
    if ($('#depositsTable').length) {
        // Initialize deposits table
        const initDepositsTable = function() {
            return $('#depositsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "ajax/deposit.php",
                    type: "POST",
                    data: function(d) {
                        d.csrf_token = $('meta[name="csrf-token"]').attr('content');
                    },
                    error: function(xhr, error, thrown) {
                        console.error('AJAX Error:', xhr.responseText);
                        toastr.error('Failed to load deposits. Please try again.');
                    }
                },
                columns: [
                    { 
                        data: "type",
                        render: function(data) {
                            return data ? data.charAt(0).toUpperCase() + data.slice(1) : 'N/A';
                        }
                    },
                    { 
                        data: "amount",
                        render: function(data, type, row) {
                            return data ? '$' + parseFloat(data).toFixed(2) : '$0.00';
                        }
                    },
                    { 
                        data: "method",
                        render: function(data, type, row) {
                            if (!data) return 'N/A';
                            const methodNames = {
                                'bank_transfer': 'Bank Transfer',
                                'bitcoin': 'Bitcoin',
                                'ethereum': 'Ethereum',
                                'paypal': 'PayPal'
                            };
                            return methodNames[data] || data;
                        }
                    },
                    { 
                        data: "status",
                        render: function(data, type, row) {
                            if (!data) return '';
                            const statusClass = {
                                'pending': 'warning',
                                'completed': 'success',
                                'failed': 'danger',
                                'processing': 'info',
                                'cancelled': 'secondary'
                            }[data.toLowerCase()] || 'primary';
                            return `<span class="badge badge-${statusClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                        }
                    },
                    { 
                        data: "created_at",
                        render: function(data, type, row) {
                            if (!data) return '';
                            return new Date(data).toLocaleString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                        }
                    }
                ],
                responsive: true,
                order: [[4, 'desc']],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>',
                    emptyTable: "No deposits found",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    lengthMenu: "Show _MENU_ entries",
                    search: "Search:",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                initComplete: function() {
                    $('.dataTables_length select').addClass('custom-select custom-select-sm');
                }
            });
        };

        let depositsTable = initDepositsTable();

        // Payment method details display
        $('#paymentMethod').change(function() {
            const selectedOption = $(this).find(':selected');
            const details = selectedOption.data('details');
            const $paymentDetails = $('#paymentDetails');
            
            if (!details) {
                $paymentDetails.html('<p class="text-muted">Select a payment method to view details</p>');
                return;
            }

            try {
                const paymentDetails = typeof details === 'string' ? JSON.parse(details.replace(/&quot;/g, '"')) : details;
                
                let detailsHtml = '<div class="payment-details-container">';
                
                if (paymentDetails.bank_name || paymentDetails.account_number) {
                    detailsHtml += `
                    <div class="bank-details mb-3">
                        <h6 class="text-primary">Bank Transfer Instructions</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Bank Name:</strong></p>
                                <p><strong>Account Number:</strong></p>
                                <p><strong>Routing Number:</strong></p>
                            </div>
                            <div class="col-md-6">
                                <p>${paymentDetails.bank_name || 'N/A'}</p>
                                <p>${paymentDetails.account_number || 'N/A'}</p>
                                <p>${paymentDetails.routing_number || 'N/A'}</p>
                            </div>
                        </div>
                    </div>`;
                }
                
                if (paymentDetails.wallet_address) {
                    detailsHtml += `
                    <div class="crypto-details mb-3">
                        <h6 class="text-primary">Crypto Wallet Details</h6>
                        <div class="input-group">
                            <input type="text" class="form-control" value="${paymentDetails.wallet_address}" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary copy-btn" type="button">
                                    <i class="anticon anticon-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Send only ${selectedOption.text()} to this address</small>
                    </div>`;
                }
                
                if (paymentDetails.instructions) {
                    detailsHtml += `
                    <div class="instructions">
                        <h6 class="text-primary">Additional Instructions</h6>
                        <p>${paymentDetails.instructions.replace(/\n/g, '<br>')}</p>
                    </div>`;
                }
                
                detailsHtml += '</div>';
                $paymentDetails.html(detailsHtml);
                
                $paymentDetails.find('.copy-btn').click(function() {
                    const address = $(this).closest('.input-group').find('input').val();
                    navigator.clipboard.writeText(address).then(() => {
                        toastr.success('Address copied to clipboard');
                    }).catch(() => {
                        toastr.error('Failed to copy address');
                    });
                });
                
            } catch (e) {
                console.error('Error parsing payment details:', e);
                $paymentDetails.html('<div class="alert alert-danger">Error loading payment details</div>');
            }
        });

        // Deposit form submission
        $('#depositForm').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const formData = new FormData($form[0]);
            const $submitBtn = $form.find('button[type="submit"]');
            const $modal = $('#newDepositModal');
            
            if (!$form[0].checkValidity()) {
                $form[0].reportValidity();
                return;
            }
            
            if (!$('#paymentMethod').val()) {
                toastr.error('Please select a payment method');
                return;
            }
            
            if (!$('#proofOfPayment')[0].files[0]) {
                toastr.error('Please upload proof of payment');
                return;
            }
            
            $submitBtn.prop('disabled', true)
                .html('<i class="anticon anticon-loading anticon-spin"></i> Processing...');
            
            $modal.find('.modal-content').append(
                '<div class="modal-overlay"><div class="spinner-border text-primary"></div></div>'
            );
            
            $.ajax({
                url: 'ajax/process-deposit.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else if (data.success) {
                            toastr.success(data.message || 'Deposit submitted successfully');
                            $modal.modal('hide');
                            depositsTable.ajax.reload();
                            
                            if (data.new_balance) {
                                $('[id*="currentBalance"]').each(function() {
                                    $(this).text('$' + parseFloat(data.new_balance).toFixed(2));
                                });
                            }
                            
                            $form[0].reset();
                            $('.custom-file-label').text('Choose file');
                            $('#paymentDetails').html('');
                        } else {
                            toastr.error(data.message || 'Failed to process deposit');
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        toastr.error('Error processing server response');
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.status === 401 
                        ? 'Session expired. Please login again.' 
                        : (xhr.responseJSON?.message || 'Failed to process deposit. Please try again.');
                    toastr.error(errorMsg);
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Submit Deposit');
                    $modal.find('.modal-overlay').remove();
                }
            });
        });

        // File input label update
        $('.custom-file-input').on('change', function() {
            const fileName = $(this).val().split('\\').pop();
            const $label = $(this).next('.custom-file-label');
            
            if (fileName) {
                const validExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
                const fileExt = fileName.split('.').pop().toLowerCase();
                
                if (!validExtensions.includes(fileExt)) {
                    toastr.error('Only JPG, PNG, or PDF files are allowed');
                    $(this).val('');
                    $label.text('Choose file');
                    return;
                }
                
                if (this.files[0].size > 5 * 1024 * 1024) {
                    toastr.error('File size must be less than 5MB');
                    $(this).val('');
                    $label.text('Choose file');
                    return;
                }
                
                $label.text(fileName);
            } else {
                $label.text('Choose file');
            }
        });

        // Refresh button functionality
        $('#refreshDeposits').click(function() {
            const $btn = $(this);
            $btn.prop('disabled', true)
                .html('<i class="anticon anticon-loading anticon-spin"></i> Refreshing...');
            
            depositsTable.ajax.reload(() => {
                $btn.prop('disabled', false).html('<i class="anticon anticon-reload"></i> Refresh');
                toastr.success('Deposits refreshed');
            }, false);
        });
    }
});