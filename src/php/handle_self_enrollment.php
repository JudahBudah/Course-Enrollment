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
$class_id      = (int)$_POST['class_id'];
$action        = $_POST['action'] ?? '';

// Verify enrollment is ongoing and belongs to student
$stmt = mysqli_prepare($con, "SELECT status FROM enrollments WHERE enrollment_id = ? AND student_id = ? AND status = 'ongoing'");
mysqli_stmt_bind_param($stmt, "ii", $enrollment_id, $student_id);
mysqli_stmt_execute($stmt);
$enroll = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$enroll) {
    header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=invalid&t=" . time());
    exit;
}

if ($action === 'accept') {
    // Change ongoing -> confirmed (admin-approved)
    $stmt = mysqli_prepare($con, "UPDATE enrollments SET status = 'confirmed' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $enrollment_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_query($con, "UPDATE students SET status = 'Enrolled' WHERE student_id = $student_id");

    header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&success=self_accepted&t=" . time());

} elseif ($action === 'reject') {
    // Change ongoing -> dropped, decrement slot
    $stmt = mysqli_prepare($con, "UPDATE enrollments SET status = 'dropped' WHERE enrollment_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $enrollment_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_query($con, "UPDATE classes SET enrolled_count = GREATEST(0, enrolled_count - 1) WHERE class_id = $class_id");

    // Update student status based on remaining active enrollments
    $check = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM enrollments WHERE student_id = $student_id AND status IN ('reserved','confirmed','ongoing')"));
    $new_status = $check['c'] > 0 ? 'Enrolled' : 'Dropped';
    mysqli_query($con, "UPDATE students SET status = '$new_status' WHERE student_id = $student_id");

    header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&success=self_rejected&t=" . time());

} else {
    header("Location: ../pages/admin/admin_manual_enroll.php?student_id=$student_id&error=invalid&t=" . time());
}
exit;
?>
