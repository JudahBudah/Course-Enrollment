<?php
session_start();
include("connection.php");
include("admin_functions.php");

$admin_data = check_admin_login($con);

if (($admin_data['role'] ?? 'admin') !== 'superadmin') {
    header("Location: ../pages/admin/admin_home.php?error=unauthorized");
    die;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/admin/admin_accounts.php");
    die;
}

$semester    = trim($_POST['current_semester']    ?? '');
$school_year = trim($_POST['current_school_year'] ?? '');

$valid_semesters = ['1st', '2nd', 'summer'];
if (!in_array($semester, $valid_semesters) || !preg_match('/^\d{4}-\d{4}$/', $school_year)) {
    header("Location: ../pages/admin/admin_accounts.php?error=invalid_settings");
    die;
}

save_setting($con, 'current_semester',    $semester);
save_setting($con, 'current_school_year', $school_year);
save_setting($con, 'enrollment_open',     $_POST['enrollment_open'] === '1' ? '1' : '0');
save_setting($con, 'block_enrollment_restricted', isset($_POST['block_enrollment_restricted']) && $_POST['block_enrollment_restricted'] === '1' ? '1' : '0');

$min_units = max(0, (int)($_POST['min_units'] ?? 0));
$max_units = max(0, (int)($_POST['max_units'] ?? 0));
if ($min_units > 0) save_setting($con, 'min_units', (string)$min_units);
if ($max_units > 0) save_setting($con, 'max_units', (string)$max_units);

// Mark confirmed enrollments from previous semester/year as completed
mysqli_query($con,
    "UPDATE enrollments e
     JOIN classes c ON e.class_id = c.class_id
     SET e.status = 'completed'
     WHERE e.status = 'confirmed'
       AND c.grades_finalized = 1
       AND (c.semester != '$semester' OR c.school_year != '$school_year')"
);

$enrollment_status = $_POST['enrollment_open'] === '1' ? 'Open' : 'Closed';
log_activity($con, 'Updated system settings', 'admin',
    'Semester: ' . $semester . ' | AY: ' . $school_year . ' | Enrollment: ' . $enrollment_status);

header("Location: ../pages/admin/admin_accounts.php?success=settings_saved");
die;
?>
