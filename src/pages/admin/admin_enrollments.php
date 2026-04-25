<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);

$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status = 'pending'"))['c'];

// Stats
$total_enrolled = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(DISTINCT student_id) as c FROM enrollments WHERE status NOT IN ('dropped', 'cancelled')"))['c'];

$cur_semester    = get_setting($con, 'current_semester', '1st');
$cur_school_year = get_setting($con, 'current_school_year', date('Y') . '-' . (date('Y') + 1));
$sem_labels      = ['1st' => '1st Sem', '2nd' => '2nd Sem', 'summer' => 'Summer'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollments - PLM Admin</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
    <link rel="stylesheet" href="../../css/admin/admin_enrollments.css">
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

                    <!-- Student Records Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="student-records-dropdown">
                            <i class="fa-solid fa-user-graduate"></i>
                            <span class="li-name chev-space">
                                Student Records
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="student-records-menu">
                            <ul>
                                <li><a href="admin_students.php">Students</a></li>
                                <li><a href="admin_enrollments.php">Enrollments</a></li>
                                <li>
                                    <a href="admin_drop_requests.php">
                                        Drop Requests
                                        <?php if (!empty($GLOBALS['pending_drops'])): ?>
                                            <span class="sidebar-badge"><?php echo $GLOBALS['pending_drops']; ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Academic Records Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="acad-records-dropdown">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <span class="li-name chev-space">
                                Academic Records
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="acad-records-menu">
                            <ul>
                                <li><a href="admin_subjects.php">Subjects</a></li>
                                <li><a href="admin_classes.php">Classes</a></li>
                                <li><a href="admin_blocks.php">Blocks</a></li>
                            </ul>
                        </div>
                    </li>

                    <!-- Personnel Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="personnel-dropdown">
                            <i class="fa-solid fa-users-gear"></i>
                            <span class="li-name chev-space">
                                Personnel
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="personnel-menu">
                            <ul>
                                <li><a href="admin_faculty.php">Faculty</a></li>
                                <?php if (($admin_data['role'] ?? 'admin') === 'superadmin'): ?>
                                    <li><a href="admin_accounts.php">Admin Accounts</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>

                    <!-- Communications Dropdown -->
                    <li class="course-dropdown">
                        <a href="#" id="comms-dropdown">
                            <i class="fa-solid fa-bullhorn"></i>
                            <span class="li-name chev-space">
                                Communications
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>
                        </a>
                        <div class="acad-dropdown-menu" id="comms-menu">
                            <ul>
                                <li><a href="admin_announcements.php">Announcements</a></li>
                                <li><a href="admin_calendar.php">Calendar</a></li>
                            </ul>
                        </div>
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

                <?php if (isset($_GET['success'])): ?>
                    <?php $sm = ['drop_accepted' => 'Drop request approved.', 'drop_rejected' => 'Drop request rejected.']; ?>
                    <div class="success-message"><i class="fa-solid fa-check-circle"></i> <?php echo $sm[$_GET['success']] ?? 'Done.'; ?></div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-icon"><i class="fa-solid fa-user-check"></i></div>
                        <div class="stat-content">
                            <h3>Enrolled Students</h3>
                            <p class="stat-number"><?php echo number_format($total_enrolled); ?></p>
                        </div>
                    </div>
                    <div class="stat-card navy">
                        <div class="stat-icon"><i class="fa-solid fa-calendar"></i></div>
                        <div class="stat-content" style="display:flex;gap:1.5rem;align-items:center;flex:1;">
                            <div style="flex:1;border-right:1px solid var(--off);padding-right:1.5rem;">
                                <h3>Enrollment Period</h3>
                                <p class="stat-number" style="font-size:1.4rem;"><?php echo htmlspecialchars($sem_labels[$cur_semester] ?? $cur_semester); ?></p>
                            </div>
                            <div style="flex:1;">
                                <h3>School Year</h3>
                                <p class="stat-number" style="font-size:1.4rem;">AY <?php echo htmlspecialchars($cur_school_year); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Drop Requests -->
                <?php
                $drop_reqs = mysqli_query($con, "
                    SELECT e.enrollment_id, e.class_id, e.student_id,
                        st.first_name, st.last_name, st.student_number,
                        s.subject_code, s.subject_name, s.units,
                        c.section, c.schedule_day, c.schedule_time
                    FROM enrollments e
                    JOIN students st ON e.student_id = st.student_id
                    JOIN classes c ON e.class_id = c.class_id
                    JOIN subjects s ON c.subject_id = s.subject_id
                    WHERE e.status = 'drop_requested'
                    ORDER BY st.last_name, st.first_name
                ");
                if (mysqli_num_rows($drop_reqs) > 0):
                ?>
                <div class="card" style="margin-bottom:1.5rem;border-left:4px solid #dc2626;">
                    <div class="card-header" style="background:#dc2626;">
                        <h2><i class="fa-solid fa-right-from-bracket"></i> Pending Drop Requests</h2>
                    </div>

                    <div class="admin-drop-table-wrapper">
                        <div class="admin-drop-table">

                            <div class="admin-drop-table-header">
                                <div class="admin-drop-col-left">Student</div>
                                <div>Student No.</div>
                                <div class="admin-drop-col-left">Subject</div>
                                <div>Section</div>
                                <div>Schedule</div>
                                <div>Units</div>
                                <div>Action</div>
                            </div>

                            <div class="admin-drop-table-body">
                            <?php while ($dr = mysqli_fetch_assoc($drop_reqs)): ?>
                            <div class="admin-drop-row">
                                <div class="admin-drop-col-left"><?php echo htmlspecialchars($dr['first_name'] . ' ' . $dr['last_name']); ?></div>
                                <div><?php echo htmlspecialchars($dr['student_number']); ?></div>
                                <div class="admin-drop-col-left"><strong><?php echo htmlspecialchars($dr['subject_code']); ?></strong> — <?php echo htmlspecialchars($dr['subject_name']); ?></div>
                                <div><?php echo htmlspecialchars($dr['section'] ?? 'TBA'); ?></div>
                                <div><?php echo htmlspecialchars(($dr['schedule_day'] ?? '') . ' ' . ($dr['schedule_time'] ?? '')); ?></div>
                                <div><?php echo $dr['units']; ?></div>
                                <div>
                                    <form method="POST" action="../../php/handle_drop_request_v2.php" style="display:inline;">
                                        <input type="hidden" name="student_id" value="<?php echo $dr['student_id']; ?>">
                                        <input type="hidden" name="enrollment_id" value="<?php echo $dr['enrollment_id']; ?>">
                                        <input type="hidden" name="class_id" value="<?php echo $dr['class_id']; ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <input type="hidden" name="redirect" value="enrollments">
                                        <button type="submit" class="btn-icon" style="background:#16a34a;color:#fff;" title="Approve" onclick="return confirm('Approve this drop request?')"><i class="fa-solid fa-check"></i></button>
                                    </form>
                                    <form method="POST" action="../../php/handle_drop_request_v2.php" style="display:inline;">
                                        <input type="hidden" name="student_id" value="<?php echo $dr['student_id']; ?>">
                                        <input type="hidden" name="enrollment_id" value="<?php echo $dr['enrollment_id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="redirect" value="enrollments">
                                        <button type="submit" class="btn-icon danger" title="Reject" onclick="return confirm('Reject this drop request?')"><i class="fa-solid fa-xmark"></i></button>
                                    </form>
                                    <a href="admin_manual_enroll.php?student_id=<?php echo $dr['student_id']; ?>" class="btn-icon" title="View Student">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                            </div>

                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Enrollments Table -->
                <div class="card">
                    <div class="card-header">
                        <h2>Enrollment Records</h2>
                        <div class="search-bar-wrap">
                            <i class="fa-solid fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Search students...">
                        </div>
                    </div>

                    <div class="enrollment-table-wrapper">
                        <div class="enrollment-table">

                            <div class="enrollment-table-header">
                                <div>Student ID</div>
                                <div class="enrollment-col-left">Name</div>
                                <div class="enrollment-col-left">Course</div>
                                <div>Year Level</div>
                                <div>Block</div>
                                <div>Enrolled Subjects</div>
                                <div>Total Units</div>
                                <div>Actions</div>
                            </div>

                            <div class="enrollment-table-body" id="enrollmentTable">
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
                                        "SELECT COUNT(*) as c FROM enrollments WHERE student_id = $sid AND status NOT IN ('dropped', 'cancelled')"
                                    ));
                                    $enrolled_count = $enr['c'] ?? 0;

                                    $uq = mysqli_fetch_assoc(mysqli_query($con,
                                        "SELECT SUM(s.units) as t
                                        FROM enrollments e
                                        JOIN classes c ON e.class_id = c.class_id
                                        JOIN subjects s ON c.subject_id = s.subject_id
                                        WHERE e.student_id = $sid AND e.status NOT IN ('dropped', 'cancelled')"
                                    ));
                                    $total_units = $uq['t'] ?? 0;
                                ?>
                                <div class="enrollment-row">
                                    <div><?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></div>
                                    <div class="enrollment-col-left"><?php echo htmlspecialchars(trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''))); ?></div>
                                    <div class="enrollment-col-left"><?php echo htmlspecialchars($student['course'] ?? 'N/A'); ?></div>
                                    <div><?php echo htmlspecialchars($student['year_level'] ?? 'N/A'); ?></div>
                                    <div>
                                        <?php if (!empty($student['block_name'])): ?>
                                            <span class="badge blue"><?php echo htmlspecialchars($student['block_name']); ?></span>
                                        <?php else: ?>
                                            <span class="badge incomplete">No Block</span>
                                        <?php endif; ?>
                                    </div>
                                    <div><span class="enroll-count"><?php echo $enrolled_count; ?> subjects</span></div>
                                    <div><span class="enroll-units"><?php echo $total_units; ?> units</span></div>
                                    <div>
                                        <div class="action-buttons">
                                            <a href="admin_manual_enroll.php?student_id=<?php echo $sid; ?>"
                                            class="btn-icon" title="View/Edit Enrollment">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>

                        </div>
                    </div>
                </div>

            </div><!-- /.main-content -->
        </main>
    </div><!-- /.main-flex -->

    <script src="../../js/admin/admin_main.js"></script>
    <script src="../../js/admin/admin_enrollments.js"></script>
</body>
</html>