<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);

$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status = 'pending'"))['c'];

// Stats
$total_students = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM students"))['c'];
$total_enrolled = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(DISTINCT student_id) as c FROM enrollments WHERE status = 'enrolled'"))['c'];
$pending_enrollment = $total_students - $total_enrolled;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollments - PLM Admin</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
    <link rel="stylesheet" href="../../css/admin/admin_enrollments.css">
</head>
<body>

    <!-- ── Top Nav Bar ────────────────────────────────── -->
    <header>
        <div class="nav-section">
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
                        <a href="admin_home.php">
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
                        <a href="admin_enrollments.php" class="active">
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
                    <h1>Enrollment Management</h1>
                    <p>Manage student enrollments for current semester</p>
                </div>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-icon"><i class="fa-solid fa-user-check"></i></div>
                        <div class="stat-content">
                            <h3>Enrolled Students</h3>
                            <p class="stat-number"><?php echo number_format($total_enrolled); ?></p>
                        </div>
                    </div>
                    <div class="stat-card gold">
                        <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
                        <div class="stat-content">
                            <h3>Pending Enrollment</h3>
                            <p class="stat-number"><?php echo number_format($pending_enrollment); ?></p>
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

                <!-- Enrollments Table -->
                <div class="card">
                    <div class="card-header">
                        <h2>Enrollment Records</h2>
                        <div class="search-bar-wrap">
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
                                $students = mysqli_query($con, "
                                    SELECT s.*, b.block_name
                                    FROM students s
                                    LEFT JOIN blocks b ON s.block_id = b.block_id
                                    ORDER BY s.student_id DESC
                                ");
                                while ($student = mysqli_fetch_assoc($students)):
                                    $sid = (int)$student['student_id'];

                                    $enr = mysqli_fetch_assoc(mysqli_query($con,
                                        "SELECT COUNT(*) as c FROM enrollments WHERE student_id = $sid AND status = 'enrolled'"
                                    ));
                                    $enrolled_count = $enr['c'] ?? 0;

                                    $uq = mysqli_fetch_assoc(mysqli_query($con,
                                        "SELECT SUM(s.units) as t
                                         FROM enrollments e
                                         JOIN classes c ON e.class_id = c.class_id
                                         JOIN subjects s ON c.subject_id = s.subject_id
                                         WHERE e.student_id = $sid AND e.status = 'enrolled'"
                                    ));
                                    $total_units = $uq['t'] ?? 0;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars(trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''))); ?></td>
                                    <td><?php echo htmlspecialchars($student['course'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($student['year_level'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if (!empty($student['block_name'])): ?>
                                            <span class="badge blue"><?php echo htmlspecialchars($student['block_name']); ?></span>
                                        <?php elseif (!empty($student['block_id'])): ?>
                                            <span class="badge no-block">No Block</span>
                                        <?php else: ?>
                                            <span class="badge incomplete">Irregular</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="enroll-count"><?php echo $enrolled_count; ?> subjects</span></td>
                                    <td><span class="enroll-units"><?php echo $total_units; ?> units</span></td>
                                    <td>
                                        <a href="admin_manual_enroll.php?student_id=<?php echo $sid; ?>"
                                           class="btn-icon" title="View/Edit Enrollment">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div><!-- /.main-content -->
        </main>
    </div><!-- /.main-flex -->

    <script src="../../js/admin/admin_main.js"></script>
    <script src="../../js/admin/admin_enrollments.js"></script>
</body>
</html>