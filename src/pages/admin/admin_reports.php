<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);

// Get statistics
$total_students = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM students"))['count'];
$total_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM applicants"))['count'];
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM applicants WHERE application_status = 'pending'"))['count'];
$total_faculty = 0; // Faculty table not yet created
$total_subjects = 0; // Subjects table not yet created
$total_classes = 0; // Classes table not yet created

// Count students with enrollments
$enrolled_query = mysqli_query($con, "SELECT COUNT(DISTINCT student_id) as count FROM enrollments WHERE status = 'enrolled'");
$enrolled_students = $enrolled_query ? mysqli_fetch_assoc($enrolled_query)['count'] : 0;
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM applicants WHERE application_status = 'pending'"))['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - PLM Admin</title>
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
            <a href="admin_reports.php" class="sidebar-link active">
                <i class="fa-solid fa-chart-bar"></i>
                <span>Reports</span>
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
                <h1>Reports & Analytics</h1>
                <p>Generate and export system reports</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Students</h3>
                        <p class="stat-number"><?php echo number_format($total_students); ?></p>
                    </div>
                </div>

                <div class="stat-card gold">
                    <div class="stat-icon">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Applicants</h3>
                        <p class="stat-number"><?php echo number_format($total_applicants); ?></p>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Enrolled Students</h3>
                        <p class="stat-number"><?php echo number_format($enrolled_students); ?></p>
                    </div>
                </div>

                <div class="stat-card red">
                    <div class="stat-icon">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Pending Applications</h3>
                        <p class="stat-number"><?php echo number_format($pending_applicants); ?></p>
                    </div>
                </div>
            </div>

            <div class="content-grid">
                <div class="card">
                    <div class="card-header">
                        <h2>Generate Reports</h2>
                    </div>
                    <div class="quick-actions">
                        <button class="action-btn" onclick="alert('Generating Student List Report...')">
                            <i class="fa-solid fa-users"></i>
                            <span>Student List Report</span>
                        </button>
                        <button class="action-btn" onclick="alert('Generating Applicant Report...')">
                            <i class="fa-solid fa-user-plus"></i>
                            <span>Applicant Report</span>
                        </button>
                        <button class="action-btn" onclick="alert('Generating Enrollment Report...')">
                            <i class="fa-solid fa-file-lines"></i>
                            <span>Enrollment Report</span>
                        </button>
                        <button class="action-btn" onclick="alert('Generating Course Statistics...')">
                            <i class="fa-solid fa-chart-pie"></i>
                            <span>Course Statistics</span>
                        </button>
                        <button class="action-btn" onclick="alert('Generating Year Level Report...')">
                            <i class="fa-solid fa-layer-group"></i>
                            <span>Year Level Report</span>
                        </button>
                        <button class="action-btn" onclick="alert('Generating Custom Report...')">
                            <i class="fa-solid fa-file-export"></i>
                            <span>Custom Report</span>
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Export Options</h2>
                    </div>
                    <form class="form-grid" style="padding: 20px;">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Report Type</label>
                            <select>
                                <option>Student Records</option>
                                <option>Applicant Records</option>
                                <option>Enrollment Data</option>
                                <option>Course Statistics</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date From</label>
                            <input type="date">
                        </div>
                        <div class="form-group">
                            <label>Date To</label>
                            <input type="date">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Export Format</label>
                            <select>
                                <option>Excel (.xlsx)</option>
                                <option>CSV (.csv)</option>
                                <option>PDF (.pdf)</option>
                            </select>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <button type="button" class="btn-primary" onclick="alert('Exporting report...')">
                                <i class="fa-solid fa-download"></i> Export Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Recent Reports</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Report Name</th>
                                <th>Type</th>
                                <th>Generated By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Student Enrollment Report - 2nd Sem AY 2024-2025</td>
                                <td><span class="badge blue">Enrollment</span></td>
                                <td><?php echo htmlspecialchars($admin_data['username'] ?? 'Admin'); ?></td>
                                <td><?php echo date('M d, Y'); ?></td>
                                <td>
                                    <button class="btn-icon" title="Download"><i class="fa-solid fa-download"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>Applicant Statistics - January 2025</td>
                                <td><span class="badge gold">Applicants</span></td>
                                <td><?php echo htmlspecialchars($admin_data['username'] ?? 'Admin'); ?></td>
                                <td><?php echo date('M d, Y', strtotime('-2 days')); ?></td>
                                <td>
                                    <button class="btn-icon" title="Download"><i class="fa-solid fa-download"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>







