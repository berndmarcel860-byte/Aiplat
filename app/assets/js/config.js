// Global Configuration and Session Management
$(document).ready(function() {
    // Configure Toastr
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        preventDuplicates: true,
        showDuration: 300,
        hideDuration: 1000,
        timeOut: 5000,
        extendedTimeOut: 1000
    };

    // Global AJAX setup with session handling
    $.ajaxSetup({
        xhrFields: {
            withCredentials: true
        },
        error: function(xhr, status, error) {
            if (xhr.status === 401) {
                toastr.error('Your session has expired. Redirecting to login...');
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 2000);
            } else if (xhr.status === 500) {
                toastr.error('Server error occurred. Please try again later.');
            }
        }
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip({
        trigger: 'hover',
        placement: 'top'
    });
});