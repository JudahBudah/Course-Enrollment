<?php
session_start();
include("../../php/connection.php");
include("../../php/admin_functions.php");

$admin_data = check_admin_login($con);
$pending_applicants = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as c FROM applicants WHERE application_status = 'pending'"))['c'];

$flash = '';
if (isset($_GET['success'])) {
    $count = $_GET['count'] ?? 0;
    $flash = '<div class="success-message"><i class="fa-solid fa-check-circle"></i> Successfully imported ' . $count . ' subjects!</div>';
}
if (isset($_GET['error'])) {
    $msgs = [
        'no_data' => 'No subjects data provided.',
        'invalid_format' => 'Invalid data format. Please check your input.',
        'import_failed' => 'Failed to import subjects. Please try again.'
    ];
    $flash = '<div class="error-message"><i class="fa-solid fa-circle-exclamation"></i> ' . ($msgs[$_GET['error']] ?? 'An error occurred.') . '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Import Subjects - PLM Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin.css">
    <style>
        .import-container { max-width: 1200px; margin: 0 auto; }
        .import-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .import-section { background: var(--gray-lt); padding: 1.5rem; border-radius: 8px; }
        .import-section h3 { font-family: 'Playfair Display', serif; color: var(--gold); margin-bottom: 1rem; }
        .import-textarea { width: 100%; min-height: 400px; background: var(--gray); border: 1px solid rgba(212,175,55,0.2); color: var(--white); padding: 1rem; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 0.85rem; resize: vertical; }
        .format-example { background: var(--gray); padding: 1rem; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 0.8rem; color: rgba(242,243,242,0.7); margin-top: 1rem; overflow-x: auto; }
        .format-example pre { margin: 0; white-space: pre; }
        .help-text { color: rgba(242,243,242,0.5); font-size: 0.85rem; margin-top: 0.5rem; }
        .btn-import { background: var(--gold); color: var(--navy-dk); padding: 0.75rem 2rem; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 1rem; }
        .btn-import:hover { background: #c9a84a; }
        @media (max-width: 900px) { .import-grid { grid-template-columns: 1fr; } }
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
        <a href="admin_subjects.php" class="sidebar-link active"><i class="fa-solid fa-book"></i><span>Subjects</span></a>
        <a href="admin_classes.php" class="sidebar-link"><i class="fa-solid fa-door-open"></i><span>Classes</span></a>
        <a href="admin_enrollments.php" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>Enrollments</span></a>
        <a href="admin_announcements.php" class="sidebar-link"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
        <a href="admin_reports.php" class="sidebar-link"><i class="fa-solid fa-chart-bar"></i><span>Reports</span></a>
        <a href="../../php/admin_logout.php" class="sidebar-link logout"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>Batch Import Subjects</h1>
                <p>Import multiple subjects from curriculum data</p>
            </div>
            <a href="admin_subjects.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Subjects</a>
        </div>

        <?php echo $flash; ?>

        <div class="import-container">
            <div class="import-grid">
                <div class="import-section">
                    <h3><i class="fa-solid fa-upload"></i> Import Data</h3>
                    <form method="POST" action="../../php/admin_subjects_batch_import.php">
                        <div class="form-group">
                            <label>Paste Curriculum Data</label>
                            <p class="help-text">Copy and paste subject data in the format shown on the right →</p>
                            <textarea name="subjects_data" class="import-textarea" placeholder="CODE|NAME|UNITS|LEC|LAB|DEPT|YEAR|SEM|PREREQ" required></textarea>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="skip_duplicates" value="1" checked>
                                Skip duplicate subject codes
                            </label>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="set_active" value="1" checked>
                                Set all imported subjects as active
                            </label>
                        </div>

                        <button type="submit" class="btn-import">
                            <i class="fa-solid fa-file-import"></i> Import Subjects
                        </button>
                    </form>
                </div>

                <div class="import-section">
                    <h3><i class="fa-solid fa-info-circle"></i> Format Guide</h3>
                    <p class="help-text">Each line should contain subject data separated by pipes (|):</p>
                    <div class="format-example">
                        <pre>CODE|NAME|UNITS|LEC|LAB|DEPT|YEAR|SEM|PREREQ</pre>
                    </div>

                    <p class="help-text" style="margin-top: 1.5rem;"><strong>Example:</strong></p>
                    <div class="format-example">
<pre>STS 0002|Science, Technology and Society|3|3|0|General Education|1|1st|
ICC 0101|Introduction to Computing|3|2|1|Information Technology|1|1st|
ICC 0102|Fundamentals of Programming|3|2|1|Information Technology|1|1st|ICC 0101</pre>
                    </div>

                    <p class="help-text" style="margin-top: 1.5rem;"><strong>Field Descriptions:</strong></p>
                    <ul style="color: rgba(242,243,242,0.7); font-size: 0.85rem; line-height: 1.8;">
                        <li><strong>CODE:</strong> Subject code (e.g., ICC 0101)</li>
                        <li><strong>NAME:</strong> Subject name</li>
                        <li><strong>UNITS:</strong> Credit units (1-9)</li>
                        <li><strong>LEC:</strong> Lecture hours (0-9)</li>
                        <li><strong>LAB:</strong> Lab hours (0-9)</li>
                        <li><strong>DEPT:</strong> Department name</li>
                        <li><strong>YEAR:</strong> Year level (1-4)</li>
                        <li><strong>SEM:</strong> Semester (1st, 2nd, summer)</li>
                        <li><strong>PREREQ:</strong> Prerequisites (optional)</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
