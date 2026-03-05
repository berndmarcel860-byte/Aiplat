// =============================================
// CASES TABLE IMPLEMENTATION
// =============================================
$(document).ready(function() {
    if ($('#casesTable').length) {
        const statusMap = {
            'open': { class: 'info', text: 'Open' },
            'documents_required': { class: 'warning', text: 'Documents Required' },
            'under_review': { class: 'primary', text: 'Under Review' },
            'in_progress': { class: 'primary', text: 'In Progress' },
            'resolved': { class: 'success', text: 'Resolved' },
            'completed': { class: 'success', text: 'Completed' },
            'closed': { class: 'secondary', text: 'Closed' },
            'rejected': { class: 'danger', text: 'Rejected' },
            'pending': { class: 'warning', text: 'Pending' },
            'failed': { class: 'danger', text: 'Failed' }
        };

        const casesTable = $('#casesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "ajax/cases.php",
                type: "POST",
                dataSrc: function(json) {
                    if (json.redirect) {
                        window.location.href = json.redirect;
                        return [];
                    }
                    return json.data || [];
                },
                error: function(xhr) {
                    if (xhr.status === 401) {
                        toastr.error('Session expired. Redirecting...');
                        setTimeout(() => window.location.href = 'login.php', 1500);
                    }
                }
            },
            columns: [
                { data: "id" },
                { 
                    data: "reported_amount",
                    render: function(data) {
                        return data ? '$' + parseFloat(data).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,') : '$0.00';
                    }
                },
                { 
                    data: "recovered_amount",
                    render: function(data) {
                        return data ? '$' + parseFloat(data).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,') : '-';
                    }
                },
                { 
                    data: "status",
                    render: function(data) {
                        const status = (data || '').toLowerCase();
                        const statusInfo = statusMap[status] || { class: 'light', text: status };
                        return `<span class="badge badge-${statusInfo.class}">${statusInfo.text}</span>`;
                    }
                },
                { 
                    data: "created_at",
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString('en-US') : '-';
                    }
                },
                { 
                    data: "updated_at",
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString('en-US') : '-';
                    }
                },
                {
                    data: "id",
                    render: function(data) {
                        return `<button class="btn btn-sm btn-primary view-case" data-id="${data}">
                               <i class="anticon anticon-eye"></i> View
                               </button>`;
                    },
                    orderable: false
                }
            ],
            responsive: true,
            order: [[4, 'desc']],
            language: {
                emptyTable: "No cases found",
                processing: "<i class='fa fa-spinner fa-spin'></i> Loading cases...",
                search: "_INPUT_",
                searchPlaceholder: "Search cases...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });

        // View case details with enhanced session handling
        $('#casesTable').on('click', '.view-case', function() {
            const caseId = $(this).data('id');
            const $modal = $('#caseModal');
            const $modalContent = $('#caseModalContent');
            
            $modalContent.html(`<div class="text-center p-4">
                <i class="anticon anticon-loading anticon-spin" style="font-size:24px"></i>
                <p>Loading case details...</p>
            </div>`);
            
            $modal.modal('show');
            
            $.get('ajax/get-case.php', { id: caseId })
                .done(function(response) {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else if (response.success) {
                        // Format and display case data
                        const caseData = response.case;
                        $modalContent.html(`
                            <div class="modal-header">
                                <h5 class="modal-title">Case #${caseData.case_number}</h5>
                                <button type="button" class="close" data-dismiss="modal">
                                    <i class="anticon anticon-close"></i>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p><strong>Status:</strong> <span class="badge badge-${getStatusClass(caseData.status)}">${caseData.status_display}</span></p>
                                        <p><strong>Reported Amount:</strong> ${caseData.reported_amount}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Recovered Amount:</strong> ${caseData.recovered_amount}</p>
                                        <p><strong>Created:</strong> ${caseData.created_at}</p>
                                    </div>
                                </div>
                                <!-- Additional case details would go here -->
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            </div>
                        `);
                    } else {
                        $modalContent.html(`<div class="alert alert-danger">${response.message || 'Failed to load case details'}</div>`);
                    }
                })
                .fail(function(xhr) {
                    const errorMsg = xhr.status === 401 
                        ? 'Session expired. Please <a href="login.php">login</a> again.' 
                        : 'Failed to load case details. Please try again.';
                    $modalContent.html(`<div class="alert alert-danger">${errorMsg}</div>`);
                });
        });

        // Refresh button
        $('.refresh-btn').click(function() {
            casesTable.ajax.reload(null, false);
            toastr.info('Cases table refreshed');
        });

        // Helper function for status classes
        function getStatusClass(status) {
            const statusLower = (status || '').toLowerCase().replace(' ', '_');
            return statusMap[statusLower]?.class || 'secondary';
        }
    }
});