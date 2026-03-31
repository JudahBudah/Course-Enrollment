<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);

$block_id = $_GET['block_id'] ?? 0;

// Get block info
$block_query = mysqli_query($con, "SELECT * FROM blocks WHERE block_id = $block_id");
$block = mysqli_fetch_assoc($block_query);

if (!$block) {
    header("Location: admin_blocks.php");
    exit;
}

// Get students assigned to this block
$assigned_students = mysqli_query($con, "
    SELECT * FROM students 
    WHERE block_id = $block_id 
    ORDER BY last_name, first_name
");

// Get unassigned students (same course and year level, no block assigned)
$unassigned_students = mysqli_query($con, "
    SELECT * FROM students 
    WHERE (block_id IS NULL OR block_id = 0)
    AND course = '{$block['course']}'
    AND year_level = '{$block['year_level']}'
    ORDER BY last_name, first_name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Block Students - PLM Admin</title>
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
            <a href="admin_home.php" class="sidebar-link"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
            <a href="admin_applicants.php" class="sidebar-link"><i class="fa-solid fa-user-plus"></i><span>Applicants</span></a>
            <a href="admin_students.php" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Students</span></a>
            <a href="admin_blocks.php" class="sidebar-link active"><i class="fa-solid fa-layer-group"></i><span>Blocks</span></a>
            <a href="admin_faculty.php" class="sidebar-link"><i class="fa-solid fa-chalkboard-user"></i><span>Faculty</span></a>
            <a href="admin_subjects.php" class="sidebar-link"><i class="fa-solid fa-book"></i><span>Subjects</span></a>
            <a href="admin_classes.php" class="sidebar-link"><i class="fa-solid fa-door-open"></i><span>Classes</span></a>
            <a href="admin_enrollments.php" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>Enrollments</span></a>
            <a href="admin_announcements.php" class="sidebar-link"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
            <a href="admin_reports.php" class="sidebar-link"><i class="fa-solid fa-chart-bar"></i><span>Reports</span></a>
            <a href="../../php/admin_logout.php" class="sidebar-link logout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Block <?php echo htmlspecialchars($block['block_name']); ?> - Students</h1>
                <p><?php echo htmlspecialchars($block['course']); ?> | Year <?php echo $block['year_level']; ?> | <?php echo $block['semester']; ?> Semester <?php echo $block['school_year']; ?></p>
                <p><strong>Capacity:</strong> <?php echo $block['current_students']; ?> / <?php echo $block['max_students']; ?> students</p>
                <a href="admin_blocks.php" class="link"><i class="fa-solid fa-arrow-left"></i> Back to Blocks</a>
            </div>

            <div class="content-grid">
                <div class="card">
                    <div class="card-header">
                        <h2>Students in Block <?php echo htmlspecialchars($block['block_name']); ?></h2>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Student Number</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Registration</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($assigned_students) > 0): ?>
                                    <?php while ($student = mysqli_fetch_assoc($assigned_students)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['student_number'] ?? $student['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars($student['email'] ?? ''); ?></td>
                                        <td><span class="badge <?php echo strtolower($student['registration_status'] ?? 'regular'); ?>"><?php echo htmlspecialchars($student['registration_status'] ?? 'Regular'); ?></span></td>
                                        <td>
                                            <form method="POST" action="../../php/remove_student_from_block.php" style="display:inline;">
                                                <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                                <input type="hidden" name="block_id" value="<?php echo $block_id; ?>">
                                                <button type="submit" class="btn-icon" title="Remove from Block" onclick="return confirm('Remove student from this block?')">
                                                    <i class="fa-solid fa-user-minus"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center; color: rgba(242,243,242,0.5);">No students assigned yet</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Assign Students</h2>
                    </div>
                    
                    <!-- Search and Filter -->
                    <div style="padding: 20px; background: rgba(212,175,55,0.05); border-bottom: 1px solid rgba(212,175,55,0.2);">
                        <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; margin-bottom: 1rem;">
                            <div>
                                <label style="font-size: 0.85rem; color: rgba(242,243,242,0.7); margin-bottom: 0.5rem; display: block;">
                                    <i class="fa-solid fa-search"></i> Search Student
                                </label>
                                <input type="text" id="studentSearch" placeholder="Search by ID, name, or email..." 
                                       style="width: 100%; padding: 0.75rem; background: var(--gray-lt); border: 1px solid rgba(212,175,55,0.2); color: var(--white); border-radius: 4px;"
                                       onkeyup="filterStudents()">
                            </div>
                            <div>
                                <label style="font-size: 0.85rem; color: rgba(242,243,242,0.7); margin-bottom: 0.5rem; display: block;">
                                    <i class="fa-solid fa-filter"></i> Registration Status
                                </label>
                                <select id="statusFilter" onchange="filterStudents()" 
                                        style="width: 100%; padding: 0.75rem; background: var(--gray-lt); border: 1px solid rgba(212,175,55,0.2); color: var(--white); border-radius: 4px;">
                                    <option value="">All Students</option>
                                    <option value="Regular">Regular Only</option>
                                    <option value="Irregular">Irregular Only</option>
                                </select>
                            </div>
                            <div style="display: flex; align-items: flex-end;">
                                <button onclick="clearFilters()" class="btn-secondary" style="padding: 0.75rem 1rem;">
                                    <i class="fa-solid fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                        <div style="font-size: 0.8rem; color: rgba(242,243,242,0.5);">
                            <i class="fa-solid fa-info-circle"></i> Showing students from <?php echo htmlspecialchars($block['course']); ?>, Year <?php echo $block['year_level']; ?> without a block assignment.
                            <span id="studentCount" style="margin-left: 1rem; color: var(--gold);"></span>
                        </div>
                    </div>

                    <!-- Individual Assignment -->
                    <div style="padding: 20px; border-bottom: 1px solid rgba(212,175,55,0.2);">
                        <h3 style="font-size: 0.95rem; color: var(--gold); margin-bottom: 1rem;">
                            <i class="fa-solid fa-user-plus"></i> Assign Individual Student
                        </h3>
                        <form method="POST" action="../../php/assign_student_to_block.php">
                            <input type="hidden" name="block_id" value="<?php echo $block_id; ?>">
                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem;">
                                <select name="student_id" id="studentSelect" required 
                                        style="width: 100%; padding: 0.75rem; background: var(--gray-lt); border: 1px solid rgba(212,175,55,0.2); color: var(--white); border-radius: 4px;">
                                    <option value="">Choose a student...</option>
                                    <?php while ($student = mysqli_fetch_assoc($unassigned_students)): ?>
                                        <option value="<?php echo $student['student_id']; ?>" 
                                                data-name="<?php echo htmlspecialchars(strtolower(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''))); ?>"
                                                data-id="<?php echo htmlspecialchars(strtolower($student['student_number'] ?? $student['student_id'])); ?>"
                                                data-email="<?php echo htmlspecialchars(strtolower($student['email'] ?? '')); ?>"
                                                data-status="<?php echo htmlspecialchars($student['registration_status'] ?? 'Regular'); ?>">
                                            <?php echo htmlspecialchars(($student['student_number'] ?? $student['student_id']) . ' - ' . ($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')); ?>
                                            <?php if ($student['registration_status'] === 'Irregular'): ?>
                                                (Irregular)
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <button type="submit" class="btn-primary" style="padding: 0.75rem 1.5rem; white-space: nowrap;">
                                    <i class="fa-solid fa-user-plus"></i> Assign
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Batch Assignment -->
                    <div style="padding: 20px;">
                        <h3 style="font-size: 0.95rem; color: var(--gold); margin-bottom: 1rem;">
                            <i class="fa-solid fa-users-gear"></i> Batch Assign Students
                        </h3>
                        <form method="POST" action="../../php/batch_assign_to_block.php" onsubmit="return confirm('Assign all filtered students to this block? This may take a moment.')">
                            <input type="hidden" name="block_id" value="<?php echo $block_id; ?>">
                            <input type="hidden" name="course" value="<?php echo htmlspecialchars($block['course']); ?>">
                            <input type="hidden" name="year_level" value="<?php echo $block['year_level']; ?>">
                            
                            <div style="background: rgba(212,175,55,0.1); padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" name="regular_only" value="1" checked>
                                    <span style="font-size: 0.9rem; color: rgba(242,243,242,0.9);">
                                        Assign only <strong>Regular</strong> students (skip Irregular students)
                                    </span>
                                </label>
                            </div>
                            
                            <div style="background: rgba(59,130,246,0.1); padding: 1rem; border-radius: 6px; margin-bottom: 1rem; border-left: 3px solid #3b82f6;">
                                <div style="font-size: 0.85rem; color: rgba(242,243,242,0.8); line-height: 1.6;">
                                    <strong style="color: #60a5fa;"><i class="fa-solid fa-info-circle"></i> What will happen:</strong><br>
                                    • All unassigned students from <?php echo htmlspecialchars($block['course']); ?>, Year <?php echo $block['year_level']; ?> will be assigned to this block<br>
                                    • Students will be auto-enrolled in all block subjects<br>
                                    • Block capacity: <?php echo $block['current_students']; ?> / <?php echo $block['max_students']; ?> (<?php echo $block['max_students'] - $block['current_students']; ?> slots available)
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-submit" style="width: 100%;" 
                                    <?php if ($block['current_students'] >= $block['max_students']): ?>disabled<?php endif; ?>>
                                <i class="fa-solid fa-users-gear"></i> Batch Assign All Eligible Students
                            </button>
                            
                            <?php if ($block['current_students'] >= $block['max_students']): ?>
                                <p style="text-align: center; color: #ef4444; margin-top: 0.5rem; font-size: 0.85rem;">
                                    <i class="fa-solid fa-exclamation-triangle"></i> Block is at full capacity
                                </p>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function filterStudents() {
            const search = document.getElementById('studentSearch').value.toLowerCase();
            const status = document.getElementById('statusFilter').value;
            const select = document.getElementById('studentSelect');
            const options = select.querySelectorAll('option');
            
            let visibleCount = 0;
            
            options.forEach((option, index) => {
                if (index === 0) return; // Skip first option
                
                const name = option.dataset.name || '';
                const id = option.dataset.id || '';
                const email = option.dataset.email || '';
                const optStatus = option.dataset.status || '';
                
                let show = true;
                
                if (search && !name.includes(search) && !id.includes(search) && !email.includes(search)) {
                    show = false;
                }
                
                if (status && optStatus !== status) {
                    show = false;
                }
                
                option.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
            
            document.getElementById('studentCount').textContent = `(${visibleCount} student${visibleCount !== 1 ? 's' : ''} available)`;
        }
        
        function clearFilters() {
            document.getElementById('studentSearch').value = '';
            document.getElementById('statusFilter').value = '';
            filterStudents();
        }
        
        // Initialize count on load
        window.addEventListener('load', filterStudents);
    </script>
</body>
</html>
