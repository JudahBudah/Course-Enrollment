<?php
include("../../php/connection.php");
include("../../php/admin_functions.php");

session_start();

$admin_data = check_admin_login($con);

// Get enrollment statistics
// Count students with enrollments
$total_enrolled_query = mysqli_query($con, "SELECT COUNT(DISTINCT student_id) as count FROM enrollments WHERE status = 'enrolled'");
$total_enrolled = $total_enrolled_query ? mysqli_fetch_assoc($total_enrolled_query)['count'] : 0;

// Count total students
$total_students_query = mysqli_query($con, "SELECT COUNT(*) as count FROM students");
$total_students = $total_students_query ? mysqli_fetch_assoc($total_students_query)['count'] : 0;

// Pending = students without any enrollments
$pending_enrollment = $total_students - $total_enrolled;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollments - PLM Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="dashboard">
    <nav class="dashboard-nav">
        <div class="nav-brand">
            <img src="../assets/plm-logo.png" alt="PLM">
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
            <a href="admin_enrollments.php" class="sidebar-link active">
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
            <a href="admin_logout.php" class="sidebar-link logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Enrollment Management</h1>
                <p>Manage student enrollments for current semester</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Enrolled Students</h3>
                        <p class="stat-number"><?php echo number_format($total_enrolled); ?></p>
                    </div>
                </div>

                <div class="stat-card gold">
                    <div class="stat-icon">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Pending Enrollment</h3>
                        <p class="stat-number"><?php echo number_format($pending_enrollment); ?></p>
                    </div>
                </div>

                <div class="stat-card navy">
                    <div class="stat-icon">
                        <i class="fa-solid fa-calendar"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Current Semester</h3>
                        <p class="stat-number">2nd Sem</p>
                        <small>AY 2024-2025</small>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Enrollment Records</h2>
                    <div class="search-bar">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search students...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Year Level</th>
                                <th>Block</th>
                                <th>Enrolled Subjects</th>
                                <th>Total Units</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="enrollmentTable">
                            <?php
                            $students = mysqli_query($con, "SELECT * FROM students ORDER BY student_id DESC");
                            while ($student = mysqli_fetch_assoc($students)):
                                // Count enrolled subjects for this student
                                $enrolled_count_query = mysqli_query($con, "SELECT COUNT(*) as count FROM enrollments WHERE student_id = {$student['student_id']} AND status = 'enrolled'");
                                $enrolled_count = $enrolled_count_query ? mysqli_fetch_assoc($enrolled_count_query)['count'] : 0;
                                
                                // Calculate total units
                                $units_query = mysqli_query($con, "SELECT SUM(s.units) as total FROM enrollments e JOIN classes c ON e.class_id = c.class_id JOIN subjects s ON c.subject_id = s.subject_id WHERE e.student_id = {$student['student_id']} AND e.status = 'enrolled'");
                                $total_units = 0;
                                if ($units_query) {
                                    $units_result = mysqli_fetch_assoc($units_query);
                                    $total_units = $units_result['total'] ?? 0;
                                }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($student['course'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($student['year_level'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if (isset($student['block_id']) && $student['block_id']): ?>
                                        <?php 
                                        $block_query = mysqli_query($con, "SELECT block_name FROM blocks WHERE block_id = {$student['block_id']}");
                                        if ($block_query && mysqli_num_rows($block_query) > 0) {
                                            $block_info = mysqli_fetch_assoc($block_query);
                                            echo '<span class="badge blue">' . htmlspecialchars($block_info['block_name'] ?? 'N/A') . '</span>';
                                        } else {
                                            echo '<span class="badge incomplete">No Block</span>';
                                        }
                                        ?>
                                    <?php else: ?>
                                        <span class="badge incomplete">Irregular</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $enrolled_count; ?> subjects</td>
                                <td><?php echo $total_units; ?> units</td>
                                <td>
                                    <a href="admin_manual_enroll.php?student_id=<?php echo $student['student_id']; ?>" class="btn-icon" title="View/Edit Enrollment">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#enrollmentTable tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
