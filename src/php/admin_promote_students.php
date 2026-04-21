<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

// Only superadmins can promote — check role from DB via the returned admin data
$admin_data = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT role FROM admins WHERE admin_id = {$_SESSION['admin_id']} LIMIT 1"
));
if (!$admin_data || $admin_data['role'] !== 'superadmin') {
    header("Location: ../pages/admin/admin_accounts.php?error=unauthorized");
    die;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/admin/admin_accounts.php");
    die;
}

// Step 1: For every student about to be promoted, mark ungraded active enrollments as INC
// Find all confirmed/ongoing enrollments with no finalized grade entry
$ungraded = mysqli_query($con,
    "SELECT e.enrollment_id, e.student_id, e.class_id, c.subject_id,
            c.semester, c.school_year
     FROM enrollments e
     JOIN classes c ON e.class_id = c.class_id
     LEFT JOIN grades g ON g.student_id = e.student_id
         AND g.subject_id = c.subject_id
         AND g.semester = c.semester
         AND g.school_year = c.school_year
     WHERE e.status IN ('confirmed','ongoing','reserved')
       AND c.grades_finalized = 0
       AND g.grade_id IS NULL"
);

$inc_stmt = mysqli_prepare($con,
    "INSERT INTO grades (student_id, subject_id, grade, status, semester, school_year)
     VALUES (?, ?, 'INC', 'Incomplete', ?, ?)
     ON DUPLICATE KEY UPDATE grade = 'INC', status = 'Incomplete'"
);

$inc_students = [];
while ($row = mysqli_fetch_assoc($ungraded)) {
    mysqli_stmt_bind_param($inc_stmt, 'iiss',
        $row['student_id'], $row['subject_id'],
        $row['semester'], $row['school_year']
    );
    mysqli_stmt_execute($inc_stmt);

    // Mark enrollment as completed
    mysqli_query($con,
        "UPDATE enrollments SET status = 'completed'
         WHERE enrollment_id = {$row['enrollment_id']}"
    );

    $inc_students[$row['student_id']] = true;
}
mysqli_stmt_close($inc_stmt);

// Flag all affected students as Irregular
if (!empty($inc_students)) {
    $ids = implode(',', array_keys($inc_students));
    mysqli_query($con,
        "UPDATE students SET registration_status = 'Irregular' WHERE student_id IN ($ids)"
    );
}

// Step 2: Promote all students with year_level 1–5 up by one year (year 6 stays at 6)
$stmt = mysqli_prepare($con,
    "UPDATE students SET year_level = year_level + 1 WHERE year_level < 6"
);
mysqli_stmt_execute($stmt);
$promoted = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

log_activity($con, 'Promoted all students by one year level', 'student',
    "$promoted students promoted, " . count($inc_students) . " students marked INC"
);

header("Location: ../pages/admin/admin_accounts.php?success=promoted&count=$promoted&inc=" . count($inc_students));
die;
?>
