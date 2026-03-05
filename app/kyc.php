<?php
require_once 'config.php';
require_once 'header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get KYC status and history
$kycRequests = [];
$hasPendingKyc = false;
$latestKyc = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM kyc_verification_requests 
                          WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $kycRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($kycRequests)) {
        $latestKyc = $kycRequests[0];
        $hasPendingKyc = ($latestKyc['status'] === 'pending');
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching KYC requests: " . $e->getMessage();
}
?>

<style>
/* KYC Specific Styles */
:root {
    --brand: #2950a8;
    --brand-light: #2da9e3;
    --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
    --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.12);
    --card-radius: 12px;
}

.kyc-header {
    background: linear-gradient(135deg, #2950a8 0%, #2da9e3 100%);
    color: white;
    padding: 2rem;
    border-radius: var(--card-radius);
    text-align: center;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-md);
}

.kyc-header h1 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
}

.kyc-header .subtitle {
    font-size: 1rem;
    opacity: 0.95;
    margin-top: 10px;
}

.status-pending {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 193, 7, 0.05));
    border: 2px solid #ffc107;
    border-radius: var(--card-radius);
    padding: 2rem;
    text-align: center;
    margin-bottom: 2rem;
}

.status-approved {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
    border: 2px solid #28a745;
    border-radius: var(--card-radius);
    padding: 2rem;
    text-align: center;
    margin-bottom: 2rem;
}

.status-rejected {
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05));
    border: 2px solid #dc3545;
    border-radius: var(--card-radius);
    padding: 2rem;
    text-align: center;
    margin-bottom: 2rem;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #e9ecef;
    padding: 12px 15px;
    transition: all 0.2s ease;
}

.form-control:focus, .form-select:focus {
    border-color: var(--brand);
    box-shadow: 0 0 0 0.2rem rgba(41, 80, 168, 0.15);
}

.btn-primary {
    background: linear-gradient(135deg, var(--brand), var(--brand-light));
    border: none;
    border-radius: 8px;
    padding: 12px 30px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(41, 80, 168, 0.3);
}

.card {
    border-radius: var(--card-radius);
    box-shadow: var(--shadow-sm);
    border: none;
    margin-bottom: 1.5rem;
}

.card:hover {
    box-shadow: var(--shadow-md);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(41, 80, 168, 0.3);
}

.file-preview {
    margin-top: 10px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.file-preview img {
    max-width: 150px;
    max-height: 100px;
    border-radius: 5px;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.file-preview img:hover {
    transform: scale(1.05);
}

.card {
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: none;
}

.badge {
    font-size: 85%;
    padding: 0.35em 0.65em;
    font-weight: 500;
    border-radius: 20px;
}

.badge-success {
    background-color: #28a745;
}

.badge-warning {
    background-color: #ffc107;
    color: #212529;
}

.badge-danger {
    background-color: #dc3545;
}

.debug-info {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 10px;
    margin: 10px 0;
    font-family: monospace;
    font-size: 12px;
}

@media (max-width: 768px) {
    .kyc-header h1 {
        font-size: 2rem;
    }
}

.file-preview {
    margin-top: 10px;
    padding: 10px;
    border: 1px solid #eee;
    border-radius: 5px;
    background-color: #f9f9f9;
}

.progress {
    height: 10px;
    margin-top: 10px;
    display: none;
}

#uploadStatus {
    margin-top: 15px;
    display: none;
}

.upload-success {
    color: #28a745;
    font-weight: bold;
}

.upload-error {
    color: #dc3545;
    font-weight: bold;
}

.upload-info {
    color: #17a2b8;
    font-weight: bold;
}
</style>

<div class="main-content">
    <!-- KYC Header -->
    <div class="kyc-header">
        <h1>KYC Verification</h1>
        <div class="subtitle">Secure your account with identity verification</div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="anticon anticon-close-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="anticon anticon-check-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if ($hasPendingKyc): ?>
        <!-- Pending Status Display -->
        <div class="status-pending">
            <div class="m-b-20">
                <i class="anticon anticon-clock-circle" style="font-size: 4rem; color: #ff9500;"></i>
            </div>
            <h3 style="color: #ff9500; margin-bottom: 15px;">
                <i class="anticon anticon-loading anticon-spin m-r-10"></i>
                Verification in Progress
            </h3>
            <p class="lead m-b-20" style="color: #856404;">
                Your KYC documents have been submitted and are currently being reviewed by our verification team.
            </p>
            <div class="row text-center">
                <div class="col-md-4">
                    <div class="m-b-15 mt-4">
                        <i class="anticon anticon-file-text" style="font-size: 2.5rem; color: #28a745;"></i>
                    </div>
                    <h6>Documents Received</h6>
                    <small class="text-muted">✓ All required documents uploaded</small>
                </div>
                <div class="col-md-4">
                    <div class="m-b-15 mt-4">
                        <i class="anticon anticon-eye" style="font-size: 2.5rem; color: #ff9500;"></i>
                    </div>
                    <h6>Under Review</h6>
                    <small class="text-muted">⏱️ Verification in progress</small>
                </div>
                <div class="col-md-4">
                    <div class="m-b-15 mt-4">
                        <i class="anticon anticon-check-circle" style="font-size: 2.5rem; color: #6c757d;"></i>
                    </div>
                    <h6>Approval Pending</h6>
                    <small class="text-muted">⏳ 1-3 business days</small>
                </div>
            </div>
            <div class="m-t-20 p-15 bg-white" style="border-radius: 10px;">
                <strong>Submitted:</strong> <?= date('F j, Y \a\t g:i A', strtotime($latestKyc['created_at'])) ?>
            </div>
        </div>
    <?php elseif (!empty($kycRequests) && $latestKyc['status'] === 'approved'): ?>
        <!-- Approved Status -->
        <div class="status-approved">
            <div class="m-b-20">
                <i class="anticon anticon-check-circle" style="font-size: 4rem; color: #28a745;"></i>
            </div>
            <h3 style="color: #28a745; margin-bottom: 15px;">
                Verification Completed ✓
            </h3>
            <p class="lead m-b-15" style="color: #155724;">
                Congratulations! Your identity has been successfully verified.
            </p>
            <div class="bg-white p-15" style="border-radius: 10px;">
                <strong>Approved:</strong> <?= date('F j, Y \a\t g:i A', strtotime($latestKyc['verified_at'])) ?>
            </div>
        </div>
    <?php elseif (!empty($kycRequests) && $latestKyc['status'] === 'rejected'): ?>
        <!-- Rejected Status -->
        <div class="status-rejected">
            <div class="m-b-20">
                <i class="anticon anticon-close-circle" style="font-size: 4rem; color: #dc3545;"></i>
            </div>
            <h3 style="color: #dc3545; margin-bottom: 15px;">
                Verification Rejected
            </h3>
            <p class="lead m-b-15" style="color: #721c24;">
                Your KYC submission was not approved. Please review the reason and resubmit.
            </p>
            <?php if (!empty($latestKyc['rejection_reason'])): ?>
            <div class="bg-white p-15 m-b-15" style="border-radius: 10px;">
                <strong>Reason:</strong> <?= htmlspecialchars($latestKyc['rejection_reason']) ?>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!$hasPendingKyc && (empty($kycRequests) || $latestKyc['status'] === 'rejected')): ?>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="anticon anticon-upload m-r-10"></i>Submit KYC Documents
                    </h5>
                    
                    <!-- Upload Progress -->
                    <div class="progress" id="uploadProgress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                    
                    <!-- Upload Status -->
                    <div id="uploadStatus"></div>
                    
                    <form method="POST" enctype="multipart/form-data" id="kycForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="anticon anticon-idcard m-r-10"></i>Document Type
                            </label>
                            <select class="form-control" name="document_type" id="documentType" required>
                                <option value="">Select document type</option>
                                <option value="passport">🛂 Passport</option>
                                <option value="id_card">🆔 National ID Card</option>
                                <option value="driving_license">🚗 Driver's License</option>
                                <option value="other">📄 Other Government ID</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="anticon anticon-camera m-r-10"></i>Document Front
                            </label>
                            <input type="file" class="form-control" name="document_front" id="documentFront" accept="image/*,.pdf" required>
                            <small class="form-text text-muted">Clear photo/scan of the front of your document (JPG, PNG, PDF, max 10MB)</small>
                            <div id="frontPreview" class="file-preview" style="display: none;"></div>
                        </div>
                        
                        <div class="form-group" id="backDocumentGroup">
                            <label class="form-label">
                                <i class="anticon anticon-camera m-r-10"></i>Document Back
                            </label>
                            <input type="file" class="form-control" name="document_back" id="documentBack" accept="image/*,.pdf">
                            <small class="form-text text-muted">Clear photo/scan of the back of your document (JPG, PNG, PDF, max 10MB)</small>
                            <div id="backPreview" class="file-preview" style="display: none;"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="anticon anticon-user m-r-10"></i>Selfie with Document
                            </label>
                            <input type="file" class="form-control" name="selfie_with_id" id="selfieWithId" accept="image/*" required>
                            <small class="form-text text-muted">Your face clearly visible holding the document (JPG, PNG, max 10MB)</small>
                            <div id="selfiePreview" class="file-preview" style="display: none;"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="anticon anticon-home m-r-10"></i>Proof of Address
                            </label>
                            <input type="file" class="form-control" name="address_proof" id="addressProof" accept="image/*,.pdf" required>
                            <small class="form-text text-muted">Utility bill or bank statement (not older than 3 months, JPG, PNG, PDF, max 10MB)</small>
                            <div id="addressPreview" class="file-preview" style="display: none;"></div>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" name="submit_kyc" class="btn btn-primary btn-tone" id="submitKycBtn">
                                <i class="anticon anticon-safety-certificate m-r-10"></i>
                                Submit for Verification
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="anticon anticon-check-circle m-r-10 text-success"></i>
                        Verification Requirements
                    </h5>
                    <div class="alert alert-info">
                        <h6><i class="anticon anticon-safety-certificate m-r-10"></i>Required Documents:</h6>
                        <ul class="m-b-0">
                            <li>📄 Government-issued ID (Passport, National ID, Driver's License)</li>
                            <li>🤳 Clear selfie holding your ID document</li>
                            <li>🏠 Proof of address (utility bill or bank statement, max 3 months old)</li>
                            <li>📸 High-quality photos (all details must be clearly visible)</li>
                        </ul>
                    </div>
                    <div class="alert alert-warning">
                        <h6><i class="anticon anticon-exclamation-circle m-r-10"></i>Important Notes:</h6>
                        <ul class="m-b-0">
                            <li>⚡ Processing time: 1-3 business days</li>
                            <li>📏 Maximum file size: 10MB per document</li>
                            <li>🔒 All documents are encrypted and secure</li>
                            <li>✅ Ensure all text and details are clearly readable</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- KYC History -->
    <?php if (!empty($kycRequests)): ?>
    <div class="row m-t-30">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="anticon anticon-history m-r-10"></i>KYC Status History
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover" id="kycHistoryTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kycRequests as $request): ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('M d, Y H:i', strtotime($request['created_at']))) ?></td>
                                    <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $request['document_type']))) ?></td>
                                    <td>
                                        <span class="badge badge-<?= 
                                            $request['status'] == 'approved' ? 'success' : 
                                            ($request['status'] == 'rejected' ? 'danger' : 'warning') 
                                        ?>">
                                            <?= htmlspecialchars(ucfirst($request['status'])) ?>
                                            <?php if ($request['status'] == 'rejected' && !empty($request['rejection_reason'])): ?>
                                                <i class="anticon anticon-exclamation-circle m-l-5" title="<?= htmlspecialchars($request['rejection_reason']) ?>"></i>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary view-kyc" 
                                                data-id="<?= htmlspecialchars($request['id']) ?>"
                                                data-document-front="<?= htmlspecialchars($request['document_front']) ?>"
                                                data-document-back="<?= htmlspecialchars($request['document_back']) ?>"
                                                data-selfie="<?= htmlspecialchars($request['selfie_with_id']) ?>"
                                                data-address="<?= htmlspecialchars($request['address_proof']) ?>"
                                                data-type="<?= htmlspecialchars($request['document_type']) ?>"
                                                data-status="<?= htmlspecialchars($request['status']) ?>"
                                                data-created="<?= htmlspecialchars($request['created_at']) ?>"
                                                data-verified="<?= htmlspecialchars($request['verified_at']) ?>"
                                                data-reason="<?= htmlspecialchars($request['rejection_reason'] ?? '') ?>">
                                            <i class="anticon anticon-eye m-r-5"></i> View
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- KYC Details Modal -->
<div class="modal fade" id="kycDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">KYC Submission Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="kycDetailsContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Image Zoom Modal -->
<div class="modal fade" id="imageZoomModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Document Preview</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img src="" class="img-fluid" id="zoomedImage" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>
<?php require_once 'footer.php'; ?>
<script>
$(document).ready(function() {
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

    // File preview handling
    function handleFilePreview(input, previewContainer) {
        const file = input.files[0];
        const preview = $(previewContainer);
        
        if (file) {
            // Validate file size
            if (file.size > 10 * 1024 * 1024) {
                alert('File size must be less than 10MB');
                input.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                let content = '';
                if (file.type.startsWith('image/')) {
                    content = `
                        <div class="d-flex align-items-center mt-3">
                            <img src="${e.target.result}" alt="Preview" class="img-thumbnail m-r-15" style="max-height: 100px; cursor: pointer;" onclick="zoomImage('${e.target.result}')">
                            <div>
                                <strong>${file.name}</strong><br>
                                <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small><br>
                                <button type="button" class="btn btn-sm btn-danger m-t-10" onclick="clearFile('${input.id}', '${previewContainer}')">
                                    <i class="anticon anticon-delete"></i> Remove
                                </button>
                            </div>
                        </div>
                    `;
                } else if (file.type === 'application/pdf') {
                    content = `
                        <div class="d-flex align-items-center mt-3">
                            <i class="anticon anticon-file-pdf m-r-15" style="font-size: 3rem; color: #dc3545;"></i>
                            <div>
                                <strong>${file.name}</strong><br>
                                <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small><br>
                                <button type="button" class="btn btn-sm btn-danger m-t-10" onclick="clearFile('${input.id}', '${previewContainer}')">
                                    <i class="anticon anticon-delete"></i> Remove
                                </button>
                            </div>
                        </div>
                    `;
                } else {
                    content = `
                        <div class="d-flex align-items-center mt-3">
                            <i class="anticon anticon-file m-r-15" style="font-size: 3rem;"></i>
                            <div>
                                <strong>${file.name}</strong><br>
                                <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small><br>
                                <button type="button" class="btn btn-sm btn-danger m-t-10" onclick="clearFile('${input.id}', '${previewContainer}')">
                                    <i class="anticon anticon-delete"></i> Remove
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
    $('#documentFront').change(function() { handleFilePreview(this, '#frontPreview'); });
    $('#documentBack').change(function() { handleFilePreview(this, '#backPreview'); });
    $('#selfieWithId').change(function() { handleFilePreview(this, '#selfiePreview'); });
    $('#addressProof').change(function() { handleFilePreview(this, '#addressPreview'); });
    
    // Enhanced form submission with AJAX
    $('#kycForm').submit(function(e) {
        e.preventDefault();
        
        const btn = $('#submitKycBtn');
        const formData = new FormData(this);
        const progressBar = $('#uploadProgress .progress-bar');
        const progressContainer = $('#uploadProgress');
        const statusContainer = $('#uploadStatus');
        
        // Additional validation
        if ($('#documentType').val() !== 'passport' && !$('#documentBack')[0].files.length) {
            showStatus('Document back side is required for non-passport documents.', 'error');
            return;
        }
        
        // Check if all required files are selected
        const requiredFields = ['#documentFront', '#selfieWithId', '#addressProof'];
        for (let field of requiredFields) {
            if (!$(field)[0].files.length) {
                showStatus(`Please select a file for ${field.replace('#', '').replace(/([A-Z])/g, ' $1')}`, 'error');
                return;
            }
        }
        
        // Show progress bar
        progressContainer.show();
        progressBar.css('width', '0%');
        statusContainer.hide();
        
        // Disable submit button
        btn.prop('disabled', true)
           .html('<i class="anticon anticon-loading anticon-spin m-r-10"></i>Uploading and Processing...');
        
        // AJAX request
        $.ajax({
            url: 'ajax/kyc_submit.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                
                // Upload progress
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 50; // 50% for upload
                        progressBar.css('width', percentComplete + '%');
                    }
                }, false);
                
                return xhr;
            },
            success: function(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (data.success) {
                        progressBar.css('width', '100%');
                        showStatus(data.message, 'success');
                        
                        // Redirect after a short delay to show success message
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        showStatus(data.message, 'error');
                        btn.prop('disabled', false)
                           .html('<i class="anticon anticon-safety-certificate m-r-10"></i>Submit for Verification');
                    }
                } catch (e) {
                    showStatus('Error processing server response', 'error');
                    btn.prop('disabled', false)
                       .html('<i class="anticon anticon-safety-certificate m-r-10"></i>Submit for Verification');
                }
            },
            error: function(xhr, status, error) {
                let errorMsg = 'An error occurred during submission. ';
                
                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMsg += response.message || error;
                    } catch (e) {
                        errorMsg += error;
                    }
                } else {
                    errorMsg += error;
                }
                
                showStatus(errorMsg, 'error');
                btn.prop('disabled', false)
                   .html('<i class="anticon anticon-safety-certificate m-r-10"></i>Submit for Verification');
            }
        });
    });
    
    // Function to show status messages
    function showStatus(message, type) {
        const statusContainer = $('#uploadStatus');
        statusContainer.removeClass('upload-success upload-error upload-info');
        
        switch(type) {
            case 'success':
                statusContainer.addClass('upload-success');
                break;
            case 'error':
                statusContainer.addClass('upload-error');
                break;
            case 'info':
                statusContainer.addClass('upload-info');
                break;
        }
        
        statusContainer.html('<i class="anticon ' + 
            (type === 'success' ? 'anticon-check-circle' : 
             type === 'error' ? 'anticon-close-circle' : 'anticon-info-circle') + 
            ' m-r-5"></i>' + message).show();
    }
    
    // View KYC details
    $('.view-kyc').click(function() {
        const id = $(this).data('id');
        const documentFront = $(this).data('document-front');
        const documentBack = $(this).data('document-back');
        const selfie = $(this).data('selfie');
        const address = $(this).data('address');
        const type = $(this).data('type');
        const status = $(this).data('status');
        const created = $(this).data('created');
        const verified = $(this).data('verified');
        const reason = $(this).data('reason');
        
        const statusClass = {
            'approved': 'success',
            'rejected': 'danger',
            'pending': 'warning'
        }[status] || 'secondary';
        
        let html = `
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="kyc-info-item mb-2">
                        <span class="font-weight-bold">Document Type:</span>
                        <span class="float-right">${type.replace(/_/g, ' ')}</span>
                    </div>
                    <div class="kyc-info-item mb-2">
                        <span class="font-weight-bold">Status:</span>
                        <span class="float-right badge badge-${statusClass}">
                            ${status.charAt(0).toUpperCase() + status.slice(1)}
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="kyc-info-item mb-2">
                        <span class="font-weight-bold">Submitted:</span>
                        <span class="float-right">${new Date(created).toLocaleString()}</span>
                    </div>
                    ${verified ? `
                    <div class="kyc-info-item mb-2">
                        <span class="font-weight-bold">Verified:</span>
                        <span class="float-right">${new Date(verified).toLocaleString()}</span>
                    </div>` : ''}
                </div>
            </div>
            
            ${reason ? `
            <div class="alert alert-danger">
                <strong>Rejection Reason:</strong> ${reason}
            </div>` : ''}
            
            <div class="row">
        `;
        
        // Document viewer component
        const addDocumentCard = (title, path) => {
            if (!path) return '';
            
            const ext = path.split('.').pop().toLowerCase();
            const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(ext);
            
            return `
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="mb-0">${title}</h6>
                        </div>
                        <div class="card-body text-center">
                            ${isImage ? 
                                `<img src="${path}" class="img-fluid mb-3" style="max-height: 200px; cursor: pointer" 
                                      onclick="zoomImage('${path}')">` : 
                                `<div class="py-4">
                                    <i class="fas fa-file-${ext === 'pdf' ? 'pdf text-danger' : 'image'} fa-4x"></i>
                                    <p class="mt-2">${ext.toUpperCase()} File</p>
                                </div>`
                            }
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-grid gap-2">
                                <a href="${path}" class="btn btn-sm btn-primary" download>
                                    <i class="anticon anticon-download m-r-5"></i>Download
                                </a>
                                ${isImage ? `
                                <button class="btn btn-sm btn-outline-secondary" onclick="zoomImage('${path}')">
                                    <i class="anticon anticon-zoom-in m-r-5"></i>Zoom
                                </button>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        };
        
        html += addDocumentCard('Document Front', documentFront);
        html += addDocumentCard('Document Back', documentBack);
        html += addDocumentCard('Selfie with Document', selfie);
        html += addDocumentCard('Proof of Address', address);
        
        html += `
            </div>
        `;
        
        $('#kycDetailsContent').html(html);
        $('#kycDetailsModal').modal('show');
    });
    
    // Initialize DataTable for history
    if ($('#kycHistoryTable').length) {
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
    $('#imageZoomModal').modal('show');
}
</script>
</body>
</html>