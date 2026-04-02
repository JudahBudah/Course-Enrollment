<?php
session_start();
include("../../php/connection.php");
include("../../php/applicant_functions.php");

$applicant_data = check_applicant_login($con);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $applicant_id = $applicant_data['applicant_id'];

    $upload_dir = "uploads/applicants/{$applicant_id}/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $stmt = mysqli_prepare($con,
        "UPDATE applicants SET documents_submitted = 1 WHERE applicant_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $applicant_id);
    mysqli_stmt_execute($stmt);

    $success = "Documents submitted successfully!";
    $applicant_data = check_applicant_login($con);
}

$initials  = strtoupper(
    substr($applicant_data['first_name'] ?? 'A', 0, 1) .
    substr($applicant_data['last_name']  ?? 'P', 0, 1)
);
$full_name = htmlspecialchars(
    trim(($applicant_data['first_name'] ?? '') . ' ' . ($applicant_data['last_name'] ?? ''))
);

$documents = [
    [
        'name'  => 'form138',
        'label' => 'Report Card (Form 138)',
        'desc'  => 'Senior High School Report Card',
    ],
    [
        'name'  => 'birth_cert',
        'label' => 'PSA Issued Birth Certificate',
        'desc'  => 'Original or certified true copy',
    ],
    [
        'name'  => 'good_moral',
        'label' => 'Good Moral Character',
        'desc'  => 'Certificate from previous school',
    ],
    [
        'name'  => 'our_au001',
        'label' => 'OUR Waiver and Notice of Undertaking',
        'desc'  => 'OUR AU001 Form',
    ],
    [
        'name'  => 'our_au002',
        'label' => 'OUR Admission Data & Personal Information',
        'desc'  => 'OUR AU002 Form',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Documents – PLM Applicant Portal</title>
    <link rel="icon" href="../../assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/applicant/applicant_main.css">
    <link rel="stylesheet" href="../../css/applicant/applicant_submit.css">
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
                    <a href="applicant_submit.php" class="active">
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
            <h1>Submit Required Documents</h1>
            <p>Upload all required documents for your application</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="success-message">
                <i class="fa-solid fa-circle-check"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($applicant_data['documents_submitted'] ?? 0): ?>
            <div class="info-message">
                <i class="fa-solid fa-circle-info"></i>
                You have already submitted your documents. You can re-upload if needed.
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <!-- Required Documents -->
            <div class="card">
                <div class="card-header">
                    <h2>Required Documents</h2>
                </div>

                <div class="documents-list">
                    <?php foreach ($documents as $doc): ?>
                    <div class="document-item">
                        <div class="document-info">
                            <i class="fa-solid fa-file-pdf"></i>
                            <div>
                                <h4><?php echo htmlspecialchars($doc['label']); ?></h4>
                                <p><?php echo htmlspecialchars($doc['desc']); ?></p>
                            </div>
                        </div>
                        <div class="document-upload">
                            <input type="file"
                                   name="<?php echo $doc['name']; ?>"
                                   id="<?php echo $doc['name']; ?>"
                                   accept=".pdf,.jpg,.jpeg,.png">
                            <label for="<?php echo $doc['name']; ?>" class="upload-btn">
                                <i class="fa-solid fa-upload"></i>
                                Choose File
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Important Notes -->
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
</div><!-- /.main-flex -->

<script src="../../js/applicant/applicant_main.js"></script>
<script src="../../js/applicant/applicant_submit.js"></script>
</body>
</html>