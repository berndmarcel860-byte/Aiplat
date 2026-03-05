<?php
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2>Roles & Permissions</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Roles & Permissions</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Roles & Permissions</h5>
                <div class="d-flex">
                    <button class="btn btn-info mr-2" id="refreshRoles">
                        <i class="anticon anticon-reload"></i> Refresh
                    </button>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addRolesModal">
                        <i class="anticon anticon-plus"></i> Add New
                    </button>
                </div>
            </div>
            
            
            <div class="table-responsive">
                <table id="rolesTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addRolesModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Roles & Permissions</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="addRolesForm">
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


<script>
$(document).ready(function() {
    // Initialize DataTable
    const rolesTable = $('#rolesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_roles.php',
            type: 'POST'
        },
        order: [[3, 'desc']],
        columns: [
            { data: 'id' },
            { data: 'name' },
            { 
                data: 'status',
                render: function(data) {
                    const statusClass = data == 'active' ? 'success' : 'secondary';
                    return `<span class="badge badge-${statusClass}">${data.toUpperCase()}</span>`;
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleDateString();
                }
            },
            {
                data: 'id',
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info view-item" 
                                    data-id="${data}" 
                                    title="View Details">
                                <i class="anticon anticon-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-primary edit-item" 
                                    data-id="${data}" 
                                    title="Edit">
                                <i class="anticon anticon-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-item" 
                                    data-id="${data}" 
                                    title="Delete">
                                <i class="anticon anticon-delete"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });
    
    // Refresh button
    $('#refreshRoles').click(function() {
        rolesTable.ajax.reload();
    });
});
</script>
