<?php
session_start();
include("../../php/connection.php");
include("../../php/functions.php");
include("../../php/grade_helpers.php");
require_once("../../php/admin_functions.php");

$user_data = check_login($con);
$profile_src = !empty($user_data['profile_photo'])
    ? '../../' . $user_data['profile_photo']
    : '../../uploads/default.jpg';

$program            = htmlspecialchars($user_data['course'] ?? 'N/A');
$year_level         = $user_data['year_level'] ?? null;
$registration_status = htmlspecialchars($user_data['registration_status'] ?? 'Regular');
$account_status     = !empty($registration_status) ? $registration_status : 'Regular';

$full_name = htmlspecialchars(
    trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? ''))
);

$student_number = htmlspecialchars($user_data['student_number'] ?? 'N/A');

/* ── Helpers ── */
function formatYear(int|null $year): string {
    return match($year) {
        1 => "1st Year", 2 => "2nd Year",
        3 => "3rd Year", 4 => "4th Year",
        5 => "5th Year", 6 => "6th Year",
        default => "N/A",
    };
}
$year_display = formatYear($year_level);

/* ── Transmutation helpers ── */
function transmute(float $g): int {
    if ($g>=97) return 99; if ($g>=94) return 96; if ($g>=91) return 93;
    if ($g>=88) return 90; if ($g>=85) return 87; if ($g>=82) return 84;
    if ($g>=79) return 81; if ($g>=76) return 78; if ($g>=73) return 75;
    if ($g>=70) return 72; if ($g>=67) return 69; if ($g>=64) return 66;
    if ($g>=61) return 63; if ($g>=55) return 60; return 55;
}
function pointGrade(int $t): string {
    if ($t>=97) return '1.00'; if ($t>=94) return '1.25'; if ($t>=91) return '1.50';
    if ($t>=88) return '1.75'; if ($t>=85) return '2.00'; if ($t>=82) return '2.25';
    if ($t>=79) return '2.50'; if ($t>=76) return '2.75'; if ($t>=73) return '3.00';
    if ($t>=70) return '4.00'; return '5.00';
}

/* ── Fetch grades from grade_entries (saved by faculty) ── */
$grades_by_year = [];
for ($y = 1; $y <= 6; $y++) {
    $grades_by_year[$y] = ['1st' => [], '2nd' => []];
}

// Determine the student's earliest school year to compute relative year bucket
$first_year_row = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT MIN(c.school_year) as first_sy FROM enrollments e
     JOIN classes c ON e.class_id = c.class_id
     WHERE e.student_id = {$user_data['student_id']}
       AND e.status IN ('reserved','ongoing','confirmed','completed','drop_requested')"
));
$first_sy = $first_year_row['first_sy'] ?? null; // e.g. '2026-2027'

function sy_to_year_bucket(?string $first_sy, string $class_sy): int {
    if (!$first_sy) return 1;
    // Extract start year from '2026-2027' format
    $first_start = (int)explode('-', $first_sy)[0];
    $class_start = (int)explode('-', $class_sy)[0];
    $diff = $class_start - $first_start + 1;
    return max(1, min(6, $diff));
}

// 1) Live grades from active/completed enrollments (class still exists)
$query = "SELECT ge.class_standing, ge.quiz, ge.midterms, ge.finals, ge.computed_grade,
                 e.enrollment_id, e.status AS enroll_status,
                 c.semester, c.section, c.school_year, c.grades_finalized,
                 s.subject_code, s.subject_name, s.units, s.year_level,
                 CONCAT(f.first_name, ' ', f.last_name) AS faculty_name
          FROM enrollments e
          JOIN classes c    ON e.class_id   = c.class_id
          JOIN subjects s   ON c.subject_id = s.subject_id
          LEFT JOIN faculty f  ON c.faculty_id  = f.faculty_id
          LEFT JOIN grade_entries ge ON ge.enrollment_id = e.enrollment_id
          WHERE e.student_id = ?
            AND e.status IN ('reserved','ongoing','confirmed','completed','drop_requested')
          ORDER BY c.school_year, c.semester, s.subject_code";

$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "i", $user_data['student_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Track which class instances are already covered (by class_id for live, by subject+sem+year+section for history)
$seen_class_ids = [];

while ($row = mysqli_fetch_assoc($result)) {
    if (!$row['grades_finalized']) {
        $row['class_standing'] = null;
        $row['quiz']           = null;
        $row['midterms']       = null;
        $row['finals']         = null;
        $row['computed_grade'] = null;
    }

    $cg = $row['computed_grade'] !== null ? (float)$row['computed_grade'] : null;
    $row['grade'] = $cg !== null ? pointGrade(transmute($cg)) : null;

    $sem_key  = $row['semester'] === '2nd' ? '2nd' : '1st';
    $year_num = sy_to_year_bucket($first_sy, $row['school_year']);

    // Track by the unique class instance key so history fallback skips exact duplicates only
    $seen_class_ids[$row['subject_code'] . '|' . $row['semester'] . '|' . $row['school_year'] . '|' . $row['section']] = true;

    $grades_by_year[$year_num][$sem_key][] = $row;
}
mysqli_stmt_close($stmt);

// 2) Archived grades from grade_history (class was deleted but grades were finalized)
$hist_query = "SELECT gh.class_id, gh.class_standing, gh.quiz, gh.midterms, gh.finals,
                      gh.computed_grade, gh.point_grade,
                      gh.semester, gh.section, gh.school_year,
                      gh.subject_code, gh.subject_name, gh.remarks,
                      CONCAT(f.first_name, ' ', f.last_name) AS faculty_name,
                      s.units, s.year_level
               FROM grade_history gh
               INNER JOIN (
                   SELECT MAX(history_id) as max_id
                   FROM grade_history
                   WHERE student_id = ?
                     AND class_id NOT IN (SELECT class_id FROM classes)
                   GROUP BY class_id
               ) latest ON gh.history_id = latest.max_id
               LEFT JOIN subjects s ON s.subject_code COLLATE utf8mb4_0900_ai_ci = gh.subject_code
                   AND (s.course_id IS NULL OR s.course_id = (
                       SELECT course_id FROM courses
                       WHERE course_name = ? OR course_code = ? LIMIT 1
                   ))
               LEFT JOIN faculty f ON f.faculty_id = gh.faculty_id
               ORDER BY gh.school_year, gh.semester, gh.subject_code";

$hist_stmt = mysqli_prepare($con, $hist_query);
$course = $user_data['course'] ?? '';
mysqli_stmt_bind_param($hist_stmt, "iss", $user_data['student_id'], $course, $course);
mysqli_stmt_execute($hist_stmt);
$hist_result = mysqli_stmt_get_result($hist_stmt);

while ($row = mysqli_fetch_assoc($hist_result)) {
    // Skip if this class_id was already shown from live data or a previous history row
    $instance_key = $row['subject_code'] . '|' . $row['semester'] . '|' . $row['school_year'] . '|' . $row['section'];
    if (isset($seen_class_ids[$instance_key])) continue;
    $seen_class_ids[$instance_key] = true;

    $cg = $row['computed_grade'] !== null ? (float)$row['computed_grade'] : null;
    $row['grade']            = $row['point_grade'] ?? ($cg !== null ? pointGrade(transmute($cg)) : null);
    $row['grades_finalized'] = 1;
    $row['enroll_status']    = 'completed';
    $row['enrollment_id']    = null;

    $sem_key  = $row['semester'] === '2nd' ? '2nd' : '1st';
    $year_num = sy_to_year_bucket($first_sy, $row['school_year']);

    $grades_by_year[$year_num][$sem_key][] = $row;
}
mysqli_stmt_close($hist_stmt);

/* ── GWA calculator ── */
function calculateGWA(array $grades): ?float {
    $total_points = 0;
    $total_units  = 0;
    foreach ($grades as $g) {
        $val = floatval($g['grade']);
        if ($val > 0 && $val <= 5.0) {
            $total_points += $val * $g['units'];
            $total_units  += $g['units'];
        }
    }
    return $total_units > 0 ? round($total_points / $total_units, 4) : null;
}

/* ── Overall stats ── */
$total_units       = 0;
$academic_units    = 0;
$non_academic_units = 0;
$all_grades        = [];

foreach ($grades_by_year as $year => $semesters) {
    foreach ($semesters as $sem => $grades) {
        foreach ($grades as $g) {
            $all_grades[]   = $g;
            $total_units   += $g['units'];
            if (preg_match('/^(PATHFIT|PE|NSTP)/i', $g['subject_code'])) {
                $non_academic_units += $g['units'];
            } else {
                $academic_units += $g['units'];
            }
        }
    }
}

// Fetch curriculum URL for the student's course
$curriculum_url = '';
$course_info = get_course_info($con, $user_data['course'] ?? '');
$curriculum_url = $course_info['curriculum_url'] ?? '';

$overall_gwa = calculateGWA($all_grades);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
    <link rel="stylesheet" href="../../css/student/student_grades.css">
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
                <div class="acc-name"><?php echo $full_name; ?></div>
                <div class="acc-img">
                    <img src="<?php echo htmlspecialchars($profile_src); ?>" alt="Profile">
                </div>
            </div>
        </div>

        <nav class="main-nav" id="navMenu">
            <div class="nav-wrapper">
                <ul class="main-ul">
                    <li>
                        <a href="student_home.php">
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
                        <a href="student_enrollment.php">
                            <i class="fa-solid fa-id-card"></i>
                            <div class="li-name">Enrollment</div>
                        </a>
                    </li>
                    <li>
                        <a href="student_grades.php" class="active">
                            <i class="fa-solid fa-book"></i>
                            <div class="li-name">Grades</div>
                        </a>
                    </li>
                    <li>
                        <a href="student_my_subjects.php">
                            <i class="fa-solid fa-layer-group"></i>
                            <div class="li-name">My Subjects</div>
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
                                <?php if ($curriculum_url): ?><li><a href="<?php echo htmlspecialchars($curriculum_url); ?>" target="_blank">Curriculum</a></li><?php endif; ?>
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
            <!-- STUDENT INFO CARD -->
            <div class="card">
                <div class="card-header">
                    <h2>Student Information</h2>
                </div>
                <div class="student-body">
                    <div class="avatar-wrap">
                        <img src="<?php echo htmlspecialchars($profile_src); ?>" alt="Profile">
                    </div>
                    <div class="student-details">
                        <div class="detail-item">
                            <label>Full Name</label>
                            <span><?php echo $full_name; ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Student Number</label>
                            <span><?php echo $student_number; ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Program</label>
                            <span><?php echo $program; ?></span>
                        </div>
                        <div class="detail-item">
                            <label>School Year</label>
                            <span><?php echo $year_display; ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Status</label>
                            <span><?php echo ucfirst($account_status); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Semester</label>
                            <span><?php echo htmlspecialchars(get_setting($con, 'current_semester', '—')); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FOLDER TABS + GRADES -->
            <div class="folder-card">
                <!-- Year Tabs -->
                <div class="tab-row">
                    <?php for ($y = 1; $y <= 6; $y++): ?>
                    <button class="tab <?php echo $y === 1 ? 'active' : ''; ?>" data-year="<?php echo $y; ?>">
                        <?php echo formatYear($y); ?>
                    </button>
                    <?php endfor; ?>
                </div>

                <!-- Folder Body -->
                <div class="folder-body">

                    <?php for ($year = 1; $year <= 6; $year++): ?>
                    <div class="year-panel <?php echo $year === 1 ? 'active' : ''; ?>" data-year="<?php echo $year; ?>">

                        <?php foreach (['1st', '2nd'] as $sem): ?>
                        <?php $sem_grades = $grades_by_year[$year][$sem]; ?>
                        <div class="semester-panel">
                            <div class="semester-label"><?php echo $sem; ?> Semester</div>

                            <div class="grades-table-wrapper">
                                <div class="grades-table">

                                    <div class="grades-table-header">
                                        <div>#</div>
                                        <div>Subject Code</div>
                                        <div class="grades-col-left">Subject Title</div>
                                        <div>Units</div>
                                        <div>Section</div>
                                        <div>Professor</div>
                                        <div>Class Standing</div>
                                        <div>Quiz</div>
                                        <div>Midterms</div>
                                        <div>Finals</div>
                                        <div>Final Grade</div>
                                        <div>Remarks</div>
                                    </div>

                                    <div class="grades-table-body">
                                    <?php if (empty($sem_grades)): ?>
                                        <div class="grades-empty">No grades recorded for this semester.</div>
                                    <?php else: ?>
                                        <?php $counter = 1; foreach ($sem_grades as $g): ?>
                                        <div class="grades-row">
                                            <div class="row-num"><?php echo $counter++; ?></div>
                                            <div><span class="subj-code"><?php echo htmlspecialchars($g['subject_code']); ?></span></div>
                                            <div class="grades-col-left"><?php echo htmlspecialchars($g['subject_name']); ?></div>
                                            <div><?php echo htmlspecialchars($g['units']); ?></div>
                                            <div><?php echo htmlspecialchars($g['section'] ?? '—'); ?></div>
                                            <div><?php echo htmlspecialchars($g['faculty_name'] ?? '—'); ?></div>
                                            <div><?php echo $g['class_standing'] !== null ? number_format($g['class_standing'], 2) : '<span style="color:#999;">—</span>'; ?></div>
                                            <div><?php echo $g['quiz'] !== null ? number_format($g['quiz'], 2) : '<span style="color:#999;">—</span>'; ?></div>
                                            <div><?php echo $g['midterms'] !== null ? number_format($g['midterms'], 2) : '<span style="color:#999;">—</span>'; ?></div>
                                            <div><?php echo $g['finals'] !== null ? number_format($g['finals'], 2) : '<span style="color:#999;">—</span>'; ?></div>
                                            <div><?php echo $g['grade'] !== null ? renderGradeValue($g['grade']) : '<span class="grade-val" style="color:#999;">—</span>'; ?></div>
                                            <div><?php echo $g['grade'] !== null ? renderGradeStatus($g['grade']) : '<span class="grade-status ongoing"><i class="fa-solid fa-clock" style="font-size:.65rem"></i>Pending</span>'; ?></div>
                                        </div>
                                        <?php endforeach; ?>

                                        <div class="grades-gwa-row">
                                            GWA: <span class="gwa-val">
                                                <?php
                                                    $gwa = calculateGWA($sem_grades);
                                                    echo $gwa ? number_format($gwa, 4) : '—';
                                                ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    </div>

                                </div>
                            </div>
                        </div><!-- .semester-panel -->
                        <?php endforeach; ?>

                        <!-- Overall Summary Bar -->
                        <div class="summary-bar">
                            <div class="summary-item">
                                <span class="s-label">Overall GWA</span>
                                <span class="s-val gold">
                                    <?php echo $overall_gwa ? number_format($overall_gwa, 5) : '—'; ?>
                                </span>
                            </div>
                            <div class="summary-item">
                                <span class="s-label">Actual Total Units Earned</span>
                                <span class="s-val"><?php echo $total_units; ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="s-label">Academic Units</span>
                                <span class="s-val"><?php echo $academic_units; ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="s-label">Non-Academic Units</span>
                                <span class="s-val"><?php echo $non_academic_units; ?></span>
                            </div>
                        </div><!-- .summary-bar -->

                    </div><!-- .year-panel -->
                    <?php endfor; ?>

                </div><!-- .folder-body -->
            </div><!-- .folder-card -->
        </main>
    </div>

    <script src="../../js/student/student_main.js"></script>
    <script src="../../js/student/student_grades.js"></script>
</body>
</html>