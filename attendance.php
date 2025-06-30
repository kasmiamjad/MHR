<?php
session_start();
require_once 'config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
    exit;
}

// Include header
include 'header.php';

?>
<style>
.card {
    border: none;
    border-radius: 15px;
}
.btn {
    border-radius: 10px;
    padding: 12px 20px;
}
.btn-light {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}
.btn-light:hover {
    background: #e9ecef;
}
.shadow-sm {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}
#attendance-status .alert {
    border-radius: 10px;
    margin-bottom: 0;
}
.rounded {
    border-radius: 12px !important;
}
.h5 {
    font-weight: 600;
}
.text-muted {
    color: #6c757d !important;
}
</style>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <!-- Action Bar with Title and List Button -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">
                    <i class="fas fa-user-clock"></i> 
                    Attendance
                </h4>
                <a href="attendance_list.php" class="btn btn-light">
                    <i class="fas fa-clipboard-list"></i>
                    View History
                </a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- Network Status Banner -->
                    <div id="attendance-status" class="text-center mb-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                    
                    <!-- Today's Status Card -->
                    <div class="bg-light rounded p-3 mb-4">
                        <h6 class="text-muted mb-3">Today's Status</h6>
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <small class="text-muted d-block">Check In</small>
                                    <span id="checkInTime" class="h5">-</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <small class="text-muted d-block">Check Out</small>
                                    <span id="checkOutTime" class="h5">-</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <small class="text-muted d-block">Status</small>
                            <span id="currentStatus" class="h5">-</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center">
                        <button id="checkInBtn" class="btn btn-success btn-lg px-5 mb-2" style="display: none; width: 80%;">
                            <i class="fas fa-sign-in-alt me-2"></i> Check In
                        </button>
                        <button id="checkOutBtn" class="btn btn-danger btn-lg px-5 mb-2" style="display: none; width: 80%;">
                            <i class="fas fa-sign-out-alt me-2"></i> Check Out
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Stats Card -->
            <div class="card mt-3 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-3">This Month's Overview</h6>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="p-2">
                                <i class="fas fa-calendar-check text-success mb-2"></i>
                                <h4>23</h4>
                                <small class="text-muted">Present</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2">
                                <i class="fas fa-calendar-times text-danger mb-2"></i>
                                <h4>2</h4>
                                <small class="text-muted">Absent</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2">
                                <i class="fas fa-clock text-warning mb-2"></i>
                                <h4>1</h4>
                                <small class="text-muted">Late</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function checkWifiConnection() {
    try {
        const response = await fetch('get_network_info.php');
        const data = await response.json();
        return data.isOfficeNetwork;
    } catch (error) {
        console.error('Error checking network:', error);
        return false;
    }
}

async function updateAttendanceStatus() {
    try {
        const response = await fetch('get_attendance_status.php');
        const data = await response.json();
        if (data.error) {
            console.error('Error updating status:', data.error);
            return;
        }

        document.getElementById('checkInTime').textContent = data.check_in || '-';
        document.getElementById('checkOutTime').textContent = data.check_out || '-';
        document.getElementById('currentStatus').textContent = data.status || '-';

        const isOfficeNetwork = await checkWifiConnection();
        //alert("wifi"+isOfficeNetwork)
        if (!data.check_in && isOfficeNetwork) {
            document.getElementById('checkInBtn').style.display = 'inline-block';
            document.getElementById('checkOutBtn').style.display = 'none';
        } else if (data.check_in && !data.check_out && isOfficeNetwork) {
            document.getElementById('checkInBtn').style.display = 'none';
            document.getElementById('checkOutBtn').style.display = 'inline-block';
        } else {
            document.getElementById('checkInBtn').style.display = 'none';
            document.getElementById('checkOutBtn').style.display = 'none';
        }
        document.getElementById('attendance-status').innerHTML = isOfficeNetwork ?
            '<div class="alert alert-success">Connected to Office Network</div>' :
            '<div class="alert alert-warning">Please connect to office network to mark attendance</div>';

    } catch (error) {
        console.error('Error updating status:', error);
    }
}

// Check-in function
async function checkIn() {
    try {
        const isOfficeWifi = await checkWifiConnection();
        if (!isOfficeWifi) {
            alert('Please connect to office network to check in');
            return;
        }
        
        const response = await fetch('process_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'check_in'
            })
        });
        
        const data = await response.json();
        if (data.success) {
            alert('Check-in successful!');
            updateAttendanceStatus();
        } else {
            alert(data.message || 'Check-in failed');
        }
    } catch (error) {
        console.error('Error during check-in:', error);
        alert('An error occurred during check-in');
    }
}

// Check-out function
async function checkOut() {
    try {
        const isOfficeWifi = await checkWifiConnection();
        if (!isOfficeWifi) {
            alert('Please connect to office network to check out');
            return;
        }
        
        const response = await fetch('process_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'check_out'
            })
        });
        
        const data = await response.json();
        if (data.success) {
            alert('Check-out successful!');
            updateAttendanceStatus();
        } else {
            alert(data.message || 'Check-out failed');
        }
    } catch (error) {
        console.error('Error during check-out:', error);
        alert('An error occurred during check-out');
    }
}

// Add event listeners
document.getElementById('checkInBtn').addEventListener('click', checkIn);
document.getElementById('checkOutBtn').addEventListener('click', checkOut);

// Update status every minute
updateAttendanceStatus();
setInterval(updateAttendanceStatus, 60000);
</script>

<?php include 'footer.php'; ?>