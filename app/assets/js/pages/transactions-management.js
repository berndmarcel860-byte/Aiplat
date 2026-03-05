// =============================================
// TRANSACTIONS TABLE
// =============================================
$(document).ready(function() {
    if ($('#transactionsTable').length) {
        $('#transactionsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/transactions.php",
                type: "POST",
                data: function(d) {
                    // Add CSRF token if needed
                    d.csrf_token = $('meta[name="csrf-token"]').attr('content');
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTables error:', error, thrown);
                    // Show user-friendly error message
                    $('#transactionsTable').before(
                        '<div class="alert alert-danger">Failed to load transactions. Please try again.</div>'
                    );
                }
            },
            columns: [
                { data: "type" },
                { 
                    data: "amount",
                    render: function(data, type) {
                        if (type === 'display') {
                            return data ? '$' + parseFloat(data).toFixed(2) : '$0.00';
                        }
                        return data;
                    }
                },
                { data: "method" },
                { 
                    data: "status",
                    render: function(data, type) {
                        if (type === 'display') {
                            if (!data) return '';
                            var statusClass = {
                                'pending': 'warning',
                                'completed': 'success',
                                'failed': 'danger',
                                'cancelled': 'secondary'
                            }[data.toLowerCase()] || 'info';
                            return '<span class="badge badge-' + statusClass + '">' + data + '</span>';
                        }
                        return data;
                    }
                },
                { 
                    data: "created_at",
                    render: function(data, type) {
                        if (type === 'display') {
                            return data ? new Date(data).toLocaleString() : '';
                        }
                        return data;
                    }
                }
            ],
            responsive: true,
            order: [[4, 'desc']],
            language: {
                processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span>Loading...</span>'
            }
        });
    }
});