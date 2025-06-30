<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
    exit;
}
?>
<?php include 'header.php'; ?>

<style>
    .dashboard-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-light));
        color: var(--white);
        padding: 2rem 0;
        margin-bottom: 2rem;
    }

    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
        border-left: 4px solid var(--primary);
    }

    .stat-value {
        font-size: 1.8rem;
        font-weight: 600;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }

    .stat-label {
        color: #6c757d;
        font-size: 0.9rem;
        text-transform: uppercase;
    }

    .activity-card {
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
        margin-bottom: 1rem;
    }

    .filter-section {
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow);
    }

    .date-range {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .activity-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .activity-type {
        font-size: 0.9rem;
        font-weight: 500;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
    }

    .activity-type.payment {
        background: #e3f2fd;
        color: #1976D2;
    }

    .activity-time {
        color: #6c757d;
        font-size: 0.85rem;
    }

    .activity-meta {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        margin-top: 0.5rem;
    }

    .activity-meta > span {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #6c757d;
    }

    .activity-amount {
        font-weight: 600;
        color: var(--primary);
    }

    .user-summary-table {
        margin-top: 2rem;
    }

    .user-summary-table th {
        background: var(--primary);
        color: white;
    }

    @media (max-width: 768px) {
        .date-range {
            flex-direction: column;
            align-items: stretch;
        }
        
        .activity-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .activity-meta {
            flex-direction: column;
            gap: 0.5rem;
        }
    }
</style>

<div class="container">
    <!-- Filter Section -->
    <div class="filter-section">
        <form id="dateRangeForm" class="date-range">
            <div class="form-group">
                <label for="startDate">Start Date</label>
                <input type="date" id="startDate" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="endDate">End Date</label>
                <input type="date" id="endDate" class="form-control" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>

    <!-- Summary Stats -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-value" id="totalCollections">₹0</div>
            <div class="stat-label">Total Collections</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="cashCollections">₹0</div>
            <div class="stat-label">Cash Collections</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="onlineCollections">₹0</div>
            <div class="stat-label">Online Collections</div>
        </div>
    </div>

    <!-- User Summary -->
    <div class="activity-card">
        <h5 class="mb-3">Collections by User</h5>
        <div id="userSummary" class="table-responsive"></div>
    </div>

    <!-- Recent Activities -->
    <div class="activity-card">
        <h5 class="mb-3">Recent Payment Activities</h5>
        <div id="activitiesList"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set default date range (current month)
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    document.getElementById('startDate').value = firstDay.toISOString().split('T')[0];
    document.getElementById('endDate').value = today.toISOString().split('T')[0];

    // Initial load
    fetchActivities();

    // Form submission
    document.getElementById('dateRangeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        fetchActivities();
    });
});

function fetchActivities() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    $.ajax({
        url: 'fetch_payment_activities.php',
        method: 'GET',
        data: {
            startDate: startDate,
            endDate: endDate
        },
        success: function(response) {
            updateStats(response.summary);
            updateUserSummary(response.userSummary);
            updateActivitiesList(response.payments);
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            alert('Error loading data. Please try again.');
        }
    });
}

function updateStats(summary) {
    document.getElementById('totalCollections').textContent = 
        `₹${parseFloat(summary.total).toLocaleString('en-IN')}`;
    document.getElementById('cashCollections').textContent = 
        `₹${parseFloat(summary.cash).toLocaleString('en-IN')}`;
    document.getElementById('onlineCollections').textContent = 
        `₹${parseFloat(summary.online).toLocaleString('en-IN')}`;
}

function updateUserSummary(userSummary) {
    const container = document.getElementById('userSummary');
    let html = '<table class="table user-summary-table">';
    html += `
        <thead>
            <tr>
                <th>User</th>
                <th>Total Collections</th>
                <th>Cash</th>
                <th>Online</th>
            </tr>
        </thead>
        <tbody>
    `;
    
    userSummary.forEach(user => {
        html += `
            <tr>
                <td>${user.created_by || 'Unknown'}</td>
                <td>₹${parseFloat(user.total).toLocaleString('en-IN')}</td>
                <td>₹${parseFloat(user.cash).toLocaleString('en-IN')}</td>
                <td>₹${parseFloat(user.online).toLocaleString('en-IN')}</td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

function updateActivitiesList(activities) {
    const container = document.getElementById('activitiesList');
    
    if (activities.length === 0) {
        container.innerHTML = '<div class="text-center p-4">No activities found for the selected period.</div>';
        return;
    }

    container.innerHTML = '';
    activities.forEach(activity => {
        const activityDate = new Date(activity.payment_date);
        const formattedDate = activityDate.toLocaleDateString('en-GB', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        const bookingDate = new Date(activity.booking_date);
        const formattedBookingDate = bookingDate.toLocaleDateString('en-GB');

        container.innerHTML += `
            <div class="activity-card">
                <div class="activity-header">
                    <div class="activity-type payment">Payment</div>
                    <div class="activity-time">
                        <i class="far fa-clock"></i> ${formattedDate}
                    </div>
                </div>
                <div class="activity-description">
                    ${activity.description}
                </div>
                <div class="activity-meta">
                    <span><i class="fas fa-user"></i> ${activity.guest_name}</span>
                    <span><i class="fas fa-calendar"></i> Booking: ${formattedBookingDate}</span>
                    <span class="activity-amount"><i class="fas fa-rupee-sign"></i> ${parseFloat(activity.amount).toLocaleString('en-IN')}</span>
                    <span><i class="fas fa-credit-card"></i> ${activity.payment_type || 'Not Specified'}</span>
                    <span><i class="fas fa-user-check"></i> Added by: ${activity.created_by}</span>
                    ${activity.transaction_id ? `<span><i class="fas fa-hashtag"></i> Txn: ${activity.transaction_id}</span>` : ''}
                </div>
            </div>
        `;
    });
}
</script>

<?php include 'footer.php'; ?>