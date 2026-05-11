<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);

// Superadmin only
if (($admin_data['role'] ?? '') !== 'superadmin') {
    header('Location: admin_home.php');
    die;
}

$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status='pending'"))['c'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $fields = ['current_semester', 'current_school_year',
               'enrollment_start', 'enrollment_end', 'late_enrollment_start', 'late_enrollment_end'];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) save_setting($con, $f, trim($_POST[$f]));
    }
    log_activity($con, 'Updated system settings', 'settings', 'Saved by ' . ($admin_data['username'] ?? ''));
    header('Location: admin_settings.php?saved=1'); die;
}

$s_cur_semester          = get_setting($con, 'current_semester',       '');
$s_cur_school_year       = get_setting($con, 'current_school_year',    '');
$s_enrollment_start      = get_setting($con, 'enrollment_start',       '');
$s_enrollment_end        = get_setting($con, 'enrollment_end',         '');
$s_late_enrollment_start = get_setting($con, 'late_enrollment_start',  '');
$s_late_enrollment_end   = get_setting($con, 'late_enrollment_end',    '');

// Derive current period status for display
$period = get_enrollment_period($con);
$period_labels = [
    'enrollment'      => ['Open — Regular Enrollment',      '#16a34a', 'fa-circle-check'],
    'late_enrollment' => ['Open — Late Enrollment / Add-Drop', '#d97706', 'fa-right-left'],
    'closed'          => ['Closed',                          '#dc2626', 'fa-lock'],
];
[$period_text, $period_color, $period_icon] = $period_labels[$period] ?? ['Unknown', '#888', 'fa-question'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - PLM Admin</title>
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
    <link rel="stylesheet" href="../../css/admin/admin_settings.css">
</head>
<body>
    <header>
        <div class="nav-section">
            <button class="nav-button" id="navButton"><i class="fa-solid fa-bars" id="trans-bars"></i></button>
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
                            <li><a href="admin_accounts.php">Admin Accounts</a></li>
                        </ul></div>
                    </li>
                    <li class="course-dropdown">
                        <a href="#" id="comms-dropdown"><i class="fa-solid fa-bullhorn"></i><span class="li-name chev-space">Communications <i class="fa-solid fa-chevron-down"></i></span></a>
                        <div class="acad-dropdown-menu" id="comms-menu"><ul>
                            <li><a href="admin_announcements.php">Announcements</a></li>
                            <li><a href="admin_calendar.php">Calendar</a></li>
                        </ul></div>
                    </li>
                    <li>
                        <a href="admin_settings.php" class="active superadmin-link">
                            <i class="fa-solid fa-sliders"></i>
                            <span class="li-name">System Settings</span>
                        </a>
                    </li>
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
                        <h1><i class="fa-solid fa-sliders" style="color:var(--red);margin-right:.5rem;font-size:1.4rem;"></i>System Settings</h1>
                        <p>Configure enrollment periods, academic calendar, and system-wide rules.</p>
                    </div>
                    <?php if (isset($_GET['saved'])): ?>
                    <div class="settings-saved-badge"><i class="fa-solid fa-check"></i> Settings saved</div>
                    <?php endif; ?>
                </div>

                <!-- Current Period Status -->
                <div class="settings-status-bar" style="border-left-color:<?php echo $period_color; ?>;">
                    <i class="fa-solid <?php echo $period_icon; ?>" style="color:<?php echo $period_color; ?>;font-size:1.1rem;"></i>
                    <div>
                        <span class="settings-status-label">Current Enrollment Period</span>
                        <span class="settings-status-value" style="color:<?php echo $period_color; ?>;"><?php echo $period_text; ?></span>
                    </div>
                    <?php if (!empty($s_cur_semester) || !empty($s_cur_school_year)): ?>
                    <div class="settings-status-meta">
                        <?php if ($s_cur_semester): ?><span><i class="fa-solid fa-calendar-half"></i> <?php echo htmlspecialchars(ucfirst($s_cur_semester)); ?> Semester</span><?php endif; ?>
                        <?php if ($s_cur_school_year): ?><span><i class="fa-solid fa-school"></i> S.Y. <?php echo htmlspecialchars($s_cur_school_year); ?></span><?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Section: Promote Students -->
                <div class="settings-section" style="border-left: 4px solid #dc2626;">
                    <div class="settings-section-header">
                        <i class="fa-solid fa-arrow-up" style="color:#dc2626;"></i>
                        <div>
                            <h2>Promote All Students</h2>
                            <p>Advance every student up by one year level. Ungraded active enrollments will be marked <strong>INC</strong> and those students flagged as Irregular. This action cannot be undone.</p>
                        </div>
                    </div>
                    <div style="padding:1.25rem 1.5rem;">
                        <?php if (isset($_GET['success']) && $_GET['success'] === 'promoted'): ?>
                        <div style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;border-radius:8px;padding:.65rem 1rem;font-size:.85rem;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;">
                            <i class="fa-solid fa-check-circle"></i>
                            <?php echo (int)$_GET['count']; ?> students promoted &mdash; <?php echo (int)$_GET['inc']; ?> marked INC.
                        </div>
                        <?php endif; ?>
                        <form method="POST" action="../../php/admin_promote_students.php"
                              onsubmit="return confirm('Promote ALL students by one year level?\n\nUngraded enrollments will be marked INC and those students flagged Irregular.\n\nThis cannot be undone.');">
                            <button type="submit" class="btn-settings-save" style="background:#dc2626;">
                                <i class="fa-solid fa-arrow-up"></i> Promote All Students
                            </button>
                        </form>
                    </div>
                </div>

                <form method="POST">

                    <!-- Section: Academic Calendar -->
                    <div class="settings-section">
                        <div class="settings-section-header">
                            <i class="fa-solid fa-calendar-days"></i>
                            <div>
                                <h2>Academic Calendar</h2>
                                <p>Set the current semester and school year used across the system.</p>
                            </div>
                        </div>
                        <div class="settings-fields">
                            <div class="settings-field">
                                <label class="settings-label">Current Semester</label>
                                <select name="current_semester" class="settings-input">
                                    <option value="">— Select —</option>
                                    <option value="1st"    <?php echo $s_cur_semester === '1st'    ? 'selected' : ''; ?>>1st Semester</option>
                                    <option value="2nd"    <?php echo $s_cur_semester === '2nd'    ? 'selected' : ''; ?>>2nd Semester</option>
                                    <option value="summer" <?php echo $s_cur_semester === 'summer' ? 'selected' : ''; ?>>Summer</option>
                                </select>
                            </div>
                            <div class="settings-field">
                                <label class="settings-label">Current School Year</label>
                                <input type="text" name="current_school_year" value="<?php echo htmlspecialchars($s_cur_school_year); ?>" placeholder="e.g. 2024-2025" class="settings-input">
                            </div>
                        </div>
                    </div>

                    <!-- Section: Enrollment Periods -->
                    <div class="settings-section">
                        <div class="settings-section-header">
                            <i class="fa-solid fa-clock"></i>
                            <div>
                                <h2>Enrollment Periods</h2>
                                <p>Define when students can enroll and when the late enrollment / add-drop window opens.</p>
                            </div>
                        </div>

                        <div class="settings-period-grid">
                            <div class="settings-period-block">
                                <div class="settings-period-title"><i class="fa-solid fa-door-open" style="color:#16a34a;"></i> Regular Enrollment</div>
                                <div class="settings-fields">
                                    <div class="settings-field">
                                        <label class="settings-label">Start</label>
                                        <input type="datetime-local" name="enrollment_start" value="<?php echo htmlspecialchars($s_enrollment_start); ?>" class="settings-input">
                                    </div>
                                    <div class="settings-field">
                                        <label class="settings-label">End</label>
                                        <input type="datetime-local" name="enrollment_end" value="<?php echo htmlspecialchars($s_enrollment_end); ?>" class="settings-input">
                                    </div>
                                </div>
                            </div>
                            <div class="settings-period-divider"></div>
                            <div class="settings-period-block">
                                <div class="settings-period-title"><i class="fa-solid fa-right-left" style="color:#d97706;"></i> Late Enrollment / Add-Drop</div>
                                <div class="settings-fields">
                                    <div class="settings-field">
                                        <label class="settings-label">Start</label>
                                        <input type="datetime-local" name="late_enrollment_start" value="<?php echo htmlspecialchars($s_late_enrollment_start); ?>" class="settings-input">
                                    </div>
                                    <div class="settings-field">
                                        <label class="settings-label">End</label>
                                        <input type="datetime-local" name="late_enrollment_end" value="<?php echo htmlspecialchars($s_late_enrollment_end); ?>" class="settings-input">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="settings-note">
                            <i class="fa-solid fa-circle-info"></i>
                            If all four dates are blank, enrollment is <strong>closed</strong> by default.
                            Drop requests are only allowed during the Late Enrollment / Add-Drop window.
                        </p>
                    </div>

                    <div class="settings-actions">
                        <button type="submit" name="save_settings" class="btn-settings-save">
                            <i class="fa-solid fa-floppy-disk"></i> Save Settings
                        </button>
                    </div>

                </form>

            </div>
        </main>
    </div>

    <script src="../../js/admin/admin_main.js"></script>
</body>
</html>
