<?php
session_start();
include("connection.php");
include("admin_functions.php");
check_admin_login($con);

// Ensure tables exist
mysqli_query($con, "CREATE TABLE IF NOT EXISTS exam_schedules (
    schedule_id  INT AUTO_INCREMENT PRIMARY KEY,
    exam_date    DATE NOT NULL,
    exam_time    VARCHAR(50) NOT NULL,
    location     VARCHAR(255) NOT NULL,
    notes        TEXT,
    created_by   INT DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Ensure exam columns exist (compatible with MySQL 5.x)
$cols = [];
$col_res = mysqli_query($con, "SHOW COLUMNS FROM applicants");
while ($c = mysqli_fetch_assoc($col_res)) $cols[] = $c['Field'];
if (!in_array('exam_schedule_id', $cols))
    mysqli_query($con, "ALTER TABLE applicants ADD COLUMN exam_schedule_id INT DEFAULT NULL");
if (!in_array('exam_notified', $cols))
    mysqli_query($con, "ALTER TABLE applicants ADD COLUMN exam_notified TINYINT(1) DEFAULT 0");

$action = $_POST['action'] ?? '';

if ($action === 'assign_exam') {
    $ids      = $_POST['applicant_ids'] ?? [];
    $date     = trim($_POST['exam_date'] ?? '');
    $time     = trim($_POST['exam_time'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $notes    = trim($_POST['notes'] ?? '');
    $admin_id = $_SESSION['admin_id'];

    if (!$date || !$time || !$location || empty($ids)) {
        header("Location: ../pages/admin/admin_applicants.php?exam_error=missing");
        die;
    }

    // Create or reuse a matching schedule
    $existing = mysqli_fetch_assoc(mysqli_prepare_and_execute($con,
        "SELECT schedule_id FROM exam_schedules WHERE exam_date=? AND exam_time=? AND location=? LIMIT 1",
        'sss', [$date, $time, $location]
    ));

    if ($existing) {
        $schedule_id = (int)$existing['schedule_id'];
    } else {
        $stmt = mysqli_prepare($con, "INSERT INTO exam_schedules (exam_date, exam_time, location, notes, created_by) VALUES (?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'ssssi', $date, $time, $location, $notes, $admin_id);
        mysqli_stmt_execute($stmt);
        $schedule_id = (int)mysqli_insert_id($con);
    }

    // Assign each applicant
    $upd = mysqli_prepare($con, "UPDATE applicants SET exam_schedule_id=?, exam_notified=0 WHERE applicant_id=?");
    foreach ($ids as $aid) {
        $aid = (int)$aid;
        mysqli_stmt_bind_param($upd, 'ii', $schedule_id, $aid);
        mysqli_stmt_execute($upd);
    }

    $count = count($ids);
    header("Location: ../pages/admin/admin_applicants.php?exam_success=$count");
    die;
}

if ($action === 'clear_exam') {
    $ids = $_POST['applicant_ids'] ?? [];
    $upd = mysqli_prepare($con, "UPDATE applicants SET exam_schedule_id=NULL, exam_notified=0 WHERE applicant_id=?");
    foreach ($ids as $aid) {
        $aid = (int)$aid;
        mysqli_stmt_bind_param($upd, 'i', $aid);
        mysqli_stmt_execute($upd);
    }
    header("Location: ../pages/admin/admin_applicants.php?exam_cleared=1");
    die;
}

header("Location: ../pages/admin/admin_applicants.php");
die;

// Helper: prepare + bind + execute, returns result
function mysqli_prepare_and_execute($con, $sql, $types, $params) {
    $stmt = mysqli_prepare($con, $sql);
    if ($types && $params) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}
?>
