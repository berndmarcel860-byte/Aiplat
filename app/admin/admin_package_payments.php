<?php
// admin_package_payments.php
// Package Payments Management — dedicated view/approve/reject for package purchase payments,
// separated from regular user deposit/withdrawal transactions.

require_once 'admin_header.php';

// Load packages for filter dropdown
$packages = [];
try {
    $packages = $pdo->query("SELECT id, name, price FROM packages ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // packages table not yet available
}
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Package Payments</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <a href="admin_transactions.php" class="breadcrumb-item">Financial Management</a>
                <span class="breadcrumb-item active">Package Payments</span>
            </nav>
        </div>
    </div>

    <!-- Stats cards -->
    <div class="row" id="ppStatsRow">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-blue">
                            <i class="anticon anticon-gift"></i>
                        </div>
                        <div class="m-l-15">
                            <h2 class="m-b-0" id="ppStatTotal">—</h2>
                            <p class="m-b-0 text-muted">Total Payments</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-gold">
                            <i class="anticon anticon-clock-circle"></i>
                        </div>
                        <div class="m-l-15">
                            <h2 class="m-b-0" id="ppStatPending">—</h2>
                            <p class="m-b-0 text-muted">Pending</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-green">
                            <i class="anticon anticon-check-circle"></i>
                        </div>
                        <div class="m-l-15">
                            <h2 class="m-b-0" id="ppStatCompleted">—</h2>
                            <p class="m-b-0 text-muted">Completed</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="media align-items-center">
                        <div class="avatar avatar-icon avatar-lg avatar-red">
                            <i class="anticon anticon-close-circle"></i>
                        </div>
                        <div class="m-l-15">
                            <h2 class="m-b-0" id="ppStatRevenue">—</h2>
                            <p class="m-b-0 text-muted">Total Revenue (€)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation tabs: link back to User Transactions -->
    <ul class="nav nav-tabs mb-0" style="border-bottom:none;">
        <li class="nav-item">
            <a class="nav-link" href="admin_transactions.php">
                <i class="anticon anticon-transaction mr-1"></i> User Transactions
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="admin_package_payments.php">
                <i class="anticon anticon-gift mr-1"></i> Package Payments
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="admin_deposits.php">
                <i class="anticon anticon-arrow-down mr-1"></i> Deposits
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="admin_withdrawals.php">
                <i class="anticon anticon-arrow-up mr-1"></i> Withdrawals
            </a>
        </li>
    </ul>

    <div class="card" style="border-top-left-radius:0;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Package Payment Records</h5>
                <button class="btn btn-primary" data-toggle="modal" data-target="#filterPpModal">
                    <i class="anticon anticon-filter"></i> Filter
                </button>
            </div>

            <!-- Inline quick-filters -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <select id="ppQuickStatus" class="form-control form-control-sm">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select id="ppQuickPackage" class="form-control form-control-sm">
                        <option value="">All Packages</option>
                        <?php foreach ($packages as $pkg): ?>
                        <option value="<?= (int)$pkg['id'] ?>">
                            <?= htmlspecialchars($pkg['name']) ?> (€<?= number_format((float)$pkg['price'], 2) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table id="ppTable" class="table table-hover" style="width:100%;">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Package</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ===== Filter Modal ===== -->
<div class="modal fade" id="filterPpModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="anticon anticon-filter mr-1"></i> Filter Package Payments</h5>
                <button type="button" class="close" data-dismiss="modal"><i class="anticon anticon-close"></i></button>
            </div>
            <form id="filterPpForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status" id="filterPpStatus">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Package</label>
                        <select class="form-control" name="package_id" id="filterPpPackage">
                            <option value="">All Packages</option>
                            <?php foreach ($packages as $pkg): ?>
                            <option value="<?= (int)$pkg['id'] ?>">
                                <?= htmlspecialchars($pkg['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date Range</label>
                        <div class="input-group">
                            <input type="date" class="form-control" name="start_date" id="filterPpStartDate">
                            <div class="input-group-prepend input-group-append">
                                <span class="input-group-text">to</span>
                            </div>
                            <input type="date" class="form-control" name="end_date" id="filterPpEndDate">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" id="clearPpFilter">Clear Filters</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== Payment Details Modal ===== -->
<div class="modal fade" id="ppDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title font-weight-bold">
                    <i class="anticon anticon-file-text mr-2 text-primary"></i> Package Payment Details
                </h5>
                <button type="button" class="close" data-dismiss="modal"><i class="anticon anticon-close"></i></button>
            </div>
            <div class="modal-body p-0" id="ppDetailsContent">
                <div class="text-center py-5">
                    <i class="anticon anticon-loading anticon-spin font-size-24 text-primary"></i>
                    <p class="mt-2 text-muted">Loading…</p>
                </div>
            </div>
            <div class="modal-footer" id="ppDetailsFooter">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== Reject Reason Modal ===== -->
<div class="modal fade" id="ppRejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="anticon anticon-close-circle mr-1 text-danger"></i> Reject Payment
                </h5>
                <button type="button" class="close" data-dismiss="modal"><i class="anticon anticon-close"></i></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Provide an optional reason for rejection. The package will remain in its current state.</p>
                <input type="hidden" id="ppRejectId">
                <div class="form-group">
                    <label for="ppRejectNotes">Rejection Notes <span class="text-muted">(optional)</span></label>
                    <textarea id="ppRejectNotes" class="form-control" rows="3" placeholder="e.g. Payment not received, suspicious transaction…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="ppRejectConfirm">
                    <i class="anticon anticon-stop mr-1"></i> Reject Payment
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function () {

    // ------------------------------------------------------------------
    // Stats loader
    // ------------------------------------------------------------------
    function loadStats() {
        $.post('admin_ajax/get_package_payments.php',
            { draw: 1, start: 0, length: 9999 },
            function (res) {
                if (!res || !res.data) return;
                var rows = res.data;
                var total = rows.length;
                var pending = rows.filter(function(r){ return r.status === 'pending'; }).length;
                var completed = rows.filter(function(r){ return r.status === 'completed'; }).length;
                var revenue = rows
                    .filter(function(r){ return r.status === 'completed'; })
                    .reduce(function(acc, r){ return acc + parseFloat(r.amount || 0); }, 0);
                $('#ppStatTotal').text(total);
                $('#ppStatPending').text(pending);
                $('#ppStatCompleted').text(completed);
                $('#ppStatRevenue').text('€' + revenue.toLocaleString('de-DE', {minimumFractionDigits:2, maximumFractionDigits:2}));
            }, 'json');
    }
    loadStats();

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------
    function statusBadge(status) {
        var map = { pending:'warning', completed:'success', failed:'danger', refunded:'secondary' };
        var cls = map[status] || 'secondary';
        var label = status.charAt(0).toUpperCase() + status.slice(1);
        return '<span class="badge badge-' + cls + '">' + label + '</span>';
    }

    function escHtml(s) {
        return $('<span>').text(s || '').html();
    }

    // ------------------------------------------------------------------
    // DataTable
    // ------------------------------------------------------------------
    var ppTable = $('#ppTable').DataTable({
        processing : true,
        serverSide : true,
        ajax: {
            url  : 'admin_ajax/get_package_payments.php',
            type : 'POST',
            data : function (d) {
                d.status     = $('#ppQuickStatus').val();
                d.package_id = $('#ppQuickPackage').val();
                d.start_date = $('#filterPpStartDate').val();
                d.end_date   = $('#filterPpEndDate').val();
            }
        },
        columns: [
            { data: 'id' },
            {
                data: null,
                render: function(data, type, row) {
                    return '<strong>' + escHtml(row.user_name) + '</strong>'
                         + '<br><small class="text-muted">' + escHtml(row.user_email) + '</small>';
                }
            },
            { data: 'package_name', defaultContent: '<em class="text-muted">—</em>' },
            {
                data: 'amount',
                render: function(data) {
                    return '€' + parseFloat(data || 0).toLocaleString('de-DE', {minimumFractionDigits:2, maximumFractionDigits:2});
                }
            },
            {
                data: 'payment_method',
                render: function(data) {
                    if (!data) return '<em class="text-muted">—</em>';
                    return escHtml(data.replace(/_/g,' ').replace(/\b\w/g, function(c){ return c.toUpperCase(); }));
                }
            },
            {
                data: 'status',
                render: function(data) { return statusBadge(data); }
            },
            {
                data: 'created_at',
                render: function(data) {
                    return data ? new Date(data).toLocaleString('de-DE') : '—';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var id = parseInt(row.id, 10);
                    var btns = '<button class="btn btn-xs btn-primary pp-view" data-id="' + id + '" title="View Details">'
                             + '<i class="anticon anticon-eye"></i></button>';
                    if (row.status === 'pending') {
                        btns += ' <button class="btn btn-xs btn-success pp-approve" data-id="' + id + '" title="Approve">'
                              + '<i class="anticon anticon-check"></i></button>';
                        btns += ' <button class="btn btn-xs btn-danger pp-reject" data-id="' + id + '" title="Reject">'
                              + '<i class="anticon anticon-close"></i></button>';
                    }
                    return '<div class="btn-group">' + btns + '</div>';
                }
            }
        ],
        order: [[0, 'desc']],
        language: { processing: '<i class="anticon anticon-loading anticon-spin"></i> Loading…' }
    });

    // Reload on quick-filter change
    $('#ppQuickStatus, #ppQuickPackage').on('change', function () {
        ppTable.ajax.reload();
    });

    // ------------------------------------------------------------------
    // Filter form
    // ------------------------------------------------------------------
    $('#filterPpForm').on('submit', function (e) {
        e.preventDefault();
        $('#filterPpModal').modal('hide');
        // Sync quick-filter dropdown with modal value
        $('#ppQuickStatus').val($('#filterPpStatus').val());
        $('#ppQuickPackage').val($('#filterPpPackage').val());
        ppTable.ajax.reload();
    });

    $('#clearPpFilter').on('click', function () {
        $('#filterPpForm')[0].reset();
        $('#ppQuickStatus, #ppQuickPackage').val('');
        ppTable.ajax.reload();
        $('#filterPpModal').modal('hide');
    });

    // ------------------------------------------------------------------
    // View Details
    // ------------------------------------------------------------------
    $('#ppTable').on('click', '.pp-view', function () {
        var id = parseInt($(this).data('id'), 10);

        // Find row in current DataTable data
        var rowData = ppTable.rows().data().toArray().filter(function(r){ return parseInt(r.id,10) === id; })[0];
        if (!rowData) {
            $('#ppDetailsContent').html('<div class="alert alert-warning m-3">Details not available.</div>');
            $('#ppDetailsModal').modal('show');
            return;
        }

        var r = rowData;
        var statusColor = { pending:'#fff3cd', completed:'#d1e7dd', failed:'#f8d7da', refunded:'#e2e3e5' }[r.status] || '#f0f4ff';
        var statusBorder = { pending:'#ffc107', completed:'#198754', failed:'#dc3545', refunded:'#6c757d' }[r.status] || '#4e73df';
        var amountFmt = '€' + parseFloat(r.amount || 0).toLocaleString('de-DE', {minimumFractionDigits:2, maximumFractionDigits:2});
        var methodFmt = r.payment_method ? r.payment_method.replace(/_/g,' ').replace(/\b\w/g, function(c){ return c.toUpperCase(); }) : '—';

        var html = '<div class="p-4">'
            + '<div class="d-flex align-items-center mb-4 p-3 rounded" style="background:' + statusColor + ';border-left:4px solid ' + statusBorder + '">'
            +   '<div class="flex-grow-1">'
            +     '<div class="text-muted small mb-1">Payment #' + parseInt(r.id,10) + (r.reference ? ' &nbsp;·&nbsp; ' + escHtml(r.reference) : '') + '</div>'
            +     '<div class="font-weight-700 font-size-20">' + amountFmt + '</div>'
            +   '</div>'
            +   '<div class="text-right">' + statusBadge(r.status) + '</div>'
            + '</div>'

            + '<div class="row">'
            +   '<div class="col-md-6 mb-3"><label class="text-muted small d-block">User</label><span>' + escHtml(r.user_name) + '</span><br><small class="text-muted">' + escHtml(r.user_email) + '</small></div>'
            +   '<div class="col-md-6 mb-3"><label class="text-muted small d-block">Package</label><span>' + escHtml(r.package_name || '—') + '</span>' + (r.package_price ? '<br><small class="text-muted">Listed price: €' + parseFloat(r.package_price).toLocaleString('de-DE', {minimumFractionDigits:2}) + '</small>' : '') + '</div>'
            +   '<div class="col-md-6 mb-3"><label class="text-muted small d-block">Payment Method</label><span>' + escHtml(methodFmt) + '</span></div>'
            +   '<div class="col-md-6 mb-3"><label class="text-muted small d-block">Created</label><span>' + (r.created_at ? new Date(r.created_at).toLocaleString('de-DE') : '—') + '</span></div>'
            +   '<div class="col-md-6 mb-3"><label class="text-muted small d-block">Processed At</label><span>' + (r.processed_at ? new Date(r.processed_at).toLocaleString('de-DE') : '—') + '</span></div>'
            +   '<div class="col-md-6 mb-3"><label class="text-muted small d-block">Processed By</label><span>' + escHtml(r.processed_by_name || '—') + '</span></div>'
            + '</div>';

        if (r.admin_notes) {
            html += '<div class="alert alert-light border"><strong>Admin Notes:</strong> ' + escHtml(r.admin_notes) + '</div>';
        }

        html += '</div>';

        $('#ppDetailsContent').html(html);

        var footer = '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>';
        if (r.status === 'pending') {
            footer += ' <button type="button" class="btn btn-success pp-approve-modal" data-id="' + parseInt(r.id,10) + '">'
                    + '<i class="anticon anticon-check mr-1"></i> Approve</button>';
            footer += ' <button type="button" class="btn btn-danger pp-reject-modal" data-id="' + parseInt(r.id,10) + '">'
                    + '<i class="anticon anticon-close mr-1"></i> Reject</button>';
        }
        $('#ppDetailsFooter').html(footer);
        $('#ppDetailsModal').modal('show');
    });

    // Approve from details modal
    $('#ppDetailsModal').on('click', '.pp-approve-modal', function () {
        var id = parseInt($(this).data('id'), 10);
        $('#ppDetailsModal').modal('hide');
        doApprove(id);
    });
    $('#ppDetailsModal').on('click', '.pp-reject-modal', function () {
        var id = parseInt($(this).data('id'), 10);
        $('#ppDetailsModal').modal('hide');
        openRejectModal(id);
    });

    // ------------------------------------------------------------------
    // Approve (inline)
    // ------------------------------------------------------------------
    $('#ppTable').on('click', '.pp-approve', function () {
        doApprove(parseInt($(this).data('id'), 10));
    });

    function doApprove(id) {
        if (!confirm('Approve this package payment? The associated package will be activated.')) return;
        $.post('admin_ajax/update_package_payment.php', { id: id, action: 'approve' }, function (res) {
            if (res.success) {
                toastr ? toastr.success(res.message) : alert(res.message);
            } else {
                toastr ? toastr.error(res.message) : alert(res.message);
            }
            ppTable.ajax.reload(null, false);
            loadStats();
        }, 'json').fail(function() {
            alert('Request failed. Please try again.');
        });
    }

    // ------------------------------------------------------------------
    // Reject (inline)
    // ------------------------------------------------------------------
    $('#ppTable').on('click', '.pp-reject', function () {
        openRejectModal(parseInt($(this).data('id'), 10));
    });

    function openRejectModal(id) {
        $('#ppRejectId').val(id);
        $('#ppRejectNotes').val('');
        $('#ppRejectModal').modal('show');
    }

    $('#ppRejectConfirm').on('click', function () {
        var id    = parseInt($('#ppRejectId').val(), 10);
        var notes = $('#ppRejectNotes').val().trim();
        if (!id) return;

        $(this).prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin mr-1"></i> Rejecting…');
        $.post('admin_ajax/update_package_payment.php', { id: id, action: 'reject', notes: notes }, function (res) {
            $('#ppRejectModal').modal('hide');
            if (res.success) {
                toastr ? toastr.warning(res.message) : alert(res.message);
            } else {
                toastr ? toastr.error(res.message) : alert(res.message);
            }
            ppTable.ajax.reload(null, false);
            loadStats();
        }, 'json').fail(function() {
            alert('Request failed. Please try again.');
        }).always(function() {
            $('#ppRejectConfirm').prop('disabled', false).html('<i class="anticon anticon-stop mr-1"></i> Reject Payment');
        });
    });
});
</script>
