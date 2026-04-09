<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
include("../../php/connection.php");
include("../../php/applicant_functions.php");

$applicant_data = check_applicant_login($con);

// Fetch joined exam schedule data
$exam = null;
if (!empty($applicant_data['exam_schedule_id'])) {
    $es = mysqli_prepare($con, "SELECT exam_date, exam_time, location FROM exam_schedules WHERE schedule_id = ? LIMIT 1");
    mysqli_stmt_bind_param($es, 'i', $applicant_data['exam_schedule_id']);
    mysqli_stmt_execute($es);
    $exam = mysqli_fetch_assoc(mysqli_stmt_get_result($es));
}

// Avatar initials fallback
$initials = strtoupper(
    substr($applicant_data['first_name'] ?? 'A', 0, 1) .
    substr($applicant_data['last_name']  ?? 'P', 0, 1)
);

$full_name = htmlspecialchars(
    trim(($applicant_data['first_name'] ?? '') . ' ' . ($applicant_data['last_name'] ?? ''))
);

$status = strtolower($applicant_data['application_status'] ?? 'incomplete');
$status_icon = match($status) {
    'approved'       => 'circle-check',
    'rejected'       => 'circle-xmark',
    'pending'        => 'clock',
    'pending_review' => 'clock',
    default          => 'pen-to-square',
};
$status_message = match($status) {
    'incomplete'     => 'Your application form is incomplete. Please fill out all required fields.',
    'pending'        => 'Your application has been submitted and is currently under review by the admissions office.',
    'pending_review' => 'Your application is currently being reviewed by the admissions office.',
    'approved'       => 'Congratulations! Your application has been approved.',
    'rejected'       => 'Unfortunately, your application has been rejected. Please contact the admissions office for more information.',
    default          => 'Your application is being processed.',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – PLM Applicant Portal</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/applicant/applicant_main.css">
    <link rel="stylesheet" href="../../css/applicant/applicant_home.css">
    <link rel="stylesheet" href="../../css/plm_loader.css">
    <script>window.addEventListener('pageshow',function(e){if(e.persisted){document.documentElement.style.visibility='hidden';window.location.reload();}});</script>
</head>
<body>

<!-- Loading Screen -->
<div id="plm-loader">
    <div id="plm-loader-bar"></div>
    <div class="plm-loader-logo">
        <img src="../../assets/plm-logo.png" alt="PLM">
        <div class="plm-loader-name">
            <p>PLM</p>
            <p>Pamantasan ng Lungsod ng Maynila</p>
        </div>
        <div class="plm-loader-dots">
            <span></span><span></span><span></span>
        </div>
        <p class="plm-loader-status" id="plm-loader-status">Loading...</p>
    </div>
</div>

<!-- ── Top Nav ─────────────────────────────────────────── -->
<header>
    <div class="nav-section">

        <!-- Mobile toggle -->
        <button class="nav-button" id="navButton">
            <i class="fa-solid fa-bars" id="trans-bars"></i>
        </button>

        <!-- Logo -->
        <div class="logo-container">
            <img src="../../assets/plm-logo.png" alt="PLM Logo" loading="lazy">
            <div class="title-container">
                <div class="logo-title">PAMANTASAN NG LUNGSOD NG MAYNILA</div>
                <div class="logo-sub">University of the City of Manila</div>
            </div>
        </div>

        <!-- Account display -->
        <div class="acc-display-container">
            <div class="acc-name"><?php echo $full_name; ?></div>
            <div class="acc-initials"><?php echo $initials; ?></div>
        </div>

    </div>

    <!-- ── Side Nav ──────────────────────────────────────── -->
    <nav class="main-nav" id="navMenu">
        <div class="nav-wrapper">
            <ul class="main-ul">
                <li>
                    <a href="applicant_home.php" class="active">
                        <i class="fa-solid fa-house"></i>
                        <span class="li-name">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="applicant_apply.php">
                        <i class="fa-solid fa-file-pen"></i>
                        <span class="li-name">Application Form</span>
                    </a>
                </li>
                <li>
                    <a href="applicant_submit.php">
                        <i class="fa-solid fa-file-arrow-up"></i>
                        <span class="li-name">Submit Documents</span>
                    </a>
                </li>
                <li>
                    <a href="applicant_exam.php">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span class="li-name">Exam Schedule</span>
                    </a>
                </li>
                <li>
                    <a href="../../php/applicant_logout.php" class="logout-link">
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


<!-- ── Page Body ───────────────────────────────────────── -->
<div class="main-flex">
    <div class="spacer"></div>

    <main>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Application Dashboard</h1>
            <p>Welcome, <strong><?php echo htmlspecialchars($applicant_data['first_name'] ?? 'Applicant'); ?></strong>!</p>
        </div>

        <!-- Status Banner -->
        <div class="status-banner <?php echo $status; ?>">
            <div class="status-icon">
                <i class="fa-solid fa-<?php echo $status_icon; ?>"></i>
            </div>
            <div class="status-content">
                <h3>Application Status: <?php echo ucfirst($status); ?></h3>
                <p><?php echo $status_message; ?></p>
            </div>
        </div>

        <!-- Info Cards -->
        <div class="cards-grid">
            <div class="info-card">
                <div class="card-icon blue">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <div class="card-content">
                    <h3>LRN</h3>
                    <p><?php echo htmlspecialchars($applicant_data['lrn'] ?? 'N/A'); ?></p>
                </div>
            </div>

            <div class="info-card">
                <div class="card-icon gold">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div class="card-content">
                    <h3>First Choice Program</h3>
                    <p><?php echo htmlspecialchars($applicant_data['first_choice'] ?? 'N/A'); ?></p>
                </div>
            </div>

            <div class="info-card">
                <div class="card-icon red">
                    <i class="fa-solid fa-calendar"></i>
                </div>
                <div class="card-content">
                    <h3>Application Date</h3>
                    <p><?php echo $applicant_data['created_at'] ? date('M d, Y', strtotime($applicant_data['created_at'])) : 'N/A'; ?></p>
                </div>
            </div>

            <div class="info-card">
                <div class="card-icon green">
                    <i class="fa-solid fa-file-circle-check"></i>
                </div>
                <div class="card-content">
                    <h3>Documents Status</h3>
                    <p><?php echo ($applicant_data['documents_submitted'] ?? 0) ? 'Submitted' : 'Pending'; ?></p>
                </div>
            </div>
        </div>

        <!-- Two-column section -->
        <div class="content-grid">

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h2>Quick Actions</h2>
                </div>
                <div class="quick-actions">
                    <a href="applicant_apply.php" class="action-btn">
                        <i class="fa-solid fa-file-pen"></i>
                        <span>Fill Application Form</span>
                    </a>
                    <a href="applicant_submit.php" class="action-btn">
                        <i class="fa-solid fa-file-arrow-up"></i>
                        <span>Submit Documents</span>
                    </a>
                    <a href="applicant_exam.php" class="action-btn">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span>View Exam Schedule</span>
                    </a>
                </div>
            </div>

            <!-- Application Timeline -->
            <div class="card">
                <div class="card-header">
                    <h2>Application Timeline</h2>
                </div>
                <div class="timeline">
                    <?php
                        $app_submitted  = !in_array($status, ['incomplete', '']);
                        $docs_submitted = !empty($applicant_data['documents_submitted']);
                        $exam_assigned  = !empty($exam);
                        $final_done     = in_array($status, ['approved', 'rejected', 'enrolled']);
                    ?>
                    <div class="timeline-item <?php echo $app_submitted ? 'completed' : ''; ?>">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h4>Application Submitted</h4>
                            <p><?php echo $app_submitted ? date('M d, Y', strtotime($applicant_data['updated_at'] ?? $applicant_data['created_at'])) : 'Not yet submitted'; ?></p>
                        </div>
                    </div>
                    <div class="timeline-item <?php echo $docs_submitted ? 'completed' : ''; ?>">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h4>Documents Submitted</h4>
                            <p><?php echo $docs_submitted ? 'Completed' : 'Pending'; ?></p>
                        </div>
                    </div>
                    <div class="timeline-item <?php echo $exam_assigned ? 'completed' : ''; ?>">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h4>Entrance Exam</h4>
                            <p><?php echo $exam_assigned ? date('M d, Y', strtotime($exam['exam_date'])) . ' · ' . htmlspecialchars($exam['exam_time']) . ' · ' . htmlspecialchars($exam['location']) : 'To be scheduled'; ?></p>
                        </div>
                    </div>
                    <div class="timeline-item <?php echo $final_done ? 'completed' : ''; ?>">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h4>Final Decision</h4>
                            <p><?php echo $final_done ? ucfirst($status) : 'Awaiting results'; ?></p>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.content-grid -->

        <!-- Program Choices -->
        <div class="card">
            <div class="card-header">
                <h2>Program Choices</h2>
            </div>
            <div class="choices-list">
                <div class="choice-item">
                    <span class="choice-number">1st Choice</span>
                    <span class="choice-program"><?php echo htmlspecialchars($applicant_data['first_choice'] ?? 'N/A'); ?></span>
                </div>
                <?php if (!empty($applicant_data['second_choice'])): ?>
                <div class="choice-item">
                    <span class="choice-number">2nd Choice</span>
                    <span class="choice-program"><?php echo htmlspecialchars($applicant_data['second_choice']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($applicant_data['third_choice'])): ?>
                <div class="choice-item">
                    <span class="choice-number">3rd Choice</span>
                    <span class="choice-program"><?php echo htmlspecialchars($applicant_data['third_choice']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div><!-- /.main-flex -->

<script src="../../js/applicant/applicant_main.js"></script>
<script src="../../js/no_cache.js"></script>
<script src="../../js/plm_loader.js"></script>
</body>
</html>