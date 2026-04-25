<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status='pending'"))['c'];

// Flash messages
$flash = '';
if (isset($_GET['success'])) {
    $count = (int)($_GET['count'] ?? 0);
    $skipped = (int)($_GET['skipped'] ?? 0);
    $msg = 'Successfully imported ' . $count . ' subject' . ($count !== 1 ? 's' : '') . '!';
    if ($skipped > 0) $msg .= ' (' . $skipped . ' skipped as duplicates)';
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> ' . $msg . '</div>';
}
if (isset($_GET['error'])) {
    $msgs = [
        'no_data'        => 'No subjects data provided.',
        'invalid_format' => 'Invalid data format. Please check your input.',
        'import_failed'  => 'Failed to import subjects. Please try again.',
        'all_skipped'    => 'All ' . (int)($_GET['skipped'] ?? 0) . ' subjects were skipped as duplicates. No new subjects imported.',
    ];
    $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> ' . ($msgs[$_GET['error']] ?? 'An error occurred.') . '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Import Subjects - PLM Admin</title>
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

    <!-- ── Top Nav Bar ────────────────────────────────── -->
    <header>
        <div class="nav-section">
            <!-- Mobile toggle -->
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
                <div class="acc-name">
                    <?php echo htmlspecialchars($admin_data['username'] ?? 'Admin'); ?>
                </div>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($admin_data['username'] ?? 'A', 0, 1)); ?>
                </div>
            </div>
        </div>

        <!-- ── Side Nav ───────────────────────────────── -->
        <nav class="main-nav" id="navMenu">
            <div class="nav-wrapper">
                <ul class="main-ul">
                    <li>
                        <a href="admin_home.php">
                            <i class="fa-solid fa-house"></i>
                            <span class="li-name">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_applicants.php">
                            <i class="fa-solid fa-user-plus"></i>
                            <span class="li-name">Applicants</span>
                            <?php if ($pending_applicants > 0): ?>
                                <span class="sidebar-badge li-name"><?php echo $pending_applicants; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <!-- Student Records Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="student-records-dropdown">
                            <i class="fa-solid fa-user-graduate"></i>
                            <span class="li-name chev-space">
                                Student Records
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="student-records-menu">
                            <ul>
                                <li><a href="admin_students.php">Students</a></li>
                                <li><a href="admin_enrollments.php">Enrollments</a></li>
                                <li>
                                    <a href="admin_drop_requests.php">
                                        Drop Requests
                                        <?php if (!empty($GLOBALS['pending_drops'])): ?>
                                            <span class="sidebar-badge"><?php echo $GLOBALS['pending_drops']; ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Academic Records Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="acad-records-dropdown">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <span class="li-name chev-space">
                                Academic Records
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="acad-records-menu">
                            <ul>
                                <li><a href="admin_subjects.php">Subjects</a></li>
                                <li><a href="admin_classes.php">Classes</a></li>
                                <li><a href="admin_blocks.php">Blocks</a></li>
                            </ul>
                        </div>
                    </li>

                    <!-- Personnel Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="personnel-dropdown">
                            <i class="fa-solid fa-users-gear"></i>
                            <span class="li-name chev-space">
                                Personnel
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="personnel-menu">
                            <ul>
                                <li><a href="admin_faculty.php">Faculty</a></li>
                                <?php if (($admin_data['role'] ?? 'admin') === 'superadmin'): ?>
                                    <li><a href="admin_accounts.php">Admin Accounts</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>

                    <!-- Communications Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="comms-dropdown">
                            <i class="fa-solid fa-bullhorn"></i>
                            <span class="li-name chev-space">
                                Communications
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="comms-menu">
                            <ul>
                                <li><a href="admin_announcements.php">Announcements</a></li>
                                <li><a href="admin_calendar.php">Calendar</a></li>
                            </ul>
                        </div>
                    </li>

                    <li>
                        <a href="../../php/admin_logout.php" class="logout-bg">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span class="li-name">Logout</span>
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

    <!-- ── Page Body ──────────────────────────────────── -->
    <div class="main-flex">
        <div class="spacer"></div>

        <main>
            <div class="main-content">

                <div class="page-header">
                    <div>
                        <h1>Batch Import Subjects</h1>
                        <p>Import multiple subjects from curriculum data</p>
                    </div>
                    <a href="admin_subjects.php" class="btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span class="li-name">Back to Subjects</span>
                    </a>
                </div>

                <?php echo $flash; ?>

                <div class="import-grid">

                    <!-- ── Import Form ──────────────────── -->
                    <div class="import-section">
                        <h3><i class="fa-solid fa-upload"></i> Import Data</h3>
                        <form method="POST" action="../../php/admin_subjects_batch_import.php">
                            <div class="form-group">
                                <label>Paste Curriculum Data</label>
                                <p class="help-text">Copy and paste subject data in the format shown on the right →</p>
                                <textarea name="subjects_data" class="import-textarea"
                                          placeholder="COURSE_CODE|SUBJECT_CODE|SUBJECT_NAME|UNITS|LEC|LAB|DEPT|YEAR|SEM|PREREQ"
                                          required></textarea>
                            </div>

                            <div class="form-group">
                                <label class="import-checkbox-label">
                                    <input type="checkbox" name="skip_duplicates" value="1" checked>
                                    Skip duplicate subject codes
                                </label>
                                <label class="import-checkbox-label">
                                    <input type="checkbox" name="set_active" value="1" checked>
                                    Set all imported subjects as active
                                </label>
                            </div>

                            <button type="submit" class="btn-import">
                                <i class="fa-solid fa-file-import"></i> Import Subjects
                            </button>
                        </form>
                    </div>

                    <!-- ── Format Guide ─────────────────── -->
                    <div class="import-section">
                        <h3><i class="fa-solid fa-info-circle"></i> Format Guide</h3>

                        <p class="help-text">Each line should contain subject data separated by pipes (<code>|</code>):</p>
                        <div class="format-example">
                            <pre>COURSE_CODE|SUBJECT_CODE|SUBJECT_NAME|UNITS|LEC|LAB|DEPT|YEAR|SEM|PREREQ</pre>
                        </div>

                        <p class="help-text" style="margin-top:1.25rem;"><strong>Example:</strong></p>
                        <div class="format-example">
<pre>BSIT|STS 0002|Science, Technology and Society|3|3|0|General Education|1|1st|
BSIT|ICC 0101|Introduction to Computing|3|2|1|Information Technology|1|1st|
BSCpE|CPE 0111|Computer Engineering as a Discipline|1|1|0|Computer Engineering|1|1st|</pre>
                        </div>

                        <p class="help-text" style="margin-top:1.25rem;"><strong>Field Descriptions:</strong></p>
                        <ul class="field-list">
                            <li><strong>COURSE_CODE</strong> — Course code (e.g., BSIT, BSCpE, BSCE)</li>
                            <li><strong>SUBJECT_CODE</strong> — Subject code (e.g., ICC 0101)</li>
                            <li><strong>SUBJECT_NAME</strong> — Subject name</li>
                            <li><strong>UNITS</strong> — Credit units (1–9)</li>
                            <li><strong>LEC</strong> — Lecture hours (0–9)</li>
                            <li><strong>LAB</strong> — Lab hours (0–9)</li>
                            <li><strong>DEPT</strong> — Department name</li>
                            <li><strong>YEAR</strong> — Year level (1–4, or leave blank)</li>
                            <li><strong>SEM</strong> — Semester (1st, 2nd, summer, or leave blank)</li>
                            <li><strong>PREREQ</strong> — Prerequisites <em>(optional, leave blank)</em></li>
                        </ul>
                    </div>

                </div><!-- /.import-grid -->

            </div><!-- /.main-content -->
        </main>
    </div><!-- /.main-flex -->

    <script src="../../js/admin/admin_main.js"></script>
</body>
</html>