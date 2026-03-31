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
            <a href="admin_home.php" class="sidebar-link">
                <i class="fa-solid fa-house"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin_applicants.php" class="sidebar-link">
                <i class="fa-solid fa-user-plus"></i>
                <span>Applicants</span>
            </a>
            <a href="admin_students.php" class="sidebar-link">
                <i class="fa-solid fa-users"></i>
                <span>Students</span>
            </a>
            <a href="admin_blocks.php" class="sidebar-link active">
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
            <a href="admin_reports.php" class="sidebar-link">
                <i class="fa-solid fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <a href="../../php/admin_logout.php" class="sidebar-link logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
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
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($assigned_students) > 0): ?>
                                    <?php while ($student = mysqli_fetch_assoc($assigned_students)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars($student['email'] ?? ''); ?></td>
                                        <td><span class="badge <?php echo strtolower($student['status'] ?? 'active'); ?>"><?php echo htmlspecialchars(ucfirst($student['status'] ?? 'Active')); ?></span></td>
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
                    <form method="POST" action="../../php/assign_student_to_block.php">
                        <input type="hidden" name="block_id" value="<?php echo $block_id; ?>">
                        <div class="form-group" style="padding: 20px;">
                            <label>Select Student to Assign</label>
                            <select name="student_id" required style="width: 100%; padding: 0.75rem; background: var(--gray-lt); border: 1px solid rgba(212,175,55,0.2); color: var(--white);">
                                <option value="">Choose a student...</option>
                                <?php while ($student = mysqli_fetch_assoc($unassigned_students)): ?>
                                    <option value="<?php echo $student['student_id']; ?>">
                                        <?php echo htmlspecialchars($student['student_id'] . ' - ' . ($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <button type="submit" class="btn-primary" style="margin-top: 1rem; width: 100%;">
                                <i class="fa-solid fa-user-plus"></i> Assign to Block
                            </button>
                        </div>
                    </form>

                    <div style="padding: 0 20px 20px;">
                        <p style="font-size: 0.85rem; color: rgba(242,243,242,0.6);">
                            <i class="fa-solid fa-info-circle"></i> Only showing students from <?php echo htmlspecialchars($block['course']); ?>, Year <?php echo $block['year_level']; ?> without a block assignment.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>







