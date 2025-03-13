<!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Kenzaki Systems <?php echo date('Y'); ?></span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Add sidebar state persistence script -->
    <script>
        // Execute immediately before DOM is fully loaded to prevent flicker
        (function() {
            var sidebarState = localStorage.getItem('sidebarToggled');
            
            if (sidebarState === 'true') {
                document.body.classList.add('sidebar-toggled');
                var sidebar = document.querySelector('.sidebar');
                if (sidebar) sidebar.classList.add('toggled');
            }
        })();

        // Additional initialization after DOM is ready
        $(document).ready(function() {
            // Listen for sidebar toggle click
            $("#sidebarToggle, #sidebarToggleTop").on('click', function() {
                // Get current state after the default toggle action
                setTimeout(function() {
                    var isToggled = $('.sidebar').hasClass('toggled');
                    localStorage.setItem('sidebarToggled', isToggled);
                }, 50);
            });
        });
    </script>
</body>
</html>
