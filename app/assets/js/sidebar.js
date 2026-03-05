// Sidebar and Navigation
$(document).ready(function() {
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