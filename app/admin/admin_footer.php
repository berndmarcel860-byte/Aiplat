<!-- admin_footer.php -->
</div> <!-- End page-container -->
</div> <!-- End layout -->
</div> <!-- End app -->

<!-- Footer START -->
<footer class="footer">
    <div class="footer-content">
        <p class="m-b-0">Admin Panel &copy; <?php echo date('Y'); ?> Scam Recovery</p>
        <span>
            <a href="#" class="text-gray m-r-15">Help</a>
            <a href="#" class="text-gray">Privacy</a>
        </span>
    </div>
</footer>
<!-- Footer END -->

<!-- Core Vendors JS -->
<script src="../assets/js/vendors.min.js"></script>

<!-- Chart JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>

<!-- DataTables JS -->
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.11.3/datatables.min.js"></script>

<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<!-- Page JS -->
<script src="../assets/js/pages/admin-dashboard.js"></script>

<!-- Core JS -->
<script src="../assets/js/app.min.js"></script>

<!-- Sidebar and AJAX functionality -->
<script>
$(document).ready(function() {
    // Sidebar functionality
    function initSidebar() {
        // Mobile toggle
        $('#mobile-toggle').on('click', function(e) {
            e.preventDefault();
            $('.side-nav').toggleClass('mobile-open');
            $('.nav-overlay').toggleClass('active');
        });

        // Close sidebar when clicking overlay
        $('.nav-overlay').on('click', function() {
            $('.side-nav').removeClass('mobile-open');
            $('.nav-overlay').removeClass('active');
        });

        // Desktop toggle
        $('#toggle-sidebar').on('click', function(e) {
            e.preventDefault();
            $('.side-nav').toggleClass('desktop-collapsed');
        });

        // Handle dropdown toggles
        $('.dropdown-toggle').on('click', function(e) {
            e.preventDefault();
            $(this).parent().toggleClass('open')
                   .siblings('.dropdown.open').removeClass('open');
        });

        // Auto-close mobile menu when clicking a link
        $('.side-nav-menu a').not('.dropdown-toggle').on('click', function() {
            if ($(window).width() < 992) {
                $('.side-nav').removeClass('mobile-open');
                $('.nav-overlay').removeClass('active');
            }
        });
    }

    // Initialize everything
    initSidebar();
    
    // Set toastr options
    toastr.options = {
        positionClass: "toast-top-right",
        timeOut: 5000,
        closeButton: true,
        progressBar: true
    };
    
    // Handle window resize
    $(window).on('resize', function() {
        if ($(window).width() >= 992) {
            $('.side-nav').removeClass('mobile-open');
            $('.nav-overlay').removeClass('active');
        }
    });
});
</script>

</body>
</html>