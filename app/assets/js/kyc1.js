// KYC Verification System
$(document).ready(function() {
    if ($('#kycForm').length) {
        // Enhanced document type handler with animation
        $('#documentType').change(function() {
            const isPassport = $(this).val() === 'passport';
            $('#backDocumentGroup').stop(true, true)[isPassport ? 'slideUp' : 'slideDown'](300, function() {
                $('#documentBack').prop('required', !isPassport);
            });
        }).trigger('change'); // Initialize on load

        // Modern file input handler with preview
        $('.custom-file-input').on('change', function() {
            const $input = $(this);
            const $label = $input.next('.custom-file-label');
            const file = this.files[0];
            
            if (!file) {
                $label.html('Choose file');
                return;
            }

            // Client-side validation
            if (file.size > 10 * 1024 * 1024) { // 10MB limit
                toastr.error('File size exceeds 10MB limit');
                $input.val('');
                $label.html('Choose file (Max 10MB)');
                return;
            }

            $label.html(file.name);
            
            // Show preview for images
            if (file.type.match('image.*') && $(this).data('preview-target')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $($input.data('preview-target')).attr('src', e.target.result).show();
                }
                reader.readAsDataURL(file);
            }
        });

        // Modern form submission with progress tracking
        $('#kycForm').submit(function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const $submitBtn = $('#submitKycBtn');
            const $progress = $('<div class="progress mt-2"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div></div>');
            
            $submitBtn.after($progress).prop('disabled', true)
                     .html('<i class="anticon anticon-loading anticon-spin"></i> Uploading...');

            // Add CSRF token if available
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            if (csrfToken) formData.append('csrf_token', csrfToken);

            $.ajax({
                url: 'kyc.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percent = Math.round((e.loaded / e.total) * 100);
                            $progress.find('.progress-bar').css('width', percent + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else if (response.success) {
                        toastr.success(response.message || 'KYC submitted successfully!');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        handleSubmissionError(response.message || 'Submission failed. Please try again.');
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'Failed to submit KYC documents.';
                    try {
                        const response = xhr.responseJSON || JSON.parse(xhr.responseText);
                        errorMsg = response.message || errorMsg;
                        
                        // Handle specific error cases
                        if (xhr.status === 413) errorMsg = 'File size too large. Maximum 10MB allowed.';
                        if (xhr.status === 401) {
                            errorMsg = 'Session expired. Redirecting to login...';
                            setTimeout(() => window.location.href = 'login.php', 2000);
                        }
                    } catch (e) {
                        console.error('Error parsing error response:', e);
                    }
                    handleSubmissionError(errorMsg);
                },
                complete: function() {
                    $progress.remove();
                }
            });

            function handleSubmissionError(message) {
                toastr.error(message);
                $submitBtn.prop('disabled', false)
                         .html('<i class="anticon anticon-upload"></i> Submit for Verification');
            }
        });
    }

    // Modern KYC details viewer with caching
    const kycDetailCache = {};
    $(document).on('click', '.view-kyc', function() {
        const kycId = $(this).data('id');
        const $modal = $('#kycDetailsModal');
        const $content = $('#kycDetailsContent');
        
        // Use cached content if available
        if (kycDetailCache[kycId]) {
            showKycDetails(kycDetailCache[kycId]);
            $modal.modal('show');
            return;
        }

        // Set loading state with modern spinner
        $content.html(`
            <div class="d-flex justify-content-center align-items-center" style="height: 300px;">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        `);
        
        $modal.modal('show');

        // Fetch KYC details with timeout
        const fetchTimer = setTimeout(() => {
            $content.find('.spinner-border').after(
                '<div class="text-muted mt-2">Taking longer than expected...</div>'
            );
        }, 3000);

        $.ajax({
            url: 'ajax/get-kyc.php',
            type: 'GET',
            data: { id: kycId },
            dataType: 'json',
            success: function(response) {
                clearTimeout(fetchTimer);
                if (response.success) {
                    kycDetailCache[kycId] = response; // Cache the response
                    showKycDetails(response);
                } else {
                    showError(response.message || 'Failed to load KYC details');
                }
            },
            error: function(xhr) {
                clearTimeout(fetchTimer);
                showError(
                    xhr.status === 401 ? 'Session expired. Please login again.' :
                    'Failed to load KYC details. Please try again.'
                );
            }
        });

        function showKycDetails(response) {
            const kyc = response.kyc;
            const statusClass = {
                'approved': 'success',
                'rejected': 'danger',
                'pending': 'warning'
            }[kyc.status] || 'secondary';
            
            let content = `
                <div class="modal-header">
                    <h5 class="modal-title">KYC Verification #${kyc.id}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="kyc-info-item">
                                <span class="info-label">Document Type:</span>
                                <span class="info-value">${kyc.document_type.replace(/_/g, ' ')}</span>
                            </div>
                            <div class="kyc-info-item">
                                <span class="info-label">Status:</span>
                                <span class="badge badge-${statusClass}">
                                    ${kyc.status.charAt(0).toUpperCase() + kyc.status.slice(1)}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="kyc-info-item">
                                <span class="info-label">Submitted:</span>
                                <span class="info-value">${new Date(kyc.created_at).toLocaleString()}</span>
                            </div>
                            ${kyc.verified_at ? `
                            <div class="kyc-info-item">
                                <span class="info-label">Verified:</span>
                                <span class="info-value">${new Date(kyc.verified_at).toLocaleString()}</span>
                            </div>` : ''}
                        </div>
                    </div>
                    
                    ${kyc.rejection_reason ? `
                    <div class="alert alert-danger">
                        <strong>Rejection Reason:</strong> ${kyc.rejection_reason}
                    </div>` : ''}
                    
                    <div class="documents-grid">
            `;
            
            // Document viewer component
            const addDocument = (title, path) => {
                if (!path) return '';
                const ext = path.split('.').pop().toLowerCase();
                const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(ext);
                
                return `
                    <div class="document-card">
                        <h6>${title}</h6>
                        <div class="document-preview">
                            ${isImage ? 
                                `<img src="${path}" class="img-fluid" alt="${title}" data-zoomable>` : 
                                `<div class="document-icon">
                                    <i class="far fa-file-${ext === 'pdf' ? 'pdf' : 'alt'}"></i>
                                </div>`
                            }
                        </div>
                        <div class="document-actions">
                            <a href="${path}" target="_blank" class="btn btn-sm btn-primary" download>
                                <i class="fas fa-download"></i> Download
                            </a>
                            /${isImage ? `
                            <button class="btn btn-sm btn-secondary zoom-btn" data-img="/${path}">
                                <i class="fas fa-search-plus"></i> Zoom
                            </button>` : ''}
                        </div>
                    </div>
                `;
            };
            
            content += addDocument('Document Front', kyc.document_front);
            content += addDocument('Document Back', kyc.document_back);
            content += addDocument('Selfie with Document', kyc.selfie_with_id);
            content += addDocument('Proof of Address', kyc.address_proof);
            
            content += `
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            `;
            
            $content.html(content);
            initDocumentViewer();
        }
        
        function showError(message) {
            $content.html(`
                <div class="alert alert-danger">
                    ${message}
                    <button type="button" class="btn btn-link retry-btn">Retry</button>
                </div>
            `).find('.retry-btn').click(function() {
                $(this).closest('.view-kyc').trigger('click');
            });
        }
        
        function initDocumentViewer() {
            // Initialize zoom functionality
            $('[data-zoomable]').click(function() {
                const src = $(this).attr('src');
                $('#imageZoomModal img').attr('src', src);
                $('#imageZoomModal').modal('show');
            });
            
            $('.zoom-btn').click(function() {
                const imgSrc = $(this).data('img');
                $('#imageZoomModal img').attr('src', imgSrc);
                $('#imageZoomModal').modal('show');
            });
        }
    });

    // Modern KYC table with server-side processing
    if ($('#kycTable').length) {
        const kycTable = $('#kycTable').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: 'ajax/kyc-list.php',
                type: 'POST',
                data: function(d) {
                    d.csrf_token = $('meta[name="csrf-token"]').attr('content');
                },
                error: function(xhr) {
                    if (xhr.status === 401) {
                        toastr.error('Session expired. Redirecting to login...');
                        setTimeout(() => window.location.href = 'login.php', 1500);
                    } else {
                        toastr.error('Failed to load KYC records');
                    }
                }
            },
            columns: [
                { 
                    data: 'created_at',
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString() : '-';
                    }
                },
                { 
                    data: 'document_type',
                    render: function(data) {
                        return data ? data.replace(/_/g, ' ') : '-';
                    }
                },
                { 
                    data: 'status',
                    render: function(data, type, row) {
                        const statusClass = {
                            'approved': 'success',
                            'rejected': 'danger',
                            'pending': 'warning'
                        }[data] || 'secondary';
                        
                        return `<span class="badge badge-${statusClass}">
                            ${data.charAt(0).toUpperCase() + data.slice(1)}
                        </span>`;
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return `
                            <button class="btn btn-sm btn-outline-primary view-kyc" data-id="${row.id}">
                                <i class="anticon anticon-eye"></i> Details
                            </button>
                        `;
                    },
                    orderable: false
                }
            ],
            order: [[0, 'desc']],
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>',
                emptyTable: "No KYC records found",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                lengthMenu: "Show _MENU_ entries"
            },
            dom: '<"top"<"row"<"col-md-6"f><"col-md-6"B>>>rt<"bottom"<"row"<"col-md-6"i><"col-md-6"p>>><"clear">',
            buttons: [
                {
                    extend: 'refresh',
                    text: '<i class="anticon anticon-reload"></i> Refresh',
                    className: 'btn btn-primary',
                    action: function(e, dt, node, config) {
                        dt.ajax.reload(null, false);
                        toastr.info('KYC records refreshed');
                    }
                },
                {
                    extend: 'excel',
                    text: '<i class="anticon anticon-file-excel"></i> Export',
                    className: 'btn btn-success',
                    title: 'KYC_Records'
                }
            ],
            initComplete: function() {
                // Add modern filter controls
                this.api().columns([1]).every(function() {
                    const column = this;
                    const select = $('<select class="form-control form-control-sm"><option value="">All Document Types</option></select>')
                        .appendTo($(column.header()).empty())
                        .on('change', function() {
                            column.search($(this).val()).draw();
                        });
                    
                    column.data().unique().sort().each(function(d) {
                        select.append(`<option value="${d}">${d.replace(/_/g, ' ')}</option>`);
                    });
                });
            }
        });

        // Add modern search delay
        let searchTimeout;
        $('.dataTables_filter input').unbind().bind('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                kycTable.search($(this).val()).draw();
            }, 500);
        });
    }
});