<?php
session_start();
include("../../php/connection.php");
include("../../php/functions.php");
include("../../php/grade_helpers.php");

$user_data = check_login($con);
$profile_src = !empty($user_data['profile_photo'])
    ? '../../' . $user_data['profile_photo']
    : '../../assets/test/student-profile.webp';

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
        default => "N/A",
    };
}
$year_display = formatYear($year_level);

/* ── Fetch grades from DB ── */
$grades_by_year = [];
for ($y = 1; $y <= 4; $y++) {
    $grades_by_year[$y] = ['1st' => [], '2nd' => []];
}

$query = "SELECT g.grade, g.status, g.semester, s.subject_code, s.subject_name, s.units,
                 c.section, c.school_year
          FROM grades g
          JOIN subjects s ON g.subject_id = s.subject_id
          LEFT JOIN enrollments e ON e.student_id = g.student_id
          LEFT JOIN classes c ON e.class_id = c.class_id AND c.subject_id = s.subject_id
          WHERE g.student_id = ?
          ORDER BY c.school_year, g.semester, s.subject_code";

$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "i", $user_data['student_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $sem = $row['semester'] ?? '1st Semester Year 1';
    preg_match('/(\d+)(st|nd|rd|th)\s+Semester/i', $sem, $sem_match);
    preg_match('/Year\s+(\d+)/i', $sem, $year_match);

    $semester_num = (int)($sem_match[1] ?? 1);
    $year_num     = (int)($year_match[1] ?? 1);
    $sem_key      = ($semester_num === 1) ? '1st' : '2nd';

    if (isset($grades_by_year[$year_num][$sem_key])) {
        $grades_by_year[$year_num][$sem_key][] = $row;
    }
}
mysqli_stmt_close($stmt);

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
                                <li><a href="https://web13.plm.edu.ph/media/courses/Bachelor_of_Science_in_Computer_Engineering.pdf" target="_blank">Curriculum</a></li>
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
                            <span>
                                <?php
                                    // Derive current semester label from the latest grades, or default
                                    echo htmlspecialchars($user_data['current_semester'] ?? '—');
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FOLDER TABS + GRADES -->
            <div class="folder-card">
                <!-- Year Tabs -->
                <div class="tab-row">
                    <?php for ($y = 1; $y <= 4; $y++): ?>
                    <button class="tab <?php echo $y === 1 ? 'active' : ''; ?>" data-year="<?php echo $y; ?>">
                        <?php echo formatYear($y); ?>
                    </button>
                    <?php endfor; ?>
                </div>

                <!-- Folder Body -->
                <div class="folder-body">

                    <?php for ($year = 1; $year <= 4; $year++): ?>
                    <div class="year-panel <?php echo $year === 1 ? 'active' : ''; ?>" data-year="<?php echo $year; ?>">

                        <?php foreach (['1st', '2nd'] as $sem): ?>
                        <?php $sem_grades = $grades_by_year[$year][$sem]; ?>
                        <div class="semester-panel">
                            <div class="semester-label"><?php echo $sem; ?> Semester</div>
                            <div class="table-wrapper">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="center">#</th>
                                            <th class="center">Subject Code</th>
                                            <th>Subject Title</th>
                                            <th class="center">Units</th>
                                            <th class="center">Section</th>
                                            <th class="center">Final Grade</th>
                                            <th class="center">Grade Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($sem_grades)): ?>
                                        <tr>
                                            <td colspan="7" style="text-align:center; padding:2rem; color:#999;">
                                                No grades recorded for this semester.
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php $counter = 1; foreach ($sem_grades as $g): ?>
                                        <tr>
                                            <td class="center row-num"><?php echo $counter++; ?></td>
                                            <td><span class="subj-code"><?php echo htmlspecialchars($g['subject_code']); ?></span></td>
                                            <td><?php echo htmlspecialchars($g['subject_name']); ?></td>
                                            <td class="center"><?php echo htmlspecialchars($g['units']); ?></td>
                                            <td class="center"><?php echo htmlspecialchars($g['section'] ?? 'N/A'); ?></td>
                                            <td class="center">
                                                <?php echo renderGradeValue($g['grade']); ?>
                                            </td>
                                            <td class="center">
                                                <?php echo renderGradeStatus($g['grade']); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="gwa-row">
                                            <td colspan="7" class="gwa-label">
                                                GWA: <span class="gwa-val">
                                                    <?php
                                                        $gwa = calculateGWA($sem_grades);
                                                        echo $gwa ? number_format($gwa, 4) : '—';
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div><!-- .table-wrapper -->
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