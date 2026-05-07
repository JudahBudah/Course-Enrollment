<?php
session_start();
include("connection.php");
include("admin_functions.php");

check_admin_login($con);

$action = $_POST['action'] ?? '';

// Preserve filter state across redirects
$_filter = $_POST['_filter'] ?? 'all';
$_year   = $_POST['_year']   ?? '';
$_course = $_POST['_course'] ?? '';
$_search = $_POST['_search'] ?? '';
$_qs = '?filter=' . urlencode($_filter)
     . '&year='   . urlencode($_year)
     . '&course=' . urlencode($_course)
     . '&search=' . urlencode($_search);
$base = '../pages/admin/admin_subjects.php';

if ($action === 'add' || $action === 'edit') {
    $subject_code   = trim($_POST['subject_code']);
    $subject_name   = trim($_POST['subject_name']);
    $description    = trim($_POST['description']);
    $units          = (int) $_POST['units'];
    $lecture_hours  = (float) $_POST['lecture_hours'];
    $lab_hours      = (float) $_POST['lab_hours'];
    $course_id      = $_POST['course_id'] !== '' ? (int) $_POST['course_id'] : null;
    $department     = trim($_POST['department']);
    $year_level     = $_POST['year_level'] !== '' ? $_POST['year_level'] : null;
    $semester       = $_POST['semester'] !== '' ? $_POST['semester'] : null;
    $prerequisite   = trim($_POST['prerequisite']);
    $status         = $_POST['status'];

    if (!$subject_code || !$subject_name || !$units) {
        header("Location: $base" . $_qs . "&error=missing_fields");
        die;
    }

    if ($action === 'add') {
        // Check duplicate: same code + same course only
        $chk = mysqli_prepare($con, "SELECT subject_id FROM subjects WHERE subject_code = ? AND (course_id = ? OR (course_id IS NULL AND ? IS NULL))");
        mysqli_stmt_bind_param($chk, "sii", $subject_code, $course_id, $course_id);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            header("Location: $base" . $_qs . "&error=duplicate_code");
            die;
        }

        $stmt = mysqli_prepare($con, "INSERT INTO subjects (subject_code, subject_name, description, units, lecture_hours, lab_hours, course_id, department, year_level, semester, prerequisite, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "sssiddisssss", $subject_code, $subject_name, $description, $units, $lecture_hours, $lab_hours, $course_id, $department, $year_level, $semester, $prerequisite, $status);

        if (!mysqli_stmt_execute($stmt)) {
            header("Location: $base" . $_qs . "&error=insert_failed");
            die;
        }
        log_activity($con, 'Added subject', 'subject', $subject_code . ' — ' . $subject_name);
        header("Location: $base" . $_qs . "&success=added");

    } else {
        $subject_id = (int) $_POST['subject_id'];

        // Check duplicate: same code + same course, excluding self
        $chk = mysqli_prepare($con, "SELECT subject_id FROM subjects WHERE subject_code = ? AND (course_id = ? OR (course_id IS NULL AND ? IS NULL)) AND subject_id != ?");
        mysqli_stmt_bind_param($chk, "siii", $subject_code, $course_id, $course_id, $subject_id);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            header("Location: $base" . $_qs . "&error=duplicate_code");
            die;
        }

        $stmt = mysqli_prepare($con, "UPDATE subjects SET subject_code=?, subject_name=?, description=?, units=?, lecture_hours=?, lab_hours=?, course_id=?, department=?, year_level=?, semester=?, prerequisite=?, status=? WHERE subject_id=?");
        mysqli_stmt_bind_param($stmt, "sssiddisssssi", $subject_code, $subject_name, $description, $units, $lecture_hours, $lab_hours, $course_id, $department, $year_level, $semester, $prerequisite, $status, $subject_id);

        if (!mysqli_stmt_execute($stmt)) {
            header("Location: $base" . $_qs . "&error=update_failed");
            die;
        }
        log_activity($con, 'Updated subject', 'subject', $subject_code . ' — ' . $subject_name);
        header("Location: $base" . $_qs . "&success=updated");
    }
    die;
}

if ($action === 'check_dependents') {
    header('Content-Type: application/json');
    $subject_id = (int)$_POST['subject_id'];

    // Get the subject code to search for in prerequisite strings
    $row = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT subject_code FROM subjects WHERE subject_id = $subject_id"
    ));
    if (!$row) { echo json_encode(['dependents' => []]); die; }

    $code = mysqli_real_escape_string($con, $row['subject_code']);

    // Find all subjects whose prerequisite field contains this code
    $res = mysqli_query($con,
        "SELECT subject_id, subject_code, subject_name, year_level, semester
         FROM subjects
         WHERE subject_id != $subject_id
           AND prerequisite REGEXP '(^|,\\s*)" . $code . "(\\s*,|$)'"
    );
    $dependents = [];
    while ($r = mysqli_fetch_assoc($res)) $dependents[] = $r;
    echo json_encode(['dependents' => $dependents]);
    die;
}

if ($action === 'delete') {
    $subject_id = (int) $_POST['subject_id'];

    // Check if subject is used in any class
    $chk = mysqli_prepare($con, "SELECT class_id FROM classes WHERE subject_id = ? LIMIT 1");
    mysqli_stmt_bind_param($chk, "i", $subject_id);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    if (mysqli_stmt_num_rows($chk) > 0) {
        header("Location: $base" . $_qs . "&error=in_use");
        die;
    }

    $stmt = mysqli_prepare($con, "DELETE FROM subjects WHERE subject_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $subject_id);
    mysqli_stmt_execute($stmt);
    log_activity($con, 'Deleted subject', 'subject', 'Subject ID ' . $subject_id);
    header("Location: $base" . $_qs . "&success=deleted");
    die;
}

if ($action === 'toggle_status') {
    $subject_id = (int) $_POST['subject_id'];
    $new_status = $_POST['new_status'];
    $stmt = mysqli_prepare($con, "UPDATE subjects SET status = ? WHERE subject_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_status, $subject_id);
    mysqli_stmt_execute($stmt);
    log_activity($con, 'Toggled subject status to ' . $new_status, 'subject', 'Subject ID ' . $subject_id);
    header("Location: $base" . $_qs . "&success=updated");
    die;
}

header("Location: $base" . $_qs);
die;
