<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../../php/connection.php';
require_once '../../php/functions.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: ../../php/student_logout.php');
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch student info
$student_query = "SELECT s.*, b.block_name, b.school_year, b.semester 
                 FROM students s 
                 LEFT JOIN blocks b ON s.block_id = b.block_id 
                 WHERE s.student_id = ?";
$stmt = mysqli_prepare($con, $student_query);
mysqli_stmt_bind_param($stmt, 'i', $student_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Fetch registered subjects (reserved, confirmed)
$registered_query = "SELECT e.enrollment_id, e.status, s.subject_code, s.subject_name, s.lecture_hours, s.lab_hours, s.units,
                     c.class_id, c.schedule_day, c.schedule_time, c.room, c.section,
                     CONCAT(f.first_name, ' ', f.last_name) as faculty_name
                     FROM enrollments e
                     JOIN classes c ON e.class_id = c.class_id
                     JOIN subjects s ON c.subject_id = s.subject_id
                     LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
                     WHERE e.student_id = ? AND e.status IN ('reserved', 'confirmed')
                     ORDER BY s.subject_code";
$stmt = mysqli_prepare($con, $registered_query);
mysqli_stmt_bind_param($stmt, 'i', $student_id);
mysqli_stmt_execute($stmt);
$registered_subjects = mysqli_stmt_get_result($stmt);

// Fetch self-enrolled subjects pending admin review
$pending_review_query = "SELECT e.enrollment_id, s.subject_code, s.subject_name, s.lecture_hours, s.lab_hours, s.units,
                     c.class_id, c.schedule_day, c.schedule_time, c.room, c.section,
                     CONCAT(f.first_name, ' ', f.last_name) as faculty_name
                     FROM enrollments e
                     JOIN classes c ON e.class_id = c.class_id
                     JOIN subjects s ON c.subject_id = s.subject_id
                     LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
                     WHERE e.student_id = ? AND e.status = 'ongoing'
                     ORDER BY s.subject_code";
$stmt = mysqli_prepare($con, $pending_review_query);
mysqli_stmt_bind_param($stmt, 'i', $student_id);
mysqli_stmt_execute($stmt);
$pending_review_subjects = mysqli_stmt_get_result($stmt);

// Fetch drop requests
$drop_requests_query = "SELECT e.enrollment_id, s.subject_code, s.subject_name, s.lecture_hours, s.lab_hours, s.units,
                        c.schedule_day, c.schedule_time, c.room, c.section,
                        CONCAT(f.first_name, ' ', f.last_name) as faculty_name
                        FROM enrollments e
                        JOIN classes c ON e.class_id = c.class_id
                        JOIN subjects s ON c.subject_id = s.subject_id
                        LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
                        WHERE e.student_id = ? AND e.status = 'drop_requested'
                        ORDER BY s.subject_code";
$stmt = mysqli_prepare($con, $drop_requests_query);
mysqli_stmt_bind_param($stmt, 'i', $student_id);
mysqli_stmt_execute($stmt);
$drop_requests = mysqli_stmt_get_result($stmt);

// Fetch subjects for retake (failed subjects)
$retake_query = "SELECT DISTINCT s.subject_code, s.subject_name, s.lecture_hours, s.lab_hours, s.units,
                 c.schedule_day, c.schedule_time, c.room, c.section,
                 CONCAT(f.first_name, ' ', f.last_name) as faculty_name
                 FROM grades g
                 JOIN subjects s ON g.subject_id = s.subject_id
                 JOIN classes c ON s.subject_id = c.subject_id
                 LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
                 WHERE g.student_id = ? AND g.status = 'Failed'";
$stmt = mysqli_prepare($con, $retake_query);
mysqli_stmt_bind_param($stmt, 'i', $student_id);
mysqli_stmt_execute($stmt);
$retake_subjects = mysqli_stmt_get_result($stmt);

// Fetch all available subjects
// If student has a block, show only their block subjects + unassigned classes
// If student has no block (irregular), show only unassigned classes
// Exclude classes the student is already enrolled in
// Respect department restrictions
$student_block_id = $student['block_id'] ?? null;
$student_course = $student['course'] ?? '';

// Fetch curriculum URL for the student's course
$curriculum_url = '';
if ($student_course) {
    $course_info = get_course_info($con, $student_course);
    $curriculum_url = $course_info['curriculum_url'] ?? '';
}

if ($student_block_id) {
    // Regular student with block: show block subjects + unassigned classes (not already enrolled)
    $all_subjects_query = "SELECT c.class_id, s.subject_code, s.subject_name, s.lecture_hours, s.lab_hours, s.units,
                           c.schedule_day, c.schedule_time, c.room, c.section,
                           CONCAT(f.first_name, ' ', f.last_name) as faculty_name,
                           CASE WHEN bs.block_id IS NOT NULL THEN 1 ELSE 0 END as is_block_subject
                           FROM classes c
                           JOIN subjects s ON c.subject_id = s.subject_id
                           LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
                           LEFT JOIN block_subjects bs ON c.class_id = bs.class_id AND bs.block_id = ?
                           WHERE c.status = 'open'
                           AND (bs.block_id = ? OR c.class_id NOT IN (SELECT class_id FROM block_subjects))
                           AND (c.specific_department IS NULL OR c.specific_department = ? OR c.specific_department = '')
                           AND c.class_id NOT IN (
                               SELECT class_id FROM enrollments 
                               WHERE student_id = ? 
                               AND status IN ('reserved', 'confirmed', 'ongoing')
                           )
                           ORDER BY is_block_subject DESC, s.subject_code";
    $stmt = mysqli_prepare($con, $all_subjects_query);
    mysqli_stmt_bind_param($stmt, 'iisi', $student_block_id, $student_block_id, $student_course, $student_id);
    mysqli_stmt_execute($stmt);
    $all_subjects = mysqli_stmt_get_result($stmt);
} else {
    // Irregular student without block: show only unassigned classes (not already enrolled)
    $all_subjects_query = "SELECT c.class_id, s.subject_code, s.subject_name, s.lecture_hours, s.lab_hours, s.units,
                           c.schedule_day, c.schedule_time, c.room, c.section,
                           CONCAT(f.first_name, ' ', f.last_name) as faculty_name
                           FROM classes c
                           JOIN subjects s ON c.subject_id = s.subject_id
                           LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
                           WHERE c.status = 'open'
                           AND c.class_id NOT IN (SELECT class_id FROM block_subjects)
                           AND (c.specific_department IS NULL OR c.specific_department = ? OR c.specific_department = '')
                           AND c.class_id NOT IN (
                               SELECT class_id FROM enrollments 
                               WHERE student_id = ? 
                               AND status IN ('reserved', 'confirmed', 'ongoing')
                           )
                           ORDER BY s.subject_code";
    $stmt = mysqli_prepare($con, $all_subjects_query);
    mysqli_stmt_bind_param($stmt, 'si', $student_course, $student_id);
    mysqli_stmt_execute($stmt);
    $all_subjects = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/student/student_enrollment.css">
    <link rel="stylesheet" href="../../css/student/student_main.css">
</head>
<body>
    <header>
        <div class="nav-section">
            <!-- Mobile Nav Button -->
            <button class="nav-button" id="navButton">
                <i class="fa-solid fa-bars trans-bars" id="trans-bars"></i>
            </button>

            <div class="logo-container">
                <img src="../../assets/plm-logo.png" alt="PLM Logo" loading="lazy">
                <div class="title-container">
                    <div class="logo-title">PAMANTASAN NG LUNGSOD NG MAYNILA</div>
                    <div class="logo-sub">University of the City of Manila</div>
                </div>
            </div>

            <div class="acc-display-container">
                <div class="acc-name">
                    Judah Isaiah dela Cruz
                </div>
                <div class="acc-img">
                    <img  src="../../assets/test/student-profile.webp">
                </div>
            </div>
        </div>
        <nav class="main-nav" id="navMenu">
            <div class="nav-wrapper">
            <ul class="main-ul">
                <li>
                    <a href="student_home.php" >
                        <i class="fa-solid fa-house"></i>
                        <div class="li-name">Dashboard</div>
                    </a>
                </li>
                <li>
                    <a href="student_subjects.php">
                        <i class="fa-solid fa-calendar"></i>
                        <div class="li-name">Schedule</div>
                    </a>
                </li>
                <li>
                    <a href="student_enrollment.php" class="active">
                        <i class="fa-solid fa-id-card"></i>
                        <div class="li-name">Enrollment</div>
                    </a>
                </li>
                <li>
                    <a href="student_grades.php">
                        <i class="fa-solid fa-book"></i>
                        <div class="li-name">Grades</div>
                    </a>
                </li>
                <li class="course-dropdown">
                    <a href="#" id="acad-dropdown">
                        <i class="fa-solid fa-school"></i>
                        <div class="li-name chev-space">
                            Academics
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                    </a>
                    <div class="acad-dropdown-menu" id="acad-dropdown-menu">
                        <ul>
                            <li><a href="student_info-program.php">Program</a></li>
                            <li><a href="student_info-college.php">College</a></li>
                            <?php if ($curriculum_url): ?>
                            <li><a href="<?php echo htmlspecialchars($curriculum_url); ?>" target="_blank">Curriculum</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
                <li>
                    <a href="student_account.php">
                        <i class="fa-solid fa-user"></i>
                        <div class="li-name">Profile</div>
                    </a>
                </li>
                <li>
                    <a href="../../php/student_logout.php" class="logout-bg">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <div class="li-name">Logout</div>
                    </a>
                </li>
            </ul> 
            </div>

            <div class="drk-mode-container">
                <div class="drk-label">
                    <i class="fa-solid fa-moon" id="modeIcon"></i>
                    <span class="li-name" id="modeLabel">Dark Mode</span>
                </div>
                <div class="toggle-track li-name" id="toggleTrack">
                    <div class="toggle-thumb"></div>
                </div>
            </div>
        </nav>
    </header>
    <div class="main-flex">
    <div class="spacer"></div>

    <main>
        <?php if (isset($_GET['success'])): ?>
            <?php if ($_GET['success'] === 'confirmed'): ?>
                <div style="background:#16a34a1a;color:#16a34a;padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem;border-left:4px solid #16a34a;">
                    <i class="fa-solid fa-check-circle"></i> Enrollment confirmed successfully.
                </div>
            <?php elseif ($_GET['success'] === 'drop_requested'): ?>
                <div style="background:#16a34a1a;color:#16a34a;padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem;border-left:4px solid #16a34a;">
                    <i class="fa-solid fa-check-circle"></i> Drop request submitted. Awaiting admin approval.
                </div>
            <?php elseif ($_GET['success'] === 'drop_cancelled'): ?>
                <div style="background:#16a34a1a;color:#16a34a;padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem;border-left:4px solid #16a34a;">
                    <i class="fa-solid fa-check-circle"></i> Drop request cancelled. You remain enrolled in the subject.
                </div>
            <?php elseif ($_GET['success'] === 'dropped'): ?>
                <div style="background:#16a34a1a;color:#16a34a;padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem;border-left:4px solid #16a34a;">
                    <i class="fa-solid fa-check-circle"></i> Reservation cancelled successfully.
                </div>
            <?php elseif ($_GET['success'] === 'submitted'): ?>
                <div style="background:#2563eb1a;color:#2563eb;padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem;border-left:4px solid #2563eb;">
                    <i class="fa-solid fa-paper-plane"></i> Enrollment submitted for admin approval. You will be notified once reviewed.
                </div>
            <?php elseif ($_GET['success'] === 'removed'): ?>
                <div style="background:#16a34a1a;color:#16a34a;padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem;border-left:4px solid #16a34a;">
                    <i class="fa-solid fa-check-circle"></i> Subject removed from your enrollment.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <?php if ($_GET['error'] === 'invalid'): ?>
                <div style="background:#dc26261a;color:#dc2626;padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem;border-left:4px solid #dc2626;">
                    <i class="fa-solid fa-exclamation-triangle"></i> Invalid request.
                </div>
            <?php elseif ($_GET['error'] === 'prereq'): ?>
                <div style="background:#dc26261a;color:#dc2626;padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem;border-left:4px solid #dc2626;">
                    <i class="fa-solid fa-exclamation-triangle"></i> Missing prerequisites: <?php echo htmlspecialchars(urldecode($_GET['missing'] ?? '')); ?>
                </div>
            <?php elseif ($_GET['error'] === 'full'): ?>
                <div style="background:#dc26261a;color:#dc2626;padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem;border-left:4px solid #dc2626;">
                    <i class="fa-solid fa-exclamation-triangle"></i> Class is full.
                </div>
            <?php elseif ($_GET['error'] === 'overload'): ?>
                <div style="background:#dc26261a;color:#dc2626;padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem;border-left:4px solid #dc2626;">
                    <i class="fa-solid fa-exclamation-triangle"></i> Unit overload: you currently have <?php echo (int)($_GET['current'] ?? 0); ?> units. Adding <?php echo (int)($_GET['adding'] ?? 0); ?> units would exceed the 24-unit limit.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- STUDENT INFO CARD -->
        <div class="table-wrapper">
            <div class="card">
                <div class="card-header">
                    <h2>Student Information</h2>
                </div>
                <div class="student-body">
                    <div class="avatar-wrap">
                        <img src="<?php echo $student['profile_photo'] ? '../../' . $student['profile_photo'] : '../../assets/test/student-profile.webp'; ?>">
                    </div>
                    <div class="student-details">
                        <div class="detail-item">
                            <label>Full Name</label>
                            <span><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Student Number</label>
                            <span><?php echo htmlspecialchars($student['student_number']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Program</label>
                            <span><?php echo htmlspecialchars($student['course']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>School Year</label>
                            <span><?php echo htmlspecialchars($student['school_year'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Status</label>
                            <span><?php echo htmlspecialchars($student['registration_status']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Semester</label>
                            <span><?php echo htmlspecialchars($student['semester'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- REGISTERED SUBJECTS -->
        <div class="card">
            <div class="table-section-head">
                Registered Subjects For the Semester
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th class="center">Hrs</th>
                            <th class="center">Units</th>
                            <th>Schedule</th>
                            <th>Professor</th>
                            <th>Status</th>
                            <th class="center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $has_subjects = false;
                        while ($row = mysqli_fetch_assoc($registered_subjects)): 
                            $has_subjects = true;
                            $status_colors = [
                                'reserved' => '#f59e0b',
                                'confirmed' => '#16a34a',
                            ];
                            $status_labels = [
                                'reserved' => 'Pending',
                                'confirmed' => 'Confirmed',
                            ];
                            $color = $status_colors[$row['status']] ?? '#888';
                            $label = $status_labels[$row['status']] ?? ucfirst($row['status']);
                            $can_drop = $row['status'] === 'confirmed';
                            $can_cancel = $row['status'] === 'reserved';
                        ?>
                        <tr>
                            <td><span class="subj-code"><?php echo htmlspecialchars($row['subject_code']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td class="center"><?php echo $row['lecture_hours'] + $row['lab_hours']; ?></td>
                            <td class="center"><?php echo $row['units']; ?></td>
                            <td><div class="sched-cell"><span class="sched-tag"><?php echo htmlspecialchars($row['section'] . ' · ' . $row['schedule_day'] . ' ' . $row['schedule_time'] . ' · ' . $row['room']); ?></span></div></td>
                            <td class="faculty-name"><?php echo htmlspecialchars($row['faculty_name']); ?></td>
                            <td>
                                <span class="status-badge" style="background:<?php echo $color; ?>1a;color:<?php echo $color; ?>;padding:0.25rem 0.75rem;border-radius:1rem;font-size:0.85rem;font-weight:500;">
                                    <?php echo $label; ?>
                                </span>
                            </td>
                            <td class="center">
                                <?php if ($can_drop): ?>
                                    <form method="POST" action="../../php/student_enrollment_action.php" style="display:inline;">
                                        <input type="hidden" name="action" value="drop">
                                        <input type="hidden" name="enrollment_id" value="<?php echo $row['enrollment_id']; ?>">
                                        <button type="submit" class="action-btn drop-btn" title="Request Drop" 
                                                onclick="return confirm('Request to drop this subject? Admin approval required.')">
                                            <i class="fa-solid fa-right-from-bracket"></i>
                                        </button>
                                    </form>
                                <?php elseif ($can_cancel): ?>
                                    <form method="POST" action="../../php/student_enrollment_action.php" style="display:inline;">
                                        <input type="hidden" name="action" value="confirm">
                                        <input type="hidden" name="enrollment_id" value="<?php echo $row['enrollment_id']; ?>">
                                        <button type="submit" class="action-btn" title="Confirm Enrollment"
                                                style="background:#16a34a;color:white;border:none;padding:0.4rem 0.8rem;border-radius:6px;cursor:pointer;"
                                                onclick="return confirm('Confirm enrollment in this subject?')">
                                            <i class="fa-solid fa-check"></i> Confirm
                                        </button>
                                    </form>
                                    <form method="POST" action="../../php/student_enrollment_action.php" style="display:inline;margin-left:4px;">
                                        <input type="hidden" name="action" value="cancel_reserved">
                                        <input type="hidden" name="enrollment_id" value="<?php echo $row['enrollment_id']; ?>">
                                        <button type="submit" class="action-btn cancel-btn" title="Cancel Reservation" 
                                                onclick="return confirm('Cancel this reservation?')">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (!$has_subjects): ?>
                        <tr>
                            <td colspan="8" class="center" style="padding:2rem;color:var(--text-label);">
                                No registered subjects yet.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PENDING ADMIN REVIEW -->
        <?php
        // Re-fetch count for submit button visibility
        $pending_count_stmt = mysqli_prepare($con, "SELECT COUNT(*) as c FROM enrollments WHERE student_id = ? AND status = 'ongoing'");
        mysqli_stmt_bind_param($pending_count_stmt, 'i', $student_id);
        mysqli_stmt_execute($pending_count_stmt);
        $pending_count = mysqli_fetch_assoc(mysqli_stmt_get_result($pending_count_stmt))['c'];
        ?>
        <?php if (mysqli_num_rows($pending_review_subjects) > 0): ?>
        <div class="card" style="border-left:4px solid #2563eb;">
            <div class="table-section-head" style="display:flex;justify-content:space-between;align-items:center;color:#2563eb;">
                <span><i class="fa-solid fa-hourglass-half"></i> Self-Enrolled Subjects — Pending Submission</span>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th class="center">Hrs</th>
                            <th class="center">Units</th>
                            <th>Schedule</th>
                            <th>Professor</th>
                            <th>Status</th>
                            <th class="center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($pending_review_subjects)): ?>
                        <tr>
                            <td><span class="subj-code"><?php echo htmlspecialchars($row['subject_code']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td class="center"><?php echo $row['lecture_hours'] + $row['lab_hours']; ?></td>
                            <td class="center"><?php echo $row['units']; ?></td>
                            <td><div class="sched-cell"><span class="sched-tag"><?php echo htmlspecialchars($row['section'] . ' · ' . $row['schedule_day'] . ' ' . $row['schedule_time'] . ' · ' . $row['room']); ?></span></div></td>
                            <td class="faculty-name"><?php echo htmlspecialchars($row['faculty_name']); ?></td>
                            <td>
                                <span class="status-badge" style="background:#f59e0b1a;color:#f59e0b;padding:0.25rem 0.75rem;border-radius:1rem;font-size:0.85rem;font-weight:500;">
                                    Not Yet Submitted
                                </span>
                            </td>
                            <td class="center">
                                <form method="POST" action="../../php/student_enrollment_action.php" style="display:inline;">
                                    <input type="hidden" name="action" value="cancel_self_enroll">
                                    <input type="hidden" name="enrollment_id" value="<?php echo $row['enrollment_id']; ?>">
                                    <button type="submit" class="action-btn cancel-btn" title="Remove"
                                            onclick="return confirm('Remove this subject from your enrollment?')">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div style="padding:1rem 1.5rem;border-top:1px solid var(--off, #e5e7eb);display:flex;justify-content:flex-end;gap:0.75rem;align-items:center;">
                <span style="font-size:0.85rem;color:var(--text-label);">
                    <i class="fa-solid fa-circle-info"></i>
                    Review your selected subjects, then click Submit to send for admin approval.
                </span>
                <form method="POST" action="../../php/student_enrollment_action.php">
                    <input type="hidden" name="action" value="submit_enrollment">
                    <button type="submit" class="enroll-btn"
                            style="padding:0.6rem 1.4rem;background:#2563eb;color:white;border:none;border-radius:6px;cursor:pointer;font-size:0.9rem;font-weight:600;"
                            onclick="return confirm('Submit all selected subjects for admin approval?')">
                        <i class="fa-solid fa-paper-plane"></i> Submit Enrollment
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- DROP REQUESTS -->
        <?php if (mysqli_num_rows($drop_requests) > 0): ?>
        <div class="card" style="border-left:4px solid #dc2626;">
            <div class="table-section-head" style="background:#dc26261a;color:#dc2626;">
                <i class="fa-solid fa-clock"></i> Pending Drop Requests (Awaiting Admin Approval)
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th class="center">Hrs</th>
                            <th class="center">Units</th>
                            <th>Schedule</th>
                            <th>Professor</th>
                            <th class="center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($drop_requests)): ?>
                        <tr>
                            <td><span class="subj-code"><?php echo htmlspecialchars($row['subject_code']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td class="center"><?php echo $row['lecture_hours'] + $row['lab_hours']; ?></td>
                            <td class="center"><?php echo $row['units']; ?></td>
                            <td><div class="sched-cell"><span class="sched-tag"><?php echo htmlspecialchars($row['section'] . ' · ' . $row['schedule_day'] . ' ' . $row['schedule_time'] . ' · ' . $row['room']); ?></span></div></td>
                            <td class="faculty-name"><?php echo htmlspecialchars($row['faculty_name']); ?></td>
                            <td class="center">
                                <form method="POST" action="../../php/student_enrollment_action.php" style="display:inline;">
                                    <input type="hidden" name="action" value="cancel_drop_request">
                                    <input type="hidden" name="enrollment_id" value="<?php echo $row['enrollment_id']; ?>">
                                    <button type="submit" class="action-btn cancel-btn" title="Cancel Drop Request" 
                                            onclick="return confirm('Cancel this drop request?')">
                                        <i class="fa-solid fa-rotate-left"></i> Cancel
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>


        <!-- SUBJECTS FOR RETAKE -->
        <div class="card">
            <div class="table-section-head">
                <div class="section-title">
                    Subjects Needed for Retake
                </div>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="select-all"/></th>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th class="center">Hrs</th>
                            <th class="center">Units</th>
                            <th>Schedule</th>
                            <th>Professor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (mysqli_num_rows($retake_subjects) > 0):
                            while ($row = mysqli_fetch_assoc($retake_subjects)): 
                        ?>
                        <tr>
                            <td class="cb-cell"><input type="checkbox"/></td>
                            <td><span class="subj-code"><?php echo htmlspecialchars($row['subject_code']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td class="center"><?php echo $row['lecture_hours'] + $row['lab_hours']; ?></td>
                            <td class="center"><?php echo $row['units']; ?></td>
                            <td><div class="sched-cell"><span class="sched-tag"><?php echo htmlspecialchars($row['section'] . ' · ' . $row['schedule_day'] . ' ' . $row['schedule_time'] . ' · ' . $row['room']); ?></span></div></td>
                            <td class="faculty-name"><?php echo htmlspecialchars($row['faculty_name']); ?></td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="7" class="center">No subjects for retake</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        


        <!-- SEARCH + SUBJECT TABLE -->
        
        <div class="card">
            <div class="search-bar-wrap">
                <label>Search available subjects:</label>
                <div class="search-bar">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Subject code or name…" id="searchInput"/>
                </div>
            </div>
            <div class="table-wrapper">
                <table id="searchTable">
                    <thead>
                        <tr>
                            <th class="center">#</th>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th class="center">Hrs</th>
                            <th class="center">Units</th>
                            <th>Schedule</th>
                            <th>Professor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 0;
                        while ($row = mysqli_fetch_assoc($all_subjects)): 
                            $count++;
                        ?>
                        <tr>
                            <td class="cb-cell"><input type="checkbox" class="subject-checkbox" data-class-id="<?php echo $row['class_id']; ?>"/></td>
                            <td>
                                <span class="subj-code"><?php echo htmlspecialchars($row['subject_code']); ?></span>
                                <?php if (isset($row['is_block_subject']) && $row['is_block_subject']): ?>
                                    <span class="type-tag" style="background:#22c55e;color:white;margin-left:0.3rem;">Block Subject</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td class="center"><?php echo $row['lecture_hours'] + $row['lab_hours']; ?></td>
                            <td class="center"><?php echo $row['units']; ?></td>
                            <td><div class="sched-cell"><span class="sched-tag"><?php echo htmlspecialchars($row['section'] . ' · ' . $row['schedule_day'] . ' ' . $row['schedule_time'] . ' · ' . $row['room']); ?></span></div></td>
                            <td class="faculty-name"><?php echo htmlspecialchars($row['faculty_name']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($count === 0): ?>
                        <tr>
                            <td colspan="7" class="center" style="padding:2rem;color:var(--text-label);">
                                <i class="fa-solid fa-info-circle" style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>
                                No available subjects to enroll. You are enrolled in all available subjects.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($count > 0): ?>
            <div style="padding: 15px; text-align: right;">
                <button id="enrollBtn" class="enroll-btn" style="padding: 10px 20px; background: #8C1C24; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">Enroll Selected</button>
            </div>
            <?php endif; ?>
        </div>
    </main>

    </div>

    <script src="../../js/student/student_enrollment.js"></script>
    <script src="../../js/student/student_main.js"></script>
</body>
</html>