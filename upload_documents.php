<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = intval($_POST['event_id']);
    $upload_dir = 'uploads/events/';
    $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
    $errors = [];
    $success_files = [];

    // Ensure the upload directory exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Loop through each uploaded file
    foreach ($_FILES['documents']['name'] as $key => $name) {
        $tmp_name = $_FILES['documents']['tmp_name'][$key];
        $size = $_FILES['documents']['size'][$key];
        $error = $_FILES['documents']['error'][$key];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        // Skip empty file inputs
        if (empty($name)) {
            continue;
        }

        // Validate file type
        if (!in_array($ext, $allowed_types)) {
            $errors[] = "$name is not a valid file type. Allowed types: " . implode(', ', $allowed_types);
            continue;
        }

        // Validate file size (optional, e.g., 5MB max)
        if ($size > 5 * 1024 * 1024) {
            $errors[] = "$name exceeds the maximum allowed size of 5MB.";
            continue;
        }

        // Save file
        $new_name = $event_id . '_' . time() . '_' . uniqid() . '.' . $ext;
        $destination = $upload_dir . $new_name;

        if (move_uploaded_file($tmp_name, $destination)) {
            // Save file details in DB
            $stmt = $conn->prepare("INSERT INTO mhr_event_documents (event_id, file_path) VALUES (?, ?)");
            $stmt->bind_param('is', $event_id, $destination);
            if ($stmt->execute()) {
                $success_files[] = $name;
            } else {
                $errors[] = "Failed to save $name in the database.";
            }
        } else {
            $errors[] = "Failed to upload $name.";
        }
    }

    // Redirect back with success or error messages
    if (!empty($success_files)) {
        $_SESSION['message'] = count($success_files) . " files uploaded successfully: " . implode(', ', $success_files);
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }

    header('Location: events_list.php');
    exit;
}
?>
