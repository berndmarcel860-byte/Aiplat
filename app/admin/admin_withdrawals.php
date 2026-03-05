<?php 
require_once 'admin_header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Withdrawal Management</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="admin_dashboard.php" class="breadcrumb-item"><i class="anticon anticon-home"></i> Dashboard</a>
                <span class="breadcrumb-item active">Withdrawals</span>
            </nav>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5>Withdrawal Requests</h5>
                <div class="btn-group">
                    <button class="btn btn-success" data-toggle="modal" data-target="#addWithdrawalModal">
                        <i class="anticon anticon-plus"></i> Add Withdrawal
                    </button>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#filterWithdrawalsModal">
                        <i class="anticon anticon-filter"></i> Filter
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="withdrawalsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- AJAX will populate this -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterWithdrawalsModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filter Withdrawals</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="filterWithdrawalsForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Currency Type</label>
                        <select class="form-control" name="currency_type">
                            <option value="">All Types</option>
                            <option value="fiat">Fiat (USD)</option>
                            <option value="crypto">Cryptocurrency</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select class="form-control" name="method_code">
                            <option value="">All Methods</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="paypal">PayPal</option>
                            <option value="crypto">Cryptocurrency</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date Range</label>
                        <div class="input-daterange input-group" data-provide="datepicker">
                            <input type="text" class="form-control" name="start_date">
                            <span class="input-group-addon">to</span>
                            <input type="text" class="form-control" name="end_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Withdrawal Modal -->
<div class="modal fade" id="addWithdrawalModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Withdrawal for User</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <form id="addWithdrawalForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select User <span class="text-danger">*</span></label>
                        <select class="form-control" name="user_id" required>
                            <option value="">-- Select User --</option>
                        </select>
                        <small class="form-text text-muted">Select the user for this withdrawal</small>
                    </div>
                    <div class="form-group">
                        <label>Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required>
                        <small class="form-text text-muted">Enter withdrawal amount</small>
                    </div>
                    <div class="form-group">
                        <label>Payment Method <span class="text-danger">*</span></label>
                        <select class="form-control" name="method_code" required>
                            <option value="">-- Select Method --</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="paypal">PayPal</option>
                            <option value="crypto">Cryptocurrency</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Payment Details</label>
                        <textarea class="form-control" name="payment_details" rows="3" placeholder="Bank account, PayPal email, crypto address, etc."></textarea>
                        <small class="form-text text-muted">Enter payment details for this withdrawal</small>
                    </div>
                    <div class="form-group">
                        <label>Admin Notes</label>
                        <textarea class="form-control" name="admin_notes" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="anticon anticon-plus"></i> Create Withdrawal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Withdrawal Details Modal -->
<div class="modal fade" id="withdrawalDetailsModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header align-items-center">
                <h5 class="modal-title">Withdrawal Details</h5>
                <div class="ml-auto">
                    <!-- NEW: Refresh logs in-place -->
                    <button type="button" class="btn btn-light btn-sm mr-2" id="refreshLogsBtn" title="Logs aktualisieren">
                        <i class="anticon anticon-reload"></i>
                    </button>
                    <!-- Info Button opens a tiny info modal showing last send result -->
                    <button type="button" class="btn btn-light btn-sm" id="openSendInfo">
                        <i class="anticon anticon-info-circle"></i> Info
                    </button>
                    <button type="button" class="close" data-dismiss="modal">
                        <i class="anticon anticon-close"></i>
                    </button>
                </div>
            </div>

            <div class="modal-body">
                <div id="withdrawalDetailsContent"><!-- AJAX will populate this --></div>

                <!-- NEW: Logs table (PDF/email attempts) -->
                <hr>
                <h6 class="mb-2"><i class="anticon anticon-file-pdf"></i> Dokumente &amp; Versand-Logs</h6>
                <div class="table-responsive">
                    <table id="payoutLogsTable" class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Erstellt</th>
                                <th>Status</th>
                                <th>Empfänger</th>
                                <th>Betreff</th>
                                <th>PDF</th>
                                <th>Gesendet</th>
                                <th>Fehler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="8" class="text-center text-muted">Keine Einträge</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer d-flex w-100 justify-content-between">
                <div>
                    <button type="button" class="btn btn-success approve-withdrawal">Approve</button>
                    <button type="button" class="btn btn-danger reject-withdrawal">Reject</button>
                </div>
                <div>
                    <!-- New: Send payout confirmation -->
                    <button type="button" class="btn btn-info send-payout-confirmation">
                        <span class="spinner-border spinner-border-sm d-none mr-1" id="sendSpinModal" role="status" aria-hidden="true"></span>
                        <i class="anticon anticon-mail"></i> Bestätigung senden
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>

            <!-- Inline result box -->
            <div class="px-4 pb-3 d-none" id="sendResultBox">
                <div class="alert alert-secondary mb-0" id="sendResultMsg"></div>
            </div>
        </div>
    </div>
</div>

<!-- Send Info Modal (populated after send) -->
<div class="modal fade" id="sendInfoModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Versand-Info</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body" id="sendInfoBody">
                <!-- Filled after sending -->
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-outline-primary d-none" target="_blank" id="openPdfLink">
                    <i class="anticon anticon-file-pdf"></i> PDF öffnen
                </a>
                <button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const withdrawalsTable = $('#withdrawalsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax/get_withdrawals.php',
            type: 'POST'
        },
        columns: [
            { data: 'id' },
            { 
                data: null,
                render: function(data) {
                    return (data.user_first_name || '') + ' ' + (data.user_last_name || '');
                }
            },
            { 
                data: 'amount',
                render: function(data) {
                    return '€' + parseFloat(data || 0).toFixed(2);
                }
            },
            { data: 'method_name' },
            { 
                data: 'status',
                render: function(data) {
                    const map = { pending:'warning', processing:'info', completed:'success', failed:'danger', cancelled:'secondary' };
                    const cls = map[data] || 'default';
                    const label = (data||'').charAt(0).toUpperCase() + (data||'').slice(1);
                    return `<span class="badge badge-${cls}">${label}</span>`;
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    return data ? new Date(data).toLocaleString() : '';
                }
            },
            {
                data: 'id',
                orderable: false,
                render: function(data, type, row) {
                    let buttons = `
                        <button class="btn btn-sm btn-primary view-withdrawal" data-id="${data}" title="Details">
                            <i class="anticon anticon-eye"></i>
                        </button>
                    `;
                    if (row.status === 'pending' || row.status === 'processing') {
                        buttons += `
                            <button class="btn btn-sm btn-success approve-withdrawal" data-id="${data}" title="Approve">
                                <i class="anticon anticon-check"></i>
                            </button>
                            <button class="btn btn-sm btn-danger reject-withdrawal" data-id="${data}" title="Reject">
                                <i class="anticon anticon-close"></i>
                            </button>
                        `;
                    }
                    // Neuer Schnell-Button: Bestätigung senden
                    buttons += `
                        <button class="btn btn-sm btn-info send-payout-confirmation" data-id="${data}" title="Bestätigung senden">
                            <i class="anticon anticon-mail"></i>
                        </button>
                    `;
                    return `<div class="btn-group">${buttons}</div>`;
                }
            }
        ]
    });

    // State
    let currentWithdrawalId = null;
    let lastSendResponse = null;

    // View Withdrawal Details (and load logs)
    $('#withdrawalsTable').on('click', '.view-withdrawal', function() {
        currentWithdrawalId = $(this).data('id');
        $('#sendResultBox').addClass('d-none');
        $('#sendResultMsg').empty();

        // details
        $.ajax({
            url: 'admin_ajax/get_withdrawal.php',
            type: 'GET',
            dataType: 'json',
            data: { id: currentWithdrawalId },
            success: function(response) {
                if (response && response.success) {
                    const w = response.withdrawal || {};
                    $('#withdrawalDetailsContent').html(`
                        <div class="form-group">
                            <label>Transaction ID</label>
                            <p>${w.id}</p>
                        </div>
                        <div class="form-group">
                            <label>User</label>
                            <p>${(w.user_first_name||'')} ${(w.user_last_name||'')}</p>
                        </div>
                        <div class="form-group">
                            <label>Amount</label>
                            <p>€${parseFloat(w.amount||0).toFixed(2)}</p>
                        </div>
                        <div class="form-group">
                            <label>Payment Method</label>
                            <p>${w.method_name || '—'}</p>
                        </div>
                        <div class="form-group">
                            <label>Payment Details</label>
                            <p>${w.payment_details ? escapeHtml(w.payment_details) : 'N/A'}</p>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <p>${capitalize(w.status||'')}</p>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <p>${w.created_at ? new Date(w.created_at).toLocaleString() : ''}</p>
                        </div>
                        <div class="form-group">
                            <label>Reference</label>
                            <p>${w.reference || 'N/A'}</p>
                        </div>
                        <div class="form-group">
                            <label>Admin Notes</label>
                            <p>${w.admin_notes ? escapeHtml(w.admin_notes) : 'N/A'}</p>
                        </div>
                    `);

                    // buttons state
                    if (w.status === 'pending' || w.status === 'processing') {
                        $('.approve-withdrawal, .reject-withdrawal').show();
                    } else {
                        $('.approve-withdrawal, .reject-withdrawal').hide();
                    }

                    // logs
                    loadPayoutLogs(currentWithdrawalId);

                    $('#withdrawalDetailsModal').modal('show');
                } else {
                    toastr.error(response && response.message ? response.message : 'Failed to load details');
                }
            },
            error: function() {
                toastr.error('Server error loading details');
            }
        });
    });

    // Refresh logs button
    $('#refreshLogsBtn').on('click', function(){
        if (currentWithdrawalId) loadPayoutLogs(currentWithdrawalId);
    });

    // Load logs table (PDF/email attempts)
    function loadPayoutLogs(withdrawalId){
        $('#payoutLogsTable tbody').html('<tr><td colspan="8" class="text-center text-muted">Laden…</td></tr>');
        $.ajax({
            url: 'admin_ajax/get_payout_confirmation_logs.php',
            type: 'GET',
            dataType: 'json',
            data: { withdrawal_id: withdrawalId },
            success: function(resp){
                if (!(resp && resp.success)) {
                    $('#payoutLogsTable tbody').html('<tr><td colspan="8" class="text-center text-danger">Fehler beim Laden</td></tr>');
                    return;
                }
                const rows = resp.logs || [];
                if (!rows.length) {
                    $('#payoutLogsTable tbody').html('<tr><td colspan="8" class="text-center text-muted">Keine Einträge</td></tr>');
                    return;
                }
                const html = rows.map((r, i) => {
                    const clsMap = { queued:'warning', sent:'success', failed:'danger' };
                    const st = (r.status || '').toLowerCase();
                    const badge = `<span class="badge badge-${clsMap[st] || 'secondary'}">${(r.status||'').toUpperCase()}</span>`;
                    const pdf = r.pdf_path ? `<a href="${r.pdf_path}" target="_blank"><i class="anticon anticon-file-pdf"></i> öffnen</a>` : '—';
                    const sent = r.sent_at ? new Date(r.sent_at).toLocaleString() : '—';
                    const created = r.created_at ? new Date(r.created_at).toLocaleString() : '—';
                    const err = r.error_message ? escapeHtml(r.error_message) : '—';
                    return `<tr>
                        <td>${r.id ?? (i+1)}</td>
                        <td>${created}</td>
                        <td>${badge}</td>
                        <td>${escapeHtml(r.email_to || '')}</td>
                        <td>${escapeHtml(r.subject || '')}</td>
                        <td>${pdf}</td>
                        <td>${sent}</td>
                        <td>${err}</td>
                    </tr>`;
                }).join('');
                $('#payoutLogsTable tbody').html(html);
            },
            error: function(){
                $('#payoutLogsTable tbody').html('<tr><td colspan="8" class="text-center text-danger">Serverfehler</td></tr>');
            }
        });
    }

    // Approve (in Modal Footer static buttons)
    $('.approve-withdrawal').click(function() {
        if (!currentWithdrawalId) { toastr.error('No withdrawal selected'); return; }
        if (!confirm('Are you sure you want to approve this withdrawal?')) return;

        $.ajax({
            url: 'admin_ajax/approve_withdrawal.php',
            type: 'POST',
            dataType: 'json',
            data: { id: currentWithdrawalId },
            success: function(resp) {
                if (resp && resp.success) {
                    toastr.success(resp.message || 'Approved');
                    withdrawalsTable.ajax.reload(null, false);
                    $('#withdrawalDetailsModal').modal('hide');
                } else {
                    toastr.error(resp && resp.message ? resp.message : 'Approve failed');
                }
            },
            error: function(){ toastr.error('Server error'); }
        });
    });

    // Reject (in Modal Footer static buttons)
    $('.reject-withdrawal').click(function() {
        if (!currentWithdrawalId) { toastr.error('No withdrawal selected'); return; }
        const reason = prompt('Please enter the rejection reason:');
        if (reason === null) return;

        $.ajax({
            url: 'admin_ajax/reject_withdrawal.php',
            type: 'POST',
            dataType: 'json',
            data: { id: currentWithdrawalId, reason: reason },
            success: function(resp) {
                if (resp && resp.success) {
                    toastr.success(resp.message || 'Rejected');
                    withdrawalsTable.ajax.reload(null, false);
                    $('#withdrawalDetailsModal').modal('hide');
                } else {
                    toastr.error(resp && resp.message ? resp.message : 'Reject failed');
                }
            },
            error: function(){ toastr.error('Server error'); }
        });
    });

    // Global handler for "Bestätigung senden" (table action & modal footer)
    $('#withdrawalsTable').on('click', '.send-payout-confirmation', function() {
        const id = $(this).data('id');
        triggerSendPayout(id);
    });
    $('#withdrawalDetailsModal').on('click', '.send-payout-confirmation', function() {
        if (!currentWithdrawalId) { toastr.error('No withdrawal selected'); return; }
        triggerSendPayout(currentWithdrawalId, { inModal: true });
    });

    // Open Info modal
    $('#openSendInfo').on('click', function() {
        if (!lastSendResponse) {
            $('#sendInfoBody').html('<div class="alert alert-info mb-0">Noch keine Versandaktion in dieser Sitzung. Senden Sie zuerst eine Bestätigung.</div>');
            $('#openPdfLink').addClass('d-none').attr('href', '#');
        } else {
            fillSendInfo(lastSendResponse);
        }
        $('#sendInfoModal').modal('show');
    });

    // Filter submit
    $('#filterWithdrawalsForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        withdrawalsTable.ajax.url('admin_ajax/get_withdrawals.php?' + formData).load();
        $('#filterWithdrawalsModal').modal('hide');
    });

    // Load users when Add Withdrawal modal opens
    $('#addWithdrawalModal').on('show.bs.modal', function() {
        $.ajax({
            url: 'admin_ajax/get_users_for_select.php',
            type: 'GET',
            dataType: 'json',
            success: function(resp) {
                if (resp && resp.success) {
                    const select = $('#addWithdrawalForm select[name="user_id"]');
                    select.find('option:not(:first)').remove();
                    (resp.users || []).forEach(function(user) {
                        select.append(`<option value="${user.id}">${user.first_name} ${user.last_name} (${user.email})</option>`);
                    });
                }
            }
        });
    });

    // Add Withdrawal Form Submit
    $('#addWithdrawalForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        $.ajax({
            url: 'admin_ajax/add_withdrawal.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(resp) {
                if (resp && resp.success) {
                    toastr.success(resp.message || 'Withdrawal created successfully');
                    $('#addWithdrawalModal').modal('hide');
                    $('#addWithdrawalForm')[0].reset();
                    withdrawalsTable.ajax.reload();
                } else {
                    toastr.error(resp && resp.message ? resp.message : 'Failed to create withdrawal');
                }
            },
            error: function() {
                toastr.error('Server error');
            }
        });
    });

    // ------------ Helpers (JS) --------------
    function triggerSendPayout(withdrawalId, opts) {
        opts = opts || {};
        if (!withdrawalId) { toastr.error('No withdrawal selected'); return; }

        // UI lock/spinner
        if (opts.inModal) {
            $('#sendSpinModal').removeClass('d-none');
            $('.send-payout-confirmation').prop('disabled', true);
        }

        $.ajax({
            url: 'admin_ajax/send_payout_confirmation.php',
            type: 'POST',
            dataType: 'json',
            data: { id: withdrawalId },
            success: function(resp) {
                lastSendResponse = resp || null;

                if (opts.inModal) {
                    $('#sendSpinModal').addClass('d-none');
                    $('.send-payout-confirmation').prop('disabled', false);
                }

                if (resp && resp.success) {
                    toastr.success(resp.message || 'E-Mail gesendet.');
                    // Inline result in modal
                    if (opts.inModal) {
                        $('#sendResultBox').removeClass('d-none');
                        const pdfLink = resp.pdf ? `<a href="${resp.pdf}" target="_blank">PDF öffnen</a>` : '';
                        $('#sendResultMsg').html(`✅ Versand erfolgreich. ${pdfLink}`);
                    }
                    // Refresh logs live in the same modal
                    if (currentWithdrawalId === withdrawalId) {
                        loadPayoutLogs(withdrawalId);
                    }
                    // Also show info modal content
                    fillSendInfo(resp);
                    $('#sendInfoModal').modal('show');
                } else {
                    const msg = (resp && resp.message) ? resp.message : 'Versand fehlgeschlagen.';
                    toastr.error(msg);
                    if (opts.inModal) {
                        $('#sendResultBox').removeClass('d-none');
                        $('#sendResultMsg').html(`❌ ${escapeHtml(msg)}`);
                    }
                    if (currentWithdrawalId === withdrawalId) {
                        loadPayoutLogs(withdrawalId);
                    }
                }
            },
            error: function() {
                if (opts.inModal) {
                    $('#sendSpinModal').addClass('d-none');
                    $('.send-payout-confirmation').prop('disabled', false);
                    $('#sendResultBox').removeClass('d-none');
                    $('#sendResultMsg').text('❌ Serverfehler beim Senden.');
                }
                toastr.error('Serverfehler beim Senden.');
            }
        });
    }

    function fillSendInfo(resp) {
        const ok = !!(resp && resp.success);
        const pdf = resp && resp.pdf ? resp.pdf : null;
        const message = resp && resp.message ? resp.message : (ok ? 'Gesendet' : 'Fehlgeschlagen');

        const html = `
            <div class="mb-3 ${ok ? 'text-success' : 'text-danger'}">
                <strong>${escapeHtml(message)}</strong>
            </div>
            <div class="small text-muted mb-2">Hinweis: Details zum Versand sind im Protokoll (payout_confirmation_logs / email_logs) gespeichert.</div>
            ${pdf ? `<div class="alert alert-light border"><i class="anticon anticon-file-pdf"></i> PDF: <a href="${pdf}" target="_blank">${pdf}</a></div>` : ''}
        `;
        $('#sendInfoBody').html(html);
        if (pdf) {
            $('#openPdfLink').removeClass('d-none').attr('href', pdf);
        } else {
            $('#openPdfLink').addClass('d-none').attr('href', '#');
        }
    }

    function escapeHtml(str){
        if (typeof str !== 'string') return str;
        return str.replace(/[&<>"'`=\/]/g, function(s) {
            return ({
                '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;'
            })[s];
        });
    }
    function capitalize(s){ if(!s) return ''; return s.charAt(0).toUpperCase()+s.slice(1); }
});
</script>