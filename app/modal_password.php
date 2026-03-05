<?php
/* modal_password.php – same folder as index.php */
if (empty($passwordChangeRequired)) return; // silent if not needed
?>
<!-- Password Change Modal -->
<div class="modal fade show" id="passwordChangeModal" tabindex="-1" role="dialog"
     aria-labelledby="passwordChangeModalLabel" style="display:block; padding-right:15px;" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title mb-0" id="passwordChangeModalLabel">
                    <i class="anticon anticon-lock m-r-5"></i> Password Change Required
                </h5>
            </div>

            <div class="modal-body">
                <div class="alert alert-warning mb-4" role="alert">
                    <i class="anticon anticon-info-circle"></i>
                    For your security, please update your password before continuing.
                </div>

                <form id="passwordChangeForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">

                    <!-- Current Password -->
                    <div class="form-group">
                        <label for="currentPassword">Current Password</label>
                        <input type="password" class="form-control" id="currentPassword" required aria-required="true" autocomplete="current-password">
                    </div>

                    <!-- New Password -->
                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <input type="password" class="form-control" id="newPassword" required minlength="8" aria-describedby="passwordHelp" autocomplete="new-password">
                        <small id="passwordHelp" class="form-text text-muted">
                            Use a unique password. We enforce a minimum of 8 characters.
                        </small>

                        <!-- Strength Bar -->
                        <div class="progress mt-2" style="height:8px;">
                            <div id="passwordStrengthBar" class="progress-bar bg-danger" style="width:0%;" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small id="passwordStrengthText" class="text-muted small d-block mb-1" aria-live="polite">Strength: Weak</small>

                        <!-- Requirements Checklist -->
                        <ul class="list-unstyled small" id="passwordChecklist" aria-hidden="false">
                            <li id="req-length" class="text-danger"><i class="anticon anticon-close"></i> At least 8 characters</li>
                            <li id="req-upper" class="text-danger"><i class="anticon anticon-close"></i> At least one uppercase letter</li>
                            <li id="req-number" class="text-danger"><i class="anticon anticon-close"></i> At least one number</li>
                            <li id="req-special" class="text-danger"><i class="anticon anticon-close"></i> At least one special character</li>
                        </ul>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label for="confirmPassword">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirmPassword" required autocomplete="new-password">
                        <small id="passwordMatchText" class="small text-muted" aria-live="polite">Waiting for input...</small>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="submitPasswordChange" aria-label="Change password">
                    <i class="anticon anticon-save"></i> Change Password
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal-backdrop fade show"></div>

<script>
/* Password modal JS */
$(function(){
    $('#newPassword').on('input', function() {
        const val = $(this).val();
        const $bar = $('#passwordStrengthBar');
        const $text = $('#passwordStrengthText');

        let score = 0;
        const req = {
            length: val.length >= 8,
            upper: /[A-Z]/.test(val),
            number: /[0-9]/.test(val),
            special: /[^A-Za-z0-9]/.test(val)
        };

        for (let key in req) {
            const $item = $('#req-' + key);
            if (req[key]) {
                $item.removeClass('text-danger').addClass('text-success')
                     .html('<i class="anticon anticon-check"></i> ' + $item.text().replace(/^[✓✗]\s*/, ''));
                score++;
            } else {
                $item.removeClass('text-success').addClass('text-danger')
                     .html('<i class="anticon anticon-close"></i> ' + $item.text().replace(/^[✓✗]\s*/, ''));
            }
        }

        const width = (score / 4) * 100;
        let colorClass, label;
        switch (score) {
            case 0:
            case 1: colorClass = 'bg-danger'; label = 'Weak'; break;
            case 2: colorClass = 'bg-warning'; label = 'Fair'; break;
            case 3: colorClass = 'bg-info'; label = 'Good'; break;
            case 4: colorClass = 'bg-success'; label = 'Strong'; break;
        }

        $bar.removeClass('bg-danger bg-warning bg-info bg-success')
            .addClass(colorClass)
            .css('width', width + '%');
        $text.text('Strength: ' + label);

        $('#confirmPassword').trigger('input');
    });

    $('#confirmPassword, #newPassword').on('input', function() {
        const newPass = $('#newPassword').val();
        const confirm = $('#confirmPassword').val();
        const $match = $('#passwordMatchText');

        if (!confirm) {
            $match.text('Waiting for input...').removeClass('text-success text-danger').addClass('text-muted');
            return;
        }

        if (confirm === newPass) {
            $match.text('Passwords match ✅').removeClass('text-danger text-muted').addClass('text-success');
        } else {
            $match.text('Passwords do not match ❌').removeClass('text-success text-muted').addClass('text-danger');
        }
    });

    $('#submitPasswordChange').click(function() {
        const currentPassword = $('#currentPassword').val();
        const newPassword = $('#newPassword').val();
        const confirmPassword = $('#confirmPassword').val();

        if (!currentPassword || !newPassword || !confirmPassword) {
            toastr.error('All fields are required');
            return;
        }
        if (newPassword !== confirmPassword) {
            toastr.error('New passwords do not match');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="anticon anticon-loading anticon-spin"></i> Processing...');

        $.ajax({
            url: 'change_password.php',
            method: 'POST',
            dataType: 'json',
            data: {
                current_password: currentPassword,
                new_password: newPassword,
                confirm_password: confirmPassword,
                force_change: 1,
                csrf_token: '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>'
            },
            success: function(data) {
                if (data.success) {
                    toastr.success(data.message || 'Password changed successfully');
                    $('#passwordChangeModal').modal('hide');
                    $('.modal-backdrop').remove();
                    setTimeout(function(){ location.reload(); }, 800);
                } else {
                    toastr.error(data.message || 'Error changing password');
                }
                $btn.prop('disabled', false).html('<i class="anticon anticon-save"></i> Change Password');
            },
            error: function(xhr, status, error) {
                toastr.error('Server error: ' + error);
                $btn.prop('disabled', false).html('<i class="anticon anticon-save"></i> Change Password');
            }
        });
    });
});
</script>