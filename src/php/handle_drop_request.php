<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/admin/admin_students.php");
    exit;
}

$student_id    = (int)$_POST['student_id'];
$enrollment_id = (int)$_POST['enrollment_id'];
$class_id      = (int)($_POST['class_id'] ?? 0);
$action        = $_POST['action'] ?? '';

// Get current enrollment
$stmt = mysqli_prepare($con, "SELECT status, class_id FROM enrollments WHERE enrollment_id = ? AND student_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $enrollment_id, $student_id);
mysqli_stmt_execute($stmt);
$enroll = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$enroll || $enroll['status'] !== 'drop_requested') {
    header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=invalid&t=" . time());
    exit;
}

if ($action === 'accept') {
    // Update enrollment to dropped
    $stmt = mysqli_prepare($con, "UPDATE enrollments SET status = 'dropped' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $enrollment_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Decrement class count
    $actual_class_id = $class_id > 0 ? $class_id : $enroll['class_id'];
    mysqli_query($con, "UPDATE classes SET enrolled_count = GREATEST(0, enrolled_count - 1) WHERE class_id = $actual_class_id");

    // Set student status to Dropped
    mysqli_query($con, "UPDATE students SET status = 'Dropped' WHERE student_id = $student_id");

    header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&success=drop_accepted&t=" . time());

} elseif ($action === 'reject') {
    // Revert enrollment to confirmed
    $stmt = mysqli_prepare($con, "UPDATE enrollments SET status = 'confirmed' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $enrollment_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&success=drop_rejected&t=" . time());

} else {
    header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=invalid&t=" . time());
}
exit;
?>