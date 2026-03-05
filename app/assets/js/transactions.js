$(document).ready(function() {
    if ($('#transactionsTable').length) {
        // Toastr initialization
        toastr.options = {
            positionClass: "toast-top-right",
            timeOut: 5000,
            closeButton: true,
            progressBar: true
        };

        // Initialize DataTable
        const initTransactionsTable = function() {
            return $('#transactionsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "ajax/transactions.php",
                    type: "POST",
                    data: function(d) {
                        d.csrf_token = $('meta[name="csrf-token"]').attr('content');
                        return d;
                    },
                    dataSrc: function(json) {
                        if (!json || !json.data) {
                            console.error('Invalid data format:', json);
                            toastr.error('Invalid data received from server');
                            return [];
                        }
                        return json.data;
                    },
                    error: function(xhr, error, thrown) {
                        console.error('AJAX Error:', xhr.responseText);
                        let errorMsg = 'Failed to load transactions';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.error) errorMsg = response.error;
                        } catch (e) {}
                        
                        $('#transactionError').text(errorMsg).removeClass('d-none');
                        toastr.error(errorMsg);
                    }
                },
                columns: [
                    { 
                        data: "type",
                        render: function(data) {
                            const icon = {
                                'deposit': '<i class="fas fa-arrow-down text-success mr-2"></i>',
                                'withdrawal': '<i class="fas fa-arrow-up text-danger mr-2"></i>',
                                'refund': '<i class="fas fa-undo text-primary mr-2"></i>',
                                'fee': '<i class="fas fa-file-invoice-dollar text-warning mr-2"></i>',
                                'transfer': '<i class="fas fa-exchange-alt text-info mr-2"></i>'
                            }[data] || '<i class="fas fa-exchange-alt mr-2"></i>';
                            
                            return icon + (data ? data.charAt(0).toUpperCase() + data.slice(1) : '');
                        }
                    },
                    { 
                        data: "amount",
                        render: function(data, type, row) {
                            const amount = parseFloat(data || 0).toFixed(2);
                            const colorClass = row.type === 'deposit' || row.type === 'refund' ? 'text-success' : 'text-danger';
                            return `<span class="${colorClass}">$${amount}</span>`;
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
                                'failed': 'danger',
                                'cancelled': 'secondary',
                                'processing': 'info'
                            }[data.toLowerCase()] || 'info';
                            return `<span class="badge badge-${statusClass}">${data}</span>`;
                        }
                    },
                    { 
                        data: "reference",
                        render: function(data) {
                            return data ? `<small class="text-muted">${data}</small>` : 'N/A';
                        }
                    },
                    { 
                        data: "created_at",
                        render: function(data) {
                            return data ? new Date(data).toLocaleString() : '';
                        }
                    },
                    { 
                        data: "details",
                        render: function(data, type, row) {
                            let details = '';
                            
                            if (row.type === 'withdrawal') {
                                try {
                                    const paymentDetails = JSON.parse(data || '{}');
                                    details = paymentDetails.wallet_address || paymentDetails.bank_details || 'N/A';
                                } catch (e) {
                                    details = data || 'N/A';
                                }
                            } else if (row.type === 'deposit' && row.proof_path) {
                                details = `<a href="${row.proof_path}" target="_blank" class="btn btn-sm btn-outline-primary">View Proof</a>`;
                            } else if (row.type === 'refund') {
                                details = data || 'Case refund';
                            }
                            
                            return details;
                        }
                    }
                ],
                responsive: true,
                order: [[5, 'desc']],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>',
                    emptyTable: "No transactions found",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    lengthMenu: "Show _MENU_ entries",
                    loadingRecords: "Loading...",
                    zeroRecords: "No matching records found"
                },
                initComplete: function() {
                    console.log('Table initialization complete');
                },
                drawCallback: function() {
                    console.log('Table redraw complete');
                }
            });
        };

        let transactionsTable = initTransactionsTable();

        // Refresh button with proper callback handling
        $('#refreshTransactions').click(function() {
            console.log('Starting refresh...');
            $('#transactionError').addClass('d-none');
            
            // Store reference to DataTable instance
            var table = $('#transactionsTable').DataTable();
            
            // Use the callback parameter of ajax.reload()
            table.ajax.reload(function(json) {
                console.log('Refresh successful', json);
                toastr.success('Transactions updated successfully');
            }, false);
        });

        // Debug processing events
        $('#transactionsTable').on('processing.dt', function(e, settings, processing) {
            console.log('Processing state:', processing);
        });
    }
});