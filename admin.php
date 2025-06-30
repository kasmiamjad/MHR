<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'config.php';

// Fetch users
$users_query = "SELECT * FROM users";
$users_result = mysqli_query($conn, $users_query);

// Fetch modules
$modules_query = "SELECT * FROM modules";
$modules_result = mysqli_query($conn, $modules_query);

// Assign modules to users
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign'])) {
    $user_id = $_POST['user_id'];
    $module_ids = $_POST['modules'];

    // Clear existing assignments
    $delete_query = "DELETE FROM user_modules WHERE user_id = $user_id";
    mysqli_query($conn, $delete_query);

    // Insert new assignments
    foreach ($module_ids as $module_id) {
        $assign_query = "INSERT INTO user_modules (user_id, module_id) VALUES ($user_id, $module_id)";
        mysqli_query($conn, $assign_query);
    }

    echo "Modules assigned successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - User Management</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>User Management</h2>
        <form method="POST">
            <div class="form-group">
                <label for="user">Select User:</label>
                <select name="user_id" id="user" class="form-control" required>
                    <?php while ($user = mysqli_fetch_assoc($users_result)) { ?>
                        <option value="<?= $user['id'] ?>"><?= $user['username'] ?> (<?= $user['role'] ?>)</option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label for="modules">Assign Modules:</label>
                <select name="modules[]" id="modules" class="form-control" multiple required>
                    <?php while ($module = mysqli_fetch_assoc($modules_result)) { ?>
                        <option value="<?= $module['id'] ?>"><?= $module['module_name'] ?></option>
                    <?php } ?>
                </select>
            </div>
            <button type="submit" name="assign" class="btn btn-primary">Assign Modules</button>
        </form>
    </div>
</body>
</html>
