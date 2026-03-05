// Payment Methods
$(document).ready(function() {
    if ($('#paymentMethodForm').length) {
        $('#paymentMethodForm').submit(function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            
            $.ajax({
                url: 'ajax/update-payment-method.php',
                type: 'POST',
                data: formData,
                beforeSend: function() {
                    $('#paymentMethodForm button[type="submit"]').prop('disabled', true)
                        .html('<i class="anticon anticon-loading anticon-spin"></i> Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                },
                complete: function() {
                    $('#paymentMethodForm button[type="submit"]').prop('disabled', false)
                        .html('Save Changes');
                }
            });
        });
    }
});