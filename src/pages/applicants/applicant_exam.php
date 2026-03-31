<?php
session_start();
include("../../php/connection.php");
include("../../php/applicant_functions.php");

$applicant_data = check_applicant_login($con);

// Fetch exam schedule if assigned
$exam = null;
if (!empty($applicant_data['exam_schedule_id'])) {
    $stmt = mysqli_prepare($con, "SELECT * FROM exam_schedules WHERE schedule_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $applicant_data['exam_schedule_id']);
    mysqli_stmt_execute($stmt);
    $exam = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Schedule - PLM Applicant Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/applicant.css">
</head>
<body class="dashboard">
    <nav class="dashboard-nav">
        <div class="nav-brand">
            <img src="../../assets/plm-logo.png" alt="PLM">
            <span>PLM Applicant Portal</span>
        </div>
        <div class="nav-user">
            <span><?php echo htmlspecialchars(($applicant_data['first_name'] ?? '') . ' ' . ($applicant_data['last_name'] ?? '')); ?></span>
            <div class="user-avatar"><?php echo strtoupper(substr($applicant_data['first_name'] ?? 'A', 0, 1) . substr($applicant_data['last_name'] ?? 'P', 0, 1)); ?></div>
        </div>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <a href="applicant_home.php" class="sidebar-link">
                <i class="fa-solid fa-house"></i>
                <span>Dashboard</span>
            </a>
            <a href="applicant_apply.php" class="sidebar-link">
                <i class="fa-solid fa-file-pen"></i>
                <span>Application Form</span>
            </a>
            <a href="applicant_submit.php" class="sidebar-link">
                <i class="fa-solid fa-file-arrow-up"></i>
                <span>Submit Documents</span>
            </a>
            <a href="applicant_exam.php" class="sidebar-link active">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Exam Schedule</span>
            </a>
            <a href="../../php/applicant_logout.php" class="sidebar-link logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Entrance Examination</h1>
                <p>View your exam schedule and status</p>
            </div>

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

            <div class="content-grid">
                <div class="card">
                    <div class="card-header">
                        <h2>Exam Guidelines</h2>
                    </div>
                    <div class="guidelines-list">
                        <div class="guideline-item">
                            <span class="guideline-number">1</span>
                            <div>
                                <h4>Arrive Early</h4>
                                <p>Be at the venue at least 30 minutes before the exam starts.</p>
                            </div>
                        </div>
                        <div class="guideline-item">
                            <span class="guideline-number">2</span>
                            <div>
                                <h4>Bring Valid ID</h4>
                                <p>Bring at least one valid government-issued ID with photo.</p>
                            </div>
                        </div>
                        <div class="guideline-item">
                            <span class="guideline-number">3</span>
                            <div>
                                <h4>Required Materials</h4>
                                <p>Bring pencils (No. 2), eraser, and sharpener. Calculator not allowed.</p>
                            </div>
                        </div>
                        <div class="guideline-item">
                            <span class="guideline-number">4</span>
                            <div>
                                <h4>Dress Code</h4>
                                <p>Wear decent and comfortable clothing. Avoid wearing caps or hats.</p>
                            </div>
                        </div>
                        <div class="guideline-item">
                            <span class="guideline-number">5</span>
                            <div>
                                <h4>No Electronic Devices</h4>
                                <p>Mobile phones and other electronic devices are strictly prohibited.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Exam Coverage</h2>
                    </div>
                    <div class="coverage-list">
                        <div class="coverage-item">
                            <i class="fa-solid fa-book"></i>
                            <div>
                                <h4>English Proficiency</h4>
                                <p>Grammar, vocabulary, reading comprehension</p>
                            </div>
                        </div>
                        <div class="coverage-item">
                            <i class="fa-solid fa-calculator"></i>
                            <div>
                                <h4>Mathematics</h4>
                                <p>Algebra, geometry, trigonometry, statistics</p>
                            </div>
                        </div>
                        <div class="coverage-item">
                            <i class="fa-solid fa-flask"></i>
                            <div>
                                <h4>Science</h4>
                                <p>Biology, chemistry, physics, earth science</p>
                            </div>
                        </div>
                        <div class="coverage-item">
                            <i class="fa-solid fa-brain"></i>
                            <div>
                                <h4>Abstract Reasoning</h4>
                                <p>Logical thinking and pattern recognition</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Exam Results Status</h2>
                </div>
                <div class="results-status">
                    <?php if ($applicant_data['exam_taken'] ?? 0): ?>
                        <?php if ($applicant_data['exam_result'] ?? null): ?>
                            <div class="result-card <?php echo strtolower($applicant_data['exam_result']); ?>">
                                <i class="fa-solid fa-<?php echo ($applicant_data['exam_result'] === 'passed') ? 'check-circle' : 'times-circle'; ?>"></i>
                                <h3>You <?php echo htmlspecialchars(ucfirst($applicant_data['exam_result'])); ?> the Entrance Exam</h3>
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
    </div>

    <script src="../../js/applicant.js"></script>
</body>
</html>








