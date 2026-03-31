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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PLM</title>
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
            <a href="admin_home.php" class="sidebar-link active">
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
                <h1>Dashboard Overview</h1>
                <p>Welcome back, <strong><?php echo htmlspecialchars($admin_data['first_name'] ?? 'Admin'); ?></strong>!</p>
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
                        <h3>Applicants</h3>
                        <p class="stat-number"><?php echo number_format($total_applicants); ?></p>
                        <?php if ($pending_applicants > 0): ?>
                            <small><?php echo $pending_applicants; ?> pending</small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="stat-card red">
                    <div class="stat-icon">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Faculty Members</h3>
                        <p class="stat-number"><?php echo number_format($total_faculty); ?></p>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fa-solid fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Subjects</h3>
                        <p class="stat-number"><?php echo number_format($total_subjects); ?></p>
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-icon">
                        <i class="fa-solid fa-door-open"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Active Classes</h3>
                        <p class="stat-number"><?php echo number_format($total_classes); ?></p>
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

            <div class="content-grid">
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Applicants</h2>
                        <a href="admin_applicants.php" class="link-small">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Program</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recent_applicants = mysqli_query($con, "SELECT * FROM applicants ORDER BY created_at DESC LIMIT 5");
                                while ($applicant = mysqli_fetch_assoc($recent_applicants)):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(($applicant['first_name'] ?? '') . ' ' . ($applicant['last_name'] ?? 'N/A')); ?></td>
                                    <td><?php echo htmlspecialchars($applicant['email'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($applicant['first_choice'] ?? 'N/A'); ?></td>
                                    <td><span class="badge <?php echo strtolower($applicant['application_status'] ?? 'incomplete'); ?>"><?php echo htmlspecialchars(ucfirst($applicant['application_status'] ?? 'Incomplete')); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($applicant['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Quick Actions</h2>
                    </div>
                    <div class="quick-actions">
                        <a href="admin_applicants.php" class="action-btn">
                            <i class="fa-solid fa-user-check"></i>
                            <span>Review Applicants</span>
                        </a>
                        <a href="admin_students.php?action=add" class="action-btn">
                            <i class="fa-solid fa-user-plus"></i>
                            <span>Add New Student</span>
                        </a>
                        <a href="admin_subjects.php?action=add" class="action-btn">
                            <i class="fa-solid fa-book-medical"></i>
                            <span>Add New Subject</span>
                        </a>
                        <a href="admin_classes.php?action=add" class="action-btn">
                            <i class="fa-solid fa-plus-circle"></i>
                            <span>Create New Class</span>
                        </a>
                        <a href="admin_announcements.php?action=add" class="action-btn">
                            <i class="fa-solid fa-bullhorn"></i>
                            <span>Post Announcement</span>
                        </a>
                        <a href="admin_reports.php" class="action-btn">
                            <i class="fa-solid fa-file-export"></i>
                            <span>Generate Reports</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>System Activity</h2>
                </div>
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon blue">
                            <i class="fa-solid fa-user-plus"></i>
                        </div>
                        <div class="activity-content">
                            <p><strong>New applicant registered</strong></p>
                            <small>2 hours ago</small>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon green">
                            <i class="fa-solid fa-check-circle"></i>
                        </div>
                        <div class="activity-content">
                            <p><strong>Student enrollment approved</strong></p>
                            <small>5 hours ago</small>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon gold">
                            <i class="fa-solid fa-book"></i>
                        </div>
                        <div class="activity-content">
                            <p><strong>New subject added to curriculum</strong></p>
                            <small>1 day ago</small>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon red">
                            <i class="fa-solid fa-bullhorn"></i>
                        </div>
                        <div class="activity-content">
                            <p><strong>Announcement posted</strong></p>
                            <small>2 days ago</small>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../../js/admin.js"></script>
</body>
</html>







