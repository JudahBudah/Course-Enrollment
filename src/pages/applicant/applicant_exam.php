<?php
session_start();
include("../../php/connection.php");
include("../../php/applicant_functions.php");

$applicant_data = check_applicant_login($con);

// Fetch exam schedule if assigned
$exam = null;
if (!empty($applicant_data['exam_schedule_id'])) {
    $stmt = mysqli_prepare($con,
        "SELECT * FROM exam_schedules WHERE schedule_id = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 'i', $applicant_data['exam_schedule_id']);
    mysqli_stmt_execute($stmt);
    $exam = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

$initials  = strtoupper(
    substr($applicant_data['first_name'] ?? 'A', 0, 1) .
    substr($applicant_data['last_name']  ?? 'P', 0, 1)
);
$full_name = htmlspecialchars(
    trim(($applicant_data['first_name'] ?? '') . ' ' . ($applicant_data['last_name'] ?? ''))
);

$exam_result = strtolower($applicant_data['exam_result'] ?? '');

$guidelines = [
    ['Arrive Early',             'Be at the venue at least 30 minutes before the exam starts.'],
    ['Bring Valid ID',           'Bring at least one valid government-issued ID with photo.'],
    ['Required Materials',       'Bring pencils (No. 2), eraser, and sharpener. Calculator not allowed.'],
    ['Dress Code',               'Wear decent and comfortable clothing. Avoid wearing caps or hats.'],
    ['No Electronic Devices',    'Mobile phones and other electronic devices are strictly prohibited.'],
];

$coverage = [
    ['fa-book',       'English Proficiency',  'Grammar, vocabulary, reading comprehension'],
    ['fa-calculator', 'Mathematics',          'Algebra, geometry, trigonometry, statistics'],
    ['fa-flask',      'Science',              'Biology, chemistry, physics, earth science'],
    ['fa-brain',      'Abstract Reasoning',   'Logical thinking and pattern recognition'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Schedule – PLM Applicant Portal</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/applicant/applicant_main.css">
    <link rel="stylesheet" href="../../css/applicant/applicant_exam.css">
</head>
<body>

<!-- ── Top Nav ─────────────────────────────────────────── -->
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
            <div class="acc-name"><?php echo $full_name; ?></div>
            <div class="acc-initials"><?php echo $initials; ?></div>
        </div>

    </div>

    <!-- ── Side Nav ──────────────────────────────────────── -->
    <nav class="main-nav" id="navMenu">
        <div class="nav-wrapper">
            <ul class="main-ul">
                <li>
                    <a href="applicant_home.php">
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
                    <a href="applicant_exam.php" class="active">
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

        <div class="page-header">
            <h1>Entrance Examination</h1>
            <p>View your exam schedule and status</p>
        </div>

        <!-- Exam Status Card -->
        <?php if ($exam): ?>
            <div class="exam-card scheduled">
                <div class="exam-header">
                    <i class="fa-solid fa-calendar-check"></i>
                    <h2>Your Exam is Scheduled</h2>
                </div>
                <div class="exam-details">
                    <div class="exam-detail-item">
                        <i class="fa-solid fa-calendar"></i>
                        <div>
                            <label>Exam Date</label>
                            <p><?php echo date('F d, Y', strtotime($exam['exam_date'])); ?></p>
                        </div>
                    </div>
                    <div class="exam-detail-item">
                        <i class="fa-solid fa-clock"></i>
                        <div>
                            <label>Exam Time</label>
                            <p><?php echo htmlspecialchars($exam['exam_time']); ?></p>
                        </div>
                    </div>
                    <div class="exam-detail-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <div>
                            <label>Venue</label>
                            <p><?php echo htmlspecialchars($exam['location']); ?></p>
                        </div>
                    </div>
                    <?php if (!empty($exam['notes'])): ?>
                    <div class="exam-detail-item">
                        <i class="fa-solid fa-circle-info"></i>
                        <div>
                            <label>Additional Notes</label>
                            <p><?php echo nl2br(htmlspecialchars($exam['notes'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="exam-card pending">
                <div class="exam-header">
                    <i class="fa-solid fa-clock"></i>
                    <h2>Exam Schedule Pending</h2>
                </div>
                <p>Your entrance examination schedule will be sent to your email once your documents have been reviewed and approved.</p>
            </div>
        <?php endif; ?>

        <!-- Guidelines & Coverage -->
        <div class="content-grid">

            <div class="card">
                <div class="card-header">
                    <h2>Exam Guidelines</h2>
                </div>
                <div class="guidelines-list">
                    <?php foreach ($guidelines as $i => [$title, $desc]): ?>
                    <div class="guideline-item">
                        <span class="guideline-number"><?php echo $i + 1; ?></span>
                        <div>
                            <h4><?php echo htmlspecialchars($title); ?></h4>
                            <p><?php echo htmlspecialchars($desc); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Exam Coverage</h2>
                </div>
                <div class="coverage-list">
                    <?php foreach ($coverage as [$icon, $title, $desc]): ?>
                    <div class="coverage-item">
                        <i class="fa-solid <?php echo $icon; ?>"></i>
                        <div>
                            <h4><?php echo htmlspecialchars($title); ?></h4>
                            <p><?php echo htmlspecialchars($desc); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div><!-- /.content-grid -->

        <!-- Exam Results -->
        <div class="card">
            <div class="card-header">
                <h2>Exam Results Status</h2>
            </div>
            <div class="results-status">
                <?php if ($applicant_data['exam_taken'] ?? 0): ?>
                    <?php if ($exam_result): ?>
                        <div class="result-card <?php echo $exam_result; ?>">
                            <i class="fa-solid fa-<?php echo $exam_result === 'passed' ? 'circle-check' : 'circle-xmark'; ?>"></i>
                            <h3>You <?php echo ucfirst($exam_result); ?> the Entrance Exam</h3>
                            <p>Score: <?php echo htmlspecialchars($applicant_data['exam_score'] ?? 'N/A'); ?>%</p>
                        </div>
                    <?php else: ?>
                        <div class="result-pending">
                            <i class="fa-solid fa-hourglass-half"></i>
                            <h3>Results Pending</h3>
                            <p>Your exam results are being processed. Please check back later.</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="result-not-taken">
                        <i class="fa-solid fa-clipboard-question"></i>
                        <h3>Exam Not Yet Taken</h3>
                        <p>Results will be available after you complete the entrance examination.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="card">
            <div class="card-header">
                <h2>Contact Information</h2>
            </div>
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fa-solid fa-envelope"></i>
                    <div>
                        <label>Email</label>
                        <p>admissions@plm.edu.ph</p>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fa-solid fa-phone"></i>
                    <div>
                        <label>Phone</label>
                        <p>(02) 8643-2500</p>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fa-solid fa-location-dot"></i>
                    <div>
                        <label>Address</label>
                        <p>General Luna corner Muralla St., Intramuros, Manila</p>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div><!-- /.main-flex -->

<script src="../../js/applicant/applicant_main.js"></script>
</body>
</html>