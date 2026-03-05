<?php
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2>System Information</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">System Information</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>System Information</h5>
                <div class="d-flex">
                    <button class="btn btn-info mr-2" id="refreshSystem_Info">
                        <i class="anticon anticon-reload"></i> Refresh
                    </button>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addSystem_InfoModal">
                        <i class="anticon anticon-plus"></i> Add New
                    </button>
                </div>
            </div>
            
            
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addSystem_InfoModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New System Information</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="addSystem_InfoForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>


