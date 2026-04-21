<?php
session_start();
include("../../php/connection.php");
include("../../php/functions.php");
include("../../php/admin_functions.php");

$user_data = check_login($con);

// System current semester
$cur_semester    = get_setting($con, 'current_semester', '1st');
$cur_school_year = get_setting($con, 'current_school_year', '');

$profile_src = !empty($user_data['profile_photo'])
    ? '../../' . $user_data['profile_photo']
    : '../../uploads/default.jpg';

$program = htmlspecialchars($user_data['course'] ?? 'N/A');

function formatYear($year) {
    return match($year) {
        1 => "1st Year", 2 => "2nd Year", 3 => "3rd Year", 4 => "4th Year",
        default => "N/A",
    };
}
$year_display = formatYear($user_data['year_level'] ?? null);

// Determine the student's current block semester for default filter
$block_semester = null;
if (!empty($user_data['block_id'])) {
    $bstmt = mysqli_prepare($con, "SELECT semester FROM blocks WHERE block_id = ? LIMIT 1");
    mysqli_stmt_bind_param($bstmt, 'i', $user_data['block_id']);
    mysqli_stmt_execute($bstmt);
    $brow = mysqli_fetch_assoc(mysqli_stmt_get_result($bstmt));
    $block_semester = $brow['semester'] ?? null; // e.g. '1st', '2nd', 'summer'
    mysqli_stmt_close($bstmt);
}

// Map block semester to display label and back
$semester_map = [
    '1st'    => 'First Semester',
    '2nd'    => 'Second Semester',
    'summer' => 'Summer',
];
$semester_reverse_map = array_flip($semester_map);

// Determine selected semester from GET param or default to system current semester
$selected_semester_label = $_GET['semester'] ?? ($semester_map[$cur_semester] ?? 'First Semester');
$selected_semester_db    = $semester_reverse_map[$selected_semester_label] ?? $cur_semester;

// Fetch enrolled subjects filtered by semester
$stmt = mysqli_prepare($con, "
    SELECT s.subject_code, s.subject_name, s.units, s.lab_hours,
           c.class_id, c.section, c.schedule_day, c.schedule_time, c.room,
           c.semester AS class_semester,
           CONCAT(f.first_name, ' ', f.last_name) AS faculty_name
    FROM enrollments e
    JOIN classes c ON e.class_id = c.class_id
    JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    WHERE e.student_id = ? AND e.status IN ('confirmed','ongoing')
      AND c.semester = ? AND c.school_year = ?
    ORDER BY s.subject_code
");
mysqli_stmt_bind_param($stmt, "iss", $user_data['student_id'], $selected_semester_db, $cur_school_year);
mysqli_stmt_execute($stmt);
$rows = mysqli_stmt_get_result($stmt);

$subjects = [];
while ($row = mysqli_fetch_assoc($rows)) {
    $subjects[] = $row;
}
mysqli_stmt_close($stmt);

$total_subjects = count($subjects);
$total_units    = array_sum(array_column($subjects, 'units'));

// Fetch curriculum URL for the student's course
$curriculum_url = '';
$course_info = get_course_info($con, $user_data['course'] ?? '');
$curriculum_url = $course_info['curriculum_url'] ?? '';

// Build scheduleData for the weekly grid
function parseDays(string $day_str): array {
    $day_str = trim($day_str);
    
    // Direct mapping for common patterns
    $direct_map = [
        'MW'   => ['M','W'],
        'MWF'  => ['M','W','F'],
        'TTH'  => ['T','TH'],
        'TH'   => ['TH'],  // Thursday alone
        'TF'   => ['T','F'],
        'MTH'  => ['M','TH'],
        'WF'   => ['W','F'],
        'MF'   => ['M','F'],
        'THS'  => ['TH','S'],  // Thursday Saturday
        'MTWTHF' => ['M','T','W','TH','F'],  // Weekdays
    ];
    
    // Single day mapping
    $single_map = [
        'Monday' => 'M', 'Mon' => 'M', 'M' => 'M',
        'Tuesday' => 'T', 'Tue' => 'T', 'T' => 'T',
        'Wednesday' => 'W', 'Wed' => 'W', 'W' => 'W',
        'Thursday' => 'TH', 'Thu' => 'TH', 'TH' => 'TH',
        'Friday' => 'F', 'Fri' => 'F', 'F' => 'F',
        'Saturday' => 'S', 'Sat' => 'S', 'S' => 'S',
        'Sunday' => 'SU', 'Sun' => 'SU', 'SU' => 'SU',
    ];
    
    // Check direct mapping first (case-insensitive)
    $day_str_upper = strtoupper($day_str);
    if (isset($direct_map[$day_str_upper])) {
        return $direct_map[$day_str_upper];
    }
    
    // Check if it's a single day
    if (isset($single_map[$day_str])) {
        return [$single_map[$day_str]];
    }
    
    // Try splitting by common delimiters (comma, slash, space)
    $parts = preg_split('/[,\/\s]+/', $day_str);
    if (count($parts) > 1) {
        $result = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if (empty($p)) continue;
            
            // Check if this part is in single_map
            if (isset($single_map[$p])) {
                $result[] = $single_map[$p];
            } else {
                // Recursively parse this part
                $sub_days = parseDays($p);
                $result = array_merge($result, $sub_days);
            }
        }
        return array_unique($result);
    }
    
    // Try to parse as concatenated abbreviations (e.g., "MWF", "THS")
    // This is a fallback for patterns not in direct_map
    $result = [];
    $i = 0;
    $len = strlen($day_str_upper);
    
    while ($i < $len) {
        // Try two-character match first (for TH, SU)
        if ($i + 1 < $len) {
            $two_char = substr($day_str_upper, $i, 2);
            if ($two_char === 'TH') {
                $result[] = 'TH';
                $i += 2;
                continue;
            }
            if ($two_char === 'SU') {
                $result[] = 'SU';
                $i += 2;
                continue;
            }
        }
        
        // Single character match
        $one_char = substr($day_str_upper, $i, 1);
        if (in_array($one_char, ['M','T','W','F','S'])) {
            // Special case: 'T' could be Tuesday or part of 'TH'
            // We already checked for 'TH' above, so this is Tuesday
            $result[] = $one_char === 'T' ? 'T' : $one_char;
            $i++;
        } else {
            // Unknown character, skip it
            $i++;
        }
    }
    
    return !empty($result) ? array_unique($result) : [$day_str];
}

function parseTime(string $time_str): array {
    $parts = preg_split('/\s*-\s*/', $time_str);
    if (count($parts) < 2) return ['start' => '08:00', 'end' => '09:00'];
    $to24 = function(string $t): string {
        $t = trim($t);
        if (preg_match('/(\d+):(\d+)\s*(AM|PM)/i', $t, $m)) {
            $h = (int)$m[1]; $min = $m[2]; $ampm = strtoupper($m[3]);
            if ($ampm === 'PM' && $h !== 12) $h += 12;
            if ($ampm === 'AM' && $h === 12) $h  =  0;
            return sprintf('%02d:%s', $h, $min);
        }
        return $t;
    };
    return ['start' => $to24($parts[0]), 'end' => $to24($parts[1])];
}

$schedule_data = [];
foreach ($subjects as $subj) {
    if (empty($subj['schedule_day']) || empty($subj['schedule_time'])) continue;
    $days  = parseDays($subj['schedule_day']);
    $time  = parseTime($subj['schedule_time']);
    $slots = [];
    foreach ($days as $d) {
        $slots[] = ['day' => $d, 'start' => $time['start'], 'end' => $time['end']];
    }
    $schedule_data[] = [
        'code'      => $subj['subject_code'],
        'name'      => $subj['subject_name'],
        'shortName' => mb_strlen($subj['subject_name']) > 18
                         ? mb_substr($subj['subject_name'], 0, 18) . '…'
                         : $subj['subject_name'],
        'room'      => $subj['room'] ?? 'TBA',
        'tag'       => ($subj['lab_hours'] > 0) ? 'Lab' : '',
        'slots'     => $slots,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/student/student_subjects.css">
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
                    <?php echo htmlspecialchars(trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? ''))); ?>
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
                        <a href="student_home.php">
                            <i class="fa-solid fa-house"></i>
                            <div class="li-name">Dashboard</div>
                        </a>
                    </li>
                    <li>
                        <a href="student_subjects.php" class="active">
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
            <!-- Schedule Navigation -->
            <div class="sched-nav">
                <div class="sched-nav-left">
                    <div class="sched-opn-title">Academic Period</div>
                    <div class="sched-stats">
                        <div class="sched-stat-chip">
                            <span class="stat-value"><?php echo $total_subjects; ?></span>
                            <span class="stat-label">Subjects</span>
                        </div>
                        <div class="sched-stat-divider"></div>
                        <div class="sched-stat-chip">
                            <span class="stat-value"><?php echo $total_units; ?></span>
                            <span class="stat-label">Units</span>
                        </div>
                        <div class="sched-stat-divider"></div>
                        <div class="sched-stat-chip">
                            <span class="stat-value"><?php echo $year_display; ?></span>
                            <span class="stat-label">Level</span>
                        </div>
                    </div>
                </div>
                <form method="GET" action="" id="schedFilterForm">
                <div class="sched-nav-options">
                    <div class="sched-label-container">
                        <label>School Year</label>
                        <span style="font-size:.9rem;font-weight:600;"><?php echo htmlspecialchars($cur_school_year); ?></span>
                    </div>
                    <div class="sched-label-container">
                        <label>Semester</label>
                        <select name="semester" id="semesterSelect" onchange="this.form.submit()">
                            <?php foreach ($semester_map as $db_val => $label): ?>
                            <option value="<?php echo htmlspecialchars($label); ?>"
                                <?php echo ($selected_semester_label === $label) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                </form>
            </div>

            <!-- Schedule Table -->
            <div class="sched-table-wrapper">
                <div class="sched-table">
                    <div class="sched-table-header">
                        <span class="col-head">#</span>
                        <span class="col-head">SUBJECT CODE</span>
                        <span class="col-head">SECTION</span>
                        <span class="col-side">SUBJECT NAME</span>
                        <span class="col-head">UNITS</span>
                        <span class="col-head">PROFESSOR</span>
                        <span class="col-head">CLASSMATES</span>
                    </div>

                    <div class="sched-table-body">
                        <?php if (empty($subjects)): ?>
                        <div class="sched-row" style="justify-content:center;color:var(--text-label);padding:2rem;">
                            No enrolled subjects found.
                        </div>
                        <?php else: ?>
                        <?php foreach ($subjects as $i => $subj): ?>
                        <div class="sched-row">
                            <span class="col-num"><?php echo $i + 1; ?></span>
                            <span class="col-container">
                                <span class="code-badge"><?php echo htmlspecialchars($subj['subject_code']); ?></span>
                            </span>
                            <span class="col-container">
                                <span class="section-badge <?php echo (stripos($subj['subject_code'], 'PATHFIT') !== false) ? 'alt' : ''; ?>">
                                    <?php echo htmlspecialchars($subj['section'] ?? 'TBA'); ?>
                                </span>
                            </span>
                            <span class="col-side">
                                <?php echo htmlspecialchars($subj['subject_name']); ?>
                                <?php if (!empty($subj['lab_hours']) && $subj['lab_hours'] > 0): ?>
                                    <span class="type-tag">Laboratory</span>
                                <?php endif; ?>
                            </span>
                            <span class="units-badge"><?php echo $subj['units']; ?></span>
                            <span class="col-prof">
                                <?php echo htmlspecialchars($subj['faculty_name'] ?? 'TBA'); ?>
                            </span>
                            <span class="col-container">
                                <button class="btn-classmates"
                                    data-class-id="<?php echo $subj['class_id']; ?>"
                                    data-subject="<?php echo htmlspecialchars($subj['subject_code'] . ' — ' . $subj['subject_name']); ?>">
                                    <i class="fa-solid fa-users"></i> View
                                </button>
                            </span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="sched-table-footer">
                        <span>Total Subjects: <strong><?php echo $total_subjects; ?></strong></span>
                        <span>Total Units: <strong><?php echo $total_units; ?></strong></span>
                    </div>
                </div>
            </div>

            <!-- Weekly Grid -->
            <div class="sched-time">
                <div class="weekly-sched-titlebar">WEEKLY SCHEDULE</div>
                <div class="weekly-sched-wrapper">
                    <div class="weekly-grid" id="weeklyGrid"></div>
                </div>
            </div>
        </main>
    </div>

    <!-- Classmates Modal -->
    <div id="classmatesModal" class="cm-overlay" style="display:none;">
        <div class="cm-box">
            <div class="cm-header">
                <div>
                    <div class="cm-title" id="cmTitle">Classmates</div>
                    <div class="cm-sub" id="cmSub"></div>
                </div>
                <button class="cm-close" id="cmClose"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="cm-body" id="cmBody">
                <div class="cm-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div id="profileModal" class="cm-overlay" style="display:none;">
        <div class="cm-box cm-profile-box">
            <div class="cm-header">
                <div class="cm-title">Student Profile</div>
                <button class="cm-close" id="pmClose"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="cm-body" id="pmBody"></div>
        </div>
    </div>

    <script src="../../js/student/student_main.js"></script>
    <script src="../../js/student/student_subjects.js"></script>
    <script>
    (function () {

        const scheduleData = <?php echo json_encode($schedule_data, JSON_UNESCAPED_UNICODE); ?>;

        /* ─── Config ──────────────────────────────────────────────── */
        const PX_PER_MIN = 1.1;

        // Guard: if no schedule data, render empty state
        if (!scheduleData.length) {
            const grid = document.getElementById('weeklyGrid');
            if (grid) {
                grid.style.cssText = 'display:flex;align-items:center;justify-content:center;padding:3rem;';
                grid.textContent   = 'No schedule data available.';
            }
            return;
        }

        const toMin = t => { const [h,m] = t.split(':').map(Number); return h*60+m; };

        const allStarts  = scheduleData.flatMap(s => s.slots.map(sl => toMin(sl.start)));
        const allEnds    = scheduleData.flatMap(s => s.slots.map(sl => toMin(sl.end)));
        const START_HOUR = Math.max(0,  Math.floor(Math.min(...allStarts) / 60) - 1);
        const END_HOUR   = Math.min(24, Math.ceil(Math.max(...allEnds)   / 60) + 1);
        const GRID_H     = (END_HOUR - START_HOUR) * 60 * PX_PER_MIN;

        const DAYS       = ['M','T','W','TH','F','S','SU'];
        const DAY_LABELS = { M:'MON', T:'TUE', W:'WED', TH:'THU', F:'FRI', S:'SAT', SU:'SUN' };

        const DEFAULT_COLOR = { bg:'#1A3A8F', fg:'#fff' };
        const COLORS = {
            // Add custom overrides by subject code here, e.g.:
            // 'PATHFIT 405': { bg:'#D4AF37', fg:'#4a2e00' },
        };

        // Auto-color PATHFIT subjects gold
        scheduleData.forEach(s => {
            if (/^PATHFIT/i.test(s.code)) COLORS[s.code] = { bg:'#D4AF37', fg:'#fff' };
        });

        /* ─── Helpers ─────────────────────────────────────────────── */
        const toTop = t      => (toMin(t) - START_HOUR*60) * PX_PER_MIN;
        const toHgt = (s,e)  => (toMin(e) - toMin(s)) * PX_PER_MIN;

        function el(tag, cls, txt) {
            const e = document.createElement(tag);
            if (cls) e.className = cls;
            if (txt !== undefined) e.textContent = txt;
            return e;
        }

        /* ─── Render ──────────────────────────────────────────────── */
        function renderGrid() {
            const grid = document.getElementById('weeklyGrid');
            if (!grid) return;

            /* Time column */
            const tcol  = el('div', 'wg-time-col');
            tcol.appendChild(el('div', 'wg-time-header'));
            const tbody = el('div');
            tbody.style.cssText = `position:relative;height:${GRID_H}px;`;

            for (let h = START_HOUR; h <= END_HOUR; h++) {
                const lbl = el('div', 'wg-time-label');
                lbl.style.top = `${(h - START_HOUR) * 60 * PX_PER_MIN}px`;
                const dh  = h > 12 ? h - 12 : (h === 0 ? 12 : h);
                lbl.textContent = `${dh}${h >= 12 ? 'PM' : 'AM'}`;
                tbody.appendChild(lbl);
            }
            tcol.appendChild(tbody);
            grid.appendChild(tcol);

            /* Day columns */
            DAYS.forEach(day => {
                const col  = el('div', 'wg-day-col');
                col.appendChild(el('div', 'wg-day-header', DAY_LABELS[day]));

                const body = el('div', 'wg-body-area');
                body.style.height = `${GRID_H}px`;

                /* Gridlines */
                for (let h = START_HOUR; h < END_HOUR; h++) {
                    const min  = (h - START_HOUR) * 60;
                    const hl   = el('div', 'wg-hour-line');
                    hl.style.top = `${min * PX_PER_MIN}px`;
                    body.appendChild(hl);

                    const half = el('div', 'wg-half-line');
                    half.style.top = `${(min + 30) * PX_PER_MIN}px`;
                    body.appendChild(half);
                }

                /* Course blocks */
                scheduleData.forEach(subj => {
                    subj.slots.forEach(slot => {
                        if (slot.day !== day) return;

                        const c    = COLORS[subj.code] || DEFAULT_COLOR;
                        const top  = toTop(slot.start);
                        const hgt  = toHgt(slot.start, slot.end);
                        const room = slot.room || subj.room;
                        const tag  = subj.tag ? ` (${subj.tag})` : '';

                        const block = el('div', 'wg-block');
                        block.style.cssText = `top:${top}px;height:${hgt-4}px;background:${c.bg};color:${c.fg};`;

                        block.appendChild(el('div', 'wg-block-code', subj.code + tag));
                        if (hgt > 34) block.appendChild(el('div', 'wg-block-name', subj.shortName || subj.name));
                        if (hgt > 58) block.appendChild(el('div', 'wg-block-room', room));

                        body.appendChild(block);
                    });
                });

                col.appendChild(body);
                grid.appendChild(col);
            });
        }

        renderGrid();
    })();
    </script>
</body>
</html>