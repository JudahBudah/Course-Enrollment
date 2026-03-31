<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);


// Handle delete
if (isset($_POST['delete_applicant'])) {
    $applicant_id = (int)$_POST['applicant_id'];
    $stmt = mysqli_prepare($con, "DELETE FROM applicants WHERE applicant_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $applicant_id);
    mysqli_stmt_execute($stmt);
    $success = "Applicant deleted successfully.";
}

// Handle status update
if (isset($_POST['update_status'])) {
    $applicant_id = $_POST['applicant_id'];
    $new_status = $_POST['status'];
    
    $stmt = mysqli_prepare($con, "UPDATE applicants SET application_status = ? WHERE applicant_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_status, $applicant_id);
    mysqli_stmt_execute($stmt);
    
    $success = "Application status updated successfully!";
}

// Handle flash messages from convert_to_student.php
$error_messages = [
    'missing_fields'           => 'Please fill in all required fields.',
    'not_found'                => 'Applicant not found or not yet approved.',
    'duplicate_student_number' => 'Student number already exists.',
    'already_student'          => 'This applicant is already a student.',
    'insert_failed'            => 'Failed to create student record. Please try again.',
    'incomplete_profile'       => 'Cannot convert — applicant has not completed their profile (missing first name or last name).',
];
if (isset($_GET['error']) && isset($error_messages[$_GET['error']])) {
    $error = $error_messages[$_GET['error']];
}
if (isset($_GET['success']) && $_GET['success'] === 'converted') {
    $success = 'Applicant successfully converted to student!';
}
if (isset($_GET['exam_success'])) {
    $success = 'Exam schedule assigned to ' . (int)$_GET['exam_success'] . ' applicant(s).';
}
if (isset($_GET['exam_cleared'])) {
    $success = 'Exam schedule cleared for selected applicants.';
}
if (isset($_GET['exam_error'])) {
    $error = 'Please fill in all required exam schedule fields and select at least one applicant.';
}

// Ensure exam columns exist (compatible with MySQL 5.x)
$cols = [];
$col_res = mysqli_query($con, "SHOW COLUMNS FROM applicants");
while ($c = mysqli_fetch_assoc($col_res)) $cols[] = $c['Field'];
if (!in_array('exam_schedule_id', $cols))
    mysqli_query($con, "ALTER TABLE applicants ADD COLUMN exam_schedule_id INT DEFAULT NULL");
if (!in_array('exam_notified', $cols))
    mysqli_query($con, "ALTER TABLE applicants ADD COLUMN exam_notified TINYINT(1) DEFAULT 0");
mysqli_query($con, "CREATE TABLE IF NOT EXISTS exam_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_date   DATE NOT NULL,
    exam_time   VARCHAR(50) NOT NULL,
    location    VARCHAR(255) NOT NULL,
    notes       TEXT,
    created_by  INT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Get statistics
$total_students = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM students"))['count'];
$total_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM applicants"))['count'];
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM applicants WHERE application_status = 'pending'"))['count'];
$total_faculty = 0; // Faculty table not yet created
$total_subjects = 0; // Subjects table not yet created
$total_classes = 0; // Classes table not yet created

// Get filter
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$query = "SELECT a.*, es.exam_date, es.exam_time, es.location as exam_location
          FROM applicants a
          LEFT JOIN exam_schedules es ON a.exam_schedule_id = es.schedule_id
          WHERE 1=1";
if ($filter === 'all') {
    $query .= " AND a.application_status != 'enrolled'";
} else {
    $query .= " AND a.application_status = '$filter'";
}
if (!empty($search)) {
    $query .= " AND (a.first_name LIKE '%$search%' OR a.last_name LIKE '%$search%' OR a.email LIKE '%$search%' OR a.lrn LIKE '%$search%')";
}
$query .= " ORDER BY a.created_at DESC";

$applicants = mysqli_query($con, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applicants - PLM Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin.css">
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
           <a href="admin_applicants.php" class="sidebar-link active">
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
            <a href="admin_classes.php" class="sidebar-link">
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
                <h1>Manage Applicants</h1>
                <p>Review and manage student applications</p>
            </div>

            <?php if (isset($success)): ?>
                <div class="success-message"><i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>Applicants List</h2>
                    <div class="header-actions">
                        <form method="GET" class="search-form">
                            <input type="text" name="search" placeholder="Search applicants..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit"><i class="fa-solid fa-search"></i></button>
                        </form>
                        <button class="btn-primary" onclick="openExamModal()" style="margin-left:0.5rem;"><i class="fa-solid fa-calendar-plus"></i> Schedule Exam</button>
                    </div>
                </div>

                <!-- Batch action bar -->
                <div id="batchBar" style="display:none;background:rgba(212,175,55,0.08);border:1px solid rgba(212,175,55,0.2);border-radius:6px;padding:0.6rem 1rem;margin:0.75rem 1.5rem;display:none;align-items:center;gap:1rem;flex-wrap:wrap;">
                    <span id="batchCount" style="font-size:0.85rem;color:var(--gold);">0 selected</span>
                    <button class="btn-primary" onclick="openExamModal()" style="padding:0.4rem 0.9rem;font-size:0.82rem;"><i class="fa-solid fa-calendar-plus"></i> Assign Exam Schedule</button>
                    <button class="btn-secondary" onclick="clearExam()" style="padding:0.4rem 0.9rem;font-size:0.82rem;"><i class="fa-solid fa-calendar-xmark"></i> Clear Exam</button>
                    <button class="btn-secondary" onclick="deselectAll()" style="padding:0.4rem 0.9rem;font-size:0.82rem;">Deselect All</button>
                </div>

                <div class="filter-tabs">
                    <a href="?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?filter=incomplete" class="filter-tab <?php echo $filter == 'incomplete' ? 'active' : ''; ?>">Incomplete</a>
                    <a href="?filter=pending" class="filter-tab <?php echo $filter == 'pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="?filter=approved" class="filter-tab <?php echo $filter == 'approved' ? 'active' : ''; ?>">Approved</a>
                    <a href="?filter=rejected" class="filter-tab <?php echo $filter == 'rejected' ? 'active' : ''; ?>">Rejected</a>
                    <a href="?filter=enrolled" class="filter-tab <?php echo $filter == 'enrolled' ? 'active' : ''; ?>">Converted</a>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" onchange="toggleAll(this)" title="Select all"></th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>LRN</th>
                                <th>First Choice</th>
                                <th>Status</th>
                                <th>Exam Schedule</th>
                                <th>Applied Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($applicant = mysqli_fetch_assoc($applicants)): ?>
                            <tr>
                                <td><input type="checkbox" class="row-check" value="<?php echo $applicant['applicant_id']; ?>" onchange="updateBatch()"></td>
                                <td><?php echo $applicant['applicant_id']; ?></td>
                                <td><?php echo htmlspecialchars(($applicant['first_name'] ?? '') . ' ' . ($applicant['last_name'] ?? 'N/A')); ?></td>
                                <td><?php echo htmlspecialchars($applicant['email']); ?></td>
                                <td><?php echo htmlspecialchars($applicant['lrn'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($applicant['first_choice'] ?? 'N/A'); ?></td>
                                <td><span class="badge <?php echo strtolower($applicant['application_status']); ?>"><?php echo htmlspecialchars(ucfirst($applicant['application_status'])); ?></span></td>
                                <td>
                                    <?php if ($applicant['exam_schedule_id']): ?>
                                        <div style="font-size:0.8rem;line-height:1.5;">
                                            <div style="color:var(--gold);font-weight:600;"><?php echo date('M j, Y', strtotime($applicant['exam_date'])); ?></div>
                                            <div style="color:rgba(242,243,242,0.6);"><?php echo htmlspecialchars($applicant['exam_time']); ?></div>
                                            <div style="color:rgba(242,243,242,0.5);font-size:0.75rem;"><?php echo htmlspecialchars($applicant['exam_location']); ?></div>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:rgba(242,243,242,0.3);font-size:0.8rem;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($applicant['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="updateStatus(<?php echo $applicant['applicant_id']; ?>)" class="btn-icon" title="Update Status">
                                            <i class="fa-solid fa-edit"></i>
                                        </button>
                                        <?php if ($applicant['application_status'] === 'approved'): ?>
                                        <button onclick="openConvertModal(<?php echo $applicant['applicant_id']; ?>, '<?php echo htmlspecialchars(addslashes($applicant['first_name'] . ' ' . $applicant['last_name'])); ?>', '<?php echo htmlspecialchars(addslashes($applicant['first_choice'] ?? '')); ?>')" class="btn-icon" title="Convert to Student" style="color:#4ade80;border-color:#4ade80;">
                                            <i class="fa-solid fa-user-graduate"></i>
                                        </button>
                                        <?php endif; ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this applicant? This cannot be undone.')">
                                            <input type="hidden" name="applicant_id" value="<?php echo $applicant['applicant_id']; ?>">
                                            <button type="submit" name="delete_applicant" class="btn-icon" title="Delete" style="color:#ef4444;border-color:#ef4444;">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
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

    <!-- Exam Schedule Modal -->
    <div id="examModal" class="modal">
        <div class="modal-content" style="max-width:520px;">
            <span class="close" onclick="document.getElementById('examModal').style.display='none'">&times;</span>
            <h2 style="font-family:'Playfair Display',serif;margin-bottom:0.5rem;">Schedule Exam</h2>
            <p id="examModalDesc" style="font-size:0.85rem;color:rgba(242,243,242,0.5);margin-bottom:1.5rem;"></p>
            <form method="POST" action="../../php/admin_exam_schedule_handler.php" id="examForm">
                <input type="hidden" name="action" value="assign_exam">
                <div id="examApplicantInputs"></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 1rem;">
                    <div class="form-group">
                        <label>Exam Date <span style="color:var(--red)">*</span></label>
                        <input type="date" name="exam_date" id="exam_date" required>
                    </div>
                    <div class="form-group">
                        <label>Exam Time <span style="color:var(--red)">*</span></label>
                        <input type="text" name="exam_time" id="exam_time" placeholder="e.g. 8:00 AM" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Location / Venue <span style="color:var(--red)">*</span></label>
                    <input type="text" name="location" id="exam_location" placeholder="e.g. PLM Main Building, Room 201" required>
                </div>
                <div class="form-group">
                    <label>Notes <small style="color:rgba(242,243,242,0.4);">(optional)</small></label>
                    <textarea name="notes" rows="2" placeholder="Additional instructions for applicants…"></textarea>
                </div>
                <div style="display:flex;gap:1rem;margin-top:0.5rem;">
                    <button type="submit" class="btn-submit" style="flex:1;">Assign Schedule</button>
                    <button type="button" class="btn-secondary" onclick="document.getElementById('examModal').style.display='none'" style="flex:1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Clear Exam Form (hidden) -->
    <form method="POST" action="../../php/admin_exam_schedule_handler.php" id="clearExamForm">
        <input type="hidden" name="action" value="clear_exam">
        <div id="clearExamInputs"></div>
    </form>

    <!-- Convert to Student Modal -->
    <div id="convertModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('convertModal').style.display='none'">&times;</span>
            <h2>Convert to Student</h2>
            <p style="color:rgba(242,243,242,0.6);font-size:0.9rem;margin-bottom:1.5rem;">Applicant: <strong id="convertName" style="color:var(--gold);"></strong></p>
            <form method="POST" action="../../php/convert_to_student.php">
                <input type="hidden" name="applicant_id" id="convert_applicant_id">
                <div class="form-group">
                    <label>Student Number <span style="color:var(--red)">*</span></label>
                    <input type="text" name="student_number" placeholder="e.g., 2024-12345" required>
                </div>
                <div class="form-group">
                    <label>College <span style="color:var(--red)">*</span></label>
                    <input type="text" name="college" placeholder="e.g., CE, CBA, CLA" required>
                </div>
                <div class="form-group">
                    <label>Course <span style="color:var(--red)">*</span></label>
                    <select name="course" id="convert_course" required>
                        <option value="">Select Course</option>
                        <option value="BS Computer Science">BS Computer Science</option>
                        <option value="BS Information Technology">BS Information Technology</option>
                        <option value="BS Business Administration">BS Business Administration</option>
                        <option value="BS Accountancy">BS Accountancy</option>
                        <option value="BS Civil Engineering">BS Civil Engineering</option>
                        <option value="BS Electrical Engineering">BS Electrical Engineering</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Year Level <span style="color:var(--red)">*</span></label>
                    <select name="year_level" required>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit" style="background:linear-gradient(135deg,#16a34a,#15803d);">Convert to Student</button>
            </form>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Update Application Status</h2>
            <form method="POST">
                <input type="hidden" name="applicant_id" id="applicant_id">
                <div class="form-group">
                    <label>New Status</label>
                    <select name="status" required>
                        <option value="incomplete">Incomplete</option>
                        <option value="pending">Pending Review</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <button type="submit" name="update_status" class="btn-submit">Update Status</button>
            </form>
        </div>
    </div>

    <script>
        function updateStatus(id) {
            document.getElementById('applicant_id').value = id;
            document.getElementById('statusModal').style.display = 'block';
        }

        function openConvertModal(id, name, course) {
            document.getElementById('convert_applicant_id').value = id;
            document.getElementById('convertName').textContent = name;
            const courseSelect = document.getElementById('convert_course');
            for (let opt of courseSelect.options) {
                if (opt.value === course) { opt.selected = true; break; }
            }
            document.getElementById('convertModal').style.display = 'block';
        }

        window.onclick = function(event) {
            ['statusModal', 'convertModal', 'examModal'].forEach(id => {
                const modal = document.getElementById(id);
                if (event.target == modal) modal.style.display = 'none';
            });
        }

        /* ── Batch selection ── */
        function getSelected() {
            return [...document.querySelectorAll('.row-check:checked')].map(c => c.value);
        }

        function updateBatch() {
            const sel = getSelected();
            const bar = document.getElementById('batchBar');
            bar.style.display = sel.length > 0 ? 'flex' : 'none';
            document.getElementById('batchCount').textContent = sel.length + ' selected';
            document.getElementById('selectAll').indeterminate =
                sel.length > 0 && sel.length < document.querySelectorAll('.row-check').length;
            document.getElementById('selectAll').checked =
                sel.length === document.querySelectorAll('.row-check').length;
        }

        function toggleAll(cb) {
            document.querySelectorAll('.row-check').forEach(c => c.checked = cb.checked);
            updateBatch();
        }

        function deselectAll() {
            document.querySelectorAll('.row-check').forEach(c => c.checked = false);
            document.getElementById('selectAll').checked = false;
            updateBatch();
        }

        /* ── Exam modal ── */
        function openExamModal() {
            const sel = getSelected();
            if (sel.length === 0) {
                alert('Please select at least one applicant first.');
                return;
            }
            document.getElementById('examModalDesc').textContent =
                'Assigning exam schedule to ' + sel.length + ' applicant(s).';
            // Populate hidden inputs
            const wrap = document.getElementById('examApplicantInputs');
            wrap.innerHTML = sel.map(id =>
                `<input type="hidden" name="applicant_ids[]" value="${id}">`
            ).join('');
            document.getElementById('examModal').style.display = 'block';
        }

        function clearExam() {
            const sel = getSelected();
            if (sel.length === 0) return;
            if (!confirm('Clear exam schedule for ' + sel.length + ' applicant(s)?')) return;
            const wrap = document.getElementById('clearExamInputs');
            wrap.innerHTML = sel.map(id =>
                `<input type="hidden" name="applicant_ids[]" value="${id}">`
            ).join('');
            document.getElementById('clearExamForm').submit();
        }
    </script>
</body>
</html>







