<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);

$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status = 'pending'"))['c'];

// Flash messages
$flash_errors = [
    'missing_fields'  => 'Please fill in all required fields.',
    'duplicate_code'  => 'Subject code already exists.',
    'insert_failed'   => 'Failed to add subject. Please try again.',
    'update_failed'   => 'Failed to update subject. Please try again.',
    'in_use'          => 'Cannot delete — subject is assigned to an existing class.',
];
$flash = '';
if (isset($_GET['error']) && isset($flash_errors[$_GET['error']])) {
    $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> ' . $flash_errors[$_GET['error']] . '</div>';
}
if (isset($_GET['success'])) {
    $msgs = ['added' => 'Subject added successfully.', 'updated' => 'Subject updated successfully.', 'deleted' => 'Subject deleted successfully.'];
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> ' . ($msgs[$_GET['success']] ?? 'Done.') . '</div>';
}

// Stats
$total_subjects  = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM subjects"))['c'];
$active_subjects = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM subjects WHERE status = 'active'"))['c'];
$inactive_subjects = $total_subjects - $active_subjects;
$total_units_row = mysqli_fetch_assoc(mysqli_query($con, "SELECT SUM(units) as s FROM subjects WHERE status = 'active'"));
$total_units = $total_units_row['s'] ?? 0;

// Search & filter
$search   = trim($_GET['search'] ?? '');
$filter   = $_GET['filter'] ?? 'all';
$dept_filter = $_GET['dept'] ?? '';
$year_filter = $_GET['year'] ?? '';

$where = "WHERE 1=1";
if ($filter === 'active')   $where .= " AND status = 'active'";
if ($filter === 'inactive') $where .= " AND status = 'inactive'";
if ($dept_filter !== '')    $where .= " AND department = '" . mysqli_real_escape_string($con, $dept_filter) . "'";
if ($year_filter !== '')    $where .= " AND year_level = '" . mysqli_real_escape_string($con, $year_filter) . "'";
if ($search !== '') {
    $s = mysqli_real_escape_string($con, $search);
    $where .= " AND (subject_code LIKE '%$s%' OR subject_name LIKE '%$s%' OR department LIKE '%$s%' OR prerequisite LIKE '%$s%')";
}

$subjects = mysqli_query($con, "SELECT * FROM subjects $where ORDER BY year_level ASC, semester ASC, subject_code ASC");

// Departments for filter dropdown
$depts_query = mysqli_query($con, "SELECT DISTINCT department FROM subjects WHERE department IS NOT NULL AND department != '' ORDER BY department");
$departments = [];
while ($d = mysqli_fetch_assoc($depts_query)) $departments[] = $d['department'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects Management - PLM Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin.css">
    <style>
        .modal-content { max-width: 680px; max-height: 90vh; overflow-y: auto; }
        .modal-content.wide { max-width: 780px; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0 1rem; }
        .view-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem 2rem; }
        .view-grid .full { grid-column: 1 / -1; }
        .view-item label { font-size: 0.75rem; color: rgba(242,243,242,0.45); display: block; margin-bottom: 0.15rem; }
        .view-item span { font-size: 0.9rem; color: var(--white); }
        .sec-label { font-family: 'Playfair Display', serif; font-size: 0.9rem; color: var(--gold); border-bottom: 1px solid rgba(212,175,55,0.2); padding-bottom: 0.35rem; margin: 1.1rem 0 0.8rem; grid-column: 1 / -1; }
        .badge.active   { background: rgba(34,197,94,0.2);  color: #4ade80; }
        .badge.inactive { background: rgba(156,163,175,0.2); color: #9ca3af; }
        .badge.yr1 { background: rgba(59,130,246,0.15); color: #60a5fa; }
        .badge.yr2 { background: rgba(168,85,247,0.15); color: #c084fc; }
        .badge.yr3 { background: rgba(249,115,22,0.15); color: #fb923c; }
        .badge.yr4 { background: rgba(212,175,55,0.15); color: var(--gold); }
        .filter-tabs { flex-wrap: wrap; }
        .dept-select { padding: 0.45rem 0.75rem; background: var(--gray-lt); border: 1px solid rgba(212,175,55,0.2); color: var(--white); font-size: 0.85rem; border-radius: 4px; }
        .dept-select option { background: var(--gray); }
        .units-badge { display: inline-block; background: rgba(212,175,55,0.15); color: var(--gold); padding: 0.2rem 0.6rem; border-radius: 10px; font-size: 0.8rem; font-weight: 600; }
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
        <a href="admin_faculty.php" class="sidebar-link"><i class="fa-solid fa-chalkboard-user"></i><span>Faculty</span></a>
        <a href="admin_subjects.php" class="sidebar-link active"><i class="fa-solid fa-book"></i><span>Subjects</span></a>
        <a href="admin_classes.php" class="sidebar-link"><i class="fa-solid fa-door-open"></i><span>Classes</span></a>
        <a href="admin_enrollments.php" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>Enrollments</span></a>
        <a href="admin_announcements.php" class="sidebar-link"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
        <a href="admin_calendar.php" class="sidebar-link"><i class="fa-solid fa-calendar-days"></i><span>Calendar</span></a>
        <a href="admin_accounts.php" class="sidebar-link"><i class="fa-solid fa-user-shield"></i><span>Admin Accounts</span></a>
        <a href="../../php/admin_logout.php" class="sidebar-link logout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1>Subjects Management</h1>
            <p>Manage curriculum subjects, units, and schedules</p>
        </div>

        <?php echo $flash; ?>

        <!-- Stat Cards -->
        <div class="stats-grid" style="margin-bottom:1.5rem;">
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fa-solid fa-book"></i></div>
                <div class="stat-content"><h3>Total Subjects</h3><p class="stat-number"><?php echo $total_subjects; ?></p></div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="stat-content"><h3>Active</h3><p class="stat-number"><?php echo $active_subjects; ?></p></div>
            </div>
            <div class="stat-card gold">
                <div class="stat-icon"><i class="fa-solid fa-calculator"></i></div>
                <div class="stat-content"><h3>Total Active Units</h3><p class="stat-number"><?php echo $total_units; ?></p></div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></div>
                <div class="stat-content"><h3>Inactive</h3><p class="stat-number"><?php echo $inactive_subjects; ?></p></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
                <h2>All Subjects</h2>
                <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                    <form method="GET" style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <select name="year" class="dept-select" onchange="this.form.submit()">
                            <option value="">All Years</option>
                            <option value="1" <?php echo $year_filter==='1'?'selected':''; ?>>1st Year</option>
                            <option value="2" <?php echo $year_filter==='2'?'selected':''; ?>>2nd Year</option>
                            <option value="3" <?php echo $year_filter==='3'?'selected':''; ?>>3rd Year</option>
                            <option value="4" <?php echo $year_filter==='4'?'selected':''; ?>>4th Year</option>
                        </select>
                        <select name="dept" class="dept-select" onchange="this.form.submit()">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?php echo htmlspecialchars($d); ?>" <?php echo $dept_filter===$d?'selected':''; ?>><?php echo htmlspecialchars($d); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="search-bar" style="min-width:0;">
                            <i class="fa-solid fa-search"></i>
                            <input type="text" name="search" placeholder="Search code, name, dept..." value="<?php echo htmlspecialchars($search); ?>" style="min-width:220px;">
                        </div>
                        <button type="submit" class="btn-secondary" style="padding:0.5rem 0.75rem;"><i class="fa-solid fa-search"></i></button>
                        <?php if ($search || $dept_filter || $year_filter): ?><a href="?filter=<?php echo $filter; ?>" class="btn-secondary" style="padding:0.5rem 0.75rem;">Clear</a><?php endif; ?>
                    </form>
                    <a href="admin_subjects_batch_import.php" class="btn-secondary" style="background:var(--gold);color:var(--navy-dk);"><i class="fa-solid fa-file-import"></i> Batch Import</a>
                    <button class="btn-secondary" onclick="openAdd()"><i class="fa-solid fa-plus"></i> Add Subject</button>
                </div>
            </div>

            <div class="filter-tabs">
                <a href="?filter=all&search=<?php echo urlencode($search); ?>&dept=<?php echo urlencode($dept_filter); ?>&year=<?php echo urlencode($year_filter); ?>" class="filter-tab <?php echo $filter==='all'?'active':''; ?>">All</a>
                <a href="?filter=active&search=<?php echo urlencode($search); ?>&dept=<?php echo urlencode($dept_filter); ?>&year=<?php echo urlencode($year_filter); ?>" class="filter-tab <?php echo $filter==='active'?'active':''; ?>">Active</a>
                <a href="?filter=inactive&search=<?php echo urlencode($search); ?>&dept=<?php echo urlencode($dept_filter); ?>&year=<?php echo urlencode($year_filter); ?>" class="filter-tab <?php echo $filter==='inactive'?'active':''; ?>">Inactive</a>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Subject Name</th>
                            <th>Units</th>
                            <th>Lec / Lab Hrs</th>
                            <th>Department</th>
                            <th>Year</th>
                            <th>Semester</th>
                            <th>Prerequisite</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($subjects) === 0): ?>
                        <tr><td colspan="10" style="text-align:center;color:rgba(242,243,242,0.4);padding:2rem;">No subjects found.</td></tr>
                    <?php else: ?>
                    <?php while ($sub = mysqli_fetch_assoc($subjects)):
                        $yr_class = $sub['year_level'] ? 'yr'.$sub['year_level'] : 'incomplete';
                        $js = htmlspecialchars(json_encode($sub), ENT_QUOTES);
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($sub['subject_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                            <td><span class="units-badge"><?php echo $sub['units']; ?> units</span></td>
                            <td><?php echo $sub['lecture_hours']; ?> / <?php echo $sub['lab_hours']; ?></td>
                            <td><?php echo htmlspecialchars($sub['department'] ?? '—'); ?></td>
                            <td><?php echo $sub['year_level'] ? '<span class="badge '.$yr_class.'">Year '.$sub['year_level'].'</span>' : '<span style="color:rgba(242,243,242,0.3)">—</span>'; ?></td>
                            <td><?php echo $sub['semester'] ? htmlspecialchars(ucfirst($sub['semester'])) : '<span style="color:rgba(242,243,242,0.3)">—</span>'; ?></td>
                            <td><?php echo htmlspecialchars($sub['prerequisite'] ?: '—'); ?></td>
                            <td><span class="badge <?php echo $sub['status']; ?>"><?php echo ucfirst($sub['status']); ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-icon" title="View" onclick="openView('<?php echo $js; ?>')"><i class="fa-solid fa-eye"></i></button>
                                    <button class="btn-icon" title="Edit" onclick="openEdit('<?php echo $js; ?>')"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <form method="POST" action="../../php/admin_subjects_handler.php" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="subject_id" value="<?php echo $sub['subject_id']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $sub['status']==='active'?'inactive':'active'; ?>">
                                        <button type="submit" class="btn-icon" title="<?php echo $sub['status']==='active'?'Deactivate':'Activate'; ?>" style="color:<?php echo $sub['status']==='active'?'#fb923c':'#4ade80'; ?>;border-color:<?php echo $sub['status']==='active'?'#fb923c':'#4ade80'; ?>;">
                                            <i class="fa-solid <?php echo $sub['status']==='active'?'fa-toggle-on':'fa-toggle-off'; ?>"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="../../php/admin_subjects_handler.php" style="display:inline;" onsubmit="return confirm('Delete this subject? This cannot be undone.')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="subject_id" value="<?php echo $sub['subject_id']; ?>">
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

<!-- ===== VIEW MODAL ===== -->
<div id="viewModal" class="modal">
    <div class="modal-content wide">
        <span class="close" onclick="closeModal('viewModal')">&times;</span>
        <h2 style="font-family:'Playfair Display',serif;margin-bottom:0.25rem;" id="vw_title"></h2>
        <p style="color:rgba(242,243,242,0.5);font-size:0.85rem;margin-bottom:1.5rem;" id="vw_code_line"></p>

        <div class="view-grid">
            <div class="sec-label">Subject Details</div>
            <div class="view-item full"><label>Subject Name</label><span id="vw_subject_name"></span></div>
            <div class="view-item"><label>Subject Code</label><span id="vw_subject_code"></span></div>
            <div class="view-item"><label>Units</label><span id="vw_units"></span></div>
            <div class="view-item"><label>Lecture Hours</label><span id="vw_lecture_hours"></span></div>
            <div class="view-item"><label>Lab Hours</label><span id="vw_lab_hours"></span></div>
            <div class="view-item"><label>Total Hours</label><span id="vw_total_hours"></span></div>

            <div class="sec-label">Classification</div>
            <div class="view-item"><label>Department</label><span id="vw_department"></span></div>
            <div class="view-item"><label>Year Level</label><span id="vw_year_level"></span></div>
            <div class="view-item"><label>Semester</label><span id="vw_semester"></span></div>
            <div class="view-item"><label>Prerequisite</label><span id="vw_prerequisite"></span></div>
            <div class="view-item"><label>Status</label><span id="vw_status"></span></div>

            <div class="sec-label">Description</div>
            <div class="view-item full"><label>Description</label><span id="vw_description"></span></div>
        </div>

        <div style="margin-top:1.5rem;text-align:right;">
            <button class="btn-secondary" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>

<!-- ===== ADD / EDIT MODAL ===== -->
<div id="formModal" class="modal">
    <div class="modal-content wide">
        <span class="close" onclick="closeModal('formModal')">&times;</span>
        <h2 style="font-family:'Playfair Display',serif;margin-bottom:1.5rem;" id="formModalTitle">Add Subject</h2>

        <form method="POST" action="../../php/admin_subjects_handler.php">
            <input type="hidden" name="action" id="form_action" value="add">
            <input type="hidden" name="subject_id" id="form_subject_id">

            <p style="font-family:'Playfair Display',serif;font-size:0.9rem;color:var(--gold);border-bottom:1px solid rgba(212,175,55,0.2);padding-bottom:0.35rem;margin-bottom:1rem;">Basic Information</p>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Subject Code <span style="color:var(--red)">*</span></label>
                    <input type="text" name="subject_code" id="form_subject_code" placeholder="e.g. CS101" required>
                </div>
                <div class="form-group">
                    <label>Subject Name <span style="color:var(--red)">*</span></label>
                    <input type="text" name="subject_name" id="form_subject_name" placeholder="e.g. Introduction to Computing" required>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="form_description" placeholder="Brief description of the subject..."></textarea>
            </div>

            <p style="font-family:'Playfair Display',serif;font-size:0.9rem;color:var(--gold);border-bottom:1px solid rgba(212,175,55,0.2);padding-bottom:0.35rem;margin:1.1rem 0 1rem;">Units & Hours</p>
            <div class="form-grid-3">
                <div class="form-group">
                    <label>Units <span style="color:var(--red)">*</span></label>
                    <input type="number" name="units" id="form_units" min="1" max="9" value="3" required>
                </div>
                <div class="form-group">
                    <label>Lecture Hours</label>
                    <input type="number" name="lecture_hours" id="form_lecture_hours" min="0" max="9" step="0.5" value="3.0">
                </div>
                <div class="form-group">
                    <label>Lab Hours</label>
                    <input type="number" name="lab_hours" id="form_lab_hours" min="0" max="9" step="0.5" value="0.0">
                </div>
            </div>

            <p style="font-family:'Playfair Display',serif;font-size:0.9rem;color:var(--gold);border-bottom:1px solid rgba(212,175,55,0.2);padding-bottom:0.35rem;margin:1.1rem 0 1rem;">Classification</p>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" id="form_department" placeholder="e.g. Computer Science">
                </div>
                <div class="form-group">
                    <label>Prerequisite</label>
                    <input type="text" name="prerequisite" id="form_prerequisite" placeholder="e.g. CS101, CS102">
                </div>
                <div class="form-group">
                    <label>Year Level</label>
                    <select name="year_level" id="form_year_level">
                        <option value="">Not specified</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Semester</label>
                    <select name="semester" id="form_semester">
                        <option value="">Not specified</option>
                        <option value="1st">1st Semester</option>
                        <option value="2nd">2nd Semester</option>
                        <option value="summer">Summer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="form_status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div style="display:flex;gap:1rem;margin-top:1.5rem;">
                <button type="submit" class="btn-submit" style="flex:1;" id="formSubmitBtn">Add Subject</button>
                <button type="button" class="btn-secondary" onclick="closeModal('formModal')" style="flex:1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    window.onclick = function(e) {
        ['viewModal','formModal'].forEach(id => {
            const m = document.getElementById(id);
            if (e.target === m) m.style.display = 'none';
        });
    }

    function openAdd() {
        document.getElementById('formModalTitle').textContent = 'Add Subject';
        document.getElementById('formSubmitBtn').textContent = 'Add Subject';
        document.getElementById('form_action').value = 'add';
        document.getElementById('form_subject_id').value = '';
        ['subject_code','subject_name','description','department','prerequisite'].forEach(f => document.getElementById('form_'+f).value = '');
        document.getElementById('form_units').value = 3;
        document.getElementById('form_lecture_hours').value = 3.0;
        document.getElementById('form_lab_hours').value = 0.0;
        document.getElementById('form_year_level').value = '';
        document.getElementById('form_semester').value = '';
        document.getElementById('form_status').value = 'active';
        document.getElementById('formModal').style.display = 'block';
    }

    function openEdit(raw) {
        const s = JSON.parse(raw);
        document.getElementById('formModalTitle').textContent = 'Edit Subject';
        document.getElementById('formSubmitBtn').textContent = 'Save Changes';
        document.getElementById('form_action').value = 'edit';
        document.getElementById('form_subject_id').value = s.subject_id;
        ['subject_code','subject_name','description','department','prerequisite','units','lecture_hours','lab_hours','status'].forEach(f => {
            const el = document.getElementById('form_'+f);
            if (el) el.value = s[f] ?? '';
        });
        document.getElementById('form_year_level').value = s.year_level ?? '';
        document.getElementById('form_semester').value = s.semester ?? '';
        document.getElementById('formModal').style.display = 'block';
    }

    function openView(raw) {
        const s = JSON.parse(raw);
        document.getElementById('vw_title').textContent = s.subject_name;
        document.getElementById('vw_code_line').textContent = s.subject_code + (s.department ? ' · ' + s.department : '');
        ['subject_name','subject_code','units','lecture_hours','lab_hours','department','prerequisite','status','description'].forEach(f => {
            const el = document.getElementById('vw_'+f);
            if (el) el.textContent = s[f] || '—';
        });
        document.getElementById('vw_total_hours').textContent = (parseFloat(s.lecture_hours||0) + parseFloat(s.lab_hours||0)).toFixed(1);
        document.getElementById('vw_year_level').textContent = s.year_level ? 'Year ' + s.year_level : '—';
        document.getElementById('vw_semester').textContent = s.semester ? s.semester.charAt(0).toUpperCase() + s.semester.slice(1) + ' Semester' : '—';
        document.getElementById('viewModal').style.display = 'block';
    }
</script>
</body>
</html>
