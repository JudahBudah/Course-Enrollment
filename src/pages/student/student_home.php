<?php
    session_start();
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    include("../../php/connection.php");
    include("../../php/functions.php");

    $user_data = check_login($con);

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
                    <div class="avatar-wrap">
                        <img src="<?php echo htmlspecialchars($profile_src); ?>" alt="Profile">
                    </div>
                    <div class="student-title-content">
                        <h3><?php echo htmlspecialchars(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? '')); ?></h3>
                        <p><?php echo htmlspecialchars($user_data['student_number'] ?? 'N/A'); ?></p>
                        <p><?php echo htmlspecialchars($user_data['email'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="student-divider"></div>
                    <div class="student-details">
                        <div class="detail-item">
                            <label>Program</label>
                            <span><?php echo $program; ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Year</label>
                            <span><?php echo $year_display; ?></span>
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
</body>
</html>