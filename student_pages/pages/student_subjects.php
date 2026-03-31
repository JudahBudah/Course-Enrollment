<?php
session_start();
include("../../php/connection.php");
include("../../php/functions.php");

$user_data = check_login($con);

$profile_src = !empty($user_data['profile_photo'])
    ? '../../' . $user_data['profile_photo']
    : '../../assets/test/student-profile.webp';

$program = htmlspecialchars($user_data['course'] ?? 'N/A');

function formatYear($year) {
    return match($year) {
        1 => "1st Year", 2 => "2nd Year", 3 => "3rd Year", 4 => "4th Year",
        default => "N/A",
    };
}
$year_display = formatYear($user_data['year_level'] ?? null);

// Fetch enrolled subjects from DB
$stmt = mysqli_prepare($con, "
    SELECT s.subject_code, s.subject_name, s.units, s.lab_hours,
           c.section, c.schedule_day, c.schedule_time, c.room,
           CONCAT(f.first_name, ' ', f.last_name) AS faculty_name
    FROM enrollments e
    JOIN classes c ON e.class_id = c.class_id
    JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    WHERE e.student_id = ? AND e.status IN ('confirmed','ongoing')
    ORDER BY s.subject_code
");
mysqli_stmt_bind_param($stmt, "i", $user_data['student_id']);
mysqli_stmt_execute($stmt);
$rows = mysqli_stmt_get_result($stmt);

$subjects = [];
while ($row = mysqli_fetch_assoc($rows)) {
    $subjects[] = $row;
}
mysqli_stmt_close($stmt);

$total_subjects = count($subjects);
$total_units    = array_sum(array_column($subjects, 'units'));

// Build scheduleData for the weekly grid
function parseDays(string $day_str): array {
    $day_str = trim($day_str);
    $map = [
        'Monday'    => 'M',  'Tuesday' => 'T',  'Wednesday' => 'W',
        'Thursday'  => 'TH', 'Friday'  => 'F',  'Saturday'  => 'S', 'Sunday' => 'SU',
        'Mon' => 'M', 'Tue' => 'T', 'Wed' => 'W', 'Thu' => 'TH', 'Fri' => 'F', 'Sat' => 'S',
        'MW'  => ['M','W'],  'TTH' => ['T','TH'], 'MWF' => ['M','W','F'],
        'TF'  => ['T','F'],  'MTH' => ['M','TH'],
    ];
    if (isset($map[$day_str])) {
        return is_array($map[$day_str]) ? $map[$day_str] : [$map[$day_str]];
    }
    $parts  = preg_split('/[,\/]/', $day_str);
    $result = [];
    foreach ($parts as $p) {
        $p        = trim($p);
        $result[] = $map[$p] ?? $p;
    }
    return $result;
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
                <div class="sched-nav-options">
                    <div class="sched-label-container">
                        <label>Year</label>
                        <select name="sched-year">
                            <option value="2025 - 2026">2025 - 2026</option>
                        </select>
                    </div>
                    <div class="sched-label-container">
                        <label>Semester</label>
                        <select name="admission_status">
                            <option value="First Semester">First Semester</option>
                            <option value="Second Semester">Second Semester</option>
                        </select>
                    </div>
                </div>
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

    <script src="../../js/student/student_main.js"></script>
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

        const allStarts  = scheduleData.flatMap(s => s.slots.map(sl => parseInt(sl.start)));
        const allEnds    = scheduleData.flatMap(s => s.slots.map(sl => Math.ceil(parseInt(sl.end))));
        const START_HOUR = Math.max(0,  Math.min(...allStarts) - 1);
        const END_HOUR   = Math.min(24, Math.max(...allEnds)   + 1);
        const GRID_H     = (END_HOUR - START_HOUR) * 60 * PX_PER_MIN;

        const DAYS       = ['M','T','W','TH','F','S'];
        const DAY_LABELS = { M:'MON', T:'TUE', W:'WED', TH:'THU', F:'FRI', S:'SAT' };

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
        const toMin = t      => { const [h,m] = t.split(':').map(Number); return h*60+m; };
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