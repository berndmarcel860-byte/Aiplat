$(document).ready(function () {

    // ===============================================================
    // 🧾 WITHDRAWALS DATATABLE
    // ===============================================================
    const withdrawalsTable = $('#withdrawalsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "ajax/withdrawal.php",
            type: "POST",
            data: function (d) {
                d.csrf_token = $('meta[name="csrf-token"]').attr('content');
                return d;
            },
            dataSrc: function (json) {
                if (!json || !json.data) {
                    console.error('Invalid data format:', json);
                    $('#withdrawalError').text('Invalid data received from server').removeClass('d-none');
                    return [];
                }
                return json.data;
            },
            error: function (xhr) {
                let errorMsg = xhr.status === 401 ?
                    'Session expired. Please login again.' :
                    'Failed to load withdrawals. Please try again.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error) errorMsg = response.error;
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
                $('#withdrawalError').text(errorMsg).removeClass('d-none');
            }
        },
        columns: [
            { data: "reference", render: d => d ? `<small class="text-muted">${d}</small>` : 'N/A' },
            { data: "amount", render: d => d ? '$' + parseFloat(d).toFixed(2) : '$0.00' },
            {
                data: "method_code",
                render: d => {
                    if (!d) return 'N/A';
                    const methods = {
                        'bank_transfer': 'Bank Transfer',
                        'paypal': 'PayPal',
                        'bitcoin': 'Bitcoin',
                        'ethereum': 'Ethereum',
                        'credit_card': 'Credit Card',
                        'BANK_TRANSFER': 'Bank Transfer'
                    };
                    return methods[d.toLowerCase()] || d;
                }
            },
            {
                data: "status",
                render: d => {
                    if (!d) return '';
                    const cls = getStatusClass(d);
                    return `<span class="badge badge-${cls}">${d}</span>`;
                }
            },
            { data: "created_at", render: d => d ? new Date(d).toLocaleString() : '' },
            {
                data: "id",
                render: function (id, type, row) {
                    let btns = '';
                    if (row.status && row.status.toLowerCase() === 'pending') {
                        btns += `<button class="btn btn-sm btn-danger cancel-withdrawal mr-1" data-id="${id}">
                                    <i class="anticon anticon-close"></i> Cancel
                                 </button>`;
                    }
                    btns += `<button class="btn btn-sm btn-primary view-withdrawal" data-id="${id}">
                                <i class="anticon anticon-eye"></i> View
                             </button>`;
                    return `<div class="btn-group">${btns}</div>`;
                },
                orderable: false
            }
        ],
        responsive: true,
        order: [[4, 'desc']],
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>',
            emptyTable: "No withdrawal requests found",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            lengthMenu: "Show _MENU_ entries",
            loadingRecords: "Loading...",
            zeroRecords: "No matching records found"
        }
    });

    // ===============================================================
    // 🔄 REFRESH TABLE
    // ===============================================================
    $('#refreshWithdrawals').click(function () {
        const $btn = $(this);
        const original = $btn.html();
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Refreshing...');
        $('#withdrawalError').addClass('d-none');

        withdrawalsTable.ajax.reload(function () {
            $btn.prop('disabled', false).html(original);
            toastr.success('Withdrawals refreshed');
        }, false);

        $.get('ajax/get-balance.php', {
            csrf_token: $('meta[name="csrf-token"]').attr('content')
        }, function (r) {
            if (r.success)
                $('#currentBalance, #currentBalanceDisplay').text('$' + parseFloat(r.balance).toFixed(2));
        }).fail(() => toastr.error('Failed to update balance'));
    });

    // ===============================================================
    // 🧠 STATUS + HELPERS
    // ===============================================================
    function getStatusClass(status) {
        const map = {
            pending: 'warning',
            completed: 'success',
            failed: 'danger',
            cancelled: 'secondary',
            processing: 'info'
        };
        return map[status.toLowerCase()] || 'info';
    }

    // ===============================================================
    // 🧾 VIEW / CANCEL WITHDRAWAL
    // ===============================================================
    $('#withdrawalsTable').on('click', '.view-withdrawal', function () {
        showWithdrawalDetailsModal($(this).data('id'));
    });

    $('#withdrawalsTable').on('click', '.cancel-withdrawal', function () {
        cancelWithdrawal($(this).data('id'), $(this));
    });

    $(document).on('click', '.cancel-withdrawal-modal', function () {
        cancelWithdrawal($(this).data('id'), $(this));
    });

    function cancelWithdrawal(id, $btn) {
        if (!confirm('Are you sure you want to cancel this withdrawal?')) return;
        const original = $btn.html();
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Processing...');

        $.ajax({
            url: 'ajax/cancel-withdrawal.php',
            type: 'POST',
            data: {
                id: id,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function (r) {
                if (r.success) {
                    toastr.success(r.message || 'Withdrawal cancelled');
                    withdrawalsTable.ajax.reload(null, false);
                    if (r.new_balance)
                        $('#currentBalance, #currentBalanceDisplay').text('$' + parseFloat(r.new_balance).toFixed(2));
                    $('#withdrawalDetailsModal').modal('hide');
                } else {
                    toastr.error(r.message || 'Failed to cancel withdrawal');
                    $btn.prop('disabled', false).html(original);
                }
            },
            error: function () {
                toastr.error('Failed to cancel withdrawal.');
                $btn.prop('disabled', false).html(original);
            }
        });
    }

    // ===============================================================
    // 💸 WITHDRAWAL FORM SUBMIT (AFTER OTP)
    // ===============================================================
    $('#withdrawalForm').submit(function (e) {
        e.preventDefault();
        const $form = $(this);
        const $submit = $('#withdrawalSubmitBtn');
        const original = $submit.html();

        const amount = parseFloat($('#amount').val());
        const method = $('#withdrawalMethod').val();
        const details = $('#paymentDetails').val();

        if (isNaN(amount) || amount <= 0) return toastr.error('Please enter a valid amount');
        if (!method) return toastr.error('Please select a payment method');
        if (!$('#confirmDetails').is(':checked')) return toastr.error('Please confirm payment details');

        $submit.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Processing...');

        $.ajax({
            url: 'ajax/process-withdrawal.php',
            type: 'POST',
            data: {
                amount: amount,
                payment_method: method,
                payment_details: details,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function (r) {
                if (r.success) {
                    toastr.success(r.message);
                    $('#newWithdrawalModal').modal('hide');
                    withdrawalsTable.ajax.reload(null, false);
                    if (r.data && r.data.new_balance)
                        $('#currentBalance, #currentBalanceDisplay').text('$' + r.data.new_balance);
                    $form[0].reset();
                    resetOtpSection();
                } else {
                    toastr.error(r.message || 'Error processing withdrawal');
                }
            },
            error: function () {
                toastr.error('Request failed.');
            },
            complete: function () {
                $submit.prop('disabled', false).html(original);
            }
        });
    });

    // ===============================================================
    // 🔐 OTP HANDLING
    // ===============================================================
    $('#sendOtpBtn').click(function () {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Sending...');
        $.post('ajax/otp-handler.php', {
            action: 'send',
            csrf_token: $('meta[name="csrf-token"]').attr('content')
        }, function (r) {
            if (r.success) {
                toastr.success(r.message);
                $('#otpCode').prop('disabled', false);
                $('#verifyOtpBtn').prop('disabled', false);
            } else {
                toastr.error(r.message);
            }
        }, 'json').fail(function () {
            toastr.error('Failed to send OTP');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="anticon anticon-mail"></i> Send OTP');
        });
    });

    $('#verifyOtpBtn').click(function () {
        const code = $('#otpCode').val().trim();
        if (!code) return toastr.error('Please enter OTP');
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Verifying...');

        $.post('ajax/otp-handler.php', {
            action: 'verify',
            otp_code: code,
            csrf_token: $('meta[name="csrf-token"]').attr('content')
        }, function (r) {
            if (r.success) {
                toastr.success(r.message);
                $('#withdrawalSubmitBtn').prop('disabled', false);
                $('#otpCode, #sendOtpBtn, #verifyOtpBtn').prop('disabled', true);
            } else {
                toastr.error(r.message);
            }
        }, 'json').fail(function () {
            toastr.error('Verification failed');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="anticon anticon-check-circle"></i> Verify OTP');
        });
    });

    // ===============================================================
    // 🧹 RESET OTP SECTION ON MODAL CLOSE
    // ===============================================================
    $('#newWithdrawalModal').on('hidden.bs.modal', function () {
        resetOtpSection();
    });

    function resetOtpSection() {
        $('#otpCode').val('').prop('disabled', true);
        $('#sendOtpBtn').prop('disabled', false);
        $('#verifyOtpBtn').prop('disabled', true);
        $('#withdrawalSubmitBtn').prop('disabled', true);
    }
});

