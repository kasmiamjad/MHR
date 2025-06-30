<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
    // echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    // exit;
}

// Check if the user is logged in and is an admin

// if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {

//     http_response_code(401);

//     echo json_encode(['success' => false, 'message' => 'Unauthorized']);

//     exit;

// }
// Fetch available modules
$modules_query = "SELECT * FROM mhr_modules";
$modules_result = mysqli_query($conn, $modules_query);

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $modules = isset($_POST['modules']) ? $_POST['modules'] : [];

    // Insert user into the database
    $insert_user_query = "INSERT INTO mhr_users (username, password, role) VALUES ('$username', '$password', '$role')";
    if (mysqli_query($conn, $insert_user_query)) {
        $user_id = mysqli_insert_id($conn);

        // Assign modules to the user
        foreach ($modules as $module_id) {
            $assign_module_query = "INSERT INTO mhr_user_modules (user_id, module_id) VALUES ($user_id, $module_id)";
            mysqli_query($conn, $assign_module_query);
        }

        echo "<div class='alert alert-success'>User registered successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
    }
}
?>

<?php include 'header.php'; ?>
<div class="container mt-5">
    <h2>Register User</h2>
    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="role">Role:</label>
            <select class="form-control" id="role" name="role" required>
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="form-group">
            <label for="modules">Assign Modules:</label>
            <select class="form-control" id="modules" name="modules[]" multiple>
                <?php while ($module = mysqli_fetch_assoc($modules_result)) { ?>
                    <option value="<?= $module['id'] ?>"><?= $module['module_name'] ?></option>
                <?php } ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Register User</button>
    </form>
</div>

<?php include 'footer.php'; ?>
