<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

// Ensure table exists
mysqli_query($con, "CREATE TABLE IF NOT EXISTS calendar_events (
    event_id    INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    event_date  DATE NOT NULL,
    end_date    DATE DEFAULT NULL,
    event_time  VARCHAR(50) DEFAULT NULL,
    color       VARCHAR(20) DEFAULT '#8C1C24',
    audience    ENUM('all','students','faculty','applicants') DEFAULT 'all',
    image       VARCHAR(255) DEFAULT NULL,
    created_by  INT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$action = $_POST['action'] ?? '';
$upload_dir = __DIR__ . '/../uploads/events/';

function upload_event_image(string $upload_dir): ?string {
    if (empty($_FILES['image']['name']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['jpg','jpeg','png','gif','webp'];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return null;
    if ($_FILES['image']['size'] > 5 * 1024 * 1024) return null;
    $filename = 'event_' . uniqid('', true) . '.' . $ext;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) return null;
    return $filename;
}

if ($action === 'add') {
    $title      = trim($_POST['title']);
    $desc       = trim($_POST['description'] ?? '');
    $date       = $_POST['event_date'];
    $end_date   = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $time       = trim($_POST['event_time'] ?? '');
    $color      = $_POST['color'] ?? '#8C1C24';
    $audience   = $_POST['audience'] ?? 'all';
    $admin_id   = $_SESSION['admin_id'];

    if (!$title || !$date) { header("Location: ../pages/admin/admin_calendar.php?error=missing"); die; }

    $image = upload_event_image($upload_dir);
    $stmt = mysqli_prepare($con, "INSERT INTO calendar_events (title, description, event_date, end_date, event_time, color, audience, image, created_by) VALUES (?,?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'ssssssssi', $title, $desc, $date, $end_date, $time, $color, $audience, $image, $admin_id);
    mysqli_stmt_execute($stmt);
    log_activity($con, 'Added calendar event', 'calendar', $title . ' (' . $date . ')');
    header("Location: ../pages/admin/admin_calendar.php?success=added");
    die;
}

if ($action === 'edit') {
    $id       = (int)$_POST['event_id'];
    $title    = trim($_POST['title']);
    $desc     = trim($_POST['description'] ?? '');
    $date     = $_POST['event_date'];
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $time     = trim($_POST['event_time'] ?? '');
    $color    = $_POST['color'] ?? '#8C1C24';
    $audience = $_POST['audience'] ?? 'all';

    if (!$title || !$date) { header("Location: ../pages/admin/admin_calendar.php?error=missing"); die; }

    $new_image = upload_event_image($upload_dir);
    if ($new_image) {
        // Delete old image
        $old = mysqli_fetch_assoc(mysqli_query($con, "SELECT image FROM calendar_events WHERE event_id=$id"));
        if (!empty($old['image'])) @unlink($upload_dir . $old['image']);
        $stmt = mysqli_prepare($con, "UPDATE calendar_events SET title=?,description=?,event_date=?,end_date=?,event_time=?,color=?,audience=?,image=? WHERE event_id=?");
        mysqli_stmt_bind_param($stmt, 'ssssssssi', $title, $desc, $date, $end_date, $time, $color, $audience, $new_image, $id);
    } else {
        $stmt = mysqli_prepare($con, "UPDATE calendar_events SET title=?,description=?,event_date=?,end_date=?,event_time=?,color=?,audience=? WHERE event_id=?");
        mysqli_stmt_bind_param($stmt, 'sssssssi', $title, $desc, $date, $end_date, $time, $color, $audience, $id);
    }
    mysqli_stmt_execute($stmt);
    log_activity($con, 'Updated calendar event', 'calendar', $title);
    header("Location: ../pages/admin/admin_calendar.php?success=updated");
    die;
}

if ($action === 'delete') {
    $id = (int)$_POST['event_id'];
    $old = mysqli_fetch_assoc(mysqli_query($con, "SELECT image FROM calendar_events WHERE event_id=$id"));
    if (!empty($old['image'])) @unlink($upload_dir . $old['image']);
    mysqli_query($con, "DELETE FROM calendar_events WHERE event_id=$id");
    log_activity($con, 'Deleted calendar event', 'calendar', 'Event ID ' . $id);
    header("Location: ../pages/admin/admin_calendar.php?success=deleted");
    die;
}

header("Location: ../pages/admin/admin_calendar.php");
die;
?>
