<?php
session_start();
include("../../php/connection.php");

if (!isset($_SESSION['faculty_id'])) { header("Location: faculty_login.php"); die; }
$faculty_id = (int)$_SESSION['faculty_id'];

$faculty = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT * FROM faculty WHERE faculty_id = $faculty_id LIMIT 1"
));
if (!$faculty) { session_destroy(); header("Location: faculty_login.php"); die; }

// Distinct school years for filter
$years = [];
$yq = mysqli_query($con, "SELECT DISTINCT school_year FROM classes WHERE faculty_id = $faculty_id ORDER BY school_year DESC");
while ($r = mysqli_fetch_assoc($yq)) $years[] = $r['school_year'];

$sel_year = $_GET['year'] ?? ($years[0] ?? '');
$sel_sem  = $_GET['sem']  ?? '';

$where = "c.faculty_id = $faculty_id";
if ($sel_year) $where .= " AND c.school_year = '" . mysqli_real_escape_string($con, $sel_year) . "'";
if ($sel_sem)  $where .= " AND c.semester = '"    . mysqli_real_escape_string($con, $sel_sem)  . "'";

$classes = [];
$cq = mysqli_query($con,
    "SELECT c.class_id, c.section, c.schedule_day, c.schedule_time, c.room,
            c.semester, c.school_year,
            s.subject_code, s.subject_name, s.units
     FROM classes c
     JOIN subjects s ON c.subject_id = s.subject_id
     WHERE $where
     ORDER BY c.schedule_time, s.subject_code"
);
while ($r = mysqli_fetch_assoc($cq)) $classes[] = $r;

$total_units   = array_sum(array_column($classes, 'units'));
$total_classes = count($classes);

// TODO: Query the faculty's department from the DB and assign to $department
// $department = $faculty['department'] ?? 'N/A';

// Parse schedule_day string into grid day tokens (e.g. "TTH" ? ['T','TH'])
function parse_days_to_tokens(string $raw): array {
    $raw = strtoupper(trim($raw));
    $combos = [
        'TTH'    => ['T','TH'],
        'MWF'    => ['M','W','F'],
        'MW'     => ['M','W'],
        'TF'     => ['T','F'],
        'MTH'    => ['M','TH'],
        'WF'     => ['W','F'],
        'MTWTHF' => ['M','T','W','TH','F'],
    ];
    if (isset($combos[$raw])) return $combos[$raw];
    $map = [
        'MONDAY'=>'M','TUESDAY'=>'T','WEDNESDAY'=>'W','THURSDAY'=>'TH',
        'FRIDAY'=>'F','SATURDAY'=>'S','SUNDAY'=>'SU',
        'MON'=>'M','TUE'=>'T','WED'=>'W','THU'=>'TH','FRI'=>'F','SAT'=>'S','SUN'=>'SU',
        'M'=>'M','T'=>'T','W'=>'W','TH'=>'TH','F'=>'F','S'=>'S','SU'=>'SU',
    ];
    $parts = preg_split('/[,\/\s]+/', $raw);
    $out = [];
    foreach ($parts as $p) { $p = trim($p); if (isset($map[$p])) $out[] = $map[$p]; }
    return array_unique($out);
}

// Parse "8:00 AM - 10:00 AM" or "08:00 - 10:00" ? ['start'=>'08:00', 'end'=>'10:00']
function parse_time_range(string $raw): array {
    if (preg_match('/(\d{1,2}:\d{2})\s*(AM|PM)?\s*[--]\s*(\d{1,2}:\d{2})\s*(AM|PM)?/i', $raw, $m)) {
        $to24 = function($t, $ampm) {
            [$h,$min] = explode(':', $t);
            $h = (int)$h; $min = (int)$min;
            if ($ampm && strtoupper($ampm) === 'PM' && $h !== 12) $h += 12;
            if ($ampm && strtoupper($ampm) === 'AM' && $h === 12) $h = 0;
            return sprintf('%02d:%02d', $h, $min);
        };
        return ['start' => $to24($m[1], $m[2]), 'end' => $to24($m[3], $m[4] ?? $m[2])];
    }
    if (preg_match('/(\d{1,2}:\d{2})\s*[--]\s*(\d{1,2}:\d{2})/', $raw, $m)) {
        return ['start' => $m[1], 'end' => $m[2]];
    }
    return ['start' => '08:00', 'end' => '09:00'];
}

// Build scheduleData array for the weekly grid JS
$js_data = [];
foreach ($classes as $cls) {
    if (empty($cls['schedule_day']) || empty($cls['schedule_time'])) continue;
    $tokens = parse_days_to_tokens($cls['schedule_day']);
    $time   = parse_time_range($cls['schedule_time']);
    $slots  = array_map(fn($d) => ['day' => $d, 'start' => $time['start'], 'end' => $time['end']], $tokens);
    if (empty($slots)) continue;
    $js_data[] = [
        'code'      => $cls['subject_code'],
        'name'      => $cls['subject_name'],
        'shortName' => mb_strimwidth($cls['subject_name'], 0, 18, '-'),
        'section'   => $cls['section'],
        'room'      => $cls['room'] ?? '',
        'slots'     => $slots,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Faculty Load</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"/>
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
    <link rel="stylesheet" href="../../css/faculty/faculty_load.css" />
    <link rel="stylesheet" href="../../css/faculty/faculty_main.css" />
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
                <div class="acc-name"><?php echo htmlspecialchars($faculty['first_name'] . ' ' . $faculty['last_name']); ?></div>
                <div class="acc-img">
                    <img src="<?php echo !empty($faculty['profile_photo']) ? htmlspecialchars('../../' . $faculty['profile_photo']) : '../../uploads/default.jpg'; ?>" alt="Profile">
                </div>
            </div>
        </div>

        <nav class="main-nav" id="navMenu">
            <div class="nav-wrapper">
                <ul class="main-ul">
                    <li>
                        <a href="faculty_home.php">
                            <i class="fa-solid fa-house"></i>
                            <div class="li-name">Dashboard</div>
                        </a>
                    </li>
                    <li>
                        <a href="faculty_load.php" class="active">
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

            <!-- Load Navigation -->
            <div class="sched-nav">
                <div class="sched-nav-left">
                    <div class="sched-opn-title">Teaching Load</div>
                    <div class="sched-stats">
                        <div class="sched-stat-chip">
                            <span class="stat-value"><?php echo $total_classes; ?></span>
                            <span class="stat-label">Classes</span>
                        </div>
                        <div class="sched-stat-divider"></div>
                        <div class="sched-stat-chip">
                            <span class="stat-value"><?php echo $total_units; ?></span>
                            <span class="stat-label">Units</span>
                        </div>
                        <div class="sched-stat-divider"></div>
                        <!-- TODO: Replace hardcoded department - query faculty's department from DB into $department
                             then replace the empty echo below with: echo htmlspecialchars($department); -->
                        <div class="sched-stat-chip">
                            <span class="stat-value"><?php /* echo htmlspecialchars($department); */ ?></span>
                            <span class="stat-label">Department</span>
                        </div>
                    </div>
                </div>
                <div class="sched-nav-options">
                    <form method="GET" style="display:contents;">
                        <div class="sched-label-container">
                            <label>Year</label>
                            <select name="year" onchange="this.form.submit()">
                                <option value="">All School Years</option>
                                <?php foreach ($years as $y): ?>
                                <option value="<?php echo htmlspecialchars($y); ?>" <?php echo $y === $sel_year ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($y); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="sched-label-container">
                            <label>Semester</label>
                            <select name="sem" onchange="this.form.submit()">
                                <option value="">All Semesters</option>
                                <option value="1st"    <?php echo $sel_sem === '1st'    ? 'selected' : ''; ?>>1st Semester</option>
                                <option value="2nd"    <?php echo $sel_sem === '2nd'    ? 'selected' : ''; ?>>2nd Semester</option>
                                <option value="summer" <?php echo $sel_sem === 'summer' ? 'selected' : ''; ?>>Summer</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (empty($classes)): ?>
            <!-- Empty State -->
            <div class="sched-empty">
                <i class="fa-solid fa-calendar-xmark"></i>
                <span>No classes found for the selected period.</span>
            </div>

            <?php else: ?>
            <!-- Load Table -->
            <div class="sched-table-wrapper">
                <div class="sched-table">
                    <div class="sched-table-header">
                        <span class="col-head">#</span>
                        <span class="col-head">SUBJECT CODE</span>
                        <span class="col-head">SECTION</span>
                        <span class="col-side">SUBJECT NAME</span>
                        <span class="col-head">SCHEDULE</span>
                        <span class="col-head">ROOM</span>
                        <span class="col-head">UNITS</span>
                    </div>

                    <div class="sched-table-body">
                        <?php foreach ($classes as $i => $cls): ?>
                        <div class="sched-row">
                            <span class="col-num"><?php echo $i + 1; ?></span>
                            <span class="col-container">
                                <span class="code-badge"><?php echo htmlspecialchars($cls['subject_code']); ?></span>
                            </span>
                            <span class="col-container">
                                <span class="section-badge"><?php echo htmlspecialchars($cls['section']); ?></span>
                            </span>
                            <span class="col-side"><?php echo htmlspecialchars($cls['subject_name']); ?>
                                <!-- TODO: subject type (Lecture/Laboratory) not in current DB query.
                                     Add 'class_type' to the SELECT and render:
                                     <span class="type-tag">echo htmlspecialchars($cls['class_type']);</span> -->
                            </span>
                            <span class="col-sched">
                                <?php if ($cls['schedule_day'] || $cls['schedule_time']): ?>
                                    <?php echo htmlspecialchars(trim($cls['schedule_day'] . ' ' . $cls['schedule_time'])); ?>
                                <?php else: ?>
                                    <span style="color:var(--text);opacity:0.4;">TBA</span>
                                <?php endif; ?>
                            </span>
                            <span class="col-room"><?php echo htmlspecialchars($cls['room'] ?: '-'); ?></span>
                            <span class="col-container">
                                <span class="units-badge"><?php echo htmlspecialchars($cls['units']); ?></span>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="sched-table-footer">
                        <span>Total Classes: <strong><?php echo $total_classes; ?></strong></span>
                        <span>Total Units: <strong><?php echo $total_units; ?></strong></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($js_data)): ?>
            <!-- Weekly Grid -->
            <div class="sched-time">
                <div class="weekly-sched-titlebar">WEEKLY SCHEDULE</div>
                <div class="weekly-sched-wrapper">
                    <div class="weekly-grid" id="weeklyGrid"></div>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

        </main>
    </div>

    <script src="../../js/faculty/faculty_main.js"></script>
    <script>
    (function () {

        const scheduleData = <?php echo json_encode($js_data, JSON_UNESCAPED_UNICODE); ?>;

        /* --- Config --------------------------------------------------- */
        const PX_PER_MIN = 1.1;

        if (!scheduleData.length) return;

        const toMin = t => { const [h, m] = t.split(':').map(Number); return h * 60 + m; };

        const allStarts  = scheduleData.flatMap(s => s.slots.map(sl => toMin(sl.start)));
        const allEnds    = scheduleData.flatMap(s => s.slots.map(sl => toMin(sl.end)));
        const START_HOUR = Math.max(0,  Math.floor(Math.min(...allStarts) / 60) - 1);
        const END_HOUR   = Math.min(24, Math.ceil(Math.max(...allEnds)   / 60) + 1);
        const GRID_H     = (END_HOUR - START_HOUR) * 60 * PX_PER_MIN;

        const DAYS       = ['M','T','W','TH','F','S','SU'];
        const DAY_LABELS = { M:'MON', T:'TUE', W:'WED', TH:'THU', F:'FRI', S:'SAT', SU:'SUN' };
        const COLORS     = ['#8C1C24','#0B1F5B','#1a6b3c','#8a4f00','#5a1018','#1a3a8f'];

        const toTop = t      => (toMin(t) - START_HOUR * 60) * PX_PER_MIN;
        const toHgt = (s, e) => (toMin(e) - toMin(s)) * PX_PER_MIN;

        function el(tag, cls, txt) {
            const e = document.createElement(tag);
            if (cls) e.className = cls;
            if (txt !== undefined) e.textContent = txt;
            return e;
        }

        // Assign a stable colour per subject code
        const colorMap = {};
        let ci = 0;
        scheduleData.forEach(s => { if (!colorMap[s.code]) colorMap[s.code] = COLORS[ci++ % COLORS.length]; });

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

                        const top  = toTop(slot.start);
                        const hgt  = toHgt(slot.start, slot.end);
                        const bg   = colorMap[subj.code];

                        const block = el('div', 'wg-block');
                        block.style.cssText = `top:${top}px;height:${hgt - 4}px;background:${bg};color:#fff;`;

                        block.appendChild(el('div', 'wg-block-code', subj.code));
                        if (hgt > 34) block.appendChild(el('div', 'wg-block-name', subj.shortName || subj.name));
                        if (hgt > 58) block.appendChild(el('div', 'wg-block-room', subj.room || subj.section));

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
