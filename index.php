<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
    // echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    // exit;
}
if($_SESSION['role'] == 'user') {
    header("Location: booking.php");
    exit;
}
include 'header.php';
?>

<style>
:root {
    --primary-color: #2196F3;
    --primary-dark: #1976D2;
    --surface-color: #ffffff;
    --background-color: #f8f9fa;
    --text-primary: #212121;
    --text-secondary: #757575;
    --error-color: #f44336;
    --success-color: #4CAF50;
    --border-radius: 16px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    -webkit-tap-highlight-color: transparent;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

body {
    background: var(--background-color);
    min-height: 100vh;
}

.app-container {
    max-width: 100%;
    margin: 0 auto;
    padding: 16px;
    animation: fadeIn 0.3s ease-out;
}

@media (min-width: 768px) {
    .app-container {
        max-width: 1200px;
        padding: 24px;
    }
}

.calendar-header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border-radius: var(--border-radius);
    padding: 16px;
    margin-bottom: 16px;
    box-shadow: 0 4px 20px rgba(33, 150, 243, 0.15);
}

.month-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.nav-btn {
    background: rgba(255, 255, 255, 0.15);
    border: none;
    color: white;
    padding: 8px 16px;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    min-width: 44px;
    min-height: 44px;
    justify-content: center;
}

@media (min-width: 768px) {
    .nav-btn {
        padding: 12px 24px;
    }
}

.nav-btn:active {
    background: rgba(255, 255, 255, 0.25);
    transform: scale(0.98);
}

#monthDisplay {
    color: white;
    font-size: 18px;
    font-weight: 600;
    margin: 0;
    text-align: center;
}

@media (min-width: 768px) {
    #monthDisplay {
        font-size: 24px;
    }
}

.calendar-card {
    background: var(--surface-color);
    border-radius: var(--border-radius);
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    overflow: hidden;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
    padding: 8px;
}

@media (min-width: 768px) {
    .calendar-grid {
        gap: 12px;
        padding: 24px;
    }
}

.day-header {
    text-align: center;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 12px;
    padding: 8px 4px;
    text-transform: uppercase;
}

@media (min-width: 768px) {
    .day-header {
        font-size: 14px;
        padding: 8px;
    }
}

.calendar-day {
    aspect-ratio: 1;
    background: var(--background-color);
    border-radius: 8px;
    padding: 4px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    border: 2px solid transparent;
    min-height: 40px;
    touch-action: manipulation;
}

@media (min-width: 768px) {
    .calendar-day {
        border-radius: 12px;
        padding: 12px;
        min-height: 80px;
    }
}

.calendar-day:active {
    transform: scale(0.95);
}

.day-number {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 2px;
}

@media (min-width: 768px) {
    .day-number {
        font-size: 16px;
        margin-bottom: 4px;
    }
}

.calendar-day.empty {
    background: transparent;
    cursor: default;
}

.calendar-day.has-event {
    background: var(--primary-color);
    color: white;
}

.event-info {
    font-size: 10px;
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    width: 100%;
    opacity: 0.9;
    display: none;
}

@media (min-width: 768px) {
    .event-info {
        font-size: 12px;
        display: block;
    }
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    animation: fadeIn 0.2s ease;
}

.modal-content {
    background: var(--surface-color);
    width: calc(100% - 32px);
    max-width: 400px;
    margin: 16px auto;
    padding: 20px;
    border-radius: var(--border-radius);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    animation: slideUp 0.3s ease;
    position: relative;
    top: 50%;
    transform: translateY(-50%);
}

.modal-title {
    color: var(--text-primary);
    margin-bottom: 16px;
    font-size: 18px;
    font-weight: 600;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    color: var(--text-secondary);
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s;
    background: var(--background-color);
}

.form-control:focus {
    border-color: var(--primary-color);
    background: var(--surface-color);
    outline: none;
    box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.1);
}

.button-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 20px;
}

@media (min-width: 768px) {
    .button-group {
        flex-direction: row;
        gap: 12px;
    }
}

.modal-btn {
    padding: 12px;
    border-radius: 12px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 16px;
    min-height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
}

.save-btn {
    background: var(--primary-color);
    color: white;
    order: -1;
}

@media (min-width: 768px) {
    .save-btn {
        order: 0;
        flex: 2;
    }
}

.save-btn:active {
    transform: scale(0.98);
    background: var(--primary-dark);
}

.cancel-btn {
    background: var(--background-color);
    color: var(--text-primary);
}

.delete-btn {
    background: var(--error-color);
    color: white;
}

.delete-btn:active {
    background: #d32f2f;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(calc(-50% + 20px)); }
    to { opacity: 1; transform: translateY(-50%); }
}
</style>

<div class="app-container">
    <div class="calendar-header">
        <div class="month-navigation">
            <button class="nav-btn" onclick="previousMonth()">
                <i class="fas fa-chevron-left"></i>
            </button>
            <h2 id="monthDisplay"></h2>
            <button class="nav-btn" onclick="nextMonth()">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <div class="calendar-card">
        <div class="calendar-grid" id="calendarGrid"></div>
    </div>
</div>

<div class="modal" id="eventModal">
    <div class="modal-content">
        <h3 class="modal-title">Booking Details</h3>
        <form id="eventForm">
            <input type="hidden" id="selectedDate">
            <div class="form-group">
                <label for="name">Guest Name</label>
                <input type="text" id="name" class="form-control" required placeholder="Enter guest name">
            </div>
            <div class="form-group">
                <label for="event">Event Details</label>
                <input type="text" id="event" class="form-control" required placeholder="Enter event details">
            </div>
            <div class="button-group">
                <button type="submit" class="modal-btn save-btn">
                    <i class="fas fa-save"></i> Save Booking
                </button>
                <button type="button" class="modal-btn cancel-btn" onclick="closeModal()">Cancel</button>
                <button type="button" class="modal-btn delete-btn" onclick="deleteEvent()" id="deleteBtn" style="display:none">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            </div>
        </form>
    </div>
</div>


    <script>

let currentDate = new Date();
let events = {};

function renderCalendar() {
    const grid = document.getElementById('calendarGrid');
    const monthDisplay = document.getElementById('monthDisplay');
    grid.innerHTML = '';
    
    const monthYear = currentDate.toLocaleString('default', { month: 'long', year: 'numeric' });
    monthDisplay.textContent = monthYear;
    
    // Day headers
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    days.forEach(day => {
        const dayHeader = document.createElement('div');
        dayHeader.className = 'day-header';
        dayHeader.textContent = day;
        grid.appendChild(dayHeader);
    });

    const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);

    // Empty cells
    for (let i = 0; i < firstDay.getDay(); i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'calendar-day empty';
        grid.appendChild(emptyDay);
    }

    // Days with events
    for (let day = 1; day <= lastDay.getDate(); day++) {
        const dayCell = document.createElement('div');
        dayCell.className = 'calendar-day';
        dayCell.textContent = day;

        const dateStr = `${currentDate.getFullYear()}-${(currentDate.getMonth()+1).toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;

        if (events[dateStr]) {
            dayCell.classList.add('has-event');
            const eventInfo = document.createElement('div');
            eventInfo.className = 'event-info';
            eventInfo.textContent = events[dateStr].name;
            dayCell.appendChild(eventInfo);
        }

        dayCell.onclick = () => openModal(dateStr);
        grid.appendChild(dayCell);
    }
}

        function previousMonth() {

            currentDate.setMonth(currentDate.getMonth() - 1);

            renderCalendar();

        }



        function nextMonth() {

            currentDate.setMonth(currentDate.getMonth() + 1);

            renderCalendar();

        }



        function openModal(date) {

            document.getElementById('selectedDate').value = date;

            const modal = document.getElementById('eventModal');

            const nameInput = document.getElementById('name');

            const eventInput = document.getElementById('event');

            const deleteBtn = document.getElementById('deleteBtn');



            if (events[date]) {

                nameInput.value = events[date].name;

                eventInput.value = events[date].event;

                deleteBtn.style.display = 'block';

            } else {

                nameInput.value = '';

                eventInput.value = '';

                deleteBtn.style.display = 'none';

            }



            modal.style.display = 'block';

        }



        function closeModal() {

            document.getElementById('eventModal').style.display = 'none';

        }



        function deleteEvent() {

            const date = document.getElementById('selectedDate').value;



            // AJAX call to delete event

            fetch('delete_event.php', {

                method: 'POST',

                headers: {

                    'Content-Type': 'application/x-www-form-urlencoded',

                },

                body: `date=${date}`

            })

            .then(response => response.json())

            .then(data => {

                if (data.success) {

                    delete events[date];

                    renderCalendar();

                    closeModal();

                } else {

                    alert('Error deleting event');

                }

            });

        }



document.getElementById('eventForm').onsubmit = function(e) {

    e.preventDefault();

    const date = document.getElementById('selectedDate').value;

    const name = document.getElementById('name').value;

    const event = document.getElementById('event').value;



    // AJAX call to save event

    fetch('save_event.php', {

        method: 'POST',

        headers: {

            'Content-Type': 'application/x-www-form-urlencoded',

        },

        body: `date=${date}&name=${name}&event=${event}`

    })

    .then(response => response.text()) // Get raw response text

    .then(text => {

        console.log(text); // Log response for debugging

        return JSON.parse(text); // Parse JSON if valid

    })

    .then(data => {

        if (data.success) {

            events[date] = { name, event };

            renderCalendar();

            closeModal();

        } else {

            alert(data.message || 'Error saving event');

        }

    })

    .catch(error => {

        console.error('Error parsing JSON:', error);

        alert('An unexpected error occurred. Check the console for details.');

    });

};





        // Initial load of calendar

        renderCalendar();





        function handleUnauthorized(response) {

    if (response.status === 401) {

        window.location.href = 'login.php';

        return;

    }

    return response;

}



        // Load existing events

        fetch('get_events.php')

      .then(handleUnauthorized)

      .then(response => response.json())

      .then(data => {

          events = data;

          renderCalendar();

      });









            function logout() {

    window.location.href = 'logout.php';

}

    </script>
    
<?php include 'footer.php'; ?>
   
