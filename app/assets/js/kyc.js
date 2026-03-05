// KYC Verification System - Fixed Implementation
$(document).ready(function() {
    // Initialize Toastr if available
    if (typeof toastr !== 'undefined') {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 5000
        };
    }

    // Handle document type change
    $('#documentType').change(function() {
        const type = $(this).val();
        const backGroup = $('#backDocumentGroup');
        const backInput = $('#documentBack');
        
        if (type === 'passport') {
            backGroup.hide();
            backInput.prop('required', false);
        } else {
            backGroup.show();
            backInput.prop('required', true);
        }
    });

    // Trigger initial state
    $('#documentType').trigger('change');

    // Enhanced file preview function
    function handleFilePreview(input, previewContainer) {
        const file = input.files[0];
        const preview = $(previewContainer);
        
        if (file) {
            // Validate file size
            if (file.size > 10 * 1024 * 1024) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('File size must be less than 10MB');
                } else {
                    alert('File size must be less than 10MB');
                }
                input.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                let content = '';
                if (file.type.startsWith('image/')) {
                    content = `
                        <div class="d-flex align-items-center mt-3">
                            <img src="${e.target.result}" alt="Preview" onclick="zoomImage('${e.target.result}')" 
                                 class="img-fluid rounded me-3" style="max-height: 100px; cursor: pointer;">
                            <div>
                                <strong>${file.name}</strong><br>
                                <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small><br>
                                <button type="button" class="btn btn-sm btn-danger mt-2" 
                                        onclick="clearFile('${input.id}', '${previewContainer}')">
                                    <i class="fas fa-trash me-1"></i> Remove
                                </button>
                            </div>
                        </div>
                    `;
                } else if (file.type === 'application/pdf') {
                    content = `
                        <div class="d-flex align-items-center mt-3">
                            <i class="fas fa-file-pdf text-danger me-3" style="font-size: 3rem;"></i>
                            <div>
                                <strong>${file.name}</strong><br>
                                <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small><br>
                                <button type="button" class="btn btn-sm btn-danger mt-2" 
                                        onclick="clearFile('${input.id}', '${previewContainer}')">
                                    <i class="fas fa-trash me-1"></i> Remove
                                </button>
                            </div>
                        </div>
                    `;
                } else {
                    content = `
                        <div class="d-flex align-items-center mt-3">
                            <i class="fas fa-file text-primary me-3" style="font-size: 3rem;"></i>
                            <div>
                                <strong>${file.name}</strong><br>
                                <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small><br>
                                <button type="button" class="btn btn-sm btn-danger mt-2" 
                                        onclick="clearFile('${input.id}', '${previewContainer}')">
                                    <i class="fas fa-trash me-1"></i> Remove
                                </button>
                            </div>
                        </div>
                    `;
                }
                preview.html(content).show();
            };
            reader.readAsDataURL(file);
        }
    }
    
    // File input change handlers
    $('#documentFront').change(function() { 
        handleFilePreview(this, '#frontPreview'); 
    });
    
    $('#documentBack').change(function() { 
        handleFilePreview(this, '#backPreview'); 
    });
    
    $('#selfieWithId').change(function() { 
        handleFilePreview(this, '#selfiePreview'); 
    });
    
    $('#addressProof').change(function() { 
        handleFilePreview(this, '#addressPreview'); 
    });

    // View KYC details - Fixed vanilla JS to jQuery conversion
    $(document).on('click', '.view-kyc', function() {
        const kycId = $(this).data('id');
        const modal = new bootstrap.Modal(document.getElementById('kycDetailsModal'));
        const content = document.getElementById('kycDetailsContent');
        
        content.innerHTML = `
            <div class="d-flex justify-content-center align-items-center" style="height: 300px;">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        
        modal.show();
        
        fetch(`ajax/get-kyc.php?id=${kycId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const kyc = data.kyc;
                    const statusClass = {
                        'approved': 'success',
                        'rejected': 'danger',
                        'pending': 'warning'
                    }[kyc.status] || 'secondary';
                    
                    let html = `
                        <div class="modal-header">
                            <h5 class="modal-title">KYC Verification #${kyc.id}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                        <span class="badge bg-${statusClass}">
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
                            
                            <div class="row">
                    `;
                    
                    // Document viewer component
                    const addDocumentCard = (title, path) => {
                        if (!path) return '';
                        
                        const ext = path.split('.').pop().toLowerCase();
                        const isImage = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(ext);
                        
                        return `
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="mb-0">${title}</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        ${isImage ? 
                                            `<img src="${path}" class="img-fluid mb-3" style="max-height: 300px; cursor: pointer" 
                                                  onclick="zoomImage('${path}')">` : 
                                            `<div class="py-4">
                                                <i class="fas fa-file-${ext === 'pdf' ? 'pdf text-danger' : 'image text-primary'} fa-5x"></i>
                                            </div>`
                                        }
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <div class="d-grid gap-2">
                                            <a href="${path}" class="btn btn-sm btn-primary" download="${title}_${kyc.id}.${ext}">
                                                <i class="fas fa-download me-2"></i>Download
                                            </a>
                                            ${isImage ? `
                                            <button class="btn btn-sm btn-outline-secondary" onclick="zoomImage('${path}')">
                                                <i class="fas fa-search-plus me-2"></i>Zoom
                                            </button>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    };
                    
                    html += addDocumentCard('Document Front', kyc.document_front);
                    html += addDocumentCard('Document Back', kyc.document_back);
                    html += addDocumentCard('Selfie with Document', kyc.selfie_with_id);
                    html += addDocumentCard('Proof of Address', kyc.address_proof);
                    
                    html += `
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    `;
                    
                    content.innerHTML = html;
                } else {
                    content.innerHTML = `
                        <div class="alert alert-danger">
                            ${data.message || 'Failed to load KYC details'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        Failed to load KYC details. Please try again.
                    </div>
                `;
                console.error('Error:', error);
            });
    });
    
    // Enhanced form submission
    $('#kycForm').submit(function(e) {
        const btn = $('#submitKycBtn');
        
        // Additional validation
        if ($('#documentType').val() !== 'passport' && !$('#documentBack')[0].files.length) {
            e.preventDefault();
            if (typeof toastr !== 'undefined') {
                toastr.error('Document back side is required for non-passport documents.');
            } else {
                alert('Document back side is required for non-passport documents.');
            }
            return;
        }
        
        // Check if all required files are selected
        const requiredFields = ['#documentFront', '#selfieWithId', '#addressProof'];
        for (let field of requiredFields) {
            if (!$(field)[0].files.length) {
                e.preventDefault();
                const fieldName = field.replace('#', '').replace(/([A-Z])/g, ' $1').replace(/^./, function(str) { 
                    return str.toUpperCase(); 
                });
                if (typeof toastr !== 'undefined') {
                    toastr.error(`Please select a file for ${fieldName}`);
                } else {
                    alert(`Please select a file for ${fieldName}`);
                }
                return;
            }
        }
        
        console.log('Form submitting with files:', {
            documentType: $('#documentType').val(),
            frontFile: $('#documentFront')[0].files[0]?.name,
            backFile: $('#documentBack')[0].files[0]?.name,
            selfieFile: $('#selfieWithId')[0].files[0]?.name,
            addressFile: $('#addressProof')[0].files[0]?.name
        });
        
        // Show loading state
        btn.prop('disabled', true)
           .html('<i class="fas fa-spinner fa-spin me-2"></i>Uploading and Processing...');
        
        // Allow form to submit normally
        // If you want AJAX submission, you'll need to implement it here
    });
    
    // Initialize DataTable for history
    if ($('#kycHistoryTable').length && typeof $.fn.DataTable !== 'undefined') {
        $('#kycHistoryTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'desc']]
        });
    }
});

// Clear file function
function clearFile(inputId, previewId) {
    document.getElementById(inputId).value = '';
    $(previewId).hide().html('');
}

// Zoom image function
function zoomImage(src) {
    $('#zoomedImage').attr('src', src);
    // Using Bootstrap 5 modal
    const modal = new bootstrap.Modal(document.getElementById('imageZoomModal'));
    modal.show();
}