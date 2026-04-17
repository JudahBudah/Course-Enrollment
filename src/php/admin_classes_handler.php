<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

$action = $_POST['action'] ?? '';

if ($action === 'add' || $action === 'edit') {
    $subject_id = (int) $_POST['subject_id'];
    $faculty_id = !empty($_POST['faculty_id']) ? (int) $_POST['faculty_id'] : null;
    $section = trim($_POST['section']);
    $school_year = trim($_POST['school_year']);
    $semester = $_POST['semester'];
    $schedule_day = trim($_POST['schedule_day']);
    $schedule_time = trim($_POST['schedule_time']);

    // Normalize full day names to standard abbreviations to prevent misparse
    $day_normalize = [
        'monday'    => 'M',
        'tuesday'   => 'T',
        'wednesday' => 'W',
        'thursday'  => 'TH',
        'friday'    => 'F',
        'saturday'  => 'S',
        'sunday'    => 'SU',
    ];
    // Split by space or comma, normalize each token, rejoin
    if (!empty($schedule_day)) {
        $tokens = preg_split('/[\s,]+/', strtolower(trim($schedule_day)));
        $normalized = [];
        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') continue;
            $normalized[] = $day_normalize[$token] ?? strtoupper($token);
        }
        $schedule_day = implode('', $normalized);
    }
    $room = trim($_POST['room']);
    $max_slots = (int) $_POST['max_slots'];
    $status = $_POST['status'];
    
    // Handle department restriction
    $availability_type = $_POST['availability_type'] ?? 'all';
    $specific_department = null;
    if ($availability_type === 'specific' && !empty($_POST['specific_department'])) {
        $specific_department = trim($_POST['specific_department']);
    }

    if (!$section || !$school_year || !$semester || !$max_slots) {
        header("Location: ../pages/admin/admin_classes.php?error=missing_fields");
        die;
    }

    if ($action === 'add') {
        // TC017: check duplicate by subject_code + section + school_year + semester
        $dup_check = mysqli_prepare($con, "
            SELECT c.class_id FROM classes c
            JOIN subjects s ON c.subject_id = s.subject_id
            WHERE s.subject_code = (SELECT subject_code FROM subjects WHERE subject_id = ?)
            AND c.section = ? AND c.school_year = ? AND c.semester = ?"
        );
        mysqli_stmt_bind_param($dup_check, "isss", $subject_id, $section, $school_year, $semester);
        mysqli_stmt_execute($dup_check);
        mysqli_stmt_store_result($dup_check);
        if (mysqli_stmt_num_rows($dup_check) > 0) {
            header("Location: ../pages/admin/admin_classes.php?error=duplicate_class");
            die;
        }

        // TC018: room conflict (same room, day, time)
        if (!empty($room) && !empty($schedule_day) && !empty($schedule_time)) {
            $conflict_check = mysqli_prepare($con, "SELECT class_id FROM classes WHERE room = ? AND schedule_day = ? AND schedule_time = ? AND school_year = ? AND semester = ?");
            mysqli_stmt_bind_param($conflict_check, "sssss", $room, $schedule_day, $schedule_time, $school_year, $semester);
            mysqli_stmt_execute($conflict_check);
            mysqli_stmt_store_result($conflict_check);
            if (mysqli_stmt_num_rows($conflict_check) > 0) {
                header("Location: ../pages/admin/admin_classes.php?error=schedule_conflict");
                die;
            }
        }

        // TC018: faculty conflict (same faculty, day, time)
        if ($faculty_id && !empty($schedule_day) && !empty($schedule_time)) {
            $fac_check = mysqli_prepare($con, "SELECT class_id FROM classes WHERE faculty_id = ? AND schedule_day = ? AND schedule_time = ? AND school_year = ? AND semester = ?");
            mysqli_stmt_bind_param($fac_check, "issss", $faculty_id, $schedule_day, $schedule_time, $school_year, $semester);
            mysqli_stmt_execute($fac_check);
            mysqli_stmt_store_result($fac_check);
            if (mysqli_stmt_num_rows($fac_check) > 0) {
                header("Location: ../pages/admin/admin_classes.php?error=faculty_conflict");
                die;
            }
        }

        $stmt = mysqli_prepare($con, "INSERT INTO classes (subject_id, faculty_id, section, school_year, semester, schedule_day, schedule_time, room, max_slots, enrolled_count, status, specific_department) VALUES (?,?,?,?,?,?,?,?,?,0,?,?)");
        mysqli_stmt_bind_param($stmt, "iisssssisss", $subject_id, $faculty_id, $section, $school_year, $semester, $schedule_day, $schedule_time, $room, $max_slots, $status, $specific_department);

        if (!mysqli_stmt_execute($stmt)) {
            header("Location: ../pages/admin/admin_classes.php?error=insert_failed");
            die;
        }
        header("Location: ../pages/admin/admin_classes.php?success=added");

    } else {
        $class_id = (int) $_POST['class_id'];

        // TC017: check duplicate by subject_code + section (excluding current class)
        $dup_check = mysqli_prepare($con, "
            SELECT c.class_id FROM classes c
            JOIN subjects s ON c.subject_id = s.subject_id
            WHERE s.subject_code = (SELECT subject_code FROM subjects WHERE subject_id = ?)
            AND c.section = ? AND c.school_year = ? AND c.semester = ? AND c.class_id != ?"
        );
        mysqli_stmt_bind_param($dup_check, "isssi", $subject_id, $section, $school_year, $semester, $class_id);
        mysqli_stmt_execute($dup_check);
        mysqli_stmt_store_result($dup_check);
        if (mysqli_stmt_num_rows($dup_check) > 0) {
            header("Location: ../pages/admin/admin_classes.php?error=duplicate_class");
            die;
        }

        // TC018: room conflict (excluding current class)
        if (!empty($room) && !empty($schedule_day) && !empty($schedule_time)) {
            $conflict_check = mysqli_prepare($con, "SELECT class_id FROM classes WHERE room = ? AND schedule_day = ? AND schedule_time = ? AND school_year = ? AND semester = ? AND class_id != ?");
            mysqli_stmt_bind_param($conflict_check, "sssssi", $room, $schedule_day, $schedule_time, $school_year, $semester, $class_id);
            mysqli_stmt_execute($conflict_check);
            mysqli_stmt_store_result($conflict_check);
            if (mysqli_stmt_num_rows($conflict_check) > 0) {
                header("Location: ../pages/admin/admin_classes.php?error=schedule_conflict");
                die;
            }
        }

        // TC018: faculty conflict (excluding current class)
        if ($faculty_id && !empty($schedule_day) && !empty($schedule_time)) {
            $fac_check = mysqli_prepare($con, "SELECT class_id FROM classes WHERE faculty_id = ? AND schedule_day = ? AND schedule_time = ? AND school_year = ? AND semester = ? AND class_id != ?");
            mysqli_stmt_bind_param($fac_check, "issssi", $faculty_id, $schedule_day, $schedule_time, $school_year, $semester, $class_id);
            mysqli_stmt_execute($fac_check);
            mysqli_stmt_store_result($fac_check);
            if (mysqli_stmt_num_rows($fac_check) > 0) {
                header("Location: ../pages/admin/admin_classes.php?error=faculty_conflict");
                die;
            }
        }

        $stmt = mysqli_prepare($con, "UPDATE classes SET faculty_id=?, section=?, school_year=?, semester=?, schedule_day=?, schedule_time=?, room=?, max_slots=?, status=?, specific_department=? WHERE class_id=?");
        mysqli_stmt_bind_param($stmt, "issssssissi", $faculty_id, $section, $school_year, $semester, $schedule_day, $schedule_time, $room, $max_slots, $status, $specific_department, $class_id);

        if (!mysqli_stmt_execute($stmt)) {
            header("Location: ../pages/admin/admin_classes.php?error=update_failed");
            die;
        }
        header("Location: ../pages/admin/admin_classes.php?success=updated");
    }
    die;
}

if ($action === 'delete') {
    $class_id = (int) $_POST['class_id'];

    // Check if class has active enrollments
    $chk = mysqli_prepare($con, "SELECT enrollment_id FROM enrollments WHERE class_id = ? AND status IN ('reserved', 'confirmed', 'ongoing') LIMIT 1");
    mysqli_stmt_bind_param($chk, "i", $class_id);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    if (mysqli_stmt_num_rows($chk) > 0) {
        header("Location: ../pages/admin/admin_classes.php?error=has_enrollments");
        die;
    }

    $stmt = mysqli_prepare($con, "DELETE FROM classes WHERE class_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $class_id);
    mysqli_stmt_execute($stmt);
    header("Location: ../pages/admin/admin_classes.php?success=deleted");
    die;
}

if ($action === 'toggle_status') {
    $class_id = (int) $_POST['class_id'];
    $new_status = $_POST['new_status'];
    $stmt = mysqli_prepare($con, "UPDATE classes SET status = ? WHERE class_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_status, $class_id);
    mysqli_stmt_execute($stmt);
    header("Location: ../pages/admin/admin_classes.php?success=updated");
    die;
}

header("Location: ../pages/admin/admin_classes.php");
die;
?>
