<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status = 'pending'"))['c'];

// Flash messages
$flash_errors = [
    'missing_fields'   => 'Please fill in all required fields.',
    'insert_failed'    => 'Failed to add faculty. Employee ID or email may already exist.',
    'update_failed'    => 'Failed to update faculty record.',
    'already_assigned' => 'This class already has an assigned faculty member.',
];
$flash = '';
if (isset($_GET['error']) && isset($flash_errors[$_GET['error']])) {
    $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> ' . $flash_errors[$_GET['error']];
    // Show override option for already_assigned
    if ($_GET['error'] === 'already_assigned' && isset($_GET['current_faculty'], $_GET['class_id'], $_GET['faculty_id'])) {
        $current = htmlspecialchars(urldecode($_GET['current_faculty']));
        $cid = (int)$_GET['class_id'];
        $fid = (int)$_GET['faculty_id'];
        $flash .= ' Currently assigned to: <strong>' . $current . '</strong>.';
        $flash .= ' <form method="POST" action="../../php/admin_faculty_handler.php" style="display:inline;">';
        $flash .= '<input type="hidden" name="action" value="assign_class">';
        $flash .= '<input type="hidden" name="faculty_id" value="' . $fid . '">';
        $flash .= '<input type="hidden" name="class_id" value="' . $cid . '">';
        $flash .= '<input type="hidden" name="force" value="1">';
        $flash .= '<button type="submit" class="btn-primary" style="margin-left:.75rem;padding:.3rem .8rem;font-size:.82rem;" onclick="return confirm(\'Override and replace the current faculty assignment?\')">';
        $flash .= '<i class="fa-solid fa-rotate"></i> Override Assignment</button></form>';
    }
    $flash .= '</div>';
}
if (isset($_GET['success'])) {
    $msgs = [
        'added'      => 'Faculty added successfully.',
        'updated'    => 'Faculty updated successfully.',
        'deleted'    => 'Faculty deleted successfully.',
        'assigned'   => 'Faculty assigned to class.',
        'unassigned' => 'Faculty unassigned from class.',
    ];
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> ' . ($msgs[$_GET['success']] ?? 'Done.') . '</div>';
}

// Stats
$total_faculty  = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM faculty"))['c'];
$active_faculty = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM faculty WHERE status='active'"))['c'];
$assigned_count = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(DISTINCT faculty_id) as c FROM classes WHERE faculty_id IS NOT NULL"))['c'];

// Search & filter
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';

$where = "WHERE 1=1";
if ($filter === 'active')   $where .= " AND status = 'active'";
if ($filter === 'inactive') $where .= " AND status = 'inactive'";
if ($search !== '') {
    $s = mysqli_real_escape_string($con, $search);
    $where .= " AND (first_name LIKE '%$s%' OR last_name LIKE '%$s%' OR employee_id LIKE '%$s%' OR email LIKE '%$s%' OR department LIKE '%$s%')";
}

$faculty_list = mysqli_query($con, "SELECT * FROM faculty $where ORDER BY last_name, first_name");

// Classes for assign modal
$classes_query = mysqli_query($con, "
    SELECT c.class_id, c.section, c.semester, c.school_year, c.faculty_id,
           s.subject_code, s.subject_name,
           CONCAT(f.first_name,' ',f.last_name) as current_faculty
    FROM classes c
    JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    WHERE c.status = 'open'
    ORDER BY s.subject_code, c.section
");
$all_classes = [];
while ($row = mysqli_fetch_assoc($classes_query)) $all_classes[] = $row;

// Faculty detail / schedule view
$view_faculty_id = isset($_GET['faculty_id']) ? (int)$_GET['faculty_id'] : 0;
$view_mode       = $_GET['view'] ?? 'assign';
$view_faculty    = null;
$faculty_classes = [];

if ($view_faculty_id) {
    $vf = mysqli_prepare($con, "SELECT * FROM faculty WHERE faculty_id = ?");
    mysqli_stmt_bind_param($vf, "i", $view_faculty_id);
    mysqli_stmt_execute($vf);
    $view_faculty = mysqli_fetch_assoc(mysqli_stmt_get_result($vf));

    $fc = mysqli_prepare($con, "
        SELECT c.class_id, c.section, c.semester, c.school_year,
               c.schedule_day, c.schedule_time, c.room,
               s.subject_code, s.subject_name, s.units
        FROM classes c
        JOIN subjects s ON c.subject_id = s.subject_id
        WHERE c.faculty_id = ?
        ORDER BY s.subject_code
    ");
    mysqli_stmt_bind_param($fc, "i", $view_faculty_id);
    mysqli_stmt_execute($fc);
    $fcr = mysqli_stmt_get_result($fc);
    while ($row = mysqli_fetch_assoc($fcr)) $faculty_classes[] = $row;
}

$week_days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

function parse_days(string $raw): array {
    $raw    = strtoupper(trim($raw));
    $combos = [
        'TTH'    => ['Tuesday','Thursday'],
        'MWF'    => ['Monday','Wednesday','Friday'],
        'MW'     => ['Monday','Wednesday'],
        'TF'     => ['Tuesday','Friday'],
        'MTH'    => ['Monday','Thursday'],
        'WF'     => ['Wednesday','Friday'],
        'MTWTHF' => ['Monday','Tuesday','Wednesday','Thursday','Friday'],
    ];
    if (isset($combos[$raw])) return $combos[$raw];
    $day_map = [
        'M'=>'Monday','T'=>'Tuesday','W'=>'Wednesday','TH'=>'Thursday','F'=>'Friday','S'=>'Saturday',
        'MON'=>'Monday','TUE'=>'Tuesday','WED'=>'Wednesday','THU'=>'Thursday','FRI'=>'Friday','SAT'=>'Saturday',
        'MONDAY'=>'Monday','TUESDAY'=>'Tuesday','WEDNESDAY'=>'Wednesday','THURSDAY'=>'Thursday','FRIDAY'=>'Friday','SATURDAY'=>'Saturday',
    ];
    $days = [];
    foreach (preg_split('/[,\/\s]+/', $raw) as $p) {
        $p = trim($p);
        if (isset($day_map[$p])) $days[] = $day_map[$p];
    }
    return array_unique($days);
}

$calendar = array_fill_keys($week_days, []);
foreach ($faculty_classes as $cls) {
    if (empty($cls['schedule_day'])) continue;
    foreach (parse_days($cls['schedule_day']) as $d) {
        if (isset($calendar[$d])) $calendar[$d][] = $cls;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Management - PLM Admin</title>
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
    <link rel="stylesheet" href="../../css/admin/admin_faculty.css">
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
                    <h1>Faculty Management</h1>
                    <p>Manage faculty members and assign them to classes</p>
                </div>

                <?php echo $flash; ?>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                        <div class="stat-content"><h3>Total Faculty</h3><p class="stat-number"><?php echo $total_faculty; ?></p></div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                        <div class="stat-content"><h3>Active</h3><p class="stat-number"><?php echo $active_faculty; ?></p></div>
                    </div>
                    <div class="stat-card gold">
                        <div class="stat-icon"><i class="fa-solid fa-door-open"></i></div>
                        <div class="stat-content"><h3>Assigned to Classes</h3><p class="stat-number"><?php echo $assigned_count; ?></p></div>
                    </div>
                </div>

                <!-- ── Faculty Detail Panel ──────────────── -->
                <?php if ($view_faculty): ?>
                <div class="panel">
                    <div class="panel-header">
                        <h3>
                            <i class="fa-solid fa-chalkboard-user"></i>
                            <?php echo htmlspecialchars($view_faculty['first_name'] . ' ' . $view_faculty['last_name']); ?>
                        </h3>
                        <a href="admin_faculty.php" class="btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i>
                            <span class="li-name">Back</span>
                        </a>
                    </div>

                    <div class="view-tabs">
                        <button class="view-tab <?php echo $view_mode==='assign'   ? 'active':''; ?>" onclick="switchMode('assign')">
                            <i class="fa-solid fa-chalkboard"></i> Assign Classes
                        </button>
                        <button class="view-tab <?php echo $view_mode==='schedule' ? 'active':''; ?>" onclick="switchMode('schedule')">
                            <i class="fa-solid fa-calendar-days"></i> View Schedule
                        </button>
                    </div>

                    <!-- ASSIGN PANE -->
                    <div id="pane-assign" style="<?php echo $view_mode !== 'assign' ? 'display:none;' : ''; ?>">
                        <?php if (empty($faculty_classes)): ?>
                            <p style="color:var(--text-label);font-size:0.9rem;">No classes assigned yet.</p>
                        <?php else: ?>
                            <?php foreach ($faculty_classes as $fc): ?>
                            <div class="class-item">
                                <div class="class-item-info">
                                    <strong><?php echo htmlspecialchars($fc['subject_code']); ?></strong>
                                    <span class="class-name"><?php echo htmlspecialchars($fc['subject_name']); ?></span>
                                    <span class="class-meta">
                                        — Sec <?php echo htmlspecialchars($fc['section']); ?>,
                                        <?php echo htmlspecialchars($fc['semester'] . ' ' . $fc['school_year']); ?>
                                        <?php if ($fc['schedule_day']): ?>
                                            | <?php echo htmlspecialchars($fc['schedule_day'] . ' ' . $fc['schedule_time']); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <form method="POST" action="../../php/admin_faculty_handler.php" style="display:inline;">
                                    <input type="hidden" name="action"     value="unassign_class">
                                    <input type="hidden" name="class_id"   value="<?php echo $fc['class_id']; ?>">
                                    <input type="hidden" name="faculty_id" value="<?php echo $view_faculty_id; ?>">
                                    <button type="submit" class="btn-icon danger" title="Unassign"
                                        onclick="return confirm('Remove this faculty from the class?')">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="assign-row">
                            <p>Assign to a class:</p>
                            <form method="POST" action="../../php/admin_faculty_handler.php" class="assign-form">
                                <input type="hidden" name="action"     value="assign_class">
                                <input type="hidden" name="faculty_id" value="<?php echo $view_faculty_id; ?>">
                                <select name="class_id" class="filter-select" required>
                                    <option value="">— Select a class —</option>
                                    <?php foreach ($all_classes as $cls):
                                        $already = false;
                                        foreach ($faculty_classes as $fc) {
                                            if ($fc['class_id'] == $cls['class_id']) { $already = true; break; }
                                        }
                                        if ($already) continue;
                                    ?>
                                    <option value="<?php echo $cls['class_id']; ?>">
                                        <?php echo htmlspecialchars($cls['subject_code'] . ' — Sec ' . $cls['section'] . ' (' . $cls['semester'] . ' ' . $cls['school_year'] . ')'); ?>
                                        <?php if ($cls['current_faculty']): ?>[Currently: <?php echo htmlspecialchars($cls['current_faculty']); ?>]<?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-primary" style="padding:0.5rem 1rem;">
                                    <i class="fa-solid fa-plus"></i> Assign
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- SCHEDULE PANE -->
                    <div id="pane-schedule" style="<?php echo $view_mode !== 'schedule' ? 'display:none;' : ''; ?>">
                        <?php if (empty($faculty_classes)): ?>
                            <p style="color:var(--text-label);font-size:0.9rem;">No classes assigned — no schedule to display.</p>
                        <?php else: ?>
                        <div class="view-tabs" style="margin-bottom:1rem;">
                            <button class="view-tab active" id="stab-list" onclick="switchSched('list')">
                                <i class="fa-solid fa-list"></i> List
                            </button>
                            <button class="view-tab" id="stab-cal" onclick="switchSched('cal')">
                                <i class="fa-solid fa-calendar-week"></i> Calendar
                            </button>
                        </div>

                        <!-- LIST VIEW -->
                        <div id="sched-list">
                            <div class="table-responsive">
                                <table class="sched-table">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Section</th>
                                            <th>Day(s)</th>
                                            <th>Time</th>
                                            <th>Room</th>
                                            <th>Semester</th>
                                            <th>Units</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($faculty_classes as $cls): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($cls['subject_code']); ?></strong><br>
                                            <span class="subj-name"><?php echo htmlspecialchars($cls['subject_name']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($cls['section']); ?></td>
                                        <td><?php echo htmlspecialchars($cls['schedule_day'] ?: '—'); ?></td>
                                        <td><?php echo htmlspecialchars($cls['schedule_time'] ?: '—'); ?></td>
                                        <td><?php echo htmlspecialchars($cls['room'] ?: '—'); ?></td>
                                        <td><?php echo htmlspecialchars($cls['semester'] . ' ' . $cls['school_year']); ?></td>
                                        <td><?php echo htmlspecialchars($cls['units']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- CALENDAR VIEW -->
                        <div id="sched-cal" style="display:none;">
                            <div class="cal-grid">
                                <?php foreach ($week_days as $day): ?>
                                <div>
                                    <div class="cal-col-head"><?php echo $day; ?></div>
                                    <div class="cal-col">
                                        <?php if (empty($calendar[$day])): ?>
                                            <div class="cal-empty">—</div>
                                        <?php else: ?>
                                            <?php foreach ($calendar[$day] as $ev): ?>
                                            <div class="cal-event">
                                                <strong><?php echo htmlspecialchars($ev['subject_code']); ?></strong>
                                                <span><?php echo htmlspecialchars($ev['schedule_time'] ?: 'TBA'); ?></span><br>
                                                <span>Sec <?php echo htmlspecialchars($ev['section']); ?>
                                                    <?php if ($ev['room']): ?> · <?php echo htmlspecialchars($ev['room']); ?><?php endif; ?>
                                                </span>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <p class="cal-note">* Only classes with a schedule day set are shown on the calendar.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ── Faculty Table ──────────────────── -->
                <div class="card">
                    <div class="card-header">
                        <h2>All Faculty</h2>
                        <div class="card-header-actions">
                            <form method="GET" class="search-form-inline">
                                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                                <input type="text" name="search" placeholder="Search name, ID, department..." value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn-search"><i class="fa-solid fa-search"></i></button>
                                <?php if ($search): ?>
                                    <a href="?filter=<?php echo $filter; ?>" class="btn-secondary" style="padding:0.45rem 0.75rem;font-size:0.82rem;">Clear</a>
                                <?php endif; ?>
                            </form>
                            <button class="btn-secondary" onclick="openAdd()">
                                <i class="fa-solid fa-plus"></i>
                                <span class="li-name">Add Faculty</span>
                            </button>
                        </div>
                    </div>

                    <div class="filter-tabs">
                        <a href="?search=<?php echo urlencode($search); ?>&filter=all"      class="filter-tab <?php echo $filter==='all'     ? 'active':''; ?>">All</a>
                        <a href="?search=<?php echo urlencode($search); ?>&filter=active"   class="filter-tab <?php echo $filter==='active'  ? 'active':''; ?>">Active</a>
                        <a href="?search=<?php echo urlencode($search); ?>&filter=inactive" class="filter-tab <?php echo $filter==='inactive'? 'active':''; ?>">Inactive</a>
                    </div>

                    <div class="faculty-table-wrapper">
                        <div class="faculty-table">

                            <div class="faculty-table-header">
                                <div>Employee ID</div>
                                <div class="faculty-col-left">Name</div>
                                <div class="faculty-col-left">Email</div>
                                <div class="faculty-col-left">Department</div>
                                <div class="faculty-col-left">Position</div>
                                <div>Employment</div>
                                <div>Status</div>
                                <div>Actions</div>
                            </div>

                            <div class="faculty-table-body">
                            <?php if (mysqli_num_rows($faculty_list) === 0): ?>
                                <div class="faculty-empty">No faculty found.</div>
                            <?php else: ?>
                            <?php while ($fac = mysqli_fetch_assoc($faculty_list)):
                                $js = htmlspecialchars(json_encode($fac), ENT_QUOTES);
                            ?>
                            <div class="faculty-row">
                                <div><?php echo htmlspecialchars($fac['employee_id']); ?></div>
                                <div class="faculty-col-left">
                                    <strong><?php echo htmlspecialchars($fac['last_name'] . ', ' . $fac['first_name']); ?></strong>
                                    <?php if ($fac['middle_name']): ?>
                                        <br><small style="color:var(--text-label);"><?php echo htmlspecialchars($fac['middle_name']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="faculty-col-left"><?php echo htmlspecialchars($fac['email']); ?></div>
                                <div class="faculty-col-left"><?php echo htmlspecialchars($fac['department'] ?? '—'); ?></div>
                                <div class="faculty-col-left"><?php echo htmlspecialchars($fac['position'] ?? '—'); ?></div>
                                <div><span class="badge <?php echo $fac['employment_status']; ?>"><?php echo ucfirst(str_replace('-', ' ', $fac['employment_status'])); ?></span></div>
                                <div><span class="badge <?php echo $fac['status']; ?>"><?php echo ucfirst($fac['status']); ?></span></div>
                                <div>
                                    <div class="action-buttons">
                                        <button class="btn-icon" title="View Details" onclick="openView('<?php echo $js; ?>')"><i class="fa-solid fa-eye"></i></button>
                                        <a href="?faculty_id=<?php echo $fac['faculty_id']; ?>&view=assign"   class="btn-icon assign"   title="Assign to Class"><i class="fa-solid fa-chalkboard"></i></a>
                                        <a href="?faculty_id=<?php echo $fac['faculty_id']; ?>&view=schedule" class="btn-icon schedule" title="View Schedule"><i class="fa-solid fa-calendar-days"></i></a>
                                        <button class="btn-icon" title="Edit" onclick="openEdit('<?php echo $js; ?>')"><i class="fa-solid fa-pen-to-square"></i></button>
                                        <form method="POST" action="../../php/admin_faculty_handler.php" style="display:inline;"
                                            onsubmit="return confirm('Delete this faculty member?')">
                                            <input type="hidden" name="action"     value="delete">
                                            <input type="hidden" name="faculty_id" value="<?php echo $fac['faculty_id']; ?>">
                                            <button type="submit" class="btn-icon danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                            <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>

            </div><!-- /.main-content -->
        </main>
    </div><!-- /.main-flex -->

    <!-- ── View Faculty Modal ────────────────────────── -->
    <div id="viewModal" class="modal">
        <div class="modal-content wide">
            <span class="close" onclick="closeModal('viewModal')">&times;</span>

            <div class="modal-header">
                <div class="student-avatar-lg" id="view_avatar"></div>
                <div class="modal-header-info">
                    <h3 id="view_fullname"></h3>
                    <p id="view_employee_id"></p>
                    <p id="view_status_badge"></p>
                </div>
            </div>

            <div class="view-grid">
                <div class="section-title">Employment Information</div>
                <div class="view-item"><label>Position</label><span id="vw_position"></span></div>
                <div class="view-item"><label>College</label><span id="vw_college"></span></div>
                <div class="view-item"><label>Department</label><span id="vw_department"></span></div>
                <div class="view-item"><label>Employment Status</label><span id="vw_employment_status"></span></div>
                <div class="view-item"><label>PLM Email</label><span id="vw_email"></span></div>

                <div class="section-title">Personal Information</div>
                <div class="view-item"><label>First Name</label><span id="vw_first_name"></span></div>
                <div class="view-item"><label>Last Name</label><span id="vw_last_name"></span></div>
                <div class="view-item"><label>Middle Name</label><span id="vw_middle_name"></span></div>
                <div class="view-item"><label>Suffix</label><span id="vw_suffix_name"></span></div>
                <div class="view-item"><label>Date of Birth</label><span id="vw_date_of_birth"></span></div>
                <div class="view-item"><label>Place of Birth</label><span id="vw_place_of_birth"></span></div>
                <div class="view-item"><label>Sex</label><span id="vw_sex"></span></div>
                <div class="view-item"><label>Civil Status</label><span id="vw_civil_status"></span></div>
                <div class="view-item"><label>Religion</label><span id="vw_religion"></span></div>
                <div class="view-item"><label>Nationality</label><span id="vw_nationality"></span></div>
                <div class="view-item"><label>Disability</label><span id="vw_disability"></span></div>

                <div class="section-title">Contact</div>
                <div class="view-item"><label>Contact Number</label><span id="vw_phone"></span></div>
                <div class="view-item"><label>Personal Email</label><span id="vw_personal_email"></span></div>

                <div class="section-title">Permanent Address</div>
                <div class="view-item full"><label>Address</label><span id="vw_permanent_address_full"></span></div>

                <div class="section-title">Mailing Address</div>
                <div class="view-item full"><label>Address</label><span id="vw_mailing_address_full"></span></div>
            </div>
        </div>
    </div>

    <!-- ── Add / Edit Faculty Modal ──────────────────── -->
    <div id="formModal" class="modal">
        <div class="modal-content faculty-modal">
            <span class="close" onclick="document.getElementById('formModal').style.display='none'">&times;</span>
            <h2 id="formModalTitle">Add Faculty</h2>

            <form method="POST" action="../../php/admin_faculty_handler.php">
                <input type="hidden" name="action"     id="form_action"     value="add">
                <input type="hidden" name="faculty_id" id="form_faculty_id">

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Employee ID <span style="color:var(--red)">*</span></label>
                        <input type="text" name="employee_id" id="form_employee_id" placeholder="e.g., EMP2024001" required>
                    </div>
                    <div class="form-group">
                        <label>Email <span style="color:var(--red)">*</span></label>
                        <input type="email" name="email" id="form_email" placeholder="faculty@plm.edu.ph" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>First Name <span style="color:var(--red)">*</span></label>
                        <input type="text" name="first_name" id="form_first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name <span style="color:var(--red)">*</span></label>
                        <input type="text" name="last_name" id="form_last_name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" id="form_middle_name">
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="department" id="form_department" placeholder="e.g., Information Technology">
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" name="position" id="form_position" placeholder="e.g., Instructor I">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Employment Status</label>
                        <select name="employment_status" id="form_employment_status">
                            <option value="full-time">Full-time</option>
                            <option value="part-time">Part-time</option>
                            <option value="contractual">Contractual</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="form_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="on-leave">On Leave</option>
                        </select>
                    </div>
                </div>

                <p class="modal-hint" id="form_hint"></p>

                <div class="modal-actions">
                    <button type="submit" class="btn-submit" id="formSubmitBtn">Add Faculty</button>
                    <button type="button" class="btn-secondary" onclick="document.getElementById('formModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../js/admin/admin_main.js"></script>
    <script src="../../js/admin/admin_faculty.js"></script>
</body>
</html>