<!-- index.php -->
 
<?php 
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
    // echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    // exit;
}
include 'header.php'; ?>
<style>
:root {
    --primary-color: #2196F3;
    --primary-dark: #1976D2;
    --surface-color: #ffffff;
    --background-color: #f8f9fa;
    --text-primary: #212121;
    --text-secondary: #757575;
    --border-radius: 16px;
}

.booking-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 0 16px;
}

.booking-header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    padding: env(safe-area-inset-top) 20px 20px;
    border-radius: var(--border-radius);
    margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(33, 150, 243, 0.15);
}

.form-card {
    background: var(--surface-color);
    border-radius: var(--border-radius);
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.form-section-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    color: var(--text-secondary);
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 500;
}

.input-container {
    position: relative;
}

.input-container i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
}

.form-control {
    width: 100%;
    padding: 12px 16px 12px 48px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    font-size: 16px;
    background: var(--background-color);
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: var(--primary-color);
    background: var(--surface-color);
    outline: none;
    box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.1);
}

.payment-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 20px;
}

.payment-option {
    display: none;
}

.payment-option + label {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 16px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-option:checked + label {
    border-color: var(--primary-color);
    background: rgba(33, 150, 243, 0.1);
}

.payment-option + label i {
    font-size: 20px;
    color: var(--text-secondary);
}

.payment-option:checked + label i {
    color: var(--primary-color);
}

.submit-btn {
    background: var(--primary-color);
    color: white;
    width: 100%;
    padding: 16px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    margin-top: 20px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.submit-btn:active {
    transform: scale(0.98);
    background: var(--primary-dark);
}

/* Flatpickr customization */
.flatpickr-calendar {
    border-radius: 16px !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1) !important;
    border: none !important;
}

.flatpickr-day.selected {
    background: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
}
<style>
  /* Container styles */
  .form-group {
    margin-bottom: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
  }
  
  /* Label styles */
  .form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
    font-size: 16px;
  }
  
  /* Input container styles */
  .input-container {
    position: relative;
    border-radius: 8px;
    border: 2px solid #ddd;
    overflow: hidden;
    transition: all 0.3s ease;
  }
  
  /* Focus state for container */
  .input-container:focus-within {
    border-color: #4CAF50;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
  }
  
  /* Input styles */
  .form-control {
    width: 100%;
    padding: 12px 12px 12px 40px;
    border: none;
    outline: none;
    font-size: 16px;
    box-sizing: border-box;
  }
  
  /* Icon styles */
  .input-container i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #666;
  }
  
  /* Valid state */
  .form-control.is-valid {
    background-color: #f8fff8;
  }
  
  .valid-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #4CAF50;
    display: none;
  }
  
  .form-control.is-valid + .valid-icon {
    display: block;
  }
  
  /* Helper text */
  .form-text {
    margin-top: 6px;
    font-size: 12px;
    color: #666;
  }
  
  /* Error state */
  .form-control.is-invalid {
    border-color: #dc3545;
  }
  
  .invalid-feedback {
    color: #dc3545;
    font-size: 12px;
    margin-top: 6px;
    display: none;
  }
@media (max-width: 768px) {
    .booking-container {
        padding: 0 12px;
    }

    .form-card {
        padding: 16px;
    }

    .payment-options {
        grid-template-columns: 1fr;
    }
}
</style>
<div class="booking-container">
    <div class="booking-header">
        <h2>New Booking</h2>
        <p>Enter booking details below</p>
    </div>

    <form action="insert_booking.php" method="POST">
        <!-- Guest Information -->
        <div class="form-card">
            <div class="form-section-title">
                <i class="fas fa-user"></i>
                Guest Information
            </div>
            <div class="form-group">
                <label for="name">Full Name</label>
                <div class="input-container">
                    <i class="fas fa-user"></i>
                    <input type="text" class="form-control" id="name" name="name" required placeholder="Enter guest name">
                </div>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <div class="input-container">
                    <i class="fas fa-phone"></i>
                    <input 
                    type="tel" 
                    class="form-control" 
                    id="phone" 
                    name="phone" 
                    required 
                    placeholder="Enter 10-digit phone number"
                    maxlength="10"
                    pattern="[0-9]{10}"
                    oninput="validatePhone(this)"
                    autocomplete="tel"
                    >
                    <small id="phoneHelp" class="form-text text-muted">Enter a valid 10-digit phone number for WhatsApp messages.</small>
                    <div id="phoneError" class="invalid-feedback">Please enter exactly 10 digits.</div>
                </div>
                </div>
        </div>

        <!-- Booking Details -->
        <div class="form-card">
            <div class="form-section-title">
                <i class="fas fa-calendar-alt"></i>
                Booking Details
            </div>
            <div class="form-group">
                <label for="checkin">Check-In Date</label>
                <div class="input-container">
                    <i class="fas fa-calendar-check"></i>
                    <input type="text" class="form-control" id="checkin" name="checkin" required placeholder="Select check-in date">
                </div>
            </div>
            <div class="form-group">
                <label for="checkout">Check-Out Date</label>
                <div class="input-container">
                    <i class="fas fa-calendar-times"></i>
                    <input type="text" class="form-control" id="checkout" name="checkout" required placeholder="Select check-out date">
                </div>
            </div>
            <div class="form-group">
                <label for="adults">Number of Adults</label>
                <div class="input-container">
                    <i class="fas fa-user-friends"></i>
                    <input type="number" class="form-control" id="adults" name="adults" required placeholder="Enter number of adults">
                </div>
            </div>
            <div class="form-group">
                <label for="kids">Number of Kids</label>
                <div class="input-container">
                    <i class="fas fa-child"></i>
                    <input type="number" class="form-control" id="kids" name="kids" required placeholder="Enter number of kids">
                </div>
            </div>
            <div class="form-group">
                <label for="package">Package Type</label>
                <div class="input-container">
                    <i class="fas fa-box"></i>
                    <select class="form-control" id="package" name="package" required>
                        <option value="">Select package</option>
                        <option value="without_food">Without Food</option>
                        <option value="with_food">With Food</option>
                    </select>
                </div>
            </div>
            <br/>
        </div>

        <!-- Payment Details -->
        <div class="form-card">
            <div class="form-section-title">
                <i class="fas fa-money-bill-wave"></i>
                Payment Details
            </div>
            <div class="form-group">
                <label for="payment_mode">Payment Mode</label>
                <div class="payment-options">
                    <input type="radio" id="cash" name="payment_mode" value="cash" class="payment-option" required>
                    <label for="cash">
                        <i class="fas fa-money-bill-wave"></i>
                        Cash
                    </label>
                    
                    <input type="radio" id="online" name="payment_mode" value="online" class="payment-option">
                    <label for="online">
                        <i class="fas fa-credit-card"></i>
                        Online
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label for="total_amount">Total Amount</label>
                <div class="input-container">
                    <i class="fas fa-rupee-sign"></i>
                    <input type="number" class="form-control" id="total_amount" name="total_amount" required placeholder="Enter total amount">
                </div>
            </div>
            <div class="form-group">
                <label for="advance_amount">Advance Amount</label>
                <div class="input-container">
                    <i class="fas fa-hand-holding-usd"></i>
                    <input type="number" class="form-control" id="advance_amount" name="advance_amount" required placeholder="Enter advance amount">
                </div>
            </div>
        </div>

        <button type="submit" class="submit-btn">
            <i class="fas fa-check"></i>
            Confirm Booking
        </button>
    </form>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const checkin = flatpickr("#checkin", {
            dateFormat: "Y-m-d",
            minDate: "today",
            onChange: function (selectedDates, dateStr, instance) {
                checkout.set("minDate", dateStr);
            }
        });

        const checkout = flatpickr("#checkout", {
            dateFormat: "Y-m-d",
            minDate: "today",
        });
    });
    function validatePhone(input) {
  // Get the input value and remove any non-digit characters
  let phoneNumber = input.value.replace(/\D/g, '');
  
  // Restrict to only 10 digits maximum
  phoneNumber = phoneNumber.substring(0, 10);
  
  // Update the input with cleaned value (digits only)
  input.value = phoneNumber;
  
  // Check if the phone number is valid (exactly 10 digits)
  const isValid = phoneNumber.length === 10;
  
  // Show/hide validation message
  const errorElement = document.getElementById('phoneError');
  
  if (isValid) {
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
    errorElement.style.display = 'none';
  } else {
    input.classList.remove('is-valid');
    input.classList.add('is-invalid');
    errorElement.style.display = 'block';
  }
  
  return isValid;
}

// Add form submission validation
document.querySelector('form').addEventListener('submit', function(event) {
  const phoneInput = document.getElementById('phone');
  if (!validatePhone(phoneInput)) {
    event.preventDefault();
    alert("Please enter a valid 10-digit phone number before submitting.");
  }
});
</script>



<?php include 'footer.php'; ?>

