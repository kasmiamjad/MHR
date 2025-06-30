<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
}
include 'header.php';

$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');


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

// Get current user's attendance
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

// Get all employees attendance for current month
$all_emp_sql = "SELECT
    u.username,
    DATE_FORMAT(e.check_in, '%Y-%m-%d') as date,
    DATE_FORMAT(e.check_in, '%h:%i %p') as check_in_time,
    DATE_FORMAT(e.check_out, '%h:%i %p') as check_out_time,
    CASE
        WHEN e.check_out IS NULL THEN 'Checked In'
        ELSE 'Completed'
    END as status,
    e.check_in_ip,
    e.check_out_ip
FROM mhr_employee_attendance e
JOIN mhr_users u ON e.employee_id = u.id
WHERE MONTH(e.check_in) = ? AND YEAR(e.check_in) = ?
ORDER BY e.check_in DESC, u.username ASC";

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
    <!-- Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="fas fa-clipboard-list"></i> 
            Attendance Records
        </h4>
        <a href="attendance.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i>
            Back to Attendance
        </a>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Select Month</label>
                    <select name="year-month" class="form-select form-control">
                        <?= getMonthOptions($month, $year); ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <button id="exportBtn" type="button" class="btn btn-outline-secondary">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-user-check text-success mb-2 h3"></i>
                    <h5 class="mb-0">23</h5>
                    <small class="text-muted">Present Days</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-user-times text-danger mb-2 h3"></i>
                    <h5 class="mb-0">2</h5>
                    <small class="text-muted">Absent Days</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-user-clock text-warning mb-2 h3"></i>
                    <h5 class="mb-0">3</h5>
                    <small class="text-muted">Late Arrivals</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-clock text-info mb-2 h3"></i>
                    <h5 class="mb-0">180</h5>
                    <small class="text-muted">Total Hours</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Desktop View -->
    <div class="card shadow-sm d-none d-md-block">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                            <th>Network Info</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    $stmt = $conn->prepare($all_emp_sql);
                    $stmt->bind_param("ii", $month, $year);
                    $stmt->execute();
                    $stmt->bind_result($username, $date, $check_in_time, $check_out_time, $record_status, $record_in_ip, $record_out_ip);
                    $i = 1;
                    while ($stmt->fetch()):
                    ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= htmlspecialchars($username); ?></td>
                            <td><?= date('d M Y', strtotime($date)); ?></td>
                            <td>
                                <div><?= $check_in_time; ?></div>
                                <small class="text-muted"><?= $record_in_ip; ?></small>
                            </td>
                            <td>
                                <div><?= $check_out_time ?: '-'; ?></div>
                                <small class="text-muted"><?= $record_out_ip ?: ''; ?></small>
                            </td>
                            <td>
                                <span class="badge badge-<?= $record_status === 'Completed' ? 'success' : 'warning' ?> rounded-pill">
                                    <?= $record_status; ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= $record_in_ip; ?>
                                    <?= $record_out_ip ? "<br>" . $record_out_ip : ''; ?>
                                </small>
                            </td>
                        </tr>
                    <?php endwhile;
                    $stmt->close();
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Mobile View -->
    <div class="d-md-none">
        <?php 
        $stmt = $conn->prepare($all_emp_sql);
        $stmt->bind_param("ii", $month, $year);
        $stmt->execute();
        $stmt->bind_result($username, $date, $check_in_time, $check_out_time, $record_status, $record_in_ip, $record_out_ip);
        while ($stmt->fetch()):
        ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($username); ?></h6>
                            <div class="text-muted small"><?= date('d M Y', strtotime($date)); ?></div>
                        </div>
                        <span class="badge badge-<?= $record_status === 'Completed' ? 'success' : 'warning' ?> rounded-pill">
                            <?= $record_status; ?>
                        </span>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="bg-light rounded p-2">
                                <small class="text-muted d-block">Check In</small>
                                <strong><?= $check_in_time; ?></strong>
                                <div class="small text-muted"><?= $record_in_ip; ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded p-2">
                                <small class="text-muted d-block">Check Out</small>
                                <strong><?= $check_out_time ?: '-'; ?></strong>
                                <div class="small text-muted"><?= $record_out_ip ?: ''; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php 
        endwhile;
        $stmt->close();
        ?>
    </div>
</div>

<style>
.card {
    border: none;
    border-radius: 15px;
}
.shadow-sm {
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
}
.badge {
    padding: 8px 12px;
    font-weight: 500;
}
.form-label {
    font-weight: 500;
    color: #6c757d;
}
.table th {
    font-weight: 500;
    color: #6c757d;
}
.btn {
    padding: 8px 16px;
    border-radius: 8px;
}
.rounded-pill {
    border-radius: 50rem;
}
@media (max-width: 768px) {
    .container {
        padding: 10px;
    }
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
    document.getElementById('monthSelect').addEventListener('change', function() {
        const [year, month] = this.value.split('-');
        const searchParams = new URLSearchParams(window.location.search);
        searchParams.set('year', year);
        searchParams.set('month', month);
        window.location.search = searchParams.toString();
    });

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

<?php include 'footer.php'; ?>