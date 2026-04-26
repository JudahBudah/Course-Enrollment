<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status = 'pending'"))['c'];

// Get stats
$total_students = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM students"))['c'];
$total_classes = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM classes WHERE status = 'open'"))['c'];

// Flash messages
$flash = '';
if (isset($_GET['success'])) {
    $enrolled = $_GET['enrolled'] ?? 0;
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> Successfully enrolled ' . $enrolled . ' students!</div>';
}
if (isset($_GET['error'])) {
    $msgs = [
        'no_students' => 'No students found matching the criteria.',
        'no_classes' => 'No classes found for the selected criteria.',
        'missing_fields' => 'Please fill in all required fields.',
        'enroll_failed' => 'Failed to enroll students. Please try again.'
    ];
    $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> ' . ($msgs[$_GET['error']] ?? 'An error occurred.') . '</div>';
}

// Get available courses
$courses_query = mysqli_query($con, "SELECT DISTINCT course FROM students WHERE course IS NOT NULL AND course != '' ORDER BY course");
$courses = [];
while ($c = mysqli_fetch_assoc($courses_query)) $courses[] = $c['course'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Enrollment - PLM Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script>
        (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode');
        }
        })();
    </script>
    <link rel="stylesheet" href="../../css/admin.css">
    <style>
        .batch-container { max-width: 1000px; margin: 0 auto; }
        .batch-section { background: var(--gray-lt); padding: 2rem; border-radius: 8px; margin-bottom: 2rem; }
        .batch-section h3 { font-family: "DM Serif Display", serif; color: var(--gold); margin-bottom: 1.5rem; }
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .info-box { background: rgba(212,175,55,0.1); border-left: 3px solid var(--gold); padding: 1rem; margin: 1rem 0; color: rgba(242,243,242,0.8); font-size: 0.9rem; }
        .preview-box { background: var(--gray); padding: 1.5rem; border-radius: 6px; margin-top: 1.5rem; }
        .preview-box h4 { color: var(--gold); margin-bottom: 1rem; font-size: 0.95rem; }
        .preview-list { color: rgba(242,243,242,0.7); font-size: 0.85rem; line-height: 1.8; }
        .btn-batch { background: var(--gold); color: var(--navy-dk); padding: 0.75rem 2rem; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 1rem; width: 100%; }
        .btn-batch:hover { background: #c9a84a; }
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
        <a href="admin_students.php" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Students</span></a>
        <a href="admin_blocks.php" class="sidebar-link"><i class="fa-solid fa-layer-group"></i><span>Blocks</span></a>
        <a href="admin_faculty.php" class="sidebar-link"><i class="fa-solid fa-chalkboard-user"></i><span>Faculty</span></a>
        <a href="admin_subjects.php" class="sidebar-link"><i class="fa-solid fa-book"></i><span>Subjects</span></a>
        <a href="admin_classes.php" class="sidebar-link"><i class="fa-solid fa-door-open"></i><span>Classes</span></a>
        <a href="admin_enrollments.php" class="sidebar-link active"><i class="fa-solid fa-file-lines"></i><span>Enrollments</span></a>
        <a href="admin_announcements.php" class="sidebar-link"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
        <a href="../../php/admin_logout.php" class="sidebar-link logout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>Batch Enrollment</h1>
                <p>Enroll multiple students in classes by year level and semester</p>
            </div>
            <a href="admin_enrollments.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Enrollments</a>
        </div>

        <?php echo $flash; ?>

        <div class="batch-container">
            <!-- Stats -->
            <div class="stats-grid" style="margin-bottom: 2rem;">
                <div class="stat-card blue">
                    <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-content"><h3>Total Students</h3><p class="stat-number"><?php echo $total_students; ?></p></div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon"><i class="fa-solid fa-door-open"></i></div>
                    <div class="stat-content"><h3>Open Classes</h3><p class="stat-number"><?php echo $total_classes; ?></p></div>
                </div>
            </div>

            <!-- Batch Enrollment Form -->
            <div class="batch-section">
                <h3><i class="fa-solid fa-users-gear"></i> Batch Enroll Students</h3>
                
                <div class="info-box">
                    <i class="fa-solid fa-info-circle"></i> This will enroll all students matching the criteria into classes for the selected year level and semester.
                </div>

                <form method="POST" action="../../php/admin_batch_enroll.php" id="batchForm">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Course/Program <span style="color:var(--red)">*</span></label>
                            <select name="course" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo htmlspecialchars($course); ?>"><?php echo htmlspecialchars($course); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Year Level <span style="color:var(--red)">*</span></label>
                            <select name="year_level" required>
                                <option value="">Select Year</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Semester <span style="color:var(--red)">*</span></label>
                            <select name="semester" required>
                                <option value="">Select Semester</option>
                                <option value="1st">1st Semester</option>
                                <option value="2nd">2nd Semester</option>
                                <option value="summer">Summer</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>School Year <span style="color:var(--red)">*</span></label>
                            <input type="text" name="school_year" placeholder="e.g., 2024-2025" value="2024-2025" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="skip_enrolled" value="1" checked>
                            Skip students already enrolled in these classes
                        </label>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="regular_only" value="1">
                            Enroll regular students only (skip irregular students)
                        </label>
                    </div>

                    <div class="preview-box">
                        <h4><i class="fa-solid fa-eye"></i> What will happen:</h4>
                        <ul class="preview-list">
                            <li>System will find all students matching the selected course and year level</li>
                            <li>System will find all open classes for the selected year level and semester</li>
                            <li>Each student will be enrolled in all matching classes</li>
                            <li>Enrollment status will be set to "ongoing"</li>
                            <li>Class enrolled_count will be updated automatically</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn-batch" onclick="return confirm('Proceed with batch enrollment? This will enroll multiple students at once.')">
                        <i class="fa-solid fa-user-check"></i> Enroll Students
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
