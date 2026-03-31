<?php
session_start();
include("../../php/connection.php");
include("../../php/applicant_functions.php");

$applicant_data = check_applicant_login($con);

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $applicant_id = $applicant_data['applicant_id'];
    
    // Handle file uploads
    $upload_dir = "uploads/applicants/" . $applicant_id . "/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $files_uploaded = true;
    
    // Update documents_submitted status
    $stmt = mysqli_prepare($con, "UPDATE applicants SET documents_submitted = 1 WHERE applicant_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $applicant_id);
    mysqli_stmt_execute($stmt);
    
    $success = "Documents submitted successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Documents - PLM Applicant Portal</title>
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
            <a href="applicant_submit.php" class="sidebar-link active">
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
                <h1>Submit Required Documents</h1>
                <p>Upload all required documents for your application</p>
            </div>

            <?php if (isset($success)): ?>
                <div class="success-message">
                    <i class="fa-solid fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($applicant_data['documents_submitted'] ?? 0): ?>
                <div class="info-message">
                    <i class="fa-solid fa-info-circle"></i>
                    You have already submitted your documents. You can re-upload if needed.
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="documents-form">
                <div class="card">
                    <div class="card-header">
                        <h2>Required Documents</h2>
                    </div>

                    <div class="documents-list">
                        <div class="document-item">
                            <div class="document-info">
                                <i class="fa-solid fa-file-pdf"></i>
                                <div>
                                    <h4>Report Card (Form 138)</h4>
                                    <p>Senior High School Report Card</p>
                                </div>
                            </div>
                            <div class="document-upload">
                                <input type="file" name="form138" id="form138" accept=".pdf,.jpg,.jpeg,.png">
                                <label for="form138" class="upload-btn">
                                    <i class="fa-solid fa-upload"></i> Choose File
                                </label>
                            </div>
                        </div>

                        <div class="document-item">
                            <div class="document-info">
                                <i class="fa-solid fa-file-pdf"></i>
                                <div>
                                    <h4>PSA Issued Birth Certificate</h4>
                                    <p>Original or certified true copy</p>
                                </div>
                            </div>
                            <div class="document-upload">
                                <input type="file" name="birth_cert" id="birth_cert" accept=".pdf,.jpg,.jpeg,.png">
                                <label for="birth_cert" class="upload-btn">
                                    <i class="fa-solid fa-upload"></i> Choose File
                                </label>
                            </div>
                        </div>

                        <div class="document-item">
                            <div class="document-info">
                                <i class="fa-solid fa-file-pdf"></i>
                                <div>
                                    <h4>Good Moral Character</h4>
                                    <p>Certificate from previous school</p>
                                </div>
                            </div>
                            <div class="document-upload">
                                <input type="file" name="good_moral" id="good_moral" accept=".pdf,.jpg,.jpeg,.png">
                                <label for="good_moral" class="upload-btn">
                                    <i class="fa-solid fa-upload"></i> Choose File
                                </label>
                            </div>
                        </div>

                        <div class="document-item">
                            <div class="document-info">
                                <i class="fa-solid fa-file-pdf"></i>
                                <div>
                                    <h4>OUR Waiver and Notice of Undertaking</h4>
                                    <p>OUR AU001 Form</p>
                                </div>
                            </div>
                            <div class="document-upload">
                                <input type="file" name="our_au001" id="our_au001" accept=".pdf,.jpg,.jpeg,.png">
                                <label for="our_au001" class="upload-btn">
                                    <i class="fa-solid fa-upload"></i> Choose File
                                </label>
                            </div>
                        </div>

                        <div class="document-item">
                            <div class="document-info">
                                <i class="fa-solid fa-file-pdf"></i>
                                <div>
                                    <h4>OUR Admission Data & Personal Information</h4>
                                    <p>OUR AU002 Form</p>
                                </div>
                            </div>
                            <div class="document-upload">
                                <input type="file" name="our_au002" id="our_au002" accept=".pdf,.jpg,.jpeg,.png">
                                <label for="our_au002" class="upload-btn">
                                    <i class="fa-solid fa-upload"></i> Choose File
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Important Notes</h2>
                    </div>
                    <div class="notes-list">
                        <div class="note-item">
                            <i class="fa-solid fa-circle-check"></i>
                            <p>All documents must be clear and readable</p>
                        </div>
                        <div class="note-item">
                            <i class="fa-solid fa-circle-check"></i>
                            <p>Accepted formats: PDF, JPG, PNG (Max 5MB per file)</p>
                        </div>
                        <div class="note-item">
                            <i class="fa-solid fa-circle-check"></i>
                            <p>Ensure all information is visible and not cut off</p>
                        </div>
                        <div class="note-item">
                            <i class="fa-solid fa-circle-check"></i>
                            <p>Submit all documents within 7 days of application</p>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>Submit Documents</span>
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script src="../../js/applicant.js"></script>
</body>
</html>








