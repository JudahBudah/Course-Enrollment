<?php
    session_start();
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    include("../../php/connection.php");
    include("../../php/functions.php");

    $user_data = check_login($con);

    $block_row = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT b.block_name FROM students s
         LEFT JOIN blocks b ON s.block_id = b.block_id
         WHERE s.student_id = {$user_data['student_id']} LIMIT 1"
    ));
    $block_name = $block_row['block_name'] ?? null;

    // Check if student must change their password
    if (!empty($user_data['must_change_password'])) {
        $_SESSION['must_change_password'] = true;
    } else {
        unset($_SESSION['must_change_password']);
    }

    // Profile photo fallback
    $profile_src = !empty($user_data['profile_photo']) 
        ? '../../' . $user_data['profile_photo'] 
        : '../../uploads/default.jpg';

    // Student details
    $program              = htmlspecialchars($user_data['course'] ?? 'N/A');
    $year_level           = $user_data['year_level'] ?? null;
    $registration_status  = htmlspecialchars($user_data['registration_status'] ?? 'Regular');
    $account_status       = !empty($registration_status) ? $registration_status : 'Regular';

    // Format year level
    function formatYear($year) {
        return match($year) {
            1 => "1st Year",
            2 => "2nd Year",
            3 => "3rd Year",
            4 => "4th Year",
            default => "N/A",
        };
    }
    $year_display = formatYear($year_level);

    // Fetch curriculum URL for the student's course
    $curriculum_url = '';
    $course_info = get_course_info($con, $user_data['course'] ?? '');
    $curriculum_url = $course_info['curriculum_url'] ?? '';

    // Enrollment status
    $enrollment_status = "Not Enrolled";
    $stmt = mysqli_prepare($con, "SELECT status FROM enrollments WHERE student_id = ? AND status IN ('confirmed','ongoing') ORDER BY enrollment_id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $user_data['student_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $enrollment_status = !empty($row['status']) ? ucfirst($row['status']) : "Not Enrolled";
    }
    mysqli_stmt_close($stmt);

    // Day abbreviation map for schedule matching
    $today_name = date('l');
    $today_abbr_map = [
        'Monday'    => ['M','MW','MWF','MTH','MF','Monday','Mon'],
        'Tuesday'   => ['T','TTH','TF','Tuesday','Tue'],
        'Wednesday' => ['W','MW','MWF','WF','Wednesday','Wed'],
        'Thursday'  => ['TH','TTH','MTH','THS','Thursday','Thu'],
        'Friday'    => ['F','MWF','TF','WF','MF','Friday','Fri'],
        'Saturday'  => ['S','THS','Saturday','Sat'],
        'Sunday'    => ['SU','Sunday','Sun'],
    ];
    
    // Helper function to parse day strings
    function parseScheduleDays($day_str) {
        $day_str = trim($day_str);
        $day_str_upper = strtoupper($day_str);
        
        // Direct patterns
        $patterns = [
            'MW' => ['Monday','Wednesday'],
            'MWF' => ['Monday','Wednesday','Friday'],
            'TTH' => ['Tuesday','Thursday'],
            'TH' => ['Thursday'],
            'TF' => ['Tuesday','Friday'],
            'MTH' => ['Monday','Thursday'],
            'WF' => ['Wednesday','Friday'],
            'MF' => ['Monday','Friday'],
            'THS' => ['Thursday','Saturday'],
            'MTWTHF' => ['Monday','Tuesday','Wednesday','Thursday','Friday'],
        ];
        
        if (isset($patterns[$day_str_upper])) {
            return $patterns[$day_str_upper];
        }
        
        // Single days
        $singles = [
            'M' => 'Monday', 'T' => 'Tuesday', 'W' => 'Wednesday',
            'F' => 'Friday', 'S' => 'Saturday', 'SU' => 'Sunday',
            'MONDAY' => 'Monday', 'TUESDAY' => 'Tuesday', 'WEDNESDAY' => 'Wednesday',
            'THURSDAY' => 'Thursday', 'FRIDAY' => 'Friday', 'SATURDAY' => 'Saturday', 'SUNDAY' => 'Sunday',
            'MON' => 'Monday', 'TUE' => 'Tuesday', 'WED' => 'Wednesday',
            'THU' => 'Thursday', 'FRI' => 'Friday', 'SAT' => 'Saturday', 'SUN' => 'Sunday',
        ];
        
        if (isset($singles[$day_str_upper])) {
            return [$singles[$day_str_upper]];
        }
        
        // Try splitting by delimiters
        $parts = preg_split('/[,\/\s]+/', $day_str);
        if (count($parts) > 1) {
            $result = [];
            foreach ($parts as $p) {
                $p = trim($p);
                if (empty($p)) continue;
                $sub = parseScheduleDays($p);
                $result = array_merge($result, $sub);
            }
            return array_unique($result);
        }
        
        // Parse concatenated (e.g., THS)
        $result = [];
        $i = 0;
        $len = strlen($day_str_upper);
        
        while ($i < $len) {
            if ($i + 1 < $len) {
                $two = substr($day_str_upper, $i, 2);
                if ($two === 'TH') {
                    $result[] = 'Thursday';
                    $i += 2;
                    continue;
                }
                if ($two === 'SU') {
                    $result[] = 'Sunday';
                    $i += 2;
                    continue;
                }
            }
            
            $one = substr($day_str_upper, $i, 1);
            $map = ['M'=>'Monday','T'=>'Tuesday','W'=>'Wednesday','F'=>'Friday','S'=>'Saturday'];
            if (isset($map[$one])) {
                $result[] = $map[$one];
            }
            $i++;
        }
        
        return !empty($result) ? array_unique($result) : [];
    }

    // Fetch all enrolled classes with schedule
    $all_schedule = [];
    $sched_stmt = mysqli_prepare($con, "
        SELECT s.subject_code, s.subject_name, c.schedule_day, c.schedule_time, c.room,
               CONCAT(f.first_name, ' ', f.last_name) AS faculty_name
        FROM enrollments e
        JOIN classes c ON e.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
        WHERE e.student_id = ? AND e.status IN ('confirmed','ongoing')
          AND c.schedule_day IS NOT NULL AND c.schedule_day != ''
        ORDER BY c.schedule_time
    ");
    mysqli_stmt_bind_param($sched_stmt, "i", $user_data['student_id']);
    mysqli_stmt_execute($sched_stmt);
    $sched_result = mysqli_stmt_get_result($sched_stmt);
    while ($r = mysqli_fetch_assoc($sched_result)) $all_schedule[] = $r;
    mysqli_stmt_close($sched_stmt);

    // Group schedule by day
    $days_order    = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    $week_schedule = array_fill_keys($days_order, []);

    foreach ($all_schedule as $cls) {
        $parsed_days = parseScheduleDays($cls['schedule_day']);
        foreach ($parsed_days as $day) {
            if (in_array($day, $days_order)) {
                $week_schedule[$day][] = $cls;
            }
        }
    }

    $today_schedule = $week_schedule[$today_name] ?? [];

    // Quick grades summary for dashboard block
    require_once '../../php/admin_functions.php';
    require_once '../../php/grade_helpers.php';
    $cur_sem = get_setting($con, 'current_semester', '');
    $cur_sy  = get_setting($con, 'current_school_year', '');
    $block_semester = $cur_sem;

    // Enrolled subjects & units this semester
    $stmt2 = mysqli_prepare($con,
        "SELECT COUNT(*) as subj_count, COALESCE(SUM(s.units),0) as total_units
         FROM enrollments e
         JOIN classes c ON e.class_id = c.class_id
         JOIN subjects s ON c.subject_id = s.subject_id
         WHERE e.student_id = ? AND e.status IN ('confirmed','ongoing')
           AND c.semester = ? AND c.school_year = ?");
    mysqli_stmt_bind_param($stmt2, 'iss', $user_data['student_id'], $cur_sem, $cur_sy);
    mysqli_stmt_execute($stmt2);
    $enroll_stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
    mysqli_stmt_close($stmt2);
    $dash_subj_count  = (int)($enroll_stats['subj_count']  ?? 0);
    $dash_total_units = (int)($enroll_stats['total_units'] ?? 0);

    // Pending drop requests
    $drop_stmt = mysqli_prepare($con,
        "SELECT COUNT(*) as c FROM enrollments e
         JOIN classes c ON e.class_id = c.class_id
         WHERE e.student_id = ? AND e.status = 'drop_requested'
           AND c.semester = ? AND c.school_year = ?");
    mysqli_stmt_bind_param($drop_stmt, 'iss', $user_data['student_id'], $cur_sem, $cur_sy);
    mysqli_stmt_execute($drop_stmt);
    $dash_pending_drops = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($drop_stmt))['c'] ?? 0);
    mysqli_stmt_close($drop_stmt);

    // Enrollment period
    $dash_period = get_enrollment_period($con);
    $dash_period_map = [
        'enrollment'      => ['Open',      '#16a34a'],
        'late_enrollment' => ['Late/Add-Drop', '#d97706'],
        'closed'          => ['Closed',    '#dc2626'],
    ];
    [$dash_period_label, $dash_period_color] = $dash_period_map[$dash_period] ?? ['Unknown','#888'];
    $max_units = (int)get_setting($con, 'max_units', '0');

    $gs = mysqli_prepare($con, "
        SELECT ge.computed_grade, s.units
        FROM enrollments e
        JOIN classes c ON e.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN grade_entries ge ON ge.enrollment_id = e.enrollment_id
        WHERE e.student_id = ? AND e.status IN ('confirmed','ongoing','completed')
          AND c.grades_finalized = 1
    ");
    mysqli_stmt_bind_param($gs, 'i', $user_data['student_id']);
    mysqli_stmt_execute($gs);
    $gs_res = mysqli_stmt_get_result($gs);
    $gwa_points = 0; $gwa_units = 0; $graded_count = 0;
    while ($gr = mysqli_fetch_assoc($gs_res)) {
        if ($gr['computed_grade'] === null) continue;
        $t = (function($g) {
            if ($g>=97) return 99; if ($g>=94) return 96; if ($g>=91) return 93;
            if ($g>=88) return 90; if ($g>=85) return 87; if ($g>=82) return 84;
            if ($g>=79) return 81; if ($g>=76) return 78; if ($g>=73) return 75;
            if ($g>=70) return 72; if ($g>=67) return 69; if ($g>=64) return 66;
            if ($g>=61) return 63; if ($g>=55) return 60; return 55;
        })((float)$gr['computed_grade']);
        $pg = (function($t) {
            if ($t>=97) return 1.00; if ($t>=94) return 1.25; if ($t>=91) return 1.50;
            if ($t>=88) return 1.75; if ($t>=85) return 2.00; if ($t>=82) return 2.25;
            if ($t>=79) return 2.50; if ($t>=76) return 2.75; if ($t>=73) return 3.00;
            if ($t>=70) return 4.00; return 5.00;
        })($t);
        $gwa_points += $pg * $gr['units'];
        $gwa_units  += $gr['units'];
        $graded_count++;
    }
    mysqli_stmt_close($gs);
    $dash_gwa = $gwa_units > 0 ? number_format($gwa_points / $gwa_units, 4) : null;

    // Fetch calendar events visible to students
    $cal_events = [];
    $ce_q = mysqli_query($con, "
        SELECT event_id, title, description, event_date, end_date, event_time, color, audience, image
        FROM calendar_events
        WHERE audience IN ('all','students')
        ORDER BY event_date ASC
    ");
    while ($ce = mysqli_fetch_assoc($ce_q)) $cal_events[] = $ce;

    // Slider images (used as fallback for announcements without images)
    $slider_images = [
        '../../assets/student_home_slider/slider-sample01.webp',
        '../../assets/student_home_slider/slider-sample02.webp',
        '../../assets/student_home_slider/slider-sample03.webp',
        '../../assets/student_home_slider/slider-sample04.webp',
        '../../assets/student_home_slider/slider-sample05.webp',
    ];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
    <link rel="stylesheet" href="../../css/student/student_home.css">
    <link rel="stylesheet" href="../../css/student/student_main.css">
    <link rel="stylesheet" href="../../css/plm_loader.css">
    <script>window.addEventListener('pageshow',function(e){if(e.persisted){document.documentElement.style.visibility='hidden';window.location.reload();}});</script>
</head>
<body>

    <!-- Loading Screen -->
    <div id="plm-loader">
        <div id="plm-loader-bar"></div>
        <div class="plm-loader-logo">
            <img src="../../assets/plm-logo.png" alt="PLM">
            <div class="plm-loader-name">
                <p>PLM</p>
                <p>Pamantasan ng Lungsod ng Maynila</p>
            </div>
            <div class="plm-loader-dots">
                <span></span><span></span><span></span>
            </div>
            <p class="plm-loader-status" id="plm-loader-status">Loading...</p>
        </div>
    </div>
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
                    <?php echo htmlspecialchars(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? '')); ?>
                </div>
                <div class="acc-img">
                    <img src="<?php echo htmlspecialchars($profile_src); ?>" alt="Profile">
                </div>
            </div>
        </div>

        <nav class="main-nav" id="navMenu">
            <div class="nav-wrapper">
                <ul class="main-ul">
                    <li>
                        <a href="student_home.php" class="active">
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
                        <a href="student_grades.php">
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

            <!-- Dark Mode Toggle -->
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
                    <div class="profile-section">
                        <div class="avatar-wrap">
                                <img src="<?php echo htmlspecialchars($profile_src); ?>" alt="Profile">
                            </div>
                        <div class="student-title-content">
                            <h3><?php echo htmlspecialchars(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? '')); ?></h3>
                            <p><?php echo htmlspecialchars($user_data['student_number'] ?? 'N/A'); ?></p>
                            <p><?php echo htmlspecialchars($user_data['email'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                    <div class="student-divider"></div>
                    <div class="student-details">
                        <div class="detail-item">
                            <label>Program</label>
                            <span><?php echo $program; ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Block</label>
                            <span><?php echo htmlspecialchars($block_name ?? 'No Block'); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Year</label>
                            <span><?php echo $year_display; ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Semester</label>
                            <span><?php echo htmlspecialchars($block_semester ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Registration Status</label>
                            <span><?php echo ucfirst($account_status); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Enrollment Status</label>
                            <span><?php echo htmlspecialchars($enrollment_status); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SUMMARY STRIP -->
            <div class="dash-summary-strip">
                <div class="dash-summary-cell">
                    <span class="dash-summary-num"><?php echo $dash_subj_count; ?></span>
                    <span class="dash-summary-label">Subjects Enrolled</span>
                </div>
                <div class="dash-summary-cell">
                    <span class="dash-summary-num"><?php echo $dash_total_units; ?><?php if ($max_units > 0): ?><span class="dash-summary-denom">/<?php echo $max_units; ?></span><?php endif; ?></span>
                    <span class="dash-summary-label">Units This Sem</span>
                </div>
                <div class="dash-summary-cell">
                    <span class="dash-summary-num" style="<?php echo $dash_gwa ? '' : 'font-size:1rem;color:var(--text-label);'; ?>"><?php echo $dash_gwa ?? '—'; ?></span>
                    <span class="dash-summary-label">GWA (Finalized)</span>
                </div>
                <div class="dash-summary-cell <?php echo $dash_pending_drops > 0 ? 'dash-summary-warn' : ''; ?>">
                    <span class="dash-summary-num"><?php echo $dash_pending_drops; ?></span>
                    <span class="dash-summary-label">Pending Drops</span>
                </div>
                <div class="dash-summary-cell">
                    <span class="dash-summary-num" style="font-size:0.95rem;font-weight:700;color:<?php echo $dash_period_color; ?>;"><?php echo $dash_period_label; ?></span>
                    <span class="dash-summary-label">Enrollment Period</span>
                </div>
            </div>

            <div class="content-grid">
                <!-- SCHEDULE CARD -->
                <div class="card" id="scheduleCard">
                    <div class="card-header" style="flex-direction:column;gap:0.6rem;align-items:stretch;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <h2>Schedule</h2>
                            <a href="student_subjects.php" class="link-small">Full Schedule</a>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
                            <div class="sched-toggle">
                                <button class="sched-toggle-btn active" id="btnToday" onclick="setRange('today')">Today</button>
                                <button class="sched-toggle-btn" id="btnWeek" onclick="setRange('week')">This Week</button>
                            </div>
                            <div class="sched-toggle">
                                <button class="sched-toggle-btn active" id="btnList" onclick="setMode('list')" title="List view"><i class="fa-solid fa-list"></i></button>
                                <button class="sched-toggle-btn" id="btnGrid" onclick="setMode('grid')" title="Grid view"><i class="fa-solid fa-table-cells"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- LIST VIEW -->
                    <div id="schedListView">
                        <div id="listToday">
                            <?php if (empty($today_schedule)): ?>
                                <div class="sched-empty"><i class="fa-solid fa-calendar-xmark"></i> No classes today.</div>
                            <?php else: ?>
                                <?php foreach ($today_schedule as $s): ?>
                                <div class="schedule-item">
                                    <div class="schedule-time"><?php echo htmlspecialchars($s['schedule_time'] ?? 'TBA'); ?></div>
                                    <div class="schedule-details">
                                        <h4><?php echo htmlspecialchars($s['subject_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($s['room'] ?? 'TBA'); ?> &bull; <?php echo htmlspecialchars($s['faculty_name'] ?? 'TBA'); ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div id="listWeek" style="display:none;">
                            <?php
                            $has_any = false;
                            foreach ($days_order as $day):
                                if (empty($week_schedule[$day])) continue;
                                $has_any = true;
                            ?>
                                <div class="sched-day-label"><?php echo $day; ?></div>
                                <?php foreach ($week_schedule[$day] as $s): ?>
                                <div class="schedule-item">
                                    <div class="schedule-time"><?php echo htmlspecialchars($s['schedule_time'] ?? 'TBA'); ?></div>
                                    <div class="schedule-details">
                                        <h4><?php echo htmlspecialchars($s['subject_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($s['room'] ?? 'TBA'); ?> &bull; <?php echo htmlspecialchars($s['faculty_name'] ?? 'TBA'); ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            <?php if (!$has_any): ?>
                                <div class="sched-empty"><i class="fa-solid fa-calendar-xmark"></i> No classes this week.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- GRID VIEW -->
                    <div id="schedGridView" style="display:none;overflow-x:auto;">
                        <div id="gridEmpty" class="sched-empty" style="display:none;">
                            <i class="fa-solid fa-calendar-xmark"></i> No classes to display.
                        </div>
                        <div class="mini-weekly-grid" id="miniWeeklyGrid"></div>
                    </div>
                </div>

                <!-- CALENDAR CARD -->
                <div class="card">
                    <div class="card-header">
                        <h2>Calendar</h2>
                        <div class="cal-nav">
                            <button class="cal-nav-btn" id="cal-prev"><i class="fa-solid fa-chevron-left"></i></button>
                            <span class="cal-month-label" id="cal-month-label"></span>
                            <button class="cal-nav-btn" id="cal-next"><i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    </div>
                    <div class="calendar-wrap">
                        <div class="cal-grid-header">
                            <span>Sun</span><span>Mon</span><span>Tue</span>
                            <span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                        </div>
                        <div class="cal-grid" id="cal-grid"></div>
                    </div>
                    <div class="cal-event-peek" id="cal-event-peek">
                        <p id="cal-event-text"></p>
                    </div>
                </div>
            </div>

            <!-- NEWS & EVENTS -->
            <div class="news-events-section">
                <div class="news-events-header">
                    <div class="news-events-title">
                        <i class="fa-regular fa-newspaper"></i>
                        <span>News & Events</span>
                    </div>
                    <div class="nav-buttons">
                        <button onclick="annScroll(-1)"><i class="fa-solid fa-chevron-left"></i></button>
                        <button onclick="annScroll(1)"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
                <hr>
                <?php $ann_audience = 'students'; include('../../php/announcement_feed.php'); ?>
            </div>
        </main>
    </div>

    <!-- Calendar Event Modal -->
    <div id="calEventModal">
        <div class="cem-inner">
            <div id="cem_banner_wrap"></div>
            <div id="cem_color_strip"></div>
            <div class="cem-body">
                <h3 id="cem_title"></h3>
                <div id="cem_meta"></div>
                <p id="cem_desc"></p>
            </div>
            <button class="cem-close-btn" onclick="closeCalEventModal()">Close</button>
        </div>
    </div>

    <!-- Pass PHP data to JavaScript -->
    <script>
        window._weekSchedule = <?php echo json_encode($week_schedule, JSON_UNESCAPED_UNICODE); ?>;
        window._calEvents    = <?php echo json_encode($cal_events,    JSON_UNESCAPED_UNICODE); ?>;
        window._evImageBase  = '../../uploads/events/';

        function annScroll(d) {
            const r = document.getElementById('annNewsRow');
            if (r) r.scrollBy({ left: d * 240, behavior: 'smooth' });
        }
    </script>

    <script src="../../js/student/student_home.js"></script>
    <script src="../../js/student/student_main.js"></script>
    <script src="../../js/no_cache.js"></script>
    <script src="../../js/plm_loader.js"></script>

    <?php if (!empty($_SESSION['must_change_password'])): ?>
    <div id="pwChangeOverlay" style="position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.65);display:flex;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;padding:2rem;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <div style="text-align:center;margin-bottom:1.25rem;">
                <i class="fa-solid fa-lock" style="font-size:2rem;color:#8C1C24;"></i>
                <h2 style="margin:0.5rem 0 0.25rem;font-size:1.2rem;">Change Your Password</h2>
                <p style="font-size:0.85rem;color:#666;margin:0;">Your account was created with a temporary password. Please set a new one to continue.</p>
            </div>
            <div id="pwChangeError" style="display:none;background:#fde8ea;color:#8C1C24;border-radius:6px;padding:0.6rem 0.9rem;font-size:0.83rem;margin-bottom:1rem;"></div>
            <div style="display:flex;flex-direction:column;gap:0.85rem;">
                <div style="position:relative;">
                    <input type="password" id="pw_new" placeholder="New password (min. 6 characters)"
                        style="width:100%;padding:0.6rem 2.5rem 0.6rem 0.75rem;border:1px solid #ddd;border-radius:6px;font-size:0.9rem;box-sizing:border-box;">
                    <i class="fa-regular fa-eye" id="pw_new_icon" onclick="togglePwVis('pw_new','pw_new_icon')"
                        style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);cursor:pointer;color:#888;"></i>
                </div>
                <div style="position:relative;">
                    <input type="password" id="pw_confirm" placeholder="Confirm new password"
                        style="width:100%;padding:0.6rem 2.5rem 0.6rem 0.75rem;border:1px solid #ddd;border-radius:6px;font-size:0.9rem;box-sizing:border-box;">
                    <i class="fa-regular fa-eye" id="pw_confirm_icon" onclick="togglePwVis('pw_confirm','pw_confirm_icon')"
                        style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);cursor:pointer;color:#888;"></i>
                </div>
                <button onclick="submitPwChange()"
                    style="background:#8C1C24;color:#fff;border:none;border-radius:6px;padding:0.7rem;font-size:0.95rem;font-weight:600;cursor:pointer;">
                    Set New Password
                </button>
            </div>
        </div>
    </div>
    <script>
        function togglePwVis(inputId, iconId) {
            const inp  = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (inp.type === 'password') {
                inp.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                inp.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
        function submitPwChange() {
            const newPw  = document.getElementById('pw_new').value;
            const confPw = document.getElementById('pw_confirm').value;
            const errEl  = document.getElementById('pwChangeError');
            errEl.style.display = 'none';
            if (newPw.length < 6) {
                errEl.textContent = 'Password must be at least 6 characters.';
                errEl.style.display = 'block'; return;
            }
            if (newPw !== confPw) {
                errEl.textContent = 'Passwords do not match.';
                errEl.style.display = 'block'; return;
            }
            const fd = new FormData();
            fd.append('action',           'change_password');
            fd.append('new_password',     newPw);
            fd.append('confirm_password', confPw);
            fetch('../../php/update_student_profile.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.ok) {
                        document.getElementById('pwChangeOverlay').remove();
                    } else {
                        errEl.textContent = d.msg || 'Failed to change password.';
                        errEl.style.display = 'block';
                    }
                })
                .catch(() => {
                    errEl.textContent = 'An error occurred. Please try again.';
                    errEl.style.display = 'block';
                });
        }
        document.getElementById('pw_confirm').addEventListener('keydown', e => {
            if (e.key === 'Enter') submitPwChange();
        });
    </script>
    <?php endif; ?>
</body>
</html>