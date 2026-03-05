// Deposits Table Implementation
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
                    { data: "type" },
                    { 
                        data: "amount",
                        render: function(data) {
                            return data ? '$' + parseFloat(data).toFixed(2) : '$0.00';
                        }
                    },
                    { 
                        data: "method",
                        render: function(data) {
                            return data || 'N/A';
                        }
                    },
                    { 
                        data: "status",
                        render: function(data) {
                            if (!data) return '';
                            const statusClass = {
                                'pending': 'warning',
                                'completed': 'success',
                                'failed': 'danger'
                            }[data.toLowerCase()] || 'info';
                            return `<span class="badge badge-${statusClass}">${data}</span>`;
                        }
                    },
                    { 
                        data: "created_at",
                        render: function(data) {
                            return data ? new Date(data).toLocaleString() : '';
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
                    lengthMenu: "Show _MENU_ entries"
                }
            });
        };

        let depositsTable = initDepositsTable();

        // Payment method details display
        $('#paymentMethod').change(function() {
            const details = $(this).find(':selected').data('details');
            $('#paymentDetails').html(details || '<p class="text-muted">Select a payment method to view details</p>');
        });

        // Deposit form submission
        $('#depositForm').submit(function(e) {
            e.preventDefault();
            const formData = $(this).serialize();
            const $submitBtn = $(this).find('button[type="submit"]');
            
            $submitBtn.prop('disabled', true)
                .html('<i class="anticon anticon-loading anticon-spin"></i> Processing...');
            
            $.ajax({
                url: 'ajax/process-deposit.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else if (response.success) {
                        toastr.success(response.message);
                        $('#newDepositModal').modal('hide');
                        depositsTable.ajax.reload();
                        $('#depositForm')[0].reset();
                        $('#paymentDetails').html('');
                    } else {
                        toastr.error(response.message);
                    }
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html('Submit Deposit');
                },
                error: function(xhr) {
                    toastr.error(xhr.status === 401 ? 'Session expired. Please login again.' : 'Failed to process deposit.');
                }
            });
        });
    }
});