<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);

// Get statistics
$total_students     = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM students"))['count'];
$total_applicants   = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM applicants"))['count'];
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM applicants WHERE application_status = 'pending'"))['count'];
$total_faculty      = 0; // Faculty table not yet created
$total_subjects     = 0; // Subjects table not yet created
$total_classes      = 0; // Classes table not yet created
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PLM Admin</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
    <link rel="stylesheet" href="../../css/admin/admin_home.css">
</head>
<body>

    <!-- ── Top Nav Bar ────────────────────────────────── -->
    <header>
        <div class="nav-section">
            <!-- Mobile toggle -->
            <button class="nav-button" id="navButton">
                <i class="fa-solid fa-bars" id="trans-bars"></i>
            </button>

            <div class="logo-container">
                <img src="../../assets/plm-logo.png" alt="PLM Logo" loading="lazy">
                <div class="title-container">
                    <div class="logo-title">PAMANTASAN NG LUNGSOD NG MAYNILA</div>
                    <div class="logo-sub">University of the City of Manila</div>
                </div>
            </div>

            <div class="acc-display-container">
                <div class="acc-name">
                    <?php echo htmlspecialchars($admin_data['username'] ?? 'Admin'); ?>
                </div>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($admin_data['username'] ?? 'A', 0, 1)); ?>
                </div>
            </div>
        </div>

        <!-- ── Side Nav ───────────────────────────────── -->
        <nav class="main-nav" id="navMenu">
            <div class="nav-wrapper">
                <ul class="main-ul">
                    <li>
                        <a href="admin_home.php" class="active">
                            <i class="fa-solid fa-house"></i>
                            <span class="li-name">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_applicants.php">
                            <i class="fa-solid fa-user-plus"></i>
                            <span class="li-name">Applicants</span>
                            <?php if ($pending_applicants > 0): ?>
                                <span class="sidebar-badge li-name"><?php echo $pending_applicants; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="admin_students.php">
                            <i class="fa-solid fa-users"></i>
                            <span class="li-name">Students</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_blocks.php">
                            <i class="fa-solid fa-layer-group"></i>
                            <span class="li-name">Blocks</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_faculty.php">
                            <i class="fa-solid fa-chalkboard-user"></i>
                            <span class="li-name">Faculty</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_subjects.php">
                            <i class="fa-solid fa-book"></i>
                            <span class="li-name">Subjects</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_classes.php">
                            <i class="fa-solid fa-door-open"></i>
                            <span class="li-name">Classes</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_enrollments.php">
                            <i class="fa-solid fa-file-lines"></i>
                            <span class="li-name">Enrollments</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_announcements.php">
                            <i class="fa-solid fa-bullhorn"></i>
                            <span class="li-name">Announcements</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_calendar.php">
                            <i class="fa-solid fa-calendar-days"></i>
                            <span class="li-name">Calendar</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_accounts.php">
                            <i class="fa-solid fa-user-shield"></i>
                            <span class="li-name">Admin Accounts</span>
                        </a>
                    </li>
                    <li>
                        <a href="../../php/admin_logout.php" class="logout-bg">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span class="li-name">Logout</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Dark Mode Toggle -->
            <div class="drk-mode-container">
                <div class="drk-label">
                    <i class="fa-solid fa-moon" id="modeIcon"></i>
                    <span class="li-name" id="modeLabel">Dark Mode</span>
                </div>
                <div class="toggle-track li-name" id="toggleTrack">
                    <div class="toggle-thumb"></div>
                </div>
            </div>
        </nav>
    </header>

    <!-- ── Page Body ──────────────────────────────────── -->
    <div class="main-flex">
        <div class="spacer"></div>

        <main>
            <div class="main-content">

                <div class="page-header">
                    <h1>Dashboard Overview</h1>
                    <p>Welcome back, <strong><?php echo htmlspecialchars($admin_data['first_name'] ?? 'Admin'); ?></strong>!</p>
                </div>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="stat-content">
                            <h3>Total Students</h3>
                            <p class="stat-number"><?php echo number_format($total_students); ?></p>
                        </div>
                    </div>
                    <div class="stat-card gold">
                        <div class="stat-icon"><i class="fa-solid fa-user-plus"></i></div>
                        <div class="stat-content">
                            <h3>Applicants</h3>
                            <p class="stat-number"><?php echo number_format($total_applicants); ?></p>
                            <?php if ($pending_applicants > 0): ?>
                                <small><?php echo $pending_applicants; ?> pending</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-card red">
                        <div class="stat-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                        <div class="stat-content">
                            <h3>Faculty Members</h3>
                            <p class="stat-number"><?php echo number_format($total_faculty); ?></p>
                        </div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-icon"><i class="fa-solid fa-book"></i></div>
                        <div class="stat-content">
                            <h3>Subjects</h3>
                            <p class="stat-number"><?php echo number_format($total_subjects); ?></p>
                        </div>
                    </div>
                    <div class="stat-card purple">
                        <div class="stat-icon"><i class="fa-solid fa-door-open"></i></div>
                        <div class="stat-content">
                            <h3>Active Classes</h3>
                            <p class="stat-number"><?php echo number_format($total_classes); ?></p>
                        </div>
                    </div>
                    <div class="stat-card navy">
                        <div class="stat-icon"><i class="fa-solid fa-calendar"></i></div>
                        <div class="stat-content">
                            <h3>Current Semester</h3>
                            <p class="stat-number">2nd Sem</p>
                            <small>AY 2024-2025</small>
                        </div>
                    </div>
                </div>

                <!-- Content grid: recent applicants + quick actions -->
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
                                    $recent = mysqli_query($con, "SELECT * FROM applicants ORDER BY created_at DESC LIMIT 5");
                                    while ($row = mysqli_fetch_assoc($recent)):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? 'N/A')); ?></td>
                                        <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['first_choice'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge <?php echo strtolower($row['application_status'] ?? 'incomplete'); ?>">
                                                <?php echo htmlspecialchars(ucfirst($row['application_status'] ?? 'Incomplete')); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
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

                <!-- System Activity -->
                <div class="card">
                    <div class="card-header">
                        <h2>System Activity</h2>
                    </div>
                    <div class="activity-list">
                        <div class="activity-item">
                            <div class="activity-icon blue"><i class="fa-solid fa-user-plus"></i></div>
                            <div class="activity-content">
                                <p><strong>New applicant registered</strong></p>
                                <small>2 hours ago</small>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon green"><i class="fa-solid fa-check-circle"></i></div>
                            <div class="activity-content">
                                <p><strong>Student enrollment approved</strong></p>
                                <small>5 hours ago</small>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon gold"><i class="fa-solid fa-book"></i></div>
                            <div class="activity-content">
                                <p><strong>New subject added to curriculum</strong></p>
                                <small>1 day ago</small>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon red"><i class="fa-solid fa-bullhorn"></i></div>
                            <div class="activity-content">
                                <p><strong>Announcement posted</strong></p>
                                <small>2 days ago</small>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /.main-content -->
        </main>
    </div><!-- /.main-flex -->

    <script src="../../js/admin/admin_main.js"></script>
    <script src="../../js/admin/admin_home.js"></script>
</body>
</html>