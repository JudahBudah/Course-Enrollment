<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status='pending'"))['c'];

$flash = '';
if (isset($_GET['success'])) {
    $count   = (int)($_GET['count']   ?? 0);
    $skipped = (int)($_GET['skipped'] ?? 0);
    $msg = 'Successfully created ' . $count . ' subject' . ($count !== 1 ? 's' : '') . '!';
    if ($skipped > 0) $msg .= ' (' . $skipped . ' skipped as duplicates)';
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> ' . $msg . '</div>';
}
if (isset($_GET['error'])) {
    $msgs = [
        'no_data'        => 'No subjects data provided.',
        'invalid_format' => 'Invalid data format. Please check your input.',
        'import_failed'  => 'Failed to create subjects. Please try again.',
        'all_skipped'    => 'All ' . (int)($_GET['skipped'] ?? 0) . ' subjects were skipped as duplicates.',
        'csv_invalid'    => 'Invalid CSV file. Please upload a valid .csv file.',
    ];
    $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> ' . ($msgs[$_GET['error']] ?? 'An error occurred.') . '</div>';
}

$active_tab = $_GET['tab'] ?? 'paste';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Create Subject - PLM Admin</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
    <link rel="stylesheet" href="../../css/admin/admin_subjects_batch_import.css">
</head>
<body>

    <header>
        <div class="nav-section">
            <button class="nav-button" id="navButton">
                <i class="fa-solid fa-bars" id="trans-bars"></i>
            </button>
            <div class="logo-container">
                <img src="../../assets/plm-logo.png" alt="PLM Logo" loading="lazy">
                <div class="title-container">
                    <div class="logo-title">PAMANTASAN NG LUNGSOD NG MAYNILA</div>
                    <div class="logo-sub">University of the City of Manila</div>
                </div>
            </div>
            <div class="acc-display-container">
                <div class="acc-name"><?php echo htmlspecialchars($admin_data['username'] ?? 'Admin'); ?></div>
                <div class="user-avatar"><?php echo strtoupper(substr($admin_data['username'] ?? 'A', 0, 1)); ?></div>
            </div>
        </div>

        <nav class="main-nav" id="navMenu">
            <div class="nav-wrapper">
                <ul class="main-ul">
                    <li><a href="admin_home.php"><i class="fa-solid fa-house"></i><span class="li-name">Dashboard</span></a></li>
                    <li>
                        <a href="admin_applicants.php"><i class="fa-solid fa-user-plus"></i><span class="li-name">Applicants</span>
                        <?php if ($pending_applicants > 0): ?><span class="sidebar-badge li-name"><?php echo $pending_applicants; ?></span><?php endif; ?>
                        </a>
                    </li>
                    <li class="course-dropdown">
                        <a href="#" id="student-records-dropdown"><i class="fa-solid fa-user-graduate"></i><span class="li-name chev-space">Student Records <i class="fa-solid fa-chevron-down"></i></span></a>
                        <div class="acad-dropdown-menu" id="student-records-menu"><ul>
                            <li><a href="admin_students.php">Students</a></li>
                            <li><a href="admin_enrollments.php">Enrollments</a></li>
                            <li><a href="admin_drop_requests.php">Drop Requests<?php if (!empty($GLOBALS['pending_drops'])): ?><span class="sidebar-badge"><?php echo $GLOBALS['pending_drops']; ?></span><?php endif; ?></a></li>
                        </ul></div>
                    </li>
                    <li class="course-dropdown">
                        <a href="#" id="acad-records-dropdown"><i class="fa-solid fa-graduation-cap"></i><span class="li-name chev-space">Academic Records <i class="fa-solid fa-chevron-down"></i></span></a>
                        <div class="acad-dropdown-menu" id="acad-records-menu"><ul>
                            <li><a href="admin_subjects.php">Subjects</a></li>
                            <li><a href="admin_classes.php">Classes</a></li>
                            <li><a href="admin_blocks.php">Blocks</a></li>
                        </ul></div>
                    </li>
                    <li class="course-dropdown">
                        <a href="#" id="personnel-dropdown"><i class="fa-solid fa-users-gear"></i><span class="li-name chev-space">Personnel <i class="fa-solid fa-chevron-down"></i></span></a>
                        <div class="acad-dropdown-menu" id="personnel-menu"><ul>
                            <li><a href="admin_faculty.php">Faculty</a></li>
                            <?php if (($admin_data['role'] ?? 'admin') === 'superadmin'): ?><li><a href="admin_accounts.php">Admin Accounts</a></li><?php endif; ?>
                        </ul></div>
                    </li>
                    <li class="course-dropdown">
                        <a href="#" id="comms-dropdown"><i class="fa-solid fa-bullhorn"></i><span class="li-name chev-space">Communications <i class="fa-solid fa-chevron-down"></i></span></a>
                        <div class="acad-dropdown-menu" id="comms-menu"><ul>
                            <li><a href="admin_announcements.php">Announcements</a></li>
                            <li><a href="admin_calendar.php">Calendar</a></li>
                        </ul></div>
                    </li>
                    <?php if (($admin_data['role'] ?? '') === 'superadmin'): ?><li><a href="admin_settings.php" class="superadmin-link"><i class="fa-solid fa-sliders"></i><span class="li-name">System Settings</span></a></li><?php endif; ?>
                <li><a href="../../php/admin_logout.php" class="logout-bg"><i class="fa-solid fa-right-from-bracket"></i><span class="li-name">Logout</span></a></li>
                </ul>
            </div>
            <div class="drk-mode-container">
                <div class="drk-label"><i class="fa-solid fa-moon" id="modeIcon"></i><span class="li-name" id="modeLabel">Dark Mode</span></div>
                <div class="toggle-track li-name" id="toggleTrack"><div class="toggle-thumb"></div></div>
            </div>
        </nav>
    </header>

    <div class="main-flex">
        <div class="spacer"></div>
        <main>
            <div class="main-content">

                <div class="page-header">
                    <div>
                        <h1>Batch Create Subject</h1>
                        <p>Create multiple subjects by pasting data or uploading a CSV file</p>
                    </div>
                    <div style="display:flex;gap:.75rem;align-items:center;">
                        <a href="../../php/admin_subjects_batch_import.php?download_template=1" class="btn-secondary">
                            <i class="fa-solid fa-download"></i>
                            <span class="li-name">CSV Template</span>
                        </a>
                        <a href="admin_subjects.php" class="btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i>
                            <span class="li-name">Back to Subjects</span>
                        </a>
                    </div>
                </div>

                <?php echo $flash; ?>

                <!-- Tabs -->
                <div class="batch-tabs">
                    <button class="batch-tab <?php echo $active_tab === 'paste' ? 'active' : ''; ?>" onclick="switchTab('paste')">
                        <i class="fa-solid fa-paste"></i> Paste Data
                    </button>
                    <button class="batch-tab <?php echo $active_tab === 'csv' ? 'active' : ''; ?>" onclick="switchTab('csv')">
                        <i class="fa-solid fa-file-csv"></i> Import CSV
                    </button>
                </div>

                <!-- PASTE TAB -->
                <div class="batch-tab-panel <?php echo $active_tab === 'paste' ? 'active' : ''; ?>" id="panel-paste">
                    <div class="import-grid">
                        <div class="import-section">
                            <h3><i class="fa-solid fa-paste"></i> Paste Curriculum Data</h3>
                            <form method="POST" action="../../php/admin_subjects_batch_import.php">
                                <input type="hidden" name="mode" value="paste">
                                <div class="form-group">
                                    <label>Paste data below — one subject per line</label>
                                    <p class="help-text">Format: <code>COURSE_CODE|SUBJECT_CODE|SUBJECT_NAME|UNITS|LEC|LAB|DEPT|YEAR|SEM|PREREQ</code></p>
                                    <textarea name="subjects_data" class="import-textarea"
                                              placeholder="BSIT|ICC 0101|Introduction to Computing|3|2|1|Information Technology|1|1st|"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="import-checkbox-label">
                                        <input type="checkbox" name="skip_duplicates" value="1" checked> Skip duplicate subject codes
                                    </label>
                                    <label class="import-checkbox-label">
                                        <input type="checkbox" name="set_active" value="1" checked> Set all as active
                                    </label>
                                </div>
                                <button type="submit" class="btn-import">
                                    <i class="fa-solid fa-plus-circle"></i> Create Subjects
                                </button>
                            </form>
                        </div>

                        <div class="import-section">
                            <h3><i class="fa-solid fa-circle-info"></i> Format Guide</h3>
                            <p class="help-text">Each line — pipe-separated (<code>|</code>):</p>
                            <div class="format-example">
                                <pre>COURSE_CODE|SUBJECT_CODE|SUBJECT_NAME|UNITS|LEC|LAB|DEPT|YEAR|SEM|PREREQ</pre>
                            </div>
                            <p class="help-text" style="margin-top:1.25rem;"><strong>Example:</strong></p>
                            <div class="format-example">
<pre>BSIT|STS 0002|Science, Technology and Society|3|3|0|General Education|1|1st|
BSIT|ICC 0101|Introduction to Computing|3|2|1|Information Technology|1|1st|
BSCpE|CPE 0111|Computer Engineering as a Discipline|1|1|0|Computer Engineering|1|1st|</pre>
                            </div>
                            <p class="help-text" style="margin-top:1.25rem;"><strong>Fields:</strong></p>
                            <ul class="field-list">
                                <li><strong>COURSE_CODE</strong> — e.g. BSIT, BSCpE</li>
                                <li><strong>SUBJECT_CODE</strong> — e.g. ICC 0101</li>
                                <li><strong>SUBJECT_NAME</strong> — full subject title</li>
                                <li><strong>UNITS</strong> — credit units (1–9)</li>
                                <li><strong>LEC</strong> — lecture hours</li>
                                <li><strong>LAB</strong> — lab hours</li>
                                <li><strong>DEPT</strong> — department name</li>
                                <li><strong>YEAR</strong> — year level (1–6)</li>
                                <li><strong>SEM</strong> — 1st, 2nd, or summer</li>
                                <li><strong>PREREQ</strong> — prerequisites <em>(optional)</em></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- CSV TAB -->
                <div class="batch-tab-panel <?php echo $active_tab === 'csv' ? 'active' : ''; ?>" id="panel-csv">
                    <div class="import-grid">
                        <div class="import-section">
                            <h3><i class="fa-solid fa-file-csv"></i> Upload CSV File</h3>
                            <form method="POST" action="../../php/admin_subjects_batch_import.php" enctype="multipart/form-data">
                                <input type="hidden" name="mode" value="csv">

                                <div class="csv-drop-zone" id="csvDropZone">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <p>Drag &amp; drop your CSV file here</p>
                                    <span>or</span>
                                    <label class="csv-browse-btn">
                                        Browse File
                                        <input type="file" name="csv_file" id="csvFileInput" accept=".csv" required>
                                    </label>
                                    <p class="csv-file-name" id="csvFileName">No file selected</p>
                                </div>

                                <!-- CSV Preview -->
                                <div id="csvPreviewWrap" style="display:none;margin-top:1rem;">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
                                        <span style="font-size:.82rem;font-weight:600;color:var(--text-label);">PREVIEW <span id="csvPreviewCount"></span></span>
                                        <button type="button" onclick="clearCsv()" style="background:none;border:none;font-size:.8rem;color:var(--red);cursor:pointer;">✕ Clear</button>
                                    </div>
                                    <div class="csv-preview-table-wrap">
                                        <table class="csv-preview-table" id="csvPreviewTable"></table>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-top:1rem;">
                                    <label class="import-checkbox-label">
                                        <input type="checkbox" name="skip_duplicates" value="1" checked> Skip duplicate subject codes
                                    </label>
                                    <label class="import-checkbox-label">
                                        <input type="checkbox" name="set_active" value="1" checked> Set all as active
                                    </label>
                                </div>
                                <button type="submit" class="btn-import" id="csvSubmitBtn" disabled>
                                    <i class="fa-solid fa-plus-circle"></i> Create Subjects
                                </button>
                            </form>
                        </div>

                        <div class="import-section">
                            <h3><i class="fa-solid fa-circle-info"></i> CSV Format Guide</h3>
                            <p class="help-text">The CSV must have a header row with these columns (order matters):</p>
                            <div class="format-example">
                                <pre>course_code,subject_code,subject_name,units,lecture_hours,lab_hours,department,year_level,semester,prerequisite</pre>
                            </div>
                            <p class="help-text" style="margin-top:1.25rem;"><strong>Example rows:</strong></p>
                            <div class="format-example">
<pre>course_code,subject_code,subject_name,units,lecture_hours,lab_hours,department,year_level,semester,prerequisite
BSIT,ICC 0101,Introduction to Computing,3,2,1,Information Technology,1,1st,
BSIT,ICC 0102,Computer Programming 1,3,2,1,Information Technology,1,1st,ICC 0101
BSCpE,CPE 0111,Computer Engineering as a Discipline,1,1,0,Computer Engineering,1,1st,</pre>
                            </div>
                            <p class="help-text" style="margin-top:1.25rem;">
                                <i class="fa-solid fa-lightbulb" style="color:var(--red);"></i>
                                Download the <a href="../../php/admin_subjects_batch_import.php?download_template=1" style="color:var(--red);font-weight:600;">CSV template</a> to get started quickly.
                            </p>
                            <p class="help-text" style="margin-top:.75rem;"><strong>Notes:</strong></p>
                            <ul class="field-list">
                                <li>First row must be the header</li>
                                <li>Wrap values with commas in double quotes</li>
                                <li><strong>prerequisite</strong> column can be left empty</li>
                                <li>Max file size: 2MB</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div><!-- /.main-content -->
        </main>
    </div>

    <script src="../../js/admin/admin_main.js"></script>
    <script>
    function switchTab(tab) {
        document.querySelectorAll('.batch-tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.batch-tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelector('.batch-tab[onclick*="' + tab + '"]').classList.add('active');
        document.getElementById('panel-' + tab).classList.add('active');
    }

    // CSV drag & drop + preview
    const dropZone   = document.getElementById('csvDropZone');
    const fileInput  = document.getElementById('csvFileInput');
    const fileName   = document.getElementById('csvFileName');
    const submitBtn  = document.getElementById('csvSubmitBtn');
    const previewWrap = document.getElementById('csvPreviewWrap');

    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file) { fileInput.files = e.dataTransfer.files; handleFile(file); }
    });
    fileInput.addEventListener('change', () => { if (fileInput.files[0]) handleFile(fileInput.files[0]); });

    function handleFile(file) {
        if (!file.name.endsWith('.csv')) { alert('Please select a .csv file.'); return; }
        fileName.textContent = file.name;
        submitBtn.disabled = false;
        const reader = new FileReader();
        reader.onload = e => renderPreview(e.target.result);
        reader.readAsText(file);
    }

    function renderPreview(text) {
        const lines = text.trim().split('\n').filter(l => l.trim());
        if (lines.length < 2) { previewWrap.style.display = 'none'; return; }
        const headers = parseCSVLine(lines[0]);
        const rows    = lines.slice(1, 6); // preview first 5 data rows
        const total   = lines.length - 1;

        document.getElementById('csvPreviewCount').textContent = '(' + total + ' row' + (total !== 1 ? 's' : '') + ')';

        let html = '<thead><tr>' + headers.map(h => '<th>' + esc(h) + '</th>').join('') + '</tr></thead><tbody>';
        rows.forEach(row => {
            const cols = parseCSVLine(row);
            html += '<tr>' + cols.map(c => '<td>' + esc(c) + '</td>').join('') + '</tr>';
        });
        if (total > 5) html += '<tr><td colspan="' + headers.length + '" style="text-align:center;color:var(--text-label);font-size:.78rem;">… and ' + (total - 5) + ' more rows</td></tr>';
        html += '</tbody>';

        document.getElementById('csvPreviewTable').innerHTML = html;
        previewWrap.style.display = 'block';
    }

    function parseCSVLine(line) {
        const result = []; let cur = ''; let inQ = false;
        for (let i = 0; i < line.length; i++) {
            const c = line[i];
            if (c === '"') { inQ = !inQ; }
            else if (c === ',' && !inQ) { result.push(cur.trim()); cur = ''; }
            else { cur += c; }
        }
        result.push(cur.trim());
        return result;
    }

    function clearCsv() {
        fileInput.value = '';
        fileName.textContent = 'No file selected';
        submitBtn.disabled = true;
        previewWrap.style.display = 'none';
    }

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }
    </script>
</body>
</html>
