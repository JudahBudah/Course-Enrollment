<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status = 'pending'"))['c'];

// Flash messages
$flash_errors = [
    'missing_fields' => 'Please fill in all required fields.',
    'insert_failed' => 'Failed to create class. Please try again.',
    'update_failed' => 'Failed to update class. Please try again.',
    'has_enrollments' => 'Cannot delete — class has enrolled students.',
];
$flash = '';
if (isset($_GET['error']) && isset($flash_errors[$_GET['error']])) {
    $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> ' . $flash_errors[$_GET['error']] . '</div>';
}
if (isset($_GET['success'])) {
    $msgs = ['added' => 'Class created successfully.', 'updated' => 'Class updated successfully.', 'deleted' => 'Class deleted successfully.'];
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> ' . ($msgs[$_GET['success']] ?? 'Done.') . '</div>';
}

// Stats
$total_classes = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM classes"))['c'];
$open_classes = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM classes WHERE status = 'open'"))['c'];
$closed_classes = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM classes WHERE status = 'closed'"))['c'];
$total_enrolled = mysqli_fetch_assoc(mysqli_query($con, "SELECT SUM(enrolled_count) as s FROM classes"))['s'] ?? 0;

// Search & filter
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$semester_filter = $_GET['semester'] ?? '';
$year_filter = $_GET['year'] ?? '';

$where = "WHERE 1=1";
if ($filter === 'open') $where .= " AND c.status = 'open'";
if ($filter === 'closed') $where .= " AND c.status = 'closed'";
if ($filter === 'cancelled') $where .= " AND c.status = 'cancelled'";
if ($semester_filter !== '') $where .= " AND c.semester = '" . mysqli_real_escape_string($con, $semester_filter) . "'";
if ($year_filter !== '') $where .= " AND s.year_level = '" . mysqli_real_escape_string($con, $year_filter) . "'";
if ($search !== '') {
    $s = mysqli_real_escape_string($con, $search);
    $where .= " AND (s.subject_code LIKE '%$s%' OR s.subject_name LIKE '%$s%' OR c.section LIKE '%$s%' OR c.room LIKE '%$s%')";
}

$query = "SELECT c.*, s.subject_code, s.subject_name, s.units, s.year_level,
                 CONCAT(f.first_name, ' ', f.last_name) as faculty_name
          FROM classes c
          JOIN subjects s ON c.subject_id = s.subject_id
          LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
          $where
          ORDER BY c.school_year DESC, c.semester ASC, s.subject_code ASC";
$classes = mysqli_query($con, $query);

// Get subjects for dropdown
$subjects_query = mysqli_query($con, "SELECT subject_id, subject_code, subject_name, year_level, semester, department FROM subjects WHERE status = 'active' ORDER BY year_level, semester, subject_code");
$subjects = [];
while ($s = mysqli_fetch_assoc($subjects_query)) $subjects[] = $s;

// Get unique departments/courses for filter
$dept_query = mysqli_query($con, "SELECT DISTINCT department FROM subjects WHERE status = 'active' AND department IS NOT NULL AND department != '' ORDER BY department");
$departments = [];
while ($d = mysqli_fetch_assoc($dept_query)) $departments[] = $d['department'];

// Get faculty for dropdown
$faculty_query = mysqli_query($con, "SELECT faculty_id, first_name, last_name FROM faculty WHERE status = 'active' ORDER BY last_name, first_name");
$faculty = [];
while ($f = mysqli_fetch_assoc($faculty_query)) $faculty[] = $f;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes Management - PLM Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin.css">
    <style>
        .modal-content { max-width: 700px; max-height: 90vh; overflow-y: auto; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0 1rem; }
        .badge.open { background: rgba(34,197,94,0.2); color: #4ade80; }
        .badge.closed { background: rgba(239,68,68,0.2); color: #ef4444; }
        .badge.cancelled { background: rgba(156,163,175,0.2); color: #9ca3af; }
        .capacity-badge { display: inline-block; padding: 0.25rem 0.6rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
        .capacity-badge.available { background: rgba(34,197,94,0.15); color: #4ade80; }
        .capacity-badge.full { background: rgba(239,68,68,0.15); color: #ef4444; }
        .capacity-badge.almost { background: rgba(251,146,60,0.15); color: #fb923c; }
        .filter-select { padding: 0.45rem 0.75rem; background: var(--gray-lt); border: 1px solid rgba(212,175,55,0.2); color: var(--white); font-size: 0.85rem; border-radius: 4px; }
        .filter-select option { background: var(--gray); }
        .filter-box { background: rgba(212,175,55,0.1); border-left: 3px solid var(--gold); padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
        .filter-box label { font-size: 0.85rem; color: rgba(242,243,242,0.7); margin-bottom: 0.5rem; display: block; }
        #form_subject_id option { padding: 0.5rem; }
        #form_subject_id option:hover { background: rgba(212,175,55,0.2); }
    </style>
</head>
<body class="dashboard">
    <nav class="dashboard-nav">
        <div class="nav-brand">
            <img src="../../assets/plm-logo.png" alt="PLM">
            <span>PLM Admin Portal</span>
        </div>
        <div class="nav-user">
            <span><?php echo htmlspecialchars(($admin_data['username'] ?? 'Admin')); ?></span>
            <div class="user-avatar"><?php echo strtoupper(substr($admin_data['username'] ?? 'A', 0, 1)); ?></div>
        </div>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <a href="admin_home.php" class="sidebar-link">
                <i class="fa-solid fa-house"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin_applicants.php" class="sidebar-link">
                <i class="fa-solid fa-user-plus"></i>
                <span>Applicants</span>
                <?php if ($pending_applicants > 0): ?>
                    <span class="badge"><?php echo $pending_applicants; ?></span>
                <?php endif; ?>
            </a>
            <a href="admin_students.php" class="sidebar-link">
                <i class="fa-solid fa-users"></i>
                <span>Students</span>
            </a>
              <a href="admin_blocks.php" class="sidebar-link">
                <i class="fa-solid fa-layer-group"></i>
                <span>Blocks</span>
            </a>
            <a href="admin_faculty.php" class="sidebar-link">
                <i class="fa-solid fa-chalkboard-user"></i>
                <span>Faculty</span>
            </a>
            <a href="admin_subjects.php" class="sidebar-link">  
                <i class="fa-solid fa-book"></i>
                <span>Subjects</span>
            </a>    
            <a href="admin_classes.php" class="sidebar-link active">
                <i class="fa-solid fa-door-open"></i>
                <span>Classes</span>
            </a>
            <a href="admin_enrollments.php" class="sidebar-link">
                <i class="fa-solid fa-file-lines"></i>
                <span>Enrollments</span>
            </a>
            <a href="admin_announcements.php" class="sidebar-link">
                <i class="fa-solid fa-bullhorn"></i>
                <span>Announcements</span>
            </a>
            <a href="admin_calendar.php" class="sidebar-link">
                <i class="fa-solid fa-calendar-days"></i>
                <span>Calendar</span>
            </a>
            <a href="admin_accounts.php" class="sidebar-link">
                <i class="fa-solid fa-user-shield"></i>
                <span>Admin Accounts</span>
            </a>
            <a href="../../php/admin_logout.php" class="sidebar-link logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Classes Management</h1>
                <p>Manage class schedules, sections, and capacity</p>
            </div>

            <?php echo $flash; ?>

            <!-- Stats -->
            <div class="stats-grid" style="margin-bottom:1.5rem;">
                <div class="stat-card blue">
                    <div class="stat-icon"><i class="fa-solid fa-door-open"></i></div>
                    <div class="stat-content"><h3>Total Classes</h3><p class="stat-number"><?php echo $total_classes; ?></p></div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="stat-content"><h3>Open Classes</h3><p class="stat-number"><?php echo $open_classes; ?></p></div>
                </div>
                <div class="stat-card red">
                    <div class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></div>
                    <div class="stat-content"><h3>Closed</h3><p class="stat-number"><?php echo $closed_classes; ?></p></div>
                </div>
                <div class="stat-card gold">
                    <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-content"><h3>Total Enrolled</h3><p class="stat-number"><?php echo $total_enrolled; ?></p></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
                    <h2>All Classes</h2>
                    <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                        <form method="GET" style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                            <select name="year" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Years</option>
                                <option value="1" <?php echo $year_filter==='1'?'selected':''; ?>>1st Year</option>
                                <option value="2" <?php echo $year_filter==='2'?'selected':''; ?>>2nd Year</option>
                                <option value="3" <?php echo $year_filter==='3'?'selected':''; ?>>3rd Year</option>
                                <option value="4" <?php echo $year_filter==='4'?'selected':''; ?>>4th Year</option>
                            </select>
                            <select name="semester" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Semesters</option>
                                <option value="1st" <?php echo $semester_filter==='1st'?'selected':''; ?>>1st Semester</option>
                                <option value="2nd" <?php echo $semester_filter==='2nd'?'selected':''; ?>>2nd Semester</option>
                                <option value="summer" <?php echo $semester_filter==='summer'?'selected':''; ?>>Summer</option>
                            </select>
                            <div class="search-bar" style="min-width:0;">
                                <i class="fa-solid fa-search"></i>
                                <input type="text" name="search" placeholder="Search code, section, room..." value="<?php echo htmlspecialchars($search); ?>" style="min-width:220px;">
                            </div>
                            <button type="submit" class="btn-secondary" style="padding:0.5rem 0.75rem;"><i class="fa-solid fa-search"></i></button>
                            <?php if ($search || $semester_filter || $year_filter): ?><a href="?filter=<?php echo $filter; ?>" class="btn-secondary" style="padding:0.5rem 0.75rem;">Clear</a><?php endif; ?>
                        </form>
                        <button class="btn-secondary" onclick="openAdd()"><i class="fa-solid fa-plus"></i> Add Class</button>
                    </div>
                </div>

                <div class="filter-tabs">
                    <a href="?search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester_filter); ?>&year=<?php echo urlencode($year_filter); ?>&filter=all" class="filter-tab <?php echo $filter==='all'?'active':''; ?>">All</a>
                    <a href="?search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester_filter); ?>&year=<?php echo urlencode($year_filter); ?>&filter=open" class="filter-tab <?php echo $filter==='open'?'active':''; ?>">Open</a>
                    <a href="?search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester_filter); ?>&year=<?php echo urlencode($year_filter); ?>&filter=closed" class="filter-tab <?php echo $filter==='closed'?'active':''; ?>">Closed</a>
                    <a href="?search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester_filter); ?>&year=<?php echo urlencode($year_filter); ?>&filter=cancelled" class="filter-tab <?php echo $filter==='cancelled'?'active':''; ?>">Cancelled</a>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Section</th>
                                <th>Faculty</th>
                                <th>Schedule</th>
                                <th>Room</th>
                                <th>Capacity</th>
                                <th>Semester</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (mysqli_num_rows($classes) === 0): ?>
                            <tr><td colspan="9" style="text-align:center;color:rgba(242,243,242,0.4);padding:2rem;">No classes found.</td></tr>
                        <?php else: ?>
                        <?php while ($cls = mysqli_fetch_assoc($classes)):
                            $capacity_pct = $cls['max_slots'] > 0 ? ($cls['enrolled_count'] / $cls['max_slots']) * 100 : 0;
                            $capacity_class = $capacity_pct >= 100 ? 'full' : ($capacity_pct >= 80 ? 'almost' : 'available');
                            $js = htmlspecialchars(json_encode($cls), ENT_QUOTES);
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($cls['subject_code']); ?></strong><br>
                                    <small style="color:rgba(242,243,242,0.6);"><?php echo htmlspecialchars($cls['subject_name']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($cls['section']); ?></td>
                                <td><?php echo htmlspecialchars($cls['faculty_name'] ?? 'TBA'); ?></td>
                                <td>
                                    <?php if ($cls['schedule_day']): ?>
                                        <?php echo htmlspecialchars($cls['schedule_day']); ?><br>
                                        <small style="color:rgba(242,243,242,0.6);"><?php echo htmlspecialchars($cls['schedule_time'] ?? ''); ?></small>
                                    <?php else: ?>
                                        <span style="color:rgba(242,243,242,0.3);">TBA</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($cls['room'] ?? 'TBA'); ?></td>
                                <td>
                                    <span class="capacity-badge <?php echo $capacity_class; ?>">
                                        <?php echo $cls['enrolled_count']; ?>/<?php echo $cls['max_slots']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($cls['semester'] . ' ' . $cls['school_year']); ?></td>
                                <td><span class="badge <?php echo $cls['status']; ?>"><?php echo ucfirst($cls['status']); ?></span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-icon" title="Edit" onclick="openEdit('<?php echo $js; ?>')"><i class="fa-solid fa-pen-to-square"></i></button>
                                        <form method="POST" action="../../php/admin_classes_handler.php" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="class_id" value="<?php echo $cls['class_id']; ?>">
                                            <input type="hidden" name="new_status" value="<?php echo $cls['status']==='open'?'closed':'open'; ?>">
                                            <button type="submit" class="btn-icon" title="<?php echo $cls['status']==='open'?'Close':'Open'; ?>">
                                                <i class="fa-solid <?php echo $cls['status']==='open'?'fa-lock':'fa-lock-open'; ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="../../php/admin_classes_handler.php" style="display:inline;" onsubmit="return confirm('Delete this class? This cannot be undone.')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="class_id" value="<?php echo $cls['class_id']; ?>">
                                            <button type="submit" class="btn-icon" title="Delete" style="color:#ef4444;border-color:#ef4444;"><i class="fa-solid fa-trash"></i></button>
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
        <span class="close" onclick="closeModal('formModal')">&times;</span>
        <h2 style="font-family:'Playfair Display',serif;margin-bottom:1.5rem;" id="formModalTitle">Add Class</h2>

        <form method="POST" action="../../php/admin_classes_handler.php">
            <input type="hidden" name="action" id="form_action" value="add">
            <input type="hidden" name="class_id" id="form_class_id">

            <!-- Filter Section -->
            <div style="background:rgba(212,175,55,0.1);padding:1rem;border-radius:6px;margin-bottom:1.5rem;">
                <label style="font-size:0.85rem;color:rgba(242,243,242,0.7);margin-bottom:0.5rem;display:block;">
                    <i class="fa-solid fa-filter"></i> Filter Subjects by:
                </label>
                <div class="form-grid-3">
                    <div>
                        <select id="filter_dept" class="filter-select" style="width:100%;" onchange="filterSubjects()">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <select id="filter_year" class="filter-select" style="width:100%;" onchange="filterSubjects()">
                            <option value="">All Years</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                    <div>
                        <select id="filter_sem" class="filter-select" style="width:100%;" onchange="filterSubjects()">
                            <option value="">All Semesters</option>
                            <option value="1st">1st Semester</option>
                            <option value="2nd">2nd Semester</option>
                            <option value="summer">Summer</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:0.5rem;font-size:0.75rem;color:rgba(242,243,242,0.5);">
                    <span id="filter_count">Showing all subjects</span>
                </div>
            </div>

            <div class="form-group">
                <label>Subject <span style="color:var(--red)">*</span></label>
                <select name="subject_id" id="form_subject_id" required style="max-height:200px;">
                    <option value="">Select Subject</option>
                    <?php foreach ($subjects as $subj): ?>
                        <option value="<?php echo $subj['subject_id']; ?>" 
                                data-year="<?php echo $subj['year_level']; ?>" 
                                data-sem="<?php echo $subj['semester']; ?>"
                                data-dept="<?php echo htmlspecialchars($subj['department']); ?>">
                            <?php echo htmlspecialchars($subj['subject_code'] . ' - ' . $subj['subject_name']); ?>
                            <?php if ($subj['year_level']): ?>
                                (Year <?php echo $subj['year_level']; ?><?php echo $subj['semester'] ? ', ' . $subj['semester'] : ''; ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Section <span style="color:var(--red)">*</span></label>
                    <input type="text" name="section" id="form_section" placeholder="e.g., BSIT 1-A" required>
                </div>
                <div class="form-group">
                    <label>Faculty</label>
                    <select name="faculty_id" id="form_faculty_id">
                        <option value="">TBA</option>
                        <?php foreach ($faculty as $fac): ?>
                            <option value="<?php echo $fac['faculty_id']; ?>">
                                <?php echo htmlspecialchars($fac['last_name'] . ', ' . $fac['first_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>School Year <span style="color:var(--red)">*</span></label>
                    <input type="text" name="school_year" id="form_school_year" placeholder="e.g., 2024-2025" value="2024-2025" required>
                </div>
                <div class="form-group">
                    <label>Semester <span style="color:var(--red)">*</span></label>
                    <select name="semester" id="form_semester" required>
                        <option value="">Select Semester</option>
                        <option value="1st">1st Semester</option>
                        <option value="2nd">2nd Semester</option>
                        <option value="summer">Summer</option>
                    </select>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Schedule Day</label>
                    <input type="text" name="schedule_day" id="form_schedule_day" placeholder="e.g., Monday, MW, TTH">
                </div>
                <div class="form-group">
                    <label>Schedule Time</label>
                    <input type="text" name="schedule_time" id="form_schedule_time" placeholder="e.g., 8:00 AM - 10:00 AM">
                </div>
            </div>

            <div class="form-grid-3">
                <div class="form-group">
                    <label>Room</label>
                    <input type="text" name="room" id="form_room" placeholder="e.g., GV 208">
                </div>
                <div class="form-group">
                    <label>Max Slots <span style="color:var(--red)">*</span></label>
                    <input type="number" name="max_slots" id="form_max_slots" min="1" max="100" value="40" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="form_status">
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <div style="display:flex;gap:1rem;margin-top:1.5rem;">
                <button type="submit" class="btn-submit" style="flex:1;" id="formSubmitBtn">Create Class</button>
                <button type="button" class="btn-secondary" onclick="closeModal('formModal')" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    window.onclick = function(e) {
        const m = document.getElementById('formModal');
        if (e.target === m) m.style.display = 'none';
    }

    // Filter subjects in the dropdown
    function filterSubjects() {
        const deptFilter = document.getElementById('filter_dept').value.toLowerCase();
        const yearFilter = document.getElementById('filter_year').value;
        const semFilter = document.getElementById('filter_sem').value.toLowerCase();
        const select = document.getElementById('form_subject_id');
        const options = select.querySelectorAll('option');
        
        let visibleCount = 0;
        
        options.forEach((option, index) => {
            if (index === 0) return; // Skip "Select Subject" option
            
            const dept = (option.dataset.dept || '').toLowerCase();
            const year = option.dataset.year || '';
            const sem = (option.dataset.sem || '').toLowerCase();
            
            let show = true;
            
            if (deptFilter && !dept.includes(deptFilter)) show = false;
            if (yearFilter && year !== yearFilter) show = false;
            if (semFilter && sem !== semFilter) show = false;
            
            option.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        
        // Update count
        const countEl = document.getElementById('filter_count');
        if (deptFilter || yearFilter || semFilter) {
            countEl.textContent = `Showing ${visibleCount} subject${visibleCount !== 1 ? 's' : ''}`;
        } else {
            countEl.textContent = 'Showing all subjects';
        }
    }

    function openAdd() {
        document.getElementById('formModalTitle').textContent = 'Add Class';
        document.getElementById('formSubmitBtn').textContent = 'Create Class';
        document.getElementById('form_action').value = 'add';
        document.getElementById('form_class_id').value = '';
        ['subject_id','section','faculty_id','schedule_day','schedule_time','room'].forEach(f => {
            const el = document.getElementById('form_'+f);
            if (el) el.value = '';
        });
        document.getElementById('form_school_year').value = '2024-2025';
        document.getElementById('form_semester').value = '';
        document.getElementById('form_max_slots').value = 40;
        document.getElementById('form_status').value = 'open';
        
        // Reset filters
        document.getElementById('filter_dept').value = '';
        document.getElementById('filter_year').value = '';
        document.getElementById('filter_sem').value = '';
        filterSubjects();
        
        document.getElementById('formModal').style.display = 'block';
    }

    function openEdit(raw) {
        const c = JSON.parse(raw);
        document.getElementById('formModalTitle').textContent = 'Edit Class';
        document.getElementById('formSubmitBtn').textContent = 'Save Changes';
        document.getElementById('form_action').value = 'edit';
        document.getElementById('form_class_id').value = c.class_id;
        ['subject_id','section','faculty_id','school_year','semester','schedule_day','schedule_time','room','max_slots','status'].forEach(f => {
            const el = document.getElementById('form_'+f);
            if (el) el.value = c[f] ?? '';
        });
        
        // Reset filters when editing
        document.getElementById('filter_dept').value = '';
        document.getElementById('filter_year').value = '';
        document.getElementById('filter_sem').value = '';
        filterSubjects();
        
        document.getElementById('formModal').style.display = 'block';
    }
</script>
</body>
</html>







