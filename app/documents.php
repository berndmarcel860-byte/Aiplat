<?php include 'header.php'; ?>

<!-- Content Wrapper START -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">My Documents</h4>
                        <div class="float-right">
                            <button class="btn btn-primary" data-toggle="modal" data-target="#uploadDocumentModal">
                                <i class="anticon anticon-upload"></i> Upload Document
                            </button>
                            <button class="btn btn-success ml-2" id="refreshDocuments">
                                <i class="anticon anticon-reload"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger d-none" id="documentError"></div>
                        <div class="table-responsive">
                            <table id="documentsTable" class="table table-bordered nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Document Name</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Upload Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Content Wrapper END -->

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocumentModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload New Document</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="documentForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Document Type *</label>
                                <select class="form-control" name="document_type" required>
                                    <option value="">Select Type</option>
                                    <option value="ID Proof">ID Proof (Passport, Driver's License)</option>
                                    <option value="Address Proof">Address Proof (Utility Bill, Bank Statement)</option>
                                    <option value="Payment Proof">Payment Proof (Receipt, Transaction Screenshot)</option>
                                    <option value="Case Evidence">Case Evidence (Screenshots, Emails)</option>
                                    <option value="Other">Other Document</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Document Name (Optional)</label>
                                <input type="text" class="form-control" name="document_name" placeholder="e.g., Passport Front Page">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Document File *</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="documentFile" name="document" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                            <label class="custom-file-label" for="documentFile">Choose file (Max 10MB)</label>
                        </div>
                        <small class="form-text text-muted">Accepted formats: PDF, JPG, PNG, DOC, DOCX</small>
                    </div>
                    <div class="form-group">
                        <label>Description (Optional)</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Any additional information about this document"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="anticon anticon-upload"></i> Upload Document
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Document Preview Modal -->
<div class="modal fade" id="documentPreviewModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewTitle">Document Preview</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body text-center" id="previewContent">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p>Loading document preview...</p>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-primary" id="downloadDocumentBtn" download>
                    <i class="anticon anticon-download"></i> Download
                </a>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>