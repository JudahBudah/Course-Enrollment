<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status = 'pending'"))['c'];

// Flash messages
$flash_errors = [
    'missing_fields' => 'Please fill in all required fields.',
    'insert_failed'  => 'Failed to add faculty. Employee ID or email may already exist.',
    'update_failed'  => 'Failed to update faculty record.',
];
$flash = '';
if (isset($_GET['error']) && isset($flash_errors[$_GET['error']])) {
    $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> ' . $flash_errors[$_GET['error']] . '</div>';
}
if (isset($_GET['success'])) {
    $msgs = ['added' => 'Faculty added successfully.', 'updated' => 'Faculty updated successfully.', 'deleted' => 'Faculty deleted successfully.', 'assigned' => 'Faculty assigned to class.', 'unassigned' => 'Faculty unassigned from class.'];
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

// For assign modal: get all classes with subject info
$classes_query = mysqli_query($con, "SELECT c.class_id, c.section, c.semester, c.school_year, c.faculty_id,
    s.subject_code, s.subject_name,
    CONCAT(f.first_name,' ',f.last_name) as current_faculty
    FROM classes c
    JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    WHERE c.status = 'open'
    ORDER BY s.subject_code, c.section");
$all_classes = [];
while ($row = mysqli_fetch_assoc($classes_query)) $all_classes[] = $row;

// If viewing a specific faculty's assignments / schedule
$view_faculty_id = isset($_GET['faculty_id']) ? (int)$_GET['faculty_id'] : 0;
$view_mode = $_GET['view'] ?? 'assign'; // 'assign' | 'schedule'
$view_faculty = null;
$faculty_classes = [];
if ($view_faculty_id) {
    $vf = mysqli_prepare($con, "SELECT * FROM faculty WHERE faculty_id = ?");
    mysqli_stmt_bind_param($vf, "i", $view_faculty_id);
    mysqli_stmt_execute($vf);
    $view_faculty = mysqli_fetch_assoc(mysqli_stmt_get_result($vf));

    $fc = mysqli_prepare($con, "SELECT c.class_id, c.section, c.semester, c.school_year, c.schedule_day, c.schedule_time, c.room, s.subject_code, s.subject_name, s.units
        FROM classes c JOIN subjects s ON c.subject_id = s.subject_id
        WHERE c.faculty_id = ? ORDER BY s.subject_code");
    mysqli_stmt_bind_param($fc, "i", $view_faculty_id);
    mysqli_stmt_execute($fc);
    $fcr = mysqli_stmt_get_result($fc);
    while ($row = mysqli_fetch_assoc($fcr)) $faculty_classes[] = $row;
}

$week_days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

function parse_days(string $raw): array {
    $raw = strtoupper(trim($raw));
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
        'M'=>'Monday','T'=>'Tuesday','W'=>'Wednesday','TH'=>'Thursday','F'=>'Friday','S'=>'Saturday','SU'=>'Sunday',
        'MON'=>'Monday','TUE'=>'Tuesday','WED'=>'Wednesday','THU'=>'Thursday','FRI'=>'Friday','SAT'=>'Saturday','SUN'=>'Sunday',
        'MONDAY'=>'Monday','TUESDAY'=>'Tuesday','WEDNESDAY'=>'Wednesday','THURSDAY'=>'Thursday','FRIDAY'=>'Friday','SATURDAY'=>'Saturday','SUNDAY'=>'Sunday',
    ];
    $parts = preg_split('/[,\/\s]+/', $raw);
    $days = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if (isset($day_map[$p])) $days[] = $day_map[$p];
    }
    return array_unique($days);
}

$calendar = [];
foreach ($week_days as $d) $calendar[$d] = [];
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin.css">
    <style>
        .modal-content { max-width: 600px; max-height: 90vh; overflow-y: auto; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
        .badge.full-time  { background: rgba(34,197,94,0.2); color: #4ade80; }
        .badge.part-time  { background: rgba(251,146,60,0.2); color: #fb923c; }
        .badge.contractual{ background: rgba(168,85,247,0.2); color: #a855f7; }
        .badge.on-leave   { background: rgba(156,163,175,0.2); color: #9ca3af; }
        .panel { background: rgba(212,175,55,0.05); border: 1px solid rgba(212,175,55,0.15); border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .panel-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding-bottom:0.75rem; border-bottom:1px solid rgba(212,175,55,0.1); }
        .panel-header h3 { font-family:'Playfair Display',serif; font-size:1.1rem; }
        .class-item { display:flex; justify-content:space-between; align-items:center; padding:0.6rem 0.75rem; border-radius:4px; background:rgba(255,255,255,0.03); margin-bottom:0.4rem; }
        .class-item:last-child { margin-bottom:0; }
        .filter-select { padding: 0.45rem 0.75rem; background: var(--gray-lt); border: 1px solid rgba(212,175,55,0.2); color: var(--white); font-size: 0.85rem; border-radius: 4px; }
        .filter-select option { background: var(--gray); }
        .view-tabs { display:flex; gap:0.5rem; margin-bottom:1.25rem; }
        .view-tab { padding:0.4rem 1rem; border:1px solid rgba(212,175,55,0.25); border-radius:4px; background:transparent; color:rgba(242,243,242,0.6); cursor:pointer; font-size:0.85rem; transition:0.2s; font-family:'DM Sans',sans-serif; }
        .view-tab.active { background:rgba(212,175,55,0.15); border-color:var(--gold); color:var(--gold); }
        .sched-table { width:100%; border-collapse:collapse; font-size:0.85rem; }
        .sched-table th { padding:0.6rem 0.75rem; background:rgba(212,175,55,0.08); color:var(--gold); font-weight:600; text-align:left; border-bottom:1px solid rgba(212,175,55,0.15); }
        .sched-table td { padding:0.6rem 0.75rem; border-bottom:1px solid rgba(255,255,255,0.04); vertical-align:top; color:rgba(242,243,242,0.85); }
        .sched-table tr:last-child td { border-bottom:none; }
        .cal-grid { display:grid; grid-template-columns:repeat(6,1fr); gap:0.5rem; }
        .cal-col-head { text-align:center; font-size:0.75rem; font-weight:700; color:var(--gold); padding:0.4rem 0; background:rgba(212,175,55,0.08); border-radius:4px 4px 0 0; }
        .cal-col { background:rgba(255,255,255,0.02); border:1px solid rgba(212,175,55,0.1); border-radius:0 0 4px 4px; min-height:120px; padding:0.4rem; }
        .cal-event { background:rgba(212,175,55,0.15); border-left:3px solid var(--gold); border-radius:3px; padding:0.35rem 0.5rem; margin-bottom:0.35rem; font-size:0.75rem; line-height:1.4; }
        .cal-event:last-child { margin-bottom:0; }
        .cal-event strong { display:block; color:var(--white); }
        .cal-event span { color:rgba(242,243,242,0.55); }
        .cal-empty { color:rgba(242,243,242,0.2); font-size:0.75rem; text-align:center; padding-top:1rem; }
    </style>
</head>
<body class="dashboard">
    <nav class="dashboard-nav">
        <div class="nav-brand">
            <img src="../../assets/plm-logo.png" alt="PLM">
            <span>PLM Admin Portal</span>
        </div>
        <div class="nav-user">
            <span><?php echo htmlspecialchars($admin_data['username'] ?? 'Admin'); ?></span>
            <div class="user-avatar"><?php echo strtoupper(substr($admin_data['username'] ?? 'A', 0, 1)); ?></div>
        </div>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <a href="admin_home.php" class="sidebar-link"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
            <a href="admin_applicants.php" class="sidebar-link">
                <i class="fa-solid fa-user-plus"></i><span>Applicants</span>
                <?php if ($pending_applicants > 0): ?><span class="badge"><?php echo $pending_applicants; ?></span><?php endif; ?>
            </a>
            <a href="admin_students.php" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Students</span></a>
            <a href="admin_blocks.php" class="sidebar-link"><i class="fa-solid fa-layer-group"></i><span>Blocks</span></a>
            <a href="admin_faculty.php" class="sidebar-link active"><i class="fa-solid fa-chalkboard-user"></i><span>Faculty</span></a>
            <a href="admin_subjects.php" class="sidebar-link"><i class="fa-solid fa-book"></i><span>Subjects</span></a>
            <a href="admin_classes.php" class="sidebar-link"><i class="fa-solid fa-door-open"></i><span>Classes</span></a>
            <a href="admin_enrollments.php" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>Enrollments</span></a>
            <a href="admin_announcements.php" class="sidebar-link"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
            <a href="admin_calendar.php" class="sidebar-link"><i class="fa-solid fa-calendar-days"></i><span>Calendar</span></a>
            <a href="admin_accounts.php" class="sidebar-link"><i class="fa-solid fa-user-shield"></i><span>Admin Accounts</span></a>
            <a href="../../php/admin_logout.php" class="sidebar-link logout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Faculty Management</h1>
                <p>Manage faculty members and assign them to classes</p>
            </div>

            <?php echo $flash; ?>

            <!-- Stats -->
            <div class="stats-grid" style="margin-bottom:1.5rem;">
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

            <?php if ($view_faculty): ?>
            <div class="panel">
                <div class="panel-header">
                    <h3>
                        <i class="fa-solid fa-chalkboard-user" style="color:var(--gold);margin-right:0.5rem;"></i>
                        <?php echo htmlspecialchars($view_faculty['first_name'] . ' ' . $view_faculty['last_name']); ?>
                    </h3>
                    <a href="admin_faculty.php" class="btn-secondary" style="padding:0.4rem 0.75rem;font-size:0.8rem;">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>
                </div>

                <div class="view-tabs">
                    <button class="view-tab <?php echo $view_mode==='assign'?'active':''; ?>" onclick="switchMode('assign')">
                        <i class="fa-solid fa-chalkboard"></i> Assign Classes
                    </button>
                    <button class="view-tab <?php echo $view_mode==='schedule'?'active':''; ?>" onclick="switchMode('schedule')">
                        <i class="fa-solid fa-calendar-days"></i> View Schedule
                    </button>
                </div>

                <!-- ASSIGN PANE -->
                <div id="pane-assign" style="<?php echo $view_mode!=='assign'?'display:none;':''; ?>">
                    <?php if (empty($faculty_classes)): ?>
                        <p style="color:rgba(242,243,242,0.4);font-size:0.9rem;">No classes assigned yet.</p>
                    <?php else: ?>
                        <?php foreach ($faculty_classes as $fc): ?>
                        <div class="class-item">
                            <div>
                                <strong><?php echo htmlspecialchars($fc['subject_code']); ?></strong>
                                <span style="color:rgba(242,243,242,0.6);margin-left:0.5rem;"><?php echo htmlspecialchars($fc['subject_name']); ?></span>
                                <span style="color:rgba(242,243,242,0.4);font-size:0.8rem;margin-left:0.5rem;">
                                    — Sec <?php echo htmlspecialchars($fc['section']); ?>, <?php echo htmlspecialchars($fc['semester'] . ' ' . $fc['school_year']); ?>
                                    <?php if ($fc['schedule_day']): ?> | <?php echo htmlspecialchars($fc['schedule_day'] . ' ' . $fc['schedule_time']); ?><?php endif; ?>
                                </span>
                            </div>
                            <form method="POST" action="../../php/admin_faculty_handler.php" style="display:inline;">
                                <input type="hidden" name="action" value="unassign_class">
                                <input type="hidden" name="class_id" value="<?php echo $fc['class_id']; ?>">
                                <input type="hidden" name="faculty_id" value="<?php echo $view_faculty_id; ?>">
                                <button type="submit" class="btn-icon" title="Unassign" style="color:#ef4444;border-color:#ef4444;"
                                    onclick="return confirm('Remove this faculty from the class?')">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid rgba(212,175,55,0.1);">
                        <p style="font-size:0.85rem;color:rgba(242,243,242,0.6);margin-bottom:0.75rem;">Assign to a class:</p>
                        <form method="POST" action="../../php/admin_faculty_handler.php" style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                            <input type="hidden" name="action" value="assign_class">
                            <input type="hidden" name="faculty_id" value="<?php echo $view_faculty_id; ?>">
                            <select name="class_id" class="filter-select" style="flex:1;min-width:280px;" required>
                                <option value="">— Select a class —</option>
                                <?php foreach ($all_classes as $cls):
                                    $already = false;
                                    foreach ($faculty_classes as $fc) { if ($fc['class_id'] == $cls['class_id']) { $already = true; break; } }
                                    if ($already) continue;
                                ?>
                                <option value="<?php echo $cls['class_id']; ?>">
                                    <?php echo htmlspecialchars($cls['subject_code'] . ' — Sec ' . $cls['section'] . ' (' . $cls['semester'] . ' ' . $cls['school_year'] . ')'); ?>
                                    <?php if ($cls['current_faculty']): ?>[Currently: <?php echo htmlspecialchars($cls['current_faculty']); ?>]<?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-primary" style="padding:0.5rem 1rem;"><i class="fa-solid fa-plus"></i> Assign</button>
                        </form>
                    </div>
                </div>

                <!-- SCHEDULE PANE -->
                <div id="pane-schedule" style="<?php echo $view_mode!=='schedule'?'display:none;':''; ?>">
                    <?php if (empty($faculty_classes)): ?>
                        <p style="color:rgba(242,243,242,0.4);font-size:0.9rem;">No classes assigned — no schedule to display.</p>
                    <?php else: ?>
                    <div class="view-tabs" style="margin-bottom:1rem;">
                        <button class="view-tab active" id="stab-list" onclick="switchSched('list')"><i class="fa-solid fa-list"></i> List</button>
                        <button class="view-tab" id="stab-cal" onclick="switchSched('cal')"><i class="fa-solid fa-calendar-week"></i> Calendar</button>
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
                                        <span style="color:rgba(242,243,242,0.5);font-size:0.8rem;"><?php echo htmlspecialchars($cls['subject_name']); ?></span>
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
                                            <span>Sec <?php echo htmlspecialchars($ev['section']); ?><?php if ($ev['room']): ?> · <?php echo htmlspecialchars($ev['room']); ?><?php endif; ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p style="font-size:0.75rem;color:rgba(242,243,242,0.3);margin-top:0.75rem;">* Only classes with a schedule day set are shown on the calendar.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Faculty Table -->
            <div class="card">
                <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
                    <h2>All Faculty</h2>
                    <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                        <form method="GET" style="display:flex;gap:0.5rem;align-items:center;">
                            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                            <div class="search-bar" style="min-width:0;">
                                <i class="fa-solid fa-search"></i>
                                <input type="text" name="search" placeholder="Search name, ID, department..." value="<?php echo htmlspecialchars($search); ?>" style="min-width:220px;">
                            </div>
                            <button type="submit" class="btn-secondary" style="padding:0.5rem 0.75rem;"><i class="fa-solid fa-search"></i></button>
                            <?php if ($search): ?><a href="?filter=<?php echo $filter; ?>" class="btn-secondary" style="padding:0.5rem 0.75rem;">Clear</a><?php endif; ?>
                        </form>
                        <button class="btn-secondary" onclick="openAdd()"><i class="fa-solid fa-plus"></i> Add Faculty</button>
                    </div>
                </div>

                <div class="filter-tabs">
                    <a href="?search=<?php echo urlencode($search); ?>&filter=all"      class="filter-tab <?php echo $filter==='all'?'active':''; ?>">All</a>
                    <a href="?search=<?php echo urlencode($search); ?>&filter=active"   class="filter-tab <?php echo $filter==='active'?'active':''; ?>">Active</a>
                    <a href="?search=<?php echo urlencode($search); ?>&filter=inactive" class="filter-tab <?php echo $filter==='inactive'?'active':''; ?>">Inactive</a>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Employment</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (mysqli_num_rows($faculty_list) === 0): ?>
                            <tr><td colspan="8" style="text-align:center;color:rgba(242,243,242,0.4);padding:2rem;">No faculty found.</td></tr>
                        <?php else: ?>
                        <?php while ($fac = mysqli_fetch_assoc($faculty_list)):
                            $js = htmlspecialchars(json_encode($fac), ENT_QUOTES);
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fac['employee_id']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($fac['last_name'] . ', ' . $fac['first_name']); ?></strong>
                                    <?php if ($fac['middle_name']): ?>
                                        <br><small style="color:rgba(242,243,242,0.5);"><?php echo htmlspecialchars($fac['middle_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($fac['email']); ?></td>
                                <td><?php echo htmlspecialchars($fac['department'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($fac['position'] ?? '—'); ?></td>
                                <td><span class="badge <?php echo $fac['employment_status']; ?>"><?php echo ucfirst($fac['employment_status']); ?></span></td>
                                <td><span class="badge <?php echo $fac['status']; ?>"><?php echo ucfirst($fac['status']); ?></span></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?faculty_id=<?php echo $fac['faculty_id']; ?>&view=assign" class="btn-icon" title="Assign to Class" style="color:#60a5fa;border-color:#60a5fa;">
                                            <i class="fa-solid fa-chalkboard"></i>
                                        </a>
                                        <a href="?faculty_id=<?php echo $fac['faculty_id']; ?>&view=schedule" class="btn-icon" title="View Schedule" style="color:#4ade80;border-color:#4ade80;">
                                            <i class="fa-solid fa-calendar-days"></i>
                                        </a>
                                        <button class="btn-icon" title="Edit" onclick="openEdit('<?php echo $js; ?>')">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form method="POST" action="../../php/admin_faculty_handler.php" style="display:inline;"
                                            onsubmit="return confirm('Delete this faculty member?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="faculty_id" value="<?php echo $fac['faculty_id']; ?>">
                                            <button type="submit" class="btn-icon" title="Delete" style="color:#ef4444;border-color:#ef4444;">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

<!-- ADD/EDIT MODAL -->
<div id="formModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('formModal').style.display='none'">&times;</span>
        <h2 style="font-family:'Playfair Display',serif;margin-bottom:1.5rem;" id="formModalTitle">Add Faculty</h2>

        <form method="POST" action="../../php/admin_faculty_handler.php">
            <input type="hidden" name="action" id="form_action" value="add">
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

            <p id="form_hint" style="font-size:0.78rem;color:rgba(242,243,242,0.4);margin-bottom:1rem;"></p>

            <div style="display:flex;gap:1rem;margin-top:0.5rem;">
                <button type="submit" class="btn-submit" style="flex:1;" id="formSubmitBtn">Add Faculty</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('formModal').style.display='none'" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    window.onclick = function(e) {
        const m = document.getElementById('formModal');
        if (e.target === m) m.style.display = 'none';
    };

    function openAdd() {
        document.getElementById('formModalTitle').textContent = 'Add Faculty';
        document.getElementById('formSubmitBtn').textContent = 'Add Faculty';
        document.getElementById('form_action').value = 'add';
        document.getElementById('form_faculty_id').value = '';
        document.getElementById('form_hint').textContent = 'Default password will be set to the Employee ID.';
        ['employee_id','email','first_name','last_name','middle_name','department','position'].forEach(f => {
            document.getElementById('form_' + f).value = '';
        });
        document.getElementById('form_employment_status').value = 'full-time';
        document.getElementById('form_status').value = 'active';
        document.getElementById('formModal').style.display = 'block';
    }

    function switchMode(mode) {
        document.getElementById('pane-assign').style.display   = mode === 'assign'   ? '' : 'none';
        document.getElementById('pane-schedule').style.display = mode === 'schedule' ? '' : 'none';
        document.querySelectorAll('.panel > .view-tabs .view-tab').forEach((t, i) => {
            t.classList.toggle('active', (mode === 'assign' && i === 0) || (mode === 'schedule' && i === 1));
        });
    }

    function switchSched(tab) {
        document.getElementById('sched-list').style.display = tab === 'list' ? '' : 'none';
        document.getElementById('sched-cal').style.display  = tab === 'cal'  ? '' : 'none';
        document.getElementById('stab-list').classList.toggle('active', tab === 'list');
        document.getElementById('stab-cal').classList.toggle('active',  tab === 'cal');
    }

    function openEdit(raw) {
        const f = JSON.parse(raw);
        document.getElementById('formModalTitle').textContent = 'Edit Faculty';
        document.getElementById('formSubmitBtn').textContent = 'Save Changes';
        document.getElementById('form_action').value = 'edit';
        document.getElementById('form_hint').textContent = '';
        ['faculty_id','employee_id','email','first_name','last_name','middle_name','department','position','employment_status','status'].forEach(k => {
            const el = document.getElementById('form_' + k);
            if (el) el.value = f[k] ?? '';
        });
        document.getElementById('formModal').style.display = 'block';
    }
</script>
</body>
</html>
