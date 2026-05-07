<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
include("../../php/connection.php");

if (!isset($_SESSION['faculty_id'])) { header("Location: ../../pages/login_hub.php?portal=faculty"); die; }
$faculty_id = (int)$_SESSION['faculty_id'];

$stmt = mysqli_prepare($con, "SELECT * FROM faculty WHERE faculty_id = ? AND status = 'active' LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $faculty_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (!$result || mysqli_num_rows($result) == 0) { session_destroy(); header("Location: ../../pages/login_hub.php?portal=faculty"); die; }
$faculty_data = mysqli_fetch_assoc($result);

include("../../php/admin_functions.php");

$cur_semester    = get_setting($con, 'current_semester', '');
$cur_school_year = get_setting($con, 'current_school_year', '');

// Fetch assigned classes for current semester and school year only
// Excludes cancelled classes and past-finalized classes with zero enrolled students
$classes = [];
$cq = mysqli_query($con,
    "SELECT c.class_id, c.section, c.schedule_day, c.schedule_time, c.room,
            c.enrolled_count, c.max_slots, c.grades_finalized, c.status,
            s.subject_code, s.subject_name, s.units
     FROM classes c
     JOIN subjects s ON c.subject_id = s.subject_id
     WHERE c.faculty_id = $faculty_id
       AND c.semester = '" . mysqli_real_escape_string($con, $cur_semester) . "'
       AND c.school_year = '" . mysqli_real_escape_string($con, $cur_school_year) . "'
       AND c.status != 'cancelled'
     ORDER BY c.schedule_time, s.subject_code"
);
while ($r = mysqli_fetch_assoc($cq)) $classes[] = $r;

// Summary stats
// Active = open classes not yet finalized
// Finalized = closed classes with grades submitted
$active_classes = array_filter($classes, fn($c) => !$c['grades_finalized'] && $c['status'] === 'open');
$total_load     = count($active_classes);
$total_students = array_sum(array_column(array_values($active_classes), 'enrolled_count'));
$grades_pending = count(array_filter($active_classes, fn($c) => (int)$c['enrolled_count'] > 0));
$grades_done    = count(array_filter($classes, fn($c) => (int)$c['grades_finalized'] === 1));

// Build week schedule grouped by day
$today = date('l');
$day_patterns = [
    'Monday'    => ['M','MW','MWF','MTH','MON','MONDAY'],
    'Tuesday'   => ['T','TTH','TF','TUE','TUESDAY'],
    'Wednesday' => ['W','MW','MWF','WED','WEDNESDAY'],
    'Thursday'  => ['TH','TTH','MTH','THU','THURSDAY'],
    'Friday'    => ['F','MWF','TF','FRI','FRIDAY'],
    'Saturday'  => ['S','SAT','SATURDAY'],
    'Sunday'    => ['SU','SUN','SUNDAY'],
];
$days_order = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$week_schedule = array_fill_keys($days_order, []);

foreach ($classes as $cls) {
    if (empty($cls['schedule_day'])) continue;
    $raw = strtoupper(trim($cls['schedule_day']));
    foreach ($days_order as $day) {
        foreach ($day_patterns[$day] ?? [] as $p) {
            if (strcasecmp($raw, strtoupper($p)) === 0 || preg_match('/\b'.preg_quote(strtoupper($p),'/').'\b/', $raw)) {
                $week_schedule[$day][] = [
                    'subject_code'  => $cls['subject_code'],
                    'subject_name'  => $cls['subject_name'],
                    'schedule_time' => $cls['schedule_time'],
                    'room'          => $cls['room'],
                    'section'       => $cls['section'],
                ];
                break 2;
            }
        }
    }
}

$today_classes = array_filter($classes, function($c) use ($today, $day_patterns) {
    if (empty($c['schedule_day'])) return false;
    $raw = strtoupper(trim($c['schedule_day']));
    foreach ($day_patterns[$today] ?? [] as $p) {
        if (strcasecmp($raw, strtoupper($p)) === 0 || preg_match('/\b'.preg_quote(strtoupper($p),'/').'\b/i', $raw)) return true;
    }
    return false;
});

// Fetch calendar events visible to faculty
$cal_events = [];
$ce_q = mysqli_query($con, "SELECT event_id, title, description, event_date, end_date, event_time, color, audience, image FROM calendar_events WHERE audience IN ('all','faculty') ORDER BY event_date ASC");
while ($ce = mysqli_fetch_assoc($ce_q)) $cal_events[] = $ce;
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - Faculty Portal</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"/>
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
    <link rel="stylesheet" href="../../css/faculty/faculty_home.css" />
    <link rel="stylesheet" href="../../css/plm_loader.css" />
    <link rel="stylesheet" href="../../css/faculty/faculty_main.css" />
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
                    <?php echo htmlspecialchars($faculty_data['first_name'] . ' ' . $faculty_data['last_name']); ?>
                </div>
                <div class="acc-img">
                    <img src="<?php echo !empty($faculty_data['profile_photo']) ? htmlspecialchars('../../'.$faculty_data['profile_photo']) : '../../uploads/default.jpg'; ?>" alt="Profile">
                </div>
            </div>
        </div>

        <nav class="main-nav" id="navMenu">
            <div class="nav-wrapper">
                <ul class="main-ul">
                    <li>
                        <a href="faculty_home.php" class="active">
                            <i class="fa-solid fa-house"></i>
                            <div class="li-name">Dashboard</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_load.php">
                            <i class="fa-solid fa-calendar"></i>
                            <div class="li-name">Schedule</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_classlist.php">
                            <i class="fa-solid fa-list"></i>
                            <div class="li-name">Class List</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_spreadsheet.php">
                            <i class="fa-solid fa-table"></i>
                            <div class="li-name">Spreadsheet</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_gradebook.php">
                            <i class="fa-solid fa-book"></i>
                            <div class="li-name">Gradebook</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_grade_history.php">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <div class="li-name">Grade History</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_profile.php">
                            <i class="fa-solid fa-user"></i>
                            <div class="li-name">Profile</div>
                        </a>
                    </li>
                    <li>
                        <a href="../../php/faculty_logout.php" class="logout-bg">
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
            <!-- FACULTY INFO CARD -->
            <div class="card">
                <div class="card-header">
                    <h2>Faculty Information</h2>
                </div>
                <div class="faculty-body">
                    <div class="avatar-wrap">
                        <img src="<?php echo !empty($faculty_data['profile_photo']) ? htmlspecialchars('../../'.$faculty_data['profile_photo']) : '../../uploads/default.jpg'; ?>" alt="Profile">
                    </div>
                    <div class="faculty-title-content">
                        <h2><?php echo htmlspecialchars($faculty_data['first_name'] . ' ' . ($faculty_data['middle_name'] ?? '') . ' ' . $faculty_data['last_name']); ?></h2>
                        <p><?php echo htmlspecialchars($faculty_data['email']); ?></p>
                    </div>
                    <div class="faculty-divider"></div>
                    <div class="faculty-details-wrapper">
                        <div class="faculty-details">
                            <div class="detail-item">
                                <label>Employee ID</label>
                                <span><?php echo htmlspecialchars($faculty_data['employee_id']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Position</label>
                                <span><?php echo htmlspecialchars($faculty_data['position'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>College</label>
                                <span><?php echo htmlspecialchars($faculty_data['college'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Department</label>
                                <span><?php echo htmlspecialchars($faculty_data['department'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Employment Status</label>
                                <span><?php echo htmlspecialchars(ucfirst($faculty_data['employment_status'] ?? 'N/A')); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Specialization</label>
                                <span><?php echo htmlspecialchars($faculty_data['specialization'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SUMMARY STRIP -->
            <div class="dash-summary-strip">
                <div class="dash-summary-cell">
                    <span class="dash-summary-num"><?php echo $total_load; ?></span>
                    <span class="dash-summary-label">Classes This Sem</span>
                </div>
                <div class="dash-summary-cell">
                    <span class="dash-summary-num"><?php echo $total_students; ?></span>
                    <span class="dash-summary-label">Total Students</span>
                </div>
                <div class="dash-summary-cell <?php echo $grades_pending > 0 ? 'dash-summary-warn' : ''; ?>">
                    <span class="dash-summary-num"><?php echo $grades_pending; ?></span>
                    <span class="dash-summary-label">Grades Pending</span>
                </div>
                <div class="dash-summary-cell">
                    <span class="dash-summary-num"><?php echo $grades_done; ?></span>
                    <span class="dash-summary-label">Grades Finalized</span>
                </div>
                <div class="dash-summary-cell">
                    <span class="dash-summary-num" style="font-size:0.9rem;font-weight:700;color:var(--text-label);"><?php echo htmlspecialchars(ucfirst($cur_semester) . ' ' . $cur_school_year); ?></span>
                    <span class="dash-summary-label">Current Semester</span>
                </div>
            </div>

            <div class="content-grid">
                <!-- SCHEDULE CARD (Today / This Week toggle + List / Grid view) -->
                <div class="card" id="scheduleCard">
                    <div class="card-header" style="flex-direction:column;gap:0.6rem;align-items:stretch;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <h2>Schedule</h2>
                            <a href="faculty_load.php" class="link-small">Full Schedule</a>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
                            <div class="sched-toggle">
                                <button class="sched-toggle-btn active" id="btnToday" onclick="setRange('today')">Today</button>
                                <button class="sched-toggle-btn" id="btnWeek"  onclick="setRange('week')">This Week</button>
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
                            <?php if (empty($today_classes)): ?>
                                <div class="sched-empty"><i class="fa-solid fa-calendar-xmark"></i> No classes today.</div>
                            <?php else: ?>
                                <?php foreach ($today_classes as $cls): ?>
                                <div class="schedule-item">
                                    <div class="schedule-time"><?php echo htmlspecialchars($cls['schedule_time'] ?: 'TBA'); ?></div>
                                    <div class="schedule-details">
                                        <h4><?php echo htmlspecialchars($cls['subject_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($cls['room'] ?: 'TBA'); ?> &bull; <?php echo htmlspecialchars($cls['section']); ?></p>
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
                            <?php foreach ($week_schedule[$day] as $cls): ?>
                            <div class="schedule-item">
                                <div class="schedule-time"><?php echo htmlspecialchars($cls['schedule_time'] ?: 'TBA'); ?></div>
                                <div class="schedule-details">
                                    <h4><?php echo htmlspecialchars($cls['subject_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($cls['room'] ?: 'TBA'); ?> &bull; <?php echo htmlspecialchars($cls['section']); ?></p>
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

            <!-- ANNOUNCEMENTS (replaces static News & Events) -->
            <div class="card" style="margin-top:1.5rem;">
                <div class="card-header">
                    <h2><i class="fa-solid fa-bullhorn" style="color:var(--gold);margin-right:0.5rem;"></i>Announcements</h2>
                </div>
                <div style="padding:0.5rem 0;">
                    <?php $ann_audience = 'faculty'; include('../../php/announcement_feed.php'); ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Pass PHP data to JS -->
    <script>
    window._weekSchedule = <?php echo json_encode($week_schedule, JSON_UNESCAPED_UNICODE); ?>;
    window._calEvents    = <?php echo json_encode($cal_events,    JSON_UNESCAPED_UNICODE); ?>;
    window._evImageBase  = '../../uploads/events/';
    </script>

<!-- Calendar Event Modal -->
<div id="calEventModal">
    <div class="cem-dialog">
        <div id="cem_banner_wrap"></div>
        <div id="cem_color_strip"></div>
        <div class="cem-body">
            <div class="cem-header">
                <h3 id="cem_title"></h3>
                <button class="cem-close-btn" onclick="closeCalEventModal()">&times;</button>
            </div>
            <div id="cem_meta"></div>
            <p id="cem_desc"></p>
        </div>
    </div>
</div>

    <script src="../../js/faculty/faculty_home.js"></script>
    <script src="../../js/faculty/faculty_main.js"></script>
    <script src="../../js/no_cache.js"></script>
    <script src="../../js/plm_loader.js"></script>

    <?php if (!empty($_SESSION['must_change_password']) || !empty($faculty_data['must_change_password'])): ?>
    <!-- Force password change modal -->
    <div id="pwChangeModal" style="display:flex;position:fixed;z-index:2000;inset:0;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;">
        <div style="background:var(--card,#fff);border-radius:12px;padding:2rem;width:90%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.25);">
            <h3 style="margin:0 0 0.5rem;font-size:1.15rem;"><i class="fa-solid fa-lock" style="color:#8c1c24;margin-right:0.5rem;"></i>Change Your Password</h3>
            <p style="font-size:0.88rem;color:var(--text-label,#666);margin:0 0 1.25rem;">Your account is using a temporary password. Please set a new password to continue.</p>
            <div id="pwChangeError" style="display:none;color:#c0392b;font-size:0.85rem;margin-bottom:0.75rem;"></div>
            <form id="pwChangeForm">
                <div style="margin-bottom:1rem;">
                    <label style="font-size:0.82rem;font-weight:600;display:block;margin-bottom:0.35rem;">New Password</label>
                    <input type="password" id="newPw" placeholder="Enter new password" required
                        style="width:100%;padding:0.6rem 0.75rem;border:1px solid var(--border,#ddd);border-radius:6px;font-size:0.9rem;box-sizing:border-box;">
                </div>
                <div style="margin-bottom:1.25rem;">
                    <label style="font-size:0.82rem;font-weight:600;display:block;margin-bottom:0.35rem;">Confirm Password</label>
                    <input type="password" id="confirmPw" placeholder="Confirm new password" required
                        style="width:100%;padding:0.6rem 0.75rem;border:1px solid var(--border,#ddd);border-radius:6px;font-size:0.9rem;box-sizing:border-box;">
                </div>
                <button type="submit" style="width:100%;padding:0.7rem;background:#8c1c24;color:#fff;border:none;border-radius:6px;font-size:0.95rem;cursor:pointer;font-weight:600;">Update Password</button>
            </form>
        </div>
    </div>
    <script>
    document.getElementById('pwChangeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const np = document.getElementById('newPw').value;
        const cp = document.getElementById('confirmPw').value;
        const err = document.getElementById('pwChangeError');
        if (np.length < 6) { err.textContent = 'Password must be at least 6 characters.'; err.style.display='block'; return; }
        if (np !== cp)     { err.textContent = 'Passwords do not match.'; err.style.display='block'; return; }
        err.style.display = 'none';
        fetch('../../php/faculty_profile_handler.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'action=change_password&new_password=' + encodeURIComponent(np) + '&confirm_password=' + encodeURIComponent(cp)
        })
        .then(r => r.json())
        .then(d => {
            if (d.ok) {
                document.getElementById('pwChangeModal').style.display = 'none';
            } else {
                err.textContent = d.msg || 'Failed to update password.';
                err.style.display = 'block';
            }
        })
        .catch(() => { err.textContent = 'An error occurred.'; err.style.display='block'; });
    });
    </script>
    <?php endif; ?>

  </body>
</html>
