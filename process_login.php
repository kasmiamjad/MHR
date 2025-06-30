<?php
session_start();
 require 'config.php'; // Include database credentials

// // Establish database connection
 //$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle login form submission
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Validate user credentials
    $user_query = "SELECT * FROM mhr_users WHERE username = '$username'";
    $user_result = mysqli_query($conn, $user_query);

    if (mysqli_num_rows($user_result) > 0) {
        $user = mysqli_fetch_assoc($user_result);

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Fetch user's role
            $role = $user['role'];

            // Store user info and role in session
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $role;
            //echo  'success';    
            // Redirect based on role
            // if ($role === 'admin') {
                 header("Location: index.php");
            // } else {
            //     header("Location: user_dashboard.php");
            // }
            exit;
        } else {
            echo "Invalid password. Please try again.";
            $error = "Invalid password. Please try again.";
        }
    } else {
        echo "User ID not found. Please check your credentials.";
        $error = "User ID not found. Please check your credentials.";
    }
?>
