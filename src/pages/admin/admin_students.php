<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);

$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM applicants WHERE application_status = 'pending'"))['count'];

// Flash messages
$flash_errors = [
    'missing_fields'          => 'Please fill in all required fields.',
    'duplicate_email'         => 'That email is already used by another student.',
    'duplicate_student_number'=> 'That student number is already taken.',
    'update_failed'           => 'Update failed. Please try again.',
];
$flash = '';
if (isset($_GET['error']) && isset($flash_errors[$_GET['error']])) {
    $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> ' . $flash_errors[$_GET['error']] . '</div>';
}
if (isset($_GET['success']) && $_GET['success'] === 'updated') {
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> Student record updated successfully.</div>';
}

// Stats
$total_students    = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM students"))['c'];
$enrolled_count    = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM students WHERE status != 'Not Enrolled'"))['c'];
$not_enrolled      = $total_students - $enrolled_count;
$irregular_count   = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM students WHERE registration_status = 'Irregular'"))['c'];

// Search & filter
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';

$where = "WHERE 1=1";
if ($filter === 'enrolled')     $where .= " AND s.status != 'Not Enrolled'";
if ($filter === 'not_enrolled') $where .= " AND s.status = 'Not Enrolled'";
if ($filter === 'irregular')    $where .= " AND s.registration_status = 'Irregular'";
if ($search !== '') {
    $s = mysqli_real_escape_string($con, $search);
    $where .= " AND (s.first_name LIKE '%$s%' OR s.last_name LIKE '%$s%' OR s.student_number LIKE '%$s%' OR s.email LIKE '%$s%' OR s.course LIKE '%$s%')";
}

$students_query = mysqli_query($con, "
    SELECT s.*, b.block_name
    FROM students s
    LEFT JOIN blocks b ON s.block_id = b.block_id
    $where
    ORDER BY s.student_id DESC
");

// All blocks for dropdowns
$blocks_list = mysqli_query($con, "SELECT block_id, block_name, course, year_level FROM blocks ORDER BY block_name");
$blocks_arr = [];
while ($b = mysqli_fetch_assoc($blocks_list)) $blocks_arr[] = $b;

$courses = ['BS Computer Science','BS Information Technology','BS Business Administration','BS Accountancy','BS Civil Engineering','BS Electrical Engineering'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Management - PLM Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin.css">
    <style>
        .modal-content { max-width: 700px; max-height: 90vh; overflow-y: auto; }
        .modal-content.wide { max-width: 860px; }
        .view-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem 2rem; margin-bottom: 1rem; }
        .view-grid .full { grid-column: 1 / -1; }
        .view-item label { font-size: 0.75rem; color: rgba(242,243,242,0.5); display: block; margin-bottom: 0.2rem; }
        .view-item span { font-size: 0.9rem; color: var(--white); }
        .section-title { font-family: 'Playfair Display', serif; font-size: 1rem; color: var(--gold); border-bottom: 1px solid rgba(212,175,55,0.2); padding-bottom: 0.4rem; margin: 1.2rem 0 0.8rem; grid-column: 1 / -1; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
        .student-avatar-lg { width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, var(--red), var(--gold)); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; color: white; flex-shrink: 0; }
        .modal-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; }
        .modal-header-info h3 { font-family: 'Playfair Display', serif; font-size: 1.3rem; margin: 0; }
        .modal-header-info p { font-size: 0.85rem; color: rgba(242,243,242,0.5); margin: 0.2rem 0 0; }
        .badge.enrolled { background: rgba(34,197,94,0.2); color: #4ade80; }
        .badge.not-enrolled { background: rgba(156,163,175,0.2); color: #9ca3af; }
        .badge.blue { background: rgba(59,130,246,0.2); color: #60a5fa; }
        .filter-tabs { flex-wrap: wrap; }
        .card-header { flex-wrap: wrap; gap: 1rem; }
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
            <a href="admin_students.php" class="sidebar-link active"><i class="fa-solid fa-users"></i><span>Students</span></a>
            <a href="admin_blocks.php" class="sidebar-link"><i class="fa-solid fa-layer-group"></i><span>Blocks</span></a>
            <a href="admin_faculty.php" class="sidebar-link"><i class="fa-solid fa-chalkboard-user"></i><span>Faculty</span></a>
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
                <h1>Students Management</h1>
                <p>View, edit, and manage all student records</p>
            </div>

            <?php echo $flash; ?>

            <!-- Stat Cards -->
            <div class="stats-grid" style="margin-bottom:1.5rem;">
                <div class="stat-card blue">
                    <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-content"><h3>Total Students</h3><p class="stat-number"><?php echo $total_students; ?></p></div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon"><i class="fa-solid fa-user-check"></i></div>
                    <div class="stat-content"><h3>Enrolled</h3><p class="stat-number"><?php echo $enrolled_count; ?></p></div>
                </div>
                <div class="stat-card gold">
                    <div class="stat-icon"><i class="fa-solid fa-user-clock"></i></div>
                    <div class="stat-content"><h3>Not Enrolled</h3><p class="stat-number"><?php echo $not_enrolled; ?></p></div>
                </div>
                <div class="stat-card purple">
                    <div class="stat-icon"><i class="fa-solid fa-shuffle"></i></div>
                    <div class="stat-content"><h3>Irregular</h3><p class="stat-number"><?php echo $irregular_count; ?></p></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>All Students</h2>
                    <form method="GET" class="search-form">
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <input type="text" name="search" placeholder="Search name, ID, email, course..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fa-solid fa-search"></i></button>
                        <?php if ($search): ?><a href="?filter=<?php echo $filter; ?>" class="btn-secondary" style="padding:0.5rem 0.75rem;">Clear</a><?php endif; ?>
                    </form>
                </div>

                <div class="filter-tabs">
                    <a href="?filter=all&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $filter==='all'?'active':''; ?>">All</a>
                    <a href="?filter=enrolled&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $filter==='enrolled'?'active':''; ?>">Enrolled</a>
                    <a href="?filter=not_enrolled&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $filter==='not_enrolled'?'active':''; ?>">Not Enrolled</a>
                    <a href="?filter=irregular&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $filter==='irregular'?'active':''; ?>">Irregular</a>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student No.</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Course</th>
                                <th>Year</th>
                                <th>Block</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="studentsTable">
                        <?php while ($student = mysqli_fetch_assoc($students_query)):
                            $initials = strtoupper(substr($student['first_name'] ?? '', 0, 1) . substr($student['last_name'] ?? '', 0, 1));
                            $photo    = $student['profile_photo'] ? '../../' . $student['profile_photo'] : null;
                            $fullname = htmlspecialchars(trim(($student['first_name'] ?? '') . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . ($student['last_name'] ?? '') . ($student['suffix_name'] ? ' ' . $student['suffix_name'] : '')));
                            $status_class = strtolower(str_replace(' ', '-', $student['status'] ?? ''));

                            // Encode all student data for JS
                            $js_data = htmlspecialchars(json_encode($student), ENT_QUOTES);
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($student['student_number']); ?></strong></td>
                            <td>
                                <?php if ($photo): ?>
                                    <img src="<?php echo htmlspecialchars($photo); ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                <?php endif; ?>
                                <div style="display:<?php echo $photo ? 'none' : 'flex'; ?>;width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--red),var(--gold));color:white;align-items:center;justify-content:center;font-size:13px;font-weight:700;"><?php echo $initials; ?></div>
                            </td>
                            <td><?php echo $fullname; ?></td>
                            <td><?php echo htmlspecialchars($student['email'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($student['course'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($student['year_level'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($student['block_name']): ?>
                                    <span class="badge blue"><?php echo htmlspecialchars($student['block_name']); ?></span>
                                <?php else: ?>
                                    <span class="badge incomplete">No Block</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?php echo $student['registration_status']==='Irregular'?'pending':'approved'; ?>"><?php echo htmlspecialchars($student['registration_status'] ?? 'Regular'); ?></span></td>
                            <td><span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($student['status'] ?? 'N/A'); ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-icon" title="View Details" onclick="openView('<?php echo $js_data; ?>')">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button class="btn-icon" title="Edit Student" onclick="openEdit('<?php echo $js_data; ?>')">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <a href="admin_manual_enroll.php?student_id=<?php echo $student['student_id']; ?>" class="btn-icon" title="Manual Enrollment">
                                        <i class="fa-solid fa-user-pen"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
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

            <div class="modal-header">
                <div class="student-avatar-lg" id="view_avatar"></div>
                <div class="modal-header-info">
                    <h3 id="view_fullname"></h3>
                    <p id="view_student_number"></p>
                    <p id="view_status_badge"></p>
                </div>
            </div>

            <div class="view-grid">
                <div class="section-title">Personal Information</div>
                <div class="view-item"><label>First Name</label><span id="vw_first_name"></span></div>
                <div class="view-item"><label>Last Name</label><span id="vw_last_name"></span></div>
                <div class="view-item"><label>Middle Name</label><span id="vw_middle_name"></span></div>
                <div class="view-item"><label>Suffix</label><span id="vw_suffix_name"></span></div>
                <div class="view-item"><label>Gender</label><span id="vw_gender"></span></div>
                <div class="view-item"><label>Birthdate</label><span id="vw_birthdate"></span></div>

                <div class="section-title">Contact Information</div>
                <div class="view-item"><label>Email</label><span id="vw_email"></span></div>
                <div class="view-item"><label>Contact Number</label><span id="vw_contact_number"></span></div>

                <div class="section-title">Academic Information</div>
                <div class="view-item"><label>College</label><span id="vw_college"></span></div>
                <div class="view-item"><label>Course</label><span id="vw_course"></span></div>
                <div class="view-item"><label>Year Level</label><span id="vw_year_level"></span></div>
                <div class="view-item"><label>Block</label><span id="vw_block"></span></div>
                <div class="view-item"><label>Registration Type</label><span id="vw_registration_status"></span></div>
                <div class="view-item"><label>Enrollment Status</label><span id="vw_status"></span></div>
                <div class="view-item"><label>Account Status</label><span id="vw_account_status"></span></div>
                <div class="view-item"><label>Date Created</label><span id="vw_created_at"></span></div>
            </div>

            <div style="margin-top:1.5rem;text-align:right;">
                <button class="btn-secondary" onclick="closeModal('viewModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- ===== EDIT MODAL ===== -->
    <div id="editModal" class="modal">
        <div class="modal-content wide">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h2 style="font-family:'Playfair Display',serif;margin-bottom:1.5rem;">Edit Student Record</h2>

            <form method="POST" action="../../php/admin_update_student.php">
                <input type="hidden" name="student_id" id="edit_student_id">

                <p class="section-title" style="font-family:'Playfair Display',serif;font-size:0.95rem;color:var(--gold);border-bottom:1px solid rgba(212,175,55,0.2);padding-bottom:0.4rem;margin-bottom:1rem;">Academic Info</p>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Student Number <span style="color:var(--red)">*</span></label>
                        <input type="text" name="student_number" id="edit_student_number" required>
                    </div>
                    <div class="form-group">
                        <label>College</label>
                        <input type="text" name="college" id="edit_college" placeholder="e.g. CE, CBA">
                    </div>
                    <div class="form-group">
                        <label>Course</label>
                        <select name="course" id="edit_course">
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Year Level</label>
                        <select name="year_level" id="edit_year_level">
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Block</label>
                        <select name="block_id" id="edit_block_id">
                            <option value="">No Block (Irregular)</option>
                            <?php foreach ($blocks_arr as $b): ?>
                                <option value="<?php echo $b['block_id']; ?>"><?php echo htmlspecialchars($b['block_name'] . ' — ' . $b['course'] . ' Yr' . $b['year_level']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Registration Type</label>
                        <select name="registration_status" id="edit_registration_status">
                            <option value="Regular">Regular</option>
                            <option value="Irregular">Irregular</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Enrollment Status</label>
                        <select name="status" id="edit_status">
                            <option value="Not Enrolled">Not Enrolled</option>
                            <option value="Enrolled">Enrolled</option>
                            <option value="Graduated">Graduated</option>
                            <option value="Leave of Absence">Leave of Absence</option>
                            <option value="Dropped">Dropped</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Account Status</label>
                        <select name="account_status" id="edit_account_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <p class="section-title" style="font-family:'Playfair Display',serif;font-size:0.95rem;color:var(--gold);border-bottom:1px solid rgba(212,175,55,0.2);padding-bottom:0.4rem;margin:1.2rem 0 1rem;">Personal Info</p>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>First Name <span style="color:var(--red)">*</span></label>
                        <input type="text" name="first_name" id="edit_first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name <span style="color:var(--red)">*</span></label>
                        <input type="text" name="last_name" id="edit_last_name" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" id="edit_middle_name">
                    </div>
                    <div class="form-group">
                        <label>Suffix</label>
                        <input type="text" name="suffix_name" id="edit_suffix_name" placeholder="e.g. Jr., III">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" id="edit_gender">
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Birthdate</label>
                        <input type="date" name="birthdate" id="edit_birthdate">
                    </div>
                </div>

                <p class="section-title" style="font-family:'Playfair Display',serif;font-size:0.95rem;color:var(--gold);border-bottom:1px solid rgba(212,175,55,0.2);padding-bottom:0.4rem;margin:1.2rem 0 1rem;">Contact Info</p>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Email <span style="color:var(--red)">*</span></label>
                        <input type="email" name="email" id="edit_email" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" id="edit_contact_number">
                    </div>
                </div>

                <div style="display:flex;gap:1rem;margin-top:1.5rem;">
                    <button type="submit" class="btn-submit" style="flex:1;">Save Changes</button>
                    <button type="button" class="btn-secondary" onclick="closeModal('editModal')" style="flex:1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        window.onclick = function(e) {
            ['viewModal','editModal'].forEach(id => {
                const m = document.getElementById(id);
                if (e.target === m) m.style.display = 'none';
            });
        }

        function openView(raw) {
            const s = JSON.parse(raw);
            const initials = ((s.first_name||'')[0]||'') + ((s.last_name||'')[0]||'');
            document.getElementById('view_avatar').textContent = initials.toUpperCase();
            document.getElementById('view_fullname').textContent = [s.first_name, s.middle_name, s.last_name, s.suffix_name].filter(Boolean).join(' ');
            document.getElementById('view_student_number').textContent = 'Student No: ' + (s.student_number || 'N/A');
            document.getElementById('view_status_badge').innerHTML = '<span class="badge ' + (s.status||'').toLowerCase().replace(/ /g,'-') + '">' + (s.status||'N/A') + '</span>';

            const fields = ['first_name','last_name','middle_name','suffix_name','gender','birthdate','email','contact_number','college','course','year_level','registration_status','status','account_status','created_at'];
            fields.forEach(f => {
                const el = document.getElementById('vw_' + f);
                if (el) el.textContent = s[f] || '—';
            });
            document.getElementById('vw_block').textContent = s.block_name || 'No Block';
            document.getElementById('viewModal').style.display = 'block';
        }

        function openEdit(raw) {
            const s = JSON.parse(raw);
            const fields = ['student_id','student_number','first_name','last_name','middle_name','suffix_name','gender','birthdate','email','contact_number','college','course','year_level','registration_status','account_status','status'];
            fields.forEach(f => {
                const el = document.getElementById('edit_' + f);
                if (!el) return;
                el.value = s[f] || '';
            });
            // block_id select
            const blockSel = document.getElementById('edit_block_id');
            blockSel.value = s.block_id || '';
            document.getElementById('editModal').style.display = 'block';
        }
    </script>
</body>
</html>
