<?php
session_start();
include("../../php/connection.php");
include("../../php/applicant_functions.php");

$applicant_data = check_applicant_login($con);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PLM Applicant Portal</title>
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
            <a href="applicant_home.php" class="sidebar-link active">
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
            <a href="applicant_exam.php" class="sidebar-link">
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
                <h1>Application Dashboard</h1>
                <p>Welcome, <strong><?php echo htmlspecialchars($applicant_data['first_name'] ?? 'Applicant'); ?></strong>!</p>
            </div>

            <div class="status-banner <?php echo strtolower($applicant_data['application_status'] ?? 'pending'); ?>">
                <div class="status-icon">
                    <i class="fa-solid fa-<?php 
                        $status = $applicant_data['application_status'] ?? 'pending';
                        echo $status === 'approved' ? 'check-circle' : ($status === 'rejected' ? 'times-circle' : 'clock'); 
                    ?>"></i>
                </div>
                <div class="status-content">
                    <h3>Application Status: <?php echo htmlspecialchars(ucfirst($applicant_data['application_status'] ?? 'Pending')); ?></h3>
                    <p>Your application is currently being reviewed by the admissions office.</p>
                </div>
            </div>

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
                        <i class="fa-solid fa-file-check"></i>
                    </div>
                    <div class="card-content">
                        <h3>Documents Status</h3>
                        <p><?php echo ($applicant_data['documents_submitted'] ?? 0) ? 'Submitted' : 'Pending'; ?></p>
                    </div>
                </div>
            </div>

            <div class="content-grid">
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

                <div class="card">
                    <div class="card-header">
                        <h2>Application Timeline</h2>
                    </div>
                    <div class="timeline">
                        <div class="timeline-item completed">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h4>Application Submitted</h4>
                                <p><?php echo $applicant_data['created_at'] ? date('M d, Y', strtotime($applicant_data['created_at'])) : 'N/A'; ?></p>
                            </div>
                        </div>
                        <div class="timeline-item <?php echo ($applicant_data['documents_submitted'] ?? 0) ? 'completed' : ''; ?>">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h4>Documents Submitted</h4>
                                <p><?php echo ($applicant_data['documents_submitted'] ?? 0) ? 'Completed' : 'Pending'; ?></p>
                            </div>
                        </div>
                        <div class="timeline-item <?php echo ($applicant_data['exam_scheduled'] ?? 0) ? 'completed' : ''; ?>">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h4>Entrance Exam</h4>
                                <p><?php echo ($applicant_data['exam_date'] ?? null) ? date('M d, Y', strtotime($applicant_data['exam_date'])) : 'To be scheduled'; ?></p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h4>Final Decision</h4>
                                <p>Awaiting results</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Important Reminders</h2>
                    </div>
                    <div style="padding:0.25rem 0;">
                        <?php $ann_audience = 'applicants'; include('../../php/announcement_feed.php'); ?>
                    </div>
                </div>
            </div>

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
    </div>

    <script src="../../js/applicant.js"></script>
</body>
</html>








