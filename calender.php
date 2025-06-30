<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Resort Availability Calendar</title>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #2196F3;
            --primary-dark: #1976D2;
            --surface-color: #ffffff;
            --background-color: #f8f9fa;
            --text-primary: #212121;
            --text-secondary: #757575;
            --success-color: #4CAF50;
            --error-color: #f44336;
            --available-color: #e8f5e9;
            --booked-color: #ffebee;
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
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .app-container {
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .calendar-card {
            background: var(--surface-color);
            border-radius: var(--border-radius);
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: var(--primary-color);
            color: white;
        }

        .month-display {
            font-size: 20px;
            font-weight: 600;
        }

        .nav-button {
            background: none;
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }

        .nav-button:active {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            padding: 20px;
        }

        .day-header {
            text-align: center;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 14px;
            padding: 8px 0;
        }

        .calendar-day {
            aspect-ratio: 1;
            border-radius: 12px;
            padding: 8px;
            text-align: center;
            position: relative;
            background: var(--surface-color);
            border: 2px solid #e0e0e0;
            transition: all 0.2s ease;
        }

        .calendar-day.past {
            background: #f5f5f5;
            color: var(--text-secondary);
            border-color: #eee;
        }

        .calendar-day.booked {
            background: var(--booked-color);
            border-color: var(--error-color);
            color: var(--error-color);
        }

        .calendar-day.available {
            background: var(--available-color);
            border-color: var(--success-color);
            color: var(--success-color);
        }

        .day-number {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .status-badge {
            font-size: 10px;
            font-weight: 500;
            padding: 2px 4px;
            border-radius: 4px;
            position: absolute;
            bottom: 4px;
            left: 4px;
            right: 4px;
        }

        .legend {
            display: flex;
            justify-content: center;
            gap: 16px;
            padding: 16px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--surface-color);
            border-radius: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
        }

        .contact-card {
            background: var(--surface-color);
            border-radius: var(--border-radius);
            padding: 24px;
            text-align: center;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }

        .contact-card h3 {
            color: var(--text-primary);
            margin-bottom: 16px;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 24px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--text-secondary);
        }

        .contact-item a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .action-buttons {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }

        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn:active {
            transform: scale(0.98);
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .calendar-header {
                padding: 16px;
            }

            .calendar-grid {
                gap: 4px;
                padding: 12px;
            }

            .calendar-day {
                padding: 4px;
                border-radius: 8px;
            }

            .day-number {
                font-size: 14px;
            }

            .status-badge {
                font-size: 8px;
            }

            .contact-card {
                padding: 16px;
            }
        }

        @media (hover: none) {
            .btn:active {
                background: var(--primary-dark);
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="calendar-card">
            <div class="calendar-header">
                <button class="nav-button" onclick="previousMonth()">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <h2 class="month-display" id="monthDisplay"></h2>
                <button class="nav-button" onclick="nextMonth()">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="calendar-grid" id="calendarGrid"></div>
        </div>

        <div class="legend">
            <div class="legend-item">
                <div class="legend-color" style="background: var(--available-color); border: 2px solid var(--success-color)"></div>
                <span>Available</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: var(--booked-color); border: 2px solid var(--error-color)"></div>
                <span>Booked</span>
            </div>
        </div>

        <div class="contact-card">
            <h3>Book Your Stay</h3>
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <a href="tel:9904074848">9904074848</a>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <a href="mailto:booking@mumbrahillresort.com">booking@mumbrahillresort.com</a>
                </div>
            </div>
            <div class="action-buttons">
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </a>
                <a href="calculator.php" class="btn btn-primary">
                    <i class="fas fa-calculator"></i>
                    Booking Calculator
                </a>
            </div>
        </div>
    </div>

    <script>
        let currentDate = new Date();
        let events = {};

        function renderCalendar() {
            const grid = document.getElementById('calendarGrid');
            const monthDisplay = document.getElementById('monthDisplay');

            // Clear previous calendar
            grid.innerHTML = '';

            // Set month display
            const monthYear = currentDate.toLocaleString('default', { month: 'long', year: 'numeric' });
            monthDisplay.textContent = monthYear;

            // Add day headers
            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            days.forEach(day => {
                const dayHeader = document.createElement('div');
                dayHeader.className = 'day-header';
                dayHeader.textContent = day;
                grid.appendChild(dayHeader);
            });

            // Get first day of month and total days
            const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);

            // Add empty cells for days before start of month
            for (let i = 0; i < firstDay.getDay(); i++) {
                grid.appendChild(document.createElement('div'));
            }

            // Add days of month
            for (let day = 1; day <= lastDay.getDate(); day++) {
                const dayCell = document.createElement('div');
                dayCell.className = 'calendar-day';

                const dayNumber = document.createElement('div');
                dayNumber.className = 'day-number';
                dayNumber.textContent = day;
                dayCell.appendChild(dayNumber);

                const dateStr = `${currentDate.getFullYear()}-${(currentDate.getMonth()+1).toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
                
                const currentDateObj = new Date();
                const cellDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
                
                if (cellDate < new Date(currentDateObj.setHours(0,0,0,0))) {
                    dayCell.classList.add('past');
                } else if (events[dateStr] && events[dateStr].booked) {
                    dayCell.classList.add('booked');
                    const badge = document.createElement('div');
                    badge.className = 'status-badge';
                    badge.textContent = 'Booked';
                    dayCell.appendChild(badge);
                } else {
                    dayCell.classList.add('available');
                    const badge = document.createElement('div');
                    badge.className = 'status-badge';
                    badge.textContent = 'Available';
                    dayCell.appendChild(badge);
                }

                grid.appendChild(dayCell);
            }
        }

        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
            loadEvents();
        }

        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
            loadEvents();
        }

        function loadEvents() {
            fetch('get_public_events.php')
                .then(response => response.json())
                .then(data => {
                    events = data;
                    renderCalendar();
                });
        }

        // Initial load
        loadEvents();
    </script>
</body>
</html>