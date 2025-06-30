<!-- Core JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

<!-- jQuery UI for web datepicker -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<!-- Flatpickr for mobile -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>


<!-- Initialize plugins -->
<script>
    
    $(document).ready(function() {
    

        $('#globalConfirmOk').on('click', function () {
            $('#globalConfirmModal').modal('hide');
            if (typeof globalConfirmCallback === 'function') {
                globalConfirmCallback();
            }
        });

        // Add active class based on current page
        const currentPage = window.location.pathname.split('/').pop();
        $('.navbar-nav .nav-link').each(function() {
            const href = $(this).attr('href');
            if (href === currentPage) {
                $(this).addClass('active');
            }
        });

        // Function to detect if user is on mobile
        function isMobile() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }

        // Initialize appropriate datepicker based on device
        if (isMobile()) {
            // Use Flatpickr for mobile
            const checkin = flatpickr("#checkin", {
                dateFormat: "Y-m-d",
                minDate: "today",
                onChange: function(selectedDates, dateStr) {
                    checkout.set("minDate", dateStr);
                }
            });

            const checkout = flatpickr("#checkout", {
                dateFormat: "Y-m-d",
                minDate: "today"
            });
        } else {
            // Use jQuery UI Datepicker for web
            $("#checkin").datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: 0,
                onSelect: function(dateText) {
                    $("#checkout").datepicker("option", "minDate", dateText);
                }
            });

            $("#checkout").datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: 0
            });

            // Add calendar icon click handler for web
            $(".fa-calendar-check, .fa-calendar-times").click(function() {
                $(this).siblings('input').datepicker('show');
            });

            // Style fixes for jQuery UI Datepicker
            $("<style>").text(`
                .ui-datepicker {
                    font-size: 14px;
                    z-index: 999 !important;
                }
                .ui-datepicker .ui-datepicker-header {
                    background: #2196F3;
                    color: white;
                    border: none;
                }
                .ui-datepicker .ui-datepicker-calendar .ui-state-active {
                    background: #2196F3;
                    color: white;
                }
                .ui-datepicker .ui-datepicker-calendar td:not(.ui-datepicker-current-day) a:hover {
                    background: #e3f2fd;
                }
            `).appendTo("head");
        }
    });
</script>
</body>
</html>