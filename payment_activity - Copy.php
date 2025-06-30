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
    .activity-card {
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
        margin-bottom: 1rem;
        border-left: 4px solid var(--primary);
        transition: var(--transition);
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
        background: var(--light);
    }

    .activity-type.payment {
        background: #e3f2fd;
        color: #1976D2;
    }

    .activity-type.booking {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .activity-time {
        color: #6c757d;
        font-size: 0.85rem;
    }

    .activity-details {
        display: grid;
        gap: 0.8rem;
    }

    .activity-meta {
        display: flex;
        gap: 1rem;
        color: #6c757d;
        font-size: 0.9rem;
    }

    .activity-amount {
        font-weight: 600;
        color: var(--primary);
    }

    @media (max-width: 768px) {
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

<div class="dashboard-header">
    <div class="container">
        <h1 class="mb-0">Payments</h1>
    </div>
</div>

<div class="container">
    <div class="content-card p-4">
        <div id="activitiesList">
            <!-- Activities will be loaded here -->
        </div>
    </div>
</div>

<script>
function fetchActivities() {
    $.ajax({
        url: 'fetch_payment_activities.php',
        method: 'GET',
        success: function(response) {
            const activities = response;
            const activitiesList = document.getElementById('activitiesList');
            activitiesList.innerHTML = '';

            if (activities.length > 0) {
                activities.forEach(activity => {
                    const activityDate = new Date(activity.created_at);
                    const formattedDate = activityDate.toLocaleDateString('en-GB', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    activitiesList.innerHTML += `
                        <div class="activity-card">
                            <div class="activity-header">
                                <div class="activity-type ${activity.type.toLowerCase()}">
                                    ${activity.type}
                                </div>
                                <div class="activity-time">
                                    <i class="far fa-clock"></i> ${formattedDate}
                                </div>
                            </div>
                            <div class="activity-details">
                                <div class="activity-description">
                                    ${activity.description}
                                </div>
                                <div class="activity-meta">
                                    <span><i class="fas fa-user"></i> ${activity.guest_name}</span>
                                    <span><i class="fas fa-calendar"></i> ${new Date(activity.booking_date).toLocaleDateString('en-GB', {
                                        day: 'numeric',
                                        month: 'short',
                                        year: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    })}</span>
                                    ${activity.amount ? `
                                        <span class="activity-amount">
                                            <i class="fas fa-rupee-sign"></i> ${activity.amount}
                                        </span>
                                    ` : ''}
                                    <span><i class="fas fa-credit-card"></i> ${activity.payment_type}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                activitiesList.innerHTML = '<div class="text-center p-4">No recent activities found.</div>';
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching activities:', error);
            document.getElementById('activitiesList').innerHTML = 
                '<div class="text-center text-danger p-4">Error loading activities. Please try again.</div>';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    fetchActivities();
    // Refresh activities every 5 minutes
    setInterval(fetchActivities, 300000);
});
</script>

<?php include 'footer.php'; ?>