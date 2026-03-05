// =============================================
// GLOBAL CONFIGURATION AND SESSION MANAGEMENT
// =============================================
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

    // =============================================
    // SIDEBAR AND NAVIGATION
    // =============================================
    $('.side-nav .dropdown-toggle').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $parent = $(this).closest('.nav-item.dropdown');
        var wasOpen = $parent.hasClass('open');
        
        $('.side-nav .nav-item.dropdown').removeClass('open');
        if (!wasOpen) $parent.addClass('open');
    });

    $('#toggle-mobile-sidebar').on('click', function(e) {
        e.preventDefault();
        $('body').toggleClass('side-nav-visible');
        $('.side-nav .nav-item.dropdown').removeClass('open');
    });

    $('#toggle-sidebar').on('click', function(e) {
        e.preventDefault();
        $('.side-nav').toggleClass('desktop-collapsed');
        $('.side-nav .nav-item.dropdown').removeClass('open');
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.side-nav').length) {
            $('.side-nav .nav-item.dropdown').removeClass('open');
        }
        
        if (!$(e.target).closest('#toggle-mobile-sidebar').length && 
            !$(e.target).closest('.side-nav').length) {
            $('body').removeClass('side-nav-visible');
        }
    });

    $('.side-nav .dropdown-menu').on('click', function(e) {
        e.stopPropagation();
    });
});