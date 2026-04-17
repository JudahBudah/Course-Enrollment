<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

$action = $_POST['action'] ?? '';
$upload_dir = __DIR__ . '/../uploads/announcements/';

function handle_media_uploads(string $upload_dir): array {
    $media = [];
    if (empty($_FILES['media']['name'][0])) return $media;

    $allowed_images = ['jpg','jpeg','png','gif','webp'];
    $allowed_videos = ['mp4','webm','ogg','mov'];

    foreach ($_FILES['media']['name'] as $i => $name) {
        if ($_FILES['media']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, array_merge($allowed_images, $allowed_videos))) continue;

        $filename = uniqid('ann_', true) . '.' . $ext;
        $dest = $upload_dir . $filename;
        if (!move_uploaded_file($_FILES['media']['tmp_name'][$i], $dest)) continue;

        $type = in_array($ext, $allowed_images) ? 'image' : 'video';
        $media[] = ['type' => $type, 'file' => $filename];
    }
    return $media;
}

if ($action === 'add') {
    $title    = trim($_POST['title']);
    $message  = trim($_POST['message']);
    $audience = $_POST['target_audience'] ?? 'all';
    $priority = $_POST['priority'] ?? 'normal';

    if (!$title || !$message) {
        header("Location: ../pages/admin/admin_announcements.php?error=missing_fields");
        die;
    }

    $media = handle_media_uploads($upload_dir);
    $media_json = !empty($media) ? json_encode($media) : null;

    $stmt = mysqli_prepare($con, "INSERT INTO announcements (admin_id, title, message, media, target_audience, priority, status) VALUES (?,?,?,?,?,?,'active')");
    $admin_id = $_SESSION['admin_id'];
    mysqli_stmt_bind_param($stmt, "isssss", $admin_id, $title, $message, $media_json, $audience, $priority);
    mysqli_stmt_execute($stmt);
    header("Location: ../pages/admin/admin_announcements.php?success=added");
    die;
}

if ($action === 'edit') {
    $id       = (int) $_POST['announcement_id'];
    $title    = trim($_POST['title']);
    $message  = trim($_POST['message']);
    $audience = $_POST['target_audience'] ?? 'all';
    $priority = $_POST['priority'] ?? 'normal';
    $status   = $_POST['status'] ?? 'active';

    if (!$title || !$message) {
        header("Location: ../pages/admin/admin_announcements.php?error=missing_fields");
        die;
    }

    // Fetch existing media
    $row = mysqli_fetch_assoc(mysqli_query($con, "SELECT media FROM announcements WHERE announcement_id = $id"));
    $existing = json_decode($row['media'] ?? '[]', true) ?: [];

    // Remove flagged files
    $remove = $_POST['remove_media'] ?? [];
    foreach ($remove as $filename) {
        $existing = array_filter($existing, fn($m) => $m['file'] !== $filename);
        @unlink($upload_dir . $filename);
    }
    $existing = array_values($existing);

    $new_media = handle_media_uploads($upload_dir);
    $all_media = array_merge($existing, $new_media);
    $media_json = !empty($all_media) ? json_encode($all_media) : null;

    $stmt = mysqli_prepare($con, "UPDATE announcements SET title=?, message=?, media=?, target_audience=?, priority=?, status=? WHERE announcement_id=?");
    mysqli_stmt_bind_param($stmt, "ssssssi", $title, $message, $media_json, $audience, $priority, $status, $id);
    mysqli_stmt_execute($stmt);
    header("Location: ../pages/admin/admin_announcements.php?success=updated");
    die;
}

if ($action === 'delete') {
    $id = (int) $_POST['announcement_id'];
    $row = mysqli_fetch_assoc(mysqli_query($con, "SELECT media FROM announcements WHERE announcement_id = $id"));
    foreach (json_decode($row['media'] ?? '[]', true) ?: [] as $m) {
        @unlink($upload_dir . $m['file']);
    }
    mysqli_query($con, "DELETE FROM announcements WHERE announcement_id = $id");
    header("Location: ../pages/admin/admin_announcements.php?success=deleted");
    die;
}

if ($action === 'toggle') {
    $id = (int) $_POST['announcement_id'];
    mysqli_query($con, "UPDATE announcements SET status = IF(status='active','archived','active') WHERE announcement_id = $id");
    header("Location: ../pages/admin/admin_announcements.php?success=updated");
    die;
}

header("Location: ../pages/admin/admin_announcements.php");
die;
?>
