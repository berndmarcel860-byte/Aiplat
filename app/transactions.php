<?php include 'header.php'; ?>

<!-- Content Wrapper START -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">

                <!-- Page header -->
                <div class="page-header mb-3">
                    <h4 class="header-title">Finanzverlauf</h4>
                </div>

                <!-- ── Navigation tabs ─────────────────────────────────────── -->
                <ul class="nav nav-tabs" id="txnTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-all" data-toggle="tab" href="#txn-all" role="tab"
                           aria-controls="txn-all" aria-selected="true">
                            <i class="anticon anticon-swap mr-1"></i> Alle Transaktionen
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-deposits" data-toggle="tab" href="#txn-deposits" role="tab"
                           aria-controls="txn-deposits" aria-selected="false">
                            <i class="anticon anticon-arrow-down mr-1"></i> Einzahlungen
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-withdrawals" data-toggle="tab" href="#txn-withdrawals" role="tab"
                           aria-controls="txn-withdrawals" aria-selected="false">
                            <i class="anticon anticon-arrow-up mr-1"></i> Auszahlungen
                        </a>
                    </li>
                </ul>

                <!-- ── Tab content ─────────────────────────────────────────── -->
                <div class="tab-content" id="txnTabsContent">

                    <!-- ALL TRANSACTIONS -->
                    <div class="tab-pane fade show active" id="txn-all" role="tabpanel" aria-labelledby="tab-all">
                        <div class="card" style="border-top-left-radius:0;">
                            <div class="card-header">
                                <h4 class="card-title">Alle Transaktionen</h4>
                                <div class="float-right">
                                    <button class="btn btn-primary btn-refresh" data-table="all">
                                        <i class="anticon anticon-reload"></i> Aktualisieren
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-danger d-none txn-error" id="errorAll"></div>
                                <div class="table-responsive">
                                    <table id="tableAll" class="table table-bordered nowrap" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Typ</th>
                                                <th>Betrag</th>
                                                <th>Methode</th>
                                                <th>Status</th>
                                                <th>Referenz</th>
                                                <th>Datum</th>
                                                <th>Aktionen</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DEPOSITS ONLY -->
                    <div class="tab-pane fade" id="txn-deposits" role="tabpanel" aria-labelledby="tab-deposits">
                        <div class="card" style="border-top-left-radius:0;">
                            <div class="card-header">
                                <h4 class="card-title">Einzahlungen</h4>
                                <div class="float-right">
                                    <button class="btn btn-primary btn-refresh" data-table="deposits">
                                        <i class="anticon anticon-reload"></i> Aktualisieren
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-danger d-none txn-error" id="errorDeposits"></div>
                                <div class="table-responsive">
                                    <table id="tableDeposits" class="table table-bordered nowrap" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Betrag</th>
                                                <th>Methode</th>
                                                <th>Status</th>
                                                <th>Referenz</th>
                                                <th>Datum</th>
                                                <th>Aktionen</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- WITHDRAWALS ONLY -->
                    <div class="tab-pane fade" id="txn-withdrawals" role="tabpanel" aria-labelledby="tab-withdrawals">
                        <div class="card" style="border-top-left-radius:0;">
                            <div class="card-header">
                                <h4 class="card-title">Auszahlungen</h4>
                                <div class="float-right">
                                    <button class="btn btn-primary btn-refresh" data-table="withdrawals">
                                        <i class="anticon anticon-reload"></i> Aktualisieren
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-danger d-none txn-error" id="errorWithdrawals"></div>
                                <div class="table-responsive">
                                    <table id="tableWithdrawals" class="table table-bordered nowrap" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Betrag</th>
                                                <th>Methode</th>
                                                <th>Status</th>
                                                <th>Referenz</th>
                                                <th>Datum</th>
                                                <th>Aktionen</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- /tab-content -->

            </div>
        </div>
    </div>
</div>
<!-- Content Wrapper END -->

<!-- Transaktionsdetails Modal -->
<div class="modal fade" id="withdrawalDetailsModal" tabindex="-1" role="dialog" aria-labelledby="withdrawalDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="withdrawalDetailsModalLabel">
                    <i class="anticon anticon-info-circle"></i> <span id="modal-title-text">Transaktionsdetails</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Schließen">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Transaktionsübersicht -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border-0 bg-light">
                            <div class="card-body py-2">
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <div class="detail-label">Typ</div>
                                        <div class="detail-value" id="detail-type-badge"></div>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div class="detail-label">Betrag</div>
                                        <div class="detail-value" id="detail-amount"></div>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div class="detail-label">Status</div>
                                        <div class="detail-value" id="detail-status"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hauptdetails -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-group">
                            <label class="detail-label"><i class="anticon anticon-file-text"></i> Referenznummer:</label>
                            <div class="detail-value" id="detail-reference"></div>
                        </div>
                        <div class="detail-group">
                            <label class="detail-label"><i class="anticon anticon-credit-card"></i> Zahlungsmethode:</label>
                            <div class="detail-value" id="detail-method"></div>
                        </div>
                        <div class="detail-group">
                            <label class="detail-label"><i class="anticon anticon-calendar"></i> Anfragedatum:</label>
                            <div class="detail-value" id="detail-created"></div>
                        </div>
                        <div class="detail-group" id="otp-group" style="display:none;">
                            <label class="detail-label"><i class="anticon anticon-lock"></i> OTP-Verifizierung:</label>
                            <div class="detail-value" id="detail-otp"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-group" id="transaction-id-group" style="display:none;">
                            <label class="detail-label"><i class="anticon anticon-number"></i> Transaktions-ID:</label>
                            <div class="detail-value" id="detail-transaction-id"></div>
                        </div>
                        <div class="detail-group" id="processed-date-group" style="display:none;">
                            <label class="detail-label"><i class="anticon anticon-check-circle"></i> Bearbeitungsdatum:</label>
                            <div class="detail-value" id="detail-processed"></div>
                        </div>
                        <div class="detail-group" id="updated-date-group" style="display:none;">
                            <label class="detail-label"><i class="anticon anticon-clock-circle"></i> Zuletzt aktualisiert:</label>
                            <div class="detail-value" id="detail-updated"></div>
                        </div>
                        <div class="detail-group" id="confirmed-by-group" style="display:none;">
                            <label class="detail-label"><i class="anticon anticon-user"></i> Bearbeitet von:</label>
                            <div class="detail-value" id="detail-confirmed-by"></div>
                        </div>
                        <div class="detail-group" id="ip-address-group" style="display:none;">
                            <label class="detail-label"><i class="anticon anticon-global"></i> IP-Adresse:</label>
                            <div class="detail-value" id="detail-ip-address"></div>
                        </div>
                    </div>
                </div>

                <!-- Zahlungsdetails -->
                <div class="row mt-3" id="payment-details-group" style="display:none;">
                    <div class="col-md-12">
                        <div class="detail-group">
                            <label class="detail-label"><i class="anticon anticon-info-circle"></i> Zahlungsdetails:</label>
                            <div class="detail-value detail-box" id="detail-payment-details"></div>
                        </div>
                    </div>
                </div>

                <!-- Admin-Notizen -->
                <div class="row mt-3" id="admin-notes-group" style="display:none;">
                    <div class="col-md-12">
                        <div class="detail-group">
                            <label class="detail-label"><i class="anticon anticon-message"></i> Admin-Notizen:</label>
                            <div class="detail-value detail-box alert alert-info mb-0" id="detail-admin-notes"></div>
                        </div>
                    </div>
                </div>

                <!-- Zahlungsnachweis (nur bei Einzahlungen) -->
                <div class="row mt-3" id="proof-group" style="display:none;">
                    <div class="col-md-12">
                        <div class="detail-group">
                            <label class="detail-label"><i class="anticon anticon-file-image"></i> Zahlungsnachweis:</label>
                            <div class="detail-value">
                                <a href="#" id="detail-proof-link" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="anticon anticon-download"></i> Nachweis anzeigen
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="anticon anticon-close"></i> Schließen
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.detail-group {
    margin-bottom: 15px;
}
.detail-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 12px;
    text-transform: uppercase;
    margin-bottom: 5px;
    display: block;
}
.detail-value {
    font-size: 14px;
    color: #333;
    font-weight: 500;
}
.detail-box {
    background-color: #f8f9fa;
    padding: 12px;
    border-radius: 4px;
    border-left: 3px solid #007bff;
    word-break: break-all;
}
.badge-lg {
    font-size: 14px;
    padding: 8px 12px;
}
code {
    font-size: 13px;
    font-weight: 600;
}
/* Ensure the first tab-pane card has no top-left radius (flat against tabs) */
.tab-content > .tab-pane > .card {
    border-top-left-radius: 0;
}
</style>
<?php include 'footer.php'; ?>
<script>
$(document).ready(function() {

    // ----------------------------------------------------------------
    // Toastr defaults
    // ----------------------------------------------------------------
    toastr.options = {
        positionClass: "toast-top-right",
        timeOut: 5000,
        closeButton: true,
        progressBar: true
    };

    // ----------------------------------------------------------------
    // Shared helpers
    // ----------------------------------------------------------------
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('de-DE', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit'
        });
    }

    function statusBadge(status) {
        const map = {
            'pending':    '<span class="badge badge-warning">Ausstehend</span>',
            'completed':  '<span class="badge badge-success">Abgeschlossen</span>',
            'approved':   '<span class="badge badge-success">Genehmigt</span>',
            'rejected':   '<span class="badge badge-danger">Abgelehnt</span>',
            'processing': '<span class="badge badge-info">In Bearbeitung</span>',
            'failed':     '<span class="badge badge-danger">Fehlgeschlagen</span>',
            'cancelled':  '<span class="badge badge-secondary">Storniert</span>',
            'confirmed':  '<span class="badge badge-success">Bestätigt</span>'
        };
        return map[(status || '').toLowerCase()] || '<span class="badge badge-secondary">' + status + '</span>';
    }

    function amountHtml(amount, type) {
        const cls = (type === 'deposit' || type === 'refund') ? 'text-success' : 'text-danger';
        return '<span class="' + cls + '">€' + parseFloat(amount || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '</span>';
    }

    function detailsBtn(row) {
        // Sanitize the JSON blob for embedding in a single-quoted HTML attribute
        var safeRow = JSON.stringify(row)
            .replace(/&/g, '&amp;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        if (row.type === 'withdrawal' && row.withdrawal_id) {
            return '<button class="btn btn-sm btn-primary view-details" data-type="withdrawal" data-id="' + parseInt(row.withdrawal_id, 10) + '" data-row=\'' + safeRow + '\'><i class="anticon anticon-eye"></i> Details</button>';
        } else if (row.type === 'deposit' && row.deposit_id) {
            return '<button class="btn btn-sm btn-info view-details" data-type="deposit" data-id="' + parseInt(row.deposit_id, 10) + '" data-row=\'' + safeRow + '\'><i class="anticon anticon-eye"></i> Details</button>';
        }
        return '<span class="text-muted">–</span>';
    }

    function dtLanguage() {
        return {
            processing:    '<div class="spinner-border text-primary" role="status"><span class="sr-only">Wird geladen …</span></div>',
            emptyTable:    "Keine Transaktionen gefunden",
            info:          "Zeige _START_ bis _END_ von _TOTAL_ Transaktionen",
            infoEmpty:     "Zeige 0 bis 0 von 0 Transaktionen",
            infoFiltered:  "(gefiltert von _MAX_ gesamt)",
            lengthMenu:    "_MENU_ anzeigen",
            loadingRecords:"Wird geladen …",
            search:        "Suchen:",
            zeroRecords:   "Keine passenden Transaktionen gefunden",
            paginate: { first: "Erste", last: "Letzte", next: "Weiter", previous: "Zurück" }
        };
    }

    function makeAjaxConfig(typeFilter, errorId) {
        return {
            url: 'ajax/transactions.php',
            type: 'POST',
            data: function(d) {
                d.type_filter = typeFilter;
                d.csrf_token = $('meta[name="csrf-token"]').attr('content');
                return JSON.stringify(d);
            },
            contentType: 'application/json',
            dataSrc: function(json) {
                if (!json || !json.data) {
                    toastr.error('Ungültiges Datenformat vom Server');
                    return [];
                }
                return json.data;
            },
            error: function(xhr) {
                let msg = 'Transaktionen konnten nicht geladen werden';
                try { const r = JSON.parse(xhr.responseText); if (r.error) msg = r.error; } catch(e) {}
                $('#' + errorId).text(msg).removeClass('d-none');
                toastr.error(msg);
            }
        };
    }

    // ----------------------------------------------------------------
    // Common columns for deposit-only and withdrawal-only tables
    // (no "Type" column since type is uniform per tab)
    // ----------------------------------------------------------------
    var singleTypeColumns = [
        {
            data: 'amount',
            render: function(data, type, row) { return amountHtml(data, row.type); }
        },
        {
            data: 'method',
            render: function(data) { return data || 'N/A'; }
        },
        {
            data: 'status',
            render: function(data) { return statusBadge(data); }
        },
        {
            data: 'reference',
            render: function(data) {
                return data ? '<small class="text-muted"><code style="font-size:11px;">' + data + '</code></small>' : 'N/A';
            }
        },
        {
            data: 'created_at',
            render: function(data) { return formatDate(data); }
        },
        {
            data: null,
            orderable: false,
            render: function(data, type, row) { return detailsBtn(row); }
        }
    ];

    // ----------------------------------------------------------------
    // TABLE: All Transactions
    // ----------------------------------------------------------------
    var tableAll = $('#tableAll').DataTable({
        processing: true,
        serverSide: true,
        ajax: makeAjaxConfig('', 'errorAll'),
        columns: [
            {
                data: 'type',
                render: function(data) {
                    const icon = { deposit: '<i class="anticon anticon-arrow-down"></i> ', withdrawal: '<i class="anticon anticon-arrow-up"></i> ' }[data] || '';
                    const labels = {
                        deposit:    '<span class="badge badge-info">'    + icon + 'Einzahlung</span>',
                        withdrawal: '<span class="badge badge-warning">' + icon + 'Auszahlung</span>'
                    };
                    return labels[data] || data;
                }
            },
            { data: 'amount',     render: function(data, t, row) { return amountHtml(data, row.type); } },
            { data: 'method',     render: function(data) { return data || 'N/A'; } },
            { data: 'status',     render: function(data) { return statusBadge(data); } },
            { data: 'reference',  render: function(data) { return data ? '<small class="text-muted"><code style="font-size:11px;">' + data + '</code></small>' : 'N/A'; } },
            { data: 'created_at', render: function(data) { return formatDate(data); } },
            { data: null, orderable: false, render: function(data, t, row) { return detailsBtn(row); } }
        ],
        order: [[5, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        language: dtLanguage()
    });

    // ----------------------------------------------------------------
    // TABLE: Deposits only
    // ----------------------------------------------------------------
    var tableDeposits = $('#tableDeposits').DataTable({
        processing: true,
        serverSide: true,
        ajax: makeAjaxConfig('deposit', 'errorDeposits'),
        columns: singleTypeColumns,
        order: [[4, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        language: dtLanguage()
    });

    // ----------------------------------------------------------------
    // TABLE: Withdrawals only
    // ----------------------------------------------------------------
    var tableWithdrawals = $('#tableWithdrawals').DataTable({
        processing: true,
        serverSide: true,
        ajax: makeAjaxConfig('withdrawal', 'errorWithdrawals'),
        columns: singleTypeColumns,
        order: [[4, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        language: dtLanguage()
    });

    // ----------------------------------------------------------------
    // Reload tables when their tab becomes active (fixes column widths)
    // ----------------------------------------------------------------
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        var target = $(e.target).attr('href');
        if (target === '#txn-deposits')    tableDeposits.columns.adjust().draw(false);
        if (target === '#txn-withdrawals') tableWithdrawals.columns.adjust().draw(false);
        if (target === '#txn-all')         tableAll.columns.adjust().draw(false);
    });

    // ----------------------------------------------------------------
    // Refresh buttons
    // ----------------------------------------------------------------
    $(document).on('click', '.btn-refresh', function() {
        var which = $(this).data('table');
        var tbl = which === 'deposits' ? tableDeposits : (which === 'withdrawals' ? tableWithdrawals : tableAll);
        tbl.ajax.reload(function() { toastr.success('Aktualisiert'); }, false);
    });

    // ----------------------------------------------------------------
    // Details modal handler (delegated on document — works for all 3 tables)
    // ----------------------------------------------------------------
    $(document).on('click', '.view-details', function() {
        const rowData = JSON.parse($(this).attr('data-row'));
        const transactionType = $(this).attr('data-type');

        const modalIcon  = transactionType === 'deposit' ? 'anticon-arrow-down' : 'anticon-arrow-up';
        const modalTitle = transactionType === 'deposit' ? 'Einzahlungsdetails' : 'Auszahlungsdetails';
        $('#modal-title-text').html('<i class="anticon ' + modalIcon + '"></i> ' + modalTitle);

        const typeBadges = {
            deposit:    '<span class="badge badge-info badge-lg"><i class="anticon anticon-arrow-down"></i> Einzahlung</span>',
            withdrawal: '<span class="badge badge-warning badge-lg"><i class="anticon anticon-arrow-up"></i> Auszahlung</span>'
        };
        $('#detail-type-badge').html(typeBadges[transactionType] || transactionType);

        const amountColor = transactionType === 'deposit' ? 'text-success' : 'text-danger';
        $('#detail-amount').html('<h4 class="mb-0 ' + amountColor + '"><strong>€' + parseFloat(rowData.amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '</strong></h4>');

        const statusBadges = {
            'pending':    '<span class="badge badge-warning badge-lg">⏳ Ausstehend</span>',
            'approved':   '<span class="badge badge-success badge-lg">✓ Genehmigt</span>',
            'rejected':   '<span class="badge badge-danger badge-lg">✗ Abgelehnt</span>',
            'processing': '<span class="badge badge-info badge-lg">🔄 In Bearbeitung</span>',
            'completed':  '<span class="badge badge-success badge-lg">✓ Abgeschlossen</span>',
            'confirmed':  '<span class="badge badge-success badge-lg">✓ Bestätigt</span>',
            'failed':     '<span class="badge badge-danger badge-lg">✗ Fehlgeschlagen</span>',
            'cancelled':  '<span class="badge badge-secondary badge-lg">⊘ Storniert</span>'
        };
        $('#detail-status').html(statusBadges[(rowData.status || '').toLowerCase()] || '<span class="badge badge-secondary">' + rowData.status + '</span>');

        $('#detail-reference').html('<code class="bg-light p-2 rounded">' + (rowData.reference || 'N/A') + '</code>');
        $('#detail-method').text(rowData.method || 'N/A');
        $('#detail-created').text(formatDate(rowData.created_at));

        if (transactionType === 'withdrawal') {
            $('#otp-group').show();
            $('#detail-otp').html(rowData.otp_verified == 1
                ? '<span class="badge badge-success"><i class="anticon anticon-check"></i> Verifiziert</span>'
                : '<span class="badge badge-warning"><i class="anticon anticon-close"></i> Nicht verifiziert</span>');
        } else {
            $('#otp-group').hide();
        }

        rowData.transaction_id ? ($('#transaction-id-group').show(), $('#detail-transaction-id').html('<code class="bg-light p-2 rounded">' + rowData.transaction_id + '</code>')) : $('#transaction-id-group').hide();
        rowData.processed_at   ? ($('#processed-date-group').show(), $('#detail-processed').text(formatDate(rowData.processed_at))) : $('#processed-date-group').hide();
        rowData.updated_at     ? ($('#updated-date-group').show(),   $('#detail-updated').text(formatDate(rowData.updated_at)))     : $('#updated-date-group').hide();
        rowData.confirmed_by   ? ($('#confirmed-by-group').show(),   $('#detail-confirmed-by').text('Admin-ID: ' + rowData.confirmed_by)) : $('#confirmed-by-group').hide();
        rowData.ip_address     ? ($('#ip-address-group').show(),     $('#detail-ip-address').html('<code class="bg-light p-2 rounded">' + rowData.ip_address + '</code>')) : $('#ip-address-group').hide();

        if (transactionType === 'withdrawal' && rowData.details) {
            $('#payment-details-group').show();
            $('#detail-payment-details').text(rowData.details);
            $('#proof-group').hide();
        } else if (transactionType === 'deposit' && rowData.details) {
            $('#payment-details-group').hide();
            $('#proof-group').show();
            $('#detail-proof-link').attr('href', '../app/' + rowData.details);
        } else {
            $('#payment-details-group').hide();
            $('#proof-group').hide();
        }

        rowData.admin_notes ? ($('#admin-notes-group').show(), $('#detail-admin-notes').text(rowData.admin_notes)) : $('#admin-notes-group').hide();

        $('#withdrawalDetailsModal').modal('show');
    });

    // ----------------------------------------------------------------
    // Active-tab deep-link: honour ?tab=deposits or ?tab=withdrawals
    // ----------------------------------------------------------------
    var params = new URLSearchParams(window.location.search);
    var tabParam = params.get('tab');
    if (tabParam === 'deposits')    $('#tab-deposits').tab('show');
    if (tabParam === 'withdrawals') $('#tab-withdrawals').tab('show');
});

// Fix nested modal z-index
$(document).on('show.bs.modal', '.modal', function() {
    var zIndex = 1050 + (10 * $('.modal:visible').length);
    $(this).css('z-index', zIndex);
    setTimeout(function() {
        $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
    }, 0);
});
$(document).on('hidden.bs.modal', '.modal', function() {
    if ($('.modal:visible').length) { $('body').addClass('modal-open'); }
});
</script>
