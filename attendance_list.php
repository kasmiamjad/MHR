<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
}
include 'header.php';

// Handle messages
$message = '';
$message_type = '';
if (isset($_SESSION['attendance_message'])) {
    $message = $_SESSION['attendance_message'];
    $message_type = $_SESSION['attendance_message_type'];
    unset($_SESSION['attendance_message']);
    unset($_SESSION['attendance_message_type']);
}

$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get current user's attendance for today
$today_sql = "SELECT
    e.id AS attendance_id,
    DATE_FORMAT(e.check_in, '%h:%i %p') AS check_in,
    DATE_FORMAT(e.check_out, '%h:%i %p') AS check_out,
    CASE
        WHEN e.check_in IS NULL THEN 'Not Marked'
        WHEN e.check_out IS NULL THEN 'Checked In'
        ELSE 'Checked Out'
    END AS status,
    e.check_in_ip,
    e.check_out_ip
FROM mhr_employee_attendance e
WHERE e.employee_id = ? AND DATE(e.check_in) = CURRENT_DATE";

$stmt = $conn->prepare($today_sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($attendance_id, $check_in, $check_out, $status, $check_in_ip, $check_out_ip);
$stmt->fetch();
$stmt->close();

// Get monthly attendance records
$monthly_sql = "SELECT
    DATE_FORMAT(check_in, '%Y-%m-%d') as date,
    DATE_FORMAT(check_in, '%h:%i %p') as check_in_time,
    DATE_FORMAT(check_out, '%h:%i %p') as check_out_time,
    CASE
        WHEN check_out IS NULL THEN 'Checked In'
        ELSE 'Completed'
    END as status,
    check_in_ip,
    check_out_ip
FROM mhr_employee_attendance
WHERE employee_id = ? AND MONTH(check_in) = ? AND YEAR(check_in) = ?
ORDER BY check_in DESC";

function getMonthOptions($currentMonth, $currentYear) {
    $options = '';
    for ($i = 0; $i < 12; $i++) {
        $date = strtotime("-$i month");
        $monthValue = date('m', $date);
        $yearValue = date('Y', $date);
        $selected = ($currentMonth == $monthValue && $currentYear == $yearValue) ? 'selected' : '';
        $options .= "<option value='$yearValue-$monthValue' $selected>" . date('F Y', $date) . "</option>";
    }
    return $options;
}
?>

<div class="container mt-4">
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="expenses-container">
        <!-- Today's Attendance Status -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="stats-amount"><?= $status ?? 'Not Marked' ?></div>
                    <div class="stats-label">Today's Attendance Status</div>
                    <?php if ($check_in): ?>
                        <div class="small text-muted mt-2">
                            Check-in: <?= $check_in ?> <?= $check_in_ip ? "($check_in_ip)" : '' ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($check_out): ?>
                        <div class="small text-muted">
                            Check-out: <?= $check_out ?> <?= $check_out_ip ? "($check_out_ip)" : '' ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6 d-flex align-items-center justify-content-end">
                <?php if (!$check_in): ?>
                    <form method="POST" action="attendance_action.php" class="w-100">
                        <input type="hidden" name="action" value="checkin">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-sign-in-alt mr-2"></i>Check In
                        </button>
                    </form>
                <?php elseif (!$check_out): ?>
                    <form method="POST" action="attendance_action.php" class="w-100">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn btn-danger btn-lg w-100">
                            <i class="fas fa-sign-out-alt mr-2"></i>Check Out
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Month Selection -->
        <form method="GET" class="mb-4">
            <div class="form-row align-items-center">
                <div class="col-auto flex-grow-1">
                    <label for="monthSelect" class="mr-sm-2 font-weight-bold">Select Month:</label>
                    <select name="year-month" id="monthSelect" class="form-control custom-select">
                        <?= getMonthOptions($month, $year); ?>
                    </select>
                </div>
                
            </div>
        </form>

        <!-- Desktop Table View -->
        <div class="expense-table table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                        <th>Network Info</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $stmt = $conn->prepare($monthly_sql);
                $stmt->bind_param("iii", $_SESSION['user_id'], $month, $year);
                $stmt->execute();
                $stmt->bind_result($date, $check_in_time, $check_out_time, $record_status, $record_in_ip, $record_out_ip);
                $i = 1;
                while ($stmt->fetch()):
                ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><?= date('d/m/Y', strtotime($date)); ?></td>
                        <td><?= $check_in_time; ?><br><small class="text-muted"><?= $record_in_ip; ?></small></td>
                        <td><?= $check_out_time ?: '-'; ?><br><small class="text-muted"><?= $record_out_ip ?: ''; ?></small></td>
                        <td>
                            <span class="badge badge-<?= $record_status === 'Completed' ? 'success' : 'warning' ?>">
                                <?= $record_status; ?>
                            </span>
                        </td>
                        <td>
                            <small><?= $record_in_ip; ?><?= $record_out_ip ? "<br>" . $record_out_ip : ''; ?></small>
                        </td>
                    </tr>
                <?php endwhile;
                $stmt->close();
                ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <?php 
        $stmt = $conn->prepare($monthly_sql);
        $stmt->bind_param("iii", $_SESSION['user_id'], $month, $year);
        $stmt->execute();
        $stmt->bind_result($date, $check_in_time, $check_out_time, $record_status, $record_in_ip, $record_out_ip);
        while ($stmt->fetch()):
        ?>
            <div class="expense-card">
                <div class="expense-card-header">
                    <div>
                        <h6 class="mb-1"><?= date('d/m/Y', strtotime($date)); ?></h6>
                        <div class="expense-card-date">
                            Check In: <?= $check_in_time; ?><br>
                            Check Out: <?= $check_out_time ?: 'Not checked out'; ?>
                        </div>
                    </div>
                    <div class="expense-card-amount">
                        <span class="badge badge-<?= $record_status === 'Completed' ? 'success' : 'warning' ?>">
                            <?= $record_status; ?>
                        </span>
                    </div>
                </div>
                <div class="small text-muted">
                    Check-in IP: <?= $record_in_ip; ?><br>
                    <?php if ($record_out_ip): ?>
                        Check-out IP: <?= $record_out_ip; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php 
        endwhile;
        $stmt->close();
        ?>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
    // Month selection handler
    document.getElementById('monthSelect').addEventListener('change', function() {
        const [year, month] = this.value.split('-');
        const searchParams = new URLSearchParams(window.location.search);
        searchParams.set('year', year);
        searchParams.set('month', month);
        window.location.search = searchParams.toString();
    });

    // Export functionality
    document.getElementById('exportBtn').addEventListener('click', function() {
        html2canvas(document.querySelector('.expenses-container')).then(function(canvas) {
            var dataURL = canvas.toDataURL('image/png');
            var link = document.createElement('a');
            link.download = 'attendance_report.png';
            link.href = dataURL;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }).catch(function(error) {
            console.error('Error capturing page:', error);
        });
    });
</script>