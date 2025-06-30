<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = intval($_POST['event_id']);
    $upload_dir = 'uploads/events/';
    $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
    $errors = [];

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    foreach ($_FILES['documents']['name'] as $key => $name) {
        $tmp_name = $_FILES['documents']['tmp_name'][$key];
        $size = $_FILES['documents']['size'][$key];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_types)) {
            $errors[] = "$name is not a valid file type.";
            continue;
        }

        if ($size > 5 * 1024 * 1024) {
            $errors[] = "$name exceeds the maximum allowed size.";
            continue;
        }

        $new_name = $event_id . '_' . time() . "_$name";
        $destination = $upload_dir . $new_name;

        if (move_uploaded_file($tmp_name, $destination)) {
            $stmt = $conn->prepare("INSERT INTO mhr_event_documents (event_id, file_path) VALUES (?, ?)");
            $stmt->bind_param('is', $event_id, $destination);
            $stmt->execute();
        } else {
            $errors[] = "Failed to upload $name.";
        }
    }

    if (empty($errors)) {
        $_SESSION['message'] = "Files uploaded successfully.";
    } else {
        $_SESSION['errors'] = $errors;
    }

    header('Location: upload_image.php?event_id=' . $event_id);
    exit;
}
?>

<?php include 'header.php'; ?>
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3>Upload Documents</h3>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
                <div class="alert alert-danger">
                    <?= implode('<br>', $_SESSION['errors']); unset($_SESSION['errors']); ?>
                </div>
            <?php endif; ?>

            <form action="upload_image.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="event_id" value="<?= $_GET['event_id']; ?>">
                <div class="mb-3">
                    <label for="documents" class="form-label">Select Images/Documents</label>
                    <input type="file" name="documents[]" id="documents" accept=".pdf,.jpg,.jpeg,.png" multiple class="form-control" onchange="previewFiles()">
                </div>
                <div class="mb-3" id="preview-container"></div>
                <button type="submit" class="btn btn-primary">Upload</button>
                <a href="events_list.php" class="btn btn-secondary">Back to Events List</a>
            </form>
        </div>
    </div>
</div>

<script>
function previewFiles() {
    const previewContainer = document.getElementById('preview-container');
    const files = document.getElementById('documents').files;
    previewContainer.innerHTML = '';

    for (const file of files) {
        const fileReader = new FileReader();
        fileReader.onload = function (event) {
            const div = document.createElement('div');
            div.style.marginBottom = '10px';
            div.innerHTML = `
                <img src="${event.target.result}" alt="${file.name}" style="width: 100px; height: 100px; object-fit: cover; margin-right: 10px;">
                <p>${file.name}</p>
            `;
            previewContainer.appendChild(div);
        };
        fileReader.readAsDataURL(file);
    }
}
</script>

<?php include 'footer.php'; ?>
