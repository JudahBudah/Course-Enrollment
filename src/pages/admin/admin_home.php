<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);

// Handle settings save (superadmin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (($admin_data['role'] ?? '') === 'superadmin') {
        $fields = ['current_semester', 'current_school_year', 'min_units', 'max_units',
                   'enrollment_start', 'enrollment_end', 'late_enrollment_start', 'late_enrollment_end'];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) save_setting($con, $f, trim($_POST[$f]));
        }
    }
    header('Location: admin_home.php?saved=1'); die;
}

$s_cur_semester          = get_setting($con, 'current_semester',       '');
$s_cur_school_year       = get_setting($con, 'current_school_year',    '');
$s_min_units             = get_setting($con, 'min_units',              '0');
$s_max_units             = get_setting($con, 'max_units',              '0');
$s_enrollment_start      = get_setting($con, 'enrollment_start',       '');
$s_enrollment_end        = get_setting($con, 'enrollment_end',         '');
$s_late_enrollment_start = get_setting($con, 'late_enrollment_start',  '');
$s_late_enrollment_end   = get_setting($con, 'late_enrollment_end',    '');

// Get statistics
$total_students     = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM students"))['count'];
$total_applicants   = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM applicants"))['count'];
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM applicants WHERE application_status = 'pending'"))['count'];
$total_faculty      = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM faculty WHERE status = 'active'"))['count'];
$total_subjects     = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM subjects WHERE status = 'active'"))['count'];
$total_classes      = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM classes WHERE status IN ('open', 'closed')"))['count'];

// Enrollment period & current semester stats
$period          = get_enrollment_period($con);
$sy              = get_setting($con, 'current_school_year', '');
$sem             = get_setting($con, 'current_semester', '');

$enrolled_now    = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT COUNT(*) as c FROM enrollments e
     JOIN classes c ON e.class_id = c.class_id
     WHERE e.status IN ('confirmed','ongoing')
       AND c.school_year = '" . mysqli_real_escape_string($con,$sy) . "'
       AND c.semester    = '" . mysqli_real_escape_string($con,$sem) . "'"))['c'];

$pending_drops   = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT COUNT(*) as c FROM enrollments e
     JOIN classes c ON e.class_id = c.class_id
     WHERE e.status = 'drop_requested'
       AND c.school_year = '" . mysqli_real_escape_string($con,$sy) . "'
       AND c.semester    = '" . mysqli_real_escape_string($con,$sem) . "'"))['c'];

$full_classes    = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT COUNT(*) as c FROM classes
     WHERE status = 'open' AND enrolled_count >= max_slots
       AND school_year = '" . mysqli_real_escape_string($con,$sy) . "'
       AND semester    = '" . mysqli_real_escape_string($con,$sem) . "'"))['c'];

$open_classes_now = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT COUNT(*) as c FROM classes
     WHERE status = 'open'
       AND school_year = '" . mysqli_real_escape_string($con,$sy) . "'
       AND semester    = '" . mysqli_real_escape_string($con,$sem) . "'"))['c'];

$irregular_count = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT COUNT(*) as c FROM students WHERE registration_status = 'Irregular'"))['c'];

$period_labels = [
    'enrollment'      => ['Open — Regular Enrollment', '#16a34a', 'fa-circle-check'],
    'late_enrollment' => ['Open — Late / Add-Drop',    '#d97706', 'fa-right-left'],
    'closed'          => ['Closed',                    '#dc2626', 'fa-lock'],
];
[$period_text, $period_color, $period_icon] = $period_labels[$period] ?? ['Unknown','#888','fa-question'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PLM Admin</title>
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
    <link rel="stylesheet" href="../../css/admin/admin_home.css">
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

                    <?php if (($admin_data['role'] ?? '') === 'superadmin'): ?>
                    <li>
                        <a href="admin_settings.php" class="superadmin-link">
                            <i class="fa-solid fa-sliders"></i>
                            <span class="li-name">System Settings</span>
                        </a>
                    </li>
                    <?php endif; ?>
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
                    <h1>Dashboard Overview</h1>
                    <p>Welcome back, <strong><?php echo htmlspecialchars($admin_data['username'] ?? 'Admin'); ?></strong>!</p>
                </div>

                <!-- Enrollment Period Banner -->
                <div class="dash-period-banner" style="border-left-color:<?php echo $period_color; ?>;">
                    <i class="fa-solid <?php echo $period_icon; ?>" style="color:<?php echo $period_color; ?>;font-size:1.1rem;"></i>
                    <div class="dash-period-text">
                        <span class="dash-period-label">Enrollment Period</span>
                        <span class="dash-period-value" style="color:<?php echo $period_color; ?>;"><?php echo $period_text; ?></span>
                    </div>
                    <?php if ($sy || $sem): ?>
                    <div class="dash-period-meta">
                        <?php if ($sem): ?><span><i class="fa-solid fa-calendar-half"></i> <?php echo htmlspecialchars(ucfirst($sem)); ?> Semester</span><?php endif; ?>
                        <?php if ($sy): ?><span><i class="fa-solid fa-school"></i> S.Y. <?php echo htmlspecialchars($sy); ?></span><?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($pending_drops > 0): ?>
                    <a href="admin_drop_requests.php" class="dash-period-alert">
                        <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $pending_drops; ?> pending drop<?php echo $pending_drops > 1 ? 's' : ''; ?>
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="stat-content">
                            <h3>Total Students</h3>
                            <p class="stat-number"><?php echo number_format($total_students); ?></p>
                        </div>
                    </div>
                    <div class="stat-card gold">
                        <div class="stat-icon"><i class="fa-solid fa-user-plus"></i></div>
                        <div class="stat-content">
                            <h3>Applicants</h3>
                            <p class="stat-number"><?php echo number_format($total_applicants); ?></p>
                            <?php if ($pending_applicants > 0): ?>
                                <small><?php echo $pending_applicants; ?> pending</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-card red">
                        <div class="stat-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                        <div class="stat-content">
                            <h3>Active Faculty</h3>
                            <p class="stat-number"><?php echo number_format($total_faculty); ?></p>
                        </div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-icon"><i class="fa-solid fa-book"></i></div>
                        <div class="stat-content">
                            <h3>Active Subjects</h3>
                            <p class="stat-number"><?php echo number_format($total_subjects); ?></p>
                        </div>
                    </div>
                    <div class="stat-card purple">
                        <div class="stat-icon"><i class="fa-solid fa-door-open"></i></div>
                        <div class="stat-content">
                            <h3>Active Classes</h3>
                            <p class="stat-number"><?php echo number_format($total_classes); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Current Semester Summary Strip -->
                <div class="dash-sem-strip">
                    <div class="dash-sem-cell">
                        <span class="dash-sem-num"><?php echo number_format($enrolled_now); ?></span>
                        <span class="dash-sem-label">Enrolled This Sem</span>
                    </div>
                    <div class="dash-sem-cell">
                        <span class="dash-sem-num"><?php echo number_format($open_classes_now); ?></span>
                        <span class="dash-sem-label">Open Classes</span>
                    </div>
                    <div class="dash-sem-cell <?php echo $full_classes > 0 ? 'dash-sem-warn' : ''; ?>">
                        <span class="dash-sem-num"><?php echo number_format($full_classes); ?></span>
                        <span class="dash-sem-label">Classes at Capacity</span>
                    </div>
                    <div class="dash-sem-cell <?php echo $irregular_count > 0 ? 'dash-sem-warn' : ''; ?>">
                        <span class="dash-sem-num"><?php echo number_format($irregular_count); ?></span>
                        <span class="dash-sem-label">Irregular Students</span>
                    </div>
                    <div class="dash-sem-cell <?php echo $pending_drops > 0 ? 'dash-sem-alert' : ''; ?>">
                        <span class="dash-sem-num"><?php echo number_format($pending_drops); ?></span>
                        <span class="dash-sem-label">Pending Drop Requests</span>
                    </div>
                </div>

                <!-- Content grid: recent applicants + quick actions -->
                <div class="content-grid">
                    <div class="card">
                        <div class="card-header">
                            <h2>Recent Applicants</h2>
                            <a href="admin_applicants.php" class="link-small">View All</a>
                        </div>

                        <div class="recent-applicants-table-wrapper">
                            <div class="recent-applicants-table">

                                <div class="recent-applicants-table-header">
                                    <div class="recent-applicants-col-left">Name</div>
                                    <div class="recent-applicants-col-left">Email</div>
                                    <div class="recent-applicants-col-left">Program</div>
                                    <div>Status</div>
                                    <div>Date</div>
                                </div>

                                <div class="recent-applicants-table-body">
                                <?php
                                $recent = mysqli_query($con, "SELECT * FROM applicants ORDER BY created_at DESC LIMIT 5");
                                while ($row = mysqli_fetch_assoc($recent)):
                                ?>
                                <div class="recent-applicants-row">
                                    <div class="recent-applicants-col-left"><?php echo htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? 'N/A')); ?></div>
                                    <div class="recent-applicants-col-left word-break"><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></div>
                                    <div class="recent-applicants-col-left"><?php echo htmlspecialchars($row['first_choice'] ?? 'N/A'); ?></div>
                                    <div>
                                        <span class="badge <?php echo strtolower($row['application_status'] ?? 'incomplete'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($row['application_status'] ?? 'Incomplete')); ?>
                                        </span>
                                    </div>
                                    <div><?php echo date('M d, Y', strtotime($row['created_at'])); ?></div>
                                </div>
                                <?php endwhile; ?>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2>Quick Actions</h2>
                        </div>
                        <div class="quick-actions">
                            <a href="admin_applicants.php" class="action-btn">
                                <i class="fa-solid fa-user-check"></i>
                                <span>Review Applicants</span>
                            </a>
                            <a href="admin_students.php?action=add" class="action-btn">
                                <i class="fa-solid fa-user-plus"></i>
                                <span>Add New Student</span>
                            </a>
                            <a href="admin_subjects.php?action=add" class="action-btn">
                                <i class="fa-solid fa-book-medical"></i>
                                <span>Add New Subject</span>
                            </a>
                            <a href="admin_classes.php?action=add" class="action-btn">
                                <i class="fa-solid fa-plus-circle"></i>
                                <span>Create New Class</span>
                            </a>
                            <a href="admin_announcements.php?action=add" class="action-btn">
                                <i class="fa-solid fa-bullhorn"></i>
                                <span>Post Announcement</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- System Activity -->
                <div class="card">
                    <div class="card-header">
                        <h2>System Activity</h2>
                        <span class="activity-live"><i class="fa-solid fa-circle"></i> Live</span>
                    </div>
                    <div class="table-responsive">
                        <div class="activity-list" id="activityList">
                            <div style="padding:2rem;text-align:center;color:var(--text-label);">
                                <i class="fa-solid fa-spinner fa-spin"></i> Loading...
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /.main-content -->
        </main>
    </div><!-- /.main-flex -->

    <script src="../../js/admin/admin_main.js"></script>
    <script src="../../js/admin/admin_home.js"></script>
    <script src="../../js/no_cache.js"></script>
    <script src="../../js/plm_loader.js"></script>
    <script>
    const ICON_MAP = {
        'fa-right-to-bracket': 'blue',
        'fa-door-open':        'purple',
        'fa-book':             'gold',
        'fa-bullhorn':         'red',
        'fa-chalkboard-user':  'teal',
        'fa-users':            'blue',
        'fa-file-lines':       'green',
        'fa-layer-group':      'navy',
        'fa-user-shield':      'red',
        'fa-calendar-days':    'gold',
        'fa-user-plus':        'gold',
        'fa-circle-info':      'blue',
    };

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function renderActivity(items) {
        const list = document.getElementById('activityList');
        if (!items.length) {
            list.innerHTML = '<div style="padding:2rem;text-align:center;color:var(--text-label);">No activity logged yet.</div>';
            return;
        }
        list.innerHTML = items.map(item => {
            const detail = item.detail ? `<span class="activity-detail">${esc(item.detail)}</span>` : '';
            const by     = item.by    ? `<span class="activity-by">by ${esc(item.by)}</span>` : '';
            return `<div class="activity-item">
                <div class="activity-icon ${esc(item.color)}"><i class="fa-solid ${esc(item.icon)}"></i></div>
                <div class="activity-content">
                    <p><strong>${esc(item.action)}</strong>${detail}</p>
                    <small>${esc(item.ago)}${by}</small>
                </div>
            </div>`;
        }).join('');
    }

    function fetchActivity() {
        fetch('../../php/admin_activity_feed.php')
            .then(r => r.json())
            .then(renderActivity)
            .catch(() => {});
    }

    fetchActivity();
    setInterval(fetchActivity, 30000);
    </script>
</body>
</html>