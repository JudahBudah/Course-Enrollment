<?php
session_start();
include("connection.php");
include("admin_functions.php");

$admin_data = check_admin_login($con);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $block_name   = trim($_POST['block_name']);
    $course       = trim($_POST['course']);
    $year_level   = trim($_POST['year_level']);
    $semester     = trim($_POST['semester']);
    $school_year  = trim($_POST['school_year']);
    $max_students = (int)$_POST['max_students'];

    if (!$block_name || !$course || !$year_level || !$semester || !$school_year || !$max_students) {
        header("Location: ../pages/admin/admin_blocks.php?error=missing_fields");
        exit;
    }

    // Check for duplicate block
    $chk = mysqli_prepare($con,
        "SELECT block_id FROM blocks WHERE block_name=? AND course=? AND year_level=? AND semester=? AND school_year=? LIMIT 1"
    );
    mysqli_stmt_bind_param($chk, 'sssss', $block_name, $course, $year_level, $semester, $school_year);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    if (mysqli_stmt_num_rows($chk) > 0) {
        header("Location: ../pages/admin/admin_blocks.php?error=duplicate_block");
        exit;
    }
    mysqli_stmt_close($chk);

    $stmt = mysqli_prepare($con,
        "INSERT INTO blocks (block_name, course, year_level, semester, school_year, max_students, current_students, status)
         VALUES (?, ?, ?, ?, ?, ?, 0, 'active')"
    );
    mysqli_stmt_bind_param($stmt, 'ssssssi', $block_name, $course, $year_level, $semester, $school_year, $max_students);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../pages/admin/admin_blocks.php?success=created");
    } else {
        header("Location: ../pages/admin/admin_blocks.php?error=failed");
    }
} else {
    header("Location: ../pages/admin/admin_blocks.php");
}
?>
