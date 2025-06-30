<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mumbra Hill Resort</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2a2a72;
            --danger: #dc3545;
            --danger-light: #fff1f1;
            --text-dark: #333;
            --border-radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: #ffffff;
            padding: 20px;
            max-width: 100%;
            overflow-x: hidden;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .resort-title {
            color: var(--primary);
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .resort-subtitle {
            color: #666;
            text-align: center;
            font-size: 1.2rem;
            margin-bottom: 30px;
        }

        .calendar {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .calendar-header {
            background: var(--primary);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .month-year {
            font-size: 1.75rem;
            font-weight: 500;
            margin: 0;
            flex-grow: 1;
            text-align: center;
        }

        .year {
            display: block;
            font-size: 1.5rem;
            margin-top: 5px;
        }

        .nav-button {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .nav-button:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            padding: 10px;
            background: #f8f9fa;
            gap: 5px;
        }

        .weekdays div {
            font-weight: 600;
            color: var(--primary);
            text-align: center;
            font-size: 0.9rem;
            padding: 10px;
        }

        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            padding: 10px;
            gap: 5px;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-size: 1.1rem;
            background: #f8f9fa;
            border-radius: 8px;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .past-date {
            color: #aaa;
            background: #f5f5f5;
            cursor: not-allowed;
        }

        .booked {
            background: var(--danger-light);
        }

        .status-badge {
            position: absolute;
            bottom: 5px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 12px;
            white-space: nowrap;
        }

        .booked .status-badge {
            background: var(--danger);
            color: white;
        }

        .available .status-badge {
            background: #4CAF50;
            color: white;
        }

        .legend {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 20px;
            padding: 15px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 10px;
            }

            .resort-title {
                font-size: 2rem;
            }

            .resort-subtitle {
                font-size: 1rem;
            }

            .calendar-header {
                padding: 15px;
            }

            .month-year {
                font-size: 1.5rem;
            }

            .nav-button {
                padding: 8px 15px;
                font-size: 0.8rem;
            }

            .weekdays div {
                font-size: 0.8rem;
                padding: 5px;
            }

            .calendar-day {
                font-size: 0.9rem;
            }

            .status-badge {
                font-size: 0.6rem;
                padding: 1px 6px;
            }

            .legend {
                flex-direction: column;
                align-items: center;
            }
        }

        @media (max-width: 360px) {
            .calendar-day {
                font-size: 0.8rem;
            }
        }
        /* Update these styles in your CSS */

:root {
    --primary: #2a2a72;
    --primary-light: #45458b;
    --danger: #dc3545;
    --danger-light: #fff1f1;
    --text-dark: #333;
    --border-radius: 12px;
}

.container {
    max-width: 100%;
    width: 100%;
    margin: 0 auto;
    padding: 0 10px;
}

.calendar-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    padding: 15px;
    gap: 8px;
}

.calendar-day {
    aspect-ratio: 1;
    padding: 5px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    font-size: 1.2rem;
    font-weight: 500;
}

.contact-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 2rem;
    margin-top: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    text-align: center;
}

.contact-card h3 {
    color: var(--primary);
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
}

.contact-info {
    margin-bottom: 2rem;
}

.contact-item {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.contact-item i {
    color: var(--primary);
    font-size: 1.2rem;
}

.contact-item a {
    color: var(--text-dark);
    text-decoration: none;
    transition: color 0.3s ease;
}

.contact-item a:hover {
    color: var(--primary);
}

.action-buttons {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-top: 1.5rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.8rem 1.5rem;
    border-radius: var(--border-radius);
    font-size: 1rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: var(--primary);
    color: white;
    box-shadow: 0 4px 15px rgba(42, 42, 114, 0.2);
}

.btn-primary:hover {
    background: var(--primary-light);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(42, 42, 114, 0.3);
}

@media (max-width: 480px) {
    .container {
        padding: 0 5px;
    }
    
    .calendar {
        margin: 0 -5px;
    }
    
    .calendar-days {
        padding: 10px 5px;
        gap: 4px;
    }

    .calendar-day {
        font-size: 1rem;
        padding: 2px;
    }

    .action-buttons {
        grid-template-columns: 1fr;
    }

    .btn {
        padding: 1rem;
        font-size: 1.1rem;
    }

    .contact-item {
        flex-direction: column;
        gap: 0.5rem;
    }

    .contact-item i {
        font-size: 1.5rem;
    }
}

/* Add this to ensure consistent spacing */
.status-badge {
    font-size: 0.75rem;
    padding: 3px 8px;
    bottom: 3px;
}

/* Improve calendar header visibility */
.month-year {
    font-size: 2rem;
}

.nav-button {
    padding: 12px 24px;
    font-size: 1rem;
}
    </style>
</head>
<body>
    <div class="container">
        <h1 class="resort-title">Mumbra Hill Resort</h1>
        <p class="resort-subtitle">Check availability and book your stay</p>

        <div class="calendar">
            <div class="calendar-header">
                <button class="nav-button" onclick="previousMonth()">
                    <i class="fas fa-chevron-left"></i> Previous
                </button>
                <div class="month-year">
                    <span id="monthDisplay">December</span>
                    <span class="year" id="yearDisplay">2024</span>
                </div>
                <button class="nav-button" onclick="nextMonth()">
                    Next <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <div class="weekdays">
                <div>SUN</div>
                <div>MON</div>
                <div>TUE</div>
                <div>WED</div>
                <div>THU</div>
                <div>FRI</div>
                <div>SAT</div>
            </div>

            <div class="calendar-days" id="calendarGrid">
                <!-- Days will be inserted here by JavaScript -->
            </div>
        </div>

        <div class="legend">
            <div class="legend-item">
                <div class="legend-color" style="background: #f8f9fa;"></div>
                <span>Available</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: var(--danger-light);"></div>
                <span>Booked</span>
            </div>
        </div>
    </div>
    <div class="contact-card">
    <h3>Book Your Dream Stay</h3>
    <div class="contact-info">
        <div class="contact-item">
            <i class="fas fa-phone-alt"></i>
            <a href="tel:9904074848">+91 9904074848</a>
        </div>
        <div class="contact-item">
            <i class="fas fa-envelope"></i>
            <a href="mailto:booking@mumbrahillresort.com">booking@mumbrahillresort.com</a>
        </div>
    </div>
    <div class="action-buttons">
        <a href="calculator.php" class="btn btn-primary">
            <i class="fas fa-calculator mr-2"></i>
            Booking Calculator
        </a>
        <a href="login.php" class="btn btn-primary">
            <i class="fas fa-sign-in-alt mr-2"></i>
            Login
        </a>
    </div>
</div>
    <script>
        let currentDate = new Date();
        let events = {};

        function renderCalendar() {
            const grid = document.getElementById('calendarGrid');
            const monthDisplay = document.getElementById('monthDisplay');
            const yearDisplay = document.getElementById('yearDisplay');
            
            grid.innerHTML = '';
            
            monthDisplay.textContent = currentDate.toLocaleString('default', { month: 'long' });
            yearDisplay.textContent = currentDate.getFullYear();

            const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);

            // Empty cells for days before first day of month
            for (let i = 0; i < firstDay.getDay(); i++) {
                const emptyCell = document.createElement('div');
                emptyCell.className = 'calendar-day';
                grid.appendChild(emptyCell);
            }

            // Days of the month
            for (let day = 1; day <= lastDay.getDate(); day++) {
                const dayCell = document.createElement('div');
                dayCell.className = 'calendar-day';
                dayCell.textContent = day;

                const dateStr = `${currentDate.getFullYear()}-${(currentDate.getMonth()+1).toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
                const today = new Date();
                today.setHours(0,0,0,0);
                const cellDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);

                if (cellDate < today) {
                    dayCell.classList.add('past-date');
                } else if (events[dateStr]) {
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