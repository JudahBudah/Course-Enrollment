<?php
session_start();
include("../../php/connection.php");

if (!isset($_SESSION['faculty_id'])) { header("Location: faculty_login.php"); die; }
$faculty_id = (int)$_SESSION['faculty_id'];

$faculty = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT * FROM faculty WHERE faculty_id = $faculty_id LIMIT 1"
));
if (!$faculty) { session_destroy(); header("Location: faculty_login.php"); die; }

// Ensure grade_entries table exists
mysqli_query($con, "CREATE TABLE IF NOT EXISTS grade_entries (
    entry_id       INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id  INT NOT NULL,
    class_id       INT NOT NULL,
    student_id     INT NOT NULL,
    class_standing DECIMAL(5,2) DEFAULT NULL,
    quiz           DECIMAL(5,2) DEFAULT NULL,
    midterms       DECIMAL(5,2) DEFAULT NULL,
    finals         DECIMAL(5,2) DEFAULT NULL,
    computed_grade DECIMAL(5,2) GENERATED ALWAYS AS (
        ROUND(
            COALESCE(class_standing,0)*0.30 +
            COALESCE(quiz,0)*0.30 +
            COALESCE(midterms,0)*0.20 +
            COALESCE(finals,0)*0.20
        , 2)
    ) STORED,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_enroll (enrollment_id)
)");

// Get classes assigned to this faculty - split current vs past
$current_classes = [];
$past_classes    = [];
$cq = mysqli_query($con,
    "SELECT c.class_id, c.section, c.semester, c.school_year, c.grades_finalized,
            s.subject_code, s.subject_name
     FROM classes c
     JOIN subjects s ON c.subject_id = s.subject_id
     WHERE c.faculty_id = $faculty_id
     ORDER BY c.grades_finalized ASC, s.subject_code, c.section"
);
while ($r = mysqli_fetch_assoc($cq)) {
    if ((int)$r['grades_finalized'] === 1) $past_classes[] = $r;
    else $current_classes[] = $r;
}
$classes = array_merge($current_classes, $past_classes);

$view_mode = $_GET['view'] ?? 'current';
$display_classes = $view_mode === 'past' ? $past_classes : $current_classes;

// Selected class
$selected_class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : ($display_classes[0]['class_id'] ?? 0);
$selected_class = null;
foreach ($classes as $c) {
    if ($c['class_id'] == $selected_class_id) { $selected_class = $c; break; }
}

// Students + grade entries for selected class
$students  = [];
$entries   = [];
$finalized = false;
if ($selected_class_id) {
    $sq = mysqli_query($con,
        "SELECT e.enrollment_id, e.student_id,
                s.student_number, s.last_name, s.first_name, s.middle_name
         FROM enrollments e
         JOIN students s ON e.student_id = s.student_id
         WHERE e.class_id = $selected_class_id
           AND e.status IN ('ongoing','confirmed')
           AND e.enrollment_id = (
               SELECT MAX(e2.enrollment_id) FROM enrollments e2
               WHERE e2.student_id = e.student_id
                 AND e2.class_id = $selected_class_id
                 AND e2.status IN ('ongoing','confirmed')
           )
         ORDER BY s.last_name, s.first_name"
    );
    while ($r = mysqli_fetch_assoc($sq)) $students[] = $r;

    $eq = mysqli_query($con,
        "SELECT * FROM grade_entries WHERE class_id = $selected_class_id"
    );
    while ($r = mysqli_fetch_assoc($eq)) $entries[$r['enrollment_id']] = $r;

    $cls_row   = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT grades_finalized, grades_finalized_at FROM classes WHERE class_id = $selected_class_id"
    ));
    $finalized = $cls_row && (int)$cls_row['grades_finalized'] === 1;
    $finalized_at = $cls_row['grades_finalized_at'] ?? null;
}

// Transmutation helpers (PHP side for initial render)
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
function remark(string $p): string {
    if ($p === '5.00') return 'failed';
    if ($p === '4.00') return 'conditional';
    return 'passed';
}
function remarkLabel(string $r): string {
    return match($r) { 'passed' => 'Passed', 'failed' => 'Failed', 'conditional' => 'Conditional', default => 'Pending' };
}
function remarkIcon(string $r): string {
    return match($r) { 'passed' => 'fa-circle-check', 'failed' => 'fa-circle-xmark', 'conditional' => 'fa-circle-half-stroke', default => 'fa-clock' };
}

$passed_count = count(array_filter($entries, fn($e) => $e['computed_grade'] !== null && transmute((float)$e['computed_grade']) >= 73));
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Spreadsheet</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"/>
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
    <link rel="stylesheet" href="../../css/faculty/faculty_tables.css" />
    <link rel="stylesheet" href="../../css/faculty/faculty_main.css" />
  </head>
  <body>
    <header>
        <div class="nav-section">
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
                        <a href="faculty_spreadsheet.php" class="active">
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

        <!-- Current / Past toggle -->
        <div style="display:flex;gap:.5rem;margin-bottom:1rem;">
            <a href="?view=current" class="sched-toggle-btn <?php echo $view_mode==='current'?'active':''; ?>" style="text-decoration:none;padding:.4rem .9rem;border-radius:6px;font-size:.85rem;border:1px solid var(--off);">
                <i class="fa-solid fa-chalkboard"></i> Current Classes
                <?php if (count($current_classes)): ?><span style="background:var(--maroon);color:#fff;border-radius:10px;padding:0 6px;font-size:.75rem;margin-left:4px;"><?php echo count($current_classes); ?></span><?php endif; ?>
            </a>
            <a href="?view=past" class="sched-toggle-btn <?php echo $view_mode==='past'?'active':''; ?>" style="text-decoration:none;padding:.4rem .9rem;border-radius:6px;font-size:.85rem;border:1px solid var(--off);">
                <i class="fa-solid fa-clock-rotate-left"></i> Past Classes
                <?php if (count($past_classes)): ?><span style="background:var(--navy);color:#fff;border-radius:10px;padding:0 6px;font-size:.75rem;margin-left:4px;"><?php echo count($past_classes); ?></span><?php endif; ?>
            </a>
        </div>

        <!-- Class Selector Card -->
        <div class="class-nav">
            <div class="class-nav-left">
                <div class="class-nav-title">Spreadsheet <?php if($view_mode==='past'): ?><span style="font-size:.75rem;color:var(--text-label);font-weight:400;"> - Past / Finalized</span><?php endif; ?></div>
                <?php if ($selected_class): ?>
                <div class="class-meta">
                    <span class="class-meta-badge code" id="metaCode"><?php echo htmlspecialchars($selected_class['subject_code']); ?></span>
                    <span class="class-meta-sep"><i class="fa-solid fa-chevron-right"></i></span>
                    <span class="class-meta-badge section" id="metaSection"><?php echo htmlspecialchars($selected_class['section']); ?></span>
                    <span class="class-meta-sep"><i class="fa-solid fa-chevron-right"></i></span>
                    <span class="class-meta-name" id="metaName"><?php echo htmlspecialchars($selected_class['subject_name']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <div class="class-nav-right">
                <form method="GET" id="classSelectForm">
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">
                    <div class="sched-label-container">
                        <label>Class</label>
                        <select name="class_id" id="classSelect" onchange="this.form.submit()">
                            <?php if (empty($display_classes)): ?>
                                <option>No classes found</option>
                            <?php else: ?>
                                <?php foreach ($display_classes as $c): ?>
                                <option value="<?php echo $c['class_id']; ?>"
                                    data-code="<?php echo htmlspecialchars($c['subject_code']); ?>"
                                    data-section="<?php echo htmlspecialchars($c['section']); ?>"
                                    data-name="<?php echo htmlspecialchars($c['subject_name']); ?>"
                                    <?php echo $c['class_id'] == $selected_class_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['subject_code'] . ' - ' . $c['section'] . ' (' . $c['semester'] . ' ' . $c['school_year'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!$selected_class || empty($students)): ?>
        <!-- Empty State -->
        <div class="sched-empty">
            <i class="fa-solid fa-table"></i>
            <span><?php echo empty($display_classes) ? 'No ' . ($view_mode==='past'?'past':'current') . ' classes found.' : 'No enrolled students in this class.'; ?></span>
        </div>

        <?php else: ?>

        <!-- Toolbar: Legend + Export -->
        <div class="ss-toolbar">
            <div class="ss-legend">
                <span><i class="fa-solid fa-circle-check" style="color:var(--passed-green);"></i> Passed (1.00&ndash;3.00)</span>
                <span><i class="fa-solid fa-circle-half-stroke" style="color:var(--perm-navy);"></i> Conditional (4.00)</span>
                <span><i class="fa-solid fa-circle-xmark" style="color:var(--red);"></i> Failed (5.00)</span>
                <?php if (!$finalized): ?>
                <span class="ss-legend-hint">Click any grade cell to edit &mdash; Tab / Enter to move &mdash; Values 0&ndash;100</span>
                <?php endif; ?>
            </div>
            <div style="display:flex;gap:0.6rem;align-items:center;">
                <?php if ($finalized): ?>
                    <span style="display:flex;align-items:center;gap:0.4rem;color:var(--passed-green);font-weight:600;font-size:0.85rem;">
                        <i class="fa-solid fa-lock"></i> Grades Finalized
                        <?php if ($finalized_at): ?>
                            <span style="font-weight:400;color:var(--text-label);">(<?php echo date('M d, Y', strtotime($finalized_at)); ?>)</span>
                        <?php endif; ?>
                    </span>
                <?php else: ?>
                    <button class="btn-finalize" id="btnFinalize" onclick="confirmFinalize()">
                        <i class="fa-solid fa-lock"></i> Finalize Grades
                    </button>
                <?php endif; ?>
                <button class="btn-export" onclick="exportCSV()">
                    <i class="fa-solid fa-file-csv"></i> Export CSV
                </button>
            </div>
        </div>

        <!-- Spreadsheet Table -->
        <div class="ss-table-wrapper">
            <div class="ss-table">
                <div class="ss-table-header">
                    <span class="col-head">#</span>
                    <span class="col-head col-align-left">FULL NAME</span>
                    <span class="col-head">CLASS STANDING<br><small>(30%)</small></span>
                    <span class="col-head">QUIZ<br><small>(30%)</small></span>
                    <span class="col-head">MIDTERMS<br><small>(20%)</small></span>
                    <span class="col-head">FINALS<br><small>(20%)</small></span>
                    <span class="col-head">TRANSMUTED<br><small>GRADE</small></span>
                    <span class="col-head">POINT<br><small>GRADE</small></span>
                    <span class="col-head">REMARKS</span>
                </div>

                <div class="ss-table-body" id="ssBody">
                    <?php foreach ($students as $idx => $st):
                        $eid  = $st['enrollment_id'];
                        $ge   = $entries[$eid] ?? [];
                        $cs   = $ge['class_standing'] ?? null;
                        $qz   = $ge['quiz']           ?? null;
                        $mt   = $ge['midterms']       ?? null;
                        $fn   = $ge['finals']         ?? null;
                        $cg   = $ge['computed_grade'] ?? null;
                        $tg   = $cg !== null ? transmute((float)$cg) : null;
                        $pg   = $tg !== null ? pointGrade($tg)       : null;
                        $rm   = $pg !== null ? remark($pg)           : 'pending';
                        $rmLbl = $pg !== null ? remarkLabel($rm)     : 'Pending';
                        $rmIco = remarkIcon($rm);
                        $fullname = htmlspecialchars($st['last_name'] . ', ' . $st['first_name'] . ($st['middle_name'] ? ' ' . $st['middle_name'][0] . '.' : ''));
                    ?>
                    <div class="ss-row"
                         data-enrollment="<?php echo $eid; ?>"
                         data-student="<?php echo $st['student_id']; ?>"
                         data-class="<?php echo $selected_class_id; ?>">
                        <span class="col-num"><?php echo $idx + 1; ?></span>
                        <span class="col-side"><?php echo $fullname; ?></span>

                        <?php foreach (['class_standing' => $cs, 'quiz' => $qz, 'midterms' => $mt, 'finals' => $fn] as $field => $val): ?>
                        <span class="col-grade">
                            <input type="number" class="cell-input"
                                   data-field="<?php echo $field; ?>"
                                   value="<?php echo $val !== null ? htmlspecialchars($val) : ''; ?>"
                                   placeholder="-" min="1" max="100" step="0.01"
                                   title="<?php echo ucwords(str_replace('_', ' ', $field)); ?> (0-100)"
                                   <?php echo $finalized ? 'disabled' : ''; ?>>
                        </span>
                        <?php endforeach; ?>

                        <span class="col-tg col-computed" data-col="tg"><?php echo $tg ?? '-'; ?></span>
                        <span class="col-fg col-computed" data-col="fg"><?php echo $pg ?? '-'; ?></span>
                        <span class="col-remark <?php echo $rm; ?>" data-col="remark">
                            <i class="fa-solid <?php echo $rmIco; ?>"></i>
                            <?php echo $rmLbl; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="table-footer">
                    <span><?php echo count($students); ?> student<?php echo count($students) !== 1 ? 's' : ''; ?></span>
                    <span><?php echo $passed_count; ?> passed</span>
                </div>
            </div>
        </div>

        <?php endif; ?>

    </main>
    </div>

    <!-- Save toast -->
    <div class="save-indicator" id="saveIndicator">Saved</div>

    <script src="../../js/faculty/faculty_main.js"></script>
    <script>
        // -- Meta badge updater (instant feedback before form submits) --------
        const classSelect = document.getElementById('classSelect');
        if (classSelect) {
            classSelect.addEventListener('change', function () {
                const opt = this.options[this.selectedIndex];
                const metaCode    = document.getElementById('metaCode');
                const metaSection = document.getElementById('metaSection');
                const metaName    = document.getElementById('metaName');
                if (metaCode)    metaCode.textContent    = opt.dataset.code    ?? '';
                if (metaSection) metaSection.textContent = opt.dataset.section ?? '';
                if (metaName)    metaName.textContent    = opt.dataset.name    ?? '';
            });
        }

        // -- Transmutation (mirrors PHP) --------------------------------------
        const HANDLER   = '../../php/faculty_grades_handler.php';
        const CLASS_ID  = <?php echo $selected_class_id; ?>;
        const FINALIZED = <?php echo $finalized ? 'true' : 'false'; ?>;
        let saveTimer = null;

        function transmute(g) {
            if(g>=97)return 99;if(g>=94)return 96;if(g>=91)return 93;
            if(g>=88)return 90;if(g>=85)return 87;if(g>=82)return 84;
            if(g>=79)return 81;if(g>=76)return 78;if(g>=73)return 75;
            if(g>=70)return 72;if(g>=67)return 69;if(g>=64)return 66;
            if(g>=61)return 63;if(g>=55)return 60;return 55;
        }
        function pointGrade(t) {
            if(t>=97)return'1.00';if(t>=94)return'1.25';if(t>=91)return'1.50';
            if(t>=88)return'1.75';if(t>=85)return'2.00';if(t>=82)return'2.25';
            if(t>=79)return'2.50';if(t>=76)return'2.75';if(t>=73)return'3.00';
            if(t>=70)return'4.00';return'5.00';
        }
        function remark(p) {
            if(p==='5.00') return 'failed';
            if(p==='4.00') return 'conditional';
            return 'passed';
        }
        const remarkLabel = { passed:'Passed', failed:'Failed', conditional:'Conditional', pending:'Pending' };
        const remarkIcon  = { passed:'fa-circle-check', failed:'fa-circle-xmark', conditional:'fa-circle-half-stroke', pending:'fa-clock' };

        // -- Update computed columns in the row -------------------------------
        function updateRowComputed(row) {
            const inputs = row.querySelectorAll('.cell-input');
            const vals = {};
            inputs.forEach(i => { vals[i.dataset.field] = i.value !== '' ? parseFloat(i.value) : null; });

            const allNull = Object.values(vals).every(v => v === null);
            const tgEl = row.querySelector('[data-col="tg"]');
            const fgEl = row.querySelector('[data-col="fg"]');
            const rmEl = row.querySelector('[data-col="remark"]');

            if (allNull) {
                tgEl.textContent = '-';
                fgEl.textContent = '-';
                rmEl.className = 'col-remark pending';
                rmEl.innerHTML = '<i class="fa-solid fa-clock"></i> Pending';
                return;
            }

            const weights = { class_standing: 0.30, quiz: 0.30, midterms: 0.20, finals: 0.20 };
            let weightedSum = 0;
            for (const [field, w] of Object.entries(weights)) {
                weightedSum += (vals[field] ?? 0) * w;
            }
            const computed = Math.round(weightedSum * 100) / 100;
            const tg = transmute(computed);
            const pg = pointGrade(tg);
            const rm = remark(pg);

            tgEl.textContent = tg;
            fgEl.textContent = pg;
            rmEl.className   = 'col-remark ' + rm;
            rmEl.innerHTML   = `<i class="fa-solid ${remarkIcon[rm]}"></i> ${remarkLabel[rm]}`;
        }

        // -- Save toast -------------------------------------------------------
        function showSaved(ok = true) {
            const el = document.getElementById('saveIndicator');
            el.textContent = ok ? 'Saved' : 'Error saving';
            el.className   = 'save-indicator show' + (ok ? '' : ' error');
            clearTimeout(saveTimer);
            saveTimer = setTimeout(() => el.classList.remove('show'), 1800);
        }

        // -- Finalize grades ---------------------------------------------------
        function confirmFinalize() {
            if (!confirm('Finalize grades for this class?\n\nThis is permanent and cannot be undone. Students will see their final grades and no further edits will be allowed.')) return;
            const fd = new FormData();
            fd.append('action',   'finalize_class');
            fd.append('class_id', CLASS_ID);
            fetch(HANDLER, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.ok) location.reload();
                    else alert(d.msg || 'Failed to finalize grades.');
                })
                .catch(() => alert('Error finalizing grades.'));
        }

        // -- Save cell to server ----------------------------------------------
        function saveCell(input, row) {
            const fd  = new FormData();
            fd.append('action',        'save_cell');
            fd.append('enrollment_id', row.dataset.enrollment);
            fd.append('student_id',    row.dataset.student);
            fd.append('class_id',      row.dataset.class);
            fd.append('field',         input.dataset.field);
            fd.append('value',         input.value);

            fetch(HANDLER, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    showSaved(d.ok);
                    if (!d.ok) return;
                    const tgEl = row.querySelector('[data-col="tg"]');
                    const fgEl = row.querySelector('[data-col="fg"]');
                    const rmEl = row.querySelector('[data-col="remark"]');
                    if (d.transmuted !== null && d.transmuted !== undefined) {
                        tgEl.textContent = d.transmuted;
                        fgEl.textContent = d.point;
                        rmEl.className   = 'col-remark ' + d.remark;
                        rmEl.innerHTML   = `<i class="fa-solid ${remarkIcon[d.remark]}"></i> ${remarkLabel[d.remark]}`;
                    } else {
                        tgEl.textContent = '-';
                        fgEl.textContent = '-';
                        rmEl.className   = 'col-remark pending';
                        rmEl.innerHTML   = '<i class="fa-solid fa-clock"></i> Pending';
                    }
                })
                .catch(() => showSaved(false));
        }

        // -- Keyboard navigation ----------------------------------------------
        const allInputs = () => [...document.querySelectorAll('.cell-input')];

        function moveFocus(current, dir) {
            const inputs = allInputs();
            const idx  = inputs.indexOf(current);
            const next = inputs[idx + dir];
            if (next) { next.focus(); next.select(); }
        }

        // -- Wire up all inputs -----------------------------------------------
        document.querySelectorAll('.cell-input').forEach(input => {
            // Live compute on every keystroke
            input.addEventListener('input', () => updateRowComputed(input.closest('.ss-row')));

            // Save on blur
            input.addEventListener('blur', () => saveCell(input, input.closest('.ss-row')));

            // Keyboard nav
            input.addEventListener('keydown', e => {
                const COLS = 4; // class_standing, quiz, midterms, finals
                if (e.key === 'Tab') {
                    e.preventDefault();
                    moveFocus(input, e.shiftKey ? -1 : 1);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    moveFocus(input, COLS);
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault(); moveFocus(input, COLS);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault(); moveFocus(input, -COLS);
                } else if (e.key === 'ArrowRight' && input.selectionStart === input.value.length) {
                    moveFocus(input, 1);
                } else if (e.key === 'ArrowLeft' && input.selectionStart === 0) {
                    moveFocus(input, -1);
                }
            });

            // Select all on focus
            input.addEventListener('focus', () => input.select());
        });

        // -- CSV Export -------------------------------------------------------
        function exportCSV() {
            const rows = document.querySelectorAll('.ss-row');
            const lines = [['#', 'Full Name', 'Class Standing', 'Quiz', 'Midterms', 'Finals', 'Transmuted Grade', 'Point Grade', 'Remarks']];
            rows.forEach((row, i) => {
                const inputs = row.querySelectorAll('.cell-input');
                const vals   = [...inputs].map(inp => inp.value || '');
                lines.push([
                    i + 1,
                    row.querySelector('.col-side').textContent.trim(),
                    ...vals,
                    row.querySelector('[data-col="tg"]').textContent.trim(),
                    row.querySelector('[data-col="fg"]').textContent.trim(),
                    row.querySelector('[data-col="remark"]').textContent.trim(),
                ]);
            });
            const csv = lines.map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\n');
            const a   = document.createElement('a');
            a.href     = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
            a.download = 'grades_<?php echo $selected_class_id; ?>.csv';
            a.click();
        }
    </script>
  </body>
</html>

